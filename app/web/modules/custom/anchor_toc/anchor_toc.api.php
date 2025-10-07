<?php

/**
 * @file
 * Document all supported APIs.
 */

use Drupal\anchor_toc\AnchorTocUtils;

/**
 *  Provides a ability to alter Anchor TOC links by entity.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 * @param array $links
 *
 * @return void
 */
function hook_anchor_toc_links_alter(Drupal\Core\Entity\ContentEntityInterface $entity, array &$links) {
  if ($entity instanceof \Drupal\node\Entity\Node) {
    $links[] = AnchorTocUtils::buildLink('custom', 'New Section');
  }
}
