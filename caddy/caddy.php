<?php
/**
 * Plugin Name:       Caddy - Smart Side Cart for WooCommerce
 * Plugin URI:        https://usecaddy.com
 * Description:       A high performance, conversion-boosting side cart for your WooCommerce store that improves the shopping experience & helps grow your sales.
 * Version:           3.0.0
 * Author:            Tribe Interactive
 * Author URI:        https://usecaddy.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       caddy
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires PHP:      7.4
 *
 * WC requires at least: 7.0
 * WC tested up to: 10.2.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/*
 * Define all constants for the plugin
 */
if ( ! defined( 'CADDY_VERSION' ) ) {
    define( 'CADDY_VERSION', '3.0.0' );
}
if ( ! defined( 'CADDY_PLUGIN_FILE' ) ) {
    define( 'CADDY_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'CADDY_DIR_URL' ) ) {
    define( 'CADDY_DIR_URL', untrailingslashit( plugins_url( '/', CADDY_PLUGIN_FILE ) ) );
}

// If Caddy Premium is active in this request, bail out silently.
// Premium replaces all free functionality. This prevents fatal errors
// from duplicate block/store/script module registration.
if ( defined( 'CADDY_PREMIUM_VERSION' ) || defined( 'CADDY_PREMIUM_PLUGIN_FILE' ) ) {
    return;
}

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

$caddy_wc_missing = false;
if ( is_multisite() ) {
    if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
        $caddy_wc_missing = ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
    } else {
        $caddy_wc_missing = ! is_plugin_active( 'woocommerce/woocommerce.php' );
    }
} else {
    $caddy_wc_missing = ! is_plugin_active( 'woocommerce/woocommerce.php' );
}

if ( $caddy_wc_missing ) {
    add_action( 'admin_notices', 'caddy_wc_requirements_error' );
    return;
}
unset( $caddy_wc_missing );

/**
 * If WC requirements are not match
 */
function caddy_wc_requirements_error() {
    ?>
    <div class="error notice"><p>
            <strong><?php esc_html_e( 'The WooCommerce plugin needs to be installed and activated in order for Caddy to work properly.', 'caddy' ); ?></strong> <?php esc_html_e( 'Please activate WooCommerce to enable Caddy.', 'caddy' ); ?>
        </p></div>
    <?php
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-caddy-activator.php
 */
function activate_caddy() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-caddy-activator.php';
    Caddy_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-caddy-deactivator.php
 */
function deactivate_caddy() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-caddy-deactivator.php';
    Caddy_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_caddy' );
register_deactivation_hook( __FILE__, 'deactivate_caddy' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-caddy.php';

/**
 * The plugin class that is used to register and load the cart widget.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-caddy-cart-widget.php';

/**
 * The plugin class that is used to register and load the saved items widget.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-caddy-saved-items-widget.php';

/**
 * Load notices
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-caddy-notices.php';

/**
 * Load composer
 */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_caddy() {

    $plugin = new Caddy();
    $plugin->run();

}

run_caddy();

/**
 * Add plugin settings link.
 *
 * @param $caddy_links
 *
 * @return mixed
 */
function caddy_add_settings_link( $caddy_links ) {

    $caddy_links = array_merge( array( '<a href="' . esc_url( admin_url( '/admin.php?page=caddy&amp;tab=settings' ) ) . '">' . esc_html__( 'Settings', 'caddy' ) . '</a>' ), $caddy_links );

    return $caddy_links;
}

$caddy_plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$caddy_plugin", 'caddy_add_settings_link' );

/**
 * Declaring WooCommerce HPOS support
 *
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// Initialize the admin notice dismissal library
add_action('admin_init', array('PAnD', 'init'));
