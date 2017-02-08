/**
 * PHP Notifier Notice Scripts
 */
( function( $ ) {

	'use strict';

	var notice = {

		dismiss: function( e ) {

			$.post( php_notifier.ajax_url, { 'action': 'php_notifier_dismiss_notice' }, function() {} );

		}

	};

	$( document ).on( 'click', '.php-notifier-notice .notice-dismiss', notice.dismiss );

} )( jQuery );
