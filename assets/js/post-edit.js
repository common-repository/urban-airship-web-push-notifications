( function ( window, $, wp ) {
	"use strict";

	$( document ).ready( function () {

		var $enableNotification = $( '#ua-wn-enable-post-notification' );
		var $notificationData = $( '.ua-wn-meta-data' );

		if( $enableNotification.length > 0 ) {
			$enableNotification.on( 'change', function ( e ) {
				if( this.checked ) {
					$notificationData.removeClass( 'ua-hide' );
				} else {
					$notificationData.addClass( 'ua-hide' );
				}
			} );
		}

		$( document ).on( 'click', '#ua-wn-icon-select', function ( e ) {
			var mediaUploader;

			e.preventDefault();

			if ( mediaUploader ) {
				mediaUploader.open();
				return;
			}

			mediaUploader = wp.media( {
				title: 'Choose Icon',
				button: {
					text: 'Choose Icon'
				},
				library: {
					type: 'image'
				},
				multiple: false
			} );

			mediaUploader.on( 'select', function() {
				var attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
				document.getElementById( 'ua-wn-notification-data-icon' ).value = attachment.id;
				document.getElementById( 'ua-wn-notification-data-icon' ).checked = true;
				document.getElementById( 'ua-wn-icon-preview' ).attributes.src.value = attachment.url;
			} );

			mediaUploader.open();
		} );
		
		$( document ).on( 'click', '#ua-wn-notification-data-icon', function ( e ) {
			document.getElementById( 'ua-wn-icon-select' ).click();
		} );

		$( '.post-type-ua-push-notification' ).on( 'click', '#publish', function ( e ) {
			var elText = document.getElementsByName( 'ua-wn-notification-data[text]' )[0],
				errorEL = document.getElementsByClassName( 'ua-text-required-error' ),
				tempEl;
			if( elText && elText.value.trim() === '' ) {
				e.preventDefault();
				elText.focus();
				if( errorEL.length === 0 ) {
					tempEl = document.createElement( 'span' );
					tempEl.classList.add( 'ua-text-required-error' );
					tempEl.innerText = 'This field is required.';
					elText.parentNode.parentNode.appendChild( tempEl );
				}
			} else {
				errorEL[0].remove();
			}
		} );
	} );

} )( window, jQuery, wp );