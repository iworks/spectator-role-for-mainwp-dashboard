<?php
/**
 * spectator-role-for-mainwp-dashboard - Admin Class
 *
 * Handles all WordPress admin-specific functionality for the plugin.
 *
 * @package WordPress_Plugin_Stub
 * @author  Marcin Pietrzak <marcin@iworks.pl>
 * @license GPL-2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard_wp_admin' ) ) {
	return;
}

require_once dirname( __DIR__ ) . '/class-spectator-role-for-mainwp-dashboard-base.php';

/**
 * Admin functionality for spectator-role-for-mainwp-dashboard.
 *
 * This class handles all admin-specific functionality including:
 * - Plugin settings and options management
 * - Admin assets registration
 * - Plugin meta links
 * - Admin interface rendering
 *
 * @since 1.0.0
 */
class iworks_spectator_role_for_mainwp_dashboard_wp_admin extends iworks_spectator_role_for_mainwp_dashboard_base {

	/**
	 * The capability required to access plugin admin features.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $capability = 'manage_options';

	/**
	 * Initialize the admin class.
	 *
	 * Sets up the required capability and registers admin hooks.
	 *
	 * @since 1.0.0
	 * @see iworks_spectator_role_for_mainwp_dashboard_base::__construct()
	 */
	public function __construct() {
		parent::__construct();
		$this->capability = apply_filters( 'iworks_spectator_role_for_mainwp_dashboard_capability', $this->capability );
		/**
		 * WordPress Hooks
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts_register_assets' ), 0 );
		add_action( 'wp_loaded', array( $this, 'action_wp_loaded_init_options' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		/**
		 * iWorks Options Hooks
		 */
		add_filter( 'spectator-role-for-mainwp-dashboard/etc/config/metaboxes', array( $this, 'filter_iworks_options_add_meta_boxes' ) );
	}

	/**
	 * Add meta boxes to the iWorks Options page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $metaboxes The options array.
	 *
	 * @return array The modified options array.
	 */
	public function filter_iworks_options_add_meta_boxes( $metaboxes ) {
		$metaboxes['assistance'] = array(
			'title'    => __( 'Have a question or need help?', 'spectator-role-for-mainwp-dashboard' ),
			'callback' => array( $this, 'need_assistance' ),
			'context'  => 'side',
			'priority' => 'core',
		);
		$metaboxes['love']       = array(
			'title'    => __( 'Enjoying this plugin?', 'spectator-role-for-mainwp-dashboard' ),
			'callback' => array( $this, 'loved_this_plugin' ),
			'context'  => 'side',
			'priority' => 'core',
		);
		return $metaboxes;
	}
	/**
	 * Display plugin appreciation links.
	 *
	 * Outputs HTML for the "Love this plugin" section, including links to rate the plugin
	 * and share it with others.
	 *
	 * @since 1.0.0
	 * @param object $iworks_orphan The main plugin instance.
	 * @return void
	 */
	public function loved_this_plugin() {
		$content = apply_filters( 'iworks_rate_love', '', 'spectator-role-for-mainwp-dashboard' );
		if ( ! empty( $content ) ) {
			echo wp_kses_post( $content );
			return;
		}
		?>
<p><?php esc_html_e( 'Help others discover it—share the link with your friends and community!', 'spectator-role-for-mainwp-dashboard' ); ?></p>
<ul>
	<li><a target="_blank" href="<?php echo esc_url( _x( 'https://wordpress.org/support/plugin/spectator-role-for-mainwp-dashboard/reviews/#new-post', 'link to add new review page on WordPress.org', 'spectator-role-for-mainwp-dashboard' ) ); ?>"><?php esc_html_e( 'Give it a five stars on WordPress.org', 'spectator-role-for-mainwp-dashboard' ); ?></a></li>
	<li><a target="_blank" href="<?php echo esc_url( _x( 'https://wordpress.org/plugins/spectator-role-for-mainwp-dashboard/', 'plugin home page on WordPress.org', 'spectator-role-for-mainwp-dashboard' ) ); ?>"><?php esc_html_e( 'Link to it so others can easily find it', 'spectator-role-for-mainwp-dashboard' ); ?></a></li>
</ul>
		<?php
	}
	/**
	 * Display assistance information.
	 *
	 * Outputs HTML for the "Need Assistance" section, including support links.
	 *
	 * @since 1.0.0
	 * @param object $iworks_orphans The main plugin instance.
	 * @return void
	 */
	public function need_assistance() {
		$content = apply_filters( 'iworks_rate_assistance', '', 'spectator-role-for-mainwp-dashboard' );
		if ( ! empty( $content ) ) {
			echo wp_kses_post( $content );
			return;
		}

		?>
<p><?php esc_html_e( 'We’re here for you! Send us a message and we’ll get back to you as soon as possible.', 'spectator-role-for-mainwp-dashboard' ); ?></p>
<ul>
	<li><a target="_blank" href="<?php echo esc_url( _x( 'https://wordpress.org/support/plugin/spectator-role-for-mainwp-dashboard/', 'link to support forum on WordPress.org', 'spectator-role-for-mainwp-dashboard' ) ); ?>"><?php esc_html_e( 'WordPress Help Forum', 'spectator-role-for-mainwp-dashboard' ); ?></a></li>
</ul>
		<?php
	}
	/**
	 * Initialize plugin options during WordPress loaded hook.
	 *
	 * This method is called when WordPress is fully loaded. It performs the following actions:
	 * 1. Ensures the options object is properly initialized
	 * 2. Initializes the plugin options through the options object
	 *
	 * @hook wp_loaded
	 * @since 1.0.0
	 * @see iworks_spectator_role_for_mainwp_dashboard_base::check_option_object()
	 * @see iworks_options::options_init()
	 *
	 * @return void
	 */
	public function action_wp_loaded_init_options() {
		$this->check_option_object();
		$this->options->options_init();
	}

	/**
	 * Register admin assets.
	 *
	 * Registers the required JavaScript files for the admin interface.
	 *
	 * @hook admin_enqueue_scripts
	 * @since 1.1.0
	 */
	public function action_admin_enqueue_scripts_register_assets() {
		$name = $this->options->get_option_name( 'admin' );
		$file = '/assets/scripts/spectator-role-for-mainwp-dashboard-admin' . $this->dev . '.js';
		wp_register_script(
			$name,
			plugins_url( $file, $this->plugin_file_path ),
			array(),
			$this->get_version( $file ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Filters the array of row meta for the plugin in the Plugins list table.
	 *
	 * This method adds custom links to the plugin's row in the WordPress admin Plugins page.
	 * It adds:
	 * 1. A 'Settings' link (for non-multisite installations with proper capabilities)
	 * 2. A 'Donate' link (only in the free version)
	 * 3. A 'GitHub' link
	 *
	 * @hook plugin_row_meta
	 * @since 1.0.0
	 *
	 * @param string[] $links An array of the plugin's metadata, including the version, author,
	 *                       author URI, and plugin name.
	 * @param string  $file  Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[] Array of plugin row links with our custom links added.
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $this->dir . '/spectator-role-for-mainwp-dashboard.php' == $file ) {
			if ( ! is_multisite() && current_user_can( $this->capability ) ) {
				$links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						add_query_arg(
							array(
								'page' => $this->dir . '/admin/index.php',
							),
							admin_url( 'admin.php' )
						)
					),
					esc_html__( 'Settings', 'spectator-role-for-mainwp-dashboard' )
				);
			}
			/* start:free */
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'utm_source' => 'spectator-role-for-mainwp-dashboard',
							'utm_medium' => 'plugin-row-donate-link',
						),
						'https://ko-fi.com/iworks'
					)
				),
				esc_html__( 'Donate', 'spectator-role-for-mainwp-dashboard' )
			);
			/* end:free */
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'utm_source' => 'spectator-role-for-mainwp-dashboard',
							'utm_medium' => 'plugin-row-donate-link',
						),
						'https://github.com/iworks.pl/spectator-role-for-mainwp-dashboard'
					)
				),
				esc_html__( 'GitHub', 'spectator-role-for-mainwp-dashboard' )
			);
		}
		return $links;
	}
}
