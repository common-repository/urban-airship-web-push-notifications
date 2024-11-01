<?php // phpcs:disable Squiz.Commenting.FileComment.Missing

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom notification post type
 *
 * Class UA_WEB_NOTIFICATION_POST_TYPE
 */
class UA_WEB_NOTIFICATION_POST_TYPE {

	/**
	 * Post type name
	 *
	 * @var string
	 */
	public $post_type_name = 'ua-push-notification';

	/**
	 * UA_WEB_NOTIFICATION_POST_TYPE constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'ua_wn_allow_post_type', array( $this, 'allow_post_type_for_notification' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'filter_post_title_placeholder' ), 10, 2 );
		add_filter( 'manage_edit-' . $this->post_type_name . '_columns', array( $this, 'notification_list_columns' ) );
		add_action( 'manage_' . $this->post_type_name . '_posts_custom_column', array( $this, 'notification_list_column_data' ), 10, 2 );
	}

	/**
	 * Register post type
	 */
	public function register_post_type() {

		$labels = array(
			'name'               => esc_html_x( 'Web Notifications', 'post type general name', 'ua-web-notification' ),
			'singular_name'      => esc_html_x( 'Web Notification', 'post type singular name', 'ua-web-notification' ),
			'menu_name'          => esc_html_x( 'Web Notifications', 'admin menu', 'ua-web-notification' ),
			'name_admin_bar'     => esc_html_x( 'Web Notification', 'add new on admin bar', 'ua-web-notification' ),
			'add_new'            => esc_html_x( 'Add New', 'web notification', 'ua-web-notification' ),
			'add_new_item'       => esc_html__( 'Add New Web Notification', 'ua-web-notification' ),
			'new_item'           => esc_html__( 'New Web Notification', 'ua-web-notification' ),
			'edit_item'          => esc_html__( 'Edit Web Notification', 'ua-web-notification' ),
			'view_item'          => esc_html__( 'View Web Notification', 'ua-web-notification' ),
			'all_items'          => esc_html__( 'All Web Notifications', 'ua-web-notification' ),
			'search_items'       => esc_html__( 'Search Web Notifications', 'ua-web-notification' ),
			'parent_item_colon'  => esc_html__( 'Parent Web Notifications:', 'ua-web-notification' ),
			'not_found'          => esc_html__( 'No web notifications found.', 'ua-web-notification' ),
			'not_found_in_trash' => esc_html__( 'No web notifications found in Trash.', 'ua-web-notification' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-megaphone',
			'supports'           => array( 'title' ),
		);

		register_post_type( $this->post_type_name, $args );
	}

	/**
	 * Allow custom notification post type for notification feature.
	 *
	 * @param  bool   $allow     Flag that indicates if the post type is
	 *                           allowed or not to push notification.
	 * @param  string $post_type The post type.
	 * @return bool
	 */
	public function allow_post_type_for_notification( $allow, $post_type ) {

		if ( $post_type === $this->post_type_name ) {
			$allow = true;
		}

		return $allow;
	}

	/**
	 * Filter post title placeholder for custom notification post type.
	 *
	 * @param  string   $title The post title.
	 * @param  \WP_Post $post  The post object.
	 * @return string
	 */
	public function filter_post_title_placeholder( $title, $post ) {

		if ( get_post_type( $post ) === $this->post_type_name ) {
			$title = esc_html__( 'Notification Title', 'ua-web-notification' );
		}

		return $title;
	}

	/**
	 * Add notification text column is post list table.
	 *
	 * @param  array $columns List of columns.
	 * @return mixed
	 */
	public function notification_list_columns( $columns ) {

		$columns['notification_text'] = esc_html__( 'Notification Text', 'ua-web-notification' );

		// Move date at the end of array
		if ( isset( $columns['date'] ) ) {
			$date = $columns['date'];
			unset( $columns['date'] );
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * Fill notification text column data in post list table.
	 *
	 * @param string $col_name The name of the column.
	 * @param int    $post_id  The post ID.
	 */
	public function notification_list_column_data( $col_name, $post_id ) {

		if ( 'notification_text' === $col_name ) {
			$ua_notification = UA_WEB_NOTIFICATION::instance();
			if ( isset( $ua_notification->admin ) && is_a( $ua_notification->admin, 'UA_WEB_NOTIFICATION_ADMIN' ) ) {
				$data = $ua_notification->admin->get_post_notification_data( $post_id );
				if ( is_array( $data ) && isset( $data['text'] ) ) {
					echo esc_html( $data['text'] );
				}
			}
		}
	}
}
