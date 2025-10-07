<?php

namespace Drupal\header_media_slider;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

class HeaderMediaSlider {

  const FIELD_NAME_ROOT_PARAGRAPH = 'field_mediaslider';

  const FIELD_NAME_SLIDES = 'field_slide';

  const FIELD_NAME_SLIDE_HEADLINE = 'field_slide_headline';

  const FIELD_NAME_SLIDE_CATEGORY = 'field_slide_category';

  const FIELD_NAME_SLIDE_MEDIA = 'field_slide_media';

  const FIELD_NAME_MEDIA_IMAGE = 'field_media_image';

  const FIELD_NAME_MEDIA_VIDEO = 'field_media_video_file';

  const FIELD_NAME_MEDIA_REMOTE_VIDEO = 'field_media_oembed_video';

  /**
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  private $fileUrlGenerator;

  public function __construct() {
    $this->fileUrlGenerator = \Drupal::service('file_url_generator');
  }

  /**
   * Get Slides
   *
   * @param $root_paragraph_id
   *   The paragraph entity id.
   *
   * @return array
   */
  public function getSlides($root_paragraph_id): array {

    $slides = [];

    try {

      // Handle root paragraph.
      $root_paragraph_entity = Paragraph::load($root_paragraph_id);

      // Handle slides:
      if (!$root_paragraph_entity->hasField(static::FIELD_NAME_SLIDES)) {
        throw new \Exception("Missing slides field in root paragraph, did you name it '" . static::FIELD_NAME_SLIDES . "'?");
      }

      $slide_paragraphs = $root_paragraph_entity->get(static::FIELD_NAME_SLIDES)
        ->getValue();
      foreach ($slide_paragraphs as $slide_paragraph) {
        $slide_paragraph_entity = Paragraph::load($slide_paragraph['target_id']);

        // Get translated paragraph entity.
        /** @var Node $translation */
        $slide_paragraph_entity = \Drupal::service('entity.repository')->getTranslationFromContext($slide_paragraph_entity);
        
        // Set headline.
        if (!$slide_paragraph_entity->hasField(static::FIELD_NAME_SLIDE_HEADLINE)) {
          throw new \Exception("Missing slides headline field in slide paragraph, did you name it '" . static::FIELD_NAME_SLIDE_HEADLINE . "'?");
        }
        $slide_headline = $slide_paragraph_entity->get(static::FIELD_NAME_SLIDE_HEADLINE)
          ->getString();

        // Set category.
        if (!$slide_paragraph_entity->hasField(static::FIELD_NAME_SLIDE_CATEGORY)) {
          throw new \Exception("Missing slides category field in slide paragraph, did you name it '" . static::FIELD_NAME_SLIDE_CATEGORY . "'?");
        }
        $slide_category = $slide_paragraph_entity->get(static::FIELD_NAME_SLIDE_CATEGORY)
          ->getString();

        // Get media entity.
        if (!$slide_paragraph_entity->hasField(static::FIELD_NAME_SLIDE_MEDIA)) {
          throw new \Exception("Missing slides content field in slide paragraph, did you name it '" . static::FIELD_NAME_SLIDE_MEDIA . "'?");
        }

        /** @var $slide_media_entity \Drupal\media\Entity\Media */
        $slide_media_entity = $slide_paragraph_entity->get(static::FIELD_NAME_SLIDE_MEDIA)[0]->entity;
        if (!$slide_media_entity instanceof \Drupal\media\Entity\Media) {
          throw new \Exception("Slide media entity is NULL");
        }

        // Set media type.
        $slide_media_type = $slide_media_entity->bundle();

        /*
        dsm($slide_media_type, 'slide_media_type');
        dsm($slide_text_align, 'slide_text_align');
        dsm($slide_vertical_align, 'slide_vertical_align');
        dsm($slide_headline, 'slide_headline');
        dsm($slide_content, 'slide_content');
        */

        // Handle different media types.
        switch ($slide_media_type) {

          // Image.
          case 'image':

            // Load image file.
            $slide_file_id = $slide_media_entity->get(static::FIELD_NAME_MEDIA_IMAGE)[0]->getValue()['target_id'];
            $slide_file = File::load($slide_file_id);
            $slide_media_uri = $slide_file->getFileUri();
            // dsm($slide_media_uri, 'slide_media_uri');

            // Add Slide.
            $slides[] = [
              'headline' => !empty($slide_headline) ? nl2br($slide_headline) : NULL,
              'category' => !empty($slide_category) ? $slide_category : NULL,
              'media' => [
                'type' => $slide_media_type,
                'uri' => $slide_media_uri,
                'url' => $this->fileUrlGenerator->generateAbsoluteString($slide_media_uri),
                // 'style_url' => $image_style->buildUrl($uri),
                'responsive_image' => $this->getResponsiveImage($slide_file),
              ],
            ];

            break;

          // Self hosted HTML video.
          case 'video':

            $slide_file_id = $slide_media_entity->get(static::FIELD_NAME_MEDIA_VIDEO)[0]->getValue()['target_id'];
            $slide_file = File::load($slide_file_id);

            $slides[] = [
              'headline' => !empty($slide_headline) ? nl2br($slide_headline) : NULL,
              'category' => !empty($slide_category) ? $slide_category : NULL,
              'media' => [
                'type' => 'video_html',
                'url' => $slide_file->createFileUrl(),
              ],
            ];
            break;

          // Remove video (youtube / vimeo).
          case 'remote_video':

            // continue 2;

            $remote_video_url = $slide_media_entity->get(static::FIELD_NAME_MEDIA_REMOTE_VIDEO)[0]->getValue()['value'];
            // dsm($remote_video_url, "remote_video_url");

            $remote_video_source = 'vimeo';
            if (strpos($remote_video_url, 'https://youtu.be') !== FALSE) {
              $remote_video_source = 'youtube';
            }

            // Add Slide.
            $slides[] = [
              'headline' => !empty($slide_headline) ? nl2br($slide_headline) : NULL,
              'category' => !empty($slide_category) ? $slide_category : NULL,
              'media' => [
                'type' => 'video_' . $remote_video_source,
                'url' => trim($remote_video_url),
              ],
            ];

            break;

          default:
            // All other formats are not supported.
            continue 2;
          // throw new \Exception("Media type '$slide_media_type' is not supported");
        }
      }

    } catch (\Exception $e) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        dsm('Catch exception:' . $e->getMessage());
      }
      $slides = [];
    }

    return $slides;
  }

  /**
   * Get slider height.
   *
   * @param $root_paragraph_id
   *   The paragraph entity id.
   *
   * @return string
   */
  public function getSliderHeight($root_paragraph_id): string {
    return 'medium';
  }

  /**
   * Get responsive image.
   *
   * @param \Drupal\file\Entity\File $file
   *
   * @return array
   */
  private function getResponsiveImage(File $file): array {

    // The image.factory service will check if our image is valid.
    $image = \Drupal::service('image.factory')->get($file->getFileUri());

    return [
      '#theme' => 'responsive_image',
      '#width' => $image->isValid() ? $image->getWidth() : NULL,
      '#height' => $image->isValid() ? $image->getHeight() : NULL,
      '#responsive_image_style_id' => 'header_media_slider_image',
      '#uri' => $file->getFileUri(),
    ];
  }
}
