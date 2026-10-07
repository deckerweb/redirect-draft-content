<?php
/** Translate shared updater messages through this plugin’s domain.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;
/**
 * @param string $message Shared English message.
 * @return string Localized host message.
 */
return static function ( string $message ): string {
    // Literal calls let the host's normal translation extractor collect every source string.
    switch ( $message ) {
        case 'Private mode must be boolean.':
            return __( 'Private mode must be boolean.', 'redirect-draft-content' );
        case 'Invalid authentication provider.':
            return __( 'Invalid authentication provider.', 'redirect-draft-content' );
        case 'The plugin must be installed in a stable slug directory.':
            return __( 'The plugin must be installed in a stable slug directory.', 'redirect-draft-content' );
        case 'Invalid GitHub repository URL.':
            return __( 'Invalid GitHub repository URL.', 'redirect-draft-content' );
        case 'The private update could not be authorized. Check the repository credentials and refresh updates.':
            return __( 'The private update could not be authorized. Check the repository credentials and refresh updates.', 'redirect-draft-content' );
        case 'Could not create the update download file.':
            return __( 'Could not create the update download file.', 'redirect-draft-content' );
        case 'The private update download failed. Check credentials and try again.':
            return __( 'The private update download failed. Check credentials and try again.', 'redirect-draft-content' );
        case 'Could not access the update filesystem.':
            return __( 'Could not access the update filesystem.', 'redirect-draft-content' );
        case 'GitHub release does not contain the plugin main file.':
            return __( 'GitHub release does not contain the plugin main file.', 'redirect-draft-content' );
        case 'Could not prepare the GitHub release package.':
            return __( 'Could not prepare the GitHub release package.', 'redirect-draft-content' );
        case 'See the release on GitHub.':
            return __( 'See the release on GitHub.', 'redirect-draft-content' );
        default:
            return $message;
    }
};
