/**
 * @file
 * testimonialsSlider behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.testimonialsSlider = {
    attach: function (context, settings) {
      // console.log('testimonialsSlider attach');

      once('testimonialsSlider', 'html', context).forEach( function (rootElem) {
        // Default Image slider.
        rootElem.querySelectorAll(".splide.testimonial-slider").forEach(function (elem) {
          new Splide(elem, {
            perMove: 1,
            perSlide: 1,
            rewind : false,
            lazyLoad: 'nearby',
            arrows: false,
            pagination: true,
            gap: 100,
            perPage: 2,
            keyboard: true,
            paginationKeyboard: true,
            breakpoints: {
              1280: {
                perPage: 2,
              },
              1024: {
                perPage: 1,
              },
              768: {
                perPage: 1,
              },
            }
          }).mount();
        });
      });
    }
  };

} (Drupal, once));
