<?php

/**
 * The file that used to register and load the cart widget
 *
 * @since      1.0.0
 * @package    Caddy
 * @subpackage Caddy/includes
 */
if ( ! class_exists( 'caddy_cart_widget' ) ) {
	class caddy_cart_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'caddy_cart_widget',
			__( 'Caddy Cart', 'caddy' ),
			array( 'description' => __( 'Caddy cart widget', 'caddy' ), )
		);
	}

	/**
	 * Creating front-end widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$cart_widget_title = isset( $instance['cart_widget_title'] ) ? apply_filters( 'widget_title', $instance['cart_widget_title'] ) : '';
		$cc_cart_icon = isset( $instance['cc_cart_icon'] ) ? $instance['cc_cart_icon'] : '';
		$cart_text = isset( $instance['cart_text'] ) ? $instance['cart_text'] : '';

		// before and after widget arguments are defined by themes
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper args are safe HTML from theme
		echo $args['before_widget'];
		if ( ! empty( $cart_widget_title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper args are safe HTML from theme
			echo $args['before_title'] . esc_html( $cart_widget_title ) . $args['after_title'];
		}

		$cart_count    = 0;
		$cc_cart_class = '';
		if ( ! is_admin() ) {
			$cart_count    = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
			$cc_cart_class = ( $cart_count == 0 ) ? 'cc_cart_count cc_cart_zero' : 'cc_cart_count';
		}
		$allowed_svg = array(
			'svg'  => array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'style' => true, 'class' => true, 'aria-hidden' => true ),
			'path' => array( 'd' => true, 'stroke-width' => true, 'fill' => true, 'stroke' => true ),
			'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
			'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
			'line' => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true ),
			'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
			'polygon' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
			'g' => array( 'fill' => true, 'stroke' => true, 'transform' => true ),
		);
		$cart_icon_class = wp_kses( apply_filters( 'caddy_cart_bubble_icon', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-0.5 -0.5 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M5.75 1.9166666666666667 2.875 5.75v13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 1.9166666666666667h13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 -1.9166666666666667V5.75l-2.875 -3.8333333333333335z" stroke-width="1.2"></path><path d="m2.875 5.75 17.25 0" stroke-width="1.2"></path><path d="M15.333333333333334 9.583333333333334a3.8333333333333335 3.8333333333333335 0 0 1 -7.666666666666667 0" stroke-width="1.2"></path></svg>' ), $allowed_svg );
		$cart_items_link = sprintf(
			'<a href="%1$s" class="cc_cart_items_list" aria-label="%2$s">%3$s %4$s <span class="%5$s">%6$s</span></a>',
			'javascript:void(0);',
			esc_html__( 'Cart Items', 'caddy' ),
			( 'on' == $cc_cart_icon ) ? '' : $cart_icon_class,
			esc_html( $cart_text ),
			$cc_cart_class,
			esc_html( $cart_count )
		);
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in sprintf above
		echo $cart_items_link;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper args are safe HTML from theme
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form
	 *
	 * @param array $instance
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		$cart_widget_title = isset( $instance['cart_widget_title'] ) ? $instance['cart_widget_title'] : __( 'New title', 'caddy' );
		$cart_text         = isset( $instance['cart_text'] ) ? $instance['cart_text'] : __( 'Cart', 'caddy' );
		$cc_cart_icon      = ( isset( $instance['cc_cart_icon'] ) && 'on' == $instance['cc_cart_icon'] ) ? ' checked="checked"' : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cart_widget_title' ) ); ?>"><?php esc_html_e( 'Widget Title:', 'caddy' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'cart_widget_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cart_widget_title' ) ); ?>"
			       type="text" value="<?php echo esc_attr( $cart_widget_title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cart_text' ) ); ?>"><?php esc_html_e( 'Cart Text:', 'caddy' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'cart_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cart_text' ) ); ?>" type="text"
			       value="<?php echo esc_attr( $cart_text ); ?>" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php echo esc_attr( $cc_cart_icon ); ?> id="<?php echo esc_attr( $this->get_field_id( 'cc_cart_icon' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'cc_cart_icon' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'cc_cart_icon' ) ); ?>"><?php esc_html_e( 'Disable cart icon', 'caddy' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Updating widget replacing old instances with new
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                      = array();
		$instance['cart_widget_title'] = ( ! empty( $new_instance['cart_widget_title'] ) ) ? wp_strip_all_tags( $new_instance['cart_widget_title'] ) : '';
		$instance['cart_text']         = ( ! empty( $new_instance['cart_text'] ) ) ? wp_strip_all_tags( $new_instance['cart_text'] ) : '';
		$instance['cc_cart_icon']      = isset( $new_instance['cc_cart_icon'] ) ? $new_instance['cc_cart_icon'] : '';

		return $instance;
	}

	}
}

/**
 * Register and load the cart widget
 */
function caddy_cart_widget() {
	register_widget( 'caddy_cart_widget' );
}

// Add action to register and load the cart widget
add_action( 'widgets_init', 'caddy_cart_widget' );
