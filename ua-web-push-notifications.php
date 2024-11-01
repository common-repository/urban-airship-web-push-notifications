<?php // phpcs:disable Squiz.Commenting.FileComment.Missing
// phpcs:disable Squiz.Commenting.FileComment.MissingPackageTag

/**
 * Plugin Name: Airship Web Notifications
 * Description: A plugin to integrate Airship's Web Notifications product with WordPress sites to send notifications to site visitors.
 * Plugin URI:  https://www.urbanairship.com/products/web-push-notifications
 * Version:     1.3.4
 * Author:      Airship
 * Author URI:  https://www.urbanairship.com/
 * License:     GPLv2 or later
 * Text Domain: ua-web-notification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'UA_WEB_NOTIFICATION_URL' ) ) {
	define( 'UA_WEB_NOTIFICATION_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'UA_WEB_NOTIFICATION_PATH' ) ) {
	define( 'UA_WEB_NOTIFICATION_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'UA_WEB_NOTIFICATION_BASENAME' ) ) {
	define( 'UA_WEB_NOTIFICATION_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'UA_WEB_NOTIFICATION_VERSION' ) ) {
	define( 'UA_WEB_NOTIFICATION_VERSION', '1.3.4' );
}

require_once UA_WEB_NOTIFICATION_PATH . 'includes/class-ua-web-notification.php';

UA_WEB_NOTIFICATION::instance();
