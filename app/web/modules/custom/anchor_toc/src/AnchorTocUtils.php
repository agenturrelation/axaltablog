<?php

namespace Drupal\anchor_toc;
use Drupal\anchor_toc\Plugin\AnchorTocDefaultItemsInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field\Entity\FieldConfig;

class AnchorTocUtils {

  /**
   * Returns Anchor TOC links from current route.
   *
   * @return array
   */
  static public function getLinksFormRoute(): array {

    // Get content entity from route.
    $entity = AnchorTocUtils::getEntityFromRoute();
    if ($entity instanceof ContentEntityInterface) {
      // Get links by entity.
      return AnchorTocUtils::getLinksFromEntity($entity,);
    }

    return [];
  }

  /**
   * Returns Anchor TOC links by given entity.
   * @param ContentEntityInterface $entity
   *
   * @return array
   */
  static public function getLinksFromEntity(ContentEntityInterface $entity): array {

    $links = [];
    // Set links by entity.
    self::getLinksByEntity($entity, $links);

    // Implement hook_anchor_toc_links_alter().
    \Drupal::moduleHandler()->alter('anchor_toc_links', $entity, $links);
    // dsm($links, 'links');

    return $links;
  }

  /**
   * Sets Anchor TOC links by entity.
   *
   * @param ContentEntityInterface $entity
   * @param array $tocLinks
   *
   * @return void
   */
  static private function getLinksByEntity(ContentEntityInterface $entity, array &$tocLinks): void {

    /** @var FieldConfig[] $fieldDefinitions */
    $fieldDefinitions = !empty($entity) ? $entity->getFieldDefinitions() : NULL;
    if (!$fieldDefinitions) {
      return;
    }

    foreach ($fieldDefinitions as $fieldDefinition) {
      if ($fieldDefinition->getType() == 'anchor_toc') {
        // Is field from type 'anchor_toc':
        // Get links.
        $links = self::getLinksFromTocField($entity, $fieldDefinition->getName());
        if (!empty($links)) {
          $tocLinks = array_merge($tocLinks, $links);
        }
      }
      else if ($fieldDefinition->getType() == 'entity_reference_revisions') {
        // Is field from type 'entity_reference_revisions',
        // probably a paragraph.

        // Make sure target type is a paragraph.
        $fieldDefinitionSettings = $fieldDefinition->getSettings();
        if (!isset($fieldDefinitionSettings['target_type'])
          || $fieldDefinitionSettings['target_type'] != 'paragraph'
        ) {
          continue;
        }

        // Is paragraph:
        $fieldStorage = $fieldDefinition->get('fieldStorage');
        if (!$fieldStorage) {
          continue;
        }

        $entityType = $fieldStorage->get('settings')['target_type'];
        $list = $entity
          ->get($fieldDefinition->getName())
          ->getValue();

        $entityTypeManager = \Drupal::service('entity_type.manager');

        foreach ($list as $item) {
          $referenceRevisionEntity = $entityTypeManager
            ->getStorage($entityType)
            ->load($item['target_id']);

          // Call same method again with paragraph entity.
          self::getLinksByEntity($referenceRevisionEntity, $tocLinks);
        }
      }
    }
  }

  /**
   * Builds a single link.
   *
   * @param string $toc
   * @param string $label
   * @param string $anchor
   *
   * @return array
   */
  static public function buildLink(string $toc, string $label = '', string $anchor = ''): array {

//    dsm($toc, 'toc');
//    dsm($label, 'label');
//    dsm($anchor, '$anchor');

    switch ($toc) {
      case 'none':
        // No link is required:
        return [];

      case 'custom':
        // Custom: Use given label / anchor:

        if (empty($label)) {
          return [];
        }

        if (empty($anchor)) {
          // Generate anchor based on label.
          $anchor = Html::getClass($label);
        }

        return [
          'anchor' => $anchor,
          'label' => $label,
        ];

      default:
        // Probably defined in default items:

        $label = '';
        $anchor = '';

        // Find label / anchor in Plugin's default items.
        $plugin_manager = \Drupal::service('plugin.manager.anchor_toc_default_items');
        $plugin_definitions = $plugin_manager->getDefinitions();
        // dsm($plugin_definitions);
        foreach ($plugin_definitions as $plugin_definition) {
          /** @var AnchorTocDefaultItemsInterface $plugin_instance */
          $plugin_instance = $plugin_manager->createInstance($plugin_definition['id']);
          $defaultItems = $plugin_instance->getDefaultItems();
          if (count($defaultItems) > 0) {
            // Add default items to TOC options, keyed by plugin label.
            foreach ($defaultItems as $defaultItem) {

              if ($plugin_definition['id'] . '__' . $defaultItem['ident'] == $toc) {
                // Default item found.
                $label = $defaultItem['label'];
                $anchor = $defaultItem['anchor'];
                break 2;
              }
            }
          }
        }

        if (empty($label)) {
          return [];
        }

        if (empty($anchor)) {
          // Generate anchor based on label.
          $anchor = Html::getClass($label);
        }

        return [
          'anchor' => $anchor,
          'label' => $label,
        ];
    }
  }

  /**
   * Get Anchor TOC links by given entity and field name.
   *
   * @param ContentEntityInterface $entity
   * @param string $fieldName
   *
   * @return array
   */
  static private function getLinksFromTocField(ContentEntityInterface $entity, string $fieldName): array {
    $links = [];
    foreach ($entity->get($fieldName)->getValue() as $value) {
      $link = self::buildLink($value['toc'], $value['label'], $value['anchor_id']);
      if (!empty($link)) {
        $links[] = $link;
      }
    }

    return $links;
  }

  /**
   * Extracts the entity for the current route.
   *
   * @return null|ContentEntityInterface
   */
  static public function getEntityFromRoute(): ?ContentEntityInterface {
    $route_match = \Drupal::routeMatch();
    // Entity will be found in the route parameters.
    if (($route = $route_match->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $route_match->getParameter($name);
          if ($entity instanceof ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
            return $entity;
          }
        }
      }
    }

    return NULL;
  }
}
