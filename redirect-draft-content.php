<?php
/**
 * Plugin Name: Redirect Draft Content
 * Plugin URI: https://github.com/deckerweb/redirect-draft-content
 * Description: Temporarily redirect draft content to a published item or a custom URL, with individual targets for posts, pages and public custom post types.
 * Version: 0.9.0
 * Requires at least: 7.1.2
 * Requires PHP: 8.2
 * Author: David Decker – DECKERWEB
 * Author URI: https://github.com/deckerweb
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: redirect-draft-content
 * Domain Path: /languages/
 * Update URI: https://github.com/deckerweb/redirect-draft-content
 * GitHub Plugin URI: https://github.com/deckerweb/redirect-draft-content
 *
 * Copyright © 2026 David Decker – DECKERWEB.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;
define( 'DDW_RDC_FILE', __FILE__ );
define( 'DDW_RDC_VERSION', '0.9.0' );
require_once __DIR__ . '/includes/deckerweb-plugin-library/bootstrap.php';
deckerweb_library_register_v2( __FILE__, array(), __DIR__ . '/includes/deckerweb-plugin-library' );
require_once __DIR__ . '/includes/updates.php';

/** Manage site-scoped draft redirects and their compact administration screen. */
class DDW_Redirect_Draft_Content {

	const OPTION_KEY = 'rdc_targets';

	/** Register site settings and frontend routing hooks. @return void */
	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_ddw_rdc_search', array( $this, 'search_targets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DDW_RDC_FILE ), array( $this, 'action_links' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
	}

	/**
	 * Loads the plugin's translations after WordPress initializes its locale.
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'redirect-draft-content', false, dirname( plugin_basename( DDW_RDC_FILE ) ) . '/languages' );
	}

	/**
	 * Returns the relevant post types: "post" and "page" are always
	 * included, all other (custom) post types only if their content is
	 * actually publicly viewable. is_post_type_viewable() checks not just
	 * "public" but also "publicly_queryable" – i.e. whether there is a
	 * viewable front-end URL at all. Attachments/media are always excluded.
	 *
	 * @param string $output 'names' or 'objects'
	 * @return array
	 */
	private function get_public_post_types( $output = 'names' ) {
		$always_include = array( 'post', 'page' );
		$all_types      = get_post_types( array(), 'objects' );
		$result         = array();

		foreach ( $all_types as $pt_name => $pt_object ) {
			if ( 'attachment' === $pt_name ) {
				continue;
			}

			if ( in_array( $pt_name, $always_include, true ) || is_post_type_viewable( $pt_object ) ) {
				$result[ $pt_name ] = $pt_object;
			}
		}

		if ( 'names' === $output ) {
			return array_keys( $result );
		}

		return $result;
	}

	/** Register the existing settings destination. @return void */
	public function add_settings_page() {
		add_options_page(
			'Redirect Draft Content',
			__( 'Draft Redirect', 'redirect-draft-content' ),
			'manage_options',
			'redirect-draft-content',
			array( $this, 'render_settings_page' )
		);
	}

	/** Register site-owned options with validation. @return void */
	public function register_settings() {
		register_setting( 'rdc_settings_group', self::OPTION_KEY, array(
			'type' => 'array',
			'show_in_rest' => false,
			'sanitize_callback' => array( $this, 'sanitize_targets' ),
			'default'           => array(),
		) );
	}

	/**
	 * Validate site-owned settings while retaining settings for inactive content types.
	 *
	 * @param mixed $input Submitted rows keyed by content type.
	 * @return array Validated settings using the existing rdc_targets schema.
	 */
	public function sanitize_targets( $input ) {
		$clean = get_option( self::OPTION_KEY, array() );
		$clean = is_array( $clean ) ? $clean : array();
		if ( ! current_user_can( 'manage_options' ) || ! is_array( $input ) ) { return $clean; }
		foreach ( $this->get_public_post_types() as $pt ) {
			if ( ! isset( $input[ $pt ] ) || ! is_array( $input[ $pt ] ) ) { continue; }
			$row = $input[ $pt ];
			$mode = ( $row['mode'] ?? '' ) === 'custom' ? 'custom' : 'existing';
			$id = is_scalar( $row['target_id'] ?? null ) ? absint( $row['target_id'] ) : 0;
			$target = $id ? get_post( $id ) : null;
			if ( $id && ( ! $target || $target->post_type !== $pt || $target->post_status !== 'publish' || $target->post_password !== '' ) ) {
				$id = 0;
				add_settings_error( self::OPTION_KEY, 'invalid_target_' . $pt, __( 'The selected target is no longer publicly available. Please select another target.', 'redirect-draft-content' ) );
			}
			$url = is_string( $row['custom_url'] ?? null ) ? $this->valid_url( $row['custom_url'] ) : '';
			if ( $mode === 'custom' && ! empty( $row['custom_url'] ) && $url === '' ) {
				add_settings_error( self::OPTION_KEY, 'invalid_url_' . $pt, __( 'Use a complete HTTP or HTTPS URL without credentials.', 'redirect-draft-content' ) );
			}
			$clean[ $pt ] = array( 'enabled' => ! empty( $row['enabled'] ), 'mode' => $mode, 'target_id' => $id, 'custom_url' => $url );
		}
		return $clean;
	}

	/**
	 * Allow absolute HTTP(S) destinations without credentials or control characters.
	 * External destinations are an intentional administrator-configured feature.
	 *
	 * @param string $url Untrusted destination.
	 * @return string Sanitized URL or an empty string.
	 */
	public function valid_url( string $url ): string {
		$url = trim( $url );
		if ( preg_match( '/[\x00-\x20\x7f]|%0[ad]/i', $url ) ) { return ''; }
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) { return ''; }
		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	/**
	 * Resolve a currently public, valid target using site settings.
	 * @param string $post_type Source content type.
	 * @return string Destination or empty string.
	 */
	private function get_target_url_for_type( $post_type ) {
		$targets = get_option( self::OPTION_KEY, array() );
		$config = is_array( $targets ) ? ( $targets[ $post_type ] ?? null ) : null;
		if ( ! is_array( $config ) || ( isset( $config['enabled'] ) && ! $config['enabled'] ) ) { return ''; }
		if ( ( $config['mode'] ?? '' ) === 'custom' ) { return is_string( $config['custom_url'] ?? null ) ? $this->valid_url( $config['custom_url'] ) : ''; }
		$target_id = is_scalar( $config['target_id'] ?? null ) ? absint( $config['target_id'] ) : 0;
		$target = $target_id ? get_post( $target_id ) : null;
		return $target && $target->post_type === $post_type && $target->post_status === 'publish' && $target->post_password === '' ? $this->valid_url( (string) get_permalink( $target ) ) : '';
	}

	/**
	 * Combined settings page: one block per post type with mode selection,
	 * target dropdown, custom URL field, and a live preview link.
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$targets           = get_option( self::OPTION_KEY, array() );
		$targets = is_array( $targets ) ? $targets : array();
		$post_type_objects = $this->get_public_post_types( 'objects' );
		?>
		<div class="wrap rdc-settings">
			<h1>Redirect Draft Content</h1>
			<p class="rdc-series">deckerweb · Manage Content · 0.9.0</p>
			<p><?php esc_html_e( 'Define, for each content type, where requests for content in draft status should be redirected to via a 302 redirect – either to an existing published item or to a custom URL.', 'redirect-draft-content' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'rdc_settings_group' ); ?>

				<?php foreach ( $post_type_objects as $pt ) :
					$pt_name     = $pt->name;
					$config      = wp_parse_args( is_array( $targets[ $pt_name ] ?? null ) ? $targets[ $pt_name ] : array(), array( 'enabled' => true, 'mode' => 'existing', 'target_id' => 0, 'custom_url' => '' ) );
					$mode        = $config['mode'] === 'custom' ? 'custom' : 'existing';
					$target_id   = is_scalar( $config['target_id'] ) ? absint( $config['target_id'] ) : 0;
					$custom_url  = is_string( $config['custom_url'] ) ? $config['custom_url'] : '';

					$items = get_posts( array(
						'post_type'      => $pt_name,
						'post_status'    => 'publish',
						'numberposts'    => 30,
					'post_password' => '',
					'no_found_rows' => true,
						'orderby'        => 'title',
						'order'          => 'ASC',
					) );
					$selected_target = $target_id ? get_post( $target_id ) : null;
					if ( $selected_target && $selected_target->post_type === $pt_name && $selected_target->post_status === 'publish' && $selected_target->post_password === '' && ! in_array( $target_id, wp_list_pluck( $items, 'ID' ), true ) ) { $items[] = $selected_target; }
					?>
					<h2 class="title"><?php echo esc_html( $pt->labels->name ); ?></h2>
					<table class="form-table rdc-post-type-block" role="presentation" data-posttype="<?php echo esc_attr( $pt_name ); ?>">
						<tr><th scope="row"><?php esc_html_e( 'Redirects', 'redirect-draft-content' ); ?></th><td><label><input type="checkbox" name="rdc_targets[<?php echo esc_attr( $pt_name ); ?>][enabled]" value="1" <?php checked( ! empty( $config['enabled'] ) ); ?>> <?php esc_html_e( 'Enable redirects for this content type', 'redirect-draft-content' ); ?></label></td></tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Target Mode', 'redirect-draft-content' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" class="rdc-mode-radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $pt_name ); ?>][mode]" value="existing" <?php checked( $mode, 'existing' ); ?> />
										<?php esc_html_e( 'Choose an existing, published item', 'redirect-draft-content' ); ?>
									</label><br />
									<label>
										<input type="radio" class="rdc-mode-radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $pt_name ); ?>][mode]" value="custom" <?php checked( $mode, 'custom' ); ?> />
										<?php esc_html_e( 'Use a custom URL', 'redirect-draft-content' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr class="rdc-row-existing">
							<th scope="row">
								<label for="rdc_target_<?php echo esc_attr( $pt_name ); ?>"><?php esc_html_e( 'Target Content', 'redirect-draft-content' ); ?></label>
							</th>
							<td>
								<p><label><?php esc_html_e( 'Search published content', 'redirect-draft-content' ); ?><br><input type="search" class="rdc-search regular-text" autocomplete="off"></label> <button type="button" class="button rdc-search-button"><?php esc_html_e( 'Search', 'redirect-draft-content' ); ?></button> <button type="button" class="button rdc-more" hidden><?php esc_html_e( 'More results', 'redirect-draft-content' ); ?></button></p>
								<p class="rdc-search-status" role="status" aria-live="polite"></p>
								<?php if ( empty( $items ) ) : ?>
									<p class="description"><?php esc_html_e( 'No published content of this type exists yet.', 'redirect-draft-content' ); ?></p>
								<?php endif; ?>
									<select class="rdc-target-select" id="rdc_target_<?php echo esc_attr( $pt_name ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $pt_name ); ?>][target_id]">
										<option value="0" data-permalink=""><?php esc_html_e( '— Please select —', 'redirect-draft-content' ); ?></option>
										<?php foreach ( $items as $item ) : ?>
											<option value="<?php echo esc_attr( $item->ID ); ?>" data-permalink="<?php echo esc_url( get_permalink( $item->ID ) ); ?>" <?php selected( $target_id, $item->ID ); ?>>
												<?php
												/* translators: 1: post title, 2: post ID */
												echo esc_html( sprintf( __( '%1$s (ID: %2$d)', 'redirect-draft-content' ), $item->post_title, $item->ID ) );
												?>
											</option>
										<?php endforeach; ?>
									</select>
							</td>
						</tr>
						<tr class="rdc-row-custom">
							<th scope="row">
								<label for="rdc_custom_url_<?php echo esc_attr( $pt_name ); ?>"><?php esc_html_e( 'Custom URL', 'redirect-draft-content' ); ?></label>
							</th>
							<td>
								<input type="url" class="regular-text rdc-custom-url" id="rdc_custom_url_<?php echo esc_attr( $pt_name ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $pt_name ); ?>][custom_url]" value="<?php echo esc_attr( $custom_url ); ?>" placeholder="https://example.com/target-page/" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Preview', 'redirect-draft-content' ); ?></th>
							<td>
								<a href="#" class="button rdc-preview-link" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open target URL in new tab ↗', 'redirect-draft-content' ); ?></a>
								<p class="description rdc-preview-empty" style="display:none;"><?php esc_html_e( 'Please select a target or enter a URL first.', 'redirect-draft-content' ); ?></p>
							</td>
						</tr>
					</table>
				<?php endforeach; ?>

				<p><?php esc_html_e( 'External URLs are allowed. Drafts remain available to editors and in previews. Redirects use HTTP 302 and are not cached.', 'redirect-draft-content' ); ?></p>
				<?php submit_button(); ?>
			</form>
		</div>

		<?php $this->footer(); ?>
		<?php
	}

	/**
	 * Load local assets exclusively on this plugin's settings screen.
	 * @param string $hook Current administration screen hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== 'settings_page_redirect-draft-content' ) { return; }
		wp_enqueue_style( 'ddw-rdc-admin', plugins_url( 'assets/admin.css', DDW_RDC_FILE ), array(), DDW_RDC_VERSION );
		wp_enqueue_script( 'ddw-rdc-admin', plugins_url( 'assets/admin.js', DDW_RDC_FILE ), array(), DDW_RDC_VERSION, true );
		wp_localize_script( 'ddw-rdc-admin', 'ddwRdc', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'ddw_rdc_search' ), 'loading' => __( 'Searching…', 'redirect-draft-content' ), 'error' => __( 'Search failed. Please try again.', 'redirect-draft-content' ), 'count' => __( 'Results loaded. Select a target below.', 'redirect-draft-content' ) ) );
	}

	/**
	 * Add the principal settings link before WordPress's Deactivate action.
	 * @param array $links Existing action links.
	 * @return array Updated links for authorized site administrators.
	 */
	public function action_links( array $links ): array {
		if ( current_user_can( 'manage_options' ) && ! is_network_admin() ) { array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=redirect-draft-content' ) ) . '">' . esc_html__( 'Settings', 'redirect-draft-content' ) . '</a>' ); }
		return $links;
	}

	/**
	 * Search published destinations in bounded pages; requires site permissions and a nonce.
	 * @return void Sends a JSON response and terminates the request.
	 */
	public function search_targets(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( null, 403 ); }
		check_ajax_referer( 'ddw_rdc_search', 'nonce' );
		$pt = isset( $_POST['post_type'] ) && is_string( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';
		if ( ! in_array( $pt, $this->get_public_post_types(), true ) ) { wp_send_json_error( null, 400 ); }
		$term = isset( $_POST['term'] ) && is_string( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page = isset( $_POST['page'] ) && is_scalar( $_POST['page'] ) ? min( 1000, max( 1, absint( $_POST['page'] ) ) ) : 1;
		$query = new WP_Query( array( 'post_type' => $pt, 'post_status' => 'publish', 'post_password' => '', 's' => substr( $term, 0, 200 ), 'posts_per_page' => 30, 'paged' => $page, 'orderby' => 'title ID', 'order' => 'ASC', 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false ) );
		$items = array();
		foreach ( $query->posts as $post ) { $items[] = array( 'id' => $post->ID, 'label' => sprintf( __( '%1$s (ID: %2$d)', 'redirect-draft-content' ), $post->post_title ?: __( '(Untitled)', 'redirect-draft-content' ), $post->ID ), 'url' => get_permalink( $post ) ); }
		wp_send_json_success( array( 'items' => $items, 'more' => count( $items ) === 30 ) );
	}

	/** Render a compact footer and keyboard-accessible local changelog. @return void */
	public function footer(): void {
		?>
		<footer class="rdc-footer"><span>Redirect Draft Content · 0.9.0 · David Decker – DECKERWEB</span> · <a href="https://github.com/deckerweb/redirect-draft-content"><?php esc_html_e( 'Documentation', 'redirect-draft-content' ); ?></a> · <button type="button" class="button-link" id="rdc-history-open"><?php esc_html_e( 'Changelog', 'redirect-draft-content' ); ?></button> · <a href="https://ko-fi.com/deckerweb"><?php esc_html_e( 'Support this plugin', 'redirect-draft-content' ); ?></a></footer>
		<dialog id="rdc-history" aria-labelledby="rdc-history-title"><h2 id="rdc-history-title"><?php esc_html_e( 'Changelog', 'redirect-draft-content' ); ?></h2>
		<?php
		$history = json_decode( (string) file_get_contents( __DIR__ . '/history.json' ), true );
		$categories = array( 'New:' => __( 'New:', 'redirect-draft-content' ), 'Improved:' => __( 'Improved:', 'redirect-draft-content' ), 'Fixed:' => __( 'Fixed:', 'redirect-draft-content' ), 'Misc:' => __( 'Misc:', 'redirect-draft-content' ) );
		foreach ( is_array( $history ) ? $history : array() as $release ) {
			echo '<h3>' . esc_html( $release['version'] ) . ' <small>' . esc_html( $release['date'] ) . '</small></h3>';
			foreach ( $categories as $category => $label ) {
				if ( empty( $release['changes'][ $category ] ) ) { continue; }
				echo '<span class="rdc-badge rdc-' . esc_attr( strtolower( rtrim( $category, ':' ) ) ) . '">' . esc_html( $label ) . '</span><ul>';
				foreach ( $release['changes'][ $category ] as $message ) { echo '<li>' . esc_html( translate( $message, 'redirect-draft-content' ) ) . '</li>'; }
				echo '</ul>';
			}
		}
		?>
		<button type="button" class="button" id="rdc-history-close"><?php esc_html_e( 'Close', 'redirect-draft-content' ); ?></button></dialog>
		<?php
	}

	/**
	 * Checks whether the requested URL belongs to content in draft status
	 * (post, page, or a publicly viewable custom post type) and, if so,
	 * redirects to the target configured for that content type via a
	 * 302 redirect.
	 * @return void
	 */
	public function maybe_redirect() {
		$url = $this->redirect_url();
		if ( $url && ! headers_sent() ) {
			nocache_headers();
			if ( wp_redirect( $url, 302, 'Redirect Draft Content' ) ) { exit; }
		}
	}

	/**
	 * Resolve only a matching draft request; published content and unrelated 404s stay intact.
	 *
	 * @return string Valid destination or an empty string; no headers are sent here.
	 */
	public function redirect_url(): string {
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) || is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_preview() || is_feed() || is_trackback() || is_embed() || ( ! is_404() && ! is_singular() ) || current_user_can( 'edit_posts' ) ) { return ''; }
		$object = get_queried_object();
		if ( $object instanceof WP_Post && $object->post_status !== 'draft' ) { return ''; }
		$types = $this->get_public_post_types();
		$targets = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $targets ) || ! $targets ) { return ''; }
		/**
		 * @param string $type Registered source content type.
		 * @return bool Whether this type has an enabled, valid target.
		 */
		$types = array_values( array_filter( $types, function ( $type ) use ( $targets ) {
			return is_array( $targets[ $type ] ?? null ) && ( ! isset( $targets[ $type ]['enabled'] ) || $targets[ $type ]['enabled'] ) && $this->get_target_url_for_type( $type ) !== '';
		} ) );
		if ( ! $types ) { return ''; }
		$draft = null;
		$id = absint( get_query_var( 'p' ) ?: get_query_var( 'page_id' ) );
		if ( $id ) {
			$candidate = get_post( $id );
			if ( $candidate && $candidate->post_status === 'draft' && in_array( $candidate->post_type, $types, true ) && ( ! get_query_var( 'post_type' ) || get_query_var( 'post_type' ) === $candidate->post_type ) && ( ! get_query_var( 'page_id' ) || $candidate->post_type === 'page' ) ) { $draft = $candidate; }
		} else {
			$type = get_query_var( 'post_type' );
			if ( is_string( $type ) && $type !== '' ) { $types = array_values( array_intersect( $types, array( $type ) ) ); }
			global $wp;
			// WP_Query reduces hierarchical names to a basename; retain the parsed route.
			$path = $wp->query_vars['pagename'] ?? get_query_var( 'pagename' );
			if ( is_string( $type ) && $type !== '' && is_post_type_hierarchical( $type ) ) {
				$object_type = get_post_type_object( $type );
				if ( $object_type && is_string( $object_type->query_var ) ) { $path = $wp->query_vars[ $object_type->query_var ] ?? $path; }
			}
			$name = get_query_var( 'name' );
			if ( ! is_string( $path ) || ! is_string( $name ) || strlen( $path ) > 2048 || strlen( $name ) > 200 ) { return ''; }
			$candidates = array();
			foreach ( $types as $pt ) {
				if ( is_post_type_hierarchical( $pt ) && ( $path || $name ) ) {
					$found = get_page_by_path( $path ?: $name, OBJECT, $pt );
					if ( $found && $found->post_status === 'draft' ) { $candidates[] = $found; }
				} elseif ( $name ) {
					$query = new WP_Query( array( 'name' => $name, 'post_type' => $pt, 'post_status' => 'draft', 'posts_per_page' => 2, 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false, 'ignore_sticky_posts' => true ) );
					$candidates = array_merge( $candidates, $query->posts );
				}
			}
			if ( $candidates ) {
				require_once ABSPATH . 'wp-admin/includes/post.php';
				foreach ( $candidates as $candidate ) {
					$sample = get_sample_permalink( $candidate->ID );
					$canonical = str_replace( array( '%postname%', '%pagename%' ), $sample[1], $sample[0] );
					if ( $this->request_matches( $canonical ) ) {
						if ( $draft ) { return ''; } // Ambiguous routes must never select an arbitrary draft.
						$draft = $candidate;
					}
				}
			}
		}
		if ( ! $draft || current_user_can( 'edit_post', $draft->ID ) ) { return ''; }
		$url = $this->get_target_url_for_type( $draft->post_type );
		if ( ! $url || $this->request_matches( $url ) ) { return ''; }
		// A custom local URL resolving to any draft would create a loop or a broken destination.
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) === strtolower( (string) $home ) ) {
			if ( $this->local_target_is_unavailable( $url ) ) { return ''; }
		}
		return $url;
	}

	/**
	 * Reject local URLs that resolve to drafts, including pretty permalinks omitted by url_to_postid.
	 *
	 * @param string $url Validated local target URL.
	 * @return bool Whether a known target is not publicly available.
	 */
	private function local_target_is_unavailable( string $url ): bool {
		$id = url_to_postid( $url );
		if ( $id ) {
			$post = get_post( $id );
			return ! $post || $post->post_status !== 'publish' || $post->post_password !== '';
		}
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$id = is_scalar( $query['p'] ?? $query['page_id'] ?? null ) ? absint( $query['p'] ?? $query['page_id'] ) : 0;
		if ( $id ) {
			$post = get_post( $id );
			return $post && ( $post->post_status !== 'publish' || $post->post_password !== '' );
		}
		$path = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$slug = sanitize_title( rawurldecode( basename( $path ) ) );
		if ( $slug === '' ) { return false; }
		$query = new WP_Query( array( 'name' => $slug, 'post_type' => $this->get_public_post_types(), 'post_status' => array( 'draft', 'pending', 'future', 'private' ), 'posts_per_page' => 20, 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false ) );
		if ( ! $query->posts ) { return false; }
		require_once ABSPATH . 'wp-admin/includes/post.php';
		foreach ( $query->posts as $post ) {
			$sample = get_sample_permalink( $post->ID );
			$canonical = str_replace( array( '%postname%', '%pagename%' ), $sample[1], $sample[0] );
			if ( untrailingslashit( rawurldecode( (string) wp_parse_url( $canonical, PHP_URL_PATH ) ) ) === rawurldecode( $path ) ) { return true; }
		}
		// Too many candidates cannot be safely resolved within the bounded query.
		return count( $query->posts ) === 20;
	}

	/**
	 * Compare a destination with the current path and its routing query parameters.
	 * Tracking parameters are ignored; meaningful query arguments remain significant.
	 *
	 * @param string $url Absolute canonical URL.
	 * @return bool Whether the URL matches the current request.
	 */
	public function request_matches( string $url ): bool {
		$parts = wp_parse_url( $url );
		$home = wp_parse_url( home_url() );
		$request = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
		if ( ! is_array( $parts ) || ! is_array( $home ) || ! is_array( $request ) || strtolower( $parts['host'] ?? '' ) !== strtolower( $home['host'] ?? '' ) || ( $parts['port'] ?? null ) !== ( $home['port'] ?? null ) ) { return false; }
		if ( untrailingslashit( rawurldecode( $parts['path'] ?? '/' ) ) !== untrailingslashit( rawurldecode( $request['path'] ?? '/' ) ) ) { return false; }
		parse_str( $parts['query'] ?? '', $expected );
		parse_str( $request['query'] ?? '', $actual );
		foreach ( array_keys( $actual ) as $key ) { if ( str_starts_with( $key, 'utm_' ) || in_array( $key, array( 'gclid', 'fbclid' ), true ) ) { unset( $actual[ $key ] ); } }
		ksort( $expected ); ksort( $actual );
		return $expected === $actual;
	}
}

$GLOBALS['ddw_rdc_plugin'] = new DDW_Redirect_Draft_Content();
