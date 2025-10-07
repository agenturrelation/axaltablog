/**
 * @file
 * imageSliderFormatter behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.imageSliderFormatter = {
    attach: function (context, settings) {

      // console.log('imageSliderFormatter attach');

      once('imageSliderFormatter', 'html', context).forEach( function (rootElem) {
        // Default Image slider.
        rootElem.querySelectorAll(".image-slider").forEach(function (elem) {
          new Splide(elem, {
            perMove: 1,
            lazyLoad: 'nearby',
            arrows: true,
            pagination: false,
            rewind: true,
            perPage: 1,
            keyboard: true,
            paginationKeyboard: true,
          }).mount();
        });
      });
    }
  };

} (Drupal, once));
