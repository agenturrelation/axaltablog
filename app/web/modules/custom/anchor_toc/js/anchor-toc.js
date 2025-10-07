/**
 * @file
 * anchorToc behaviors.
 */

(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.anchorToc = {
    attach: function (context, settings) {

      // Store behaviour.
      const self = this;
      once('anchorToc', '.anchor-toc', context).forEach(function (toc) {

        // Get body class from data attribute.
        let bodyClass = toc.getAttribute('data-atoc-body-class');
        if (!bodyClass) {
          bodyClass = 'has-anchor-toc';
        }

        // Add class to body.
        const body = context.querySelector('body');
        if (body && !body.classList.contains(bodyClass)) {
          body.classList.add(bodyClass);
        }

        // Get offset position from data attribute.
        let offsetPosition = toc.getAttribute('data-atoc-offset-pos');
        if (['top', 'bottom'].indexOf(offsetPosition) === -1) {
          // Default offset position.
          offsetPosition = 'top';
        }
        // console.log("offsetPosition", offsetPosition);

        // Get additional offset value from data attribute.
        let offsetAdditional = parseInt(toc.getAttribute('data-atoc-offset'));
        if(isNaN(offsetAdditional)) {
          // Default additional offset.
          offsetAdditional = 0
        }
        // console.log("offsetAdditional", offsetAdditional);

        // Set sections.
        const sections = [];
        let sectionsCount = 0;
        toc.querySelectorAll('a').forEach(link => {
          const href = link.getAttribute('href');
          const id = href.replace(/#/i, "");
          const section = context.getElementById(id);
          if (section) {
            sections.push(section);
            sectionsCount++;
          }
        });
        // console.log("sections",sections);
        // console.log("sectionsCount",sectionsCount);

        // Scroll event listener.
        window.addEventListener("scroll", event => {

          // Toggle visibility.
          self.toggleVisibility(toc, sections);

          // Get TOC rect.
          const tocRect = toc.getBoundingClientRect();

          // Set offset.
          let offset = offsetAdditional;
          if (offsetPosition === "top") {
            offset += Math.round(tocRect.top);
          }
          else {
            offset += Math.round(tocRect.bottom);
          }

          // Handle active links:

          // Get active link.
          const activeLink = toc.querySelector("a.active");

          for (let i = 0; i < sectionsCount; i++) {
            const section = sections[sectionsCount - 1 - i];
            const link = toc.querySelector("a[href*=" + section.id + "]");
            const sectionRect = section.getBoundingClientRect();

            if (!link) {
              continue;
            }

            // if (scrollPos >= section.offsetTop) {
            if (sectionRect.top <= offset) {
              // Scroll position is equal or greater than current section.
              if (activeLink && activeLink !== link) {
                activeLink.classList.remove('active');
              }
              link.classList.add("active");
              return;
            } else if (link.classList.contains('active')) {
              // is lower.
              // Scroll position is lower than current section.
              link.classList.remove('active');
              return;
            }
          }
        });

        // Toggle visibility on startup.
        self.toggleVisibility(toc, sections);
      });

    },

    /**
     * Toggle visibility.
     */
    toggleVisibility: function (toc, sections) {

      if (sections.length === 0) {
        return;
      }

      const tocRect = toc.getBoundingClientRect();
      const tocYBottom = Math.round(tocRect.bottom);

      const firstSectionRec = sections[0].getBoundingClientRect();
      const firstSectionYTop = Math.round(firstSectionRec.top);

      if (firstSectionYTop < tocYBottom) {
        // Scroll position is greater than first section.
        // Show toc.
        if (!toc.classList.contains('visible')) {
          toc.classList.add('visible');
        }
      } else {
        // Scroll position is lower than first section.
        // Hide toc.
        toc.classList.remove('visible');
      }
    }
  };

} (Drupal, once));
