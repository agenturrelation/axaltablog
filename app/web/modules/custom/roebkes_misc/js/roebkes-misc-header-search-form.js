/**
 * JS File for roebkes Header Search Form.
 */

(function (Drupal, once) {

  Drupal.behaviors.roebkesHeaderSearchForm = {
    attach: function (context) {

      once('roebkesHeaderSearchForm', 'form#roebkes-misc-header-search-form', context).forEach(function (elemForm) {

        // Get trigger button.
        const triggerButton = elemForm.querySelector('button#cmhsf-trigger');
        if (!triggerButton) {
          return;
        }

        // Set 'click' listener to trigger button.
        triggerButton.addEventListener('click', function() {
          // Get required elements.
          const mainMenuBtnElem = document.getElementById('main-menu-toggle');
          if (!mainMenuBtnElem) {
            return;
          }

          // Trigger menu button click.
          if (!mainMenuBtnElem.classList.contains('open')) {
            mainMenuBtnElem.click();
          }

          // Toggle 'open' class on form.
          if (elemForm.classList.contains('open')) {
            elemForm.classList.remove('open');
          } else {
            elemForm.classList.add('open');
          }
        });

        // Get search query input field.
        const queryInputElem = elemForm.querySelector('input[name="query"]');
        if (!queryInputElem) {
          return;
        }

        // Get submit button.
        const submitButton = elemForm.querySelector('input[type="submit"]');
        if (!submitButton) {
          return;
        }

        // Handle form submission.
        elemForm.addEventListener('submit', function(e) {
          if(queryInputElem.value.trim() === '') {
            // Skip submission if query value id empty.
            e.preventDefault();
          }
        });

        // Set 'click' listener to category buttons.
        elemForm.querySelectorAll('.cmhsf-categories button').forEach(function (btnElem) {
          btnElem.addEventListener('click', function() {
            // Set textContent as query value.
            queryInputElem.value = btnElem.textContent;
            // Submit button click
            submitButton.click();
          });
        })

      });

    }
  };

} (Drupal, once));
