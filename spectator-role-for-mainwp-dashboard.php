<?php
/*
Plugin Name: Spectator Role for MainWP Dashboard
Text Domain: spectator-role-for-mainwp-dashboard
Requires Plugins: mainwp
Plugin URI: PLUGIN_URI
Description: PLUGIN_TAGLINE
Version: PLUGIN_VERSION
Author: Marcin Pietrzak
Author URI: http://iworks.pl/
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Copyright 2026-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 3, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Load the main plugin class if it doesn't exist
 * This is the core class that handles all plugin functionality
 */
if ( ! class_exists( 'iworks_spectator_role_for_mainwp_dashboard' ) ) {
	// Load the main plugin class from the includes directory
	require_once __DIR__ . '/includes/iworks/class-spectator-role-for-mainwp-dashboard.php';
}

// Initialize the main plugin class
$iworks_spectator_role_for_mainwp_dashboard = new iworks_spectator_role_for_mainwp_dashboard();

/**
 * Register plugin activation and deactivation hooks
 */
// Register activation hook to run when plugin is activated
register_activation_hook( __FILE__, array( $iworks_spectator_role_for_mainwp_dashboard, 'register_activation_hook' ) );
// Register deactivation hook to run when plugin is deactivated
register_deactivation_hook( __FILE__, array( $iworks_spectator_role_for_mainwp_dashboard, 'register_deactivation_hook' ) );
