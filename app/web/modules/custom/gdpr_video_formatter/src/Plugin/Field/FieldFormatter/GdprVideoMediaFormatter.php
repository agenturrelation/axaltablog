<?php

namespace Drupal\gdpr_video_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the GdprVideoMediaFormatter formatter.
 *
 * @FieldFormatter(
 *   id = "gdr_video_media_formatter",
 *   module = "gdr_video_formatter",
 *   label = @Translation("GDPR Video"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class GdprVideoMediaFormatter extends OEmbedFormatter {

  const FIELD_NAME_DESCRIPTION = 'field_media_description';
  const FIELD_NAME_UPLOAD_DATE = 'field_media_upload_date';

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   *  Constructs a GdprVideoMediaFormatter object.
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper, EntityStorageInterface $responsive_image_style_storage, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $messenger, $resource_fetcher, $url_resolver, $logger_factory, $config_factory, $iframe_url_helper);

    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('entity_type.manager')->getStorage('image_style'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'responsive_image_style' => '',
        'max_width' => 0,
        'max_height' => 0,
      ] + parent::defaultSettings();
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Add responsive image style.
    $responsive_image_options = [
      '' => t('- None -'),
    ];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    uasort($responsive_image_styles, '\Drupal\responsive_image\Entity\ResponsiveImageStyle::sort');
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    return parent::settingsForm($form, $form_state) + [
        'responsive_image_style' => [
          '#title' => t('Responsive image style'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('responsive_image_style') ?: NULL,
          '#required' => FALSE,
          '#options' => $responsive_image_options,
        ],
      ];
  }


  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('responsive_image_style') && !empty($this->getSetting('responsive_image_style'))) {
      $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
      $summary[] = $this->t('Responsive Image Style: %style', [
        '%style' => $responsive_image_style->label(),
      ]);
    } else {
      $summary[] = $this->t('Responsive Image Style: %style', [
        '%style' =>t('- None -'),
      ]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $max_width = $this->getSetting('max_width');
    $max_height = $this->getSetting('max_height');

    foreach ($items as $delta => $item) {
      $main_property = $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
      $value = $item->{$main_property};

      if (empty($value)) {
        continue;
      }

      try {
        $resource_url = $this->urlResolver->getResourceUrl($value, $max_width, $max_height);
        $resource = $this->resourceFetcher->fetchResource($resource_url);
      }
      catch (ResourceException $exception) {
        $this->logger->error("Could not retrieve the remote URL (@url).", ['@url' => $value]);
        continue;
      }

      if ($resource->getType() === Resource::TYPE_VIDEO && $resource->getProvider()->getName() === 'YouTube') {
        try {
          // Get module config.
          $module_config = \Drupal::config('gdpr_video_formatter.settings');

          // Get thumbnail from parent entity.
          /** @var Media $entity */
          $entity = $item->getEntity();
          $thumbnail = $entity->get('thumbnail')->getValue()[0];
          // dsm($thumbnail);
          $thumbnail_file = File::load($thumbnail['target_id']);
          if (!$thumbnail_file instanceof File) {
            $this->logger->error("Remote video thumbnail file with ID @id is not available in database.", ['@id' => $thumbnail['target_id']]);
            continue;
          }

          // Build youtube url.
          $youtube_url = $value;

          // Get video ID from youtube url.
          $video_id = $this->parseYouTubeVideoId($youtube_url);
          if (empty($video_id)) {
            continue;
          }
          // dsm($video_id, 'video_id');

          // Set video title.
          $title = $resource->getTitle();
          if ($title) {
            $element[$delta]['#attributes']['title'] = $title;
          }

          // Set description.
          $description = NULL;
          if ($entity->hasField(self::FIELD_NAME_DESCRIPTION)) {
            $description = $entity->get(self::FIELD_NAME_DESCRIPTION)->getString();
          }

          // Set upload date.
          $upload_date = NULL;
          if ($entity->hasField(self::FIELD_NAME_UPLOAD_DATE)) {
            $upload_date = $entity->get(self::FIELD_NAME_UPLOAD_DATE)->getString();
          }

          // Load responsive image style.
          $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));

          $element[$delta] = [
            '#theme' => 'gdpr_video_formatter',
            '#provider_name' => $resource->getProvider()->getName(),
            '#max_width' => $thumbnail['width'],
            '#video_id' => $video_id,
            '#video_title' => $title,
            '#video_description' => $description,
            '#video_upload_date' => $upload_date,
            '#video_width' => $max_width ?: $resource->getWidth(),
            '#video_height' => $max_height ?: $resource->getHeight(),
            '#thumbnail_image' => [
              '#theme' => 'image',
              '#width' => $thumbnail['width'],
              '#height' => $thumbnail['height'],
              '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
              '#uri' => $thumbnail_file->getFileUri(),
            ],
            '#thumbnail_responsive_image' => [
              '#theme' => 'responsive_image',
              '#width' => $thumbnail['width'],
              '#height' => $thumbnail['height'],
              '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
              '#uri' => $thumbnail_file->getFileUri(),
            ],
            '#thumbnail_image_url' => $thumbnail_file->createFileUrl(FALSE),
            '#consent_text' => new FormattableMarkup($module_config->get('consent_text_youtube'), []),
            '#attached' => [
              'library' => [
                'gdpr_video_formatter/gdpr_video_formatter',
              ],
              'drupalSettings' => [
                'gdpr_video_formatter' => [
                  'videoID' => $video_id,
                ]
              ]
            ],
          ];

          CacheableMetadata::createFromObject($resource)
            ->addCacheTags($this->config->getCacheTags())
            ->applyTo($element[$delta]);
        }
        catch (ResourceException $exception) {
          $this->logger->error("Could not retrieve the remote URL (@url).", ['@url' => $value]);
          continue;
        }
      }
    }

    return $element;
  }

  /**
   * Returns the YouTube video ID from YouTube url.
   * @param string $url
   *
   * @return string
   */
  private function parseYouTubeVideoId(string $url): string {
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    return $match[1];
  }
}
