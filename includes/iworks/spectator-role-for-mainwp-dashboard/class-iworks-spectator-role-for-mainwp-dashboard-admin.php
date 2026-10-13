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

class iworks_spectator_role_for_mainwp_dashboard_admin extends iworks_spectator_role_for_mainwp_dashboard_base {

	public function __construct() {
		parent::__construct();
		add_action( 'admin_menu', array( $this, 'action_admin_menu_register_admin_menu' ) );
	}

	public function action_admin_menu_register_admin_menu() {
			add_menu_page(
				__( 'Sites', 'PLUGIN_TEXTDOMAIN' ),          // Page title
				__( 'Sites', 'PLUGIN_TEXTDOMAIN' ),                 // Menu title
				$this->spectator_role_name,                            // Capability required
				'mainwp-custom-sites-list',                  // Menu slug
				array( $this, 'render_sites_list_page' ),    // Callback method pointing to this instance
				'dashicons-admin-site',                      // Menu icon
				30                                           // Position in admin menu
			);
			add_submenu_page(
				'mainwp-custom-sites-list',                  // Parent slug
				__( 'Plugins', 'PLUGIN_TEXTDOMAIN' ),        // Page title
				__( 'Plugins', 'PLUGIN_TEXTDOMAIN' ),        // Menu title
				$this->spectator_role_name,                  // Capability required
				'mainwp-custom-plugins-list',                // Menu slug
				array( $this, 'render_plugins_list_page' ), // Callback method
				31                                           // Position
			);
	}


	public function render_sites_list_page() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mainwp_wp';

		$sites = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY name ASC" );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'MainWP Child Sites', 'textdomain' ) . '</h1>';
		echo '<table class="wp-list-table widefat fixed striped pages">';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Site Name', 'textdomain' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'URL', 'textdomain' ) . '</th>';
		echo '<th scope="col" class="manage-column" style="width: 150px;">' . esc_html__( 'Actions', 'textdomain' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( ! empty( $sites ) ) {
			foreach ( $sites as $site ) {
				$site_obj = (object) $site;

				// MainWP direct auto-login link
				$admin_url = admin_url( 'admin.php?page=SiteOpen&id=' . $site_obj->id );

				echo '<tr>';
				echo '<td><strong>' . esc_html( $site_obj->name ) . '</strong></td>';
				echo '<td><a href="' . esc_url( $site_obj->url ) . '" target="_blank">' . esc_html( $site_obj->url ) . '</a></td>';
				echo '<td><a class="button button-secondary" href="' . esc_url( $admin_url ) . '" target="_blank">' . esc_html__( 'Go to Admin', 'textdomain' ) . '</a></td>';
				echo '</tr>';
			}
		} else {
			$connect_url = admin_url( 'admin.php?page=managesites' );
			echo '<tr><td colspan="3">' . sprintf( __( 'No child sites found. Connect them via <a href="%s">MainWP</a>.', 'textdomain' ), esc_url( $connect_url ) ) . '</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}

	public function render_plugins_list_page() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mainwp_wp';

		$plugins_data = $wpdb->get_results( "SELECT name, url, plugins FROM {$table_name}" );

		l( $plugins_data );

		$plugins = array();
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
		echo '<h1>' . esc_html__( 'MainWP Installed Plugins', 'textdomain' ) . '</h1>';
		echo '<table class="wp-list-table widefat fixed striped pages">';
		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Name', 'textdomain' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'MU', 'textdomain' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Versions', 'textdomain' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Sites', 'textdomain' ) . '</th>';
		echo '<th scope="col" class="manage-column">' . esc_html__( 'Sites Count', 'textdomain' ) . '</th>';
		//      echo '<th scope="col" class="manage-column">' . esc_html__( 'Description', 'textdomain' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( ! empty( $plugins ) ) {
			foreach ( $plugins as $plugin_name => $plugin ) {
				echo '<tr>';
				echo '<td><strong>' . esc_html( $plugin_name ) . '</strong></td>';
				echo '<td>' . ( $plugin['mu'] ? 'Yes' : '&mdash;' ) . '</td>';
				echo '<td><strong>' . esc_html( implode( ', ', array_keys( $plugin['versions'] ) ) ) . '</strong></td>';
				echo '<td>' . esc_html( implode( ', ', $plugin['sites'] ) ) . '</td>';
				echo '<td>' . count( $plugin['sites'] ) . '</td>';
				//                echo '<td>' . wp_kses_post( $plugin['description'] ) . '</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="4">' . esc_html__( 'No plugins found.', 'textdomain' ) . '</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}
}
