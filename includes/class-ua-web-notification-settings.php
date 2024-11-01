<?php // phpcs:disable Squiz.Commenting.FileComment.Missing
// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings
 *
 * Class UA_WEB_NOTIFICATION_SETTINGS
 */
class UA_WEB_NOTIFICATION_SETTINGS {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	public $settings_page_slug = 'ua-web-notification';

	/**
	 * Option key to store the config.
	 *
	 * @var string
	 */
	public $option_key = 'ua-wn-config';

	/**
	 * Option key to store parsed configs from zip file.
	 *
	 * @var string
	 */
	public $ua_config_option_key = 'ua-wn-parsed-config';

	/**
	 * Option key to flag the safari support admin notice.
	 *
	 * @since 1.3.0
	 * @var   string
	 */
	public $safari_support_notice_option_key = 'ua-wn-safari-support-notice';

	/**
	 * UA_WEB_NOTIFICATION_SETTINGS constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . UA_WEB_NOTIFICATION_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'load-settings_page_' . $this->settings_page_slug, array( $this, 'settings_fields' ) );
		add_action( 'load-settings_page_' . $this->settings_page_slug, array( $this, 'settings_help' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'safari_support_admin_notice' ) );
		add_action( 'admin_notices', array( $this, 'constant_support_admin_notice' ) );
		add_action( 'wp_ajax_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.3.0 Added dismiss notice.
	 * @since 1.0.0
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {

		if ( 'settings_page_ua-web-notification' === $hook ) {

			wp_enqueue_style(
				'ua-wn-settings',
				UA_WEB_NOTIFICATION_URL . 'assets/css/settings.css',
				array(),
				UA_WEB_NOTIFICATION_VERSION
			);

			wp_enqueue_script(
				'ua-wn-settings',
				UA_WEB_NOTIFICATION_URL . 'assets/js/settings.js',
				array( 'jquery' ),
				UA_WEB_NOTIFICATION_VERSION,
				true
			);
		}

		wp_enqueue_script(
			'ua-wn-dismiss-notice',
			UA_WEB_NOTIFICATION_URL . 'assets/js/dismiss-notice.js',
			array( 'jquery' ),
			UA_WEB_NOTIFICATION_VERSION,
			true
		);

		wp_localize_script(
			'ua-wn-dismiss-notice',
			'uaWnDismissibleNotice',
			array(
				'nonce' => wp_create_nonce( 'dismissible-notice' ),
			)
		);
	}

	/**
	 * Register settings page.
	 */
	public function action_admin_menu() {
		add_options_page(
			esc_html__( 'Airship Web Notifications Settings', 'ua-web-notification' ),
			esc_html__( 'Web Notifications', 'ua-web-notification' ),
			'manage_options',
			$this->settings_page_slug,
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Add settings page link along with plugin action links.
	 *
	 * @param  array $links An array of plugin action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {

		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=' . $this->settings_page_slug ) ),
			esc_html__( 'Settings', 'ua-web-notification' )
		);

		return $links;
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			$this->settings_page_slug,
			'ua-wn-config',
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Register settings fields.
	 */
	public function settings_fields() {
		foreach ( $this->get_settings_sections() as $section_id => $section_attributes ) {
			add_settings_section(
				$section_id,
				$section_attributes['title'],
				$section_attributes['callback'],
				$this->settings_page_slug
			);
		}

		// Register the settings sections.
		foreach ( $this->get_settings_fields() as $field_id => $field_attributes ) {

			// Skip the bundle setting if the settings have been defined in the bundle constant.
			if ( defined( 'UA_WEB_NOTIFICATION_BUNDLE' ) && 'sdk-bundle' === $field_id ) {
				continue;
			}

			$args = isset( $field_attributes['args'] ) ? $field_attributes['args'] : null;
			add_settings_field(
				$field_id,
				$field_attributes['title'],
				$field_attributes['callback'],
				$this->settings_page_slug,
				$field_attributes['section'],
				$args
			);
		}
	}

	/**
	 * Get settings sections
	 *
	 * @return array
	 */
	public function get_settings_sections() {
		return array(
			'account_settings'       => array(
				'title'    => esc_html__( 'Account Settings', 'ua-web-notification' ),
				'callback' => array( $this, '_render_account_settings_section' ),
			),
			'notification_display'   => array(
				'title'    => esc_html__( 'Notification Settings', 'ua-web-notification' ),
				'callback' => array( $this, '_render_display_settings_section' ),
			),
			'opt_in_prompt_settings' => array(
				'title'    => esc_html__( 'Opt-In Prompt Settings', 'ua-web-notification' ),
				'callback' => array( $this, '_render_notification_settings_section' ),
			),
		);
	}

	/**
	 * Get settings fields
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'sdk-bundle'           => array(
				'title'    => esc_html__( 'SDK Bundle Zip File', 'ua-web-notification' ),
				'callback' => array( $this, '_render_sdk_bundle_field' ),
				'section'  => 'account_settings',
			),
			'master-secret'        => array(
				'title'    => esc_html__( 'Master Secret', 'ua-web-notification' ),
				'callback' => array( $this, '_render_master_secret_field' ),
				'section'  => 'account_settings',
			),
			'service-connectivity' => array(
				'title'    => esc_html__( 'Service Connectivity', 'ua-web-notification' ),
				'callback' => array( $this, '_render_service_connectivity_field' ),
				'section'  => 'account_settings',
			),
			'default-title'        => array(
				'title'    => esc_html__( 'Default Title', 'ua-web-notification' ),
				'callback' => array( $this, '_render_default_title_field' ),
				'section'  => 'notification_display',
			),
			'default-action-url'   => array(
				'title'    => esc_html__( 'Default Action URL', 'ua-web-notification' ),
				'callback' => array( $this, '_render_default_action_url_field' ),
				'section'  => 'notification_display',
			),
			'default-image-url'    => array(
				'title'    => esc_html__( 'Default Icon', 'ua-web-notification' ),
				'callback' => array( $this, '_render_default_image_url_field' ),
				'section'  => 'notification_display',
			),
			'safari-support'       => array(
				'title'    => esc_html__( 'Safari Support', 'ua-web-notification' ),
				'callback' => array( $this, '_render_safari_support' ),
				'section'  => 'notification_display',
				'args'     => array(
					'class' => 'safari-support',
				),
			),
			'featured-image'       => array(
				'title'    => esc_html__( 'Featured Image', 'ua-web-notification' ),
				'callback' => array( $this, '_render_featured_image_field' ),
				'section'  => 'notification_display',
			),
			'css-prompt'           => array(
				'title'    => esc_html__( 'CSS Class for Custom Opt-In', 'ua-web-notification' ),
				'callback' => array( $this, '_render_css_prompt_field' ),
				'section'  => 'opt_in_prompt_settings',
			),
			'offer-notification'   => array(
				'title'    => esc_html__( 'Display Default Browser Prompt', 'ua-web-notification' ),
				'callback' => array( $this, '_render_offer_notification_field' ),
				'section'  => 'opt_in_prompt_settings',
			),
		);
	}

	/**
	 * Render settings page
	 */
	public function settings_page() {
		?>
		<div class="wrap ua-web-notification-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="description"><?php esc_html_e( 'Need help? Click the help link at the top of the page for tips and documentation.', 'ua-web-notification' ); ?></p>
			<form enctype="multipart/form-data" action="options.php" method="post" autocomplete="off">

				<?php settings_fields( 'ua-web-notification' ); ?>

				<?php do_settings_sections( $this->settings_page_slug ); ?>

				<?php submit_button( esc_html__( 'Save Updates', 'ua-web-notification' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render account settings section.
	 *
	 * @return void
	 */
	public function _render_account_settings_section() {
		?>
		<p class="section-description">
			<?php esc_html_e( 'Settings are configured in your Airship dashboard.', 'ua-web-notification' ); ?>
			<a href="https://go.urbanairship.com/accounts/login/" target="_blank">
				<?php esc_html_e( 'Visit your Dashboard to make updates', 'ua-web-notification' ); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span>
			</a>
		</p>
		<hr>
		<?php
	}

	/**
	 * Render display settings section.
	 *
	 * @return void
	 */
	public function _render_display_settings_section() {
		?>
		<p class="section-description">
			<?php esc_html_e( 'Notification settings are configured in your Airship dashboard.', 'ua-web-notification' ); ?>
			<a href="https://go.urbanairship.com/accounts/login/" target="_blank">
				<?php esc_html_e( 'Visit your Dashboard to make updates', 'ua-web-notification' ); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span>
			</a>
		</p>
		<hr>
		<?php
	}

	/**
	 * Render notification settings section.
	 *
	 * @return void
	 */
	public function _render_notification_settings_section() {
		?>
		<p class="description"><?php esc_html_e( 'These optional settings can be used to customize your opt-in prompt.', 'ua-web-notification' ); ?></p>
		<hr>
		<?php
	}

	/**
	 * Render SDK bundle field.
	 *
	 * @return void
	 */
	public function _render_sdk_bundle_field() {
		// App key is parsed from zip so use that to determine if zip is already uploaded or not.
		$app_key        = $this->get_app_key();
		$uploaded_class = ! empty( $app_key ) ? 'ua-wn-uploaded' : '';
		?>
		<div class="ua-wn-sdk-upload-wrap <?php echo esc_attr( $uploaded_class ); ?>">
			<div class="ua-wn-sdk-upload-new">
				<span class="ua-wn-sdk-file-name">Urban-Airship-Web-Notify.zip</span>
				<a class="button-primary" href="#" id="ua-wn-sdk-upload-new"><?php esc_html_e( 'Upload New', 'ua-web-notification' ); ?></a>
			</div>
			<div class="ua-wn-sdk-upload">
				<input type="file" name="ua-wn-sdk-bundle">
				<?php submit_button( esc_html__( 'Upload', 'ua-web-notification' ), 'primary', 'submit', false ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render master secret field.
	 *
	 * @return void
	 */
	public function _render_master_secret_field() {
		?>
		<input class="regular-text" placeholder="<?php esc_attr_e( 'Enter App Master Secret', 'ua-web-notification' ); ?>" type="text" name="ua-wn-config[master-secret]" value="<?php echo esc_attr( $this->get_app_master_secret() ); ?>">
		<?php
	}

	/**
	 * Render service connectivity field.
	 *
	 * @return void
	 */
	public function _render_service_connectivity_field() {
		$ua_wn         = UA_WEB_NOTIFICATION::instance();
		$authenticated = $ua_wn->api->is_authenticated();

		if ( ! $authenticated ) {
			$can_connect = $ua_wn->api->can_connect_api();
		} else {
			$can_connect = $authenticated;
		}
		?>
		<?php if ( $can_connect ) : ?>
			<p>
				<span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'The Airship API was contacted successfully.', 'ua-web-notification' ); ?>
			</p>
		<?php else : ?>
			<p>
				<span class="dashicons dashicons-flag" aria-hidden="true"></span><?php esc_html_e( 'The Airship API contact was unsuccessful.', 'ua-web-notification' ); ?>
			</p>
		<?php endif; ?>
		<?php if ( $authenticated ) : ?>
			<p>
				<span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Your Airship App Key and Master Secret are valid.', 'ua-web-notification' ); ?>
			</p>
		<?php else : ?>
			<p>
				<span class="dashicons dashicons-flag" aria-hidden="true"></span><?php esc_html_e( 'Your Airship App Key and Master Secret are not valid.', 'ua-web-notification' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render default title field.
	 *
	 * @return void
	 */
	public function _render_default_title_field() {
		$title = $this->get_default_title();
		$class = '';

		if ( empty( $title ) ) {
			$title = esc_html__( 'None. Upload Zip File to Generate', 'ua-web-notification' );
			$class = 'ua-wn-setting-none';
		}
		?>
		<p class="<?php echo esc_attr( $class ); ?>"><strong><?php echo esc_html( $title ); ?></strong></p>
		<?php
	}

	/**
	 * Render default action URL field.
	 *
	 * @return void
	 */
	public function _render_default_action_url_field() {
		$action_url = $this->get_default_action_url();
		$class      = '';

		if ( empty( $action_url ) ) {
			$action_url = esc_html__( 'None. Upload Zip File to Generate', 'ua-web-notification' );
			$class      = 'ua-wn-setting-none';
		}
		?>
		<p class="<?php echo esc_attr( $class ); ?>"><strong><?php echo esc_html( $action_url ); ?></strong></p>
		<?php
	}

	/**
	 * Render default image URL field.
	 *
	 * @return void
	 */
	public function _render_default_image_url_field() {
		$image_url  = $this->get_default_image_url();
		$class      = '';
		$error_text = '';

		if ( empty( $image_url ) ) {
			$error_text = esc_html__( 'None. Upload Zip File to Generate', 'ua-web-notification' );
			$class      = 'ua-wn-setting-none';
		}
		?>
		<p class="<?php echo esc_attr( $class ); ?>">
			<?php if ( empty( $image_url ) ) : ?>
				<strong><?php echo esc_html( $error_text ); ?></strong>
			<?php else : ?>
				<img
					class="ua-wn-setting-default-img"
					src="<?php echo esc_url( $image_url ); ?>"
					alt="<?php esc_attr_e( 'You have a default image set. Please visit your Airship Web Notifications Dashboard to change your default image.', 'ua-web-notification' ); ?>">
			<?php endif; ?>

		</p>
		<?php
	}

	/**
	 * Render safari support.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function _render_safari_support() {
		$title   = $this->get_default_title();
		$push_id = $this->get_website_push_id();

		/**
		 * Checks for title presence to distinguish what notice to show
		 * between new installs and versions prior to 1.3.0.
		 */
		if ( false === $push_id && empty( $title ) ) {
			?>
			<p class="ua-wn-setting-none">
				<strong><?php esc_html_e( 'None. Upload Zip File to Generate', 'ua-web-notification' ); ?></strong>
			</p>
			<?php
		} elseif ( empty( $push_id ) ) {
			?>
			<a href="https://docs.urbanairship.com/tutorials/getting-started/channels/web-notify/#safari" target="_blank">
				<?php esc_html_e( 'How to add Safari support', 'ua-web-notification' ); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span>
			</a>
			<?php
		} else {
			?>
			<p>
				<strong><?php echo esc_html( $push_id ); ?></strong>
			</p>
			<?php
		}
	}

	/**
	 * Render featured image field.
	 *
	 * @return void
	 */
	public function _render_featured_image_field() {
		?>
		<label>
			<input type="checkbox" name="ua-wn-config[featured-image]" value="1" <?php checked( $this->use_featured_image() ); ?>>
			<span><?php esc_html_e( 'Use featured image in place of default notification icon', 'ua-web-notification' ); ?></span>
		</label>
		<p class="description">
			<?php esc_html_e( 'Selecting this checkbox will update the Web Notification to show the article’s featured image instead of the default image. If an article does not have a featured image, then the default icon will be used.', 'ua-web-notification' ); ?>
		</p>
		<p class="description">
			<?php
			printf(
				/* translators: 1: open tag, 2: close tag */
				esc_html__( '%1$sNote:%2$s This is not supported by Safari.', 'ua-web-notification' ),
				'<strong>',
				'</strong>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render CSS prompt field.
	 *
	 * @return void
	 */
	public function _render_css_prompt_field() {
		?>
		<div class="ua-wn-prompt-event">
			<p class="description"><?php esc_html_e( 'Use the following CSS class to add a custom opt-in prompt or button to your site for supporter browsers.', 'ua-web-notification' ); ?></p>
			<div id="ua_wn_custom_prompt_help" class="postbox">
				<ul>
					<li>
						<?php // translators: class name ?>
						<p><?php printf( esc_html__( 'Assign the class %s to any element to prompt the visitor on-click.', 'ua-web-notification' ), '<code>"ua-opt-in"</code>' ); ?></p>
						<?php // translators: link example ?>
						<p><?php printf( esc_html__( 'Example: %s', 'ua-web-notification' ), '<code>&lt;a href="#" class="ua-opt-in"&gt;' . esc_html__( 'Receive Desktop Notifications', 'ua-web-notification' ) . '&lt;/a&gt;</code>' ); ?></p>
					</li>

					<?php if ( current_theme_supports( 'menus' ) ) : ?>
						<?php // translators: admin link ?>
						<li><p><?php printf( esc_html__( '%s can be configured to prompt on-click by enabling CSS Classes under Screen Options.', 'ua-web-notification' ), '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Menu items', 'ua-web-notification' ) . '</a>' ); ?></p></li>
					<?php endif; ?>

					<li><p><?php esc_html_e( 'Elements with this special class are hidden from visitors who already accepted/denied notifications or are using unsupported browsers.', 'ua-web-notification' ); ?></p></li>
				</ul>
			</div>
			<p class="description"><?php esc_html_e( 'Note: If you use the CSS class on a custom modal, we do not recommend using the Display Default Browser Prompt option below because they are similar in function and using both could result in over-prompting users.', 'ua-web-notification' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render offer notification field.
	 *
	 * @return void
	 */
	public function _render_offer_notification_field() {
		$prompt = $this->get_prompt_settings();
		?>
		<div>
			<label title="<?php esc_attr_e( 'After a specified number of page views.', 'ua-web-notification' ); ?>">
				<input type="checkbox" name="ua-wn-config[prompt]" value="visits" <?php checked( $prompt['prompt'], 'visits' ); ?>>
				<?php esc_html_e( 'After', 'ua-web-notification' ); ?>
			</label>
			<label for="ua-wn-config[prompt_views]">
				<input name="ua-wn-config[prompt_views]" type="number" min="1" step="1" id="ua-wn-config[prompt_views]" value="<?php echo (int) $prompt['prompt_views']; ?>" class="small-text">
				<?php esc_html_e( 'page view(s)', 'ua-web-notification' ); ?>
			</label>
			<label for="ua-wn-config[prompt_again_views]">
				<?php esc_html_e( 'and show prompt again after every', 'ua-web-notification' ); ?>
				<input name="ua-wn-config[prompt_again_views]" type="number" min="0" step="1" id="ua-wn-config[prompt_again_views]" value="<?php echo (int) $prompt['prompt_again_views']; ?>" class="small-text">
				<?php esc_html_e( 'page view(s) if user dismisses prompt', 'ua-web-notification' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'This setting controls the default browser prompt for notification permission. It does not apply to any custom opt-in prompts created with the above CSS class.', 'ua-web-notification' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param  array $input Array of settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {

		$sanitized_input = array();

		$sanitized_input['master-secret']      = empty( $input['master-secret'] ) ? '' : sanitize_text_field( $input['master-secret'] );
		$sanitized_input['prompt']             = empty( $input['prompt'] ) ? '' : sanitize_text_field( $input['prompt'] );
		$sanitized_input['prompt_views']       = empty( $input['prompt_views'] ) ? 1 : (int) $input['prompt_views'];
		$sanitized_input['prompt_again_views'] = isset( $input['prompt_again_views'] ) ? (int) $input['prompt_again_views'] : 1;
		$sanitized_input['featured-image']     = empty( $input['featured-image'] ) ? '' : sanitize_text_field( $input['featured-image'] );

		// Save config from zip file
		if (
			isset( $_FILES['ua-wn-sdk-bundle'] ) &&
			! empty( $_FILES['ua-wn-sdk-bundle']['tmp_name'] ) &&
			! isset( $this->_sdk_bundle_saved ) &&
			! defined( 'UA_WEB_NOTIFICATION_BUNDLE' )
		) {
			if ( 'application/zip' === $_FILES['ua-wn-sdk-bundle']['type'] ) {
				$zip_file = wp_handle_upload(
					$_FILES['ua-wn-sdk-bundle'],
					array(
						'test_form' => false,
					)
				);

				if ( is_array( $zip_file ) && ! empty( $zip_file['file'] ) ) {
					$config_data = $this->parse_zip( $zip_file['file'] );
					$zip_data    = array();

					if ( is_array( $config_data ) && ! empty( $config_data ) ) {
						if ( isset( $config_data['default-icon'] ) && ! empty( $config_data['default-icon'] ) ) {
							$zip_data['default-icon'] = esc_url_raw( $config_data['default-icon'] );
						}

						if ( isset( $config_data['default-title'] ) && ! empty( $config_data['default-title'] ) ) {
							$zip_data['default-title'] = sanitize_text_field( $config_data['default-title'] );
						}

						if ( isset( $config_data['default-action-url'] ) && ! empty( $config_data['default-action-url'] ) ) {
							$zip_data['default-action-url'] = esc_url_raw( $config_data['default-action-url'] );
						}

						if ( isset( $config_data['app-key'] ) && ! empty( $config_data['app-key'] ) ) {
							$zip_data['app-key'] = sanitize_text_field( $config_data['app-key'] );
						}

						if ( isset( $config_data['token'] ) && ! empty( $config_data['token'] ) ) {
							$zip_data['token'] = sanitize_text_field( $config_data['token'] );
						}

						if ( isset( $config_data['vapid-pub-key'] ) && ! empty( $config_data['vapid-pub-key'] ) ) {
							$zip_data['vapid-pub-key'] = sanitize_text_field( $config_data['vapid-pub-key'] );
						}

						if ( isset( $config_data['website-push-id'] ) ) {
							$zip_data['website-push-id'] = sanitize_text_field( $config_data['website-push-id'] );
						}

						if ( isset( $config_data['secure-bridge'] ) ) {
							$zip_data['secure-bridge'] = $config_data['secure-bridge'];
						}
					}

					if ( ! empty( $zip_data ) ) {
						update_option( $this->ua_config_option_key, $zip_data );

						/*
						 * Flush rewrite rules so that admin don't need to save permalink settings in order to rewrite
						 * rules to work.
						 *
						 * Rewrite rule for worker js will only be added if ZIP is configured. Rewrite rules are added
						 * on "init" hook which is already executed by now and because ZIP wasn't configured at that
						 * time and it didn't register rewrite rules. So first we need to register rewrite rules and then
						 * refresh them and we need to do this on "shutdown" hook so that it don't mess with other
						 * registered rewrite rules.
						 */
						add_action( 'shutdown', array( $this, 'add_rewrite_rules_and_flush' ) );

						/*
						 * Need to set this flag because for very first time when option isn't initiated, this sanitize
						 * callback will be called 2 times and second time it won't have files set which results into
						 * admin settings error. It's a known WordPress issue.
						 */
						$this->_sdk_bundle_saved = true;
					} else {
						add_settings_error(
							$this->settings_page_slug,
							'ua_wn_failed_to_parse_zip',
							esc_html__( 'Failed to parse SDK Bundle Zip File, please make sure it\'s correct file.', 'ua-web-notification' )
						);
					}
				} else {
					add_settings_error(
						$this->settings_page_slug,
						'ua_wn_failed_to_upload_file',
						esc_html__( 'Failed to upload SDK Bundle Zip File, please try again.', 'ua-web-notification' )
					);
				}
			} else {
				add_settings_error(
					$this->settings_page_slug,
					'ua_wn_invalid_file',
					esc_html__( 'Invalid file, please upload zip file.', 'ua-web-notification' )
				);
			}
		}

		// Save config from constant. Constant value should be JSON.
		if ( defined( 'UA_WEB_NOTIFICATION_BUNDLE' ) ) {
			$bundle_data = json_decode( UA_WEB_NOTIFICATION_BUNDLE, true );
			$zip_data    = array();

			if ( ! empty( $bundle_data ) && is_array( $bundle_data ) ) {

				// Sanitize data from constant.
				if ( ! empty( $bundle_data['default-icon'] ) ) {
					$zip_data['default-icon'] = esc_url_raw( $bundle_data['default-icon'] );
				}

				if ( ! empty( $bundle_data['default-title'] ) ) {
					$zip_data['default-title'] = sanitize_text_field( $bundle_data['default-title'] );
				}

				if ( ! empty( $bundle_data['default-action-url'] ) ) {
					$zip_data['default-action-url'] = esc_url_raw( $bundle_data['default-action-url'] );
				}

				if ( ! empty( $bundle_data['app-key'] ) ) {
					$zip_data['app-key'] = sanitize_text_field( $bundle_data['app-key'] );
				}

				if ( ! empty( $bundle_data['token'] ) ) {
					$zip_data['token'] = sanitize_text_field( $bundle_data['token'] );
				}

				if ( ! empty( $bundle_data['vapid-pub-key'] ) ) {
					$zip_data['vapid-pub-key'] = sanitize_text_field( $bundle_data['vapid-pub-key'] );
				}

				if ( ! empty( $bundle_data['website-push-id'] ) ) {
					$zip_data['website-push-id'] = sanitize_text_field( $bundle_data['website-push-id'] );
				}

				if ( ! empty( $bundle_data['secure-bridge'] ) ) {
					$zip_data['secure-bridge'] = $bundle_data['secure-bridge'];
				}

				if ( ! empty( $zip_data ) ) {
					update_option( $this->ua_config_option_key, $zip_data );

					/*
					 * Flush rewrite rules so that admin don't need to save permalink settings in order to rewrite
					 * rules to work.
					 *
					 * Rewrite rule for worker js will only be added if constant is configured. Rewrite rules are added
					 * on "init" hook which is already executed by now and because constant wasn't configured at that
					 * time and it didn't register rewrite rules. So first we need to register rewrite rules and then
					 * refresh them and we need to do this on "shutdown" hook so that it don't mess with other
					 * registered rewrite rules.
					 */
					add_action( 'shutdown', array( $this, 'add_rewrite_rules_and_flush' ) );

					/*
					 * Need to set this flag because for very first time when option isn't initiated, this sanitize
					 * callback will be called 2 times and second time it won't have files set which results into
					 * admin settings error. It's a known WordPress issue.
					 */
					$this->_sdk_bundle_saved = true;
				} else {
					add_settings_error(
						$this->settings_page_slug,
						'ua_wn_failed_to_parse_constant',
						__( 'Failed to parse SDK Bundle constant, please make sure it\'s been added and the value is JSON.', 'ua-web-notification' )
					);
				}
			} else {
				add_settings_error(
					$this->settings_page_slug,
					'ua_wn_wrong_constant_format',
					__( 'Failed to read SDK Bundle Zip constant or the value is not valid JSON.', 'ua-web-notification' )
				);
			}
		}

		return $sanitized_input;
	}

	/**
	 * Add rewrite rules and then flush them.
	 */
	public function add_rewrite_rules_and_flush() {
		$ua_wn = UA_WEB_NOTIFICATION::instance();
		if ( isset( $ua_wn->integration ) && is_a( $ua_wn->integration, 'UA_WEB_NOTIFICATION_INTEGRATION' ) && method_exists( $ua_wn->integration, 'add_rewrite_rule' ) ) {

			// First add rewrite rules
			$ua_wn->integration->add_rewrite_rule();

			// Now flush them
			flush_rewrite_rules();
		}
	}

	/**
	 * Settings help tabs.
	 */
	public function settings_help() {

		$current_screen = get_current_screen();

		// Bail if no current screen.
		if ( ! $current_screen ) {
			return;
		}

		// Overview.
		$current_screen->add_help_tab(
			array(
				'id'      => 'overview',
				'title'   => esc_html__( 'Overview', 'ua-web-notification' ),
				'content' => '<p>' . wp_kses_post( __( 'This is the settings screen for <a target="_blank" href="https://www.airship.com/platform/channels/web-notifications/">Web Notifications, Airship\'s web notification solution</a>. Please refer to the additional tabs for more information about each individual section.', 'ua-web-notification' ) ) . '</p>' .
					'<p>' . wp_kses_post( __( 'To use this plugin, you’ll need a Web Notifications account from Airship. <a target="_blank" href="https://www.urbanairship.com/products/web-push-notifications/pricing">Sign up for a free</a> starter plan with unlimited web notifications and up to 1,000 addressable users.', 'ua-web-notification' ) ) . '</p>',
			)
		);

		// Account settings section.
		$current_screen->add_help_tab(
			array(
				'id'      => 'account_settings',
				'title'   => esc_html__( 'Account Settings', 'ua-web-notification' ),
				'content' => '<p>' . esc_html__( 'The Account Settings section has two fields to complete:', 'ua-web-notification' ) . '</p>' .
					'<p>' .
					'<ul>' .
					'<li>' . wp_kses_post( __( '<strong>SDK Bundle Zip File</strong> - When you complete the Web channel configuration steps in the Airship dashboard, your setup files will be placed in a ZIP file for download. If you need to find this file after completing initial configuration, navigate to Channels under the Settings drop-down in the <a target="_blank" href="https://go.urbanairship.com/accounts/login/">Airship Dashboard</a> and then click Web Browsers to expose the Download SDK Bundle button. <br>Remember, if you haven’t created your account yet, you can sign up for free <a target="_blank" href="https://www.urbanairship.com/products/web-push-notifications/pricing">here</a>.', 'ua-web-notification' ) ) . '</li>' .
					'<li>' . wp_kses_post( __( '<strong>Master Secret</strong> - Airship generates this string used for server to server API access. You can find the Master Secret in the <a target="_blank" href="https://go.urbanairship.com/accounts/login/">Airship Dashboard</a> by navigating to APIs & Integrations under the Settings drop-down.', 'ua-web-notification' ) ) . '</li>' .
					'</ul>' .
					'</p>' .
					'<p>' . esc_html__( 'You must click the Save Updates button at the bottom of the screen for new settings to take effect.', 'ua-web-notification' ) . '</p>',
			)
		);

		// Notification settings section.
		$current_screen->add_help_tab(
			array(
				'id'      => 'notification_settings',
				'title'   => esc_html__( 'Notification Settings', 'ua-web-notification' ),
				'content' => '<p>' . wp_kses_post( __( 'The Default Title, Default Action URL, Default Icon, and Safari Support fields will be populated when you upload the SDK Bundle Zip file for the first time. To update these fields visit the <a target="_blank" href="https://go.urbanairship.com/accounts/login/">Airship Dashboard</a>, change the desired values, and download an updated SDK Bundle Zip File to upload on the plugin settings screen.', 'ua-web-notification' ) ) . '</p>' .
					'<p>' . esc_html__( 'The Notification Settings section has one optional field to complete:', 'ua-web-notification' ) . '</p>' .
					'<p>' .
					'<ul>' .
					'<li>' . wp_kses_post( __( '<strong>Featured Image</strong> - Selecting this checkbox will use a post’s Featured Image for the notification icon instead of the default icon from the Airship setup file ZIP. When selected, if a post does not have a Featured Image, the default icon will be used instead.', 'ua-web-notification' ) ) . '</li>' .
					'</ul>' .
					'</p>' .
					'<p>' . esc_html__( 'You must click the Save Updates button at the bottom of the screen for new settings to take effect.', 'ua-web-notification' ) . '</p>' .
					'<p>' . esc_html__( 'To update the Default Title, Default Action URL, or Default Icon fields, visit the Airship Dashboard, change the desired values, and download an updated SDK Bundle Zip File.', 'ua-web-notification' ) . '</p>',
			)
		);

		// Opt-in prompt settings section
		$current_screen->add_help_tab(
			array(
				'id'      => 'opt_in_prompt_settings',
				'title'   => esc_html__( 'Opt-In Prompt Settings', 'ua-web-notification' ),
				'content' => '<p>' . esc_html__( 'The Opt-In Prompt Settings section has two settings to configure:', 'ua-web-notification' ) . '</p>' .
					'<p>' .
					'<ul>' .
					'<li>' . wp_kses_post( __( '<strong>CSS Class for Custom Opt-In</strong> - By applying the "ua-opt-in" CSS class to any page element (such as a modal, button, or menu item), you can use that element to trigger the browser’s native opt-in prompt to display.', 'ua-web-notification' ) ) . '</li>' .
					'<li>' . wp_kses_post( __( '<strong>Display Default Browser Prompt</strong> - Toggle this setting on or off to display the browser’s native opt-in prompt after a visitor views a set number of pages based on the first editable value (default is the first page view), and display again following initial dismissal of the opt-in prompt based on the second editable value. Each browser treats the repeat prompt displays differently; some browsers block the opt-in from showing after a certain number of dismissals.', 'ua-web-notification' ) ) . '</li>' .
					'</ul>' .
					'</p>' .
					'<p>' . esc_html__( 'You must click the Save Updates button at the bottom of the screen for new settings to take effect.', 'ua-web-notification' ) . '</p>',
			)
		);

		// Help Sidebar
		$current_screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'ua-web-notification' ) . '</strong></p>' .
			'<p>' . wp_kses_post( __( '<a href="https://docs.urbanairship.com/platform/web/" target="_blank">Web SDK Docs</a>', 'ua-web-notification' ) ) . '</p>' .
			'<p>' . wp_kses_post( __( '<a href="https://support.urbanairship.com/hc/en-us" target="_blank">Support</a>', 'ua-web-notification' ) ) . '</p>'
		);
	}

	/**
	 * Get UA app master secret.
	 *
	 * @return string
	 */
	public function get_app_master_secret() {
		$options = get_option( $this->option_key );
		return isset( $options['master-secret'] ) ? $options['master-secret'] : '';
	}

	/**
	 * Get default title.
	 *
	 * @since 1.2.3 Added entity encoding/decoding to fix apostrophe bug.
	 *
	 * @return string
	 */
	public function get_default_title() {
		$options = get_option( $this->ua_config_option_key );

		$title = '';
		if ( isset( $options['default-title'] ) ) {
			$title = html_entity_decode( $options['default-title'], ENT_QUOTES, 'UTF-8' );
		}

		return $title;
	}

	/**
	 * Get default action URL.
	 *
	 * @return string
	 */
	public function get_default_action_url() {
		$options = get_option( $this->ua_config_option_key );
		return isset( $options['default-action-url'] ) ? $options['default-action-url'] : '';
	}

	/**
	 * Get default image URL.
	 *
	 * @return string
	 */
	public function get_default_image_url() {
		$options = get_option( $this->ua_config_option_key );
		return isset( $options['default-icon'] ) ? $options['default-icon'] : '';
	}

	/**
	 * Get app key.
	 *
	 * @return string
	 */
	public function get_app_key() {
		$options = get_option( $this->ua_config_option_key );
		return isset( $options['app-key'] ) ? $options['app-key'] : '';
	}

	/**
	 * Get token.
	 *
	 * @return string
	 */
	public function get_token() {
		$options = get_option( $this->ua_config_option_key );
		return isset( $options['token'] ) ? $options['token'] : '';
	}

	/**
	 * Get vapid public key.
	 *
	 * @return string
	 */
	public function get_vapid_public_key() {
		$options = get_option( $this->ua_config_option_key );
		return isset( $options['vapid-pub-key'] ) ? $options['vapid-pub-key'] : '';
	}

	/**
	 * Get website push ID.
	 *
	 * @since 1.3.0
	 *
	 * @return string|false
	 */
	public function get_website_push_id() {
		$options = get_option( $this->ua_config_option_key );
		return isset( $options['website-push-id'] ) ? $options['website-push-id'] : false;
	}

	/**
	 * Determine whether to integrate secure bridge or not
	 *
	 * @return bool
	 */
	public function use_secure_bridge() {
		$options = get_option( $this->ua_config_option_key );

		return ( isset( $options['secure-bridge'] ) && $options['secure-bridge'] ) ? true : false;
	}

	/**
	 * Determine whether to use featured image or not
	 *
	 * @return bool
	 */
	public function use_featured_image() {
		$options = get_option( $this->option_key );

		return ( isset( $options['featured-image'] ) && '1' === $options['featured-image'] ) ? true : false;
	}

	/**
	 * Get prompt settings
	 *
	 * @return array
	 */
	public function get_prompt_settings() {
		$options = get_option( $this->option_key );

		return array(
			'prompt'             => ! empty( $options['prompt'] ) ? $options['prompt'] : '',
			'prompt_views'       => empty( $options['prompt_views'] ) ? 1 : (int) $options['prompt_views'],
			'prompt_again_views' => ! isset( $options['prompt_again_views'] ) ? 1 : (int) $options['prompt_again_views'],
		);
	}

	/**
	 * Parse UA bundle zip file and extract configs from push-worker.js file.
	 *
	 * @param  string $file Filename of the newly-uploaded file.
	 * @return array
	 */
	public function parse_zip( $file ) {
		global $wp_filesystem;

		WP_Filesystem();

		$extracted_data = array();

		$extract_dir = trailingslashit( dirname( $file ) ) . basename( $file, '.zip' );

		if ( $wp_filesystem->is_dir( $extract_dir ) ) {
			$wp_filesystem->delete( $extract_dir, true );
		}

		$unzip = unzip_file( $file, $extract_dir );

		unlink( $file );

		if ( true === $unzip ) {

			if ( $wp_filesystem->exists( $extract_dir . '/push-worker.js' ) ) {
				$file_content = $wp_filesystem->get_contents( $extract_dir . '/push-worker.js' );

				if ( false !== $file_content ) {

					/**
					 * There is a very strange case that all of the "-" in strings that are inputted by user (default
					 * icon, title and action URL) are getting converted as "\u002D" which is in unicode format but
					 * none of the other characters are having issue. It's a special case so need to handle it manually.
					 */

					if ( preg_match( "/(.*)defaultIcon:[\s]*'(.*)'/", $file_content, $icon_match ) ) {
						$extracted_data['default-icon'] = $this->unicode_to_utf8( $icon_match['2'] );
					}

					if ( preg_match( "/(.*)defaultTitle:[\s]*'(.*)'/", $file_content, $title_match ) ) {
						$extracted_data['default-title'] = $this->unicode_to_utf8( $title_match['2'] );
					}

					if ( preg_match( "/(.*)defaultActionURL:[\s]*'(.*)'/", $file_content, $url_match ) ) {
						$extracted_data['default-action-url'] = $this->unicode_to_utf8( $url_match['2'] );
					}

					if ( preg_match( "/(.*)appKey:[\s]*'(.*)'/", $file_content, $app_key_match ) ) {
						$extracted_data['app-key'] = $app_key_match['2'];
					}

					if ( preg_match( "/(.*)token:[\s]*'(.*)'/", $file_content, $token_match ) ) {
						$extracted_data['token'] = $token_match['2'];
					}

					if ( preg_match( "/(.*)vapidPublicKey:[\s]*'(.*)'/", $file_content, $vapid_pub_key_match ) ) {
						$extracted_data['vapid-pub-key'] = $vapid_pub_key_match['2'];
					}
				}
			}

			$extracted_data['secure-bridge'] = false;
			if ( $wp_filesystem->exists( $extract_dir . '/secure-bridge.html' ) ) {
				$extracted_data['secure-bridge'] = true;
			}

			$extracted_data['website-push-id'] = '';
			if ( $wp_filesystem->exists( $extract_dir . '/snippet.html' ) ) {
				$file_content = $wp_filesystem->get_contents( $extract_dir . '/snippet.html' );

				if ( false !== $file_content ) {

					/**
					 * Extract website push ID.
					 *
					 * @since 1.3.0
					 */
					if ( preg_match( "/(.*)websitePushId:[\s]*'(.*)'/", $file_content, $website_push_id ) ) {
						$extracted_data['website-push-id'] = $website_push_id['2'];
					}
				}
			}
		}

		$wp_filesystem->delete( $extract_dir, true );

		return $extracted_data;
	}

	/**
	 * Determine if UA a/c is configured or not.
	 *
	 * @return bool
	 */
	public function is_ua_ac_configured() {

		$api_key       = $this->get_app_key();
		$master_secret = $this->get_app_master_secret();

		if ( ! empty( $api_key ) && ! empty( $master_secret ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert Unicode string to UTF-8.
	 *
	 * @param  string $string Unicode string to be converted.
	 * @return string
	 */
	public function unicode_to_utf8( $string ) {

		if ( false !== strpos( $string, '\u' ) ) {
			$string = html_entity_decode(
				preg_replace( '/u([0-9A-F]{4})/', "&#x\\1;", str_replace( '\u', 'u', $string ) ),
				ENT_NOQUOTES,
				'UTF-8'
			);
		}

		return $string;
	}

	/**
	 * Output the safari support admin notice.
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function safari_support_admin_notice() {

		$screen = get_current_screen();
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return;
		}

		if ( 'settings_page_ua-web-notification' === $screen->base ) {
			return;
		}

		$is_dissmised = (bool) get_option( $this->safari_support_notice_option_key, false );
		if ( $is_dissmised ) {
			return;
		}

		?>
		<div
			data-notice="<?php echo esc_attr( $this->safari_support_notice_option_key ); ?>"
			class="notice notice-info is-dismissible js-ua-wn-notice"
		>
			<p>
				<?php
				printf(
					/* translators: 1: open tag, 2: close tag */
					wp_kses_post( __( 'Airship Web Notifications now supports Safari. Go to the %1$sSettings%2$s page for more details.', 'ua-web-notification' ) ),
					sprintf(
						'<a href="%1$s">',
						esc_url( admin_url( 'options-general.php?page=' . $this->settings_page_slug ) )
					),
					'</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Output the constant support admin notice.
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function constant_support_admin_notice() {

		if ( ! defined( 'UA_WEB_NOTIFICATION_BUNDLE' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return;
		}

		if ( 'settings_page_ua-web-notification' !== $screen->base ) {
			return;
		}

		$option = get_option( $this->option_key );
		if ( ! empty( $option ) ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: 1: open tag, 2: close tag */
					wp_kses_post( __( 'The constant %1$sUA_WEB_NOTIFICATION_BUNDLE%2$s is defined but the settings are not saved. Click the "Save Updates" button.', 'ua-web-notification' ) ),
					'<strong>',
					'</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles ajax requests to persist notices dismissal.
	 *
	 * @since  1.3.0
	 * @return void
	 */
	public function dismiss_admin_notice() {
		check_ajax_referer( 'dismissible-notice', 'nonce' );

		$option_key = sanitize_key( $_POST['notice'] );
		if (
			! empty( $option_key )
			&& in_array( $option_key, [ $this->safari_support_notice_option_key ], true )
		) {
			update_option( $option_key, true );
		}

		wp_die();
	}
}
