<?php // phpcs:disable Squiz.Commenting.FileComment.Missing

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All admin hooks
 *
 * Class UA_WEB_NOTIFICATION_ADMIN
 */
class UA_WEB_NOTIFICATION_ADMIN {

	/**
	 * UA_WEB_NOTIFICATION_ADMIN constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post', array( $this, 'push_notification' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'edit_form_before_permalink', array( $this, 'notification_title_note' ), 1 );
	}

	/**
	 * Register meta box.
	 *
	 * @since 1.3.4 Removed meta box order override.
	 */
	public function register_meta_box() {
		$screen = get_current_screen();

		// Bail if screen is not setup
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return;
		}

		// Bail if current post type is not allowed
		if ( ! $this->is_post_type_allowed( $screen->id ) ) {
			return;
		}

		// Bail if not notification post type class
		$ua_notification = UA_WEB_NOTIFICATION::instance();
		if ( ! isset( $ua_notification->post_type ) || ! is_a( $ua_notification->post_type, 'UA_WEB_NOTIFICATION_POST_TYPE' ) ) {
			return;
		}

		if ( $screen->id === $ua_notification->post_type->post_type_name ) {
			// Add meta box for push notification data right after post title field
			add_meta_box(
				'webpushnotificationbox',
				esc_html__( 'Web Notifications', 'ua-web-notification' ),
				array( $this, 'render_notification_meta_box' ),
				null,
				'normal'
			);
		} else {
			// Add meta box for push notification data in sidebar
			add_meta_box(
				'webpushnotificationbox',
				esc_html__( 'Web Notifications', 'ua-web-notification' ),
				array( $this, 'render_notification_meta_box' ),
				null,
				'side',
				'high'
			);
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {

			wp_enqueue_style(
				'ua-wn-post-edit',
				UA_WEB_NOTIFICATION_URL . 'assets/css/post-edit.css',
				array(),
				UA_WEB_NOTIFICATION_VERSION
			);

			wp_enqueue_script(
				'ua-wn-post-edit',
				UA_WEB_NOTIFICATION_URL . 'assets/js/post-edit.js',
				array( 'jquery' ),
				UA_WEB_NOTIFICATION_VERSION,
				true
			);

			wp_enqueue_media();
		}
	}

	/**
	 * Render checkbox to send notification.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function render_notification_meta_box( $post ) {

		// Bail if not a post object
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// Bail if post type is not allowed
		if ( ! $this->is_post_type_allowed( get_post_type( $post ) ) ) {
			return;
		}

		// Bail if can't push notification
		if ( ! $this->can_push_notification() ) {
			return;
		}

		// Bail if can't publish this post
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}

		// Bail if not notification post type class
		$ua_notification = UA_WEB_NOTIFICATION::instance();
		if ( ! isset( $ua_notification->post_type ) || ! is_a( $ua_notification->post_type, 'UA_WEB_NOTIFICATION_POST_TYPE' ) ) {
			return;
		}

		if ( $this->already_pushed( $post->ID ) ) {
			if ( $post->post_type === $ua_notification->post_type->post_type_name ) {
				$this->show_notification_data( $post->ID );
			} else {
				$date = date_i18n( 'M j, Y @ H:i', $this->get_notification_sent_time( $post->ID ) );
				?>
				<div class="ua-wn-box">
					<label>
						<?php esc_html_e( 'Web notification sent on:', 'ua-web-notification' ); ?> <strong><?php echo esc_html( $date ); ?></strong>
					</label>
				</div>
				<?php
			}
		} else {
			$preference = $this->get_post_notification_preference( $post->ID );

			// If custom notification post type than always send notification on publish.
			if ( $post->post_type === $ua_notification->post_type->post_type_name ) {
				$preference = true;
			}

			$notification_data = $this->get_post_notification_data( $post->ID );
			?>
			<div class="ua-wn-box">
				<?php wp_nonce_field( 'ua_wn_post_notification', 'ua_wn_nonce' ); ?>
				<?php if ( $post->post_type === $ua_notification->post_type->post_type_name ) : ?>
					<input value="on" type="hidden" name="ua-wn-create-notification">
				<?php else : ?>
					<div class="ua-wn-meta-data-row">
						<p class="ua-wn-meta-data-field">
							<input id="ua-wn-enable-post-notification" <?php checked( true, $preference ); ?> type="checkbox" name="ua-wn-create-notification">
							<label for="ua-wn-enable-post-notification">
								<?php if ( 'publish' === $post->post_status ) : ?>
									<?php esc_html_e( 'Send web notification on update', 'ua-web-notification' ); ?>
								<?php else : ?>
									<?php esc_html_e( 'Send web notification on publish', 'ua-web-notification' ); ?>
								<?php endif; ?>
							</label>
						</p>
					</div>
				<?php endif; ?>

				<div class="ua-wn-meta-data
				<?php
				if ( true !== $preference ) {
					echo 'ua-hide'; }
				?>
				">
					<div class="ua-wn-meta-data-row">
						<p class="ua-wn-meta-data-field">
							<input <?php checked( true, $notification_data['persistent'] ); ?> type="checkbox" name="ua-wn-notification-data[persistent]" id="ua-wn-notification-require-interaction">
							<label for="ua-wn-notification-require-interaction" title="<?php esc_attr_e( 'Enabling Require Interaction requires a user to interact with your notification in order to remove it from their computer screen.', 'ua-web-notification' ); ?>">
								<?php esc_html_e( 'Require Interaction', 'ua-web-notification' ); ?>
							</label>
							<span class="ua-tooltip">
								<span class="ua-tooltip-text">
									<?php esc_html_e( 'Enabling Require Interaction requires a user to interact with your notification in order to remove it from their computer screen.', 'ua-web-notification' ); ?>
								</span>
							</span>
						</p>
						<div class="description ua-wn-meta-data-description">
						<?php
							printf(
								/* translators: 1: open tag, 2: close tag */
								esc_html__( '%1$sNote:%2$s This is not supported by Safari.', 'ua-web-notification' ),
								'<strong>',
								'</strong>'
							);
						?>
						</div>
					</div>
					<?php if ( $post->post_type !== $ua_notification->post_type->post_type_name ) : ?>
						<div class="ua-wn-meta-data-row">
							<p class="ua-wn-meta-data-field">
								<label>
									<input type="text" class="large-text" name="ua-wn-notification-data[title]" title="<?php esc_attr_e( 'Optional field. If field is left blank, your default Title will be used.', 'ua-web-notification' ); ?>" value="<?php echo esc_attr( $notification_data['title'] ); ?>" placeholder="<?php esc_attr_e( 'Notification Title (Optional)', 'ua-web-notification' ); ?>">
								</label>
								<span class="ua-tooltip">
									<span class="ua-tooltip-text">
										<?php esc_html_e( 'Optional field. If field is left blank, your default Title will be used.', 'ua-web-notification' ); ?>
									</span>
								</span>
							</p>
						</div>
					<?php else : ?>
						<div class="ua-wn-meta-data-row">
							<p class="ua-wn-meta-data-field">
								<label>
									<input type="url" class="large-text" name="ua-wn-notification-data[url]" title="<?php esc_attr_e( 'Optional Field. This URL will be where users are sent when they click on your notification. If field is left blank, users will be sent to your default Action URL.', 'ua-web-notification' ); ?>" value="<?php echo esc_attr( $notification_data['url'] ); ?>" placeholder="<?php esc_attr_e( 'Action URL (Optional)', 'ua-web-notification' ); ?>">
								</label>
								<span class="ua-tooltip">
									<span class="ua-tooltip-text">
										<?php esc_html_e( 'Optional Field. This URL will be where users are sent when they click on your notification. If field is left blank, users will be sent to your default Action URL.', 'ua-web-notification' ); ?>
									</span>
								</span>
							</p>
						</div>
					<?php endif; ?>
					<div class="ua-wn-meta-data-row">
						<p class="ua-wn-meta-data-field">
							<label>
								<?php
									$text_placeholder = esc_html__( 'Notification Text (Optional)', 'ua-web-notification' );
									$text_title       = esc_html__( 'Optional Field. If field is left blank, the post title will be used for the notification body.', 'ua-web-notification' );
								if ( $post->post_type === $ua_notification->post_type->post_type_name ) {
									$text_placeholder = esc_html__( 'Notification Text (Required)', 'ua-web-notification' );
									$text_title       = esc_html__( 'Required Field. This is the text for the notification body.', 'ua-web-notification' );
								}
								?>
								<input type="text" class="large-text" name="ua-wn-notification-data[text]" title="<?php echo esc_attr( $text_title ); ?>" value="<?php echo esc_attr( $notification_data['text'] ); ?>" placeholder="<?php echo esc_attr( $text_placeholder ); ?>">
							</label>
							<span class="ua-tooltip">
								<span class="ua-tooltip-text"><?php echo esc_html( $text_title ); ?></span>
							</span>
						</p>
					</div>
					<div class="ua-wn-meta-data-row">
						<label><?php esc_html_e( 'Notification Icon:', 'ua-web-notification' ); ?></label>
						<ul class="ua-wn-icon-options">
							<li>
								<label>
									<input type="radio" name="ua-wn-notification-data[icon]" checked value="default">
									<?php esc_html_e( 'Use default notification icon', 'ua-web-notification' ); ?>
								</label>
							</li>
							<?php if ( $post->post_type !== $ua_notification->post_type->post_type_name ) : ?>
								<li>
									<label>
										<input type="radio" name="ua-wn-notification-data[icon]" <?php checked( 'featured_image', $notification_data['icon'] ); ?> value="featured_image">
										<?php esc_html_e( 'Use this post\'s featured image', 'ua-web-notification' ); ?>
										<div class="description ua-wn-meta-data-description">
										<?php
											printf(
												/* translators: 1: open tag, 2: close tag */
												esc_html__( '%1$sNote:%2$s This is not supported by Safari. Default icon will be used.', 'ua-web-notification' ),
												'<strong>',
												'</strong>'
											);
										?>
										</div>
									</label>
								</li>
							<?php endif; ?>
							<li>
								<label>
									<input type="radio" id="ua-wn-notification-data-icon" name="ua-wn-notification-data[icon]" <?php checked( true, ( intval( $notification_data['icon'] ) > 0 ) ); ?> value="custom">
									<a href="#" id="ua-wn-icon-select"><?php esc_html_e( 'Select an image', 'ua-web-notification' ); ?></a>
									<div class="description ua-wn-meta-data-description">
									<?php
										printf(
											/* translators: 1: open tag, 2: close tag */
											esc_html__( '%1$sNote:%2$s This is not supported by Safari. Default icon will be used.', 'ua-web-notification' ),
											'<strong>',
											'</strong>'
										);
									?>
									</div>
									<?php
									$icon_src = '';
									if ( intval( $notification_data['icon'] ) > 0 ) {
										$icon_src = wp_get_attachment_image_url( $notification_data['icon'], 'medium' );
									}
									?>
									<img src="<?php echo esc_url( $icon_src ); ?>" id="ua-wn-icon-preview">
								</label>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Notification title note for custom notification post type.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function notification_title_note( $post ) {

		// Bail if not notification post type class
		$ua_notification = UA_WEB_NOTIFICATION::instance();
		if ( ! isset( $ua_notification->post_type ) || ! is_a( $ua_notification->post_type, 'UA_WEB_NOTIFICATION_POST_TYPE' ) ) {
			return;
		}

		// Bail if not custom notification post type
		if ( get_post_type( $post ) !== $ua_notification->post_type->post_type_name ) {
			return;
		}
		?>
		<p class="description"><?php esc_html_e( 'Optional field. If field is left blank, your default Title will be used.', 'ua-web-notification' ); ?></p>
		<?php
	}

	/**
	 * Handle notification push.
	 *
	 * @param  int      $post_id The post ID.
	 * @param  \WP_Post $post    The post object.
	 * @return void
	 */
	public function push_notification( $post_id, $post ) {

		// Bail if posts importing in progress
		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return;
		}

		// Bail if not a post object
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// Bail if post type is not allowed
		if ( ! $this->is_post_type_allowed( get_post_type( $post ) ) ) {
			return;
		}

		// Bail if can't push notification
		if ( ! $this->can_push_notification() ) {
			return;
		}

		// Bail if current user can't publish this post but also take care of cron events which publish post
		if ( ! current_user_can( 'publish_posts', $post->ID ) && ! defined( 'DOING_CRON' ) ) {
			return;
		}

		// Bail if autosave or revision
		if ( wp_is_post_autosave( $post->ID ) || wp_is_post_revision( $post->ID ) ) {
			return;
		}

		// Bail if notification is already pushed
		if ( $this->already_pushed( $post->ID ) ) {
			return;
		}

		/*
		 * This is save post request
		 * - Save notification preference
		 */
		if ( $this->is_post_request() ) {
			$nonce = filter_input( INPUT_POST, 'ua_wn_nonce', FILTER_SANITIZE_STRING );
			if ( wp_verify_nonce( $nonce, 'ua_wn_post_notification' ) ) {
				$preference = filter_input( INPUT_POST, 'ua-wn-create-notification', FILTER_SANITIZE_STRING );
				if ( 'on' === $preference ) {
					update_post_meta( $post_id, 'ua_wn_notification_preference', true );
				} else {
					delete_post_meta( $post_id, 'ua_wn_notification_preference' );
				}

				$notification_post_data = filter_input( INPUT_POST, 'ua-wn-notification-data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				if ( ! empty( $notification_post_data ) && is_array( $notification_post_data ) ) {
					update_post_meta(
						$post_id,
						'ua_wn_notification_data',
						array(
							'persistent' => ( isset( $notification_post_data['persistent'] ) && 'on' === $notification_post_data['persistent'] ) ? true : false,
							'title'      => isset( $notification_post_data['title'] ) ? sanitize_text_field( $notification_post_data['title'] ) : '',
							'text'       => isset( $notification_post_data['text'] ) ? sanitize_text_field( $notification_post_data['text'] ) : '',
							'icon'       => isset( $notification_post_data['icon'] ) ? sanitize_text_field( $notification_post_data['icon'] ) : '',
							'url'        => isset( $notification_post_data['url'] ) ? esc_url_raw( $notification_post_data['url'] ) : '',
						)
					);
				}
			}
		}

		/*
		 * Push notification if
		 *  - Post status is publish
		 *  - Preference is set to send notification
		 */
		if ( ( 'publish' === get_post_status( $post ) ) && ( true === $this->get_post_notification_preference( $post_id ) ) ) {
			$ua_wn = UA_WEB_NOTIFICATION::instance();

			if ( isset( $ua_wn->api ) && is_a( $ua_wn->api, 'UA_WEB_NOTIFICATION_API' ) ) {

				$post_notification_data = $this->get_post_notification_data( $post_id );

				$notification_icon_url = '';

				if ( 'default' === $post_notification_data['icon'] ) {
					$notification_icon_url = $ua_wn->settings->get_default_image_url();
				} elseif ( 'featured_image' === $post_notification_data['icon'] ) {
					$notification_icon_url = get_the_post_thumbnail_url( $post, 'medium' );
				} else {
					$notification_icon_url = wp_get_attachment_image_url( $post_notification_data['icon'], 'medium' );
				}

				$notification_title = '';

				/**
				 * For custom notification post type, post title is the notification title.
				 */
				if ( $post->post_type === $ua_wn->post_type->post_type_name ) {
					$notification_title = get_the_title( $post );
				} elseif ( ! empty( $post_notification_data['title'] ) ) {
					$notification_title = $post_notification_data['title'];
				}

				$notification_alert = '';

				if ( ! empty( $post_notification_data['text'] ) ) {
					$notification_alert = $post_notification_data['text'];
				} elseif ( $post->post_type !== $ua_wn->post_type->post_type_name ) {
					$notification_alert = get_the_title( $post );
				}

				$notification_url = '';

				/**
				 * For custom notification post type, it will be always custom URL and not the post permalink.
				 */
				if ( $post->post_type === $ua_wn->post_type->post_type_name ) {

					if ( ! empty( $post_notification_data['url'] ) ) {
						$notification_url = $post_notification_data['url'];
					}
				} else {
					$notification_url = get_permalink( $post );
				}

				/**
				 * Filter post notification data.
				 *
				 * @since 1.2.0 Added entity encoding/decoding to fix apostrophe bug.
				 *
				 * @param array    $notification_data The notification data.
				 * @param \WP_Post $post              The post object.
				 */
				$notification_data = apply_filters(
					'ua_wn_post_notification_data',
					array(
						'id'         => $post_id,
						'title'      => html_entity_decode( $notification_title, ENT_QUOTES, 'UTF-8' ),
						'text'       => html_entity_decode( $notification_alert, ENT_QUOTES, 'UTF-8' ),
						'url'        => $notification_url,
						'persistent' => $post_notification_data['persistent'],
						'icon_url'   => set_url_scheme( $notification_icon_url, 'https' ),
					),
					$post
				);

				$ua_wn_send_push_notification = true;

				/**
				 * Notification text is required field for custom content type.
				 */
				if ( ( $post->post_type === $ua_wn->post_type->post_type_name ) && empty( $notification_data['text'] ) ) {
					$ua_wn_send_push_notification = false;
				}

				/**
				 * Filter whether to send push notification or not
				 *
				 * @param boolean $ua_wn_send_push_notification Default true.
				 * @param WP_Post $post Post object
				 * @param array $notification_data push notification data
				 */
				if ( true === apply_filters( 'ua_wn_send_push_notification', $ua_wn_send_push_notification, $post, $notification_data ) ) {
					$res = $ua_wn->api->push_web_notification( $notification_data, $post_id );

					if ( false !== $res ) {
						update_post_meta( $post_id, 'ua_wn_response', $res );
						update_post_meta( $post_id, 'ua_wn_sent_time', time() );
					}
				}
			}
		}
	}

	/**
	 * Show notification sent data.
	 *
	 * @access protected
	 * @param  int $post_id The post ID.
	 * @return void
	 */
	protected function show_notification_data( $post_id ) {

		$notification_data = $this->get_notification_sent_data( $post_id );
		if ( empty( $notification_data ) ) {
			return;
		}

		$date = date_i18n( 'M j, Y @ H:i', $this->get_notification_sent_time( $post_id ) );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Require Interaction', 'ua-web-notification' ); ?></th>
				<td><?php echo esc_html( $notification_data['notification']['web']['require_interaction'] ? esc_html__( 'Yes', 'ua-web-notification' ) : esc_html__( 'No', 'ua-web-notification' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Action URL', 'ua-web-notification' ); ?></th>
				<td><a target="_blank" href="<?php echo esc_url( $notification_data['notification']['actions']['open']['content'] ); ?>"><?php echo esc_html( $notification_data['notification']['actions']['open']['content'] ); ?></a></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Text', 'ua-web-notification' ); ?></th>
				<td><?php echo esc_html( $notification_data['notification']['alert'] ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Icon' ); ?></th>
				<td><img src="<?php echo esc_url( $notification_data['notification']['web']['icon']['url'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Sent on', 'ua-web-notification' ); ?></th>
				<td><?php echo esc_html( $date ); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Get notification data.
	 *
	 * @param  int $post_id The post ID.
	 * @return array
	 */
	public function get_post_notification_data( $post_id ) {

		$notification_data = get_post_meta( $post_id, 'ua_wn_notification_data', true );

		if ( empty( $notification_data ) ) {

			$web_notification  = UA_WEB_NOTIFICATION::instance();
			$notification_icon = 'default';

			if ( $web_notification->settings->use_featured_image() ) {
				$notification_icon = 'featured_image';
			}

			$notification_data = array(
				'persistent' => false,
				'title'      => '',
				'text'       => '',
				'icon'       => $notification_icon,
				'url'        => '',
			);
		}

		return $notification_data;
	}

	/**
	 * Get notification sent data.
	 *
	 * Since version 1.2.0 the notification sent data is saved as an array to preserve
	 * UTF-8 encoded characters. Before this, using json_encode() was stripping backslashes
	 * from Unicode escape sequences like “\u00ed”.
	 *
	 * @since  1.2.0
	 * @param  int $post_id The post ID.
	 * @return array
	 */
	public function get_notification_sent_data( $post_id ) {

		$notification_sent_data = get_post_meta( $post_id, 'ua_wn_notification_sent_data', true );
		if ( empty( $notification_sent_data ) ) {
			return array();
		}

		if ( is_array( $notification_sent_data ) ) {
			return $notification_sent_data;
		}

		$notification_sent_data = json_decode( $notification_sent_data, true );

		if ( ! empty( $notification_sent_data['notification']['alert'] ) ) {
			$alert = $notification_sent_data['notification']['alert'];

			// Add the missing backslashes removed by json_encode().
			$alert = preg_replace( '/(u[0-9a-fA-F]{4})/i', '\\\$1', $alert );

			// Decode Unicode escape sequences like “\u00ed” to proper UTF-8 encoded characters.
			$alert = preg_replace_callback(
				'/\\\\u([0-9a-fA-F]{4})/',
				function ( $match ) {
					return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
				},
				$alert
			);

			$notification_sent_data['notification']['alert'] = $alert;
		}

		return $notification_sent_data;
	}

	/**
	 * Check if everything is setup and can send notification.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function can_push_notification() {

		$web_notification = UA_WEB_NOTIFICATION::instance();
		if ( ! $web_notification ) {
			return false;
		}

		$api_key       = $web_notification->settings->get_app_key();
		$master_secret = $web_notification->settings->get_app_master_secret();

		if ( ! empty( $api_key ) && ! empty( $master_secret ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if notification is already sent for a post.
	 *
	 * @access protected
	 * @param  int $post_id The post ID.
	 * @return bool
	 */
	protected function already_pushed( $post_id ) {
		$time_stamp = get_post_meta( $post_id, 'ua_wn_sent_time', true );
		return ! empty( $time_stamp );
	}

	/**
	 * Get notification created timestamp.
	 *
	 * @access protected
	 * @param  int $post_id The post ID.
	 * @return mixed
	 */
	protected function get_notification_sent_time( $post_id ) {
		return get_post_meta( $post_id, 'ua_wn_sent_time', true );
	}

	/**
	 * Get notification checkbox preference.
	 *
	 * @access protected
	 * @param  int $post_id The post ID.
	 * @return bool
	 */
	protected function get_post_notification_preference( $post_id ) {
		return (bool) get_post_meta( $post_id, 'ua_wn_notification_preference', true );
	}

	/**
	 * Determine if post type is allowed or not to push notification.
	 *
	 * @access protected
	 * @param  string $post_type The post type.
	 * @return bool
	 */
	protected function is_post_type_allowed( $post_type ) {

		$post_type_obj = get_post_type_object( $post_type );
		$allow         = $post_type_obj->public;

		/**
		 * Filter to determine if post type is allowed or not to push notification.
		 *
		 * @param  bool   $allow     Flag that indicates if the post type is
		 *                           allowed or not to push notification.
		 * @param  string $post_type The post type.
		 * @return bool
		 */
		return (bool) apply_filters( 'ua_wn_allow_post_type', $allow, $post_type );
	}

	/**
	 * Determine if it's a HTTP POST request.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function is_post_request() {
		return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
	}
}
