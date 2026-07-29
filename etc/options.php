<?php
/**
 * spectator-role-for-mainwp-dashboard Options Configuration
 *
 * This file contains the configuration options for the spectator-role-for-mainwp-dashboard.
 * It defines the structure of the plugin's options and settings pages.
 *
 * @package    iWorks
 * @subpackage spectator-role-for-mainwp-dashboard
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @copyright  2026-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get plugin options configuration
 *
 * Returns an array containing the configuration for the plugin's options pages
 * and settings.
 *
 * @return array Array of options configuration
 * @since 1.0.0
 */
function iworks_spectator_role_for_mainwp_dashboard_options() {
	/**
	 * Initialize empty options array
	 */
	$options = array();

	/**
	 * Parent page placeholder (uncomment and set as needed)
	 */
	$parent = null;

	/**
	 * Main settings configuration
	 *
	 * Defines the structure of the main options page including:
	 * - Version number
	 * - Page title
	 * - Menu type
	 * - Options array
	 * - Metaboxes array
	 * - Subpages array
	 */
	$options['index'] = array(
		/**
		 * Current version of the options configuration
		 */
		'version'    => '0.0',

		/**
		 * Title of the options page
		 */
		'page_title' => __( 'spectator-role-for-mainwp-dashboard', 'spectator-role-for-mainwp-dashboard' ),

/**
 * Menu type for the options page
 *
 * Possible values:
 * - 'options'      - Add as a top-level menu item (default)
 * - 'submenu'      - Add as a submenu item (requires 'parent' to be set)
 * - 'management'   - Add under Tools menu
 * - 'theme'        - Add under Appearance menu
 * - 'posts'        - Add under Posts menu
 * - 'pages'        - Add under Pages menu
 * - 'users'        - Add under Users menu (or Profile for single)
 * - 'plugins'      - Add under Plugins menu
 * - 'comments'     - Add under Comments menu
 * - 'dashboard'    - Add under Dashboard menu
 * - 'settings'     - Add under Settings menu
 * - 'media'        - Add under Media menu
 * - 'custom'       - Custom menu position (requires 'menu_slug' to be set)
 */
'menu'       => 'options',

/**
 * Parent page for submenu items
 *
 * Required when 'menu' is set to 'submenu' or when nesting under another menu.
 * Can be one of the following:
 * - The file name of a standard WordPress admin page (e.g., 'edit.php' for Posts)
 * - The value of 'menu_slug' from another options page
 * - The plugin file if you want to nest under a plugin's main menu
 *
 * Common WordPress admin page values:
 * - 'index.php'                  - Dashboard
 * - 'edit.php'                   - Posts
 * - 'upload.php'                 - Media
 * - 'edit.php?post_type=page'    - Pages
 * - 'edit-comments.php'          - Comments
 * - 'themes.php'                 - Appearance
 * - 'plugins.php'                - Plugins
 * - 'users.php'                  - Users
 * - 'tools.php'                  - Tools
 * - 'options-general.php'        - Settings
 * - 'options-general.php?page=YOUR_PAGE' - Custom settings page
 *
 * Example for nesting under a plugin's main menu:
 * 'parent' => 'my-plugin-slug',
 */
		'parent' => $parent,

		/**
		 * Use tabs for options page
		 *
		 * possible values:
		 * - true - use tabs
		 * - false - options will be shown flat on one screen
		 */
		'use_tabs' => false,

		/**
		 * Array of options fields
		 */
		'options'    => apply_filters(
			'spectator-role-for-mainwp-dashboard/etc/config/options',
			array(
				array(
					'type'  => 'heading',
					'label' => __( 'Example Header', 'spectator-role-for-mainwp-dashboard' ),
					'since' => '1.0.0',
				),
				array(
					'name'              => 'example_text',
					'type'              => 'text',
					'th'                => __( 'Example Text', 'spectator-role-for-mainwp-dashboard' ),
					'description'       => __( 'Enter some text.', 'spectator-role-for-mainwp-dashboard' ),
					'classes'           => array( 'small-text' ),
					'sanitize_callback' => 'esc_html',
					'since'             => '1.0.0',
				),
				array(
					'name'              => 'example_textarea',
					'type'              => 'textarea',
					'th'                => __( 'Example Textarea', 'spectator-role-for-mainwp-dashboard' ),
					'description'       => __( 'Enter some text.', 'spectator-role-for-mainwp-dashboard' ),
					'classes'           => array( 'code', 'large-text' ),
					'sanitize_callback' => 'wp_kses_post',
					'since'             => '1.0.0',
					'rows'              => 5,
				),
			)
		),

		/**
		 * Array of metaboxes
		 */
		'metaboxes'  => apply_filters( 'spectator-role-for-mainwp-dashboard/etc/config/metaboxes', array() ),

		/**
		 * Array of subpages
		 */
		'pages'      => apply_filters( 'spectator-role-for-mainwp-dashboard/etc/config/pages', array() ),
	);

	/**
	 * Return the complete options configuration
	 */
	return $options;
}
