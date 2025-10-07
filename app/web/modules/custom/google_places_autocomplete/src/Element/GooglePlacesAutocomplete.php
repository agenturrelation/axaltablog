<?php

namespace Drupal\google_places_autocomplete\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Select;

/**
 * Provides a Google Places autocomplete form element.
 *
 * @FormElement("google_places_autocomplete")
 *
 * Usage example:
 *
 * @code
 * $form['example_google_places_autocomplete'] = [
 *   '#type' => 'google_places_autocomplete',
 *   '#title' => $this->t('Postal code or city'),
 *   '#default_value' => '',
 *   '#required' => TRUE,
 * ];
 * @endcode
 */
class GooglePlacesAutocomplete extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#theme' => 'google_places_autocomplete_form',
      '#theme_wrappers' => ['google_places_autocomplete_wrapper'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(&$element, FormStateInterface $form_state, &$complete_form) {

    $element['#tree'] = TRUE;

    $element['location'] = [
      '#type' => 'textfield',
      '#title' => !empty($element['#title']) ? $element['#title'] : '',
      '#title_display' => !empty($element['#title_display']) ? $element['#title_display'] : 'before',
      '#description' => !empty($element['#description']) ? $element['#description'] : '',
      '#name' => $element['#name'] . '[location]',
      '#default_value' => !empty($element['#default_value']['location']) ? $element['#default_value']['location'] : '',
      '#attributes' => [
        'data-clear-on-input' => key_exists('#clear_on_input', $element) && !$element['#clear_on_input'] ? 0 : 1,
        'placeholder' => !empty($element['#placeholder']) ? $element['#placeholder'] : $element['#title'],
      ],
    ];

    $element['latitude'] = [
      '#type' => 'hidden',
      '#name' => $element['#name'] . '[latitude]',
      '#default_value' => !empty($element['#default_value']['latitude']) ? $element['#default_value']['latitude'] : '',
    ];

    $element['longitude'] = [
      '#type' => 'hidden',
      '#name' => $element['#name'] . '[longitude]',
      '#default_value' => !empty($element['#default_value']['longitude']) ? $element['#default_value']['longitude'] : '',
    ];

    // Location name.
    if (key_exists('#show_location_name', $element) && $element['#show_location_name']) {
      $element['location_name'] = [
        '#type' => 'textfield',
        '#name' => $element['#name'] . '[location_name]',
        '#title' => t("Location Name"),
        '#default_value' => !empty($element['#default_value']['location_name']) ? $element['#default_value']['location_name'] : '',
        '#required' => !empty($element['#required']),
      ];
    }

    // Address.
    if (key_exists('#show_address', $element) && $element['#show_address']) {
      $element['address'] = [
        '#type' => 'details',
        '#title' => t("Address"),
        '#attributes' => [
          'class' => ['google-places-autocomplete-address'],
        ]
      ];

      $element['address']['street'] = [
        '#type' => 'textfield',
        '#name' => $element['#name'] . '[address_street]',
        '#title' => t("Street"),
        '#default_value' => !empty($element['#default_value']['address']['street']) ? $element['#default_value']['address']['street'] : '',
        '#prefix' => '<div class="google-places-autocomplete-street">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => ['google-places-autocomplete-street'],
        ]
      ];

      $element['address']['street_no'] = [
        '#type' => 'textfield',
        '#name' => $element['#name'] . '[address_street_no]',
        '#title' => t("Street Number"),
        '#default_value' => !empty($element['#default_value']['address']['street_no']) ? $element['#default_value']['address']['street_no'] : '',
        '#prefix' => '<div class="google-places-autocomplete-street-no">',
        '#suffix' => '</div>',
      ];

      $element['address']['postal_code'] = [
        '#type' => 'textfield',
        '#name' => $element['#name'] . '[address_postal_code]',
        '#default_value' => !empty($element['#default_value']['address']['postal_code']) ? $element['#default_value']['address']['postal_code'] : '',
        '#title' => t("Postal Code"),
        '#prefix' => '<div class="google-places-autocomplete-postal-code">',
        '#suffix' => '</div>',
      ];

      $element['address']['city'] = [
        '#type' => 'textfield',
        '#name' => $element['#name'] . '[address_city]',
        '#title' => t("City"),
        '#default_value' => !empty($element['#default_value']['address']['city']) ? $element['#default_value']['address']['city'] : '',
        '#prefix' => '<div class="google-places-autocomplete-city">',
        '#suffix' => '</div>',
      ];

      $element['address']['state'] = [
        '#type' => 'textfield',
        '#name' => $element['#name'] . '[address_state]',
        '#title' => t("State/Province"),
        '#default_value' => !empty($element['#default_value']['address']['state']) ? $element['#default_value']['address']['state'] : '',
        '#prefix' => '<div class="google-places-autocomplete-state">',
        '#suffix' => '</div>',
      ];


      $countries = \Drupal::service('country_manager')->getList();
      $countryOptions = array();
      foreach ($countries as $countryCode => $countryName) {
        $countryOptions[$countryCode] = $countryName;
      }
      $element['address']['country'] = [
        '#type' => 'select',
        '#name' => $element['#name'] . '[address_country]',
        '#options' => $countryOptions,
        '#title' => t("Country"),
        '#default_value' => !empty($element['#default_value']['address']['country']) ? $element['#default_value']['address']['country'] : '',
        '#prefix' => '<div class="google-places-autocomplete-country">',
        '#suffix' => '</div>',
      ];

    }

    // Contact data.
    if (key_exists('#show_contact', $element) && $element['#show_contact']) {
      $element['contact'] = [
        '#type' => 'details',
        '#title' => t("Contact"),
        '#attributes' => [
          'class' => ['google-places-autocomplete-contact'],
        ]
      ];

      $element['contact']['phone'] = [
        '#type' => 'textfield',
        '#name' => $element['#name'] . '[contact_phone]',
        '#title' => t("Phone"),
        '#default_value' => !empty($element['#default_value']['contact']['phone']) ? $element['#default_value']['contact']['phone'] : '',
      ];

      $element['contact']['email'] = [
        '#type' => 'email',
        '#name' => $element['#name'] . '[contact_email]',
        '#title' => t("Email"),
        '#default_value' => !empty($element['#default_value']['contact']['email']) ? $element['#default_value']['contact']['email'] : '',
      ];

      $element['contact']['website'] = [
        '#type' => 'url',
        '#name' => $element['#name'] . '[contact_website]',
        '#title' => t("Website"),
        '#default_value' => !empty($element['#default_value']['contact']['website']) ? $element['#default_value']['contact']['website'] : '',
      ];
    }

    // Attach library.
    $element['#attached'] = [
      'library' => [
        'google_places_autocomplete/google_places_autocomplete_element',
      ],
    ];

    return $element;
  }

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $input = [
        'location' => !empty($element['#default_value']['location']) ? $element['#default_value']['location'] : '',
        'latitude' => !empty($element['#default_value']['latitude']) ? $element['#default_value']['latitude'] : '',
        'longitude' =>  !empty($element['#default_value']['longitude']) ? $element['#default_value']['longitude'] : '',
      ];
    }

    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $title = !empty($element['#title']) ? $element['#title'] : '';
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    // dsm($input, 'validateElement');

    if ($input_exists) {
      return;
    }

    if (empty($input['location']) && empty($input['latitude']) && empty($input['longitude']) && !$element['#required']) {
      $form_state->setValueForElement($element, [
        'location' => '',
        'latitude' => '',
        'longitude' => '',
      ]);
    }
    elseif ((empty($input['location']) || empty($input['latitude']) || empty($input['longitude'])) && $element['#required']) {
      $form_state->setError($element, t('The %field is required. Please select a valid location.', ['%field' => $title]));
    }
  }

}
