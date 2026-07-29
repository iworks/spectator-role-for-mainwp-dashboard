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

if ( class_exists( 'iworks_spectator_role_for_mainwp_dashboard_mainwp' ) ) {
	return;
}

class iworks_spectator_role_for_mainwp_dashboard_mainwp extends iworks_spectator_role_for_mainwp_dashboard_base {

	public function __construct() {
		parent::__construct();
		add_filter( 'mainwp_currentusercan', array( $this, 'filter_mainwp_currentusercan' ), PHP_INT_MAX, 3 );
	}

	public function filter_mainwp_currentusercan( $can, $cap_type, $cap ) {
		if ( $this->is_current_user_spectator() ) {
			switch ( $cap ) {
				case 'access_global_dashboard':
				case 'access_individual_dashboard':
				case 'access_insights_dashboard':
				case 'manage_security_issues':
				case 'see_server_information':
				case 'test_connection':
				case 'monitoring_sites':
					return true;
			}
			return false;
		}
		return $can;
	}
}
