<?php
/**
 * Integrate the shared deckerweb updater with plugin-scoped safeguards.
 *
 * @package RedirectDraftContent
 */

namespace Deckerweb\RedirectDraftContent;

defined( 'ABSPATH' ) || exit;

/** Configure shared update artwork and isolate plugin-specific safeguards. */
final class GitHubUpdates {
	/** Public release repository; never taken from user input. */
	private const REPOSITORY = 'https://github.com/deckerweb/redirect-draft-content';

	/** Register after WordPress initializes translations. @return void */
	public function register(): void {
		add_action( 'init', array( $this, 'boot' ) );
	}

	/** Load the shared versioned class once, including beside other deckerweb plugins. @return void */
	public function boot(): void {
		if ( ! class_exists( '\Deckerweb\GitHubReleaseUpdater\V2\Updater' ) ) {
			require_once dirname( DDW_RDC_FILE ) . '/includes/deckerweb-github-release-updater-v2.php';
		}
		if ( ! defined( '\\Deckerweb\\GitHubReleaseUpdater\\V2\\Updater::SUPPORTS_HOST_TRANSLATIONS' ) ) {
			add_action( 'admin_notices', array( $this, 'compatibility_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'compatibility_notice' ) );
			return;
		}
		try {
			$updater = new \Deckerweb\GitHubReleaseUpdater\V2\Updater(
				DDW_RDC_FILE,
				self::REPOSITORY,
				'Redirect Draft Content',
				__( 'Temporary draft redirects with individual destinations per content type.', 'redirect-draft-content' ),
				$this->artwork(),
				array( 'translate' => require dirname( DDW_RDC_FILE ) . '/includes/updater-translations.php' )
			);
		} catch ( \InvalidArgumentException $error ) {
			// An unsupported installation directory must not break draft routing.
			return;
		}
		$updater->register();
		add_filter( 'http_request_args', array( $this, 'request_limits' ), 20, 2 );
		add_filter( 'upgrader_source_selection', array( $this, 'validate_source' ), 30, 4 );
	}

	/**
	 * Explain an incompatible shared copy without exposing technical exceptions.
	 *
	 * @return void
	 */
	public function compatibility_notice(): void {
		if ( current_user_can( 'update_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Redirect Draft Content updates require a newer deckerweb Updater. Update the other deckerweb plugins or install the Redirect Draft Content ZIP manually.', 'redirect-draft-content' ) . '</p></div>';
		}
	}

	/**
	 * Match native single, automatic and bulk package-update contexts.
	 *
	 * @param mixed $upgrader WordPress upgrader instance.
	 * @param array $context Per-package upgrade metadata.
	 * @return bool Whether this package belongs to a Redirect Draft Content update.
	 */
	public function is_update_context( $upgrader, array $context ): bool {
		if ( ( $context['plugin'] ?? '' ) !== plugin_basename( DDW_RDC_FILE )
			|| ( isset( $context['type'] ) && 'plugin' !== $context['type'] )
			|| ( isset( $context['action'] ) && 'update' !== $context['action'] ) ) {
			return false;
		}
		return isset( $context['type'], $context['action'] ) || ( $upgrader instanceof \Plugin_Upgrader && true === $upgrader->bulk );
	}

	/**
	 * Provide bundled artwork matching the current administrator's language.
	 *
	 * @return array<string,array<string,string>> WordPress icon and banner maps.
	 */
	public function artwork(): array { return array(); }

	/**
	 * Bound only this repository's metadata requests; do not change package downloads.
	 *
	 * @param array  $args WordPress HTTP arguments.
	 * @param string $url Requested URL.
	 * @return array
	 */
	public function request_limits( array $args, string $url ): array {
		if ( 'https://api.github.com/repos/deckerweb/redirect-draft-content/releases/latest' === $url ) {
			$args['limit_response_size'] = 512 * 1024;
			$args['timeout']             = 6;
			$args['redirection']         = 0;
			$args['sslverify']           = true;
			$args['reject_unsafe_urls']  = true;
		}
		return $args;
	}

	/**
	 * Validate the actual candidate before WordPress removes the installed plugin.
	 * Requirements in installed headers cannot describe a future release reliably.
	 *
	 * @param mixed $source Normalized directory or WP_Error from the shared updater.
	 * @param mixed $remote_source Unused extraction root.
	 * @param mixed $upgrader Unused WordPress upgrader instance.
	 * @param array $hook_extra Upgrade context.
	 * @return mixed Original source or localized WP_Error.
	 */
	public function validate_source( $source, $remote_source, $upgrader, array $hook_extra ) {
		if ( ! $this->is_update_context( $upgrader, $hook_extra ) ) {
			return $source;
		}
		if ( is_wp_error( $source ) ) {
			$messages = array(
				'ddw_ghru_filesystem' => __( 'The update filesystem is unavailable. Please try again.', 'redirect-draft-content' ),
				'ddw_ghru_archive'    => __( 'The GitHub package does not contain the Redirect Draft Content plugin file.', 'redirect-draft-content' ),
				'ddw_ghru_rename'     => __( 'The GitHub package could not be prepared. The installed version has been kept.', 'redirect-draft-content' ),
			);
			$code     = $source->get_error_code();
			return isset( $messages[ $code ] ) ? new \WP_Error( $code, $messages[ $code ], $source->get_error_data( $code ) ) : $source;
		}
		global $wp_filesystem;
		if ( ! is_string( $source ) || ! $wp_filesystem ) {
			return new \WP_Error( 'ds_update_source', __( 'The update package could not be checked.', 'redirect-draft-content' ) );
		}
		$main = trailingslashit( $source ) . basename( DDW_RDC_FILE );
		if ( ! $wp_filesystem->is_file( $main ) || $wp_filesystem->size( $main ) > 1024 * 1024 ) {
			return new \WP_Error( 'ds_update_source', __( 'The update package could not be checked.', 'redirect-draft-content' ) );
		}
		$text = $wp_filesystem->get_contents( $main );
		if ( ! is_string( $text ) ) {
			return new \WP_Error( 'ds_update_source', __( 'The update package could not be checked.', 'redirect-draft-content' ) );
		}
		$headers = array();
		foreach ( array( 'Plugin Name', 'Version', 'Update URI', 'Requires PHP', 'Requires at least' ) as $header ) {
			$headers[ $header ] = preg_match( '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi', str_replace( "\r", "\n", substr( $text, 0, 8192 ) ), $match ) ? trim( $match[1] ) : '';
		}
		if ( 'Redirect Draft Content' !== $headers['Plugin Name'] || self::REPOSITORY !== $headers['Update URI'] || ! preg_match( '/^\d+\.\d+\.\d+$/D', $headers['Version'] ) || version_compare( $headers['Version'], DDW_RDC_VERSION, '<=' ) ) {
			return new \WP_Error( 'ds_update_identity', __( 'The package identity or version does not match a newer Redirect Draft Content release.', 'redirect-draft-content' ) );
		}
		$current  = get_site_transient( 'update_plugins' );
		$expected = is_object( $current ) ? ( $current->response[ plugin_basename( DDW_RDC_FILE ) ]->new_version ?? '' ) : '';
		if ( ! is_string( $expected ) || '' === $expected || $headers['Version'] !== $expected ) {
			return new \WP_Error( 'ds_update_version', __( 'The package version differs from the offered update. Please check for updates again.', 'redirect-draft-content' ) );
		}
		foreach ( array( 'Requires PHP', 'Requires at least' ) as $header ) {
			if ( ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/D', $headers[ $header ] ) ) {
				return new \WP_Error( 'ds_update_requirements', __( 'The update package has missing or invalid WordPress/PHP requirements.', 'redirect-draft-content' ) );
			}
		}
		if ( ! is_php_version_compatible( $headers['Requires PHP'] ) || ! is_wp_version_compatible( $headers['Requires at least'] ) ) {
			return new \WP_Error( 'ds_update_compatibility', __( 'This release requires a newer WordPress or PHP version. The installed version has been kept.', 'redirect-draft-content' ) );
		}
		return $source;
	}
}

( new GitHubUpdates() )->register();
