<?php

/**
 * Resources page for the Caddy plugin.
 *
 * Displays articles, help docs, and recommended plugins.
 *
 * @link       https://www.madebytribe.com
 * @since      3.0.0
 *
 * @package    Caddy
 * @subpackage Caddy/admin/partials
 */

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'caddy' ) );
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine which section to display
$current_section = ( ! empty( $_GET['section'] ) ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'docs';

$sections = array(
	'docs'     => array(
		'name' => __( 'Help Docs', 'caddy' ),
		'icon' => 'dashicons dashicons-book',
	),
	'playbooks' => array(
		'name' => __( 'Playbooks', 'caddy' ),
		'icon' => 'dashicons dashicons-media-document',
	),
	'plugins'  => array(
		'name' => __( 'Recommended Plugins', 'caddy' ),
		'icon' => 'dashicons dashicons-admin-plugins',
	),
);

// --- Data arrays ---

// Fetch playbooks from usecaddy.com RSS feed (cached by WordPress for 12 hours)
$playbooks      = array();
$playbooks_feed = fetch_feed( 'https://usecaddy.com/feed/?post_type=caddy_playbook' );

if ( ! is_wp_error( $playbooks_feed ) ) {
	$feed_items = $playbooks_feed->get_items( 0, 20 );
	foreach ( $feed_items as $item ) {
		$full_title  = $item->get_title();
		$title       = $full_title;
		$description = '';

		// Split "Name: Subtitle" into separate title and description
		if ( strpos( $full_title, ':' ) !== false ) {
			$parts       = explode( ':', $full_title, 2 );
			$title       = trim( $parts[0] );
			$description = trim( $parts[1] );
		}

		$playbooks[] = array(
			'title'       => $title,
			'url'         => $item->get_permalink(),
			'description' => $description,
		);
	}
}

$playbooks = apply_filters( 'caddy_resources_playbooks', $playbooks );

$docs = apply_filters( 'caddy_resources_docs', array(
	array(
		'title'    => __( 'Installing Caddy', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/getting-started/installing-caddy',
		'category' => __( 'Getting Started', 'caddy' ),
	),
	array(
		'title'    => __( 'Activating Your License', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/getting-started/activating-your-license',
		'category' => __( 'Getting Started', 'caddy' ),
		'pro'      => true,
	),
	array(
		'title'    => __( 'How to Configure the Free Shipping Meter', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/configurations/how-to-configure-the-free-shipping-meter',
		'category' => __( 'Configurations', 'caddy' ),
	),
	array(
		'title'    => __( 'How to Configure the Save for Later Feature', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/configurations/how-to-configure-the-save-for-later-feature',
		'category' => __( 'Configurations', 'caddy' ),
	),
	array(
		'title'    => __( 'How Do You Add a Caddy Cart Widget to Your Header or Navigation Menu?', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/configurations/how-do-you-add-a-caddy-cart-widget-to-your-header-or-navigation-menu',
		'category' => __( 'Configurations', 'caddy' ),
	),
	array(
		'title'    => __( 'Customizing Colors', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/configurations/customizing-colors',
		'category' => __( 'Configurations', 'caddy' ),
		'pro'      => true,
	),
	array(
		'title'    => __( 'Customizing Messaging', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/configurations/customizing-messaging',
		'category' => __( 'Configurations', 'caddy' ),
		'pro'      => true,
	),
	array(
		'title'    => __( 'How to Hide the Floating Caddy Icon', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/configurations/how-to-hide-the-floating-caddy-icon',
		'category' => __( 'Configurations', 'caddy' ),
	),
	array(
		'title'    => __( 'How to Add Custom CSS in Caddy', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/configurations/how-to-add-custom-css-in-caddy',
		'category' => __( 'Configurations', 'caddy' ),
	),
	array(
		'title'    => __( 'Understanding Caddy Conversion Tracking', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/analytics-tracking/understanding-caddy-conversion-tracking',
		'category' => __( 'Analytics & Tracking', 'caddy' ),
		'pro'      => true,
	),
	array(
		'title'    => __( 'Where Can I Find My License Key?', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/billing-licensing/where-can-i-find-my-license-key',
		'category' => __( 'Billing & Licensing', 'caddy' ),
		'pro'      => true,
	),
	array(
		'title'    => __( 'Cancelling Your License Subscription', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/billing-licensing/cancelling-your-license-subscription',
		'category' => __( 'Billing & Licensing', 'caddy' ),
		'pro'      => true,
	),
	array(
		'title'    => __( 'Staging License Support', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/billing-licensing/staging-license-support',
		'category' => __( 'Billing & Licensing', 'caddy' ),
		'pro'      => true,
	),
	array(
		'title'    => __( 'How to Change the Cart Icon', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/developers/how-to-change-the-cart-icon',
		'category' => __( 'Developers', 'caddy' ),
	),
	array(
		'title'    => __( 'How to Translate Caddy into Different Languages', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/developers/how-to-translate-caddy-into-different-languages',
		'category' => __( 'Developers', 'caddy' ),
	),
	array(
		'title'    => __( 'Caddy Cart Screen Action Hooks', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/developers/caddy-cart-screen-action-hooks',
		'category' => __( 'Developers', 'caddy' ),
	),
	array(
		'title'    => __( 'Troubleshooting & Debugging Caddy Issues', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/troubleshooting/troubleshooting-caddy',
		'category' => __( 'Troubleshooting', 'caddy' ),
	),
	array(
		'title'    => __( 'Known Plugin and Theme Compatibility Issues', 'caddy' ),
		'url'      => 'https://usecaddy.com/docs/troubleshooting/known-plugin-and-theme-compatibility-issues',
		'category' => __( 'Troubleshooting', 'caddy' ),
	),
) );

$plugins = apply_filters( 'caddy_resources_plugins', array(
	array(
		'name'        => __( 'Product Recommendations', 'caddy' ),
		'description' => __( 'Smart, relevancy-scored product suggestions that drive repeat orders.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/product-recommendations/',
		'price'       => __( 'From $99/yr', 'caddy' ),
	),
	array(
		'name'        => __( 'Loyalty and Rewards', 'caddy' ),
		'description' => __( 'Points, tiers, referrals, achievements, and subscription milestones.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/loyalty-and-rewards/',
		'price'       => __( 'From $99/yr', 'caddy' ),
	),
	array(
		'name'        => __( 'Analytics', 'caddy' ),
		'description' => __( 'Conversion rate, AOV, LTV, churn, cohort analysis, and AI insights.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/analytics/',
		'price'       => __( 'From $99/yr', 'caddy' ),
	),
	array(
		'name'        => __( 'Urgency', 'caddy' ),
		'description' => __( 'Countdown timers, stock alerts, shipping deadlines, and social proof.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/urgency/',
		'price'       => __( 'From $99/yr', 'caddy' ),
	),
	array(
		'name'        => __( 'Storewide Sale', 'caddy' ),
		'description' => __( 'Schedule store-wide sales campaigns that drive revenue and conversions.', 'caddy' ),
		'url'         => 'https://www.madebytribe.com/product/storewide-sale/',
		'price'       => __( 'From $79', 'caddy' ),
	),
	array(
		'name'        => __( 'Waitlist', 'caddy' ),
		'description' => __( 'Back-in-stock notifications and Coming Soon mode for products.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/waitlist/',
		'price'       => __( 'From $79', 'caddy' ),
	),
	array(
		'name'        => __( 'CRM', 'caddy' ),
		'description' => __( '360-degree customer dashboard built for WooCommerce.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/crm/',
		'price'       => __( 'From $99/yr', 'caddy' ),
	),
	array(
		'name'        => __( 'Content Gate', 'caddy' ),
		'description' => __( 'Restrict content by purchase, subscription, or membership.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/content-gate/',
		'price'       => __( 'From $79', 'caddy' ),
	),
	array(
		'name'        => __( 'Product Gifting', 'caddy' ),
		'description' => __( 'Let customers buy and send gifts with personalized messages.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/product-gifting/',
		'price'       => __( 'From $79', 'caddy' ),
	),
	array(
		'name'        => __( 'Coupon URLs', 'caddy' ),
		'description' => __( 'Auto-apply WooCommerce coupons via shareable links.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/coupon-urls/',
		'price'       => __( 'From $49', 'caddy' ),
	),
	array(
		'name'        => __( 'Klaviyo ToolKit', 'caddy' ),
		'description' => __( 'WooCommerce + Klaviyo custom events, subscription tracking, and more.', 'caddy' ),
		'url'         => 'https://www.madebytribe.com/product/klaviyo-toolkit-plugin/',
		'price'       => __( 'From $99', 'caddy' ),
	),
	array(
		'name'        => __( 'Coupon Generator for Klaviyo', 'caddy' ),
		'description' => __( 'Dynamically generated WooCommerce coupons for Klaviyo opt-in forms.', 'caddy' ),
		'url'         => 'https://www.madebytribe.com/product/coupon-generator-for-klaviyo/',
		'price'       => __( 'From $99', 'caddy' ),
	),
	array(
		'name'        => __( 'Better Subscription Switcher', 'caddy' ),
		'description' => __( 'Seamless subscription upgrade/downgrade for WooCommerce Subscriptions.', 'caddy' ),
		'url'         => 'https://www.madebytribe.com/product/better-subscription-switcher/',
		'price'       => __( 'From $79', 'caddy' ),
	),
	array(
		'name'        => __( 'RetentionKit', 'caddy' ),
		'description' => __( 'Exit surveys, renewal discount offers, and churn tracking.', 'caddy' ),
		'url'         => 'https://getretentionkit.com/',
		'price'       => __( 'From $99', 'caddy' ),
	),
	array(
		'name'        => __( 'Payouts', 'caddy' ),
		'description' => __( 'Automated split payouts to multiple Stripe accounts via Stripe Connect.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/payouts/',
		'price'       => __( 'From $199', 'caddy' ),
	),
	array(
		'name'        => __( 'Licensing', 'caddy' ),
		'description' => __( 'Sell WordPress plugins and themes with license keys and auto-updates.', 'caddy' ),
		'url'         => 'https://www.refinerywp.com/products/licensing/',
		'price'       => __( 'From $99/yr', 'caddy' ),
	),
) );

?>

<div class="wrap">
	<?php do_action( 'caddy_admin_header' ); ?>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $sections as $key => $section ) : ?>
			<a class="nav-tab<?php echo ( $key === $current_section ) ? ' nav-tab-active' : ''; ?>"
			   href="?page=caddy-resources&amp;section=<?php echo esc_attr( $key ); ?>">
				<i class="<?php echo esc_attr( $section['icon'] ); ?>"></i>&nbsp;<?php echo esc_html( $section['name'] ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<?php if ( 'playbooks' === $current_section ) : ?>

		<?php if ( ! empty( $playbooks ) ) : ?>
			<div class="cc-resources-list">
				<?php foreach ( $playbooks as $playbook ) : ?>
					<div class="cc-resources-item">
						<a href="<?php echo esc_url( $playbook['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $playbook['title'] ); ?>
						</a>
						<?php if ( ! empty( $playbook['description'] ) ) : ?>
							<p><?php echo esc_html( wp_trim_words( $playbook['description'], 20 ) ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="cc-resources-list">
				<div class="cc-resources-item">
					<p><?php esc_html_e( 'No playbooks available right now. Check back soon!', 'caddy' ); ?></p>
				</div>
			</div>
		<?php endif; ?>

	<?php elseif ( 'docs' === $current_section ) : ?>

		<?php
		// Group docs by category
		$docs_by_category = array();
		foreach ( $docs as $doc ) {
			$cat = ! empty( $doc['category'] ) ? $doc['category'] : __( 'General', 'caddy' );
			$docs_by_category[ $cat ][] = $doc;
		}
		?>

		<?php foreach ( $docs_by_category as $category_name => $category_docs ) : ?>
			<h3 class="cc-resources-category-heading"><?php echo esc_html( $category_name ); ?></h3>
			<div class="cc-resources-list">
				<?php foreach ( $category_docs as $doc ) : ?>
					<div class="cc-resources-item">
						<a href="<?php echo esc_url( $doc['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $doc['title'] ); ?>
							<?php if ( ! empty( $doc['pro'] ) ) : ?>
								<span class="caddy-pro-label"><?php esc_html_e( 'Pro', 'caddy' ); ?></span>
							<?php endif; ?>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>

	<?php elseif ( 'plugins' === $current_section ) : ?>

		<div class="cc-resources-plugins-header">
			<h3><?php esc_html_e( 'Recommended WooCommerce Plugins', 'caddy' ); ?></h3>
			<p><?php esc_html_e( 'Handpicked tools to grow your store.', 'caddy' ); ?></p>
		</div>

		<div class="cc-resources-plugins-grid">
			<?php foreach ( $plugins as $plugin ) : ?>
				<div class="cc-addon cc-resources-plugin<?php echo ! empty( $plugin['featured'] ) ? ' cc-resources-plugin-featured' : ''; ?>">
					<span class="cc-resources-plugin-icon"><?php echo esc_html( mb_substr( $plugin['name'], 0, 1 ) ); ?></span>
					<h4 class="addon-title"><?php echo esc_html( $plugin['name'] ); ?></h4>
					<p class="addon-description"><?php echo esc_html( $plugin['description'] ); ?></p>
					<div class="cc-resources-plugin-footer">
						<span class="cc-resources-plugin-price"><?php echo esc_html( $plugin['price'] ); ?></span>
						<a class="button addon-button" href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Get Plugin', 'caddy' ); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>
</div>
