<?php

namespace Drupal\anchor_toc\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Anchor TOC default items annotation object.
 *
 * @see \Drupal\anchor_toc\Plugin\AnchorTocDefaultItemsManager
 * @see plugin_api
 *
 * @Annotation
 */
class AnchorTocDefaultItems extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the formatter type.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $label;
}
