<?php
/**
 * iWorks spectator-role-for-mainwp-dashboard Base Class
 *
 * This is the base class for the spectator-role-for-mainwp-dashboard, providing
 * common functionality and properties for the plugin.
 *
 * @package    IWorks\WordPressPluginStub
 * @subpackage Includes
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @copyright  2026-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 * @version    1.0.0
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prevent multiple class definitions.
 *
 * Ensures that the iworks_spectator_role_for_mainwp_dashboard_base class is only defined once
 * to prevent fatal errors in case the file is loaded multiple times.
 *
 * @since 1.0.0
 */
if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard_base' ) ) {
	return;
}

/**
 * iWorks spectator-role-for-mainwp-dashboard Base Class.
 *
 * This class provides the foundation for the spectator-role-for-mainwp-dashboard,
 * offering essential properties and methods used throughout the plugin.
 *
 * @since 1.0.0
 * @package IWorks\WordPressPluginStub
 */
class iworks_spectator_role_for_mainwp_dashboard_base {

	/**
	 * Developer mode flag.
	 *
	 * Determines whether the plugin is running in developer mode,
	 * which affects asset minification and caching.
	 *
	 * @since 1.0.0
	 * @var   bool $dev Whether developer mode is enabled
	 */
	protected $dev;

	/**
	 * Meta data prefix.
	 *
	 * Prefix used for all post meta keys to avoid conflicts
	 * with other plugins and WordPress core.
	 *
	 * @since 1.0.0
	 * @var   string $meta_prefix Prefix for meta data keys
	 */
	protected $meta_prefix = '_iw';

	/**
	 * Base directory path.
	 *
	 * Absolute path to the plugin's base directory.
	 *
	 * @since 1.0.0
	 * @var   string $base Absolute path to the plugin directory
	 */
	protected $base;

	/**
	 * Directory path.
	 *
	 * Plugin directory path relative to WordPress plugins directory.
	 *
	 * @since 1.0.0
	 * @var   string $dir Plugin directory path
	 */
	protected $dir;

	/**
	 * URL path.
	 *
	 * Full URL to the plugin directory.
	 *
	 * @since 1.0.0
	 * @var   string $url Plugin URL path
	 */
	protected $url;

	/**
	 * Plugin file name.
	 *
	 * Base name of the main plugin file.
	 *
	 * @since 1.0.0
	 * @var   string $plugin_file Name of the plugin file
	 */
	protected $plugin_file;

	/**
	 * Plugin file path.
	 *
	 * Full filesystem path to the main plugin file.
	 *
	 * @since 1.0.0
	 * @var   string $plugin_file_path Full path to the plugin file
	 */
	protected $plugin_file_path;

	/**
	 * Plugin version.
	 *
	 * Current version of the plugin, used for cache busting and updates.
	 *
	 * @since 1.0.0
	 * @var   string $version Current plugin version
	 */
	protected string $version = 'PLUGIN_VERSION.BUILDTIMESTAMP';

	/**
	 * Includes directory.
	 *
	 * Path to the plugin's includes directory containing additional classes.
	 *
	 * @since 1.0.0
	 * @var   string $includes_directory Path to plugin includes directory
	 */
	protected string $includes_directory;

	/**
	 * Debug mode flag.
	 *
	 * Determines whether debug mode is enabled, affecting error reporting
	 * and development features.
	 *
	 * @since 1.0.0
	 * @var   bool $debug Whether debug mode is enabled
	 */
	protected $debug = false;

	/**
	 * End of line character.
	 *
	 * Character used for line endings in output, useful for debugging
	 * and code formatting.
	 *
	 * @since 1.0.0
	 * @var   string $eol End of line character for output
	 */
	protected string $eol = '';

	/**
	 * Spectator Role for MainWP Dashboard
	 */
	protected $spectator_role_name = 'spectator';


	/**
	 * Constructor.
	 *
	 * Initializes all necessary properties and sets up the plugin environment
	 * including debug mode, directories, URLs, and WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		// Static settings.
		$this->debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'IWORKS_DEV_MODE' ) && IWORKS_DEV_MODE );
		// Use minimized scripts if not debug.
		$this->dev = $this->debug ? '' : '.min';
		// Add EOL if debug.
		$this->eol = $this->debug ? PHP_EOL : '';
		// Directories and URLs.
		$this->base = __DIR__;
		$this->dir  = basename( dirname( $this->base, 2 ) );
		$this->url  = plugins_url( $this->dir );
		// Plugin ID.
		$this->plugin_file_path = $this->base . '/spectator-role-for-mainwp-dashboard.php';
		$this->plugin_file      = plugin_basename( $this->plugin_file_path );
		// Plugin includes directory.
		$this->includes_directory = __DIR__ . '/spectator-role-for-mainwp-dashboard';
		/**
		 * ROle name filter
		 */
		$this->spectator_role_name = apply_filters( 'iworks/spectator_role_for_mainwp_dashboard/role_name', $this->spectator_role_name );
		// WordPress Hooks.
	}

	/**
	 * Get the plugin version.
	 *
	 * Returns either the current version or a timestamp/file hash in dev mode
	 * for cache busting during development.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $file Optional file path for hash generation.
	 * @return string Version string or timestamp/hash.
	 */
	public function get_version( $file = null ): string {
		if ( defined( 'IWORKS_DEV_MODE' ) && IWORKS_DEV_MODE ) {
			if ( null !== $file ) {
				$file = dirname( $this->base ) . $file;
				if ( is_file( $file ) ) {
					return md5_file( $file );
				}
			}
			return time();
		}
		return $this->version;
	}

	/**
	 * Generate a meta key name.
	 *
	 * Creates a properly formatted meta key name using the prefix
	 * to avoid conflicts with other plugins.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Base name for the meta key.
	 * @return string Formatted meta key name.
	 */
	protected function get_meta_name( $name ): string {
		return sprintf( '%s_%s', $this->meta_prefix, sanitize_title( $name ) );
	}

	/**
	 * Get the post type.
	 *
	 * Returns the current post type being handled by the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string Post type name.
	 */
	public function get_post_type(): string {
		return $this->post_type;
	}

	/**
	 * Get the plugin capability.
	 *
	 * Returns the required capability for plugin settings and functionality.
	 *
	 * @since 1.0.0
	 *
	 * @return string Capability name.
	 */
	public function get_this_capability(): string {
		return $this->capability;
	}

	/**
	 * Generate a slug name.
	 *
	 * Creates a URL-safe slug from the given name by combining
	 * the class name with the provided string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Input name to convert.
	 * @return string URL-safe slug.
	 */
	private function slug_name( $name ): string {
		return preg_replace( '/[_ ]+/', '-', strtolower( __CLASS__ . '_' . $name ) );
	}

	/**
	 * Get post meta value.
	 *
	 * Retrieves a post meta value using the plugin's meta prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id  Post ID to get meta for.
	 * @param string $meta_key Meta key name.
	 * @return mixed Meta value.
	 */
	public function get_post_meta( $post_id, $meta_key ) {
		return get_post_meta( $post_id, $this->get_meta_name( $meta_key ), true );
	}

	/**
	 * Print table body for post meta fields.
	 *
	 * Generates an HTML table with form inputs for post meta fields.
	 * This method directly outputs HTML content.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id Post ID to display meta for.
	 * @param array $fields  Array of field definitions.
	 * @return void
	 */
	protected function print_table_body( $post_id, $fields ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( $fields as $name => $data ) {
			$key   = $this->get_meta_name( $name );
			$value = $this->get_post_meta( $post_id, $name );
			// Extra attributes.
			$extra = isset( $data['placeholder'] ) ? sprintf( ' placeholder="%s" ', esc_attr( $data['placeholder'] ) ) : '';
			foreach ( array( 'placeholder', 'style', 'class', 'id' ) as $extra_key ) {
				if ( isset( $data[ $extra_key ] ) ) {
					$extra .= sprintf( ' %s="%s" ', esc_attr( $extra_key ), esc_attr( $data[ $extra_key ] ) );
				}
			}
			// Start row.
			echo '<tr>';
			printf( '<th scope="row" style="width: 130px">%s</th>', esc_html( $data['title'] ) );
			echo '<td>';
			switch ( $data['type'] ) {
				case 'number':
					foreach ( array( 'min', 'max', 'step' ) as $extra_key ) {
						if ( isset( $data[ $extra_key ] ) ) {
							$extra .= sprintf( ' min="%d" ', intval( $data[ $extra_key ] ) );
						}
					}
					printf(
						'<input type="number" name="%s" value="%d" %s />',
						esc_attr( $key ),
						intval( $value ),
						// Data string escaped few lines above.
						$extra
					);
					break;
				case 'date':
					$date = intval( $this->get_post_meta( $post_id, $name ) );
					if ( empty( $date ) ) {
						$date = strtotime( 'now' );
					}
					printf(
						'<input type="text" class="datepicker" name="%s" value="%s" />',
						esc_attr( $this->get_meta_name( $name ) ),
						esc_attr( $date )
					);
					break;
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Get module file path.
	 *
	 * Constructs and returns the full path to a module file within the vendor directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename Name of the file to locate.
	 * @param string $vendor   Vendor directory name. Default 'iworks'.
	 * @return string|false Full path to the file or false if not found.
	 */
	protected function get_module_file( $filename, $vendor = 'iworks' ) {
		return realpath(
			sprintf(
				'%s/%s/%s/%s.php',
				$this->base,
				$vendor,
				$this->dir,
				$filename
			)
		);
	}

	/**
	 * Output HTML title.
	 *
	 * Outputs a properly escaped HTML heading element for admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Title text to display.
	 * @return void
	 */
	protected function html_title( $text ): void {
		printf( '<h1 class="wp-heading-inline">%s</h1>', esc_html( $text ) );
	}

	/**
	 * Get plugin stub metadata.
	 *
	 * Returns an array containing the plugin's metadata including:
	 * - Publication date
	 * - Current version
	 * - GitHub repository URL
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     Plugin metadata.
	 *
	 *     @type string $published The publication date in 'YYYY-MM-DD' format.
	 *     @type string $version   The current version of the plugin.
	 *     @type string $github    The GitHub repository URL.
	 * }
	 */
	public function get_stub_data(): array {
		return array(
			'published' => '2026-05-21',
			'version'   => '2.0.0',
			'github'    => 'https://github.com/iworks/spectator-role-for-mainwp-dashboard',
		);
	}

	/**
	 * Log message using Simple History.
	 *
	 * Logs a message using the Simple History plugin if available,
	 * including current user information.
	 *
	 * Read more: https://simple-history.com/docs/logging-api/#using-simpleLogger
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Log message.
	 * @param array  $data    Additional log data.
	 * @param string $level   Log level: 'debug', 'warning', 'notice'. Default 'notice'.
	 * @return void
	 */
	protected function simple_history_logger_helper( $message, $data, $level = 'notice' ): void {
		// Check if Simple History plugin is active.
		if ( ! function_exists( 'SimpleLogger' ) ) {
			return;
		}
		// Add logged in user data to log.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$data = wp_parse_args(
				$data,
				array(
					'username'    => $user->display_name ?? $user->user_login,
					'_user_id'    => get_current_user_id(),
					'_user_login' => $user->user_login,
					'_user_email' => $user->user_email,
				)
			);
		}
		// Select level and write log.
		switch ( $level ) {
			case 'debug':
				SimpleLogger()->debug( $message, $data );
				break;
			case 'warning':
				SimpleLogger()->warning( $message, $data );
				break;
			case 'notice':
				SimpleLogger()->notice( $message, $data );
				break;
			default:
				SimpleLogger()->notice( $message, $data );
				break;
		}
	}
}
