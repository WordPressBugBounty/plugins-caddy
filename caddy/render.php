<?php
/**
 * Caddy Cart Block Render Template
 *
 * This template renders the interactive cart block using WordPress Interactivity API
 * with the original Caddy template structure.
 *
 * @package    Caddy
 * @since      2.1.3
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Get block attributes with defaults
$auto_open = $attributes['autoOpen'] ?? true;

// Only show free version compass if premium plugin is not active (same logic as original)
if (!class_exists('Caddy_Premium')) {
	// Ensure cart session is properly initialized
	if (function_exists('WC') && WC()->session) {
		// Initialize WooCommerce session if not already done
		if (!WC()->session->has_session()) {
			WC()->session->set_customer_session_cookie(true);
		}

		// Ensure cart is loaded
		if (WC()->cart) {
			$cart_count = WC()->cart->get_cart_contents_count();
			if ( $cart_count > 0 ) {
				WC()->cart->calculate_totals();
			}
		} else {
			$cart_count = 0;
		}
	} else {
		$cart_count = 0;
	}
	?>
	<div data-wp-interactive="caddy/cart" data-wp-context='{"autoOpen": <?php echo json_encode($auto_open); ?>}'>
		<!-- The floating compass icon (same as original) -->
		<div class="cc-compass" data-wp-on--click="actions.toggleCart" data-wp-class--cc-compass-open="state.isOpen">
			<span class="cc-compass-cart-icon" data-wp-class--cc-hidden="state.isOpen">
				<?php
			$allowed_svg = array(
				'svg'  => array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'style' => true, 'class' => true, 'aria-hidden' => true ),
				'path' => array( 'd' => true, 'stroke-width' => true, 'fill' => true, 'stroke' => true ),
				'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
				'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
				'line' => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true ),
				'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
				'polygon' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
				'g' => array( 'fill' => true, 'stroke' => true, 'transform' => true ),
			);
			echo wp_kses( apply_filters('caddy_cart_bubble_icon', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-0.5 -0.5 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M5.75 1.9166666666666667 2.875 5.75v13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 1.9166666666666667h13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 -1.9166666666666667V5.75l-2.875 -3.8333333333333335z" stroke-width="1.2"></path><path d="m2.875 5.75 17.25 0" stroke-width="1.2"></path><path d="M15.333333333333334 9.583333333333334a3.8333333333333335 3.8333333333333335 0 0 1 -7.666666666666667 0" stroke-width="1.2"></path></svg>'), $allowed_svg );
			?>
			</span>
			<span class="cc-compass-close-icon cc-hidden" data-wp-class--cc-hidden="!state.isOpen">
				<i class="ccicon-close"></i>
			</span>
			<div class="cc-loader" style="display: none;"></div>
			<span class="cc-compass-count"
			      data-wp-class--cc-cart-zero="state.cartCount === 0"
			      data-wp-text="state.cartCount">
				<?php echo esc_html($cart_count); ?>
			</span>
		</div>

		<!-- The expanded modal (using original structure) -->
		<div class="cc-window disable-scrollbars" data-wp-class--cc-show="state.isOpen">
			<div class="cc-window-wrapper">
				<?php
				// Use the original window screen content
				include(plugin_dir_path(__FILE__) . 'public/partials/caddy-public-window.php');
				?>
			</div>
		</div>

		<!-- Overlay (same as original) -->
		<div class="cc-overlay" data-wp-class--cc-show="state.isOpen" data-wp-on--click="actions.closeCart"></div>
	</div>
	<?php
}
?>