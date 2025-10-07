/**
 * JS File for Icon Picker.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.iconPicker = {

    attach: function (context, settings) {

      // Add icon to option and selection display.
      const optionDisplay = (data, escape) => {
        // console.log(state, 'state');

        if (data.value && data.value.length > 0 ) {
          // With icon.
          return '<div class="icon-picker-option">' +
            '<span style="padding-right:20px;" class="material-symbols-outlined">' + data.value + '</span>' + 
            '<span>' + escape(data.text) + '</span>' +
          '</div>';
        }
        // Without icon (empty option).
        return '<div>' +
          '<span>' + escape(data.text) + '</span>' +
        '</div>';
      };

      once('iconPicker', 'select.icon-picker', context).forEach( function (element) {
        new TomSelect(element, {
          allowEmptyOption: true,
          highlight: true,
          maxOptions: 1000,
          render: {
            option: function(data, escape) {
              return optionDisplay(data, escape);
            },
            item: function(data, escape) {
              return optionDisplay(data, escape);
            },
          }
        });
      });
    }
  };

} (Drupal, once));
