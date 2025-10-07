<?php

namespace Drupal\anchor_toc\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class AnchorTocDefaultItemsManager extends DefaultPluginManager {

  /**
   * Constructor for ViewsReferenceSettingManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/AnchorTocDefaultItems',
      $namespaces,
      $module_handler,
      'Drupal\anchor_toc\Plugin\AnchorTocDefaultItemsInterface',
      'Drupal\anchor_toc\Annotation\AnchorTocDefaultItems'
    );

    $this->alterInfo('anchor_toc_default_items_info');
    $this->setCacheBackend($cache_backend, 'anchor_toc_default_items_info_plugins');
  }
}
