<?php
/** Preserve site-owned redirects; clean only shared component temporary storage.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
require_once __DIR__ . '/includes/deckerweb-plugin-library/lifecycle.php';
deckerweb_library_uninstall_v3( __DIR__ . '/redirect-draft-content.php' );
// rdc_targets deliberately remains available for reinstall and snippet migration.
// Clean versioned and historical caches for this repository in each network.
/**
 * Remove only repository-owned update caches from all existing networks.
 * @return void Preserves redirect settings, content and other hosts' data.
 */
(static function (): void {
$ddw_rdc_updater_class = require __DIR__ . '/includes/deckerweb-github-release-updater-v2.php';
$ddw_rdc_repository = 'https://github.com/deckerweb/redirect-draft-content';
$ddw_rdc_updater_class::clear_repository_cache( $ddw_rdc_repository );
if ( is_multisite() ) {
 foreach ( get_networks( array( 'fields' => 'ids', 'number' => 0 ) ) as $ddw_rdc_network_id ) {
  // switch_to_blog does not change the current network. Address each network explicitly.
  foreach ( array( $ddw_rdc_repository, $ddw_rdc_repository . '|' . $ddw_rdc_updater_class::IMPLEMENTATION_VERSION ) as $ddw_rdc_identity ) {
   $ddw_rdc_key = 'ddw_ghru_' . substr( md5( $ddw_rdc_identity ), 0, 24 );
   foreach ( array( $ddw_rdc_key, $ddw_rdc_key . '_private' ) as $ddw_rdc_cache ) {
    delete_network_option( (int) $ddw_rdc_network_id, '_site_transient_' . $ddw_rdc_cache );
    delete_network_option( (int) $ddw_rdc_network_id, '_site_transient_timeout_' . $ddw_rdc_cache );
   }
  }
 }
}
})();
