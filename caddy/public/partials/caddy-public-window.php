<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$current_user          = wp_get_current_user();
$display_name          = ! empty( $current_user->first_name ) ? $current_user->first_name : $current_user->display_name;
$cart_contents_count   = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

// Get custom browse products URL or default to shop page
$custom_browse_url = get_option('cc_browse_products_url', '');
$shop_page_url = !empty($custom_browse_url) ? $custom_browse_url : get_permalink( wc_get_page_id( 'shop' ) );

// Save for Later setting - enabled by default
$cc_enable_sfl_options = get_option( 'cc_enable_sfl_options', 'enabled' ); // Default to 'enabled'
$cc_sfl_tab_flag       = ( 'enabled' === $cc_enable_sfl_options );
?>
<div class="cc-header cc-text-left" data-wp-interactive="caddy/cart">
	<i class="ccicon-x" data-wp-on--click="actions.closeCart"></i>
	<div class="cc-inner-container">
		<div class="cc-nav">
			<ul data-tabs>
				<li><a href="#cc-cart" class="cc-cart-nav" data-id="cc-cart">
					<?php esc_html_e( 'Your Cart', 'caddy' ); ?>
					<span class="cc-cart-tab-count" data-wp-class--cc-hidden="!state.cartCount">
						(<span data-wp-text="state.cartCount"><?php echo esc_html( $cart_contents_count ); ?></span>)
					</span>
				</a></li>
				<?php if ( is_user_logged_in() && $cc_sfl_tab_flag ) { ?>
					<li><a href="#cc-saves" class="cc-save-nav" data-id="cc-saves">
						<?php esc_html_e( 'Saved Items', 'caddy' ); ?>
						<span class="cc-saved-tab-count" data-wp-class--cc-hidden="!state.savedItemsCount">
							(<span data-wp-text="state.savedItemsCount">0</span>)
						</span>
					</a></li>
				<?php } ?>
				<?php do_action( 'caddy_after_nav_tabs' ); ?>
			</ul>
		</div>
	</div>
</div>

<!-- Cart Screen -->
<div id="cc-cart" class="cc-cart cc-screen-tab">
	<?php Caddy_Public::cc_cart_screen(); ?>
</div>

<!-- Save for later screen -->
<?php if ( is_user_logged_in() ) { ?>
	<div id="cc-saves" class="cc-saves cc-screen-tab">
		<?php
		include( plugin_dir_path( __DIR__ ) . 'partials/caddy-public-saves.php' );
		?>
	</div>
<?php } ?>

<?php do_action( 'caddy_after_screen_tabs' ); ?>