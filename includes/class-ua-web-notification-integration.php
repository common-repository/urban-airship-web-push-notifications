<?php // phpcs:disable Squiz.Commenting.FileComment.Missing
// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Airship integration
 *
 * Class UA_WEB_NOTIFICATION_INTEGRATION
 */
class UA_WEB_NOTIFICATION_INTEGRATION {

	/**
	 * UA_WEB_NOTIFICATION_INTEGRATION constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 1 );
		add_filter( 'redirect_canonical', array( $this, 'redirect_canonical' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'wp_footer', array( $this, 'include_ua_script' ) );
	}

	/**
	 * Sets up rewrite rules.
	 */
	public function add_rewrite_rules() {
		global $wp;

		if ( ! $this->can_integrate() ) {
			return;
		}

		$wp->add_query_var( 'ua_wn_worker' );
		add_rewrite_rule( '^push-worker\.js$', 'index.php?ua_wn_worker=1', 'top' );

		if ( $this->add_secure_bridge() ) {
			$wp->add_query_var( 'ua_wn_secure_bridge' );
			add_rewrite_rule( '^secure-bridge\.html$', 'index.php?ua_wn_secure_bridge=1', 'top' );
		}
	}

	/**
	 * Stop trailing slashes on push-worker.js and secure-bridge.html URLs.
	 *
	 * @param  string $redirect The redirect URL currently determined.
	 * @return bool|string $redirect
	 */
	public function redirect_canonical( $redirect ) {

		if ( get_query_var( 'ua_wn_worker' ) || get_query_var( 'ua_wn_secure_bridge' ) ) {
			return false;
		}

		return $redirect;
	}

	/**
	 * Render worker js and secure bridge.
	 *
	 * @since 1.3.2 Added aditional parameters filter.
	 * @since 1.3.0 Added website push ID.
	 * @since 1.0.0
	 */
	public function maybe_render() {

		if ( ! $this->can_integrate() ) {
			return;
		}

		// push-worker.js
		if ( get_query_var( 'ua_wn_worker' ) ) {
			$ua_web_notify     = UA_WEB_NOTIFICATION::instance();
			$default_icon      = $ua_web_notify->settings->get_default_image_url();
			$default_title     = $ua_web_notify->settings->get_default_title();
			$default_url       = $ua_web_notify->settings->get_default_action_url();
			$app_key           = $ua_web_notify->settings->get_app_key();
			$token             = $ua_web_notify->settings->get_token();
			$vapid_pub_key     = $ua_web_notify->settings->get_vapid_public_key();
			$additional_params = $this->add_additional_params();
			$secure_iframe     = $this->add_secure_iframe_url();

			header( 'Content-Type: application/javascript' );
			?>
importScripts('https://aswpsdkus.com/notify/v1/ua-sdk.min.js');
uaSetup.worker(self, {
	defaultIcon: '<?php echo esc_js( $default_icon ); ?>',
	defaultTitle: '<?php echo esc_js( $default_title ); ?>',
	defaultActionURL: '<?php echo esc_js( $default_url ); ?>',
	appKey: '<?php echo esc_js( $app_key ); ?>',
	token: '<?php echo esc_js( $token ); ?>',
			<?php echo $additional_params; // WPCS: XSS ok. ?>
			<?php echo $secure_iframe; // WPCS: XSS ok. ?>
	vapidPublicKey: '<?php echo esc_js( $vapid_pub_key ); ?>'
});
			<?php
			exit;

			// secure-bridge.html
		} elseif ( $this->add_secure_bridge() && get_query_var( 'ua_wn_secure_bridge' ) ) {
			$ua_web_notify     = UA_WEB_NOTIFICATION::instance();
			$app_key           = $ua_web_notify->settings->get_app_key();
			$additional_params = $this->add_additional_params( 'secure_bridge' );

			header( 'Content-type: text/html' );
			?>
<!DOCTYPE html>
<html><head></head><body>
	<script type="text/javascript" src="https://aswpsdkus.com/notify/v1/ua-sdk.min.js"></script>
	<script type="text/javascript">uaSetup.secureBridge({
			<?php echo $additional_params; // WPCS: XSS ok. ?>
		appKey: '<?php echo esc_js( $app_key ); ?>'
	})</script>
</body></html>
			<?php
			exit;
		}
	}

	/**
	 * Enqueue js script
	 */
	public function enqueue_script() {
		if ( ! $this->can_integrate() ) {
			return;
		}

		$ua_web_notify   = UA_WEB_NOTIFICATION::instance();
		$prompt_settings = array();
		if ( is_a( $ua_web_notify->settings, 'UA_WEB_NOTIFICATION_SETTINGS' ) ) {
			$prompt_settings = $ua_web_notify->settings->get_prompt_settings();
		}

		$settings_data = array(
			'prompt' => array(
				'enabled'            => empty( $prompt_settings['prompt'] ) ? false : (bool) $prompt_settings['prompt'],
				'prompt_views'       => empty( $prompt_settings['prompt_views'] ) ? 1 : intval( $prompt_settings['prompt_views'] ),
				'prompt_again_views' => ! isset( $prompt_settings['prompt_again_views'] ) ? 1 : intval( $prompt_settings['prompt_again_views'] ),
			),
		);

		wp_enqueue_script(
			'ua-wn',
			UA_WEB_NOTIFICATION_URL . 'assets/js/notification.js',
			array( 'jquery' ),
			UA_WEB_NOTIFICATION_VERSION,
			false
		);

		wp_localize_script(
			'ua-wn',
			'uaWnSettings',
			$settings_data
		);
	}

	/**
	 * Include UA script on all the pages in footer.
	 *
	 * @since 1.3.2 Added aditional parameters filter.
	 * @since 1.0.0
	 */
	public function include_ua_script() {

		if ( ! $this->can_integrate() ) {
			return;
		}

		$ua_web_notify     = UA_WEB_NOTIFICATION::instance();
		$app_key           = $ua_web_notify->settings->get_app_key();
		$token             = $ua_web_notify->settings->get_token();
		$vapid_pub_key     = $ua_web_notify->settings->get_vapid_public_key();
		$website_push_id   = $ua_web_notify->settings->get_website_push_id();
		$additional_params = $this->add_additional_params();
		$secure_iframe     = $this->add_secure_iframe_url();

		?>
<script type="text/javascript">
!function(n,t,c,e,u){function r(n){try{f=n(u)}catch(n){return h=n,void i(p,n)}i(s,f)}function i(n,t){for(var c=0;c<n.length;c++)d(n[c],t);
}function o(n,t){return n&&(f?d(n,f):s.push(n)),t&&(h?d(t,h):p.push(t)),l}function a(n){return o(!1,n)}function d(t,c){
n.setTimeout(function(){t(c)},0)}var f,h,s=[],p=[],l={then:o,catch:a,_setup:r};n[e]=l;var v=t.createElement("script");
v.src=c,v.async=!0,v.id="_uasdk",v.rel=e,t.head.appendChild(v)}(window,document,'https://aswpsdkus.com/notify/v1/ua-sdk.min.js',
	'UA', {
		appKey: '<?php echo esc_js( $app_key ); ?>',
		token: '<?php echo esc_js( $token ); ?>',
		<?php if ( ! empty( $website_push_id ) ) { // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect ?>
		websitePushId: '<?php echo esc_js( $website_push_id ); ?>',
		<?php } ?>
		<?php echo $additional_params; // WPCS: XSS ok. ?>
		<?php echo $secure_iframe; // WPCS: XSS ok. ?>
		vapidPublicKey: '<?php echo esc_js( $vapid_pub_key ); ?>'
	});
</script>
		<?php
	}

	/**
	 * Determine if it can integrate or not
	 *
	 * @return bool
	 */
	public function can_integrate() {
		$ua_web_notify = UA_WEB_NOTIFICATION::instance();
		return ( is_a( $ua_web_notify->settings, 'UA_WEB_NOTIFICATION_SETTINGS' ) && $ua_web_notify->settings->is_ua_ac_configured() );
	}

	/**
	 * Determine if it should integrate secure bridge or not
	 *
	 * @return bool
	 */
	public function add_secure_bridge() {
		$add_secure_bridge = false;
		$ua_web_notify     = UA_WEB_NOTIFICATION::instance();
		if ( isset( $ua_web_notify->settings ) && is_a( $ua_web_notify->settings, 'UA_WEB_NOTIFICATION_SETTINGS' ) ) {
			$add_secure_bridge = ( $ua_web_notify->settings->use_secure_bridge() || apply_filters( 'ua_wn_integrate_html_bridge', false ) );
		}

		return $add_secure_bridge;
	}

	/**
	 * Outputs the `secureIframeUrl` worker js parameter.
	 *
	 * @since  1.3.2
	 * @return string
	 */
	public function add_secure_iframe_url() {

		if ( ! $this->add_secure_bridge() ) {
			return '';
		}

		$url = home_url( 'secure-bridge.html' );
		return "secureIframeUrl: '{$url}',";
	}

	/**
	 * Output additional worker js and secure bridge parameters.
	 *
	 * @since  1.3.2
	 * @param  string $type The snippet type where the parameters are going to be added.
	 *                      The available types are:
	 *                      'worker'        => push-worker.js
	 *                      'secure_bridge' => secure-bridge.html
	 *                      Defaults to 'worker'.
	 * @return string
	 */
	public function add_additional_params( $type = 'worker' ) {

		$additional_params = apply_filters( 'ua_wn_snippet_additional_params', [], $type );
		if ( empty( $additional_params ) ) {
			return '';
		}

		$additional_params_js = '';
		foreach ( $additional_params as $key => $value ) {
			if ( empty( $key ) || empty( $value ) ) {
				continue;
			}

			$additional_params_js .= sprintf(
				"%s: '%s',",
				esc_js( $key ),
				esc_js( $value )
			);
		}

		return $additional_params_js;
	}
}
