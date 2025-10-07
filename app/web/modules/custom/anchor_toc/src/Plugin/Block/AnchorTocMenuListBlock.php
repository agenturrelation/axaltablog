<?php

namespace Drupal\anchor_toc\Plugin\Block;

use Drupal\anchor_toc\AnchorTocUtils;
use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'menu list' block for Anchor TOC.
 *
 * @Block(
 *   id = "anchor_toc_menu_list",
 *   admin_label = @Translation("Anchor TOC Menu"),
 *   category = @Translation("Anchor TOC")
 * )
 */
class AnchorTocMenuListBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path', 'url.query_args'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get TOC links from current route
    $links = AnchorTocUtils::getLinksFormRoute();

    if (empty($links)) {
      return [];
    }

    // dsm($links, 'links in block');

    return [
      '#theme' => 'anchor_toc_menu_list',
      '#links' => $links,
      '#attached' => [
        'library' => [
          'anchor_toc/anchor_toc',
        ],
      ],
    ];
  }
}
