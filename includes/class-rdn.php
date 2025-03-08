<?php
/**
 * Remote Dashboard Notifications.
 *
 * @package   Remote Dashobard Notifications
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2013 ThemeAvenue
 */

class Remote_Notifications {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *""
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.3.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'remote-notifications';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );

		// Register post type
		add_action( 'init', array( $this, 'register_notification_post_type' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		add_action( 'admin_notices', array( $this, 'add_notification_preview' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'notice_action' ) );

		// Register taxonomies
		add_action( 'init', array( $this, 'register_channel' ), 10 );
		add_action( 'init', array( $this, 'register_post_type' ), 10 );

		// Add endpoint
		add_action( 'template_redirect', array( $this, 'endpoint' ) );

	}
	
	public function notice_action( $post)
	{
		if('notification' !== $post->post_type){
			return;
		}
		
		?>
		<style>a#post-preview { display: none; }</style>
		<div class="misc-pub-section">
			<a href="<?php echo esc_url(get_edit_post_link($post->ID)).'&wpi_np=1';?>" class="button">Preview Admin Notice</a>
		</div>
		<?php
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    string Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	
private static function get_blog_ids() {

    global $wpdb;

    // Sanitize SQL query to avoid injection
    $sql = $wpdb->prepare("
        SELECT blog_id FROM $wpdb->blogs
        WHERE archived = %d AND spam = %d AND deleted = %d
    ", 0, 0, 0);

    return $wpdb->get_col($sql);
}
    

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, RDN_PATH . 'languages/' . $domain . '-' . $locale . '.mo' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * Create API endpoint inside the custom post type template
	 *
	 * @since 1.0.0
	 */
	
public function endpoint() {

    global $wp_query;

    if (is_archive() && isset($wp_query->query_vars['post_type']) && 'notification' == $wp_query->query_vars['post_type']) {

        $single = RDN_PATH . '/includes/archive-notification.php';

        // Validate the file path
        if (file_exists($single) && strpos(realpath($single), realpath(RDN_PATH)) === 0) {
            include $single;
            exit;
        } else {
            wp_die(__('Invalid file path', 'remote-notifications'));
        }
    }
}
    



	/**
	 * Register notification post type
	 *
	 * @since 1.0.0
	 */
	public function register_notification_post_type() {

		/* Set the default labels */
		$labels = array(
			'name'               => _x( 'Notification', 'post type general name', 'remote-notifications' ),
			'singular_name'      => _x( 'Notification', 'post type singular name', 'remote-notifications' ),
			'add_new'            => __( 'Add New', 'remote-notifications' ),
			'add_new_item'       => __( 'Add New Notification', 'remote-notifications' ),
			'edit_item'          => __( 'Edit Notification', 'remote-notifications' ),
			'new_item'           => __( 'New Notification', 'remote-notifications' ),
			'all_items'          => __( 'All Notifications', 'remote-notifications' ),
			'view_item'          => __( 'View Notification', 'remote-notifications' ),
			'search_items'       => __( 'Search Notifications', 'remote-notifications' ),
			'not_found'          => __( 'No Notification found', 'remote-notifications' ),
			'not_found_in_trash' => __( 'No Notification found in Trash', 'remote-notifications' ),
			'parent_item_colon'  => '',
			'menu_icon'          => 'dashicons-bell',
			'menu_name'          => __( 'Notifications', 'remote-notifications' ),
		);

		/* Post type settings */
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'custom-fields' ),
		);

		register_post_type( 'notification', $args );

	}

	/**
	 * Custom updated messages
	 *
	 * @param array $messages Post types messages array
	 *
	 * @return array Messages array with our post type messages added
	 */
	function updated_messages( $messages ) {

		global $post;

		$post_type_object = get_post_type_object( 'notification' );

		$messages['notification'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Notification updated.', 'remote-notifications' ),
			2  => __( 'Custom field updated.', 'remote-notifications' ),
			3  => __( 'Custom field deleted.', 'remote-notifications' ),
			4  => __( 'Notification updated.', 'remote-notifications' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Notification restored to revision from %s', 'remote-notifications' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Notification published.', 'remote-notifications' ),
			7  => __( 'Notification saved.', 'remote-notifications' ),
			8  => __( 'Notification submitted.', 'remote-notifications' ),
			9  => sprintf(
				__( 'Notification scheduled for: <strong>%1$s</strong>.', 'remote-notifications' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'remote-notifications' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Notification draft updated.', 'remote-notifications' ),
		);

		return $messages;

	}

	/**
	 * @param $post_id
	 * @param $post
	 * @param $update
	 * @return null
	 */
	function add_notification_preview() {
		if ( isset( $_GET['wpi_np'] ) && isset( $_GET['post'] ) && $_GET['wpi_np'] == 1 ) {
			$notice_id = absint(  $_GET['post'] );
			
			$notice_opts = get_post_meta( $notice_id, '_rn_settings',true);
			$notice_content = $notice_opts['notice'];
			$notice_icon = ($notice_opts['icon']['url']) ?'<img style="margin-right:15px;" width="80px" class="wpi-icon" src="'.$notice_opts['icon']['url'].'" />': '';
			$notice_content .= '<style>'.$notice_opts['css'].'.wpi-notice{display: flex; flex-direction: row; justify-content: flex-start; align-items: center;padding: 15px;}</style>';
			
			?>
			<div id="wpi-rdn-<?php echo esc_attr( $notice_id ); ?>" class="wpi-notice notice notice-<?php echo esc_attr($notice_opts['style']); ?> is-dismissible">
				<?php echo $notice_icon; ?>
				<div class="wpi-notice-content" style="max-width:900px;"><?php echo $notice_content; ?></div>
			</div>
			<?php
		}
	}

	/**
	 * Register the "Channels" taxonomy
	 *
	 * @since 1.0.0
	 */
	public function register_channel() {

		$labels = array(
			'name'              => _x( 'Channels', 'taxonomy general name', 'remote-notifications' ),
			'singular_name'     => _x( 'Channel', 'taxonomy singular name', 'remote-notifications' ),
			'search_items'      => __( 'Search Channels', 'remote-notifications' ),
			'all_items'         => __( 'All Channels', 'remote-notifications' ),
			'parent_item'       => __( 'Parent Channel', 'remote-notifications' ),
			'parent_item_colon' => __( 'Parent Channel:', 'remote-notifications' ),
			'edit_item'         => __( 'Edit Channel', 'remote-notifications' ),
			'update_item'       => __( 'Update Channel', 'remote-notifications' ),
			'add_new_item'      => __( 'Add New Channel', 'remote-notifications' ),
			'new_item_name'     => __( 'New Channel Name', 'remote-notifications' ),
			'menu_name'         => __( 'Channels', 'remote-notifications' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'channel' ),
		);

		register_taxonomy( 'rn-channel', array( 'notification' ), $args );

	}

	/**
	 * Register the post type taxonomy
	 *
	 * This taxonomy will be used to limit notices
	 * display on specific post types only.
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {

		$labels = array(
			'name'              => _x( 'Post Types Limitation', 'taxonomy general name', 'remote-notifications' ),
			'singular_name'     => _x( 'Post Type', 'taxonomy singular name', 'remote-notifications' ),
			'search_items'      => __( 'Search Post Types', 'remote-notifications' ),
			'all_items'         => __( 'All Post Types', 'remote-notifications' ),
			'parent_item'       => __( 'Parent Post Type', 'remote-notifications' ),
			'parent_item_colon' => __( 'Parent Post Type:', 'remote-notifications' ),
			'edit_item'         => __( 'Edit Post Type', 'remote-notifications' ),
			'update_item'       => __( 'Update Post Type', 'remote-notifications' ),
			'add_new_item'      => __( 'Add New Post Type', 'remote-notifications' ),
			'new_item_name'     => __( 'New Post Type Name', 'remote-notifications' ),
			'menu_name'         => __( 'Post Types', 'remote-notifications' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'post-type' ),
		);

		register_taxonomy( 'rn-pt', array( 'notification' ), $args );

	}

}