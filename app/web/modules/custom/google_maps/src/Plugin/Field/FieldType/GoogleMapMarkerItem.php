<?php

namespace Drupal\google_maps\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'google_map_marker' field type.
 *
 * @FieldType(
 *   id = "google_map_marker",
 *   label = @Translation("Google Map"),
 *   description = @Translation("Stores locations for displaying on Google Map"),
 *   category = @Translation("General"),
 *   default_widget = "google_map_marker",
 *   default_formatter = "google_map",
 * )
 */
final class GoogleMapMarkerItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    $settings = [
      'marker_type' => FALSE,
      'transportation_type' => TRUE,
      'address' => TRUE,
      'contact' => TRUE,
      'info_window' => TRUE,
      'is_default_value_widget' => FALSE,
    ];
    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $element['marker_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show marker type field'),
      '#default_value' => $this->getSetting('marker_type'),
    ];

    $element['transportation_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show transportation type field'),
      '#default_value' => $this->getSetting('transportation_type'),
    ];

    $element['address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show address fields'),
      '#default_value' => $this->getSetting('address'),
    ];

    $element['contact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show contact fields'),
      '#default_value' => $this->getSetting('contact'),
    ];

    $element['info_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show info window fields'),
      '#default_value' => $this->getSetting('info_window'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {

    // Check if called from default value widget.
    $values = $this->getValue();
    if (!empty($values['is_default_value_widget'])) {
      // In default value widget:

      $marker_type = $this->get('marker_type')->getValue();
      $transportation_type = $this->get('transportation_type')->getValue();
      $info_window = $this->get('info_window')->getValue();
      $info_window_address = $this->get('info_window_address')->getValue();
      $info_window_contact = $this->get('info_window_contact')->getValue();
      $info_window_value = $this->get('info_window_value')->getValue();

      $this->set('uuid', '');
      $this->set('location', '');
      $this->set('lat', 0);
      $this->set('lng', 0);

      return empty($marker_type) && empty($transportation_type) && empty($info_window) && empty($info_window_address) && empty($info_window_contact) && empty($info_window_value);
    }

    // Not in default value widget:
    $lat = $this->get('lat')->getValue();
    $lng = $this->get('lng')->getValue();

    return $lat === NULL || $lat === '' || $lng === NULL || $lng === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {

    // @DCG
    // See /core/lib/Drupal/Core/TypedData/Plugin/DataType directory for
    // available data types.

    $properties['uuid'] = DataDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setRequired(TRUE);

    $properties['location'] = DataDefinition::create('string')
      ->setLabel(t('Location'))
      ->setRequired(TRUE);

    $properties['lat'] = DataDefinition::create('float')
      ->setLabel(t('Latitude'))
      ->setRequired(TRUE);

    $properties['lng'] = DataDefinition::create('float')
      ->setLabel(t('Longitude'))
      ->setRequired(TRUE);

    $properties['address_street'] = DataDefinition::create('string')
      ->setLabel(t('Street'))
      ->setRequired(FALSE);

    $properties['address_street_no'] = DataDefinition::create('string')
      ->setLabel(t('Street Number'))
      ->setRequired(FALSE);

    $properties['address_state'] = DataDefinition::create('string')
      ->setLabel(t('State/Province'))
      ->setRequired(FALSE);

    $properties['address_postal_code'] = DataDefinition::create('string')
      ->setLabel(t('Postal Code'))
      ->setRequired(FALSE);

    $properties['address_city'] = DataDefinition::create('string')
      ->setLabel(t('City'))
      ->setRequired(FALSE);

    $properties['address_country'] = DataDefinition::create('string')
      ->setLabel(t('Country'))
      ->setRequired(FALSE);

    $properties['phone'] = DataDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setRequired(FALSE);

    $properties['email'] = DataDefinition::create('string')
      ->setLabel(t('Email'))
      ->setRequired(FALSE);

    $properties['website'] = DataDefinition::create('uri')
      ->setLabel(t('Website'))
      ->setRequired(FALSE);

    $properties['marker_type'] = DataDefinition::create('string')
      ->setLabel(t('Marker type'))
      ->setRequired(FALSE);

    $properties['transportation_type'] = DataDefinition::create('string')
      ->setLabel(t('Transportation type'))
      ->setRequired(FALSE);

    $properties['info_window'] = DataDefinition::create('boolean')
      ->setLabel(t('Show info window on marker click'))
      ->setRequired(FALSE);

    $properties['info_window_address'] = DataDefinition::create('boolean')
      ->setLabel(t('Show address in info window'))
      ->setRequired(FALSE);

    $properties['info_window_contact'] = DataDefinition::create('boolean')
      ->setLabel(t('Show contact in info window'))
      ->setRequired(FALSE);

    $properties['info_window_value'] = DataDefinition::create('string')
      ->setLabel(t('Info Window markup'))
      ->setRequired(FALSE);

    $properties['info_window_format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Info Window format'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {

    $columns = [
      'uuid' => array(
        'description' => 'The UUID.',
        'type' => 'varchar',
        'length' => 36,
        'not null' => TRUE,
      ),
      'location' => [
        'type' => 'varchar',
        'description' => 'Stores the location name.',
        'length' => 255,
        'not null' => TRUE,
      ],
      'lat' => [
        'description' => 'Stores the latitude value',
        'type' => 'float',
        'size' => 'big',
        'not null' => TRUE,
      ],
      'lng' => [
        'description' => 'Stores the longitude value',
        'type' => 'float',
        'size' => 'big',
        'not null' => TRUE,
      ],
      'address_street' => [
        'type' => 'varchar',
        'description' => 'Stores the street of address.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'address_street_no' => [
        'type' => 'varchar',
        'description' => 'Stores the street number of address.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'address_state' => [
        'type' => 'varchar',
        'description' => 'Stores the state of address.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'address_postal_code' => [
        'type' => 'varchar',
        'description' => 'Stores the postal code of address.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'address_city' => [
        'type' => 'varchar',
        'description' => 'Stores the city of address.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'address_country' => [
        'type' => 'varchar',
        'description' => 'Stores the country code of address.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'phone' => [
        'type' => 'varchar',
        'description' => 'Stores the phone number.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'email' => [
        'type' => 'varchar',
        'description' => 'Stores the email address.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'website' => [
        'type' => 'varchar',
        'description' => 'Stores the website url.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'marker_type' => [
        'type' => 'varchar',
        'description' => 'Stores the marker type.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'transportation_type' => [
        'type' => 'varchar',
        'description' => 'Stores the transportation type.',
        'length' => 255,
        'not null' => FALSE,
      ],
      'info_window' => array(
        'description' => 'Stores a boolean value to show info window on marker click.',
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ),
      'info_window_address' => array(
        'description' => 'Stores a boolean value to show address in info window.',
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ),
      'info_window_contact' => array(
        'description' => 'Stores a boolean value to show contact in info window.',
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ),
      'info_window_value' => [
        'type' => 'text',
        'description' => 'Stores the info window markup value.',
        'size' => 'big',
        'not null' => FALSE,
      ],
      'info_window_format' => [
        'type' => 'varchar_ascii',
        'description' => 'Stores the info window text format.',
        'length' => 255,
        'not null' => FALSE,
      ],
    ];

    $schema = [
      'columns' => $columns,
      /*
      'indexes' => [
        'body_format' => 'body_format',
      ],
      */
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $values['location'] = 'Sample location';
    $values['lat'] = rand(-89, 90) - rand(0, 999999) / 1000000;
    $values['lng'] = rand(-179, 180) - rand(0, 999999) / 1000000;
    $values['transportation_type'] = NULL;
    $values['marker_type'] = NULL;
    $values['info_window_value'] = '<p>Dummy value</p>';
    $values['info_window_format'] = 'full_html';

    return $values;
  }
}
