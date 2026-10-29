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

if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard_sites_table' ) ) {
	return;
}

/**
 * WP_List_Table is not always loaded, even in the admin context.
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Sites list table for Spectator Role for MainWP Dashboard.
 *
 * Displays MainWP child sites using the native WP_List_Table API,
 * providing pagination, sortable columns, and search.
 *
 * @since 1.0.0
 * @package IWorks\Spectator_Role_For_MainWP_Dashboard
 */
class iworks_spectator_role_for_mainwp_dashboard_sites_table extends WP_List_Table {

	/**
	 * Number of sites displayed per page.
	 *
	 * @since 1.0.0
	 * @var   int $per_page Number of rows per page.
	 */
	private int $per_page = 20;

	/**
	 * Tags of the sites on the current page, keyed by site ID.
	 *
	 * @since 1.0.0
	 * @var   array $site_tags Map of site ID to array of tag objects (id, name).
	 */
	private array $site_tags = array();

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
				'singular' => 'site',
				'plural'   => 'sites',
				'ajax'     => false,
			)
		);

		/**
		 * Since WordPress 7.x, WP_List_Table::get_column_info() resolves
		 * columns via get_column_headers() and no longer falls back to
		 * get_columns() for custom screens. Set them explicitly.
		 */
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
			'name',
		);
	}

	/**
	 * Get the list of columns.
	 *
	 * @since 1.0.0
	 * @return array Column definitions keyed by column name.
	 */
	public function get_columns() {
		return array(
			'name' => __( 'Site Name', 'spectator-role-for-mainwp-dashboard' ),
			'url'  => __( 'URL', 'spectator-role-for-mainwp-dashboard' ),
			'tags' => __( 'Tags', 'spectator-role-for-mainwp-dashboard' ),
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
			'name' => array( 'name', true ),
			'url'  => array( 'url', false ),
		);
	}

	/**
	 * Prepare sites for display.
	 *
	 * Handles search, tag filtering, sorting, and pagination for the
	 * MainWP child sites query.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mainwp_wp';

		/**
		 * Filters - shared with CSV export.
		 */
		list( $where_sql, $args ) = $this->get_filter_sql();

		/**
		 * Sorting - whitelisted to prevent SQL injection.
		 */
		$orderby = 'name';
		if ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array( 'name', 'url' ), true ) ) {
			$orderby = sanitize_key( $_REQUEST['orderby'] );
		}
		$order = 'ASC';
		if ( isset( $_REQUEST['order'] ) && in_array( strtoupper( sanitize_key( $_REQUEST['order'] ) ), array( 'ASC', 'DESC' ), true ) ) {
			$order = strtoupper( sanitize_key( $_REQUEST['order'] ) );
		}

		/**
		 * Pagination.
		 */
		$current_page = max( 1, $this->get_pagenum() );
		$offset       = ( $current_page - 1 ) * $this->per_page;

		$total_items = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} w{$where_sql}", ...$args )
		);

		$args[] = $this->per_page;
		$args[] = $offset;

		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT w.* FROM {$table_name} w{$where_sql} ORDER BY w.{$orderby} {$order} LIMIT %d OFFSET %d",
				...$args
			)
		);
		if ( ! is_array( $this->items ) ) {
			$this->items = array();
		}

		/**
		 * Load tags for the sites on the current page.
		 */
		$this->load_site_tags();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => (int) ceil( $total_items / $this->per_page ),
			)
		);
	}

	/**
	 * Get the currently requested tag ID.
	 *
	 * @since 1.0.0
	 * @return int Tag ID, or 0 when no tag filter is active.
	 */
	public function get_current_tag_id() {
		return isset( $_REQUEST['tag'] ) ? absint( $_REQUEST['tag'] ) : 0;
	}

	/**
	 * Build the WHERE clause for the current filters.
	 *
	 * Handles the search term and the tag filter. Shared by the list
	 * table query and the CSV export so both always stay in sync.
	 *
	 * @since 1.0.0
	 * @return array Array of [ where_sql, args ].
	 */
	private function get_filter_sql() {
		global $wpdb;

		$table_name  = $wpdb->prefix . 'mainwp_wp';
		$group_table = $wpdb->prefix . 'mainwp_wp_group';

		/**
		 * Search - placeholders only; LIKE values are bound in a single
		 * prepare() call per query so the wildcards are never re-parsed.
		 */
		$where_sql = '';
		$args      = array();
		$search    = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$tag_id    = $this->get_current_tag_id();

		if ( '' !== $search ) {
			$like      = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql = ' WHERE ( w.name LIKE %s OR w.url LIKE %s ) ';
			$args[]    = $like;
			$args[]    = $like;
		}

		/**
		 * Tag filter - EXISTS avoids duplicate rows for sites in multiple tags.
		 */
		if ( 0 < $tag_id ) {
			$where_sql .= ( '' === $where_sql ) ? ' WHERE ' : ' AND ';
			$where_sql .= "EXISTS ( SELECT 1 FROM {$group_table} wg WHERE wg.wpid = w.id AND wg.groupid = %d ) ";
			$args[]    = $tag_id;
		}

		return array( $where_sql, $args );
	}

	/**
	 * Build the "Export CSV" URL preserving current filters.
	 *
	 * Keeps the active search term and tag filter, and adds a nonce.
	 *
	 * @since 1.0.0
	 * @return string Export URL.
	 */
	public function get_export_url() {
		$url = admin_url( 'admin.php?page=mainwp-custom-sites-list&action=export_csv' );
		$tag_id = $this->get_current_tag_id();
		if ( 0 < $tag_id ) {
			$url = add_query_arg( 'tag', $tag_id, $url );
		}
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$url = add_query_arg( 's', $search, $url );
		}
		return wp_nonce_url( $url, 'iworks_export_sites_csv' );
	}

	/**
	 * Handle the CSV export request.
	 *
	 * Triggered by the "Export CSV" button on the sites list page.
	 * Exports all sites matching the current filters (search + tag),
	 * with no pagination limit, as: site name, site url.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_handle_export_csv() {
		if ( ! isset( $_REQUEST['page'] ) || 'mainwp-custom-sites-list' !== $_REQUEST['page'] ) {
			return;
		}
		if ( ! isset( $_REQUEST['action'] ) || 'export_csv' !== $_REQUEST['action'] ) {
			return;
		}
		check_admin_referer( 'iworks_export_sites_csv' );

		global $wpdb;

		$table_name = $wpdb->prefix . 'mainwp_wp';

		list( $where_sql, $args ) = $this->get_filter_sql();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT w.name, w.url FROM {$table_name} w{$where_sql} ORDER BY w.name ASC",
				...$args
			)
		);
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$filename = sprintf( 'mainwp-sites-%s.csv', gmdate( 'Y-m-d' ) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// UTF-8 BOM so Excel displays non-ASCII site names correctly.
		echo "\xEF\xBB\xBF";

		$handle = fopen( 'php://output', 'w' );
		fputcsv( $handle, array(
			__( 'Site Name', 'spectator-role-for-mainwp-dashboard' ),
			__( 'URL', 'spectator-role-for-mainwp-dashboard' ),
		) );
		foreach ( $rows as $row ) {
			fputcsv( $handle, array( $row->name, $row->url ) );
		}
		fclose( $handle );
		exit;
	}

	/**
	 * Get tags with at least one connected site.
	 *
	 * Empty tags are excluded by the INNER JOIN.
	 *
	 * @since 1.0.0
	 * @return array Array of objects with id, name, and num properties.
	 */
	private function get_tags_with_counts() {
		global $wpdb;

		return (array) $wpdb->get_results(
			"SELECT g.id, g.name, COUNT( wg.wpid ) AS num
			FROM {$wpdb->prefix}mainwp_group g
			INNER JOIN {$wpdb->prefix}mainwp_wp_group wg ON wg.groupid = g.id
			GROUP BY g.id, g.name
			ORDER BY g.name ASC"
		);
	}

	/**
	 * Load tags for the sites on the current page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_site_tags() {
		global $wpdb;

		if ( empty( $this->items ) ) {
			return;
		}

		$ids = array();
		foreach ( $this->items as $item ) {
			$ids[] = (int) $item->id;
		}

		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				sprintf(
					"SELECT wg.wpid, g.id, g.name
					FROM {$wpdb->prefix}mainwp_wp_group wg
					INNER JOIN {$wpdb->prefix}mainwp_group g ON g.id = wg.groupid
					WHERE wg.wpid IN ( %s )
					ORDER BY g.name ASC",
					implode( ',', array_fill( 0, count( $ids ), '%d' ) )
				),
				...$ids
			)
		);

		foreach ( $rows as $row ) {
			$this->site_tags[ (int) $row->wpid ][] = (object) array(
				'id'   => (int) $row->id,
				'name' => $row->name,
			);
		}
	}

	/**
	 * Get the available views (tags) for the table.
	 *
	 * Renders tag links above the table, similar to post statuses.
	 *
	 * @since 1.0.0
	 * @return array Views in the format class => HTML.
	 */
	public function get_views() {
		$base_url = admin_url( 'admin.php?page=mainwp-custom-sites-list' );
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$tag_id   = $this->get_current_tag_id();

		/**
		 * All sites.
		 */
		$all_url = '' !== $search ? add_query_arg( 's', $search, $base_url ) : $base_url;
		$views   = array(
			'all' => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( $all_url ),
				0 === $tag_id ? ' class="current"' : '',
				esc_html__( 'All', 'spectator-role-for-mainwp-dashboard' ),
				(int) $this->get_pagination_arg( 'total_items' )
			),
		);

		/**
		 * Tags with at least one site.
		 */
		foreach ( $this->get_tags_with_counts() as $tag ) {
			$views[ 'tag-' . $tag->id ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( $this->get_tag_url( $tag->id ) ),
				$tag_id === (int) $tag->id ? ' class="current"' : '',
				esc_html( $tag->name ),
				(int) $tag->num
			);
		}

		return $views;
	}

	/**
	 * Build the URL that filters the table by a tag.
	 *
	 * Preserves an active search term.
	 *
	 * @since 1.0.0
	 *
	 * @param int $tag_id Tag ID.
	 * @return string Filter URL.
	 */
	private function get_tag_url( $tag_id ) {
		$url    = admin_url( 'admin.php?page=mainwp-custom-sites-list' );
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$url = add_query_arg( 's', $search, $url );
		}
		return add_query_arg( 'tag', $tag_id, $url );
	}

	/**
	 * Render the site name column.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item Site row.
	 * @return string Column HTML.
	 */
	public function column_name( $item ) {
		return '<strong>' . esc_html( $item->name ) . '</strong>';
	}

	/**
	 * Render the URL column.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item Site row.
	 * @return string Column HTML.
	 */
	public function column_url( $item ) {
		return sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			esc_url( $item->url ),
			esc_html( $item->url )
		);
	}

	/**
	 * Add row actions to the primary column.
	 *
	 * Provides a MainWP direct auto-login link for the site.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item        Site row.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row actions HTML, or empty string.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( 'name' !== $column_name ) {
			return '';
		}
		$admin_url = admin_url( 'admin.php?page=SiteOpen&id=' . $item->id );
		$actions   = array(
			'go-to-admin' => sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $admin_url ),
				esc_html__( 'Go to Admin', 'spectator-role-for-mainwp-dashboard' )
			),
		);
		return $this->row_actions( $actions );
	}

	/**
	 * Render the tags column.
	 *
	 * Displays tags as links that filter the table by tag.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item Site row.
	 * @return string Column HTML.
	 */
	public function column_tags( $item ) {
		if ( empty( $this->site_tags[ (int) $item->id ] ) ) {
			return '&mdash;';
		}
		$links = array();
		foreach ( $this->site_tags[ (int) $item->id ] as $tag ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $this->get_tag_url( $tag->id ) ),
				esc_html( $tag->name )
			);
		}
		return implode( ', ', $links );
	}

	/**
	 * Render fallback for undefined columns.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item        Site row.
	 * @param string $column_name Column name.
	 * @return string Column HTML.
	 */
	public function column_default( $item, $column_name ) {
		if ( isset( $item->$column_name ) ) {
			return esc_html( $item->$column_name );
		}
		return '&mdash;';
	}

	/**
	 * Message displayed when no child sites are found.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function no_items() {
		$connect_url = admin_url( 'admin.php?page=managesites' );
		printf(
			/* translators: %s: URL to the MainWP manage sites page. */
			wp_kses_post( __( 'No child sites found. Connect them via <a href="%s">MainWP</a>.', 'spectator-role-for-mainwp-dashboard' ) ),
			esc_url( $connect_url )
		);
	}
}
