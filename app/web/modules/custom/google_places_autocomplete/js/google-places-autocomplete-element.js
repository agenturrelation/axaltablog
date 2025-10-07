/**
 * @file
 * googlePlacesAutocompleteElement behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.googlePlacesAutocompleteElement = {
    attach: function (context, settings) {

      // Store behaviour.
      const self = this;
      once('googlePlacesAutocompleteElement', '.google-places-autocomplete-fields', context).forEach(function (container) {
        // console.log(container, "container");

        // Get required fields.
        const inputLocation = container.querySelector('input[name$="[location]"]');
        const inputLatitude = container.querySelector('input[name$="[latitude]"]');
        const inputLongitude = container.querySelector('input[name$="[longitude]"]');
        const inputLocationName = container.querySelector('input[name$="[location_name]"]');
        const inputAddressStreet = container.querySelector('input[name$="[address_street]"]');
        const inputAddressStreetNo = container.querySelector('input[name$="[address_street_no]"]');
        const inputAddressState = container.querySelector('input[name$="[address_state]"]');
        const inputAddressPostalCode = container.querySelector('input[name$="[address_postal_code]"]');
        const inputAddressCity = container.querySelector('input[name$="[address_city]"]');
        const inputAddressCountry = container.querySelector('[name$="[address_country]"]');
        const inputContactPhone = container.querySelector('[name$="[contact_phone]"]');
        const inputContactEmail = container.querySelector('[name$="[contact_email]"]');
        const inputContactWebsite = container.querySelector('[name$="[contact_website]"]');

        if (!inputLocation || !inputLatitude || !inputLongitude) {
          return;
        }

        // Flag to clear values on input.
        const clearOnInput = Boolean(parseInt(inputLocation.getAttribute("data-clear-on-input")));

        // Init autocomplete search.

        // GooglePlacesAutocomplete instance.
        const gAutocomplete = new GooglePlacesAutocomplete(inputLocation, {});

        // Listen for input event.
        if (clearOnInput) {
          console.log('clear on input res');
          inputLocation.addEventListener('input', function (event) {
            // Clear values.
            inputLatitude.value = '';
            inputLongitude.value = '';

            // Optional.
            if (inputLocationName) {
              inputLocationName.value = '';
            }
            if (inputAddressStreet) {
              inputAddressStreet.value = '';
            }
            if (inputAddressStreetNo) {
              inputAddressStreetNo.value = '';
            }
            if (inputAddressState) {
              inputAddressState.value = '';
            }
            if (inputAddressPostalCode) {
              inputAddressPostalCode.value = '';
            }
            if (inputAddressCity) {
              inputAddressCity.value = '';
            }
            if (inputAddressCountry) {
              inputAddressCountry.value = '';
            }
            if (inputContactPhone) {
              inputContactPhone.value = '';
            }
            if (inputContactWebsite) {
              inputContactWebsite.value = '';
            }
          });
        }

        // Listen for place changed event.
        inputLocation.addEventListener('place_changed', async function (event) {
          if (event.detail.value) {
            const placeDetails = await gAutocomplete.getPlaceDetails(event.detail.value);
            console.log(placeDetails, 'placeDetails');

            if (!placeDetails || !placeDetails.location) {
              // console.log('no location');
              return;
            }

            // Set latitude / longitude to hidden fields.
            inputLatitude.value = placeDetails.location.lat;
            inputLongitude.value = placeDetails.location.lng;

            // Set address (optional).
            if (inputLocationName) {
              inputLocationName.value = placeDetails.location_name;
            }
            if (inputAddressStreet) {
              inputAddressStreet.value = placeDetails.address.street;
            }
            if (inputAddressStreetNo) {
              inputAddressStreetNo.value = placeDetails.address.street_no;
            }
            if (inputAddressState) {
              inputAddressState.value = placeDetails.address.state;
            }
            if (inputAddressPostalCode) {
              inputAddressPostalCode.value = placeDetails.address.postal_code;
            }
            if (inputAddressCity) {
              inputAddressCity.value = placeDetails.address.city;
            }
            if (inputAddressCountry) {
              inputAddressCountry.value = placeDetails.address.country;
            }

            // Contact (optional).
            if (inputContactPhone) {
              inputContactPhone.value = placeDetails.phone_international;
            }
            if (inputContactWebsite) {
              inputContactWebsite.value = placeDetails.website;
            }

            // Clear search input.
            if (!clearOnInput) {
              inputLocation.value = '';
              inputLocation.blur();
            }
          }
        });

      });
    },
  };

} (Drupal, once));
