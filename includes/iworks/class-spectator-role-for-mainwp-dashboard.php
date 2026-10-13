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
	 * Initializes the plugin by setting up hooks and loading required files.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * WordPress Hooks
		 */
		add_action( 'init', array( $this, 'action_init_settings' ) );
		add_action( 'init', array( $this, 'create_spectator_role' ) );
		add_action( 'profile_update', array( $this, 'action_profile_update_add_administrator_role' ) );

		/**
		 * load github class
		 */
		$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-github.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
			new iworks_spectator_role_for_mainwp_dashboard_github();
		}
		/**
		 * is active?
		 */
		add_filter( 'spectator-role-for-mainwp-dashboard/is_active', '__return_true' );
		/**
		 * MainWP Dashboard integration
		 */
		$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-admin.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
			new iworks_spectator_role_for_mainwp_dashboard_admin();
		}
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
	}

	/**
	 * Create spectator role.
	 *
	 * Creates the spectator role with appropriate read-only capabilities
	 * for viewing the MainWP dashboard without making changes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function create_spectator_role() {
		$role_name = $this->spectator_role_name;

		// Check if role already exists to avoid overwriting
		$spectator_role = get_role( $this->spectator_role_name );
		if ( $spectator_role ) {
			// Grant read capability to spectator role
			$spectator_role->add_cap( 'read' );
			return;
		}

		// Define capabilities for spectator role (read-only access)
		$capabilities = array(
			'read' => true,
		);

		// Add the role
		add_role(
			$role_name,
			esc_html__( 'Spectator (MainWP)', 'spectator-role-for-mainwp-dashboard' ),
			$capabilities
		);
	}

	/**
	 * Plugin activation hook.
	 *
	 * Handles database installation when the plugin is activated.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_activation_hook() {
		$this->db_install();
		do_action( 'iworks/spectator-role-for-mainwp-dashboard/register_activation_hook' );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
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

	/**
	 * Add administrator role to user when their profile is updated.
	 *
	 * @param int $user_id The ID of the user whose profile was updated.
	 * @return void
	 */
	public function action_profile_update_add_administrator_role( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( $user && in_array( $this->spectator_role_name, $user->roles ) ) {
			$user->add_role( 'administrator' );
		}
	}

	public function grant_roles() {
	}
}
