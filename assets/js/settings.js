( function ( document, $ ) {
  "use strict";

  $( document ).ready( function () {
    if( $( 'body.settings_page_ua-web-notification' ).length > 0 ) {

      // Add role=presentation to form tables
      $( 'table.form-table' ).attr( 'role', 'presentation' );

      // Handle upload new zip file click
      $( '#ua-wn-sdk-upload-new' ).click( function ( e ) {
        e.preventDefault();
        $( e.target ).parents( '.ua-wn-sdk-upload-wrap' ).removeClass( 'ua-wn-uploaded' );
      } );
    }
  } );


} )( document, jQuery );
