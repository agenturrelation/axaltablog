<?php

namespace Drupal\google_places_autocomplete\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class AutoCompleteSearchController extends ControllerBase {

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The settings array.
   * @var array
   */
  private array $settings = [];

  /**
   * The language code.
   * @var string
   */
  private string $languageCode;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $http_client) {
    // Set httpClient.
    $this->httpClient = $http_client;

    // Get damage form settings.
    $config = \Drupal::config('google_places_autocomplete.settings');

    // Set API settings.
    $this->settings = [
      'api_key' => $config->get('api_key'),
    ];

    // Set language code.
    $this->languageCode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }

    $options = [
      'query' => [
        'input' => $input,
        // 'components' => 'country:de|country:at',
        // 'types' => 'locality|sublocality|postal_code',
        'language' => $this->languageCode,
        'key' => $this->settings['api_key'],
      ],
      'headers' => [
        'Accept' => 'application/json',
      ]
    ];
    // dsm($options, 'options');

    $request = $this->httpClient->request('GET', 'https://maps.googleapis.com/maps/api/place/autocomplete/json', $options);

    if ($request->getStatusCode() != 200) {
      return [];
    }

    /** @var $result array */
    $response = json_decode($request->getBody()->getContents());
    // dsm($response, "response");

    if (is_array($response->predictions)) {
      foreach($response->predictions as $prediction) {
        $results[] = [
          'label' => $prediction->description,
          'value' => $prediction->place_id,
        ];
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Handler for Place details.
   */
  public function handlePlaceDetails(Request $request) {

    try {
      // Get the place id from query params.
      $placeId = $request->query->get('q');
      if (empty($placeId)) {
        return new JsonResponse([]);
      }

      $options = [
        'query' => [
          'place_id' => $placeId,
          'fields' => 'name,geometry,formatted_address,address_components,formatted_phone_number,international_phone_number,opening_hours,website',
          'language' => $this->languageCode,
          'key' => $this->settings['api_key'],
        ],
        'headers' => [
          'Accept' => 'application/json',
        ]
      ];
      // dsm($options, 'options');

      $request = $this->httpClient->request('GET', 'https://maps.googleapis.com/maps/api/place/details/json', $options);

      /** @var $result array */
      $response = json_decode($request->getBody()->getContents());
      // dsm($response, "response");

      return new JsonResponse([
        'location' => $response->result->geometry->location,
        'location_name' => $response->result->name,
        'formatted_address' => $response->result->formatted_address,
        'address' => $this->guessAddressFields($response),
        'phone' => $response->result->formatted_phone_number ?? NULL,
        'phone_international' => $response->result->international_phone_number ?? NULL,
        'website' => $response->result->website ?? NULL,
        'opening_hours' => $response->result->opening_hours ?? NULL,
      ]);
    }
    catch (\Exception $e) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        dsm($e->getMessage(), 'caught exception');
      }
      return new JsonResponse([]);
    }

  }

  /**
   * Returns the address form Google Place geocode response.
   *
   * @param $geocodeResponse
   *
   * @return string[]
   */
  private function guessAddressFields($geocodeResponse):array {

    $address = [
      'street' => NULL,
      'street_no' => NULL,
      'state' => NULL,
      'postal_code' => NULL,
      'city' => NULL,
      'country' => NULL,
    ];

    // Check for street address.
    $streetAddress = null;
    foreach ($geocodeResponse->result->address_components as $component) {
      if (in_array('street_address', $component->types)) {
        $streetAddress = $component;
        break;
      }
    }

    if ($streetAddress) {
      $address['street'] = $streetAddress['long_name'];
    } else {
      // Alt: Check for route.
      $route = null;
      foreach ($geocodeResponse->result->address_components as $component) {
        if (in_array('route', $component->types)) {
          $route = $component;
          break;
        }
      }
      if ($route) {
        $address['street'] = $route->long_name;
      }
    }

    // Check for street number.
    $streetNo = null;
    foreach ($geocodeResponse->result->address_components as $component) {
      if (in_array('street_number', $component->types)) {
        $streetNo = $component;
        break;
      }
    }
    if ($streetNo) {
      $address['street_no'] = $streetNo->short_name;
    }

    // Check for locality (city).
    $locality = null;
    foreach ($geocodeResponse->result->address_components as $component) {
      if (in_array('locality', $component->types)) {
        $locality = $component;
        break;
      }
    }
    if ($locality) {
      $address['city'] = $locality->long_name;
    }

    // Check for postal_code.
    $postalCode = null;
    foreach ($geocodeResponse->result->address_components as $component) {
      if (in_array('postal_code', $component->types)) {
        $postalCode = $component;
        break;
      }
    }
    if ($postalCode) {
      $address['postal_code'] = $postalCode->short_name;
    }

    // Check for administrative_area_level_1 (state).
    $state = null;
    foreach ($geocodeResponse->result->address_components as $component) {
      if (in_array('administrative_area_level_1', $component->types)) {
        $state = $component;
        break;
      }
    }
    if ($state) {
      $address['state'] = $state->short_name;
    }

    // Check for country.
    $country = null;
    foreach ($geocodeResponse->result->address_components as $component) {
      if (in_array('country', $component->types)) {
        $country = $component;
        break;
      }
    }
    if ($country) {
      $address['country'] = $country->short_name;
    }

    return $address;
  }
}
