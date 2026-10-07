<?php
/** Preserve site-owned redirects; clean only shared component temporary storage.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
require_once __DIR__ . '/includes/deckerweb-plugin-library/lifecycle.php';
deckerweb_library_uninstall_v2( __DIR__ . '/redirect-draft-content.php' );
// rdc_targets deliberately remains available for reinstall and snippet migration.
// The updater stores one repository-specific transient, scoped per network.
$key = 'ddw_ghru_' . substr( md5( 'https://github.com/deckerweb/redirect-draft-content' ), 0, 24 );
if ( is_multisite() ) {
 foreach ( get_networks( array( 'fields' => 'ids', 'number' => 0 ) ) as $network_id ) { delete_network_option( $network_id, '_site_transient_' . $key ); delete_network_option( $network_id, '_site_transient_timeout_' . $key ); }
} else { delete_site_transient( $key ); }
