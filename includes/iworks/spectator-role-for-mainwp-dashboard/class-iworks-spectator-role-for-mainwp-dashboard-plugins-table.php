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

if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard_plugins_table' ) ) {
	return;
}

/**
 * WP_List_Table is not always loaded, even in the admin context.
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Plugins list table for Spectator Role for MainWP Dashboard.
 *
 * Displays plugins installed across MainWP child sites using the native
 * WP_List_Table API, providing search, MU/regular views, sortable columns,
 * pagination, and CSV export.
 *
 * @since 1.0.0
 * @package IWorks\Spectator_Role_For_MainWP_Dashboard
 */
class iworks_spectator_role_for_mainwp_dashboard_plugins_table extends WP_List_Table {

	/**
	 * Number of plugins displayed per page.
	 *
	 * @since 1.0.0
	 * @var   int $per_page Number of rows per page.
	 */
	private int $per_page = 20;

	/**
	 * Aggregated plugin rows.
	 *
	 * @since 1.0.0
	 * @var   array $plugins Aggregated plugins data.
	 */
	private array $plugins = array();

	/**
	 * Constructor.
	 *
	 * Configures the list table as a non-AJAX table with singular/plural labels.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'plugin',
				'plural'   => 'plugins',
				'ajax'     => false,
			)
		);

		/**
		 * Columns are not set via $_column_headers: doing so would bypass
		 * get_hidden_columns() and disable the Screen Options checkboxes.
		 * They are provided by the "manage_{screen}_columns" filter, which
		 * the admin class registers on the page load hook.
		 */
	}

	/**
	 * Get the list of columns.
	 *
	 * @since 1.0.0
	 * @return array Column definitions keyed by column name.
	 */
	public function get_columns() {
		return array(
			'name'        => __( 'Name', 'spectator-role-for-mainwp-dashboard' ),
			'mu'          => __( 'Type', 'spectator-role-for-mainwp-dashboard' ),
			'versions'    => __( 'Versions', 'spectator-role-for-mainwp-dashboard' ),
			'sites'       => __( 'Sites', 'spectator-role-for-mainwp-dashboard' ),
			'sites_count' => __( 'Sites Count', 'spectator-role-for-mainwp-dashboard' ),
		);
	}

	/**
	 * Get the sortable columns.
	 *
	 * @since 1.0.0
	 * @return array Sortable column definitions.
	 */
	protected function get_sortable_columns() {
		return array(
			'name'        => array( 'name', true ),
			'sites_count' => array( 'sites_count', false ),
		);
	}

	/**
	 * Aggregate plugins from all MainWP child sites.
	 *
	 * Reads the plugins JSON stored by MainWP and groups it by plugin name.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_plugins() {
		global $wpdb;

		if ( ! empty( $this->plugins ) ) {
			return;
		}

		$table_name   = $wpdb->prefix . 'mainwp_wp';
		$plugins_data = $wpdb->get_results( "SELECT name, url, plugins FROM {$table_name}" );
		$plugins      = array();

		foreach ( $plugins_data as $plugin_data ) {
			$site_plugins = json_decode( $plugin_data->plugins, true );
			if ( ! is_array( $site_plugins ) ) {
				continue;
			}
			foreach ( $site_plugins as $site_plugin ) {
				$plugin_name = $site_plugin['name'];
				$mu          = ! empty( $site_plugin['mu'] );
				if ( ! isset( $plugins[ $plugin_name ] ) ) {
					$plugins[ $plugin_name ] = array(
						'name'        => $plugin_name,
						'mu'          => $mu,
						'sites'       => array(),
						'versions'    => array(),
						'sites_count' => 0,
					);
				}
				/*
				 * A plugin is must-use if it is marked as such on any site.
				 */
				if ( $mu ) {
					$plugins[ $plugin_name ]['mu'] = true;
				}
				/*
				 * Sites.
				 */
				$plugins[ $plugin_name ]['sites'][ $plugin_data->url ] = $plugin_data->name;
				/*
				 * Versions.
				 */
				$version = isset( $site_plugin['version'] ) ? $site_plugin['version'] : '';
				if ( ! isset( $plugins[ $plugin_name ]['versions'][ $version ] ) ) {
					$plugins[ $plugin_name ]['versions'][ $version ] = array();
				}
				$plugins[ $plugin_name ]['versions'][ $version ]['sites'][] = $plugin_data->url;
			}
		}

		foreach ( $plugins as $plugin_name => $plugin ) {
			$plugins[ $plugin_name ]['sites_count'] = count( $plugin['sites'] );
		}

		$this->plugins = $plugins;
	}

	/**
	 * Get the currently requested MU view.
	 *
	 * @since 1.0.0
	 * @return string One of: 'all', 'regular', 'mu'.
	 */
	public function get_current_mu() {
		$mu = isset( $_REQUEST['mu'] ) ? sanitize_key( $_REQUEST['mu'] ) : 'all';
		if ( ! in_array( $mu, array( 'all', 'regular', 'mu' ), true ) ) {
			$mu = 'all';
		}
		return $mu;
	}

	/**
	 * Apply the current filters (search + MU view) to the aggregated rows.
	 *
	 * @since 1.0.0
	 * @return array Filtered plugin rows.
	 */
	private function get_filtered_plugins() {
		$this->load_plugins();

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$mu     = $this->get_current_mu();

		$plugins = $this->plugins;

		/**
		 * Search by plugin name.
		 */
		if ( '' !== $search ) {
			$plugins = array_filter(
				$plugins,
				static function ( $plugin ) use ( $search ) {
					return false !== stripos( $plugin['name'], $search );
				}
			);
		}

		/**
		 * MU view.
		 */
		if ( 'mu' === $mu ) {
			$plugins = array_filter(
				$plugins,
				static function ( $plugin ) {
					return ! empty( $plugin['mu'] );
				}
			);
		} elseif ( 'regular' === $mu ) {
			$plugins = array_filter(
				$plugins,
				static function ( $plugin ) {
					return empty( $plugin['mu'] );
				}
			);
		}

		return $plugins;
	}

	/**
	 * Prepare plugins for display.
	 *
	 * Handles aggregation, search, MU filtering, sorting, and pagination.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		$plugins = $this->get_filtered_plugins();

		/**
		 * Sorting - whitelisted to prevent injection.
		 */
		$orderby = 'name';
		if ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array( 'name', 'sites_count' ), true ) ) {
			$orderby = sanitize_key( $_REQUEST['orderby'] );
		}
		$order = 'ASC';
		if ( isset( $_REQUEST['order'] ) && in_array( strtoupper( sanitize_key( $_REQUEST['order'] ) ), array( 'ASC', 'DESC' ), true ) ) {
			$order = strtoupper( sanitize_key( $_REQUEST['order'] ) );
		}
		uasort(
			$plugins,
			static function ( $a, $b ) use ( $orderby, $order ) {
				$result = strcasecmp( $a['name'], $b['name'] );
				if ( 'sites_count' === $orderby ) {
					$result = $a['sites_count'] <=> $b['sites_count'];
					if ( 0 === $result ) {
						$result = strcasecmp( $a['name'], $b['name'] );
					}
				}
				return 'DESC' === $order ? -$result : $result;
			}
		);

		/**
		 * Pagination.
		 */
		$current_page = max( 1, $this->get_pagenum() );
		$total_items  = count( $plugins );
		$offset       = ( $current_page - 1 ) * $this->per_page;

		$this->items = array_slice( $plugins, $offset, $this->per_page, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => (int) ceil( $total_items / $this->per_page ),
			)
		);
	}

	/**
	 * Get the available views (All / Regular / Must-Use).
	 *
	 * @since 1.0.0
	 * @return array Views in the format class => HTML.
	 */
	public function get_views() {
		$this->load_plugins();

		$current = $this->get_current_mu();

		$counts = array(
			'all'     => count( $this->plugins ),
			'regular' => 0,
			'mu'      => 0,
		);
		foreach ( $this->plugins as $plugin ) {
			if ( ! empty( $plugin['mu'] ) ) {
				++$counts['mu'];
			} else {
				++$counts['regular'];
			}
		}

		$views = array();
		foreach ( $counts as $key => $count ) {
			$views[ $key ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( $this->get_view_url( $key ) ),
				$current === $key ? ' class="current"' : '',
				esc_html( $this->get_view_label( $key ) ),
				(int) $count
			);
		}

		return $views;
	}

	/**
	 * Get the label for a view.
	 *
	 * @since 1.0.0
	 * @param string $key View key.
	 * @return string View label.
	 */
	private function get_view_label( $key ) {
		switch ( $key ) {
			case 'mu':
				return __( 'Must-Use', 'spectator-role-for-mainwp-dashboard' );
			case 'regular':
				return __( 'Regular', 'spectator-role-for-mainwp-dashboard' );
			default:
				return __( 'All', 'spectator-role-for-mainwp-dashboard' );
		}
	}

	/**
	 * Build the URL that filters the table by a view.
	 *
	 * Preserves an active search term.
	 *
	 * @since 1.0.0
	 * @param string $key View key: 'all', 'regular' or 'mu'.
	 * @return string Filter URL.
	 */
	private function get_view_url( $key ) {
		$url    = admin_url( 'admin.php?page=mainwp-custom-plugins-list' );
		$mu     = $this->get_current_mu();
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$url = add_query_arg( 's', $search, $url );
		}
		if ( 'all' !== $key ) {
			$url = add_query_arg( 'mu', $key, $url );
		}
		return $url;
	}

	/**
	 * Build the "Export CSV" URL preserving current filters.
	 *
	 * Keeps the active search term and MU view, and adds a nonce.
	 *
	 * @since 1.0.0
	 * @return string Export URL.
	 */
	public function get_export_url() {
		$url = admin_url( 'admin.php?page=mainwp-custom-plugins-list&action=export_csv' );
		$mu  = $this->get_current_mu();
		if ( 'all' !== $mu ) {
			$url = add_query_arg( 'mu', $mu, $url );
		}
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$url = add_query_arg( 's', $search, $url );
		}
		return wp_nonce_url( $url, 'iworks_export_plugins_csv' );
	}

	/**
	 * Handle the CSV export request.
	 *
	 * Triggered by the "Export CSV" button on the plugins list page.
	 * Exports all plugins matching the current filters (search + MU view),
	 * with no pagination limit, as one row per plugin-site pair:
	 * plugin name, version, mu, site name, site url.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_handle_export_csv() {
		if ( ! isset( $_REQUEST['page'] ) || 'mainwp-custom-plugins-list' !== $_REQUEST['page'] ) {
			return;
		}
		if ( ! isset( $_REQUEST['action'] ) || 'export_csv' !== $_REQUEST['action'] ) {
			return;
		}
		check_admin_referer( 'iworks_export_plugins_csv' );

		$plugins = $this->get_filtered_plugins();
		ksort( $plugins );

		$filename = sprintf( 'mainwp-plugins-%s.csv', gmdate( 'Y-m-d' ) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// UTF-8 BOM so Excel displays non-ASCII names correctly.
		echo "\xEF\xBB\xBF";

		$handle = fopen( 'php://output', 'w' );
		fputcsv(
			$handle,
			array(
				__( 'Plugin Name', 'spectator-role-for-mainwp-dashboard' ),
				__( 'Version', 'spectator-role-for-mainwp-dashboard' ),
				__( 'Type', 'spectator-role-for-mainwp-dashboard' ),
				__( 'Site Name', 'spectator-role-for-mainwp-dashboard' ),
				__( 'URL', 'spectator-role-for-mainwp-dashboard' ),
			)
		);
		foreach ( $plugins as $plugin ) {
			$type = $this->get_view_label( ! empty( $plugin['mu'] ) ? 'mu' : 'regular' );
			foreach ( $plugin['versions'] as $version => $data ) {
				foreach ( $data['sites'] as $site_url ) {
					fputcsv(
						$handle,
						array(
							$plugin['name'],
							$version,
							$type,
							$plugin['sites'][ $site_url ],
							$site_url,
						)
					);
				}
			}
		}
		fclose( $handle );
		exit;
	}

	/**
	 * Render the plugin name column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Plugin row.
	 * @return string Column HTML.
	 */
	public function column_name( $item ) {
		return '<strong>' . esc_html( $item['name'] ) . '</strong>';
	}

	/**
	 * Render the type column.
	 *
	 * Displays the plugin type as a post-status-style label that
	 * filters the table by type.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Plugin row.
	 * @return string Column HTML.
	 */
	public function column_mu( $item ) {
		$key = ! empty( $item['mu'] ) ? 'mu' : 'regular';
		return sprintf(
			'<a href="%s"><span class="post-state">%s</span></a>',
			esc_url( $this->get_view_url( $key ) ),
			esc_html( $this->get_view_label( $key ) )
		);
	}

	/**
	 * Render the versions column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Plugin row.
	 * @return string Column HTML.
	 */
	public function column_versions( $item ) {
		return '<strong>' . esc_html( implode( ', ', array_keys( $item['versions'] ) ) ) . '</strong>';
	}

	/**
	 * Render the sites column.
	 *
	 * Generates an HTML list of sites where the plugin is installed,
	 * grouped by version if multiple versions exist.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Plugin row.
	 * @return string Column HTML.
	 */
	public function column_sites( $item ) {
		$output = '';
		foreach ( $item['versions'] as $version => $data ) {
			if ( 1 < count( $item['versions'] ) ) {
				$output .= '<strong>' . esc_html( $version ) . '</strong>';
			}
			$output .= '<ul>';
			foreach ( $data['sites'] as $site_url ) {
				$output .= '<li>';
				$output .= '<a href="' . esc_url( $site_url ) . '/wp-admin/" target="_blank">' . esc_html( $item['sites'][ $site_url ] ) . '</a>';
				$output .= '</li>';
			}
			$output .= '</ul>';
		}
		return $output;
	}

	/**
	 * Render the sites count column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Plugin row.
	 * @return string Column HTML.
	 */
	public function column_sites_count( $item ) {
		return (int) $item['sites_count'];
	}

	/**
	 * Render fallback for undefined columns.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item        Plugin row.
	 * @param string $column_name Column name.
	 * @return string Column HTML.
	 */
	public function column_default( $item, $column_name ) {
		if ( isset( $item[ $column_name ] ) ) {
			return esc_html( $item[ $column_name ] );
		}
		return '&mdash;';
	}

	/**
	 * Message displayed when no plugins are found.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No plugins found.', 'spectator-role-for-mainwp-dashboard' );
	}
}
