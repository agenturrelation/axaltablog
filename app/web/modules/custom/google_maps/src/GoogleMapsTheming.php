<?php

namespace Drupal\google_maps;

use CommerceGuys\Addressing\Address;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Language\LanguageInterface;

class GoogleMapsTheming {

  /**
   * @var array
   */
  private array $gmapSettings;

  public function __construct() {

    // Set Gmap settings.
    $config = \Drupal::config('google_maps.settings');
    $mapSettings = $config->get('gmap');
    $this->gmapSettings = !empty($mapSettings) ? $mapSettings : [];
  }

  /**
   * Theme Google Map.
   *
   * @param array $markers
   * @param bool $useRoutes
   * @param bool $showLegend
   *
   * @return array
   */
  function themeGoogleMap(array $markers, bool $useRoutes = FALSE, bool $showLegend = FALSE): array {

    // Check for settings.
    if (empty($this->gmapSettings)) {
      $messenger = \Drupal::service('messenger');
      $messenger->addError(t('Missing settings for Google Map module.'));
      return [];
    }

    // Get language code.
    $language_code = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    // Set marker icons.
    // Build markers for use in JS.
    $jsMarkers = [];
    foreach ($markers as $marker) {

      $jsMarker = [
        'uuid' => $marker['uuid'],
        'location' => $marker['location'],
        'lat' => $marker['lat'],
        'lng' => $marker['lng'],
        'marker_type' => $marker['marker_type'],
        'transportation_type' => $marker['transportation_type'],
        'info_window_markup' => NULL,
      ];

      // Set transportation icon:
      switch($marker['transportation_type']) {
        case 'bus':
          $jsMarker['transportation_icon'] = "/modules/custom/google_maps/images/icons/secondary/icon_bus.svg";
          break;
        case 'car':
          $jsMarker['transportation_icon'] = "/modules/custom/google_maps/images/icons/secondary/icon_car.svg";
          break;
        case 'ferry':
          $jsMarker['transportation_icon'] = "/modules/custom/google_maps/images/icons/secondary/icon_ferry.svg";
          break;
        case 'train':
          $jsMarker['transportation_icon'] = "/modules/custom/google_maps/images/icons/secondary/icon_train.svg";
          break;
        case 'camper':
          $jsMarker['transportation_icon'] = "/modules/custom/google_maps/images/icons/secondary/icon_camper.svg";
          break;
        case 'plane':
          $jsMarker['transportation_icon'] = "/modules/custom/google_maps/images/icons/secondary/icon_plane.svg";
          break;
        case 'helicopter':
          $jsMarker['transportation_icon'] = "/modules/custom/google_maps/images/icons/secondary/icon_helicopter.svg";
          break;
      }

      // Set info window markup:

      if (!empty($marker['info_window_value']) ||
        !empty($marker['info_window_address']) ||
        !empty($marker['info_window_contact'])) {

        $address = NULL;
        if (!empty($marker['info_window_address'])) {
          $address = [
            'location' => $marker['location'],
            'street' => $marker['address_street'],
            'street_no' => $marker['address_street_no'],
            'postal_code' => $marker['address_postal_code'],
            'city' => $marker['address_city'],
            'state' => $marker['address_state'],
            'country' => $marker['address_country'],
          ];
        }

        $infoWindowMarkup = [
          '#theme' => 'google_maps_info_window',
          '#ident' => $marker['uuid'],
          '#location_name' => $marker['location'],
          '#show_address' => $marker['info_window_address'],
          '#address' => $address,
          '#formatted_address' => $address ? $this->getFormattedAddress($address, FALSE, TRUE) : NULL,
          '#show_contact' => $marker['info_window_contact'],
          '#phone' => $marker['phone'],
          '#email' => $marker['email'],
          '#website' => $marker['website'],
          '#markup' => !empty($marker['info_window_value']) ? new FormattableMarkup($marker['info_window_value'], []) : NULL,
        ];
        // dsm($infoWindowMarkup, 'infoWindowMarkup');

        // Set rendered output to info_window_markup.
        $jsMarker['info_window_markup'] = \Drupal::service('renderer')->render($infoWindowMarkup);
      }

      $jsMarkers[] = $jsMarker;
    }
    // dsm($jsMarkers, 'jsMarkers');

    // Set map controls.
    $map_controls = array();
    foreach ($this->gmapSettings['controls'] as $control) {
      if (!empty($control)) {
        $map_controls[] = $control;
      }
    }

    // Disabled - no need for displaying the sidebar within the privacy placeholder.

    if (count($jsMarkers) === 0) {
      return [];
    }

    // Legend.
    $legend = NULL;
    if ($showLegend) {
      $legend_items = [];
      foreach ($jsMarkers as $marker) {
        $legend_items[] = [
          'label' => $marker['location']
        ];
      }
      $legend = [
        '#theme' => 'google_maps_legend',
        '#items' => $legend_items
      ];
    }

    return [
      '#theme' => 'google_map',
      '#width' => '100%',
      '#height' => '500px',
      '#markers' => $markers,
      '#legend' => $legend,
      '#attached' => [
        'library' => [
          'google_maps/googlemaps-js-api-loader',
          'google_maps/google_map',
        ],
        'drupalSettings' => array(
          'google_maps' => array(
            'api_key' => $this->gmapSettings['api_key'],
            'language' => $language_code,
            'routes' => $useRoutes,
            'legend' => $showLegend,
            'markers' => $jsMarkers,
            'map_type_id' => $this->gmapSettings['type'],
            'map_styles' => !empty($this->gmapSettings['map_style']) ? json_decode($this->gmapSettings['map_style']) : null,
            'map_zoom' => intval($this->gmapSettings['zoom']),
            'map_controls' => $map_controls,
            'map_custom_marker_icon' => !empty($this->gmapSettings['custom_marker']) ? $this->gmapSettings['custom_marker']: null,
            'map_custom_marker' => [
              'primary' => !empty($this->gmapSettings['custom_marker']) ? $this->gmapSettings['custom_marker']: null,
              'secondary' => !empty($this->gmapSettings['custom_marker_secondary']) ? $this->gmapSettings['custom_marker_secondary']: null,
            ],
            'sidebar' => [
              'on_load' => TRUE,
            ]
          ),
        ),
      ],
      '#cache' => array(
        'contexts' => ['url.path'],
        'tags' => [
          'node_list',
        ],
      ),
    ];
  }

  /**
   * Returns formatted address.
   *
   * @param array $data
   * @param bool $includeLocation
   * @param bool $includeCountry
   *
   * @return FormattableMarkup|null
   */
  public function getFormattedAddress(array $data, bool $includeLocation = TRUE, bool $includeCountry = TRUE): ?FormattableMarkup {

    try {
      // Get current language.
      $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();

      // Build Address instance.
      $addressFormatRepository = new AddressFormatRepository();
      $countryRepository = new CountryRepository($lang_code);
      $subdivisionRepository = new SubdivisionRepository();
      $formatter = new DefaultFormatter($addressFormatRepository, $countryRepository, $subdivisionRepository);

      $address = new Address();

      if (!empty($data['street'])) {
        $street = $data['street'];
        if (!empty($data['street_no'])) {
          $street .= ' ' . $data['street_no'];
        }
        $address = $address->withAddressLine1($street);
      }

      if (!empty($data['postal_code'])) {
        $address = $address->withPostalCode($data['postal_code']);
      }

      if (!empty($data['state'])) {
        $address = $address->withAdministrativeArea($data['state']);
      }

      if (!empty($data['city'])) {
        $address = $address->withLocality($data['city']);
      }

      if (!empty($data['country'])) {
        $address = $address->withCountryCode($data['country']);
      }

      // Add 'location'.
      if ($includeLocation && !empty($data['location'])) {
        $address = $address->withOrganization($data['location']);
      }

      // Format address.
      $formattedAddress = $formatter->format($address, ['locale' => $lang_code, 'html' => FALSE]);

      // Remove country name.
      if (!$includeCountry) {
        $country = $countryRepository->get($address->getCountryCode());
        $formattedAddress = str_replace($country->getName(), '', $formattedAddress);
      }

     return new FormattableMarkup(nl2br(trim($formattedAddress), FALSE), []);
    } catch (\Exception $e) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        dsm('Catch exception:' . $e->getMessage());
      }
      return NULL;
    }
  }

}
