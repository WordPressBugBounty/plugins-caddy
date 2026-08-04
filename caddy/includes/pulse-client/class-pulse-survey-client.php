<?php
/**
 * Pulse - deactivation survey client.
 *
 * Renders the deactivation modal on the plugins screen and posts the response
 * to the Pulse Hub. Forked + renamed from the Refinery survey SDK.
 *
 * @package Pulse_Client
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Pulse_Survey_Client' ) ) {

	/**
	 * Deactivation survey modal + submit handler.
	 */
	class Pulse_Survey_Client {

		/**
		 * Config.
		 *
		 * @var array
		 */
		private $args;

		/**
		 * Constructor.
		 *
		 * @param array $args brand, plugin_slug, plugin_basename, hub_url, tier, plugin_version, branding.
		 */
		public function __construct( $args ) {
			$this->args = wp_parse_args(
				$args,
				array(
					'brand'           => '',
					'plugin_slug'     => '',
					'plugin_basename' => '',
					'hub_url'         => '',
					'license_key'     => '',
					'tier'            => '',
					'plugin_version'  => '',
					'branding'        => array(),
					'privacy_url'     => '',
				)
			);

			add_action( 'admin_footer-plugins.php', array( $this, 'render_modal' ) );
			add_action( 'wp_ajax_' . $this->action(), array( $this, 'ajax_submit' ) );
		}

		/**
		 * Survey reasons, as value => [ label, has_detail, detail_placeholder ].
		 *
		 * Built on demand rather than in the constructor: the client is
		 * instantiated on plugins_loaded, and translating this early triggers
		 * WordPress's just-in-time textdomain notice.
		 *
		 * @return array
		 */
		private function reasons() {
			return array(
				'missing_feature' => array( __( "It's missing a specific feature", 'caddy' ), true, __( 'Which feature?', 'caddy' ) ),
				'not_working'     => array( __( "It's not working / I hit a bug", 'caddy' ), true, __( 'What happened?', 'caddy' ) ),
				'not_expected'    => array( __( 'Not what I expected', 'caddy' ), false, '' ),
				'found_better'    => array( __( 'I found a better plugin', 'caddy' ), true, __( 'Which one?', 'caddy' ) ),
				'too_hard'        => array( __( 'Too hard to set up', 'caddy' ), false, '' ),
				'temporary'       => array( __( 'Only temporary', 'caddy' ), false, '' ),
				'other'           => array( __( 'Something else', 'caddy' ), false, '' ),
			);
		}

		/**
		 * Unique AJAX action for this plugin.
		 *
		 * @return string
		 */
		private function action() {
			return 'pulse_survey_' . sanitize_key( $this->args['plugin_slug'] );
		}

		/**
		 * Resolve the plugin version.
		 *
		 * @return string
		 */
		private function version() {
			if ( $this->args['plugin_version'] ) {
				return $this->args['plugin_version'];
			}
			if ( $this->args['plugin_basename'] && function_exists( 'get_plugin_data' ) ) {
				$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->args['plugin_basename'], false, false );
				return isset( $data['Version'] ) ? $data['Version'] : '';
			}
			return '';
		}

		/**
		 * Render the modal, styles, and interception script.
		 */
		public function render_modal() {
			$slug     = sanitize_key( $this->args['plugin_slug'] );
			$name     = ! empty( $this->args['branding']['name'] ) ? $this->args['branding']['name'] : ucfirst( $slug );
			$nonce    = wp_create_nonce( $this->action() );
			$basename = $this->args['plugin_basename'];
			$user     = wp_get_current_user();
			$email    = $user ? $user->user_email : '';
			?>
			<div id="pulse-survey-<?php echo esc_attr( $slug ); ?>" class="pulse-survey-modal" style="display:none;">
				<div class="pulse-survey-overlay"></div>
				<div class="pulse-survey-box">
					<button type="button" class="pulse-survey-close">&times;</button>
					<h2><?php
						/* translators: %s: plugin name */
						printf( esc_html__( 'Quick question before you go from %s', 'caddy' ), '<strong>' . esc_html( $name ) . '</strong>' );
					?></h2>
					<form class="pulse-survey-form">
						<?php foreach ( $this->reasons() as $value => $r ) : ?>
							<label class="pulse-survey-reason">
								<input type="radio" name="reason" value="<?php echo esc_attr( $value ); ?>"
									data-detail="<?php echo $r[1] ? '1' : '0'; ?>"
									data-placeholder="<?php echo esc_attr( $r[2] ); ?>">
								<span><?php echo esc_html( $r[0] ); ?></span>
							</label>
						<?php endforeach; ?>

						<input type="text" class="pulse-survey-detail" name="reason_detail" style="display:none;" />
						<textarea class="pulse-survey-feedback" name="feedback" rows="2"
							placeholder="<?php esc_attr_e( 'Anything else? (optional)', 'caddy' ); ?>"></textarea>

						<label class="pulse-survey-contact">
							<input type="checkbox" name="contact_requested" value="1" />
							<?php esc_html_e( 'I would like someone to help resolve my issue', 'caddy' ); ?>
						</label>
						<input type="email" class="pulse-survey-email" name="contact_email" disabled
							style="display:none;" value="<?php echo esc_attr( $email ); ?>"
							placeholder="<?php esc_attr_e( 'Email address', 'caddy' ); ?>"
							aria-label="<?php esc_attr_e( 'Email address', 'caddy' ); ?>" />

						<p class="pulse-survey-privacy">
							<?php esc_html_e( 'Submitting shares your reason plus basic environment data to help us improve.', 'caddy' ); ?>
							<?php if ( $this->args['privacy_url'] ) : ?>
								<a href="<?php echo esc_url( $this->args['privacy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy policy', 'caddy' ); ?></a>
							<?php endif; ?>
						</p>

						<div class="pulse-survey-actions">
							<a href="#" class="pulse-survey-skip"><?php esc_html_e( 'Skip & deactivate', 'caddy' ); ?></a>
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Submit & deactivate', 'caddy' ); ?></button>
						</div>
					</form>
				</div>
			</div>

			<style>
				.pulse-survey-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99998}
				.pulse-survey-box{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;padding:26px;width:90%;max-width:460px;z-index:99999;box-shadow:0 10px 40px rgba(0,0,0,.25)}
				.pulse-survey-box h2{margin:0 0 14px;font-size:18px}
				.pulse-survey-close{position:absolute;top:12px;right:14px;border:0;background:none;font-size:22px;color:#999;cursor:pointer}
				.pulse-survey-reason{display:block;padding:9px 11px;margin:5px 0;background:#f6f7f7;border:2px solid transparent;border-radius:5px;cursor:pointer}
				.pulse-survey-reason.is-selected,.pulse-survey-reason:has(input:checked){border-color:#2271b1;background:#f0f7fc}
				.pulse-survey-reason input{margin-right:9px}
				.pulse-survey-detail,.pulse-survey-feedback{width:100%;margin-top:10px;padding:8px;border:1px solid #ddd;border-radius:4px}
				.pulse-survey-contact{display:block;margin-top:12px;font-size:13px}
				.pulse-survey-email{width:100%;margin-top:8px;padding:8px;border:1px solid #ddd;border-radius:4px}
				.pulse-survey-privacy{font-size:11px;color:#787c82;margin:12px 0 0}
				.pulse-survey-actions{display:flex;justify-content:space-between;align-items:center;margin-top:16px;border-top:1px solid #eee;padding-top:14px}
				.pulse-survey-skip{color:#787c82;text-decoration:none}
			</style>

			<script>
			( function( $ ) {
				var slug = <?php echo wp_json_encode( $slug ); ?>,
					basename = <?php echo wp_json_encode( $basename ); ?>,
					action = <?php echo wp_json_encode( $this->action() ); ?>,
					nonce = <?php echo wp_json_encode( $nonce ); ?>,
					ajaxurl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
					$modal = $( '#pulse-survey-' + slug ),
					deactivateUrl = '';

				$( 'tr[data-plugin="' + basename + '"] .deactivate a' ).on( 'click', function( e ) {
					e.preventDefault();
					deactivateUrl = $( this ).attr( 'href' );
					$modal.show();
				} );

				$modal.on( 'click', '.pulse-survey-close, .pulse-survey-overlay', function() { $modal.hide(); } );
				$modal.on( 'click', '.pulse-survey-skip', function( e ) { e.preventDefault(); if ( deactivateUrl ) { window.location = deactivateUrl; } } );

				$modal.on( 'change', 'input[name="reason"]', function() {
					$modal.find( '.pulse-survey-reason' ).removeClass( 'is-selected' );
					$( this ).closest( '.pulse-survey-reason' ).addClass( 'is-selected' );
					var $d = $modal.find( '.pulse-survey-detail' );
					if ( $( this ).data( 'detail' ) == 1 ) {
						$d.attr( 'placeholder', $( this ).data( 'placeholder' ) ).show();
					} else {
						$d.hide().val( '' );
					}
				} );

				$modal.on( 'change', 'input[name="contact_requested"]', function() {
					var $email = $modal.find( '.pulse-survey-email' );
					if ( $( this ).is( ':checked' ) ) {
						$email.prop( 'disabled', false ).show().trigger( 'focus' );
					} else {
						$email.prop( 'disabled', true ).hide();
					}
				} );

				$modal.on( 'submit', '.pulse-survey-form', function( e ) {
					e.preventDefault();
					var contact = $modal.find( 'input[name="contact_requested"]' ).is( ':checked' ) ? 1 : 0,
						$btn = $( this ).find( 'button[type="submit"]' ).prop( 'disabled', true );
					$.post( ajaxurl, {
						action: action,
						nonce: nonce,
						reason: $modal.find( 'input[name="reason"]:checked' ).val() || 'none',
						reason_detail: $modal.find( '.pulse-survey-detail' ).val(),
						feedback: $modal.find( '.pulse-survey-feedback' ).val(),
						contact_requested: contact,
						contact_email: contact ? $modal.find( '.pulse-survey-email' ).val() : ''
					} ).always( function() { if ( deactivateUrl ) { window.location = deactivateUrl; } } );
				} );
			} )( jQuery );
			</script>
			<?php
		}

		/**
		 * Receive the submission and forward it to the hub.
		 */
		public function ajax_submit() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( 'denied', 403 );
			}
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->action() ) ) {
				wp_send_json_error( 'bad_nonce', 403 );
			}

			$reason        = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : 'none';
			$reason_detail = isset( $_POST['reason_detail'] ) ? sanitize_text_field( wp_unslash( $_POST['reason_detail'] ) ) : '';
			$feedback      = isset( $_POST['feedback'] ) ? sanitize_textarea_field( wp_unslash( $_POST['feedback'] ) ) : '';
			$contact       = ! empty( $_POST['contact_requested'] ) ? 1 : 0;

			// Consent-gated: only attach an email when they asked to be contacted.
			// Honour the address they typed; fall back to their account email.
			$email = '';
			if ( $contact ) {
				$submitted = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
				if ( $submitted && is_email( $submitted ) ) {
					$email = $submitted;
				} else {
					$user  = wp_get_current_user();
					$email = $user ? $user->user_email : '';
				}
			}

			Pulse_Client::post(
				Pulse_Client::endpoint( $this->args['hub_url'], 'survey', $this->args['plugin_slug'] ),
				array(
					'brand'             => $this->args['brand'],
					'plugin_slug'       => $this->args['plugin_slug'],
					'install_id'        => Pulse_Client::install_id(),
					'site_url'          => home_url(),
					'survey_type'       => 'deactivation',
					'reason'            => $reason,
					'reason_detail'     => $reason_detail,
					'feedback'          => $feedback,
					'tier'              => $this->args['tier'],
					'tenure_days'       => Pulse_Client::tenure_days(),
					'plugin_version'    => $this->version(),
					'contact_requested' => $contact,
					'email'             => $email,
					'license_key'       => $this->args['license_key'],
				)
			);

			wp_send_json_success();
		}
	}
}
