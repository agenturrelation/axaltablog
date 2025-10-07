/**
 * @file
 * axalta behaviors.
 */
(function (Drupal, once) {

  'use strict';

  /**
  * Page header.
  */
  Drupal.behaviors.axaltaPageHeader = {
    attach: function (context, settings) {
      // console.log('mobileMenu', context);
      const pageHeader = context.querySelector('.navbar', context);

      const scrollPercent = 6;
      const classNameScroll = 'menu-scroll';
      const classNameHover = 'menu-hover';

      if (pageHeader) {
        const toggleClass = () => {
          const scrollPosY = window.pageYOffset || document.documentElement.scrollTop;
          const viewportHeight = window.innerHeight;
          const currScrollPercent = Math.round(scrollPosY / viewportHeight * 200);

          if (currScrollPercent >= scrollPercent && !pageHeader.classList.contains(classNameScroll)) {
            pageHeader.classList.add(classNameScroll);
          }
          else if (currScrollPercent < scrollPercent && pageHeader.classList.contains(classNameScroll)) {
            pageHeader.classList.remove(classNameScroll);
          }

          // console.log(viewportHeight, scrollPosY, currScrollPercent);
        }

        const addClass = () => {
          if (!pageHeader.classList.contains(classNameHover)) {
            pageHeader.classList.add(classNameHover);
          }
        }

        const removeClass = () => {
          if (pageHeader.classList.contains(classNameHover)) {
            pageHeader.classList.remove(classNameHover);
          }
        }

        // Toggle class on scroll.
        window.addEventListener('scroll', function (e) {
          toggleClass();
        });

        // Toggle class initially.
        toggleClass();
      }
    }
  };

  /**
 * Kombinierter Parallax-Effekt für Header-Slider.
 */
Drupal.behaviors.axaltaCombinedHeaderParallax = {
  attach: function (context, settings) {
    const sliders = once('axaltaCombinedHeaderParallax', '.header-media-slider', context);

    if (!sliders.length) return;

    const elementsToAnimate = [];

    sliders.forEach(function (sliderElem) {
      // Sammle alle relevanten Elemente mit jeweiligem Parallax-Faktor.
      sliderElem.querySelectorAll('.slider-markup-container').forEach(function (el) {
        elementsToAnimate.push({ element: el, factor: 0.9 });
      });

      sliderElem.querySelectorAll('.slider-media').forEach(function (el) {
        elementsToAnimate.push({ element: el, factor: 0.7 });
      });
    });

    // Parallax-Funktion, die bei scroll & resize ausgelöst wird.
    function applyParallax() {
      const viewPortWidth = window.innerWidth || document.documentElement.clientWidth;

      elementsToAnimate.forEach(({ element, factor }) => {
        if (viewPortWidth >= 768) {
          const offset = window.scrollY * factor;
          element.style.position = 'absolute';
          element.style.inset = 0;
          element.style.top = offset + 'px';
        } else {
          element.removeAttribute('style');
        }
      });
    }

    // Event-Listener einmal registrieren
    window.addEventListener('scroll', applyParallax, { passive: true });
    window.addEventListener('resize', () => {
      window.dispatchEvent(new CustomEvent('scroll'));
    });

    // Initial ausführen
    applyParallax();
  }
};


  /**
   * Main Menu.
  */
  Drupal.behaviors.axaltaMainMenu = {
    attach: function (context, settings) {

      // Get menu elem.
      const menuElem = once('axaltaMainMenu', '#main-menu', context)[0];
      if (!menuElem) {
        return;
      }

      // Get page wrapper elem.
      const pageWrapperElem = document.getElementById("page-wrapper");
      if (!pageWrapperElem) {
        return;
      }

      // Get page elem.
      const pageElem = document.getElementById("page");
      if (!pageElem) {
        return;
      }

      // Get menu toggle button.
      const toggleBtnElem = document.getElementById('main-menu-toggle');
      if (!toggleBtnElem) {
        return;
      }

      // Get menu toggle button.
      const backgroundMaskElem = document.getElementById('menu-background-mask');
      if (!backgroundMaskElem) {
        return;
      }

      // Get search form
      const searchForm = document.getElementById('axalta-misc-header-search-form');

      const htmlClass = 'main-menu-open';
      function open() {
        // menuElem.style.width = "250px";
        // pageWrapperElem.style.marginLeft = "-250px";
        document.documentElement.classList.add(htmlClass);
        toggleBtnElem.classList.add('open');
        backgroundMaskElem.classList.add('visible');

        /*
        if (!document.getElementById(backgroundMaskId)) {
          pageElem.insertAdjacentHTML('beforeend', '<div id="' + backgroundMaskId + '"></div>');
        }
        */
      }

      function close() {
        // menuElem.style.width = "0";
        // pageWrapperElem.style.marginLeft = "0";
        document.documentElement.classList.remove(htmlClass);
        toggleBtnElem.classList.remove('open');
        backgroundMaskElem.classList.remove('visible');
        if (searchForm) {
          searchForm.classList.remove('open');
        }
        /*
        if (document.getElementById(backgroundMaskId)) {
          document.getElementById(backgroundMaskId).remove();
        }
        */
      }

      // Handle toggle 'open' class to main menu.
      toggleBtnElem.addEventListener('click', () => {
        if (document.documentElement.classList.contains(htmlClass)) {
          close();
        } else {
          open();
        }
      });

      // Handle document click to close menu.
      // document.addEventListener('click', function (event) {
      //   console.log(event.target);
      //   if (!menuElem.contains(event.target) && !toggleBtnElem.contains(event.target) && document.documentElement.classList.contains(htmlClass)) {
      //     close();
      //   }
      // });
    }
  };

  /**
   * Back to top.
   */
  Drupal.behaviors.axaltaBackToTop = {
    attach: function (context, settings) {
      once('axaltaBackToTop', '#back-to-top', context).forEach(function (element) {
        element.addEventListener('click', () => {
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
        window.addEventListener('scroll', function () {
          if (window.scrollY >= 100) {
            element.classList.add('active');
          } else {
            element.classList.remove('active');
          }
        });
      });
    }
  };

  /**
   * Animation.
   */
  Drupal.behaviors.axaltaAnimations = {
    attach: function (context, settings) {

      const observerCallback = (entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('blur-in');
            observer.unobserve(entry.target);
          }
        });
      };

      once('axaltaAnimation', '.blur-in-elem', context).forEach(function (element) {
        const observer = new IntersectionObserver(observerCallback, {
          root: null,
          rootMargin: '0px',
          threshold: 0.2
        });
        observer.observe(element);
      });
    }
  };

  /**
   * Counter.
   */
  Drupal.behaviors.axaltaCounter = {
    attach: function (context, settings) {

      let counterClass = '.counter';
      let defaultSpeed = 3000; //default value

      function getVisibilityStatus(counterElement) {
        if (!counterElement || counterElement.classList.contains('initialized')) {
          return;
        }

        const windowScrollValFromTop = window.scrollY;
        const windowHeight = window.innerHeight;
        const fromTop = Math.ceil(counterElement.getBoundingClientRect().top + windowScrollValFromTop);

        if ((windowHeight + windowScrollValFromTop) > fromTop) {

          // Add clas to counter element to check that the animation is started.
          counterElement.classList.add('initialized');

          // Counter items loop.
          const counterItemElements = counterElement.querySelectorAll(counterClass);
          counterItemElements.forEach(function (counterItem, index) {

            let num = counterItem.getAttribute('data-TargetNum');
            let speed = counterItem.getAttribute('data-Speed');
            let direction = counterItem.getAttribute('data-Direction');
            let easing = counterItem.getAttribute('data-Easing');

            if (speed === null) {
              speed = defaultSpeed;
            }

            // Add a class to recognize each counter item.
            counterItem.classList.add('c_' + index);

            // Start counter animation.
            doCount(counterItem, num, speed, direction, easing);
          });
        }
      }

      function doCount(counterItemElem, num, speed, direction, easing) {
        if (!counterItemElem) {
          return;
        }
        if (easing === undefined) {
          easing = "swing";
        }
        let start = null;
        const initialNum = direction === 'reverse' ? num : 0;
        function animateCount(timestamp) {
          if (!start) start = timestamp;
          const elapsed = timestamp - start;
          const progress = Math.min(elapsed / speed, 1);
          const currentNum = initialNum + progress * (num - initialNum);

          if (direction === 'reverse') {
            counterItemElem.textContent = Math.floor(num - currentNum);
          } else {
            counterItemElem.textContent = Math.floor(currentNum);
          }

          if (progress < 1) {
            window.requestAnimationFrame(animateCount);
          }
        }

        window.requestAnimationFrame(animateCount);
      }

      once('axaltaCounter', '#counters_1, #counters_2, #counters_3', context).forEach(function (element) {

        // Init if it becomes visible by scrolling.
        window.addEventListener('scroll', function () {
          getVisibilityStatus(element);
        });

        // Init if it's visible by page loading.
        getVisibilityStatus(element);
      });
    }
  };

}(Drupal, once));