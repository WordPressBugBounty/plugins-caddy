<?php
/**
 * Pulse - optional telemetry client.
 *
 * Opt-in (default OFF), license-independent, wp.org Guideline-7 compliant:
 * an informed consent prompt with disclosure + privacy link, never pre-checked.
 * When enabled, sends a throttled environment + usage snapshot to the hub.
 *
 * @package Pulse_Client
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Pulse_Telemetry_Client' ) ) {

	/**
	 * Consent surface + periodic telemetry sender.
	 */
	class Pulse_Telemetry_Client {

		const SEND_INTERVAL = WEEK_IN_SECONDS;

		/**
		 * Config.
		 *
		 * @var array
		 */
		private $args;

		/**
		 * Constructor.
		 *
		 * @param array $args brand, plugin_slug, hub_url, tier, plugin_version, privacy_url,
		 *                    custom_data (callable returning a features array), parent_slug
		 *                    (admin menu the consent screen hangs off), settings_url (where to
		 *                    return after answering), collects_license (disclose the license key).
		 */
		public function __construct( $args ) {
			$this->args = wp_parse_args(
				$args,
				array(
					'brand'            => '',
					'plugin_slug'      => '',
					'hub_url'          => '',
					'license_key'      => '',
					'tier'             => '',
					'plugin_version'   => '',
					'privacy_url'      => '',
					'branding'         => array(),
					'custom_data'      => null,
					'parent_slug'      => '',
					'settings_url'     => '',
					'collects_license' => false,
				)
			);

			add_action( 'admin_notices', array( $this, 'consent_notice' ) );
			add_action( 'wp_ajax_' . $this->action(), array( $this, 'ajax_optin' ) );
			add_action( 'admin_init', array( $this, 'maybe_send' ) );
			// Post-activation consent screen: registered hidden, reached by the
			// one-time redirect the host plugin arms in its activation hook.
			add_action( 'admin_menu', array( $this, 'register_consent_screen' ), 99 );
			add_action( 'admin_head', array( $this, 'hide_consent_screen_menu_item' ) );
			add_action( 'admin_init', array( $this, 'maybe_redirect_to_consent_screen' ), 1 );
			add_action( 'admin_post_' . $this->screen_action(), array( $this, 'handle_consent_screen' ) );
			// Ongoing opt-out control: a host plugin renders it on its settings
			// page with do_action( 'pulse_telemetry_settings_{slug}' ).
			add_action( 'pulse_telemetry_settings_' . sanitize_key( $this->args['plugin_slug'] ), array( $this, 'render_settings_toggle' ) );
		}

		/**
		 * Unique AJAX action.
		 *
		 * @return string
		 */
		private function action() {
			return 'pulse_tel_optin_' . sanitize_key( $this->args['plugin_slug'] );
		}

		/**
		 * Form action for the post-activation consent screen.
		 *
		 * @return string
		 */
		private function screen_action() {
			return $this->action() . '_screen';
		}

		/**
		 * Admin page slug for the consent screen.
		 *
		 * @return string
		 */
		private function screen_slug() {
			return sanitize_key( $this->args['plugin_slug'] ) . '-privacy';
		}

		/**
		 * Menu the consent screen is registered under.
		 *
		 * @return string
		 */
		private function parent_slug() {
			return $this->args['parent_slug'] ? $this->args['parent_slug'] : sanitize_key( $this->args['plugin_slug'] );
		}

		/**
		 * Option key holding the one-time "show the consent screen" flag.
		 *
		 * @param string $slug Plugin slug.
		 * @return string
		 */
		public static function redirect_key( $slug ) {
			return 'pulse_tel_activation_redirect_' . sanitize_key( $slug );
		}

		/**
		 * Option key recording that the consent screen has been shown, so the
		 * fallback admin notice stops asking.
		 *
		 * @param string $slug Plugin slug.
		 * @return string
		 */
		public static function screen_shown_key( $slug ) {
			return 'pulse_tel_screen_shown_' . sanitize_key( $slug );
		}

		/**
		 * Arm the post-activation consent screen. Call from the host plugin's
		 * activation hook; a site that already answered is never asked again.
		 *
		 * @param string $slug Plugin slug.
		 */
		public static function flag_activation( $slug ) {
			if ( false !== get_option( Pulse_Client::telemetry_optin_key( $slug ), false ) ) {
				return;
			}
			update_option( self::redirect_key( $slug ), 1, false );
		}

		/**
		 * Send the admin to the consent screen once, on the first admin page load
		 * after activation. Mirrors the plugin-activated redirect pattern: the flag
		 * is consumed whether or not the redirect happens, so it can never loop.
		 */
		public function maybe_redirect_to_consent_screen() {
			if ( wp_doing_ajax() || is_network_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( ! get_option( self::redirect_key( $this->args['plugin_slug'] ) ) ) {
				return;
			}

			delete_option( self::redirect_key( $this->args['plugin_slug'] ) );

			// Bulk activation: hijacking the plugins screen after a multi-activate is
			// hostile, so fall back to the admin notice.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading WP's own activation flag, no state change.
			if ( isset( $_GET['activate-multi'] ) ) {
				return;
			}
			if ( false !== get_option( Pulse_Client::telemetry_optin_key( $this->args['plugin_slug'] ), false ) ) {
				return;
			}

			wp_safe_redirect( $this->screen_url() );
			exit;
		}

		/**
		 * URL of the consent screen.
		 *
		 * @return string
		 */
		private function screen_url() {
			return admin_url( 'admin.php?page=' . $this->screen_slug() );
		}

		/**
		 * Where to send the admin after answering.
		 *
		 * @return string
		 */
		private function return_url() {
			return $this->args['settings_url'] ? $this->args['settings_url'] : admin_url( 'admin.php?page=' . $this->parent_slug() );
		}

		/**
		 * Register the consent screen under the plugin's own menu. It is only ever
		 * reached by the post-activation redirect, so the menu entry itself is
		 * dropped later (see hide_consent_screen_menu_item).
		 */
		public function register_consent_screen() {
			$title = ! empty( $this->args['branding']['name'] ) ? $this->args['branding']['name'] : ucfirst( $this->args['plugin_slug'] );

			$hook = add_submenu_page(
				$this->parent_slug(),
				/* translators: %s: plugin name. */
				sprintf( __( '%s Privacy', 'caddy' ), $title ),
				/* translators: %s: plugin name. */
				sprintf( __( '%s Privacy', 'caddy' ), $title ),
				'manage_options',
				$this->screen_slug(),
				array( $this, 'render_consent_screen' )
			);

			// Mark the ask as made before notices render, so the fallback notice
			// never stacks on top of the screen that replaced it.
			if ( $hook ) {
				add_action( 'load-' . $hook, array( $this, 'mark_consent_screen_shown' ) );
			}
		}

		/**
		 * Record that the consent screen has been presented.
		 */
		public function mark_consent_screen_shown() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			update_option( self::screen_shown_key( $this->args['plugin_slug'] ), 1, false );
		}

		/**
		 * Drop the consent screen from the visible menu. Done on admin_head rather
		 * than right after registering it: WP's page-access check reads the submenu
		 * array while loading the page, so removing the entry any earlier would make
		 * the screen itself 403. admin_head still runs before the menu is rendered.
		 */
		public function hide_consent_screen_menu_item() {
			remove_submenu_page( $this->parent_slug(), $this->screen_slug() );
		}

		/**
		 * Data points disclosed on the consent screen.
		 *
		 * @return array
		 */
		private function collected_items() {
			$items = array(
				__( 'Environment: WordPress, PHP, WooCommerce, theme, language', 'caddy' ),
				__( 'Which plugin features are switched on', 'caddy' ),
				__( 'Site URL, plugin version, and install age', 'caddy' ),
			);

			if ( $this->args['collects_license'] ) {
				$items[] = __( 'Your license key', 'caddy' );
			}

			return $items;
		}

		/**
		 * The post-activation consent screen.
		 */
		public function render_consent_screen() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to view this page.', 'caddy' ) );
			}

			$name = ! empty( $this->args['branding']['name'] ) ? $this->args['branding']['name'] : ucfirst( $this->args['plugin_slug'] );
			$logo = ! empty( $this->args['branding']['logo'] ) ? $this->args['branding']['logo'] : '';

			// With a logo above it the heading does not need to repeat the plugin
			// name; without one it is the only place the plugin is identified.
			$title = $logo
				? __( 'Thanks for installing! Help us improve?', 'caddy' )
				/* translators: %s: plugin name. */
				: sprintf( __( 'Thanks for installing %s! Help us improve?', 'caddy' ), $name );
			?>
			<div class="wrap pulse-optin-wrap">
				<div class="pulse-optin">
					<?php if ( $logo ) : ?>
						<div class="pulse-optin__bar pulse-optin__bar--head">
							<img class="pulse-optin__logo" src="<?php echo esc_url( $logo ); ?>" width="94" height="25" alt="" aria-hidden="true" />
							<span class="pulse-optin__lock dashicons dashicons-lock" aria-hidden="true"></span>
						</div>
					<?php endif; ?>
					<div class="pulse-optin__body">
						<h1 class="pulse-optin__title"><?php echo esc_html( $title ); ?></h1>
						<p class="pulse-optin__lede">
							<?php esc_html_e( 'Share basic usage data so we know which features matter and which setups to support. Nothing is sent unless you allow it, and you can stop at any time.', 'caddy' ); ?>
						</p>

						<div class="pulse-optin__cols">
							<div class="pulse-optin__col">
								<h2><?php esc_html_e( 'Shared', 'caddy' ); ?></h2>
								<ul class="pulse-optin__list pulse-optin__list--yes">
									<?php foreach ( $this->collected_items() as $item ) : ?>
										<li><?php echo esc_html( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<div class="pulse-optin__col">
								<h2><?php esc_html_e( 'Never shared', 'caddy' ); ?></h2>
								<ul class="pulse-optin__list pulse-optin__list--no">
									<li><?php esc_html_e( 'Customer, order, or shopper data', 'caddy' ); ?></li>
									<li><?php esc_html_e( 'Products, content, or media', 'caddy' ); ?></li>
									<li><?php esc_html_e( 'Credentials or email addresses', 'caddy' ); ?></li>
								</ul>
							</div>
						</div>

						<p class="pulse-optin__note">
							<?php
							printf(
								/* translators: %s: name of the settings control, rendered in bold. Matches the label on the plugin settings screen. */
								esc_html__( 'Change this anytime under %s in the plugin settings.', 'caddy' ),
								'<strong>' . esc_html__( 'Share usage data', 'caddy' ) . '</strong>'
							);
							?>
							<?php if ( $this->args['privacy_url'] ) : ?>
								<a href="<?php echo esc_url( $this->args['privacy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy policy', 'caddy' ); ?></a>
							<?php endif; ?>
						</p>

					</div>

					<div class="pulse-optin__bar pulse-optin__bar--foot">
						<form class="pulse-optin__actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo esc_attr( $this->screen_action() ); ?>" />
							<?php wp_nonce_field( $this->screen_action() ); ?>
							<button type="submit" name="optin" value="yes" class="button button-primary button-hero"><span class="pulse-optin__check" aria-hidden="true">&#10003;</span><?php esc_html_e( 'Allow & continue', 'caddy' ); ?></button>
							<button type="submit" name="optin" value="no" class="button-link pulse-optin__skip"><?php esc_html_e( 'No thanks', 'caddy' ); ?></button>
						</form>
					</div>
				</div>
			</div>
			<style>
				.pulse-optin-wrap{max-width:820px;margin-top:40px}
				.pulse-optin{background:#fff;border:1px solid #dcdcde;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
				.pulse-optin__bar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:16px 28px}
				.pulse-optin__bar--head{border-bottom:1px solid #dcdcde}
				.pulse-optin__bar--foot{border-top:1px solid #dcdcde}
				.pulse-optin__body{padding:24px 28px}
				.pulse-optin__logo{display:block;height:25px;width:auto}
				.pulse-optin__lock{margin-left:auto;color:#646970;font-size:20px;width:20px;height:20px}
				/* Outranks core's .wrap h1, which would otherwise win on specificity. */
				.pulse-optin-wrap .pulse-optin__title{margin:0;padding:0 0 5px 0;font-size:21px;line-height:1.3;font-weight:600}
				.pulse-optin__lede{margin:0 0 24px;font-size:15px;line-height:1.6;color:#3c434a}
				.pulse-optin__cols{display:flex;flex-wrap:wrap;gap:32px;margin-bottom:18px}
				.pulse-optin__col{flex:1 1 300px;min-width:0}
				.pulse-optin__col h2{margin:0 0 10px;font-size:14px;text-transform:uppercase;letter-spacing:.04em;color:#50575e}
				.pulse-optin__list{margin:0;font-size:14px;line-height:1.3}
				.pulse-optin__list li{position:relative;margin:0 0 8px;padding-left:26px;list-style:none}
				.pulse-optin__list li:before{position:absolute;left:0;top:0;font-family:dashicons;font-size:17px;line-height:18px}
				.pulse-optin__list--yes li:before{content:"\f147";color:#008a20}
				.pulse-optin__list--no li:before{content:"\f158";color:#b32d2e}
				.pulse-optin__actions{display:flex;flex-wrap:wrap;gap:25px;align-items:center;margin:0}
				.pulse-optin__check{margin-right:8px}
				/* Kept a submit button so the decline still posts the nonce; styled as a link so it does not compete with the primary action.
				   Scoped to the wrap to outrank core's .wp-core-ui .button-link colour. */
				.pulse-optin-wrap .pulse-optin__skip{color:#000;text-decoration:underline}
				.pulse-optin-wrap .pulse-optin__skip:hover,.pulse-optin-wrap .pulse-optin__skip:focus{color:#135e96}
				.pulse-optin__note{margin:18px 0 0;font-size:13px;color:#646970}
				.pulse-optin-wrap .pulse-optin__note a{color:#000;text-decoration:underline}
				.pulse-optin-wrap .pulse-optin__note a:hover,.pulse-optin-wrap .pulse-optin__note a:focus{color:#135e96}
				@media screen and (max-width:782px){.pulse-optin__bar,.pulse-optin__body{padding-left:20px;padding-right:20px}}
			</style>
			<?php
		}

		/**
		 * Store the answer from the consent screen and return to the plugin.
		 */
		public function handle_consent_screen() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'caddy' ), 403 );
			}
			check_admin_referer( $this->screen_action() );

			$optin = ( isset( $_POST['optin'] ) && 'yes' === $_POST['optin'] ) ? 'yes' : 'no';
			update_option( Pulse_Client::telemetry_optin_key( $this->args['plugin_slug'] ), $optin, false );
			update_option( self::screen_shown_key( $this->args['plugin_slug'] ), 1, false );

			if ( 'yes' === $optin ) {
				$this->send();
			}

			wp_safe_redirect( $this->return_url() );
			exit;
		}

		/**
		 * Fallback consent prompt for installs that never saw the post-activation
		 * screen (upgrades from an earlier version). Shows until the user answers.
		 */
		public function consent_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			// Only ask once answered state is unset.
			if ( false !== get_option( Pulse_Client::telemetry_optin_key( $this->args['plugin_slug'] ), false ) ) {
				return;
			}
			// The screen already asked; skipping it is an answer, not an invitation to nag.
			if ( get_option( self::screen_shown_key( $this->args['plugin_slug'] ) ) ) {
				return;
			}

			$name  = ! empty( $this->args['branding']['name'] ) ? $this->args['branding']['name'] : ucfirst( $this->args['plugin_slug'] );
			$nonce = wp_create_nonce( $this->action() );
			?>
			<div class="notice notice-info pulse-tel-notice" data-action="<?php echo esc_attr( $this->action() ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<p>
					<strong><?php echo esc_html( $name ); ?>:</strong>
					<?php esc_html_e( 'Help improve the plugin by sharing usage and environment data from this site: WordPress and PHP versions, which features are on, plugin age, and your site URL. No customer or order data, and you can turn it off anytime in the plugin settings.', 'caddy' ); ?>
					<?php if ( $this->args['privacy_url'] ) : ?>
						<a href="<?php echo esc_url( $this->args['privacy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy policy', 'caddy' ); ?></a>
					<?php endif; ?>
				</p>
				<p>
					<button class="button button-primary pulse-tel-allow"><?php esc_html_e( 'Allow', 'caddy' ); ?></button>
					<button class="button pulse-tel-deny"><?php esc_html_e( 'No thanks', 'caddy' ); ?></button>
				</p>
			</div>
			<script>
			( function( $ ) {
				$( '.pulse-tel-notice' ).on( 'click', '.pulse-tel-allow, .pulse-tel-deny', function() {
					var $n = $( this ).closest( '.pulse-tel-notice' );
					$.post( ajaxurl, {
						action: $n.data( 'action' ),
						nonce: $n.data( 'nonce' ),
						optin: $( this ).hasClass( 'pulse-tel-allow' ) ? 'yes' : 'no'
					} );
					$n.slideUp();
				} );
			} )( jQuery );
			</script>
			<?php
		}

		/**
		 * Store the consent choice; send immediately on opt-in.
		 */
		public function ajax_optin() {
			if ( ! current_user_can( 'manage_options' )
				|| ! isset( $_POST['nonce'] )
				|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->action() ) ) {
				wp_send_json_error( 'denied', 403 );
			}

			$optin = ( isset( $_POST['optin'] ) && 'yes' === $_POST['optin'] ) ? 'yes' : 'no';
			update_option( Pulse_Client::telemetry_optin_key( $this->args['plugin_slug'] ), $optin, false );

			if ( 'yes' === $optin ) {
				$this->send();
			}

			wp_send_json_success();
		}

		/**
		 * Render the ongoing opt-in/opt-out toggle for a plugin settings page.
		 *
		 * Self-contained: no form wiring needed. The toggle saves over the same
		 * AJAX action as the consent notice, so a host plugin only has to call
		 * do_action( 'pulse_telemetry_settings_{slug}' ) wherever it wants it.
		 */
		public function render_settings_toggle() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$enabled = Pulse_Client::telemetry_enabled( $this->args['plugin_slug'] );
			$nonce   = wp_create_nonce( $this->action() );
			$id      = 'pulse-tel-' . sanitize_key( $this->args['plugin_slug'] );
			?>
			<table class="form-table table-settings pulse-tel-setting" data-action="<?php echo esc_attr( $this->action() ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" role="presentation">
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Share usage data', 'caddy' ); ?></label>
					</th>
					<td>
						<div class="pulse-tel-control">
							<?php
							// Default control: a self-styled switch. A host plugin can swap in
							// its own native toggle markup via the filter below, keeping the
							// `pulse-tel-toggle` input class so the AJAX handler still binds.
							$default_control = '<label class="pulse-switch"><input type="checkbox" id="' . esc_attr( $id ) . '" class="pulse-tel-toggle"' . ( $enabled ? ' checked' : '' ) . ' /><span class="pulse-switch__track"><span class="pulse-switch__thumb"></span></span></label>';
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- host-controlled markup.
							echo apply_filters( 'pulse_telemetry_toggle_control_' . sanitize_key( $this->args['plugin_slug'] ), $default_control, $enabled, $id );
							?>
							<span class="pulse-tel-saved" aria-live="polite"><?php esc_html_e( 'Saved', 'caddy' ); ?></span>
						</div>
						<p class="description">
							<?php esc_html_e( 'Send usage and environment data from this site (WordPress and PHP versions, which features are on, plugin age, and your site URL) to help improve the plugin. No customer or order data.', 'caddy' ); ?>
							<?php if ( $this->args['privacy_url'] ) : ?>
								<a href="<?php echo esc_url( $this->args['privacy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy policy', 'caddy' ); ?></a>
							<?php endif; ?>
						</p>
					</td>
				</tr>
			</table>
			<style>
				.pulse-switch{position:relative;display:inline-block;width:44px;height:24px;vertical-align:middle}
				.pulse-switch input{position:absolute;opacity:0;width:0;height:0;margin:0}
				.pulse-switch__track{position:absolute;inset:0;background:#c3c4c7;border-radius:24px;transition:background .2s;cursor:pointer}
				.pulse-switch__thumb{position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:transform .2s}
				.pulse-switch input:checked + .pulse-switch__track{background:#2271b1}
				.pulse-switch input:checked + .pulse-switch__track .pulse-switch__thumb{transform:translateX(20px)}
				.pulse-switch input:focus + .pulse-switch__track{box-shadow:0 0 0 2px #fff,0 0 0 4px #2271b1}
				.pulse-tel-control{display:flex;align-items:center;gap:10px}
				.pulse-tel-saved{color:#008a20;font-weight:600;opacity:0;transition:opacity .2s}
				.pulse-tel-saved.is-visible{opacity:1}
			</style>
			<script>
			( function( $ ) {
				$( '.pulse-tel-setting' ).on( 'change', '.pulse-tel-toggle', function() {
					var $w = $( this ).closest( '.pulse-tel-setting' ),
						$s = $w.find( '.pulse-tel-saved' );
					$.post( ajaxurl, {
						action: $w.data( 'action' ),
						nonce: $w.data( 'nonce' ),
						optin: $( this ).is( ':checked' ) ? 'yes' : 'no'
					} ).done( function() {
						$s.addClass( 'is-visible' );
						setTimeout( function() { $s.removeClass( 'is-visible' ); }, 1500 );
					} );
				} );
			} )( jQuery );
			</script>
			<?php
		}

		/**
		 * Option key holding the last-sent timestamp for this plugin.
		 *
		 * @return string
		 */
		private function sent_key() {
			return 'pulse_tel_sent_' . sanitize_key( $this->args['plugin_slug'] );
		}

		/**
		 * Send a throttled snapshot if opted in.
		 */
		public function maybe_send() {
			if ( ! Pulse_Client::telemetry_enabled( $this->args['plugin_slug'] ) ) {
				return;
			}
			$last = (int) get_option( $this->sent_key(), 0 );
			if ( time() - $last < self::SEND_INTERVAL ) {
				return;
			}
			$this->send();
		}

		/**
		 * Build and post the snapshot. Non-blocking so a slow hub never stalls the
		 * admin page load; stamps the send time so an immediate opt-in send does not
		 * trigger a duplicate on the next admin_init.
		 */
		private function send() {
			global $wp_version;

			$features = is_callable( $this->args['custom_data'] ) ? (array) call_user_func( $this->args['custom_data'] ) : array();

			// Stamp before dispatch so a concurrent request in the same window cannot
			// also send, and an opt-in send throttles the next scheduled one.
			update_option( $this->sent_key(), time(), false );

			Pulse_Client::post(
				Pulse_Client::endpoint( $this->args['hub_url'], 'telemetry', $this->args['plugin_slug'] ),
				array(
					'brand'          => $this->args['brand'],
					'plugin_slug'    => $this->args['plugin_slug'],
					'install_id'     => Pulse_Client::install_id(),
					'site_url'       => home_url(),
					'plugin_version' => $this->args['plugin_version'],
					'wp_version'     => $wp_version,
					'php_version'    => PHP_VERSION,
					'woo_version'    => defined( 'WC_VERSION' ) ? WC_VERSION : '',
					'theme'          => (string) wp_get_theme()->get( 'Name' ),
					'locale'         => get_locale(),
					'is_multisite'   => is_multisite() ? 1 : 0,
					'tier'           => $this->args['tier'],
					'tenure_days'    => Pulse_Client::tenure_days(),
					'features'       => $features,
					'license_key'    => $this->args['license_key'],
				),
				false
			);
		}
	}
}
