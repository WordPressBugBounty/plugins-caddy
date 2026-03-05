<?php
/**
 * Cart Item Template
 *
 * This template displays a single cart item in the Caddy cart
 *
 * @package    Caddy
 * @subpackage Caddy/public/partials
 * @since      2.1.3
 *
 * Variables available from prepare_cart_item_data():
 * @var WC_Product $_product            The product object
 * @var array      $cart_item           Cart item data
 * @var string     $cart_item_key       Cart item key
 * @var string     $product_id          Product ID
 * @var string     $product_name        Product name
 * @var string     $product_image       Product image HTML
 * @var string     $product_permalink   Product permalink
 * @var string     $product_subtotal    Product subtotal HTML
 * @var int        $product_stock_qty   Product stock quantity
 * @var array      $quantity_args       Quantity input arguments
 * @var bool       $is_free_gift        Whether item is a free gift
 * @var bool       $is_bundle_container Whether item is a bundle container
 * @var bool       $is_bundled_item     Whether item is bundled
 * @var string     $plus_disable        CSS class for disabling plus button
 * @var string     $savings_html        Savings percentage HTML
 * @var bool       $show_save_for_later Whether to show save for later button
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Assign variables explicitly (WPCS: extract() prohibited)
$_product          = $template_args['_product'];
$cart_item         = $template_args['cart_item'];
$cart_item_key     = $template_args['cart_item_key'];
$product_id        = $template_args['product_id'];
$product_name      = $template_args['product_name'];
$product_image     = $template_args['product_image'];
$product_permalink = $template_args['product_permalink'];
$product_subtotal  = $template_args['product_subtotal'];
$product_stock_qty = $template_args['product_stock_qty'];
$quantity_args     = $template_args['quantity_args'];
$is_free_gift      = $template_args['is_free_gift'];
$is_bundle_container = $template_args['is_bundle_container'];
$is_bundled_item   = $template_args['is_bundled_item'];
$plus_disable      = $template_args['plus_disable'];
$savings_html      = $template_args['savings_html'];
$show_save_for_later = $template_args['show_save_for_later'];
$price             = isset($template_args['price']) ? $template_args['price'] : '';
$is_server_rendered = isset($template_args['is_server_rendered']) ? $template_args['is_server_rendered'] : false;

// Set container class based on bundle status
$container_class = 'cc-cart-product-list';
if ($is_bundle_container) {
	$container_class .= ' bundle';
} elseif ($is_bundled_item) {
	$container_class .= ' bundled_child';
}

// Add marker class for server-rendered items
if (isset($is_server_rendered) && $is_server_rendered) {
	$container_class .= ' cc-ssr-item';
}
?>

<div class="<?php echo esc_attr($container_class); ?> cc-cart-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-item-price="<?php echo esc_attr($price); ?>">
	<div class="cc-cart-product">
		<a href="<?php echo esc_url($product_permalink); ?>" class="cc-product-link cc-product-thumb"
		   data-title="<?php echo esc_attr($product_name); ?>">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC product image is already escaped
			echo $product_image;
			?>
		</a>
		<div class="cc_item_content">
			<div class="cc-item-content-top">
				<div class="cc_item_title">
					<?php
					if (!$product_permalink) {
						echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
					} else {
						echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s" class="cc-product-link">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
					}

					// Meta data
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC function returns escaped HTML
					echo wc_get_formatted_cart_item_data($cart_item);

					// Add Free Gift label
					if ($is_free_gift) {
						echo '<div class="cc-free-gift-label">' . esc_html__('Gift Reward', 'caddy') . '</div>';
					}
					?>

					<div class="cc_item_quantity_wrap">
						<?php if (!$_product->is_sold_individually() && strpos($quantity_args['input_value'], 'type="hidden"') === false && !$is_free_gift && !$is_bundled_item): ?>
							<div class="cc_item_quantity_update cc_item_quantity_minus" data-type="minus">−</div>
							<input type="text"
								readonly
								class="cc_item_quantity"
								data-product_id="<?php echo esc_attr($product_id); ?>"
								data-key="<?php echo esc_attr($cart_item_key); ?>"
								value="<?php echo esc_attr($cart_item['quantity']); ?>"
								step="<?php echo esc_attr(apply_filters('woocommerce_quantity_input_step', 1, $_product)); ?>"
								min="<?php echo esc_attr($quantity_args['min_value']); ?>"
								max="<?php echo esc_attr($quantity_args['max_value']); ?>">
							<div class="cc_item_quantity_update cc_item_quantity_plus<?php echo esc_attr($plus_disable); ?>" data-type="plus">+</div>
							<?php do_action('caddy_after_quantity_input', $product_id); ?>
						<?php endif; ?>
					</div>
				</div>

				<?php if (!$is_bundled_item): ?>
					<div class="cc_item_total_price">
						<div class="price">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped HTML from display logic
							echo $product_subtotal;
							?>
						</div>

						<?php if (!empty($savings_html)): ?>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in logic
							echo $savings_html;
							?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="cc-item-content-bottom">
				<div class="cc-item-content-bottom-left">
					<?php if ($show_save_for_later && !$is_bundled_item): ?>
						<div class="cc_sfl_btn">
							<a href="javascript:void(0);"
							   class="button cc-button-sm save_for_later_btn"
							   aria-label="<?php esc_attr_e('Save for later', 'caddy'); ?>"
							   data-product_id="<?php echo esc_attr($product_id); ?>"
							   data-cart_item_key="<?php echo esc_attr($cart_item_key); ?>">
								<?php esc_html_e('Save for later', 'caddy'); ?>
							</a>
							<div class="cc-loader" style="display: none;"></div>
						</div>
					<?php endif; ?>
				</div>

				<?php if (!$is_free_gift && !$is_bundled_item): ?>
					<a href="javascript:void(0);"
					   class="cc-remove-item"
					   aria-label="<?php esc_attr_e('Remove this item', 'caddy'); ?>"
					   data-product_id="<?php echo esc_attr($product_id); ?>"
					   data-cart_item_key="<?php echo esc_attr($cart_item_key); ?>"
					   data-product_name="<?php echo esc_attr($product_name); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5">
							<path stroke="currentColor" d="M1 6H23"></path>
							<path stroke="currentColor" d="M4 6H20V22H4V6Z"></path>
							<path stroke="currentColor" d="M9 10V18"></path>
							<path stroke="currentColor" d="M15 10V18"></path>
							<path stroke="currentColor" d="M8 6V6C8 3.79086 9.79086 2 12 2V2C14.2091 2 16 3.79086 16 6V6"></path>
						</svg>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php
	// Hook after individual product
	do_action('caddy_cart_after_product', $cart_item, $cart_item_key);
	?>
</div>