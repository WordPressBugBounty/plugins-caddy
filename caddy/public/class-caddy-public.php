<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Caddy
 * @subpackage Caddy/public
 * @author     Tribe Interactive <success@madebytribe.co>
 */
class Caddy_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Prevent WooCommerce mini cart block from registering
		add_action('init', array($this, 'prevent_mini_cart_block_registration'), 20);

	}

	/**
	 * Prevent WooCommerce mini cart block from registering
	 *
	 * @since 2.1.4
	 */
	public function prevent_mini_cart_block_registration() {
		// Unregister the mini-cart block to prevent it from rendering
		if (WP_Block_Type_Registry::get_instance()->is_registered('woocommerce/mini-cart')) {
			unregister_block_type('woocommerce/mini-cart');
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking for page builder preview modes to avoid loading styles
		if (isset($_GET['elementor-preview']) || isset($_GET['et_fb'])) {
			return;
		}

		wp_enqueue_style('caddy-public', CADDY_DIR_URL . '/public/css/caddy-public.css', array(), $this->version, 'all');
		wp_enqueue_style('caddy-icons', CADDY_DIR_URL . '/public/css/caddy-icons.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Interactivity API handles all cart interactions via caddy.js
		// This method kept for potential future frontend scripts
	}

	/**
	 * Load the cc widget
	 *
	 * @deprecated Interactivity API block handles cart display
	 */
	public function cc_load_widget() {
		// Interactivity API block handles cart display
		// This method kept for backward compatibility but does nothing
	}



	/**
	 * Cart screen template.
	 */
	public static function cc_cart_screen() {
		include( plugin_dir_path( __FILE__ ) . 'partials/caddy-public-cart.php' );
	}

	/**
	 * Save for later template.
	 *
	 * @deprecated 2.1.0 Use Caddy_Save_For_Later::render_saved_list() instead
	 */
	public static function cc_sfl_screen() {
		if ( class_exists( 'Caddy_Save_For_Later' ) ) {
			$sfl = new Caddy_Save_For_Later( 'caddy', CADDY_VERSION );
			$sfl->render_saved_list();
		}
	}


	/**
	 * Send product removed response with proper fragments
	 */
	private function send_product_removed_response($message) {
		WC()->cart->calculate_totals();
		WC()->cart->maybe_set_cart_cookies();
		
		$fragments = apply_filters('woocommerce_add_to_cart_fragments', array());
		$cart_hash = WC()->cart->get_cart_hash();
		
		wp_send_json(array(
			'product_removed' => true,
			'message' => $message,
			'fragments' => $fragments,
			'cart_hash' => $cart_hash
		));
	}

	/**
	 * Window screen template.
	 */
	public function cc_window_screen() {
		include( plugin_dir_path( __FILE__ ) . 'partials/caddy-public-window.php' );
	}


	/**
	 * Saved items short-code.
	 *
	 * @deprecated 2.1.0 Use Caddy_Save_For_Later::saved_items_shortcode() instead
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function cc_saved_items_shortcode( $atts ) {

		$cc_enable_sfl_options = get_option( 'cc_enable_sfl_options', 'enabled' );
		if ( 'disabled' === $cc_enable_sfl_options ) {
			return '';
		}

		$default = array(
			'text' => '',
			'icon' => '',
		);

		$attributes         = shortcode_atts( $default, $atts );
		$attributes['text'] = ! empty( $attributes['text'] ) ? $attributes['text'] : $default['text'];

		$saved_items_link = sprintf(
			'<a href="%1$s" class="cc_saved_items_list" aria-label="%2$s">%3$s %4$s</a>',
			'javascript:void(0);',
			esc_attr__( 'Saved Items', 'caddy' ),
			( 'yes' === $attributes['icon'] ) ? '<i class="ccicon-heart-empty"></i>' : '',
			esc_html( $attributes['text'] )
		);

		return $saved_items_link;
	}

	/**
	 * Cart items short-code.
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function cc_cart_items_shortcode( $atts ) {

		$default = array(
			'text' => '',
			'icon' => '',
		);

		$cart_items_link    = '';
		$attributes         = shortcode_atts( $default, $atts );
		$attributes['text'] = ! empty( $attributes['text'] ) ? $attributes['text'] : $default['text'];

		$cart_count      = '';
		$cc_cart_class   = '';
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
		$cart_icon_class = wp_kses( apply_filters( 'caddy_cart_bubble_icon', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-0.5 -0.5 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M5.75 1.9166666666666667 2.875 5.75v13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 1.9166666666666667h13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 -1.9166666666666667V5.75l-2.875 -3.8333333333333335z" stroke-width="1.2"></path><path d="m2.875 5.75 17.25 0" stroke-width="1.2"></path><path d="M15.333333333333334 9.583333333333334a3.8333333333333335 3.8333333333333335 0 0 1 -7.666666666666667 0" stroke-width="1.2"></path></svg>' ), $allowed_svg );

		if ( ! is_admin() ) {
			$cart_count = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
			$cc_cart_class = ( $cart_count == 0 ) ? 'cc_cart_count cc_cart_zero' : 'cc_cart_count';
		}

		ob_start();
		?>
		<a href="javascript:void(0);" class="cc_cart_items_list" aria-label="<?php echo esc_attr__( 'Cart Items', 'caddy' ); ?>">
			<?php if ( 'yes' === $attributes['icon'] ) : ?>
				<?php echo $cart_icon_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped via wp_kses above ?>
			<?php endif; ?>
			<span class="<?php echo esc_attr( $cc_cart_class ); ?>"><?php echo esc_html( $cart_count ); ?></span>
			<?php if ( ! empty( $attributes['text'] ) ) : ?>
				<span><?php echo esc_html( $attributes['text'] ); ?></span>
			<?php endif; ?>
		</a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display caddy cart bubble icon
	 *
	 * @param string $cart_icon_class Cart icon class HTML
	 * @return string
	 */
	public function cc_display_cart_bubble_icon( $cart_icon_class ) {
		return $cart_icon_class;
	}

	/**
	 * Add product to save for later button.
	 */
	public function cc_add_product_to_sfl() {

		$cc_enable_sfl_options = get_option( 'cc_enable_sfl_options' );
		$cc_sfl_btn_on_product = get_option( 'cc_sfl_btn_on_product' );
		$current_user_id       = get_current_user_id();
		$cc_sfl_items_array    = get_user_meta( $current_user_id, 'cc_save_for_later_items', true ); // phpcs:ignore
		$cc_sfl_items_array    = ! empty( $cc_sfl_items_array ) ? $cc_sfl_items_array : array();

		if ( is_user_logged_in() && 'enabled' === $cc_sfl_btn_on_product && 'enabled' === $cc_enable_sfl_options ) {
			global $product;
			$product_id   = $product->get_id();
			$product_type = $product->get_type();

			if ( in_array( $product_id, $cc_sfl_items_array ) ) {
				echo sprintf(
					'<a href="%1$s" class="button cc-sfl-btn remove_from_sfl_button" data-product_id="%2$s" data-product_type="%3$s"><i class="ccicon-heart-filled"></i> <span>%4$s</span></a>',
					'javascript:void(0);',
					esc_attr( $product_id ),
					esc_attr( $product_type ),
					esc_html__( 'Saved', 'caddy' )
				);
			} else {
				echo sprintf(
					'<a href="%1$s" class="button cc-sfl-btn cc_add_product_to_sfl" data-product_id="%2$s" data-product_type="%3$s"><i class="ccicon-heart-empty"></i> <span>%4$s</span></a>',
					'javascript:void(0);',
					esc_attr( $product_id ),
					esc_attr( $product_type ),
					esc_html__( 'Save for later', 'caddy' )
				);
			}
		}
	}

	/**
	 * Hide 'Added to Cart' message.
	 *
	 * @param string $message The HTML message
	 * @param array $products Array of product IDs and quantities
	 * @return string Empty string to hide the message
	 */
	public function cc_empty_wc_add_to_cart_message( $message, $products ) {
		return '';
	}

	/**
	 * Caddy load Custom CSS added to custom css box into footer.
	 */
	public function cc_load_custom_css() {

		$cc_custom_css = get_option( 'cc_custom_css' );
		if ( ! empty( $cc_custom_css ) ) {
			echo '<style>' . wp_strip_all_tags( stripslashes( $cc_custom_css ) ) . '</style>';
		}
	}

	/**
	 * Display compass icon
	 */
	public function cc_display_compass_icon() {
		if ( ! class_exists( 'Caddy_Premium' ) ) {
			$cart_count = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
			$cc_cart_zero = ( $cart_count == 0 ) ? ' cc-cart-zero' : '';
			?>
			<div class="cc-compass" data-wp-interactive="caddy/cart" data-wp-on--click="actions.toggleCart">
				<span class="licon"></span>
				<div class="cc-loader" style="display: none;"></div>
				<span class="cc-compass-count"
				      data-wp-text="state.cartCount"
				      data-wp-class--cc-cart-zero="state.cartCount === 0">
					<?php echo esc_html( $cart_count ); ?>
				</span>
			</div>
			<?php
		}
	}

	/**
	 * Display up-sells slider in product added screen
	 *
	 * @param int $product_id Product ID
	 */
	public function cc_display_product_upsells_slider( $product_id ) {
		if ( ! class_exists( 'Caddy_Premium' ) && ! empty( $product_id ) ) {
			include( plugin_dir_path( __FILE__ ) . 'partials/caddy-public-recommendations.php' );
		}
	}

	/**
	 * Display free shipping congrats text
	 *
	 * @param string $cc_shipping_country Shipping country code
	 */
	public function caddy_display_free_shipping_congrats_text( $cc_shipping_country ) {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M22.87,7.1A.24.24,0,0,0,23,6.86a.23.23,0,0,0-.15-.21L16,3.92a1.13,1.13,0,0,0-.9,0L13,4.94a.24.24,0,0,0-.14.23.24.24,0,0,0,.15.22l6.94,3.07a.52.52,0,0,0,.44,0Z" fill="currentColor"></path><path d="M16.61,19.85a.27.27,0,0,0,.12.22.26.26,0,0,0,.24,0l6.36-3.18a1.12,1.12,0,0,0,.62-1V8.06a.26.26,0,0,0-.13-.22.25.25,0,0,0-.24,0L16.74,11.5a.26.26,0,0,0-.13.22Z" fill="currentColor"></path><path d="M7.52,8.31a.24.24,0,0,0-.23,0,.23.23,0,0,0-.11.2c0,.56,0,2.22,0,7.41a1.11,1.11,0,0,0,.68,1l7.42,3.16a.21.21,0,0,0,.23,0,.24.24,0,0,0,.12-.21V11.78a.26.26,0,0,0-.16-.23Z" fill="currentColor"></path><path d="M15.87,10.65a.54.54,0,0,0,.43,0l2.3-1.23a.26.26,0,0,0,.13-.23.24.24,0,0,0-.15-.22L11.5,5.82a.48.48,0,0,0-.42,0L8.31,7.12a.24.24,0,0,0-.14.23.23.23,0,0,0,.15.22Z" fill="currentColor"></path><path d="M5,13.76,1.07,11.94a.72.72,0,0,0-1,.37.78.78,0,0,0,.39,1l3.9,1.8a.87.87,0,0,0,.31.07.73.73,0,0,0,.67-.43A.75.75,0,0,0,5,13.76Z" fill="currentColor"></path><path d="M5,10.31,2.68,9.23a.74.74,0,0,0-1,.36.75.75,0,0,0,.36,1L4.4,11.65a.7.7,0,0,0,.31.07A.74.74,0,0,0,5,10.31Z" fill="currentColor"></path><path d="M5,6.86,3.91,6.35a.73.73,0,0,0-1,.36.74.74,0,0,0,.36,1L4.4,8.2a.7.7,0,0,0,.31.07A.74.74,0,0,0,5,6.86Z" fill="currentColor"></path></g></svg>';

		echo sprintf(
			'<span class="cc-fs-icon">%1$s</span>%2$s<strong> %3$s <span class="cc-fs-country">%4$s</span> %5$s</strong>!',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded safe HTML
			$svg,
			esc_html__( 'Congrats, you\'ve activated', 'caddy' ),
			esc_html__( 'free', 'caddy' ),
			esc_html( $cc_shipping_country ),
			esc_html__( 'shipping', 'caddy' )
		);
	}

	/**
	 * Display free shipping spend text
	 *
	 * @param float  $free_shipping_remaining_amount Amount remaining for free shipping
	 * @param string $cc_shipping_country Shipping country code
	 */
	public function caddy_display_free_shipping_spend_text( $free_shipping_remaining_amount, $cc_shipping_country ) {
		if ( empty( $cc_shipping_country ) ) {
			$cc_shipping_country = get_option( 'cc_shipping_country' );
		}
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M22.87,7.1A.24.24,0,0,0,23,6.86a.23.23,0,0,0-.15-.21L16,3.92a1.13,1.13,0,0,0-.9,0L13,4.94a.24.24,0,0,0-.14.23.24.24,0,0,0,.15.22l6.94,3.07a.52.52,0,0,0,.44,0Z" fill="currentColor"></path><path d="M16.61,19.85a.27.27,0,0,0,.12.22.26.26,0,0,0,.24,0l6.36-3.18a1.12,1.12,0,0,0,.62-1V8.06a.26.26,0,0,0-.13-.22.25.25,0,0,0-.24,0L16.74,11.5a.26.26,0,0,0-.13.22Z" fill="currentColor"></path><path d="M7.52,8.31a.24.24,0,0,0-.23,0,.23.23,0,0,0-.11.2c0,.56,0,2.22,0,7.41a1.11,1.11,0,0,0,.68,1l7.42,3.16a.21.21,0,0,0,.23,0,.24.24,0,0,0,.12-.21V11.78a.26.26,0,0,0-.16-.23Z" fill="currentColor"></path><path d="M15.87,10.65a.54.54,0,0,0,.43,0l2.3-1.23a.26.26,0,0,0,.13-.23.24.24,0,0,0-.15-.22L11.5,5.82a.48.48,0,0,0-.42,0L8.31,7.12a.24.24,0,0,0-.14.23.23.23,0,0,0,.15.22Z" fill="currentColor"></path><path d="M5,13.76,1.07,11.94a.72.72,0,0,0-1,.37.78.78,0,0,0,.39,1l3.9,1.8a.87.87,0,0,0,.31.07.73.73,0,0,0,.67-.43A.75.75,0,0,0,5,13.76Z" fill="currentColor"></path><path d="M5,10.31,2.68,9.23a.74.74,0,0,0-1,.36.75.75,0,0,0,.36,1L4.4,11.65a.7.7,0,0,0,.31.07A.74.74,0,0,0,5,10.31Z" fill="currentColor"></path><path d="M5,6.86,3.91,6.35a.73.73,0,0,0-1,.36.74.74,0,0,0,.36,1L4.4,8.2a.7.7,0,0,0,.31.07A.74.74,0,0,0,5,6.86Z" fill="currentColor"></path></g></svg>';

		echo sprintf(
			'<span class="cc-fs-icon">%1$s</span>%2$s<strong> <span class="cc-fs-amount">%3$s</span> %4$s</strong> %5$s <strong>%6$s <span class="cc-fs-country">%7$s</span> %8$s</strong>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded safe HTML
			$svg,
			esc_html__( 'Spend', 'caddy' ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_price returns escaped HTML
			wc_price( $free_shipping_remaining_amount, array( 'currency' => get_woocommerce_currency() ) ),
			esc_html__( 'more', 'caddy' ),
			esc_html__( 'to get', 'caddy' ),
			esc_html__( 'free', 'caddy' ),
			esc_html( $cc_shipping_country ),
			esc_html__( 'shipping', 'caddy' )
		);
	}

	/**
	 * Free shipping bar html
	 */
	public function cc_free_shipping_bar_html() {
		if ( ! class_exists( 'Caddy_Premium' ) ) {
			
			$calculate_with_tax = 'enabled' === get_option('cc_free_shipping_tax', 'disabled');
			$final_cart_subtotal = $calculate_with_tax ? WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() : WC()->cart->get_displayed_subtotal();

			$cc_free_shipping_amount = get_option( 'cc_free_shipping_amount' );

			$free_shipping_remaining_amount = floatval( $cc_free_shipping_amount ) - floatval( $final_cart_subtotal );
			$free_shipping_remaining_amount = ! empty( $free_shipping_remaining_amount ) ? $free_shipping_remaining_amount : 0;
			$cc_bar_amount = 100;
			if ( ! empty( $cc_free_shipping_amount ) && $final_cart_subtotal <= $cc_free_shipping_amount ) {
				$cc_bar_amount = $final_cart_subtotal * 100 / $cc_free_shipping_amount;
			}

			$cc_shipping_country = get_option( 'cc_shipping_country' );
			if ( 'GB' === $cc_shipping_country ) {
				$cc_shipping_country = 'UK';
			}

			$cc_bar_active = ( $final_cart_subtotal >= $cc_free_shipping_amount ) ? ' cc-bar-active' : '';
			?>
			<span class="cc-fs-title">
				<?php
				if ( $final_cart_subtotal >= $cc_free_shipping_amount ) {
					do_action( 'caddy_fs_congrats_text', $cc_shipping_country );
				} else {
					do_action( 'caddy_fs_spend_text', $free_shipping_remaining_amount, $cc_shipping_country );
				}
				?>
			</span>
			<div class="cc-fs-meter">
				<span class="cc-fs-meter-used<?php echo esc_attr( $cc_bar_active ); ?>" style="width: <?php echo esc_attr( $cc_bar_amount ); ?>%"></span>
			</div>
			<?php
		}
	}

	/**
	 * Cart items array list for the cc-cart screen
	 *
	 * @param array $cart_items_array
	 */
	public static function cart_items_list( $cart_items_array = array() ) {
		if ( ! empty( $cart_items_array ) ) {
			foreach ( $cart_items_array as $cart_item_key => $cart_item ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

				if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ||
					 ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key )
				) {
					continue;
				}

				$product_id = $_product->get_id();
				$template_args = self::prepare_cart_item_data( $_product, $cart_item, $cart_item_key );
				include plugin_dir_path( __FILE__ ) . 'partials/caddy-public-cart-item.php';
			}
		}
	}

	/**
	 * Prepare cart item data for template
	 *
	 * @since 2.1.3
	 * @param WC_Product $product
	 * @param array $cart_item
	 * @param string $cart_item_key
	 * @return array Template arguments
	 */
	public static function prepare_cart_item_data( $product, $cart_item, $cart_item_key ) {
		$product_id = $product->get_id();
		$product_name = apply_filters( 'woocommerce_cart_item_name', wp_specialchars_decode( $product->get_name(), ENT_QUOTES ), $cart_item, $cart_item_key );
		$product_image = self::get_product_image( $product, $product_name, $cart_item, $cart_item_key );
		$product_regular_price = $product->get_regular_price();
		$product_sale_price    = $product->get_sale_price();
		$percentage = 0;
		if ( ! empty( $product_sale_price ) && $product_regular_price > 0 ) {
			$percentage = ( ( $product_regular_price - $product_sale_price ) * 100 ) / $product_regular_price;
		}

		$product_stock_qty = $product->get_stock_quantity();
		$product_permalink = apply_filters( 'woocommerce_cart_item_permalink',
			$product->is_visible() ? $product->get_permalink( $cart_item ) : '',
			$cart_item,
			$cart_item_key
		);

		$is_bundle_container = function_exists('wc_pb_is_bundle_container_cart_item') &&
							   wc_pb_is_bundle_container_cart_item($cart_item);
		$is_bundled_item = function_exists('wc_pb_is_bundled_cart_item') &&
						   wc_pb_is_bundled_cart_item($cart_item);
		$is_free_gift = isset($cart_item['caddy_free_gift']) && $cart_item['caddy_free_gift'];
		$plus_disable = '';
		if ( $product_stock_qty > 0 ) {
			if ( ( $product_stock_qty <= $cart_item['quantity'] && ! $product->backorders_allowed() )) {
				$plus_disable = ' cc-qty-disabled';
			}
		}

		$quantity_args = apply_filters('woocommerce_quantity_input_args', array(
			'input_name'  => "cart[{$cart_item_key}][qty]",
			'input_value' => $cart_item['quantity'],
			'max_value'   => $product->get_max_purchase_quantity(),
			'min_value'   => '0',
			'product_name' => wp_specialchars_decode( $product->get_name(), ENT_QUOTES ),
		), $product);

		$product_subtotal = self::get_product_subtotal_html($product, $cart_item, $cart_item_key, $is_free_gift);
		$savings_html = self::get_savings_html($product, $cart_item, $is_free_gift);
		$show_save_for_later = false;
		if (is_user_logged_in() && !$is_free_gift) {
			$cc_enable_sfl_options = get_option('cc_enable_sfl_options', 'enabled');
			$show_save_for_later = ('enabled' === $cc_enable_sfl_options);
		}

		return array(
			'_product' => $product,
			'cart_item' => $cart_item,
			'cart_item_key' => $cart_item_key,
			'product_id' => $product_id,
			'product_name' => $product_name,
			'product_image' => $product_image,
			'product_permalink' => $product_permalink,
			'product_subtotal' => $product_subtotal,
			'product_stock_qty' => $product_stock_qty,
			'quantity_args' => $quantity_args,
			'is_free_gift' => $is_free_gift,
			'is_bundle_container' => $is_bundle_container,
			'is_bundled_item' => $is_bundled_item,
			'plus_disable' => $plus_disable,
			'savings_html' => $savings_html,
			'show_save_for_later' => $show_save_for_later,
			'percentage' => $percentage, // Kept for potential future use
			'price' => $product->get_price(), // Current price for data-item-price attribute
		);
	}

	/**
	 * Get product image HTML
	 *
	 * @since 2.1.3
	 * @param WC_Product $product
	 * @param string $product_name
	 * @param array $cart_item
	 * @param string $cart_item_key
	 * @return string
	 */
	private static function get_product_image($product, $product_name, $cart_item, $cart_item_key) {
		$image_id = $product->get_image_id();

		if ($image_id) {
			// Try woocommerce_thumbnail first
			$image_src = wp_get_attachment_image_src($image_id, 'woocommerce_thumbnail');

			// Fallback to full size if thumbnail doesn't exist
			if (!$image_src || empty($image_src[0])) {
				$image_src = wp_get_attachment_image_src($image_id, 'full');
			}

			if ($image_src) {
				$thumbnail_size = wc_get_image_size('woocommerce_thumbnail');
				$product_image = sprintf(
					'<img width="%d" height="%d" src="%s" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="%s" decoding="async" loading="lazy">',
					$thumbnail_size['width'],
					$thumbnail_size['height'],
					esc_url($image_src[0]),
					esc_attr($product_name)
				);
			} else {
				$product_image = wc_placeholder_img('woocommerce_thumbnail');
			}
		} else {
			$product_image = wc_placeholder_img('woocommerce_thumbnail');
		}

		return apply_filters('woocommerce_cart_item_thumbnail', $product_image, $cart_item, $cart_item_key);
	}


	/**
	 * Get product subtotal HTML with sale price handling
	 *
	 * @since 2.1.3
	 * @param WC_Product $product
	 * @param array $cart_item
	 * @param string $cart_item_key
	 * @param bool $is_free_gift
	 * @return string
	 */
	private static function get_product_subtotal_html($product, $cart_item, $cart_item_key, $is_free_gift) {
		if ($is_free_gift) {
			return '<span class="cc-free-price">' . esc_html__('Free', 'caddy') . '</span>';
		}

		$product_subtotal = apply_filters('woocommerce_cart_item_subtotal',
			WC()->cart->get_product_subtotal($product, $cart_item['quantity']),
			$cart_item,
			$cart_item_key
		);

		if ($product->is_on_sale() && strpos($product_subtotal, '<del>') === false) {
			$tax_display = get_option('woocommerce_tax_display_cart');

			if ('incl' === $tax_display) {
				$regular_price = wc_get_price_including_tax($product, array(
					'qty' => $cart_item['quantity'],
					'price' => $product->get_regular_price()
				));
				$sale_price = wc_get_price_including_tax($product, array(
					'qty' => $cart_item['quantity'],
					'price' => $product->get_sale_price()
				));
			} else {
				$regular_price = wc_get_price_excluding_tax($product, array(
					'qty' => $cart_item['quantity'],
					'price' => $product->get_regular_price()
				));
				$sale_price = wc_get_price_excluding_tax($product, array(
					'qty' => $cart_item['quantity'],
					'price' => $product->get_sale_price()
				));
			}

			$product_subtotal = '<del>' . wc_price($regular_price) . '</del> ' . wc_price($sale_price);
		}

		return $product_subtotal;
	}

	/**
	 * Get savings percentage HTML
	 *
	 * @since 2.1.3
	 * @param WC_Product $product
	 * @param array $cart_item
	 * @param bool $is_free_gift
	 * @return string
	 */
	private static function get_savings_html($product, $cart_item, $is_free_gift) {
		if (!$product->is_on_sale() || $is_free_gift) {
			return '';
		}

		$tax_display = get_option('woocommerce_tax_display_cart');

		if ('incl' === $tax_display) {
			$regular_price = wc_get_price_including_tax($product, array(
				'qty' => $cart_item['quantity'],
				'price' => $product->get_regular_price()
			));
			$sale_price = wc_get_price_including_tax($product, array(
				'qty' => $cart_item['quantity'],
				'price' => $product->get_sale_price()
			));
		} else {
			$regular_price = wc_get_price_excluding_tax($product, array(
				'qty' => $cart_item['quantity'],
				'price' => $product->get_regular_price()
			));
			$sale_price = wc_get_price_excluding_tax($product, array(
				'qty' => $cart_item['quantity'],
				'price' => $product->get_sale_price()
			));
		}

		$savings = $regular_price - $sale_price;
		if ($savings > 0 && $regular_price > 0) {
			$savings_percentage = round(($savings / $regular_price) * 100);
			return '<div class="cc_saved_amount">' .
				   sprintf(esc_html__('(Save %s)', 'caddy'), $savings_percentage . '%') .
				   '</div>';
		}

		return '';
	}

	public function caddy_add_cart_widget_to_menu($items, $args) {
		$menu_slug = '';
		
		// Handle cases where menu is passed as object or string
		if (is_object($args->menu) && property_exists($args->menu, 'slug')) {
			$menu_slug = $args->menu->slug;
		} elseif (is_string($args->menu)) {
			$menu_slug = $args->menu;
		}

		if ($menu_slug === get_option('cc_menu_cart_widget')) {
			$cart_widget = new caddy_cart_widget();

			// Simulate the arguments required for the widget method
			$widget_args = array(
				'before_widget' => '<li class="menu-item">',
				'after_widget'  => '</li>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>'
			);
			$instance = array(); // Adjust or populate as needed

			// Use output buffering to capture the widget output
			ob_start();
			$cart_widget->widget($widget_args, $instance);
			$widget_output = ob_get_clean();

			// Append the widget output to the menu items
			$items .= $widget_output;
		}

		return $items;
	}

	public function caddy_add_saves_widget_to_menu($items, $args) {
		// Check if user is logged in
		if (!is_user_logged_in()) {
			return $items;
		}

		$cc_enable_sfl_options = get_option( 'cc_enable_sfl_options', 'enabled' );
		if ( 'disabled' === $cc_enable_sfl_options ) {
			return $items;
		}

		$menu_slug = '';
		if (is_object($args->menu) && property_exists($args->menu, 'slug')) {
			$menu_slug = $args->menu->slug;
		} elseif (is_string($args->menu)) {
			$menu_slug = $args->menu;
		}

		if ($menu_slug === get_option('cc_menu_saves_widget')) {
			$save_for_later_widget = new caddy_saved_items_widget();

			// Simulate the arguments required for the widget method
			$widget_args = array(
				'before_widget' => '<li class="menu-item">',
				'after_widget'  => '</li>',
				'before_title'  => '', // Title wrappers removed
				'after_title'   => ''
			);

			// Provide default or expected values for the instance
			$instance = array(
				'si_text'    => __('Saves', 'caddy'),  // Default text
				'cc_si_icon' => 'off'                  // Set icon display behavior
			);

			// Use output buffering to capture the widget output
			ob_start();
			$save_for_later_widget->widget($widget_args, $instance);
			$widget_output = ob_get_clean();

			// Append the widget output to the menu items
			$items .= $widget_output;
		}

		return $items;
	}

	/**
	 * Prevent redirect to cart page after adding item
	 */
	public function prevent_cart_redirect($value) {
		return false;
	}

	/**
	 * Handle post-add-to-cart actions
	 *
	 * @param string $cart_item_key Cart item key
	 * @param int    $product_id Product ID
	 * @param int    $quantity Quantity added
	 * @param int    $variation_id Variation ID
	 * @param array  $variation Variation data
	 * @param array  $cart_item_data Cart item data
	 */
	public function after_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
		WC()->cart->calculate_totals();
	}




	/**
	 * Always validate add to cart
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function validate_add_to_cart() {
		return true;
	}

	/**
	 * Exclude cart-related endpoints from caching
	 *
	 * @param array $uri Array of URIs to exclude from caching
	 * @return array Modified array of URIs
	 */
	public function exclude_cart_endpoints_from_cache($uri) {
		$cart_endpoints = array(
			'/admin-ajax\.php\?action=cc_remove_item_from_cart',
			'/admin-ajax\.php\?action=cc_get_refreshed_fragments',
			'/admin-ajax\.php\?action=cc_get_cart_fragments',
			'/admin-ajax\.php\?action=caddy_get_cart_fragments',
			'/admin-ajax\.php\?action=cc_update_item_quantity',
			'/admin-ajax\.php\?action=cc_save_for_later',
			'/admin-ajax\.php\?action=cc_move_to_cart',
			'/admin-ajax\.php\?action=cc_remove_saved_item',
			'/admin-ajax\.php\?action=cc_apply_coupon',
			'/admin-ajax\.php\?action=cc_remove_coupon'
		);

		return array_merge($uri, $cart_endpoints);
	}



}