<?php
/**
 * Save for Later functionality for Caddy
 *
 * @package    Caddy
 * @subpackage Caddy/public
 */

/**
 * Save for Later class.
 *
 * Handles all save for later functionality including:
 * - Adding items to saved list
 * - Removing items from saved list
 * - Moving items between cart and saved list
 * - Rendering saved list UI
 *
 * @package    Caddy
 * @subpackage Caddy/public
 */
class Caddy_Save_For_Later {

	/**
	 * The ID of this plugin.
	 *
	 * @var string $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string $version
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Check if save for later is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$cc_enable_sfl_options = get_option( 'cc_enable_sfl_options', 'enabled' );
		return 'enabled' === $cc_enable_sfl_options && is_user_logged_in();
	}

	/**
	 * Get saved items for current user
	 *
	 * @return array Array of product IDs
	 */
	public function get_saved_items() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$current_user_id = get_current_user_id();
		$cc_sfl_items = get_user_meta( $current_user_id, 'cc_save_for_later_items', true );

		if ( ! is_array( $cc_sfl_items ) ) {
			return array();
		}

		return array_unique( $cc_sfl_items );
	}

	/**
	 * Render the saved list HTML
	 */
	public function render_saved_list() {
		include( plugin_dir_path( __FILE__ ) . 'partials/caddy-public-saves.php' );
	}

	/**
	 * Add save for later button to product page
	 */
	public function add_product_button() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$cc_sfl_btn_on_product = get_option( 'cc_sfl_btn_on_product' );
		if ( 'enabled' !== $cc_sfl_btn_on_product ) {
			return;
		}

		global $product;
		if ( ! $product ) {
			return;
		}

		$product_id = $product->get_id();
		$product_type = $product->get_type();
		$saved_items = $this->get_saved_items();

		if ( in_array( $product_id, $saved_items ) ) {
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

	/**
	 * Shortcode for displaying saved items count
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function saved_items_shortcode( $atts ) {
		if ( ! $this->is_enabled() ) {
			return '';
		}

		$default = array(
			'text' => '',
			'icon' => '',
		);
		$atts = shortcode_atts( $default, $atts );

		$saved_items = $this->get_saved_items();
		$count = count( $saved_items );

		ob_start();
		?>
		<a href="javascript:void(0);" class="cc_saved_items_list">
			<?php if ( ! empty( $atts['icon'] ) ) : ?>
				<i class="<?php echo esc_attr( $atts['icon'] ); ?>"></i>
			<?php endif; ?>
			<span class="cc-cart-count"><?php echo esc_html( $count ); ?></span>
			<?php if ( ! empty( $atts['text'] ) ) : ?>
				<span><?php echo esc_html( $atts['text'] ); ?></span>
			<?php endif; ?>
		</a>
		<?php
		return ob_get_clean();
	}
}