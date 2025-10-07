<?php

namespace Drupal\gdpr_video_formatter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class GdprVideoFormatterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides media.oembed.resource_fetcher class to fetch higher
    // resolution of YouTube image than Drupal core allows.

    // Note: it's safest to use hasDefinition() first, because getDefinition() will
    // throw an exception if the given service doesn't exist.
    if ($container->hasDefinition('media.oembed.resource_fetcher')) {
      $definition = $container->getDefinition('media.oembed.resource_fetcher');
      $definition->setClass('Drupal\gdpr_video_formatter\OEmbed\GdprVideoFormatterResourceFetcher');
    }
  }
}
