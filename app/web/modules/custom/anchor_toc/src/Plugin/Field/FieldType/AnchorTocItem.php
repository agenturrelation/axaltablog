<?php

namespace Drupal\anchor_toc\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'anchor_toc' field type.
 *
 * @FieldType(
 *   id = "anchor_toc",
 *   label = @Translation("Anchor TOC"),
 *   category = @Translation("Anchor TOC"),
 *   default_widget = "anchor_toc",
 *   default_formatter = ""
 * )
 */
class AnchorTocItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $is_empty = TRUE;

    $toc_value = $this->get('toc')->getValue();
    $anchor_id_value = $this->get('anchor_id')->getValue();
    $label_value = $this->get('label')->getValue();

    if (!empty($toc_value) && !$toc_value != 'custom') {
      $is_empty = FALSE;
    } else if ($toc_value == 'custom' &&
      !empty($anchor_id_value) &&
      !empty($label_value)
    ) {
      $is_empty = FALSE;
    }

    return $is_empty;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    // Toc.
    $properties['toc'] = DataDefinition::create('string')
      ->setLabel(t('TOC'))
      ->setRequired(TRUE);

    // Label.
    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE);

    // Anchor ID.
    $properties['anchor_id'] = DataDefinition::create('string')
      ->setLabel(t('Anchor ID'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    // Category.
    $columns = [
      'toc' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'TOC',
        'length' => 255,
      ],
      'label' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Label',
        'length' => 255,
      ],
      'anchor_id' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Anchor ID',
        'length' => 255,
      ],
    ];

    return [
      'columns' => $columns,
    ];
  }
}
