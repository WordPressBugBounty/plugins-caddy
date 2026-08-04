<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.madebytribe.com
 * @since      1.0.0
 *
 * @package    Caddy
 * @subpackage Caddy/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Template partial; variables are scoped to the including method. The cc_ hooks are Caddy's published extension API, consumed by add-ons.

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'caddy' ) );
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine which tab to display
$caddy_tab = ( ! empty( $_GET['tab'] ) ) ? esc_attr( sanitize_text_field(wp_unslash($_GET['tab'])) ) : 'settings';

$caddy_tabs_name = array(
	'settings' => array(
		'tab_name' => __( 'Settings', 'caddy' ),
		'tab_icon' => 'dashicons cc-icon-settings',
	),
	'styles'   => array(
		'tab_name' => __( 'Styling', 'caddy' ),
		'tab_icon' => 'dashicons cc-icon-styles',
	)
);

/**
 * Filters the caddy tab names.
 *
 * @param array $caddy_tabs_name Caddy tab names.
 *
 * @since 1.3.0
 *
 */
$caddy_tabs = apply_filters( 'caddy_tab_names', $caddy_tabs_name );

$caddy_settings_saved = (bool) get_transient( 'caddy_settings_updated' );
if ( $caddy_settings_saved ) {
	delete_transient( 'caddy_settings_updated' );
}
?>

<div class="wrap">

	<?php do_action( 'caddy_admin_header' ); ?>

	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $caddy_tabs as $key => $value ) {
			$active_tab_class = ( $key == $caddy_tab ) ? ' nav-tab-active' : '';
			?>
			<a class="nav-tab<?php echo esc_attr( $active_tab_class ); ?>" href="?page=caddy&amp;tab=<?php echo esc_attr( $key ); ?>"><i class="<?php echo esc_attr( $value['tab_icon'] ); ?>"></i>&nbsp;<?php echo esc_html( $value['tab_name'] ); ?></a>
		<?php } ?>
	</h2>

	<?php if ( $caddy_settings_saved ) { ?>
		<div class="notice notice-success inline">
			<p>
				<strong><?php echo esc_html( __( 'Settings saved.', 'caddy' ) ); ?></strong> <?php echo esc_html( __( 'If you\'re using any caching plugins please be sure to ', 'caddy' ) ); ?>
				<strong><?php echo esc_html( __( 'clear your cache. ', 'caddy' ) ); ?></strong></p>
		</div>
	<?php } ?>

	<?php do_action( 'caddy_admin_notices' ); ?>

	<?php do_action( 'cc_before_setting_options' ); ?>
	<div class="cc-settings-wrap">
		<?php do_action( 'caddy_admin_tab_screen' ); ?>
		<?php do_action( 'cc_upgrade_to_premium' ); ?>
	</div>
	<?php do_action( 'cc_after_setting_options' ); ?>
</div>