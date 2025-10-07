/**
 * @file
 * googleMap behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.googleMap = {

    getSidebarMarkup: function () {
      return `
        <div class="map_sidebar__container" role="dialog" aria-modal="true">
          <button class="map-sidebar__close button-close"></button>
          <main class="map-sidebar__content"></main>
        </div>`;
    },

    getSidebarLoadingMarkup: function () {
      return `
        <div class="map_sidebar__loading">
          <div class="modal__loading"></div>
        </div>`;
    },

    initializeMap: function (mapDestElement, settings) {

      const self = Drupal.behaviors.googleMap;

      // Alter settings based on media query.
      if (settings.sidebar.on_load && settings.markers.length === 1) {
        if (window.matchMedia("(max-width: 767px)").matches) {
          settings.sidebar.on_load = false;
        }
      }

      const mapOptions = {
        mapTypeControl: true,
        mapTypeControlOptions: {
          position: google.maps.ControlPosition.TOP_RIGHT,
        },
        zoom: settings.map_zoom,
        panControl: false,
        zoomControl: settings.map_controls.indexOf('zoom') !== -1,
        scaleControl: settings.map_controls.indexOf('scale') !== -1,
        streetViewControl: settings.map_controls.indexOf('streetview') !== -1,
        overviewMapControl: false,
        qw2mapTypeControl: false,
        rotateControl: settings.map_controls.indexOf('rotate') !== -1,
        fullscreenControl: settings.map_controls.indexOf('fullscreen') !== -1,
        scrollwheel: false,
        mapTypeId: settings.map_type_id,
        styles: settings.map_styles ? settings.map_styles : null,
      };
      // console.log(mapOptions);

      // Build the map.
      const map = new google.maps.Map(mapDestElement, mapOptions);

      // Create LatLngBounds.
      const lat_lng_bounds = new google.maps.LatLngBounds();

      // Set unique sidebar id.
      const uniqueSidebarId = 'map-sidebar-' + Math.random().toString(36).substring(2,7);


      // Set default maker icon.
      let defaultMarkerIcon = null;


      if (settings.map_custom_marker && settings.map_custom_marker.primary) {
        defaultMarkerIcon = {
          url: settings.map_custom_marker.primary,
          // size: new google.maps.Size(71, 71),
          scaledSize: new google.maps.Size(37, 48),
          // origin: new google.maps.Point(0, 0),
          // anchor: new google.maps.Point(17, 34),
          // scaledSize: new google.maps.Size(28, 40)

          labelOrigin: new google.maps.Point(19, 19),
        };
      }

      // Set active maker icon.
      let activeMarkerIcon = null;
      if (settings.map_custom_marker && settings.map_custom_marker.primary) {
        activeMarkerIcon = {
          url: settings.map_custom_marker.primary,
          scaledSize: new google.maps.Size(42, 54),
          labelOrigin: new google.maps.Point(21, 21),
        };
      }

      // Set markers.
      const markers = [];
      for (const data  of settings.markers) {

        // Set maker options.
        const marker_options = {
          map: map,
          position: new google.maps.LatLng(data.lat, data.lng),
          animation: google.maps.Animation.DROP,
          zIndex: 1,
        };

        if (settings.routes || settings.legend) {
          marker_options['label'] = {
            text: (markers.length + 1).toString(),
            fontSize: '12px',
            fontWeight: 'bold',
          }
        }

        if (defaultMarkerIcon) {
          marker_options['icon'] = defaultMarkerIcon;
        }

        // Set marker.
        const marker = new google.maps.Marker(marker_options);

        // Set 'click' event listener to marker.

        (function (marker, data) {
          // Add click event listener.
          if (data.info_window_markup) {
            google.maps.event.addListener(marker, "click", function (e) {

              // Reset all markers to the default icon.
              Drupal.behaviors.googleMap.resetMarkerIcons(markers, defaultMarkerIcon);

              // Set clicked marker to active icon.
              if (activeMarkerIcon) {
                marker.setIcon(activeMarkerIcon);
                marker.setZIndex(2);
                const label = marker.getLabel();
                if (label) {
                  label['color'] = '#a91b44';
                  marker.setLabel(label);
                }
              }

              // Update sidebar.
              Drupal.behaviors.googleMap.updateSidebar(data.ident, uniqueSidebarId, data.info_window_markup);

              // Center map by marker with sidebar offset.
              let sidebarWidth = 340;
              const sidebarElem = Drupal.behaviors.googleMap.getSidebarElem(uniqueSidebarId);
              if (sidebarElem) {
                sidebarWidth = sidebarElem.clientWidth + 10;
              }
              map.setCenter(marker.getPosition());
              map.panBy(sidebarWidth * -1 / 2, 0);
            });
          }

          // Add double click event listener.
          google.maps.event.addListener(marker, "dblclick", function (e) {
            // Set zoom.
            const destZoom = 15;
            if ( map.getZoom() !== destZoom) {
              // Zoom in.
              map.setZoom(15);
            } else {
              // Zoom out.
              if (settings.markers.length === 1) {
                map.setZoom(settings.map_zoom);
              } else {
                map.fitBounds(lat_lng_bounds); // auto-zoom
              }
            }

            // Trigger single click to open sidebar and center marker.
            new google.maps.event.trigger(marker, 'click');
          });

        })(marker, data);

        // Extend each marker's position in LatLngBounds object.
        lat_lng_bounds.extend(marker.position);

        // Set marker to array.
        markers.push(marker);
      }

      // Init sidebar:
      const sidebarDiv = document.createElement('div');
      sidebarDiv.className = "map-sidebar"
      sidebarDiv.id = uniqueSidebarId;

      const showSidebarOnLoad = settings.sidebar.on_load && settings.markers.length === 1 && settings.markers[0].info_window_markup;
      // console.log("showSidebarOnLoad", showSidebarOnLoad);

      if (!showSidebarOnLoad) {
        sidebarDiv.style.display = 'none';
      }

      sidebarDiv.innerHTML = this.getSidebarMarkup();
      sidebarDiv.querySelector('button.map-sidebar__close').addEventListener("click", function() {
        Drupal.behaviors.googleMap.hideSidebar(uniqueSidebarId, markers, defaultMarkerIcon);
      });
      sidebarDiv.querySelector('main').innerHTML = this.getSidebarLoadingMarkup();

      // Set sidebar as custom control:
      map.controls[google.maps.ControlPosition.LEFT_TOP].push(sidebarDiv);

      if (markers.length === 1) {
        // Single marker:

        /*
        // Set to active icon.
        if (showSidebarOnLoad && activeMarkerIcon) {
          markers[0].setIcon(activeMarkerIcon);
        }
        */

        // Center to maker with fixed zoom.
        map.setCenter(markers[0].getPosition());

        // Set zoom.
        map.setZoom(mapOptions.zoom);
      } else {
        // Multiple markers:
        // Center and adjust Zoom based on marker positions.
        map.setCenter(lat_lng_bounds.getCenter()); // set center
        map.fitBounds(lat_lng_bounds); // auto-zoom
      }

      // Show sidebar on load.
      if (showSidebarOnLoad) {
        google.maps.event.addListenerOnce(map, 'tilesloaded', function() {
          setTimeout(function() {
            new google.maps.event.trigger(markers[0], 'click');
          }, 500);
        });
      }

      // Routes:
      try {
        if (settings.routes && markers.length > 1) {
          const directionsService = new google.maps.DirectionsService();

          // console.log(markers);

          // Build routes:
          // for (let iM= 1; iM < 2; iM++) {
          for (let iM= 1; iM < settings.markers.length; iM++) {

            // console.log(iM  + ' -> ' + iM + 1, settings.markers[iM -1]);

            // Set markers.
            const originMarker = settings.markers[iM -1];
            const destinationMarker = settings.markers[iM];

            // Set positions.
            const originLatLng = new google.maps.LatLng(originMarker.lat, originMarker.lng);
            const destinationLatLng = new google.maps.LatLng(destinationMarker.lat, destinationMarker.lng);

            if (['plane', 'helicopter'].indexOf(originMarker.transportation_type) !== -1) {
              // Use polyline for flights.
              self.drawPolylineRoute(map, originLatLng, destinationLatLng, originMarker.transportation_icon, true);
              continue;
            }

            // Get directions.
            directionsService
              .route({
                origin: originLatLng,
                destination: destinationLatLng,
                travelMode: google.maps.TravelMode.DRIVING,
              })
              .then((response) => {
                // console.log("response", response);

                // Calculate midpoint of the route.
                const route = response.routes[0].overview_path;
                const midpoint = route[Math.floor(route.length / 2)];

                // Set transportation marker.
                self.drawTransportationMarker(map, midpoint, originMarker.transportation_icon, response.routes[0].legs[0].distance.text, response.routes[0].legs[0].duration.text)

                // Draw directions (without markers).

                new google.maps.DirectionsRenderer({
                  suppressMarkers: true,
                  preserveViewport: true,
                  map: map,
                  directions: response,
                  polylineOptions: {
                    strokeColor: '#0088FF',
                    strokeWeight: 5.5,
                    strokeOpacity: 0.55,
                    /*
                    icons: [
                      {
                        icon: {
                          path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        },
                        offset: "20%",
                      },
                    ],
                    */
                  }
                });
              })
              .catch((e) => {
                console.log('Directions request failed', e);
                // Fallback:
                // Draw polyline:
                self.drawPolylineRoute(map, originLatLng, destinationLatLng);
              });
          }
        }
      } catch (error) {
        console.error(error);
      }
    },

    /**
     * Draw transportation maker.
     */
    drawTransportationMarker: function(map, position, iconUrl, distance = undefined, duration = undefined) {
      if (!iconUrl) {
        return;
      }

      const marker = new google.maps.Marker({
        position: position,
        cursor: distance && duration ? 'pointer' : 'default',
        zIndex: 0,
        icon: {
          url: iconUrl,
          // scaledSize: new google.maps.Size(32, 32),
          scaledSize: new google.maps.Size(24, 24),
          // anchor: new google.maps.Point(0, 16),
        },
        map: map, // 'map' is your Google Maps map object
      });

      if (distance && duration) {
        // Add info window with distance and duration.
        const markup =
          '<div class="google-maps-transportation-info-window">' +
          '<strong>' + Drupal.t('Distance') + ':</strong> ' +
          distance + '<br>' +
          '<strong>' + Drupal.t('Duration') + ':</strong> ' +
          duration +
          '</div>'
        ;

        const infoWindow = new google.maps.InfoWindow({
          content: markup,
        });

        google.maps.event.addListener(marker, "click", function (e) {
          infoWindow.open({
            anchor: marker,
            map,
          });
        });
      }

      return marker;
    },

    /**
     * Draw polyline route.
     */
    drawPolylineRoute(map, originLatLng, destinationLatLng, iconUrl, dashed = false) {

      // Set path.
      const path = [originLatLng, destinationLatLng];

      // Draw transportation marker.
      this.drawTransportationMarker(map, google.maps.geometry.spherical.interpolate(path[0], path[1], 0.5), iconUrl);

      if (!dashed) {
        // Draw line.
        this.drawPolyline(map, path)
      } else {
        // Draw dashed line.
        this.drawDashedPolyline(map, path)
      }
    },

    /**
     * Draw polyline.
     */
    drawPolyline: function(map, path) {
      return new google.maps.Polyline({
        path: path,
        geodesic: true,
        strokeColor: '#0088FF',
        strokeWeight: 5.5,
        strokeOpacity: 0.55,
        map: map,
      });
    },

    /**
     * Draw dashed polyline.
     */
    drawDashedPolyline: function(map, path) {
      // Define a symbol used as dashed line icon.
      const lineSymbol = {
        path: "M 0,-1 0,1",
        strokeColor: '#0088FF',
        strokeWeight: 5.5,
        strokeOpacity: 0.55,
        scale: 4,
      };

      // console.log("setLine");
      return new google.maps.Polyline({
        path: path,
        geodesic: true,
        strokeOpacity: 0,
        icons: [
          {
            icon: lineSymbol,
            offset: "0",
            repeat: "20px",
          },
        ],
        map: map,
      });
    },

    /**
     * Reset marker icons.
     */
    resetMarkerIcons: function(markers, defaultMarkerIcon) {
      if (defaultMarkerIcon && markers.length > 1) {
        markers.forEach(function(m) {
          m.setIcon(defaultMarkerIcon);
          m.setZIndex(1);
          const label = m.getLabel();
          if (label) {
            delete label.color;
            m.setLabel(label);
          }
        });
      }
    },

    /**
     * Returns sidebar element.
     */
    getSidebarElem: function(sidebarId) {
      return document.getElementById(sidebarId);
    },

    /**
     * Shows sidebar.
     */
    showSidebar: function(sidebarId) {
      // console.log("showSidebar");
      const elem = this.getSidebarElem(sidebarId);
      if (elem) {
        elem.style.display = 'block';
      }
    },

    /**
     * Hides sidebar.
     */
    hideSidebar: function(sidebarId, markers, defaultMarkerIcon) {
      // console.log("hideSidebar");
      const elem = this.getSidebarElem(sidebarId);
      if (elem) {
        elem.style.display = 'none';
      }

      // Reset all markers to the default icon.
      this.resetMarkerIcons(markers, defaultMarkerIcon);
    },

    /**
     * Set loading markup to sidebar.
     */
    setLoadingMarkupToSidebar: function(sidebarId) {
      const elem = this.getSidebarElem(sidebarId);
      if (elem) {
        const mainElem = elem.querySelector('main');
        if (mainElem) {
          mainElem.innerHTML = this.getSidebarLoadingMarkup();
        }
      }
    },

    /**
     * Update sidebar with given markup.
     */
    updateSidebar(ident, sidebarId, markup) {
      // console.log("updateSidebar",ident, sidebarId);

      // Check if sidebar contains given identifier.
      const sidebarElem = this.getSidebarElem(sidebarId);
      if (sidebarElem && sidebarElem.querySelector('[data-ident="' + ident + '"]')) {
        // Show sidebar.
        this.showSidebar(sidebarId);
        return;
      }

      // Set loading markup to sidebar.
      this.setLoadingMarkupToSidebar(sidebarId);

      // Show sidebar.
      this.showSidebar(sidebarId);

      // Set markup.
      const destElem = document.querySelector('#' + sidebarId + ' main');
      if (destElem) {
        destElem.innerHTML = markup;
      }
    },

    /**
     * Update sidebar: Fetch content and show sidebar.
     */
    updateSidebarWithAjax(ident, sidebarId, settings) {
      // console.log("updateSidebar",ident, sidebarId);

      // Check if sidebar contains given identifier.
      const sidebarElem = this.getSidebarElem(sidebarId);
      if (sidebarElem && sidebarElem.querySelector('[data-ident="' + ident + '"]')) {
        // Show sidebar.
        this.showSidebar(sidebarId);
        return;
      }

      // Set loading markup to sidebar.
      this.setLoadingMarkupToSidebar(sidebarId);

      // Show sidebar.
      this.showSidebar(sidebarId);

      // Build url.
      let url = '/google-maps/info-window/' + encodeURIComponent(ident);
      Drupal.ajax({
        url: url,
        success: function (response, status) {
          // console.log("response", response, status);
          for (let i = 0, l = response.length; i < l; i++) {
            if (response[i].hasOwnProperty('selector') && response[i].selector === null) {
              response[i].selector = '#' + sidebarId + ' main *:first-child';
            }
          }
          return Drupal.Ajax.prototype.success.apply(this, [response, status]);
        }
      }).execute();
    },

    /**
     * Load Google Maps dynamically.
     *
     * @see: https://github.com/googlemaps/js-map-loader
     */
    loadGoogleMaps: function(mapElem, map_settings) {

      // Get placeholder.
      const privacy_placeholder = mapElem.querySelector('.gmap-privacy-placeholder');

      // Remove placeholder.
      if (privacy_placeholder) {
        privacy_placeholder.remove();
      }

      const loader = new google.maps.plugins.loader.Loader({
        apiKey: map_settings.api_key,
        language: map_settings.language,
        // version: "weekly",
        libraries: ["places", "geometry"]
      });

      // Promise
      loader
        .load()
        .then((google) => {
          // console.log('loader finished');
          this.initializeMap(mapElem, map_settings);
        })
        .catch(e => {
          // do something
        });
    },

    /**
     * Helper function to get a cookie by given name.
     */
    getCookie: function(name) {
      const arr = document.cookie.split(";");

      for (let i = 0; i < arr.length; i++) {
        const pair = arr[i].split("=");
        if (name === pair[0].trim()) {
          return decodeURIComponent(pair[1]);
        }
      }

      return null;
    },

    attach: function (context, settings) {

      once('googleMap', '.google-map-container', context).forEach( function (map_elem) {

        // console.log('attach googleMap...', rootElem);

        const self = Drupal.behaviors.googleMap;

        const privacy_cookie_name = 'gmap_privacy_confirmation';
        if (!settings.google_maps) {
          return;
        }

        const map_settings = settings.google_maps;
        // console.log(map_settings);

        // Check for general consent for Google Maps in COOKiES module.
        let generalConsent = false;

        try {
          let consentCookieContent = self.getCookie('cookiesjsr');
          if (consentCookieContent) {
            consentCookieContent = JSON.parse(consentCookieContent);
            // console.log(consentCookieContent);
            if (consentCookieContent.hasOwnProperty('google_maps') && consentCookieContent['google_maps']) {
              generalConsent = true;
            }
          }
        } catch (error) {
          generalConsent = false;
        }

        // Check for confirmation cookie.
        if (generalConsent || document.cookie.indexOf(privacy_cookie_name + '=1') !== -1) {
          // Cookie available, load map.
          self.loadGoogleMaps(map_elem, map_settings);
          return;
        }

        // Cookie not available:

        // Display placeholder.
        const privacy_placeholder = map_elem.querySelector('.google-map-privacy-placeholder');
        if (privacy_placeholder) {
          privacy_placeholder.style.display = 'flex';
        }

        // Get confirmation button.
        const confButton = map_elem.querySelector('button');
        if (confButton) {
          // Set click event to load Google Maps.
          confButton.addEventListener('click', () => {
            // Set confirmation cookie with one day validity.
            const daysToExpire = 1;
            const date = new Date();
            date.setTime(date.getTime()+(daysToExpire*24*60*60*1000));
            document.cookie = privacy_cookie_name + "=1;expires=" + date.toGMTString() + ";path=/";

            // Load Google Maps and initialize map.
            self.loadGoogleMaps(map_elem, map_settings);
          });
        }
      });
    },
  };

} (Drupal, once));
