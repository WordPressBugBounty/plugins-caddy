<?php
/**
 * Caddy Block Registration and Management
 *
 * Handles the registration and functionality of the Caddy cart block
 * with WordPress Interactivity API support.
 *
 * @package    Caddy
 * @subpackage Caddy/includes
 * @since      2.1.3
 */

class Caddy_Block {

	/**
	 * Initialize block registration
	 */
	public static function init() {
		add_action('init', array(__CLASS__, 'register_script_modules'));
		add_action('init', array(__CLASS__, 'register_block'));
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_block_assets'));
		add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue_editor_assets'));
	}

	/**
	 * Register script modules for Interactivity API
	 */
	public static function register_script_modules() {
		if (!class_exists('WooCommerce') || !function_exists('wp_register_script_module')) {
			return;
		}

		// Register our cart script module
		// wp_register_script_module() already skips if module is registered
		$js_file = plugin_dir_path(dirname(__FILE__)) . 'public/js/caddy.js';
		wp_register_script_module(
			'caddy/cart',
			plugin_dir_url(dirname(__FILE__)) . 'public/js/caddy.js',
			array('@wordpress/interactivity'),
			CADDY_VERSION . '.' . (file_exists($js_file) ? filemtime($js_file) : '0')
		);
	}

	/**
	 * Register the Caddy cart block
	 */
	public static function register_block() {
		if (!class_exists('WooCommerce')) {
			return;
		}

		// Skip if block already registered (e.g. by premium)
		if (WP_Block_Type_Registry::get_instance()->is_registered('caddy/cart')) {
			return;
		}

		// Register the block from block.json
		register_block_type(
			plugin_dir_path(dirname(__FILE__)) . 'block.json',
			array(
				'render_callback' => array(__CLASS__, 'render_block'),
			)
		);

	}

	/**
	 * Render the cart block
	 *
	 * @param array $attributes Block attributes
	 * @param string $content Block content
	 * @param WP_Block $block Block instance
	 * @return string
	 */
	public static function render_block($attributes, $content, $block) {
		$cart_state = self::get_cart_state();

		$cart_state['i18n'] = array(
			// Button text
			'addToCart' => __('Add to cart', 'caddy'),
			'seeOptions' => __('Select options', 'caddy'),
			'viewProducts' => __('View products', 'caddy'),
			'saveForLater' => __('Save for later', 'caddy'),
			'saved' => __('Saved', 'caddy'),
			'adding' => __('Adding...', 'caddy'),
			'added' => __('Added!', 'caddy'),
			'addedCheckmark' => __('Added ✓', 'caddy'),
			'error' => __('Error', 'caddy'),
			'errorTryAgain' => __('Error - Try Again', 'caddy'),

			// Cart actions
			'removeItem' => __('Remove this item', 'caddy'),
			'checkout' => __('Checkout Now', 'caddy'),
			'browseProducts' => __('Browse Products', 'caddy'),
			'viewSavedItems' => __('View Saved Items', 'caddy'),

			// Coupon
			'apply' => __('Apply', 'caddy'),
			'promoCode' => __('Promo code', 'caddy'),
			'subtotal' => __('Subtotal', 'caddy'),

			// Error messages
			'errorSaveItemFailed' => __('Failed to save item', 'caddy'),
			'errorRemoveSavedItemFailed' => __('Failed to remove saved item', 'caddy'),
			'errorAddToCartFailed' => __('Failed to add item to cart', 'caddy'),
			'errorUpdateFailed' => __('Failed to update quantity', 'caddy'),
			'errorRemoveFailed' => __('Failed to remove item', 'caddy'),
			'errorMoveToCartFailed' => __('Failed to move item to cart', 'caddy'),
			'errorApplyCouponFailed' => __('Failed to apply coupon', 'caddy'),
			'errorRemoveCouponFailed' => __('Failed to remove coupon', 'caddy'),
			'errorApplyCouponTryAgain' => __('Failed to apply coupon. Please try again.', 'caddy'),
			'errorRemoveCouponTryAgain' => __('Failed to remove coupon. Please try again.', 'caddy'),
			'alertSaveForLaterFailed' => __('Failed to save item for later. Please try again.', 'caddy'),

			// Tab titles
			'yourCart' => __('Your Cart', 'caddy'),
			'yourSaves' => __('Your Saves', 'caddy'),
			'yourOffers' => __('Your Offers', 'caddy'),

			// Recommendations
			'recommendationsEmpty' => __('No recommendations available', 'caddy'),
			'recommendationsLoadError' => __('Unable to load recommendations', 'caddy'),
			'priceFrom' => __('From', 'caddy'),
			'selectAttribute' => __('Select %s', 'caddy'),

			// Rewards meter
			'allRewardsUnlocked' => __('All rewards unlocked!', 'caddy'),
			'spendMoreUnlockReward' => __('Spend {amount} more to unlock your reward', 'caddy'),
			'spendMoreFreeShipping' => __('Spend {amount} more to get free shipping', 'caddy'),
		);

		if (function_exists('wp_interactivity_state')) {
			wp_interactivity_state('caddy/cart', $cart_state);
		} else {
		}

		ob_start();
		include plugin_dir_path(dirname(__FILE__)) . 'render.php';

		$template_content = ob_get_clean();

		if (function_exists('wp_interactivity_process_directives')) {
			$template_content = wp_interactivity_process_directives($template_content);
		} else {
		}

		return $template_content;
	}

	/**
	 * Get current cart state for Interactivity API
	 *
	 * Returns initial state with cart count. Store API populates full cart data.
	 *
	 * @return array Initial cart state
	 */
	public static function get_cart_state() {
		// Use the full cart state from Caddy_Interactivity
		$state = Caddy_Interactivity::get_initial_cart_state();

		// Add block-specific state
		$is_logged_in = is_user_logged_in();
		$sfl_enabled = ('enabled' === get_option('cc_enable_sfl_options', 'enabled'));

		$state['isUserLoggedIn'] = $is_logged_in;
		$state['saveForLaterEnabled'] = $sfl_enabled && $is_logged_in;

		return $state;
	}

	/**
	 * Enqueue block assets for frontend
	 */
	public static function enqueue_block_assets() {
		$has_block = has_block('caddy/cart');
		$should_auto_insert = self::should_auto_insert();


		if (!$has_block && !$should_auto_insert) {
			return;
		}

		if ($should_auto_insert) {
			if (function_exists('wp_enqueue_script_module')) {
				wp_enqueue_script_module('caddy/cart');
			}

			$plugin_url = plugin_dir_url(dirname(__FILE__));
			wp_enqueue_style('caddy-block-style', $plugin_url . 'public/css/caddy-public.css', array(), CADDY_VERSION);
			wp_enqueue_style('caddy-icons-style', $plugin_url . 'public/css/caddy-icons.css', array(), CADDY_VERSION);
		}

		add_action('wp_head', function() {
			static $meta_output = false;
			if ( $meta_output ) {
				return;
			}
			$meta_output = true;
			$thumbnail_size = wc_get_image_size('woocommerce_thumbnail');
			$price_decimal_separator = wc_get_price_decimal_separator();
			$price_trim_zeros = apply_filters( 'woocommerce_price_trim_zeros', false );
			$sample_whole_price = html_entity_decode( strip_tags( wc_price( 1 ) ) );
			if ( ! $price_trim_zeros && false === strpos( $sample_whole_price, $price_decimal_separator ) ) {
				$price_trim_zeros = true;
			}
			$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol() );
			$currency_decimals = wc_get_price_decimals();
			$currency_thousand_separator = wc_get_price_thousand_separator();
			$currency_position = get_option( 'woocommerce_currency_pos', 'left' );
			echo '<meta name="caddy-nonce" content="' . wp_create_nonce('wp_rest') . '">' . "\n";
			echo '<meta name="wc-store-api-nonce" content="' . wp_create_nonce('wc_store_api') . '">' . "\n";
			echo '<meta name="caddy-rest-url" content="' . esc_url( rest_url('caddy/v1/') ) . '">' . "\n";
			echo '<meta name="wc-placeholder-image" content="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '">' . "\n";
			echo '<meta name="wc-thumbnail-size" content="' . esc_attr($thumbnail_size['width']) . 'x' . esc_attr($thumbnail_size['height']) . '">' . "\n";
			echo '<meta name="caddy-currency-symbol" content="' . esc_attr( $currency_symbol ) . '">' . "\n";
			echo '<meta name="caddy-currency-decimals" content="' . esc_attr( $currency_decimals ) . '">' . "\n";
			echo '<meta name="caddy-currency-dec-sep" content="' . esc_attr( $price_decimal_separator ) . '">' . "\n";
			echo '<meta name="caddy-currency-thousand-sep" content="' . esc_attr( $currency_thousand_separator ) . '">' . "\n";
			echo '<meta name="caddy-currency-position" content="' . esc_attr( $currency_position ) . '">' . "\n";
			echo '<meta name="caddy-price-trim-zeros" content="' . esc_attr( $price_trim_zeros ? '1' : '0' ) . '">' . "\n";
			echo '<style>
				.cc-window.cc-show {
					right: 0 !important;
					transition: right 0.3s ease;
				}
				.cc-overlay.cc-show {
					display: block !important;
				}
				.cc-hidden {
					display: none !important;
				}
			</style>' . "\n";
		});
	}

	/**
	 * Enqueue editor assets
	 */
	public static function enqueue_editor_assets() {
		wp_enqueue_script(
			'caddy-cart-editor',
			plugin_dir_url(dirname(__FILE__)) . 'editor.js',
			array('wp-blocks', 'wp-element', 'wp-editor'),
			CADDY_VERSION,
			true
		);

		wp_enqueue_style(
			'caddy-cart-editor-style',
			plugin_dir_url(dirname(__FILE__)) . 'editor.css',
			array('wp-edit-blocks'),
			CADDY_VERSION
		);
	}



	/**
	 * Check if block should be auto-inserted
	 *
	 * @return bool
	 */
	public static function should_auto_insert() {
		static $cached = null;
		if ( $cached !== null ) {
			return $cached;
		}
		$cached = ! is_admin() && ! has_block('caddy/cart');
		return $cached;
	}


	/**
	 * Add block to all pages automatically
	 */
	public static function auto_insert_block() {
		if (self::should_auto_insert()) {
			$attributes = array(
				'cartText' => 'Cart',
				'showIcon' => true,
				'autoOpen' => true
			);

			echo self::render_block($attributes, '', null);
		} else {
		}
	}
}
