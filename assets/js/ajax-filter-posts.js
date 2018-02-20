(function (){

  var queryParams = null;
  var container = document.querySelector('.js-container-async');
  if (container) {
    var filterTogglers = container.getElementsByClassName('js-toggle-filters');
    var content = container.querySelector('.ajax-posts__posts');
    var status = container.querySelector('.ajax-posts__status');
  }

  function init() {
    if (container) {
      addEventListeners();
      setDefaults();
    }
  }

  /**
   * Add event listeners
   */
  function addEventListeners() {
      // Add event listeners with event propagination
      on(container,'click', 'a[data-filter]', function(event) { 
        handleFilterEvent(event.target);
        event.preventDefault();
        return true;
      });

      on(container, 'click', '.js-collapse-filterlist', function(event) { 
          toggleFilterListCollapse(event);
          event.preventDefault();
          return true;
      });

      on(container, 'click', '.js-reset-filters', function() { 
          resetFilters();
          event.preventDefault();
          return true;
      });

      on(container, 'click', '.js-load-more', function(event) { 
          handleLoadMoreEvent(event.target);
          event.preventDefault();
          return true;
      });

      // Add event listner on all filter toggle buttons
      [].slice.call(filterTogglers).forEach(function(button){
        button.addEventListener('click', toggleFilters)
      });
  }

  /**
   * Set default query variables
   */
  function setDefaults() {
    queryParams = {
      'page' : null,
      'tax'  : {},
      'quantity': parseInt(container.dataset.quantity, 10) || 0,
      'postType': container.dataset.postType || 'post',
      'language': filterPosts.language || null,
    };
  }

  /**
   * Update query and get posts after filter change
   * 
   * @param  NodeElement  filter  Clicked filter
   */
  function handleFilterEvent(filter) {

    if (!filter.classList.contains('is-active')) {
      filter.classList.add('is-active');
      updateQueryParams({
        page: 1,
        filter: filter.dataset.filter,
        term: filter.dataset.term,
      });
    } else {
      filter.classList.remove('is-active');
      removeQueryParam(filter.dataset.filter, filter.dataset.term);
    }
    getAJAXPosts({reset: true});
  }

  /**
   * Get next page of posts
   * 
   * @param  NodeElement  button  Clicked load more button
   */
  function handleLoadMoreEvent(button){
    updateQueryParams({ page: parseInt(button.dataset.page, 10) })
    getAJAXPosts({reset: false});
  }

  /**
   * Reset filters to empty values
   */
  function resetFilters() {
    // Convert nodeList to array to prevent fail on for each
    var activeFilters = Array.prototype.slice.call(container.querySelectorAll('a[data-filter].is-active'));

    // Remove active classes
    activeFilters.forEach(function(filter){
      filter.classList.remove('is-active');
    });

    // Empty taxonomy from query
    queryParams.tax = {};
    queryParams.page = 1;
    getAJAXPosts({reset: true});
  }

  /**
   * Toggle the filter class.
   * Called via event listeners
   */
  function toggleFilters() {
    container.classList.toggle('is-expanded-filters');
  }

  function toggleFilterListCollapse(event) {
    event.target.parentNode.parentNode.classList.toggle('is-collapsed');
  }

  /**
   * Update te query parameters based on the filter change
   * 
   * @param  Array      params    params that will be changed
   */
  function updateQueryParams(params) {
    queryParams.page = params.page;

    // If we're also updating the taxonomy
    if (params.filter) {
      if (queryParams.tax.hasOwnProperty(params.filter)) {
        queryParams.tax[params.filter].push(params.term);
      } else {
        queryParams.tax[params.filter] = [params.term];
      }
    }
  }

  /**
   * Remove a term from the set of query params
   * 
   * @param  string   tax   taxonomy of the term to remove
   * @param  {string   term  term to remove
   */
  function removeQueryParam(tax, term) {
    if (queryParams.tax.hasOwnProperty(tax)) {
      if (queryParams.tax[tax].indexOf(term) > -1) {
        queryParams.tax[tax].splice( queryParams.tax[tax].indexOf(term) , 1 );
      }
    }
  }

  /**
   * Show the Error Reponse div
   */
  function showResponseMessage() {
    status.style.display = 'block';
    status.scrollIntoView({behavior: "smooth"});
  }

  /**
   * Show the Error Reponse div
   */
  function hideResponseMessage() {
    status.style.display = 'none';
  }

  /**
   * Get new posts via Ajax
   *
   * Retrieve a new set of posts based on the created query
   * 
   * @return string server side generated HTML
   */
  function getAJAXPosts(args) {

    // Set status to querying
    container.classList.add('is-waiting');

    var request = new XMLHttpRequest();
    request.open('POST', filterPosts.ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    request.timeout = 4000; // time in milliseconds
    
    request.onload = function() {

      console.log(this.response);

      //remove load more button
      var loadMoreButton = content.querySelector('.js-load-more');
      if (loadMoreButton) {
        content.removeChild(loadMoreButton.parentNode);
      }
      var response = JSON.parse(this.response);
      if (this.status === 200) {
        // If we have a succesfull query
        if (response.success) {
          // Hide error status button
          hideResponseMessage();
          // If we have to remove the show more button
          if (args.reset) {
            content.innerHTML = response.data.content;        
          } else {
            content.innerHTML += response.data.content;
          }          
        } else {
          status.innerHTML = response.data;
          showResponseMessage();
        }
      } else {
        status.innerHTML = filterPosts.serverErrorMessage;
        showResponseMessage();
      }
      // Resolve status
      container.classList.remove('is-waiting');
    };

    request.ontimeout = function() {
      status.innerHTML = filterPosts.timeoutMessage;
      showResponseMessage();
      container.classList.remove('is-waiting');
    }

    request.send(objectToQueryString({
      action: 'process_filter_change',
      nonce: filterPosts.nonce,
      params: queryParams,    
    }));
  }

  /**
   * Helper function for event delegation
   *
   * To add event listeners on dynamic content, you can add a listener 
   * on thewrapping container, find the dom-node that triggered 
   * the event and check if that node mach our 
   * 
   * @param  NodeElement  el          wrapping element for the dynamic content
   * @param  string       eventName   type of event, e.g. click, mouseenter, etc
   * @param  string       selector    selector criteria of the element where the action should be on
   * @param  Function     fn          callback funciton
   * @return Function     The callback
   */
  function on(el, eventName, selector, fn) {
    var element = el;

    element.addEventListener(eventName, function(event) {
        var possibleTargets = element.querySelectorAll(selector);

        var target = event.target;

        for (var i = 0, l = possibleTargets.length; i < l; i++) {
            var el = target;
            var p = possibleTargets[i];

            while(el && el !== element) {
                if (el === p) {
                    return fn.call(p, event);
                }

                el = el.parentNode;
            }
        }
    });
  }

  /**
   * Convert an deep object to a url parameter list
   *
   * Boiled down from jQuery
   *
   * WordPress Ajax post request doesn't accept JSON, only form-urlencoded!
   * Took me a while to get...
   * 
   * Although seems not to be totally true: 
   * http://wordpress.stackexchange.com/questions/177554/allowing-admin-ajax-php-to-receive-application-json-instead-of-x-www-form-url
   *
   * But in this case we just convert the params object to a url encoded string, like our friend and foe jQuery does. 
   *
   */
  function objectToQueryString(a) {
        var prefix, s, add, name, r20, output;
        s = [];
        r20 = /%20/g;
        add = function (key, value) {
            // If value is a function, invoke it and return its value
            value = ( typeof value == 'function' ) ? value() : ( value == null ? "" : value );
            s[ s.length ] = encodeURIComponent(key) + "=" + encodeURIComponent(value);
        };
        if (a instanceof Array) {
            for (name in a) {
                add(name, a[name]);
            }
        } else {
            for (prefix in a) {
                buildParams(prefix, a[ prefix ], add);
            }
        }
        output = s.join("&").replace(r20, "+");
        return output;
    };

    /**
     * Helper function to create URL parameters of deep object
     *
     * Boiled down from jQuery
     */
    function buildParams(prefix, obj, add) {
        var name, i, l, rbracket;
        rbracket = /\[\]$/;
        if (obj instanceof Array) {
            for (i = 0, l = obj.length; i < l; i++) {
                if (rbracket.test(prefix)) {
                    add(prefix, obj[i]);
                } else {
                    buildParams(prefix + "[" + ( typeof obj[i] === "object" ? i : "" ) + "]", obj[i], add);
                }
            }
        } else if (typeof obj == "object") {
            // Serialize object item.
            for (name in obj) {
                buildParams(prefix + "[" + name + "]", obj[ name ], add);
            }
        } else {
            // Serialize scalar item.
            add(prefix, obj);
        }
    }

  init();

}());
