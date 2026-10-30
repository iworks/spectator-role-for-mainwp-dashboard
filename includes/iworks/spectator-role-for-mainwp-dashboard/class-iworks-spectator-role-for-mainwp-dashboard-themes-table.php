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

if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard_themes_table' ) ) {
	return;
}

/**
 * WP_List_Table is not always loaded, even in the admin context.
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Themes list table for Spectator Role for MainWP Dashboard.
 *
 * Displays themes installed across MainWP child sites using the native
 * WP_List_Table API, providing search, active/inactive views, sortable
 * columns, pagination, and CSV export.
 *
 * @since 1.0.0
 * @package IWorks\Spectator_Role_For_MainWP_Dashboard
 */
class iworks_spectator_role_for_mainwp_dashboard_themes_table extends WP_List_Table {

	/**
	 * Number of themes displayed per page.
	 *
	 * @since 1.0.0
	 * @var   int $per_page Number of rows per page.
	 */
	private int $per_page = 20;

	/**
	 * Aggregated theme rows.
	 *
	 * @since 1.0.0
	 * @var   array $themes Aggregated themes data.
	 */
	private array $themes = array();

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
				'singular' => 'theme',
				'plural'   => 'themes',
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
			'active'      => __( 'Type', 'spectator-role-for-mainwp-dashboard' ),
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
	 * Aggregate themes from all MainWP child sites.
	 *
	 * Reads the themes JSON stored by MainWP and groups it by theme name.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_themes() {
		global $wpdb;

		if ( ! empty( $this->themes ) ) {
			return;
		}

		$table_name  = $wpdb->prefix . 'mainwp_wp';
		$themes_data = $wpdb->get_results( "SELECT name, url, themes FROM {$table_name}" );
		$themes      = array();

		foreach ( $themes_data as $theme_data ) {
			$site_themes = json_decode( $theme_data->themes, true );
			if ( ! is_array( $site_themes ) ) {
				continue;
			}
			foreach ( $site_themes as $site_theme ) {
				$theme_name = $site_theme['name'];
				$active     = ! empty( $site_theme['active'] );
				if ( ! isset( $themes[ $theme_name ] ) ) {
					$themes[ $theme_name ] = array(
						'name'        => $theme_name,
						'active'      => $active,
						'sites'       => array(),
						'versions'    => array(),
						'sites_count' => 0,
					);
				}
				/*
				 * A theme is active if it is active on any site.
				 */
				if ( $active ) {
					$themes[ $theme_name ]['active'] = true;
				}
				/*
				 * Sites.
				 */
				$themes[ $theme_name ]['sites'][ $theme_data->url ] = $theme_data->name;
				/*
				 * Versions.
				 */
				$version = isset( $site_theme['version'] ) ? $site_theme['version'] : '';
				if ( ! isset( $themes[ $theme_name ]['versions'][ $version ] ) ) {
					$themes[ $theme_name ]['versions'][ $version ] = array();
				}
				$themes[ $theme_name ]['versions'][ $version ]['sites'][] = $theme_data->url;
			}
		}

		foreach ( $themes as $theme_name => $theme ) {
			$themes[ $theme_name ]['sites_count'] = count( $theme['sites'] );
		}

		$this->themes = $themes;
	}

	/**
	 * Get the currently requested status view.
	 *
	 * @since 1.0.0
	 * @return string One of: 'all', 'active', 'inactive'.
	 */
	public function get_current_status() {
		$status = isset( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : 'all';
		if ( ! in_array( $status, array( 'all', 'active', 'inactive' ), true ) ) {
			$status = 'all';
		}
		return $status;
	}

	/**
	 * Apply the current filters (search + status view) to the aggregated rows.
	 *
	 * @since 1.0.0
	 * @return array Filtered theme rows.
	 */
	private function get_filtered_themes() {
		$this->load_themes();

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$status = $this->get_current_status();

		$themes = $this->themes;

		/**
		 * Search by theme name.
		 */
		if ( '' !== $search ) {
			$themes = array_filter(
				$themes,
				static function ( $theme ) use ( $search ) {
					return false !== stripos( $theme['name'], $search );
				}
			);
		}

		/**
		 * Status view.
		 */
		if ( 'active' === $status ) {
			$themes = array_filter(
				$themes,
				static function ( $theme ) {
					return ! empty( $theme['active'] );
				}
			);
		} elseif ( 'inactive' === $status ) {
			$themes = array_filter(
				$themes,
				static function ( $theme ) {
					return empty( $theme['active'] );
				}
			);
		}

		return $themes;
	}

	/**
	 * Prepare themes for display.
	 *
	 * Handles aggregation, search, status filtering, sorting, and pagination.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		$themes = $this->get_filtered_themes();

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
			$themes,
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
		$total_items  = count( $themes );
		$offset       = ( $current_page - 1 ) * $this->per_page;

		$this->items = array_slice( $themes, $offset, $this->per_page, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => (int) ceil( $total_items / $this->per_page ),
			)
		);
	}

	/**
	 * Get the available views (All / Active / Inactive).
	 *
	 * @since 1.0.0
	 * @return array Views in the format class => HTML.
	 */
	public function get_views() {
		$this->load_themes();

		$current = $this->get_current_status();

		$counts = array(
			'all'      => count( $this->themes ),
			'active'   => 0,
			'inactive' => 0,
		);
		foreach ( $this->themes as $theme ) {
			if ( ! empty( $theme['active'] ) ) {
				++$counts['active'];
			} else {
				++$counts['inactive'];
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
			case 'active':
				return __( 'Active', 'spectator-role-for-mainwp-dashboard' );
			case 'inactive':
				return __( 'Inactive', 'spectator-role-for-mainwp-dashboard' );
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
	 * @param string $key View key: 'all', 'active' or 'inactive'.
	 * @return string Filter URL.
	 */
	private function get_view_url( $key ) {
		$url    = admin_url( 'admin.php?page=mainwp-custom-themes-list' );
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$url = add_query_arg( 's', $search, $url );
		}
		if ( 'all' !== $key ) {
			$url = add_query_arg( 'status', $key, $url );
		}
		return $url;
	}

	/**
	 * Build the "Export CSV" URL preserving current filters.
	 *
	 * Keeps the active search term and status view, and adds a nonce.
	 *
	 * @since 1.0.0
	 * @return string Export URL.
	 */
	public function get_export_url() {
		$url    = admin_url( 'admin.php?page=mainwp-custom-themes-list&action=export_csv' );
		$status = $this->get_current_status();
		if ( 'all' !== $status ) {
			$url = add_query_arg( 'status', $status, $url );
		}
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$url = add_query_arg( 's', $search, $url );
		}
		return wp_nonce_url( $url, 'iworks_export_themes_csv' );
	}

	/**
	 * Handle the CSV export request.
	 *
	 * Triggered by the "Export CSV" button on the themes list page.
	 * Exports all themes matching the current filters (search + status view),
	 * with no pagination limit, as one row per theme-site pair:
	 * theme name, version, type, site name, site url.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_handle_export_csv() {
		if ( ! isset( $_REQUEST['page'] ) || 'mainwp-custom-themes-list' !== $_REQUEST['page'] ) {
			return;
		}
		if ( ! isset( $_REQUEST['action'] ) || 'export_csv' !== $_REQUEST['action'] ) {
			return;
		}
		check_admin_referer( 'iworks_export_themes_csv' );

		$themes = $this->get_filtered_themes();
		ksort( $themes );

		$filename = sprintf( 'mainwp-themes-%s.csv', gmdate( 'Y-m-d' ) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// UTF-8 BOM so Excel displays non-ASCII names correctly.
		echo "\xEF\xBB\xBF";

		$handle = fopen( 'php://output', 'w' );
		fputcsv(
			$handle,
			array(
				__( 'Theme Name', 'spectator-role-for-mainwp-dashboard' ),
				__( 'Version', 'spectator-role-for-mainwp-dashboard' ),
				__( 'Type', 'spectator-role-for-mainwp-dashboard' ),
				__( 'Site Name', 'spectator-role-for-mainwp-dashboard' ),
				__( 'URL', 'spectator-role-for-mainwp-dashboard' ),
			)
		);
		foreach ( $themes as $theme ) {
			$type = $this->get_view_label( ! empty( $theme['active'] ) ? 'active' : 'inactive' );
			foreach ( $theme['versions'] as $version => $data ) {
				foreach ( $data['sites'] as $site_url ) {
					fputcsv(
						$handle,
						array(
							$theme['name'],
							$version,
							$type,
							$theme['sites'][ $site_url ],
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
	 * Render the theme name column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Theme row.
	 * @return string Column HTML.
	 */
	public function column_name( $item ) {
		return '<strong>' . esc_html( $item['name'] ) . '</strong>';
	}

	/**
	 * Render the type column.
	 *
	 * Displays the theme type as a post-status-style label that
	 * filters the table by type.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Theme row.
	 * @return string Column HTML.
	 */
	public function column_active( $item ) {
		$key = ! empty( $item['active'] ) ? 'active' : 'inactive';
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
	 * @param array $item Theme row.
	 * @return string Column HTML.
	 */
	public function column_versions( $item ) {
		return '<strong>' . esc_html( implode( ', ', array_keys( $item['versions'] ) ) ) . '</strong>';
	}

	/**
	 * Render the sites column.
	 *
	 * Generates an HTML list of sites where the theme is installed,
	 * grouped by version if multiple versions exist.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Theme row.
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
	 * @param array $item Theme row.
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
	 * @param array  $item        Theme row.
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
	 * Message displayed when no themes are found.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No themes found.', 'spectator-role-for-mainwp-dashboard' );
	}
}
