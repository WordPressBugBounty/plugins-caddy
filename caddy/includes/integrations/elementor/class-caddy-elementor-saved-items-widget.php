<?php
/**
 * Caddy Saved Items — Elementor widget.
 *
 * Renders the saved-items (heart) icon as an inline trigger. The drawer's
 * Saves tab is opened by the global `.cc_saved_items_list` click handler in
 * caddy.js. Mirrors the classic saved-items menu widget: only shown to
 * logged-in users when Save for Later is enabled.
 *
 * @package    Caddy
 * @subpackage Caddy/includes/integrations/elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Caddy_Elementor_Saved_Items_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'caddy-saved-items';
	}

	public function get_title() {
		return __( 'Caddy Saved Items', 'caddy' );
	}

	public function get_icon() {
		return 'eicon-heart';
	}

	public function get_categories() {
		return array( 'caddy' );
	}

	public function get_keywords() {
		return array( 'caddy', 'save for later', 'saved', 'wishlist', 'cart' );
	}

	public function get_style_depends() {
		if ( class_exists( 'Caddy_Block' ) ) {
			Caddy_Block::ensure_frontend_assets();
		}
		return array( 'caddy-block-style', 'caddy-icons-style' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Saved Items', 'caddy' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'label',
			array(
				'label'       => __( 'Label', 'caddy' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Saves', 'caddy' ),
				'placeholder' => __( 'e.g. Saves', 'caddy' ),
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
					'{{WRAPPER}} .caddy-elementor-saves' => 'text-align: {{VALUE}};',
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
					'{{WRAPPER}} .cc_saved_items_list i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .cc_saved_items_list svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon color', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_saved_items_list i'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .cc_saved_items_list svg' => 'stroke: {{VALUE}}; color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .cc_saved_items_list',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Label color', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_saved_items_list' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'label_hover_color',
			array(
				'label'     => __( 'Label color (hover)', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc_saved_items_list:hover' => 'color: {{VALUE}};',
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
					'{{WRAPPER}} .cc-saved-count' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'count_bg_color',
			array(
				'label'     => __( 'Count background', 'caddy' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cc-saved-count' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .cc-saved-count' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .cc_saved_items_list' => 'display: inline-flex; align-items: center; gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings  = $this->get_settings_for_display();
		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

		// Save for Later must be enabled and the visitor logged in (matches the
		// classic saved-items menu widget). In the editor, always preview.
		$sfl_enabled = ( 'enabled' === get_option( 'cc_enable_sfl_options', 'enabled' ) );
		if ( ! $is_editor && ( ! is_user_logged_in() || ! $sfl_enabled ) ) {
			return;
		}

		$show_icon  = ( 'yes' === ( $settings['show_icon'] ?? 'yes' ) );
		$show_count = ( 'yes' === ( $settings['show_count'] ?? 'yes' ) );
		$label      = isset( $settings['label'] ) ? $settings['label'] : '';
		$icon_html  = $show_icon ? '<i class="ccicon-heart-empty"></i>' : '';

		// Saved count badge — class matches updateSavedItemsWidgetCount() so it
		// stays live as items are saved/removed.
		$count_html = '';
		if ( $show_count && is_user_logged_in() ) {
			$sfl_items   = get_user_meta( get_current_user_id(), 'cc_save_for_later_items', true );
			$count       = is_array( $sfl_items ) ? count( array_unique( $sfl_items ) ) : 0;
			$count_class = ( 0 === $count ) ? 'cc-saved-count cc-saved-zero' : 'cc-saved-count';
			$count_html  = sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $count_class ), esc_html( $count ) );
		}

		printf(
			'<div class="caddy-elementor-saves"><a href="javascript:void(0);" class="cc_saved_items_list" aria-label="%1$s">%2$s%3$s%4$s</a></div>',
			esc_attr__( 'Saved Items', 'caddy' ),
			$icon_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup
			$label ? ' ' . esc_html( $label ) . ' ' : ' ',
			$count_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* above
		);
	}
}
