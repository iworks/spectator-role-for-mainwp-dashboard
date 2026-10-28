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
		add_action( 'admin_menu', array( $this, 'action_admin_menu_register_admin_menu' ) );
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
			add_menu_page(
				__( 'Sites', 'spectator-role-for-mainwp-dashboard' ),          // Page title
				__( 'Sites', 'spectator-role-for-mainwp-dashboard' ),                 // Menu title
				'read',                            // Capability required
				'mainwp-custom-sites-list',                  // Menu slug
				array( $this, 'render_sites_list_page' ),    // Callback method pointing to this instance
				'dashicons-admin-site',                      // Menu icon
				30                                           // Position in admin menu
			);
			add_submenu_page(
				'mainwp-custom-sites-list',                  // Parent slug
				__( 'Plugins', 'spectator-role-for-mainwp-dashboard' ),        // Page title
				__( 'Plugins', 'spectator-role-for-mainwp-dashboard' ),        // Menu title
				'read',                  // Capability required
				'mainwp-custom-plugins-list',                // Menu slug
				array( $this, 'render_plugins_list_page' ), // Callback method
			);
			add_submenu_page(
				'mainwp-custom-sites-list',                  // Parent slug
				__( 'Themes', 'spectator-role-for-mainwp-dashboard' ),        // Page title
				__( 'Themes', 'spectator-role-for-mainwp-dashboard' ),        // Menu title
				'read',                  // Capability required
				'mainwp-custom-themes-list',                // Menu slug
				array( $this, 'render_themes_list_page' ), // Callback method
			);
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
		echo '<h1>' . esc_html__( 'MainWP Child Sites', 'spectator-role-for-mainwp-dashboard' ) . '</h1>';
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
	 * grouped by plugin name with version and site information.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_plugins_list_page() {
		global $wpdb;
		$table_name   = $wpdb->prefix . 'mainwp_wp';
		$plugins_data = $wpdb->get_results( "SELECT name, url, plugins FROM {$table_name}" );
		$plugins      = array();
		foreach ( $plugins_data as $plugin_data ) {
			$site_plugins = json_decode( $plugin_data->plugins, true );
			foreach ( $site_plugins as $site_plugin ) {
				if ( ! isset( $plugins[ $site_plugin['name'] ] ) ) {
					$plugins[ $site_plugin['name'] ]             = $site_plugin;
					$plugins[ $site_plugin['name'] ]['sites']    = array();
					$plugins[ $site_plugin['name'] ]['versions'] = array();

				}
				/*
				 * sites
				 */
				$plugins[ $site_plugin['name'] ]['sites'][ $plugin_data->url ] = $plugin_data->name;
				/*
				 * versions
				 */
				if ( ! isset( $plugins[ $site_plugin['name'] ]['versions'][ $site_plugin['version'] ] ) ) {
					$plugins[ $site_plugin['name'] ]['versions'][ $site_plugin['version'] ] = array();
				}
				$plugins[ $site_plugin['name'] ]['versions'][ $site_plugin['version'] ]['sites'][] = $plugin_data->url;
			}
		}

		asort( $plugins );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'MainWP Installed Plugins', 'spectator-role-for-mainwp-dashboard' ) . '</h1>';
		echo '<table class="wp-list-table widefat fixed striped pages">';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Name', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'MU', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Versions', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Sites', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Sites Count', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( ! empty( $plugins ) ) {
			foreach ( $plugins as $plugin_name => $plugin ) {
				echo '<tr id="plugin-' . esc_attr( dirname( $plugin['slug'] ) ) . '">';
				echo '<td><strong>' . esc_html( $plugin_name ) . '</strong></td>';
				echo '<td>' . ( isset( $plugin['mu'] ) && $plugin['mu'] ? esc_html__( 'Yes', 'spectator-role-for-mainwp-dashboard' ) : '&mdash;' ) . '</td>';
				echo '<td><strong>' . esc_html( implode( ', ', array_keys( $plugin['versions'] ) ) ) . '</strong></td>';
				echo '<td>';
				echo $this->get_sites_list( $plugin );
				echo '</td>';
				echo '<td>' . count( $plugin['sites'] ) . '</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="4">' . esc_html__( 'No plugins found.', 'spectator-role-for-mainwp-dashboard' ) . '</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}

	/**
	 * Get sites list as HTML for plugins.
	 *
	 * Generates an HTML list of sites where a plugin is installed,
	 * grouped by version if multiple versions exist.
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugin Plugin data array containing sites and versions.
	 * @return string HTML list of sites with links.
	 */
	private function get_sites_list( $plugin ) {
		$output = '';
		foreach ( $plugin['versions'] as $version => $data ) {
			if ( 1 < count( $plugin['versions'] ) ) {
				$output .= '<strong>' . esc_html( $version ) . '</strong>';
			}
			$output .= '<ul>';
			foreach ( $data['sites'] as $site_url ) {
				$output .= '<li>';
				$output .= '<a href="' . esc_url( $site_url ) . '/wp-admin/" target="_blank">' . esc_html( $plugin['sites'][ $site_url ] ) . '</a>';
				$output .= '</li>';
			}
			$output .= '</ul>';
		}
		return $output;
	}

	/**
	 * Render themes list page.
	 *
	 * Displays a table of all installed themes across MainWP child sites,
	 * grouped by theme name with version and site information.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_themes_list_page() {
		global $wpdb;
		$table_name  = $wpdb->prefix . 'mainwp_wp';
		$themes_data = $wpdb->get_results( "SELECT name, url, themes FROM {$table_name}" );
		$themes      = array();
		foreach ( $themes_data as $theme_data ) {
			$site_themes = json_decode( $theme_data->themes, true );
			foreach ( $site_themes as $site_theme ) {
				if ( ! isset( $themes[ $site_theme['name'] ] ) ) {
					$themes[ $site_theme['name'] ]             = $site_theme;
					$themes[ $site_theme['name'] ]['sites']    = array();
					$themes[ $site_theme['name'] ]['versions'] = array();

				}
				/*
				 * sites
				 */
				$themes[ $site_theme['name'] ]['sites'][ $theme_data->url ] = $theme_data->name;
				/*
				 * versions
				 */
				if ( ! isset( $themes[ $site_theme['name'] ]['versions'][ $site_theme['version'] ] ) ) {
					$themes[ $site_theme['name'] ]['versions'][ $site_theme['version'] ] = array();
				}
				$themes[ $site_theme['name'] ]['versions'][ $site_theme['version'] ]['sites'][] = $theme_data->url;
			}
		}

		asort( $themes );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'MainWP Installed Themes', 'spectator-role-for-mainwp-dashboard' ) . '</h1>';
		echo '<table class="wp-list-table widefat fixed striped pages">';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Name', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Versions', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Sites', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Sites Count', 'spectator-role-for-mainwp-dashboard' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( ! empty( $themes ) ) {
			foreach ( $themes as $theme_name => $theme ) {
				echo '<tr id="theme-' . esc_attr( $theme['slug'] ) . '">';
				echo '<td><strong>' . esc_html( $theme_name ) . '</strong></td>';
				echo '<td><strong>' . esc_html( implode( ', ', array_keys( $theme['versions'] ) ) ) . '</strong></td>';
				echo '<td>';
				echo $this->get_theme_sites_list( $theme );
				echo '</td>';
				echo '<td>' . count( $theme['sites'] ) . '</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="4">' . esc_html__( 'No themes found.', 'spectator-role-for-mainwp-dashboard' ) . '</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}

	/**
	 * Get sites list as HTML for themes.
	 *
	 * Generates an HTML list of sites where a theme is installed,
	 * grouped by version if multiple versions exist.
	 *
	 * @since 1.0.0
	 *
	 * @param array $theme Theme data array containing sites and versions.
	 * @return string HTML list of sites with links.
	 */
	private function get_theme_sites_list( $theme ) {
		$output = '';
		foreach ( $theme['versions'] as $version => $data ) {
			if ( 1 < count( $theme['versions'] ) ) {
				$output .= '<strong>' . esc_html( $version ) . '</strong>';
			}
			$output .= '<ul>';
			foreach ( $data['sites'] as $site_url ) {
				$output .= '<li>';
				$output .= '<a href="' . esc_url( $site_url ) . '/wp-admin/" target="_blank">' . esc_html( $theme['sites'][ $site_url ] ) . '</a>';
				$output .= '</li>';
			}
			$output .= '</ul>';
		}
		return $output;
	}
}
