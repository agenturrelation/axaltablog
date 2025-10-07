/**
 * @file
 * cookiesGoogleMaps behaviors.
 */

(function (Drupal) {
  'use strict';

  /**
   * Define defaults.
   */
  Drupal.behaviors.cookiesGoogleMaps = {

    // id corresponding to the cookies_service.schema->id.
    id: 'google_maps',

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

    /**
     * Called when consent was given.
     */
    consentGiven: function (context) {
      // console.log('consent given');

      // Get privacy confirmation cookie for Google Maps Module.
      const gmapPrivacyCookie = this.getCookie('gmap_privacy_confirmation');
      // console.log("gmapPrivacyCookie", gmapPrivacyCookie);
      if (!gmapPrivacyCookie || gmapPrivacyCookie !== "1") {
        // gmap_privacy_confirmation cookie is not yet set.
        // console.log("gmapPrivacyCookie not set", gmapPrivacyCookie);

        // Set cookies expires date.
        let daysToExpire = 1;
        if (document.cookiesjsr && document.cookiesjsr.config && document.cookiesjsr.config.cookie && document.cookiesjsr.config.cookie.expires) {
          // Get cookies expires days form cookiesjsr.
          daysToExpire = document.cookiesjsr.config.cookie.expires / 24 / 60 / 60 / 1000;
        }
        const expireDate = new Date();
        expireDate.setTime(expireDate.getTime()+(daysToExpire*24*60*60*1000));
        // console.log(daysToExpire, expireDate);

        // Set privacy confirmation cookie for Google Maps Module.
        document.cookie = "gmap_privacy_confirmation=1;expires=" + expireDate.toGMTString() + ";path=/";
      }
    },

    /**
     * Called when consent was denied / revoked.
     */
    consentDenied: function (context) {
      console.log('consent denied');

      // Get privacy confirmation cookie for Google Maps Module.
      const gmapPrivacyCookie = this.getCookie('gmap_privacy_confirmation');

    },

    attach: function (context) {
      const self = this;
      document.addEventListener('cookiesjsrUserConsent', function (event) {
        const service = (typeof event.detail.services === 'object') ? event.detail.services : {};
        // console.log(service);
        if (typeof service[self.id] !== 'undefined' && service[self.id]) {
          // Consent was given:
          self.consentGiven(context);
        } else {
          // Consent was denied/revoked.
          self.consentDenied(context);
        }
      });
    },
  };

})(Drupal);
