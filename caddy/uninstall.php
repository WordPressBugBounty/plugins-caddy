<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Settings and saved data are kept unless the site defines
 * CADDY_REMOVE_ALL_DATA as true in wp-config.php. Nothing is removed while
 * Caddy Pro is installed, because Pro uses the same settings.
 *
 * @link       https://www.madebytribe.com
 * @since      1.0.0
 *
 * @package    Caddy
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'CADDY_REMOVE_ALL_DATA' ) || ! CADDY_REMOVE_ALL_DATA ) {
	return;
}

if ( file_exists( WP_PLUGIN_DIR . '/caddy-premium/caddy-premium.php' ) ) {
	return;
}

/**
 * Remove Caddy's options and transients from the current site.
 *
 * @return void
 */
function caddy_uninstall_remove_site_data() {
	global $wpdb;

	$options = array(
		// General
		'cc_affiliate_id',
		'cc_browse_products_url',
		'cc_disable_branding',
		'cc_menu_cart_widget',
		'cc_menu_saves_widget',
		'cc_custom_css',

		// Free shipping meter
		'cc_free_shipping_amount',
		'cc_free_shipping_tax',
		'cc_shipping_country',

		// Recommendations
		'cc_product_recommendation',
		'cc_product_recommendation_type',
		'caddy_recs_cache_version',

		// Save for later
		'cc_enable_sfl_options',
		'cc_sfl_btn_on_product',

		// Admin notices and offers
		'caddy_install_date',
		'caddy_version',
		'caddy_css_offer_requested',
		'cc_offer_started_at',

		// Usage data opt-in and send state for Caddy. pulse_install_id and
		// pulse_first_seen are shared with other plugins, so they stay.
		'pulse_telemetry_optin_caddy',
		'pulse_tel_sent_caddy',
		'pulse_tel_screen_shown_caddy',
		'pulse_tel_activation_redirect_caddy',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time cleanup on uninstall.
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '\\_transient\\_caddy\\_recs\\_%'
		    OR option_name LIKE '\\_transient\\_timeout\\_caddy\\_recs\\_%'
		    OR option_name LIKE '\\_transient\\_caddy\\_rate\\_limit\\_%'
		    OR option_name LIKE '\\_transient\\_timeout\\_caddy\\_rate\\_limit\\_%'"
	);

	delete_post_meta_by_key( '_caddy_recommendations' );
}

if ( is_multisite() ) {
	$caddy_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $caddy_site_ids as $caddy_site_id ) {
		switch_to_blog( $caddy_site_id );
		caddy_uninstall_remove_site_data();
		restore_current_blog();
	}
} else {
	caddy_uninstall_remove_site_data();
}

// User meta is shared by every site in a network, so it is removed once.
delete_metadata( 'user', 0, 'cc_save_for_later_items', '', true );

// Dismissed admin notices are network-wide options named after a hash of the notice ID.
foreach ( array( 'notice-caddy-review', 'notice-caddy-upgrade', 'notice-caddy-optin' ) as $caddy_notice_id ) {
	delete_site_option( 'pand-' . md5( $caddy_notice_id ) );
}
