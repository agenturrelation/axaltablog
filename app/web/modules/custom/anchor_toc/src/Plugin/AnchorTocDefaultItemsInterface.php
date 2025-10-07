<?php

namespace Drupal\anchor_toc\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Anchor TOC default items plugins.
 */
interface AnchorTocDefaultItemsInterface extends PluginInspectionInterface {

  /**
   * Returns the default items.
   *
   * @return array
   */
  public function getDefaultItems(): array;
}
