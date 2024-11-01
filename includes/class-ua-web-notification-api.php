<?php // phpcs:disable Squiz.Commenting.FileComment.Missing
// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Airship API
 *
 * Class UA_WEB_NOTIFICATION_API
 */
class UA_WEB_NOTIFICATION_API {

	/**
	 * UA API URL
	 *
	 * @var string
	 */
	protected $_api_url = 'https://go.urbanairship.com';

	/**
	 * Variable to save last performed API request
	 *
	 * @var null
	 */
	protected $_last_request = null;

	/**
	 * Check if API connection is authenticated
	 *
	 * @return bool
	 */
	public function is_authenticated() {
		$request = $this->_remote_post();

		if ( false === $request ) {
			return false;
		}

		$response = wp_remote_retrieve_response_code( $request );

		return ( 401 !== $response );
	}

	/**
	 * Check if connection to API is possible
	 *
	 * @return bool
	 */
	public function can_connect_api() {

		if ( null === $this->_last_request ) {
			$request = $this->_remote_post();
		} else {
			$request = $this->_last_request;
		}

		if ( false === $request ) {
			return false;
		}

		$response = wp_remote_retrieve_response_code( $request );

		return ( ! ( 404 === $response || 410 === $response ) );
	}

	/**
	 * Push web notification.
	 *
	 * @param  array          $data    Notification data.
	 * @param  string|integer $post_id ID of the post which is associated with the package.
	 * @return array|bool
	 */
	public function push_web_notification( $data, $post_id ) {

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		$ua_wn = UA_WEB_NOTIFICATION::instance();
		if ( ! isset( $ua_wn->settings ) || ! is_a( $ua_wn->settings, 'UA_WEB_NOTIFICATION_SETTINGS' ) ) {
			return false;
		}

		$push_object = array(
			'audience'     => 'all',
			'device_types' => array(
				'web',
			),
			'notification' => array(
				'alert'   => ! empty( $data['text'] ) ? $data['text'] : '',
				'actions' => array(
					'open' => array(
						'type'    => 'url',
						'content' => ! empty( $data['url'] ) ? $data['url'] : $ua_wn->settings->get_default_action_url(),
					),
				),
				'web'     => array(
					'title'               => ! empty( $data['title'] ) ? $data['title'] : $ua_wn->settings->get_default_title(),
					'require_interaction' => $data['persistent'],
					'extra'               => array(
						'url'      => ! empty( $data['url'] ) ? $data['url'] : '',
						'story_id' => ! empty( $data['id'] ) ? $data['id'] : '',
					),
					'icon'                => array(
						'url' => ! empty( $data['icon_url'] ) ? $data['icon_url'] : $ua_wn->settings->get_default_image_url(),
					),
				),
			),
		);

		$request = $this->_remote_post( $push_object, $post_id );

		$request_response_code = wp_remote_retrieve_response_code( $request );

		$success_response_codes = array(
			201,
			202,
		);

		if ( ! in_array( $request_response_code, $success_response_codes, true ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $request ), true );
	}

	/**
	 * Perform a remote request
	 *
	 * @param  array          $data    Notification data.
	 * @param  string|integer $post_id ID of the post which is associated with the package.
	 * @return array|bool|null|WP_Error
	 */
	public function _remote_post( $data = array(), $post_id = false ) {

		$ua_wn = UA_WEB_NOTIFICATION::instance();
		if ( ! is_a( $ua_wn->settings, 'UA_WEB_NOTIFICATION_SETTINGS' ) ) {
			return false;
		}

		/**
		 * Filter API post request URL
		 *
		 * @param string $api_url
		 */
		$api_url = apply_filters( 'ua_wn_api_url', $this->_api_url . '/api/push' );

		/**
		 * Filter API post request args
		 *
		 * @param array $post_args
		 */
		$post_args = apply_filters(
			'ua_wn_api_args',
			array(
				'timeout'   => 10,
				'blocking'  => true,
				'body'      => $data,
				'sslverify' => false,
				'headers'   => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/vnd.urbanairship+json; version=3;',
					// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'Authorization' => 'Basic ' . base64_encode( $ua_wn->settings->get_app_key() . ':' . $ua_wn->settings->get_app_master_secret() ),
					// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				),
			)
		);

		/**
		 * If post ID is set than save the sent notification data body in post meta.
		 */
		if ( ! empty( $post_id ) ) {
			update_post_meta( $post_id, 'ua_wn_notification_sent_data', $post_args['body'] );
		}

		$post_args['body'] = wp_json_encode( $post_args['body'] );

		$this->_last_request = wp_remote_post( $api_url, $post_args );

		if ( is_wp_error( $this->_last_request ) || empty( $this->_last_request ) ) {
			return false;
		}

		return $this->_last_request;
	}
}
