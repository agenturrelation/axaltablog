(function()
{
  /*** Register plugin in window object */

  this.GooglePlacesAutocomplete = function()
  {
    let defaults = {};

    this.inputElement = arguments[0];
    this.settings = (arguments[1] && typeof arguments[1] === 'object') ? extendDefaults(defaults,arguments[1]) : defaults;

    // console.log(this.settings, 'settings');
    // console.log(this.inputElement, 'elements');

    this.init();
  }

  /*** Public Methods */

  GooglePlacesAutocomplete.prototype.init = function()
  {
    // console.log('Init plugin.');

    build.call(this);
  }

  GooglePlacesAutocomplete.prototype.getPlaceDetails = async function(placeId)
  {
    // console.log('getPlace', placeId);
    const url = '/places_autocomplete/place_details' + '?q=' + encodeURIComponent(placeId);
    const response = await fetch(url);

    return await response.json();
  }

  /*** Private Methods */

  function build()
  {
    // console.log('Build plugin.', this.inputElement);

    // Set local input element.
    const inputElement = this.inputElement;

    // Build autocomplete with autocompleter.
    // @see: https://github.com/kraaden/autocomplete
    autocomplete({
      input: inputElement,
      minLength: 3,
      debounceWaitMs: 200,
      emptyMsg: "Kein Ort gefunden",
      fetch: function(query, update) {
        const url = '/places_autocomplete/search' + '?q=' + encodeURIComponent(query);
        fetch(url)
          .then(response => response.json())
          .then(suggestions => {
            // console.log("suggestions", suggestions);
            update(suggestions);
          }).catch(()=>{
          console.log("catch");
          update([]);
        });
      },
      render: function(item, value) {
        const itemElement = document.createElement("div");
        const regex = new RegExp(value, 'gi');
        itemElement.innerHTML = item.label.replace(regex, function (match) {
          return "<strong>" + match + "</strong>"
        });
        return itemElement;
      },
      onSelect: function(item) {
        // console.log(item, 'item selected');

        // Set label as value to input element.
        inputElement.value = item.label;

        // Dispatch 'place_changed' event on input element.
        inputElement.dispatchEvent(new CustomEvent("place_changed", {detail: item}));
      }
    });
  }

  function extendDefaults(defaults,properties)
  {
    Object.keys(properties).forEach(property => {
      if(properties.hasOwnProperty(property))
      {
        defaults[property] = properties[property];
      }
    });
    return defaults;
  }
}());
