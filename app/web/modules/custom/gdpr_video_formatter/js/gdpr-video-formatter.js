/**
 * @file
 * gdprVideoFormatter behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.gdprVideoFormatter = {
    attach: function (context, settings) {

      once('gdprVideoFormatter', '.gdpr-video-wrapper', context).forEach( function (elem) {

        // Get video ID.
        const videoID = elem.dataset.videoId;
        if (!videoID || videoID.length === 0) {
          return;
        }
        // console.log("videoID", videoID);

        // Set container / inner container.
        const innerContainer = elem.querySelector('.gdpr-video-inner-wrapper');
        if (!innerContainer) {
          return;
        }

        // Add 'processed' class to container.
        elem.classList.add('processed');

        // Set click handler to play video.
        innerContainer.addEventListener('click', (event) => {
          event.preventDefault();

          const src = 'https://www.youtube-nocookie.com/embed/' + videoID + '?autoplay=1&amp;controls=1&amp;wmode=opaque&amp;rel=0&amp;egm=0&amp;iv_load_policy=3&amp;hd=0';
          const iframe = '<iframe width="560" height="315" src="' + src + '" allow="autoplay" allowFullScreen></iframe>';

          const innerContainer = elem.querySelector('.gdpr-video-inner-wrapper');
          if (!innerContainer) {
            return;
          }

          // Replace inner container with iframe.
          innerContainer.innerHTML = iframe;
        });

      });


    }
  };

} (Drupal, once));
