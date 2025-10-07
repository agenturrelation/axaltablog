<?php

namespace Drupal\anchor_toc;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AnchorTocTwigExtensions extends AbstractExtension {

  use StringTranslationTrait;

  public function getFilters() {
    return [
      new TwigFilter('anchor_toc_list', [
        $this,
        'anchorTocList',
      ]),
    ];
  }

  /**
   * Returns Anchor TOC list.
   *
   * @param ContentEntityInterface $entity
   *
   * @return array
   */
  public function anchorTocList(ContentEntityInterface $entity): array {

    // Get TOC links from current route
    $links = AnchorTocUtils::getLinksFromEntity($entity);

    if (empty($links)) {
      return [];
    }

    // dsm($links, 'links');

    return [
      '#theme' => 'anchor_toc_list',
      '#links' => $links,
      '#attached' => [
        'library' => [
          'anchor_toc/anchor_toc',
        ],
      ],
    ];
  }

}
