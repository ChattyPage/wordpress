<?php
/**
 * ChattyPage_Admin — the wp-admin surface: a top-level "ChattyPage" page with
 *  - connection settings (paste the integration token from My Account → Integrations),
 *  - the section browser (name, updated, copy-shortcode, Edit in ChattyPage deep link),
 *  - cache controls (refresh now).
 * All actions are classic admin-post form submits with nonces — no custom JS needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_chattypage_connect', array( __CLASS__, 'handle_connect' ) );
		add_action( 'admin_post_chattypage_disconnect', array( __CLASS__, 'handle_disconnect' ) );
		add_action( 'admin_post_chattypage_refresh', array( __CLASS__, 'handle_refresh' ) );
		add_action( 'admin_post_chattypage_redesign', array( __CLASS__, 'handle_redesign' ) );
		add_action( 'admin_post_chattypage_takeover', array( __CLASS__, 'handle_takeover' ) );
	}

	public static function register_menu() {
		add_menu_page(
			__( 'ChattyPage', 'chattypage' ),
			__( 'ChattyPage', 'chattypage' ),
			'manage_options',
			'chattypage',
			array( __CLASS__, 'render_page' ),
			'dashicons-layout',
			59
		);
	}

	public static function handle_connect() {
		check_admin_referer( 'chattypage_connect' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'chattypage' ) );
		}
		$token  = isset( $_POST['chattypage_token'] ) ? trim( (string) wp_unslash( $_POST['chattypage_token'] ) ) : '';
		$result = ChattyPage_Api_Client::connect( $token );
		$query  = is_wp_error( $result )
			? array( 'page' => 'chattypage', 'cp_error' => rawurlencode( $result->get_error_message() ) )
			: array( 'page' => 'chattypage', 'cp_connected' => '1' );
		wp_safe_redirect( add_query_arg( $query, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_disconnect() {
		check_admin_referer( 'chattypage_disconnect' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'chattypage' ) );
		}
		ChattyPage_Api_Client::disconnect();
		wp_safe_redirect( add_query_arg( array( 'page' => 'chattypage' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_refresh() {
		check_admin_referer( 'chattypage_refresh' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'chattypage' ) );
		}
		ChattyPage_Renderer::flush_all();
		ChattyPage_Api_Client::sections( true );
		wp_safe_redirect( add_query_arg( array( 'page' => 'chattypage', 'cp_refreshed' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_redesign() {
		check_admin_referer( 'chattypage_redesign' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'chattypage' ) );
		}
		$url    = isset( $_POST['chattypage_url'] ) ? esc_url_raw( (string) wp_unslash( $_POST['chattypage_url'] ) ) : '';
		$result = ChattyPage_Api_Client::redesign( $url );
		$query  = is_wp_error( $result )
			? array( 'page' => 'chattypage', 'cp_error' => rawurlencode( $result->get_error_message() ) )
			: array( 'page' => 'chattypage', 'cp_redesign' => '1' );
		wp_safe_redirect( add_query_arg( $query, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_takeover() {
		check_admin_referer( 'chattypage_takeover' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'chattypage' ) );
		}
		ChattyPage_Template::set_enabled( ! empty( $_POST['chattypage_takeover_on'] ) );
		wp_safe_redirect( add_query_arg( array( 'page' => 'chattypage', 'cp_takeover' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render_page() {
		$settings  = ChattyPage_Api_Client::settings();
		$connected = ChattyPage_Api_Client::is_connected();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ChattyPage Sections', 'chattypage' ); ?></h1>

			<?php if ( isset( $_GET['cp_error'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( rawurldecode( (string) $_GET['cp_error'] ) ); ?></p></div>
			<?php elseif ( isset( $_GET['cp_connected'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Connected to ChattyPage. Your sections are ready to place.', 'chattypage' ); ?></p></div>
			<?php elseif ( isset( $_GET['cp_refreshed'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Section caches refreshed.', 'chattypage' ); ?></p></div>
			<?php elseif ( isset( $_GET['cp_redesign'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Redesign started. In a minute or two your new section appears below and in your ChattyPage editor, ready to place and fine-tune.', 'chattypage' ); ?></p></div>
			<?php elseif ( isset( $_GET['cp_takeover'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Saved. Reload your site to see the change.', 'chattypage' ); ?></p></div>
			<?php endif; ?>

			<?php if ( ! $connected ) : ?>
				<div class="card" style="max-width:640px">
					<h2><?php esc_html_e( 'Connect your ChattyPage account', 'chattypage' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: %s: link to the ChattyPage integrations settings */
							esc_html__( 'Create an API token in %s, then paste it here. Your WordPress site keeps running as-is; ChattyPage designs the sections.', 'chattypage' ),
							'<a href="' . esc_url( CHATTYPAGE_APP_BASE . '/account' ) . '" target="_blank" rel="noopener">' . esc_html__( 'ChattyPage → My Account → Integrations', 'chattypage' ) . '</a>'
						);
						?>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'chattypage_connect' ); ?>
						<input type="hidden" name="action" value="chattypage_connect" />
						<p>
							<input type="password" name="chattypage_token" class="regular-text" style="width:100%"
								placeholder="cp_live_…" autocomplete="off" required />
						</p>
						<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Connect', 'chattypage' ); ?></button></p>
					</form>
				</div>
			<?php else : ?>
				<?php
				$me       = ChattyPage_Api_Client::me();
				$sections = ChattyPage_Api_Client::sections();
				$edit_url = CHATTYPAGE_APP_BASE . '/designables/' . rawurlencode( $settings['designable_id'] )
					. '/pages/' . rawurlencode( $settings['page_id'] );
				?>
				<p>
					<?php esc_html_e( 'Connected.', 'chattypage' ); ?>
					<?php if ( ! is_wp_error( $me ) && isset( $me['credits'] ) ) : ?>
						<?php printf( esc_html__( 'Credits available: %s.', 'chattypage' ), esc_html( number_format_i18n( (float) $me['credits'] ) ) ); ?>
					<?php endif; ?>
					<a class="button button-primary" href="<?php echo esc_url( $edit_url ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Design sections in ChattyPage', 'chattypage' ); ?>
					</a>
				</p>

				<div class="card" style="max-width:640px;margin:16px 0 24px">
					<h2><?php esc_html_e( 'Site design', 'chattypage' ); ?></h2>
					<p><?php esc_html_e( 'Let ChattyPage design your whole site: your pages and posts keep their content, wrapped in the header, footer, and typography from your ChattyPage design. Your current theme stays installed; switch back anytime.', 'chattypage' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'chattypage_takeover' ); ?>
						<input type="hidden" name="action" value="chattypage_takeover" />
						<label style="display:block;margin:8px 0 12px">
							<input type="checkbox" name="chattypage_takeover_on" value="1" <?php checked( ChattyPage_Template::is_enabled() ); ?> />
							<?php esc_html_e( 'Use my ChattyPage design for the whole site', 'chattypage' ); ?>
						</label>
						<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'chattypage' ); ?></button></p>
					</form>
				</div>

				<div class="card" style="max-width:640px;margin:16px 0 24px">
					<h2><?php esc_html_e( 'Redesign a page with AI', 'chattypage' ); ?></h2>
					<p><?php esc_html_e( 'Pick one of your pages (or paste any public URL) and ChattyPage designs a modern section from its real content. Uses credits from your account.', 'chattypage' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'chattypage_redesign' ); ?>
						<input type="hidden" name="action" value="chattypage_redesign" />
						<p>
							<select name="chattypage_url" class="regular-text" style="width:100%">
								<option value="<?php echo esc_attr( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'chattypage' ); ?></option>
								<?php foreach ( get_pages( array( 'number' => 30 ) ) as $p ) : ?>
									<option value="<?php echo esc_attr( get_permalink( $p ) ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Redesign with ChattyPage', 'chattypage' ); ?></button></p>
					</form>
				</div>

				<h2><?php esc_html_e( 'Your sections', 'chattypage' ); ?></h2>
				<?php if ( is_wp_error( $sections ) ) : ?>
					<div class="notice notice-error"><p><?php echo esc_html( $sections->get_error_message() ); ?></p></div>
				<?php elseif ( empty( $sections ) ) : ?>
					<p>
						<?php esc_html_e( 'No sections yet.', 'chattypage' ); ?>
						<a href="<?php echo esc_url( $edit_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Create your first section in ChattyPage', 'chattypage' ); ?></a>
					</p>
				<?php else : ?>
					<table class="widefat striped" style="max-width:900px">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Section', 'chattypage' ); ?></th>
								<th><?php esc_html_e( 'Shortcode', 'chattypage' ); ?></th>
								<th><?php esc_html_e( 'Updated', 'chattypage' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $sections as $section ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $section['name'] ); ?></strong></td>
									<td><code>[chattypage section="<?php echo esc_attr( $section['id'] ); ?>"]</code></td>
									<td>
										<?php
										echo isset( $section['updated_at'] )
											? esc_html( mysql2date( get_option( 'date_format' ), $section['updated_at'] ) )
											: '-';
										?>
									</td>
									<td>
										<a href="<?php echo esc_url( $edit_url . '?block=' . rawurlencode( $section['id'] ) ); ?>" target="_blank" rel="noopener">
											<?php esc_html_e( 'Edit in ChattyPage', 'chattypage' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<p style="margin-top:16px">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
						<?php wp_nonce_field( 'chattypage_refresh' ); ?>
						<input type="hidden" name="action" value="chattypage_refresh" />
						<button type="submit" class="button"><?php esc_html_e( 'Refresh section caches', 'chattypage' ); ?></button>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:8px"
						onsubmit="return confirm('<?php echo esc_js( __( 'Disconnect this site from ChattyPage?', 'chattypage' ) ); ?>');">
						<?php wp_nonce_field( 'chattypage_disconnect' ); ?>
						<input type="hidden" name="action" value="chattypage_disconnect" />
						<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Disconnect', 'chattypage' ); ?></button>
					</form>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
