<?php
/*

Copyright 2026-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard_admin' ) ) {
	return;
}

/**
 * Admin class for Spectator Role for MainWP Dashboard.
 *
 * Handles admin menu registration and rendering of sites, plugins, and themes list pages.
 *
 * @since 1.0.0
 * @package IWorks\Spectator_Role_For_MainWP_Dashboard
 */
class iworks_spectator_role_for_mainwp_dashboard_admin extends iworks_spectator_role_for_mainwp_dashboard_base {

	/**
	 * Constructor.
	 *
	 * Initializes the admin class by calling parent constructor and registering admin menu hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * load sites list table class
		 */
		$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-sites-table.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
		}
		$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-plugins-table.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
		}
		$filename = $this->includes_directory . '/class-iworks-spectator-role-for-mainwp-dashboard-themes-table.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
		}
		add_action( 'admin_menu', array( $this, 'action_admin_menu_register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'action_admin_init_maybe_export_csv' ) );
	}

	/**
	 * Handle the CSV export request on admin init.
	 *
	 * Delegates to the sites, plugins, and themes list tables.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_admin_init_maybe_export_csv() {
		$sites_table = new iworks_spectator_role_for_mainwp_dashboard_sites_table();
		$sites_table->maybe_handle_export_csv();
		$plugins_table = new iworks_spectator_role_for_mainwp_dashboard_plugins_table();
		$plugins_table->maybe_handle_export_csv();
		$themes_table = new iworks_spectator_role_for_mainwp_dashboard_themes_table();
		$themes_table->maybe_handle_export_csv();
	}

	/**
	 * Register admin menu pages.
	 *
	 * Adds main menu page for Sites and submenu pages for Plugins and Themes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_admin_menu_register_admin_menu() {
		$page_hook = add_menu_page(
			__( 'Sites', 'spectator-role-for-mainwp-dashboard' ),      // Page title
			__( 'Sites', 'spectator-role-for-mainwp-dashboard' ),      // Menu title
			'read',                                                    // Capability required
			'mainwp-custom-sites-list',                                // Menu slug
			array( $this, 'render_sites_list_page' ),                  // Callback method pointing to this instance
			'dashicons-admin-site',                                    // Menu icon
			30                                                         // Position in admin menu
		);
		add_action( "load-{$page_hook}", array( $this, 'action_load_register_sites_columns' ) );
		$page_hook = add_submenu_page(
			'mainwp-custom-sites-list',                                // Parent slug
			__( 'Plugins', 'spectator-role-for-mainwp-dashboard' ),    // Page title
			__( 'Plugins', 'spectator-role-for-mainwp-dashboard' ),    // Menu title
			'read',                                                    // Capability required
			'mainwp-custom-plugins-list',                              // Menu slug
			array( $this, 'render_plugins_list_page' )                 // Callback method
		);
		add_action( "load-{$page_hook}", array( $this, 'action_load_register_plugins_columns' ) );
		$page_hook = add_submenu_page(
			'mainwp-custom-sites-list',                                // Parent slug
			__( 'Themes', 'spectator-role-for-mainwp-dashboard' ),     // Page title
			__( 'Themes', 'spectator-role-for-mainwp-dashboard' ),     // Menu title
			'read',                                                    // Capability required
			'mainwp-custom-themes-list',                               // Menu slug
			array( $this, 'render_themes_list_page' )                  // Callback method
		);
		add_action( "load-{$page_hook}", array( $this, 'action_load_register_themes_columns' ) );
	}

	/**
	 * Register sites list columns on the current screen.
	 *
	 * WP_List_Table resolves columns via get_column_headers(), which reads
	 * the "manage_{screen}_columns" filter. Registering the columns here
	 * also enables the column checkboxes in the Screen Options tab, so
	 * users can choose which columns are visible.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_load_register_sites_columns() {
		$this->register_list_table_columns( new iworks_spectator_role_for_mainwp_dashboard_sites_table() );
	}

	/**
	 * Register plugins list columns on the current screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_load_register_plugins_columns() {
		$this->register_list_table_columns( new iworks_spectator_role_for_mainwp_dashboard_plugins_table() );
	}

	/**
	 * Register themes list columns on the current screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_load_register_themes_columns() {
		$this->register_list_table_columns( new iworks_spectator_role_for_mainwp_dashboard_themes_table() );
	}

	/**
	 * Register list table columns via the "manage_{screen}_columns" filter.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_List_Table $table List table instance.
	 * @return void
	 */
	private function register_list_table_columns( $table ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		add_filter( "manage_{$screen->id}_columns", array( $table, 'get_columns' ) );
	}


	/**
	 * Render sites list page.
	 *
	 * Displays a table of all MainWP child sites with their URLs and action links,
	 * powered by WP_List_Table with pagination, search, and sortable columns.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_sites_list_page() {
		$sites_table = new iworks_spectator_role_for_mainwp_dashboard_sites_table();
		$sites_table->prepare_items();
		echo '<div class="wrap">';
		printf(
			'<h1 class="wp-heading-inline">%s</h1><a href="%s" class="page-title-action">%s</a><hr class="wp-header-end" />',
			esc_html__( 'MainWP Child Sites', 'spectator-role-for-mainwp-dashboard' ),
			esc_url( $sites_table->get_export_url() ),
			esc_html__( 'Export CSV', 'spectator-role-for-mainwp-dashboard' )
		);
		$sites_table->views();
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="mainwp-custom-sites-list" />';
		$tag_id = $sites_table->get_current_tag_id();
		if ( 0 < $tag_id ) {
			printf( '<input type="hidden" name="tag" value="%d" />', $tag_id );
		}
		$sites_table->search_box( __( 'Search sites', 'spectator-role-for-mainwp-dashboard' ), 'mainwp-custom-sites-list' );
		$sites_table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render plugins list page.
	 *
	 * Displays a table of all installed plugins across MainWP child sites,
	 * grouped by plugin name with version and site information, powered by
	 * WP_List_Table with pagination, search, MU views, and CSV export.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_plugins_list_page() {
		$plugins_table = new iworks_spectator_role_for_mainwp_dashboard_plugins_table();
		$plugins_table->prepare_items();
		echo '<div class="wrap">';
		printf(
			'<h1 class="wp-heading-inline">%s</h1><a href="%s" class="page-title-action">%s</a><hr class="wp-header-end" />',
			esc_html__( 'MainWP Installed Plugins', 'spectator-role-for-mainwp-dashboard' ),
			esc_url( $plugins_table->get_export_url() ),
			esc_html__( 'Export CSV', 'spectator-role-for-mainwp-dashboard' )
		);
		$plugins_table->views();
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="mainwp-custom-plugins-list" />';
		$mu = $plugins_table->get_current_mu();
		if ( 'all' !== $mu ) {
			printf( '<input type="hidden" name="mu" value="%s" />', esc_attr( $mu ) );
		}
		$plugins_table->search_box( __( 'Search plugins', 'spectator-role-for-mainwp-dashboard' ), 'mainwp-custom-plugins-list' );
		$plugins_table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render themes list page.
	 *
	 * Displays a table of all installed themes across MainWP child sites,
	 * grouped by theme name with version and site information, powered by
	 * WP_List_Table with pagination, search, status views, and CSV export.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_themes_list_page() {
		$themes_table = new iworks_spectator_role_for_mainwp_dashboard_themes_table();
		$themes_table->prepare_items();
		echo '<div class="wrap">';
		printf(
			'<h1 class="wp-heading-inline">%s</h1><a href="%s" class="page-title-action">%s</a><hr class="wp-header-end" />',
			esc_html__( 'MainWP Installed Themes', 'spectator-role-for-mainwp-dashboard' ),
			esc_url( $themes_table->get_export_url() ),
			esc_html__( 'Export CSV', 'spectator-role-for-mainwp-dashboard' )
		);
		$themes_table->views();
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="mainwp-custom-themes-list" />';
		$status = $themes_table->get_current_status();
		if ( 'all' !== $status ) {
			printf( '<input type="hidden" name="status" value="%s" />', esc_attr( $status ) );
		}
		$themes_table->search_box( __( 'Search themes', 'spectator-role-for-mainwp-dashboard' ), 'mainwp-custom-themes-list' );
		$themes_table->display();
		echo '</form>';
		echo '</div>';
	}
}
