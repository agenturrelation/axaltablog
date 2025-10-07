<?php

namespace Drupal\header_media_slider\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\header_media_slider\HeaderMediaSlider;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Plugin implementation of the 'Header Media Slider' formatter.
 *
 * @FieldFormatter(
 *   id = "header_media_slider",
 *   label = @Translation("Header Media Slider"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class HeaderMediaSliderFormatter extends EntityReferenceFormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // Get HeaderMediaSlider instance.
    $headerMediaSlider = new HeaderMediaSlider();
    $elements = [];


    // Use slider attached as field value:
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // dsm("here");

      if ($entity->id()) {
        // Get slides.
        $slides = $headerMediaSlider->getSlides($entity->id());

        if (count($slides) > 0) {
          $elements[$delta] = [
            '#theme' => 'header_media_slider',
            '#slider_height' => $headerMediaSlider->getSliderHeight($entity->id()),
            '#slides' => $slides,
          ];
        }
      }
    }

    if (count($elements) > 0) {
      $elements['#attached']['library'][] = 'header_media_slider/header_media_slider';
    }
    // dsm($elements);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $paragraph_type = \Drupal::entityTypeManager()->getDefinition($target_type);
    if ($paragraph_type) {
      return $paragraph_type->entityClassImplements(ParagraphInterface::class);
    }

    return FALSE;
  }
}
