window.uaWn = (function ( document, $, uaWnSettings ) {
  'use strict';

  var promptSettings = 1;

  /**
   * Get visitor's page views
   * @returns {*}
   */
  function getPageViews() {
    var name = "ua-wn-pv=";
    var ca = document.cookie.split( ';' );
    for ( var i = 0; i < ca.length; i++ ) {
      var c = ca[ i ].trim();
      if ( c.indexOf( name ) === 0 ) {
        return parseInt( c.substring( name.length, c.length ) );
      }
    }
    return 0;
  }

  /**
   * Store this visitor's page views on client side
   */
  function setPageViews( pv_count ) {
    var date = new Date();
    date.setTime( date.getTime() + ( 60 * 24 * 60 * 60 * 1000 ) );
    var expires = date.toUTCString();
    document.cookie = 'ua-wn-pv=' + pv_count + ';' +
      'expires=' + expires + ';' +
      'path=/;';
  }

  /**
   * Determine if it can/should offer notification
   *
   * @param sdk
   * @returns {boolean}
   */
  function canOfferNotification( sdk ) {
    return !( !sdk.isSupported || !sdk.canRegister || ( sdk.channel && sdk.channel.optedIn ) );
  }

  /**
   * Offer notification
   */
  function offerNotifications() {
    if ( typeof UA === 'object' ) {
      UA.then( function ( sdk ) {
        if ( ! canOfferNotification( sdk ) ) {
          return;
        }
        var OptInSuccess = function(e) {
          $( '.ua-opt-in' ).hide();
        };
        var OptInFail = function(e) {
          $( '.ua-opt-in' ).show();
        };
        sdk.register().then( OptInSuccess, OptInFail );
      });
    }
  }

  /**
   * Initialize page view prompt
   */
  function initPageViewPrompt() {

    // get current page views
    var pv = getPageViews() + 1;

    // update page views
    setPageViews( pv );

    /**
     * Return if
     * page view prompt is not enabled
     * OR prompt view setting is set to 0
     * OR page view is less than setting value
     */
    if ( ! promptSettings.enabled || parseInt( promptSettings.prompt_views ) === 0 || promptSettings.prompt_views > pv ) {
      return;
    }

    /**
     * Return if
     * page view is grater than prompt view setting value
     * AND ( prompt again view setting is set to 0
     * OR page view hasn't reached to "show prompt again after every [x] views" setting value )
     */
    if( pv > promptSettings.prompt_views
      && ( ( parseInt( promptSettings.prompt_again_views ) === 0 )
        || ( ( pv - parseInt( promptSettings.prompt_views ) ) % parseInt( promptSettings.prompt_again_views ) )
      ) ){
      return;
    }

    /*
    Offer notification if view prompt value is higher or same as page views
     */
    if ( typeof UA === 'object' ) {
      offerNotifications();
    }
  }

  /**
   * Initialize functionality
   */
  function initialize() {
    promptSettings = uaWnSettings.prompt;

    // Initialize page view prompt
    initPageViewPrompt();

    // Handle Opt In button click
    $( document ).on( 'click', '.ua-opt-in', function ( e ) {
      e.preventDefault();
      offerNotifications();
    } );

    // Hide opt in button if not supported or already opted in
    UA.then( function ( sdk ) {
      if ( ! canOfferNotification( sdk ) ) {
        $( '.ua-opt-in' ).hide();
      }
    });
  }

  $( document ).ready( function () {
    initialize();
  } );

})( document, jQuery, uaWnSettings );