<?php
/**
 * Elementor integration loader.
 *
 * Registers the Caddy Cart and Saved Items widgets and a "Caddy" widget
 * category. Only loads when Elementor is active.
 *
 * @package    Caddy
 * @subpackage Caddy/includes/integrations/elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Caddy_Elementor {

	/**
	 * Hook into Elementor if it is available.
	 */
	public static function init() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
	}

	/**
	 * Add a "Caddy" category to the Elementor panel.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public static function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'caddy',
			array(
				'title' => __( 'Caddy', 'caddy' ),
				'icon'  => 'eicon-cart-medium',
			)
		);
	}

	/**
	 * Register the Caddy Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public static function register_widgets( $widgets_manager ) {
		require_once __DIR__ . '/class-caddy-elementor-cart-widget.php';
		require_once __DIR__ . '/class-caddy-elementor-saved-items-widget.php';

		$widgets_manager->register( new Caddy_Elementor_Cart_Widget() );
		$widgets_manager->register( new Caddy_Elementor_Saved_Items_Widget() );
	}
}
