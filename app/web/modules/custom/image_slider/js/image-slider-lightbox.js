/**
 * @file
 * imageSliderLightbox behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.imageSliderLightbox = {
    attach: function (context, settings) {

      once('imageSliderLightbox', 'html', context).forEach( function (rootElem) {

        // Non Splide elements.
        rootElem.querySelectorAll(".lightbox-gallery:not(.splide)").forEach(function (elem) {
          Drupal.behaviors.imageSliderLightbox.initLightbox(elem);
        });

        // Initialized Splide elements.
        rootElem.querySelectorAll(".splide.lightbox-gallery.is-initialized").forEach(function (elem) {
          Drupal.behaviors.imageSliderLightbox.initLightbox(elem);
        });

        // Uninitialized Splide elements.
        rootElem.querySelectorAll(".splide.lightbox-gallery:not(.is-initialized)").forEach(function (elem) {
          setTimeout(() => {
            Drupal.behaviors.imageSliderLightbox.initLightbox(elem);
          }, 0);
        });
      });
    },

    /**
     * Init lightbox.
     */
    initLightbox: function(elem) {
      // console.log('init lightbox');
      lightGallery(elem, {
        speed: 500,
        showZoomInOutIcons: true,
        actualSize: false,
        plugins: [lgZoom],
        download: false,
        zoom: false,
        allowMediaOverlap: true,
        selector: "a",
        mobileSettings: {
          showCloseIcon: true
        },
        licenseKey: "1234-1234-123-1234",
      });
    }
  };

} (Drupal, once));
