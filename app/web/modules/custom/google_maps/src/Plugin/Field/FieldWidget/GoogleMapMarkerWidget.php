<?php

namespace Drupal\google_maps\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'google_map_marker' field widget.
 *
 * @FieldWidget(
 *   id = "google_map_marker",
 *   label = @Translation("Google Map Marker"),
 *   field_types = {"google_map_marker"},
 * )
 */
final class GoogleMapMarkerWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    // Attach library for styling.
    $element['#attached'] = [
      'library' => [
        'google_maps/google_map_marker_widget',
      ],
    ];

    // Wrap element in div for easier styling.
    $element['#prefix'] = '<div class="google-map-marker-widget">';
    $element['#suffix'] = '</div>';

    // Add custom validation.
    $element['#element_validate'] = [
      [$this, 'validate'],
    ];

    // dsm($element);
    $item = $items[$delta];

    // Get the field configuration.
    $field_definition = $items->getFieldDefinition();
    $field_config = $field_definition->getSettings();

    // Set default values.
    if (!$this->isDefaultValueWidget($form_state) && $item->isEmpty()) {
      $default_values = $field_definition->getDefaultValueLiteral()[0];
      // dsm($default_values, "default_values");
      $default_fields = [
        'transportation_type',
        'marker_type',
        'info_window',
        'info_window_address',
        'info_window_contact',
        'info_window_value',
        'info_window_format',
      ];
      foreach ($default_fields as $default_field_name) {
        if (isset($default_values[$default_field_name])) {
          $item->__set($default_field_name, $default_values[$default_field_name]);
        }
      }
    }

    // POI autocomplete.
    if (!$this->isDefaultValueWidget($form_state)) {

      // UUid.
      $element['uuid'] = array(
        '#title' => t('Widget markup'),
        '#type' => 'value',
        '#value' => !empty($item->__get('uuid')) ? $item->__get('uuid') : $this->generateUuid(),
        '#required' => (bool) $element['#required'],
        '#weight' => 0,
      );

      // POI not visible in default value widget.
      $poi = NULL;
      if (!empty($item->__get('lat')) && !empty($item->__get('lng'))) {
        $poi = [
          'latitude' => $item->__get('lat'),
          'longitude' => $item->__get('lng'),
          'location_name' => $item->__get('location'),
          'address' => [
            'street' => $item->__get('address_street'),
            'street_no' => $item->__get('address_street_no'),
            'state' => $item->__get('address_state'),
            'postal_code' => $item->__get('address_postal_code'),
            'city' => $item->__get('address_city'),
            'country' => $item->__get('address_country'),
          ],
          'contact' => [
            'phone' => $item->__get('phone'),
            'email' => $item->__get('email'),
            'website' => $item->__get('website'),
          ],
        ];
      }
      $element['poi'] = [
        // '#title' => t('Location Search'),
        '#description' => t('Note: Search and selecting a value replaces location and address'),
        '#description_display' => 'before',
        '#type' => 'google_places_autocomplete',
        '#default_value' => $poi,
        '#placeholder' => t('Enter query to search for location'),
        '#show_location_name' => TRUE,
        '#show_address' => $field_config['address'],
        '#show_contact' => $field_config['contact'],
        '#clear_on_input' => FALSE,
        '#required' => !empty($element['#required']),
      ];
    }

    // Maker type.
    if (!empty($field_config['marker_type'])) {
      $element['marker_type'] = [
        '#title' => t('Marker type'),
        '#type' => 'select',
        '#options' => $this->getMarkerTypeOptions(),
        '#default_value' => $item->__get('marker_type'),
        '#required' => FALSE,
      ];
    }

    // Transportation type.
    if (!empty($field_config['transportation_type'])) {
      $element['transportation_type'] = [
        '#title' => t('Transportation type'),
        '#type' => 'select',
        '#options' => $this->getTransportationTypeOptions(),
        '#default_value' => $item->__get('transportation_type'),
        '#required' => FALSE,
      ];
    }

    // Info window.
    if (!empty($field_config['info_window'])) {

      // Details.
      $element['info_window_details'] = [
        '#type' => 'details',
        '#title' => t("Info Window"),
        '#required' => FALSE,
      ];

      // Show info window checkbox.
      $element['info_window_details']['info_window'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show info window on marker click'),
        '#default_value' => $item->__get('info_window'),
      ];

      // Show address in info window.
      if (!empty($field_config['address'])) {
        $element['info_window_details']['info_window_address'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show address in info window'),
          '#default_value' => $item->__get('info_window_address'),
        ];
      }

      if (!empty($field_config['contact'])) {
        $element['info_window_details']['info_window_contact'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show contact in info window'),
          '#default_value' => $item->__get('info_window_contact'),
        ];
      }

      // Body:

      // Get a list of formats that the current user has access to.
      $allowed_formats = array_keys(filter_formats(\Drupal::currentUser()));

      // Set text format and make sure the user has access.
      $text_format = !empty($item->__get('info_window_format')) ? $item->__get('info_window_format') : 'full_html';
      if (!in_array($text_format, $allowed_formats) && count($allowed_formats) > 0) {
        $text_format = $allowed_formats[0];
      }
      $element['info_window_details']['body'] = [
        '#title' => t('Info window content'),
        '#type' => 'text_format',
        // '#title_display' => 'invisible',
        '#default_value' => $item->__get('info_window_value'),
        '#format' => $text_format,
        '#required' => FALSE,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {

    // Skip checks on default value widget.
    if ($this->isDefaultValueWidget($form_state)) {
      return;
    }

    // Check that latitude and longitude values are available:
    $poi_value = $element['poi']['#value'];
    // dsm($poi_value);
    if (empty($poi_value['latitude']) && empty($poi_value['longitude'])) {
      return;
    }

    if (empty($poi_value['latitude']) || empty($poi_value['longitude'])) {
      $form_state->setError($element['poi'], $this->t('Missing latitude/longitude value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $item) {

      // Set information if default value widget is used.
      // This is used to invalidate isEmpty() check.
      $values[$key]['is_default_value_widget'] = $this->isDefaultValueWidget($form_state);
      if (empty($values[$key]['is_default_value_widget']) && empty($item['poi']['latitude']) && empty($item['poi']['longitude'])) {
        continue;
      }

      // From POI.
      $values[$key]['location'] = !empty($item['poi']['location_name']) ? substr($item['poi']['location_name'], 0,255) : NULL;
      $values[$key]['lat'] = !empty($item['poi']['latitude']) ? $item['poi']['latitude'] : NULL;
      $values[$key]['lng'] = !empty($item['poi']['longitude']) ? $item['poi']['longitude'] : NULL;
      $values[$key]['address_street'] = !empty($item['poi']['address_street']) ? $item['poi']['address_street'] : NULL;
      $values[$key]['address_street_no'] = !empty($item['poi']['address_street_no']) ? $item['poi']['address_street_no'] : NULL;
      $values[$key]['address_state'] = !empty($item['poi']['address_state']) ? $item['poi']['address_state'] : NULL;
      $values[$key]['address_postal_code'] = !empty($item['poi']['address_postal_code']) ? $item['poi']['address_postal_code'] : NULL;
      $values[$key]['address_city'] = !empty($item['poi']['address_city']) ? $item['poi']['address_city'] : NULL;
      $values[$key]['address_country'] = !empty($item['poi']['address_country']) ? $item['poi']['address_country'] : NULL;
      $values[$key]['phone'] = !empty($item['poi']['contact_phone']) ? $item['poi']['contact_phone'] : NULL;
      $values[$key]['email'] = !empty($item['poi']['contact_email']) ? $item['poi']['contact_email'] : NULL;
      $values[$key]['website'] = !empty($item['poi']['contact_website']) ? $item['poi']['contact_website'] : NULL;

      // Local values.
      $values[$key]['marker_type'] = !empty($item['marker_type']) ? $item['marker_type'] : NULL;
      $values[$key]['transportation_type'] = !empty($item['transportation_type']) ? $item['transportation_type'] : NULL;
      $values[$key]['info_window'] = !empty($item['info_window_details']['info_window']);
      $values[$key]['info_window_address'] = !empty($item['info_window_details']['info_window_address']);
      $values[$key]['info_window_contact'] = !empty($item['info_window_details']['info_window_contact']);
      $values[$key]['info_window_value'] = !empty($item['info_window_details']['body']['value']) ? $item['info_window_details']['body']['value'] : NULL;
      $values[$key]['info_window_format'] = !empty($item['info_window_details']['body']['value']) ? $item['info_window_details']['body']['format'] : NULL;

    }
    // dsm($values, 'values changed');

    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * Returns a UUID.
   *
   * @return string
   */
  private function generateUuid(): string {
    $uuidService = \Drupal::service('uuid');
    return $uuidService->generate();
  }

  /**
   * Returns options for 'marker_type' field.
   *
   * @return array[]
   */
  private function getMarkerTypeOptions(): array {
    return [
      NULL => $this->t('default'),
      /*
      'full_time' => $this->t('Full time'),
      'part_time' => $this->t('Part time'),
      'vocational_training' => $this->t('Vocational training'),
      */
    ];
  }

  /**
   * Returns options for 'transportation_type' field.
   *
   * @return array[]
   */
  private function getTransportationTypeOptions(): array {
    return [
      NULL => $this->t('default'),
      'bus' => $this->t('Bus'),
      'car' => $this->t('Car'),
      'ferry' => $this->t('Ferry'),
      'train' => $this->t('Train'),
      'camper' => $this->t('Camper'),
      'plane' => $this->t('Plane'),
      'helicopter' => $this->t('Helicopter'),
    ];
  }
}
