<?php

/**
 * Used to display admin notices.
 *
 * @package    caddy
 * @subpackage caddy/includes
 * @author     Tribe Interactive <hello@madebytribe.com>
 */
class Caddy_Admin_Notices {
	
	public function register_hooks() {
		
		// Display RetentionKit promo
		add_action( 'admin_notices', array( $this, 'display_rk_promo_notice' ) );
	
	}
	
	/**
	 * Display RK promo notice
	 */
	public function display_rk_promo_notice() {
		
		// Ensure WooCommerce is active
		if (!function_exists('WC')) return;
		
		global $rk_promo_notice_called;
		if (isset($rk_promo_notice_called) && $rk_promo_notice_called) {
			return; // Don't execute if the notice has already been called
		}
		$rk_promo_notice_called = true;
		
		// Get the current URL
		$current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
		// Check if RK is not active and if we're on the target WooCommerce Subscriptions pages
		if (!class_exists('rk') && (
			$this->is_subscriptions_listing_page($current_url) ||
			$this->is_edit_subscription_page($current_url)
		)) {
			wp_enqueue_style('kt-admin-notice', plugin_dir_url(__DIR__) . 'admin/css/caddy-admin-notices.css');
		
			if (!PAnD::is_admin_notice_active('notice-rk-promo-forever')) {
				return;
			}
		?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Parse numbers without commas
					var cancelledCount = parseInt($('.wc-cancelled span.count').text().replace(/\(|\)|,/g, "") || 0);
					var pendingCancelCount = parseInt($('.wc-pending-cancel span.count').text().replace(/\(|\)|,/g, "") || 0);
			
					// Function to add commas to numbers for display
					function numberWithCommas(x) {
						return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
					}
				
					// Update the message first
					if (cancelledCount === 0 && pendingCancelCount === 0) {
						$('.dynamic-message').html("put a stop subscription cancellations!");
					} else if (cancelledCount === 0) {
						$('.dynamic-message').html("You have <span class='pending-cancel-count'></span> pending-cancels!");
					} else if (pendingCancelCount === 0) {
						$('.dynamic-message').html("You have <span class='cancel-count'></span> cancellations!");
					}
				
					// Then update the counts using formatted numbers with commas
					$('.cancel-count').html(numberWithCommas(cancelledCount));
					$('.pending-cancel-count').html(numberWithCommas(pendingCancelCount));
				});
			</script>
	
			<div data-dismissible="notice-rk-promo-forever" class="notice is-dismissible caddy-notice rk-promo">
				<div class="kt-left"><img src="<?php echo plugin_dir_url( __DIR__ ) . 'admin/img/rk-cancel-promo.svg'; ?>" width="145" height="145" alt="kt Promo"></div>
				<div class="kt-right">
					<div class="welcome-heading">
						<span class="dynamic-message"><?php echo esc_html( __( 'You have ' ) ); ?> <span class="cancel-count"></span> <?php echo esc_html( __( 'cancellations and ' ) ); ?> <span class="pending-cancel-count"></span> <?php echo esc_html( __( 'pending-cancels!' ) ); ?></span>
					</div>
	
					<p class="rk-message">
						<?php echo esc_html( __( 'That\'s potential revenue slipping away. Let ' ) ); ?>
						<a href="<?php echo esc_url( 'https://www.getretentionkit.com/?utm_source=caddy-plugin&amp;utm_medium=plugin&amp;utm_campaign=sub-promo-15' ); ?>"><?php echo esc_html( __( 'RetentionKit' ) ); ?></a>
						<?php echo esc_html( __( ' step in! We\'ll not only reveal why they\'re stepping back but also weave in offers that can transform those exits into profit boosts. In the subscription game, every comeback is a win for your bottom line. 💰' ) ); ?>
					</p>
					<p>
						<?php 
						echo wp_kses(
							__( 'Use code <strong>RKSAVE15</strong> to take <strong>15% off</strong> kt today and start saving your subscription revenue.' ),
							array(
								'strong' => array()
							)
						); 
						?>
					</p>
					<p class="caddy-notice-ctas">
						<a class="button" href="<?php echo esc_url( 'https://www.getretentionkit.com/?utm_source=caddy-plugin&amp;utm_medium=plugin&amp;utm_campaign=sub-promo-15' ); ?>"><?php echo esc_html( __( 'Enable Cancellation Protection' ) ); ?><img src="<?php echo plugin_dir_url( __DIR__ ) . 'admin/img/rk-arrow-right.svg'; ?>" width="20" height="20"></a>
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
		if (!isset($_GET['id'])) return false;
	
		$post_id = intval($_GET['id']);
		$subscription = wcs_get_subscription($post_id);
	
		if (!$subscription) return false;
	
		$status = $subscription->get_status();
	
		return in_array($status, ['cancelled', 'pending-cancel']);
	}

}