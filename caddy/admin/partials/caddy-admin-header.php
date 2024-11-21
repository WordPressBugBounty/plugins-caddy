<?php

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
}

?>

<div class="cc-header-wrap">
	<img src="<?php echo plugin_dir_url( __DIR__ ) ?>img/caddy-logo.svg" width="110" height="32" class="cc-logo">
	<div class="cc-version"><?php echo CADDY_VERSION; ?></div>
	<?php do_action( 'caddy_header_links' ); ?>
</div>