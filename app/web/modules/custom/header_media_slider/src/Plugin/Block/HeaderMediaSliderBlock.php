<?php

namespace Drupal\header_media_slider\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\header_media_slider\HeaderMediaSlider;
use Drupal\node\Entity\Node;

/**
 * Header Media Slider block.
 *
 * @Block(
 *   id = "header_media_slider",
 *   admin_label = @Translation("Header Media Slider"),
 *   category = @Translation("Custom")
 * )
 */
class HeaderMediaSliderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    // Set supported entities.
    $entity = NULL;

    /** @var $node Node */
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      $entity = $node;
    }

    // Get HeaderMediaSlider instance.
    $headerMediaSlider = new HeaderMediaSlider();

    $slides = [];
    $slider_height = '';

    // Check if node has root paragraph field.
    if ($entity instanceof Node
      && $entity->hasField($headerMediaSlider::FIELD_NAME_ROOT_PARAGRAPH)
      && !empty($entity->get($headerMediaSlider::FIELD_NAME_ROOT_PARAGRAPH)->getValue())
      && $entity->get($headerMediaSlider::FIELD_NAME_ROOT_PARAGRAPH)->getValue()[0]
    ) {

      // Get slides.
      $root_paragraph = $entity->get($headerMediaSlider::FIELD_NAME_ROOT_PARAGRAPH)
        ->getValue()[0];
      $slides = $headerMediaSlider->getSlides($root_paragraph['target_id']);

      // Set slider height / options.
      $slider_height = $headerMediaSlider->getSliderHeight($root_paragraph['target_id']);
    }

    // Fallback for article node.
    if (count($slides) < 1 &&
      $entity instanceof Node
      && $entity->bundle() == 'article'
     ) {
      // dsm($cover);
      $slides = $headerMediaSlider->getArticleSlides($entity);
      $slider_height = $headerMediaSlider->getSliderHeight('');
      // dpm($slides, 'article slides');
    }

    // dpm($slides, 'slides');

    if (count($slides) > 0) {
      // Slides found.
      return [
        '#theme' => 'header_media_slider',
        '#slider_height' => !empty($slider_height) ? $slider_height: NULL,
        '#slider_options' => $slider_options ?? [],
        '#slides' => $slides,
        '#attached' => [
          'library' => [
            'header_media_slider/header_media_slider',
          ],
        ],
        '#cache' => [
          'contexts' => ['url.path'],
          'tags' => [
            'node_list',
          ],
        ],
      ];
    }
    else {
      // No slides found.
      return [
        '#markup' => NULL,
        '#cache' => [
          'contexts' => ['url.path'],
          'tags' => [
            'node_list',
          ],
        ],
      ];
    }
  }
}
