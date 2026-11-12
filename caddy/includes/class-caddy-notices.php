<?php

/**
 * Used to display admin notices.
 *
 * @package    caddy
 * @subpackage caddy/includes
 * @author     Tribe Interactive <hello@madebytribe.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Caddy_Admin_Notices {
	
	private $install_date;
	private $installed_version;
	
	public function __construct() {
		$this->setup_install_data();
	}
	
	/**
	 * Set up installation date and version data
	 */
	private function setup_install_data() {
		// Get installation date
		$this->install_date = get_option('caddy_install_date', false);
		
		// If no install date is set, set it now
		if (!$this->install_date) {
			$this->install_date = time();
			update_option('caddy_install_date', $this->install_date);
		}
		
		// Get the installed version
		$this->installed_version = get_option('caddy_version', false);
	}
	
	/**
	 * Check if the plugin was installed within a certain timeframe
	 * 
	 * @param int $days Number of days to check against
	 * @return bool True if plugin was installed within specified days
	 */
	private function is_recent_install($days = 7) {
		// If no install date, consider it a new install
		if (!$this->install_date) {
			return true;
		}
		
		$now = time();
		$diff = round(($now - $this->install_date) / DAY_IN_SECONDS);
		
		return $diff <= $days;
	}
	
	/**
	 * Check if this is an upgrade from a previous version
	 * 
	 * @return bool True if this is an upgrade
	 */
	private function is_upgrade() {
		return $this->installed_version && $this->installed_version !== CADDY_VERSION;
	}
	
	public function register_hooks() {
		
		// Display RetentionKit promo
		add_action( 'admin_notices', array( $this, 'display_rk_promo_notice' ) );
		// Display review request
		add_action( 'admin_notices', array( $this, 'display_review_notice' ) );
		// Display upgrade notice
		add_action( 'caddy_admin_notices', array( $this, 'display_upgrade_notice' ) );

		// Update version on plugin updates
		add_action( 'upgrader_process_complete', array( $this, 'update_version_on_upgrade' ), 10, 2 );
	}
	
	/**
	 * Update version when plugin is upgraded
	 */
	public function update_version_on_upgrade($upgrader, $options) {
		if ($options['action'] == 'update' && $options['type'] == 'plugin') {
			foreach($options['plugins'] as $plugin) {
				if ($plugin == plugin_basename(CADDY_PLUGIN_FILE)) {
					update_option('caddy_version', CADDY_VERSION);
				}
			}
		}
	}
	
	/**
	 * Display RK promo notice
	 */
	public function display_rk_promo_notice() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Ensure WooCommerce is active
		if (!function_exists('WC')) return;
		
		// Shared with Klaviyo Toolkit, which uses the same flag, so that only one
		// promo notice renders when both plugins are installed. The name must stay
		// in sync across both plugins.
		global $rk_promo_notice_called;
		if (isset($rk_promo_notice_called) && $rk_promo_notice_called) {
			return; // Don't execute if the notice has already been called
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Cross-plugin coordination flag; renaming would show the notice twice.
		$rk_promo_notice_called = true;
		
		// Get the current URL
		$http_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
		$request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
		$current_url = (is_ssl() ? 'https://' : 'http://') . $http_host . $request_uri;
		
		// Check if RK is not active and if we're on the target WooCommerce Subscriptions pages
		if (!class_exists('rk') && (
			$this->is_subscriptions_listing_page($current_url) ||
			$this->is_edit_subscription_page($current_url)
		)) {
			wp_enqueue_style('kt-admin-notice', plugin_dir_url(__DIR__) . 'admin/css/caddy-admin-notices.css', array(), CADDY_VERSION);
		
			if (!PAnD::is_admin_notice_active('notice-rk-promo-forever')) {
				return;
			}

			$cancel_span  = '<span class="cancel-count"></span>';
			$pending_span = '<span class="pending-cancel-count"></span>';
			$rk_messages  = array(
				/* translators: 1: number of cancellations, 2: number of pending cancellations */
				'both'    => sprintf( esc_html__( 'You have %1$s cancellations and %2$s pending-cancels!', 'caddy' ), $cancel_span, $pending_span ),
				'none'    => esc_html__( 'Put a stop to subscription cancellations!', 'caddy' ),
				/* translators: %s: number of pending cancellations */
				'pending' => sprintf( esc_html__( 'You have %s pending-cancels!', 'caddy' ), $pending_span ),
				/* translators: %s: number of cancellations */
				'cancel'  => sprintf( esc_html__( 'You have %s cancellations!', 'caddy' ), $cancel_span ),
			);
			$rk_url = 'https://www.getretentionkit.com/?utm_source=caddy-plugin&utm_medium=plugin&utm_campaign=sub-promo-15';
		?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					var messages = <?php echo wp_json_encode( $rk_messages ); ?>;

					// Parse numbers without commas
					var cancelledCount = parseInt($('.wc-cancelled span.count').text().replace(/\(|\)|,/g, "") || 0);
					var pendingCancelCount = parseInt($('.wc-pending-cancel span.count').text().replace(/\(|\)|,/g, "") || 0);
			
					// Function to add commas to numbers for display
					function numberWithCommas(x) {
						return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
					}
				
					// Update the message first
					if (cancelledCount === 0 && pendingCancelCount === 0) {
						$('.dynamic-message').html(messages.none);
					} else if (cancelledCount === 0) {
						$('.dynamic-message').html(messages.pending);
					} else if (pendingCancelCount === 0) {
						$('.dynamic-message').html(messages.cancel);
					}
				
					// Then update the counts using formatted numbers with commas
					$('.cancel-count').html(numberWithCommas(cancelledCount));
					$('.pending-cancel-count').html(numberWithCommas(pendingCancelCount));
				});
			</script>
	
			<div data-dismissible="notice-rk-promo-forever" class="notice notice-info is-dismissible caddy-notice rk-promo">
				<div class="kt-left"><img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'admin/img/rk-cancel-promo.svg' ); ?>" width="145" height="145" alt="<?php echo esc_attr__( 'RetentionKit promotion', 'caddy' ); ?>"></div>
				<div class="kt-right">
					<div class="welcome-heading">
						<span class="dynamic-message"><?php echo $rk_messages['both']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built above. ?></span>
					</div>
	
					<p class="rk-message">
						<?php
						printf(
							/* translators: %s: link to RetentionKit */
							esc_html__( 'That\'s potential revenue slipping away. Let %s step in! We\'ll not only reveal why they\'re stepping back but also weave in offers that can transform those exits into profit boosts. In the subscription game, every comeback is a win for your bottom line. 💰', 'caddy' ),
							'<a href="' . esc_url( $rk_url ) . '">' . esc_html__( 'RetentionKit', 'caddy' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						);
						?>
					</p>
					<p>
						<?php 
						echo wp_kses(
							__( 'Use code <strong>RKSAVE15</strong> to take <strong>15% off</strong> RetentionKit today and start saving your subscription revenue.', 'caddy' ),
							array(
								'strong' => array()
							)
						); 
						?>
					</p>
					<p class="caddy-notice-ctas">
						<a class="button button-primary" href="<?php echo esc_url( $rk_url ); ?>"><?php echo esc_html( __( 'Enable Cancellation Protection', 'caddy' ) ); ?><img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'admin/img/rk-arrow-right.svg' ); ?>" width="20" height="20"></a>
					</p>
				</div>
			</div>
		<?php
		}
	}

	private function is_subscriptions_listing_page($url) {
		// Check if the URL is for the subscriptions listing page
		return strpos($url, 'page=wc-orders--shop_subscription') !== false && strpos($url, 'action=edit') === false;
	}
	
	private function is_edit_subscription_page($url) {
		// Check if the URL is for the edit subscription page
		return strpos($url, 'page=wc-orders--shop_subscription') !== false && strpos($url, 'action=edit') !== false && $this->is_subscription_cancel_or_pending_cancel();
	}
	
	private function is_subscription_cancel_or_pending_cancel() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter for display logic, not processing form data
		if (!isset($_GET['id'])) return false;
	
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter for display logic, not processing form data
		$post_id = intval(sanitize_text_field(wp_unslash($_GET['id'])));
		$subscription = wcs_get_subscription($post_id);
	
		if (!$subscription) return false;
	
		$status = $subscription->get_status();
	
		return in_array($status, ['cancelled', 'pending-cancel']);
	}

	/**
	 * Display review request notice
	 */
	public function display_review_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't show if the notice has been dismissed permanently or temporarily
		if (!PAnD::is_admin_notice_active('notice-caddy-review-forever') || !PAnD::is_admin_notice_active('notice-caddy-review-30')) {
			return;
		}
		
		// Don't show for recent installs (less than 14 days old)
		if ($this->is_recent_install(14)) {
			return;
		}

		// Don't show if upgrade notice is still active (prioritize upgrade first)
		if (!class_exists('Caddy_Premium') && PAnD::is_admin_notice_active('notice-caddy-upgrade-forever') && PAnD::is_admin_notice_active('notice-caddy-upgrade-30')) {
			return;
		}

		// Skip on specific admin pages
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine page context for display logic
		$page_name = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		if ('caddy' == $page_name || 'caddy-addons' === $page_name || 'caddy-resources' === $page_name) {
			return;
		}

		wp_enqueue_style('kt-admin-notice', plugin_dir_url(__DIR__) . 'admin/css/caddy-admin-notices.css', array(), CADDY_VERSION);

		// Get user info
		$current_user = wp_get_current_user();
		$first_name = $current_user->first_name;
		$username = $current_user->user_login;

		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('.caddy-review-later').click(function(e) {
					e.preventDefault();
					$(this).closest('.notice').attr('data-dismissible', 'notice-caddy-review-30');
					$(this).closest('.notice').find('.notice-dismiss').trigger('click');
				});
				
				$('.caddy-review-button').click(function(e) {
					$(this).closest('.notice').attr('data-dismissible', 'notice-caddy-review-forever');
					$(this).closest('.notice').find('.notice-dismiss').trigger('click');
				});
			});
		</script>

		<div data-dismissible="notice-caddy-review-forever" class="notice notice-info is-dismissible caddy-notice review-notice">
			<div class="kt-right" style="padding:0;">
				<div class="welcome-heading">
					<?php 
					$display_name = !empty($first_name) ? $first_name : $username;
					printf(
						/* translators: %s: User's first name or username */
						esc_html__('A quick favor, %s?', 'caddy'),
						esc_html($display_name)
					);
					?>
				</div>
				<p class="review-message">
					<?php echo esc_html__('Hope Caddy\'s been helping you boost conversions and create a better cart experience! Quick favor — if it\'s been useful, would you mind leaving a short review on WordPress.org? Your feedback helps others discover Caddy and gives us fuel to keep improving it. Thanks so much for your support!', 'caddy'); ?>
				</p>
				<p class="caddy-notice-ctas">
					<a class="button button-primary caddy-review-button" href="<?php echo esc_url('https://wordpress.org/support/plugin/caddy/reviews/'); ?>" target="_blank">
						<?php echo esc_html__('OK, you deserve it', 'caddy'); ?>
					</a>
					<a href="#" class="caddy-review-later">
						<?php echo esc_html__('Maybe later', 'caddy'); ?>
					</a>
					<a href="#" class="dismiss-this">
						<?php echo esc_html__('I already rated it', 'caddy'); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the limited-time offer content with countdown timer.
	 *
	 * The offer runs for 48 hours from the first time it is displayed on this
	 * site and is never shown again after that.
	 *
	 * @param string $coupon_code     The coupon code to display.
	 * @param string $discount_text   The discount text to display. Defaults to a translated "25% off Caddy Pro".
	 * @param string $container_class Additional class for the container (optional).
	 * @param string $unique_id       Unique identifier for this countdown instance (optional).
	 * @return string HTML content for the limited-time offer, or an empty string when the offer is not live.
	 */
	public function get_limited_time_offer($coupon_code = '1E479230A6', $discount_text = '', $container_class = '', $unique_id = '') {
		// Don't show countdown offers for recent installs (less than 14 days old)
		if ($this->is_recent_install(14)) {
			return '';
		}

		$started_at = (int) get_option( 'cc_offer_started_at', 0 );
		if ( $started_at <= 0 ) {
			$started_at = time();
			update_option( 'cc_offer_started_at', $started_at, false );
		}

		$remaining = ( $started_at + 48 * HOUR_IN_SECONDS ) - time();
		if ( $remaining <= 0 ) {
			return '';
		}

		if ( '' === $discount_text ) {
			$discount_text = __( '25% off Caddy Pro', 'caddy' );
		}

		// Generate a unique ID if not provided
		if (empty($unique_id)) {
			$unique_id = 'caddy-' . wp_rand(1000, 9999);
		} else {
			// Ensure unique_id is safe for use in HTML and JS
			$unique_id = preg_replace('/[^a-z0-9-_]/i', '', $unique_id);
		}

		// Create unique element IDs
		$countdown_id = 'caddy-countdown-' . $unique_id;
		$timer_id = 'caddy-countdown-time-' . $unique_id;

		$initial_time = sprintf(
			'%02d:%02d:%02d',
			floor( $remaining / HOUR_IN_SECONDS ),
			floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ),
			$remaining % MINUTE_IN_SECONDS
		);

		$code_html      = '<code>' . esc_html( $coupon_code ) . '</code>';
		$countdown_html = '<div class="caddy-countdown" id="' . esc_attr( $countdown_id ) . '">'
			. '<div class="caddy-countdown-timer"><div class="caddy-countdown-item">'
			. '<span class="caddy-countdown-number" id="' . esc_attr( $timer_id ) . '">' . esc_html( $initial_time ) . '</span>'
			. '<span class="caddy-countdown-label">' . esc_html__( 'hours', 'caddy' ) . '</span>'
			. '</div></div></div>';
		$discount_html  = '<strong>' . esc_html( $discount_text ) . '</strong>';

		// Ensure the CSS is loaded
		wp_enqueue_style('kt-admin-notice', plugin_dir_url(__DIR__) . 'admin/css/caddy-admin-notices.css', array(), CADDY_VERSION);

		ob_start();
		?>
		<div class="caddy-limited-offer <?php echo esc_attr($container_class); ?>" id="<?php echo esc_attr($countdown_id); ?>-container">
			<div class="caddy-offer-content">
				<div class="caddy-offer-text">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Format string and placeholders are escaped above.
					printf(
						/* translators: 1: coupon code, 2: countdown timer, 3: discount, e.g. "25% off Caddy Pro" */
						esc_html__( 'Use code %1$s within the next %2$s to take %3$s', 'caddy' ),
						$code_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$countdown_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$discount_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
					?>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				(function() {
					var timerElement = document.getElementById("<?php echo esc_js($timer_id); ?>");
					var containerElement = document.getElementById("<?php echo esc_js($countdown_id); ?>-container");

					if (!timerElement || !containerElement) return;

					var endTime = new Date().getTime() + (<?php echo (int) $remaining; ?> * 1000);
					var countdownInterval;

					function updateCountdown() {
						var distance = endTime - new Date().getTime();

						if (distance <= 0) {
							clearInterval(countdownInterval);
							containerElement.style.display = 'none';
							return;
						}

						var totalSeconds = Math.floor(distance / 1000);
						var hours = Math.floor(totalSeconds / 3600);
						var minutes = Math.floor((totalSeconds % 3600) / 60);
						var seconds = totalSeconds % 60;

						timerElement.textContent = (hours < 10 ? "0" : "") + hours + ":" +
							(minutes < 10 ? "0" : "") + minutes + ":" +
							(seconds < 10 ? "0" : "") + seconds;
					}

					updateCountdown();
					countdownInterval = setInterval(updateCountdown, 1000);
				})();
			});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display upgrade notice
	 */
	public function display_upgrade_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't show if the notice has been dismissed permanently or temporarily
		if (!PAnD::is_admin_notice_active('notice-caddy-upgrade-forever') || !PAnD::is_admin_notice_active('notice-caddy-upgrade-30')) {
			return;
		}

		// Don't show for recent installs (less than 14 days old)
		if ($this->is_recent_install(14)) {
			return;
		}

		// Check if Caddy Premium is not active
		if (class_exists('Caddy_Premium')) {
			return;
		}

		// Only show on the Caddy settings, add-ons and resources pages
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine page context for display logic
		$page_name = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		if ('caddy' !== $page_name && 'caddy-addons' !== $page_name && 'caddy-resources' !== $page_name) {
			return;
		}

		wp_enqueue_style('kt-admin-notice', plugin_dir_url(__DIR__) . 'admin/css/caddy-admin-notices.css', array(), CADDY_VERSION);

		// Get user info
		$current_user = wp_get_current_user();
		$first_name = $current_user->first_name;
		$username = $current_user->user_login;

		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('.caddy-upgrade-later').click(function(e) {
					e.preventDefault();
					$(this).closest('.notice').attr('data-dismissible', 'notice-caddy-upgrade-30');
					$(this).closest('.notice').find('.notice-dismiss').trigger('click');
				});
				
				$('.caddy-upgrade-button').click(function(e) {
					$(this).closest('.notice').attr('data-dismissible', 'notice-caddy-upgrade-forever');
					$(this).closest('.notice').find('.notice-dismiss').trigger('click');
				});
			});
		</script>
		
		<div data-dismissible="notice-caddy-upgrade-forever" class="notice notice-info is-dismissible inline caddy-notice upgrade-notice">
			<div class="kt-left">
				<img src="<?php echo esc_url( plugin_dir_url(__DIR__) . 'admin/img/caddy-mascot.svg' ); ?>" width="145" height="145" alt="<?php echo esc_attr__( 'Caddy mascot', 'caddy' ); ?>">
			</div>
			<div class="kt-right">
				<div class="welcome-heading">
					<?php 
					$display_name = !empty($first_name) ? $first_name : $username;
					printf(
						/* translators: %s: User's first name or username */
						esc_html__('You\'re one step away from unlocking higher cart conversions, %s!', 'caddy'),
						esc_html($display_name)
					);
					?>
				</div>
				<p class="upgrade-message">
					<?php echo esc_html__('Take your cart experience and your revenue to the next level with Caddy Pro. Upgrade to access:', 'caddy'); ?>
				</p>
				<ul class="upgrade-features">
					<li>⚡️ <?php echo esc_html__('Advanced Cart Upsell Workflows', 'caddy'); ?></li>
					<li>📊 <?php echo esc_html__('Conversion & Cart Analytics', 'caddy'); ?></li>
					<li>🎁 <?php echo esc_html__('Multi-tier Cart Rewards', 'caddy'); ?></li>
					<li>💎 <?php echo esc_html__('Full design control & much more', 'caddy'); ?></li>
				</ul>
				
				<?php 
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns already escaped HTML
			echo $this->get_limited_time_offer(); 
			?>

				<p class="caddy-notice-ctas">
					<a class="button button-primary caddy-upgrade-button" href="<?php echo esc_url('https://usecaddy.com/pricing/?utm_source=caddy-plugin&utm_medium=plugin&utm_campaign=upgrade-notice'); ?>" target="_blank">
						<?php echo esc_html__('Upgrade Now', 'caddy'); ?>
					</a>
					<a href="#" class="caddy-upgrade-later">
						<?php echo esc_html__('Maybe later', 'caddy'); ?>
					</a>
					<a href="#" class="dismiss-this">
						<?php echo esc_html__('I\'ve already upgraded', 'caddy'); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

}

/**
 * Global helper function to display the limited time offer
 *
 * @param string $coupon_code The coupon code to display
 * @param string $discount_text The discount text to display (defaults to a translated "25% off Caddy Pro")
 * @param string $container_class Additional class for the container (optional)
 * @param string $unique_id Unique identifier for this countdown instance (optional)
 * @return string HTML content for the limited-time offer, or an empty string when the offer is not live
 */
function caddy_get_limited_time_offer($coupon_code = '1E479230A6', $discount_text = '', $container_class = '', $unique_id = '') {
	static $notices_instance = null;
	
	if ($notices_instance === null) {
		$notices_instance = new Caddy_Admin_Notices();
	}
	
	return $notices_instance->get_limited_time_offer($coupon_code, $discount_text, $container_class, $unique_id);
}