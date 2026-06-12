<?php
/**
 * Caddy Cart — Elementor widget.
 *
 * Renders the cart icon + count as an inline trigger. The drawer is opened
 * by the global `.cc_cart_items_list` click handler in caddy.js, and the
 * count badge is kept live by updateCartWidgetCount(); this widget only
 * outputs the trigger markup, mirroring the classic menu cart widget.
 *
 * @package    Caddy
 * @subpackage Caddy/includes/integrations/elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Caddy_Elementor_Cart_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'caddy-cart';
	}

	public function get_title() {
		return __( 'Caddy Cart', 'caddy' );
	}

	public function get_icon() {
		return 'eicon-cart-medium';
	}

	public function get_categories() {
		return array( 'caddy' );
	}

	public function get_keywords() {
		return array( 'caddy', 'cart', 'side cart', 'woocommerce', 'mini cart' );
	}

	public function get_style_depends() {
		if ( class_exists( 'Caddy_Block' ) ) {
			Caddy_Block::ensure_frontend_assets();
		}
		return array( 'caddy-block-style', 'caddy-icons-style' );
	}

	/**
	 * Allowed SVG tags for the cart bubble icon.
	 */
	private function allowed_svg() {
		return array(
			'svg'  => array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'style' => true, 'class' => true, 'aria-hidden' => true ),
			'path' => array( 'd' => true, 'stroke-width' => true, 'fill' => true, 'stroke' => true ),
			'g'    => array( 'fill' => true, 'stroke' => true, 'transform' => true ),
		);
	}

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Cart', 'caddy' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'label',
			array(
				'label'       => __( 'Label', 'caddy' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'e.g. Cart', 'caddy' ),
			)
		);

		$this->add_control(
			'show_icon',
			array(
				'label'        => __( 'Show icon', 'caddy' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'        => __( 'Show count', 'caddy' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => __( 'Left', 'caddy' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'caddy' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'caddy' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .caddy-elementor-cart' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Style', 'caddy' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => __( 'Icon size', 'caddy' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 10, 'max' => 80 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .cc_cart_items_list svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .cc_cart_items_list i'   => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon color', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_cart_items_list svg' => 'stroke: {{VALUE}}; color: {{VALUE}};',
					'{{WRAPPER}} .cc_cart_items_list i'   => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .cc_cart_items_list',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Label color', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_cart_items_list' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'label_hover_color',
			array(
				'label'     => __( 'Label color (hover)', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_cart_items_list:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'count_heading',
			array(
				'label'     => __( 'Count badge', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'count_color',
			array(
				'label'     => __( 'Count text color', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_cart_count' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'count_bg_color',
			array(
				'label'     => __( 'Count background', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_cart_count' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'count_border_radius',
			array(
				'label'      => __( 'Count border radius', 'caddy' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cc_cart_count' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_spacing',
			array(
				'label'      => __( 'Spacing', 'caddy' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .cc_cart_items_list' => 'display: inline-flex; align-items: center; gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$show_icon  = ( 'yes' === ( $settings['show_icon'] ?? 'yes' ) );
		$show_count = ( 'yes' === ( $settings['show_count'] ?? 'yes' ) );
		$label      = isset( $settings['label'] ) ? $settings['label'] : '';

		$cart_count = ( ! is_admin() && function_exists( 'WC' ) && is_object( WC()->cart ) )
			? WC()->cart->get_cart_contents_count()
			: 0;
		$count_class = ( 0 === (int) $cart_count ) ? 'cc_cart_count cc_cart_zero' : 'cc_cart_count';

		$icon_html = '';
		if ( $show_icon ) {
			$default_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-0.5 -0.5 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M5.75 1.9166666666666667 2.875 5.75v13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 1.9166666666666667h13.416666666666668a1.9166666666666667 1.9166666666666667 0 0 0 1.9166666666666667 -1.9166666666666667V5.75l-2.875 -3.8333333333333335z" stroke-width="1.2"></path><path d="m2.875 5.75 17.25 0" stroke-width="1.2"></path><path d="M15.333333333333334 9.583333333333334a3.8333333333333335 3.8333333333333335 0 0 1 -7.666666666666667 0" stroke-width="1.2"></path></svg>';
			$icon_html    = wp_kses( apply_filters( 'caddy_cart_bubble_icon', $default_icon ), $this->allowed_svg() );
		}

		$count_html = $show_count ? sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $count_class ), esc_html( $cart_count ) ) : '';

		printf(
			'<div class="caddy-elementor-cart"><a href="javascript:void(0);" class="cc_cart_items_list" aria-label="%1$s">%2$s%3$s%4$s</a></div>',
			esc_attr__( 'Cart Items', 'caddy' ),
			$icon_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses'd SVG above
			$label ? ' ' . esc_html( $label ) . ' ' : ' ',
			$count_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* above
		);
	}
}
