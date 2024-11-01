<?php // phpcs:disable Squiz.Commenting.FileComment.Missing

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * Class UA_WEB_NOTIFICATION
 */
class UA_WEB_NOTIFICATION {

	/**
	 * Instance of UA_WEB_NOTIFICATION_API class
	 *
	 * @var UA_WEB_NOTIFICATION_API
	 */
	public $api;

	/**
	 * Instance of UA_WEB_NOTIFICATION_SETTINGS class
	 *
	 * @var UA_WEB_NOTIFICATION_SETTINGS
	 */
	public $settings;

	/**
	 * Instance  of UA_WEB_NOTIFICATION_ADMIN class
	 *
	 * @var UA_WEB_NOTIFICATION_ADMIN
	 */
	public $admin;

	/**
	 * Instance of UA_WEB_NOTIFICATION_INTEGRATION class
	 *
	 * @var UA_WEB_NOTIFICATION_INTEGRATION
	 */
	public $integration;

	/**
	 * Instance of UA_WEB_NOTIFICATION_POST_TYPE class
	 *
	 * @var UA_WEB_NOTIFICATION_POST_TYPE
	 */
	public $post_type;

	/**
	 * UA_WEB_NOTIFICATION constructor.
	 */
	public function __construct() {
		$this->include_files();
		$this->init();
	}

	/**
	 * Get plugin instance.
	 *
	 * @return UA_WEB_NOTIFICATION
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Include files
	 */
	public function include_files() {
		require_once UA_WEB_NOTIFICATION_PATH . 'includes/class-ua-web-notification-api.php';
		require_once UA_WEB_NOTIFICATION_PATH . 'includes/class-ua-web-notification-settings.php';
		require_once UA_WEB_NOTIFICATION_PATH . 'includes/class-ua-web-notification-admin.php';
		require_once UA_WEB_NOTIFICATION_PATH . 'includes/class-ua-web-notification-integration.php';
		require_once UA_WEB_NOTIFICATION_PATH . 'includes/class-ua-web-notification-post-type.php';
	}

	/**
	 * Init plugin functionality
	 */
	public function init() {
		$this->api         = new UA_WEB_NOTIFICATION_API();
		$this->settings    = new UA_WEB_NOTIFICATION_SETTINGS();
		$this->admin       = new UA_WEB_NOTIFICATION_ADMIN();
		$this->integration = new UA_WEB_NOTIFICATION_INTEGRATION();
		$this->post_type   = new UA_WEB_NOTIFICATION_POST_TYPE();
	}
}
