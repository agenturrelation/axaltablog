(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.header_media_slider = {
    attach: function (context, settings) {
      // console.log('header_media_slider attach');

      once('headerMediaSlider', '.header-media-slider', context).forEach(function (elem) {
        // Get number of slides.
        const num_slides = elem.querySelectorAll("li.splide__slide").length;
        // console.log(num_slides, 'num slides');

        new Splide(elem, {
          perPage: 1,
          cover: false,
          pagination: num_slides > 1,
          drag: num_slides > 1,
          arrows: false,
          fixedWidth: '100vw',
          autoHeight: true,
          pauseOnHover: false,
          type: 'fade',
          video: {
            autoplay: true,
            mute: true,
            loop: true,
            pauseOnHover: false,
            pauseOnFocus: false,
            playerOptions: {
              htmlVideo: {
                playsInline: true,
                autoplay: true,
                muted: true,
                loop: true,
                controls: false,
                pauseOnHover: false,
                pauseOnFocus: false,
              },
              youtube: {
                playsInline: true,
                autoplay: true,
                muted: true,
                loop: true,
                controls: false,
                pauseOnHover: false,
                pauseOnFocus: false,
              },
            },
          },
        }).mount(window.splide.Extensions);
      });
    }
  };

} (Drupal, once));
