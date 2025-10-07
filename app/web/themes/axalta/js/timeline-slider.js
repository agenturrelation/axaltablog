/**
 * @file
 * timelineSlider behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.timelineSlider = {
    attach: function (context, settings) {
      // console.log('timelineSlider attach');

      once('timelineSlider', 'html', context).forEach( function (rootElem) {
        // Default Image slider.
        rootElem.querySelectorAll(".splide.timeline-slider").forEach(function (elem) {
          new Splide(elem, {
            perMove: 1,
            lazyLoad: 'nearby',
            arrows: true,
            pagination: false,
            gap: 20,
            rewind: true,
            perPage: 4,
            focus: 'number',
            isNavigation: true,
            keyboard: true,
            paginationKeyboard: true,
            breakpoints: {
              1280: {
                perPage: 3,
              },
              1024: {
                perPage: 2,
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
