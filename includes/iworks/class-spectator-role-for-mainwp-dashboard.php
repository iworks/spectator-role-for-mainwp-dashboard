<?php
/**
 * Main plugin class file.
 *
 * @package WordPress_Plugin_Stub
 * @author Marcin Pietrzak <marcin@iworks.pl>
 * @copyright 2026-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license GPL-3.0-or-later
 * @link https://iworks.pl/
 *
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard' ) ) {
	return;
}

require_once __DIR__ . '/class-spectator-role-for-mainwp-dashboard-base.php';

/**
 * Main plugin class.
 *
 * This class initializes the plugin and loads all necessary components.
 *
 * @since 1.0.0
 */
class iworks_spectator_role_for_mainwp_dashboard extends iworks_spectator_role_for_mainwp_dashboard_base {

	/**
	 * Plugin objects container.
	 *
	 * @since 1.0.0
	 * @var array $objects Array to store plugin objects.
	 */
	private array $objects = array();

	/**
	 * Class constructor.
	 *
	 * Initializes the plugin by setting up hooks and loading required files.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->version = 'PLUGIN_VERSION';
		/**
		 * WordPress Hooks
		 */
		add_action( 'init', array( $this, 'action_init_settings' ) );
		/**
		 * post types
		 */
		$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-posttypes.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
			new iworks_wordpress_plugin_posttypes();
		}
		/**
		 * load github class
		 */
		$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-github.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
			new iworks_spectator_role_for_mainwp_dashboard_github();
		}
		/**
		 * admin
		 */
		if ( is_admin() ) {
			$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-wp-admin.php';
			if ( is_file( $filename ) ) {
				include_once $filename;
				new iworks_spectator_role_for_mainwp_dashboard_wp_admin();
			}
		}
		/**
		 * is active?
		 */
		add_filter( 'spectator-role-for-mainwp-dashboard/is_active', '__return_true' );
	}

	/**
	 * Initialize plugin settings and assets.
	 *
	 * Handles the initialization of plugin settings and enqueues frontend assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_init_settings() {
		/**
		 * options
		 */
		if ( is_admin() ) {
		} else {
			$file = 'assets/styles/spectator-role-for-mainwp-dashboard-frontend' . $this->dev . '.css';
			wp_enqueue_style( 'spectator-role-for-mainwp-dashboard', plugins_url( $file, $this->base ), array(), $this->get_version( $file ) );
		}
	}

	/**
	 * Plugin activation hook.
	 *
	 * Handles database installation and option initialization
	 * when the plugin is activated.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_activation_hook() {
		$this->db_install();
		$this->check_option_object();
		$this->options->activate();
		do_action( 'iworks/spectator-role-for-mainwp-dashboard/register_activation_hook' );
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * Handles cleanup tasks when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_deactivation_hook() {
		$this->check_option_object();
		$this->options->deactivate();
		do_action( 'iworks/spectator-role-for-mainwp-dashboard/register_deactivation_hook' );
	}

	/**
	 * Database installation method.
	 *
	 * Handles the creation of required database tables.
	 * Currently empty as it's a stub implementation.
	 *
	 * @since 1.0.0
	 * @return void
	 * @todo Implement database table creation if needed.
	 */
	private function db_install() {
	}
}
