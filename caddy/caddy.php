<?php
/**
 * Plugin Name:       Caddy – WooCommerce Side Cart & Free Shipping Bar
 * Plugin URI:        https://usecaddy.com
 * Description:       A high performance, conversion-boosting side cart for your WooCommerce store that improves the shopping experience & helps grow your sales.
 * Version:           3.1.1
 * Author:            Caddy
 * Author URI:        https://usecaddy.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       caddy
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires PHP:      7.4
 *
 * WC requires at least: 7.0
 * WC tested up to: 10.9.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/*
 * Define all constants for the plugin
 */
if ( ! defined( 'CADDY_VERSION' ) ) {
    define( 'CADDY_VERSION', '3.1.1' );
}
if ( ! defined( 'CADDY_PLUGIN_FILE' ) ) {
    define( 'CADDY_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'CADDY_DIR_URL' ) ) {
    define( 'CADDY_DIR_URL', untrailingslashit( plugins_url( '/', CADDY_PLUGIN_FILE ) ) );
}

// If Caddy Premium v3+ is active, bail out — Premium replaces all free functionality.
// For older Premium versions (< 3.0.0) we must NOT bail, because they depend on
// classes from the free plugin (e.g. Caddy_Admin). We show an upgrade notice instead.
if ( defined( 'CADDY_PREMIUM_VERSION' ) || defined( 'CADDY_PREMIUM_PLUGIN_FILE' ) ) {
    $caddy_premium_ver = defined( 'CADDY_PREMIUM_VERSION' ) ? CADDY_PREMIUM_VERSION : '0';
    if ( version_compare( $caddy_premium_ver, '3.0.0', '>=' ) ) {
        return;
    }
    // Old Premium detected — continue loading so it doesn't crash, and warn the admin.
    add_action( 'admin_notices', function () {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__( 'Caddy Pro update required:', 'caddy' ) . '</strong> ';
        echo esc_html__( 'Your version of Caddy Pro is not compatible with Caddy 3.0. Please update Caddy Pro to version 3.0 or later to avoid errors.', 'caddy' );
        echo '</p></div>';
    } );
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
function caddy_activate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-caddy-activator.php';
    Caddy_Activator::activate();

    // Ask for telemetry consent on the post-activation screen instead of nagging
    // with an admin notice. Sites that already answered are not asked again.
    if ( class_exists( 'Pulse_Telemetry_Client' ) ) {
        Pulse_Telemetry_Client::flag_activation( 'caddy' );
    }
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-caddy-deactivator.php
 */
function caddy_deactivate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-caddy-deactivator.php';
    Caddy_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'caddy_activate' );
register_deactivation_hook( __FILE__, 'caddy_deactivate' );

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
 * Elementor integration (registers Caddy widgets when Elementor is active)
 */
require plugin_dir_path( __FILE__ ) . 'includes/integrations/elementor/class-caddy-elementor.php';
add_action( 'init', array( 'Caddy_Elementor', 'init' ) );

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
 * Pulse client: deactivation survey + optional (opt-in) telemetry,
 * reporting to the Pulse Hub. Replaces Caddy's legacy email survey.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/pulse-client/class-pulse-client.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pulse-client/class-pulse-survey-client.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pulse-client/class-pulse-telemetry-client.php';

if ( ! defined( 'CADDY_PULSE_HUB_URL' ) ) {
    // The Caddy Hub lives on the Caddy store (validates Caddy licenses locally).
    // Override via wp-config or the `pulse_hub_url` filter.
    define( 'CADDY_PULSE_HUB_URL', 'https://usecaddy.com' );
}

add_action( 'plugins_loaded', 'caddy_boot_pulse_client' );
function caddy_boot_pulse_client() {
    // Registered on plugins_loaded (not admin_init) so the telemetry client's own
    // admin_init hook is added before admin_init fires. Admin-only work, so bail on
    // front-end and cron requests. is_admin() is true for admin-ajax, so the survey
    // and opt-in AJAX handlers still register.
    if ( ! is_admin() ) {
        return;
    }

    // Caddy Pro reports under the same 'caddy' slug and carries the license key +
    // pro tier, so it is the authority when installed. Bail here to avoid a second
    // survey modal, telemetry notice, and duplicate AJAX handlers when both are active.
    if ( defined( 'CADDY_PREMIUM_VERSION' ) ) {
        return;
    }

    // Caddy Free has no license, so license_key stays empty and the hub treats it
    // as anonymous + hardened. Caddy Pro passes its EDD license key here instead.
    $common = array(
        'brand'           => 'caddy',
        'plugin_slug'     => 'caddy',
        'hub_url'         => CADDY_PULSE_HUB_URL,
        'license_key'     => '',
        'tier'            => 'free',
        'plugin_version'  => CADDY_VERSION,
        'privacy_url'     => 'https://usecaddy.com/privacy-policy',
        'branding'        => array(
            'name' => 'Caddy',
            'logo' => plugins_url( 'admin/img/caddy-logo.svg', CADDY_PLUGIN_FILE ),
        ),
    );

    new Pulse_Survey_Client(
        array_merge(
            $common,
            array(
                'plugin_basename' => plugin_basename( CADDY_PLUGIN_FILE ),
            )
        )
    );

    new Pulse_Telemetry_Client(
        array_merge(
            $common,
            array(
                'custom_data'  => 'caddy_pulse_feature_snapshot',
                'parent_slug'  => 'caddy',
                'settings_url' => admin_url( 'admin.php?page=caddy' ),
            )
        )
    );

    // Native toggle style + Privacy settings row. Registered here (not at file
    // scope) so they never double up when Pro supersedes Free above.
    add_filter( 'pulse_telemetry_toggle_control_caddy', 'caddy_pulse_toggle_control', 10, 3 );
    add_action( 'caddy_general_settings_end', 'caddy_pulse_render_privacy_setting' );
}

/**
 * Render the Pulse telemetry opt-out using Caddy's native toggle style, so it
 * matches the other settings switches. Keeps the `pulse-tel-toggle` input class
 * so the client's AJAX handler still binds.
 *
 * @param string $html    Default control markup.
 * @param bool   $enabled Current opt-in state.
 * @param string $id      Input id.
 * @return string
 */
function caddy_pulse_toggle_control( $html, $enabled, $id ) {
    ob_start();
    ?>
    <div class="cc-toggle cc-toggle--size-small">
        <input type="checkbox" id="<?php echo esc_attr( $id ); ?>" class="pulse-tel-toggle" value="enabled" <?php checked( $enabled ); ?> />
        <label for="<?php echo esc_attr( $id ); ?>">
            <div class="cc-toggle__switch" data-checked="On" data-unchecked="Off"></div>
        </label>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render the Pulse "Privacy" telemetry toggle at the end of the General
 * settings tab. Hooks the plugin's own settings-end action so the shared
 * settings partial does not need editing.
 */
function caddy_pulse_render_privacy_setting() {
    echo '<h2><i class="dashicons dashicons-lightbulb section-icons"></i>&nbsp;' . esc_html__( 'Help Improve Caddy', 'caddy' ) . '</h2>';
    do_action( 'pulse_telemetry_settings_caddy' );
}

/**
 * Feature-usage snapshot for telemetry (only sent when the user opts in).
 *
 * @return array
 */
function caddy_pulse_feature_snapshot() {
    // These options store the strings 'enabled'/'disabled' (or a numeric
    // amount for the shipping meter), so compare explicitly. A bool cast
    // would read 'disabled' as true.
    return array(
        'free_shipping_meter' => (float) get_option( 'cc_free_shipping_amount', 0 ) > 0,
        'save_for_later'      => 'enabled' === get_option( 'cc_enable_sfl_options', 'disabled' ),
        'recommendations'     => 'enabled' === get_option( 'cc_product_recommendation', 'disabled' ),
    );
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
function caddy_run() {

    $plugin = new Caddy();
    $plugin->run();

}

caddy_run();

/**
 * Determine whether Caddy Pro is installed and licensed.
 *
 * The license option is written by Caddy Pro and remains in the database after
 * Pro is deactivated or deleted, so it cannot be trusted on its own. Pro must
 * also be loaded for its settings and screens to exist.
 *
 * @return bool True when Pro is active and its license is valid.
 */
function caddy_pro_is_active() {

    return class_exists( 'Caddy_Premium' ) && 'valid' === get_option( 'caddy_premium_edd_license_status' );
}

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
