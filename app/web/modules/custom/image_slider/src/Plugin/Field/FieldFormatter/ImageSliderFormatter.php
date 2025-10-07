<?php

namespace Drupal\image_slider\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\node\Entity\Node;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;


/**
 * Image Slider Formatter.
 *
 * @FieldFormatter(
 *   id = "image_slider",
 *   label = @Translation("Image Slider"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageSliderFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // Remove 'image_link' from default settings.
    $default_settings = parent::defaultSettings();
    unset($default_settings['image_link']);

    return [
        'image_style_lightbox' => '',
      ] + $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Remove 'image_link'.
    unset($element['image_link']);

    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );

    // Lightbox image style.
    $element['image_style_lightbox'] = [
      '#title' => t('Lightbox image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style_lightbox'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles'),
        ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.

    // Lightbox image style.
    $image_style_setting = $this->getSetting('image_style_lightbox');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Lightbox image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Lightbox: Original image');
    }

    return array_merge($summary, parent::settingsSummary());
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // dsm($items);

    // Get lightbox image style.
    $image_style_lightbox_setting = $this->getSetting('image_style_lightbox');
    $image_style = NULL;
    if (!empty($image_style_lightbox_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_lightbox_setting);
    }

    foreach ($elements as &$element) {

      // Get image item.
      /** @var ImageItem $item */
      $item = $element['#item'];
      $item_value = $item->getValue();
      if (empty($item_value['target_id'])) {
        continue;
      }

      // Load file.
      $file = File::load($item_value['target_id']);
      if (!$file) {
        continue;
      }

      // Unset #url property.
      unset($element['#url']);

      // Add lightbox_url to image.
      if ($image_style) {
        // Use Image style for lightbox.
        $image_uri = $file->getFileUri();
        $element['#lightbox_url'] = $image_style->buildUrl($image_uri);
      } else {
        // Use original image for lightbox.
        $element['#lightbox_url'] = $file->createFileUrl(FALSE);
      }
    }

    return [
      '#theme' => 'image_slider_formatter',
      '#items' => $elements,
      '#attached' => [
        'library' => [
          'image_slider/image_slider_formatter',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $style_ids = [
      $this->getSetting('image_style_lightbox'),
    ];
    foreach ($style_ids as $style_id) {
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        // If this formatter uses a valid image style to display the image, add
        // the image style configuration entity as dependency of this formatter.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    $style_ids = [
      $this->getSetting('image_style_lightbox'),
    ];
    foreach ($style_ids as $style_id) {
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
          // If a valid replacement has been provided in the storage, replace the
          // image style with the replacement and signal that the formatter plugin
          // settings were updated.
          if ($replacement_id && ImageStyle::load($replacement_id)) {
            $this->setSetting($style_id, $replacement_id);
            $changed = TRUE;
          }
        }
      }
    }

    return $changed;
  }
}
