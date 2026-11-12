<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @see        https://www.madebytribe.com
 * @since      1.0.0
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     Tribe Interactive <success@madebytribe.co>
 */
class Caddy_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string the ID of this plugin
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string the current version of this plugin
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name the name of this plugin
	 * @param string $version     the version of this plugin
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		
		// Add hook for processing the settings form
		add_action('admin_init', array($this, 'process_settings_form'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine which assets to enqueue
		if ( isset( $_GET['page'] ) ) {
			// Get the 'page' parameter from the URL
			$raw_page_name = filter_input(INPUT_GET, 'page', FILTER_DEFAULT);
			
			// Sanitize the 'page' parameter
			$page_name = sanitize_text_field($raw_page_name);

			if ( 'caddy' == $page_name || 'caddy-addons' === $page_name || 'caddy-resources' === $page_name ) {
				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/caddy-admin.css', array(), $this->version, 'all' );
				wp_enqueue_style( 'kt-admin-notice', plugin_dir_url( __FILE__ ) . 'css/caddy-admin-notices.css', array(), $this->version, 'all' );
			}
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine which assets to enqueue
		if ( isset( $_GET['page'] ) ) {
			// Get the 'page' parameter from the URL
			$raw_page_name = filter_input(INPUT_GET, 'page', FILTER_DEFAULT);
			
			// Sanitize the 'page' parameter
			$page_name = sanitize_text_field($raw_page_name);

			if ( 'caddy' == $page_name ) {
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/caddy-admin.js', [ 'jquery' ], $this->version, true );
				// make the ajaxurl var available to the above script
				$params = array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'caddy' ),
					'i18n'    => array(
						'copied' => __( 'Copied!', 'caddy' ),
					),
				);
				wp_localize_script( $this->plugin_name, 'caddyAjaxObject', $params );
			}
		}
	}

	/**
	 * Register a caddy menu page.
	 */
	public function cc_register_menu_page() {
		add_menu_page(
			__( 'Caddy', 'caddy' ),
			__( 'Caddy', 'caddy' ),
			'manage_options',
			'caddy',
			[ $this, 'caddy_menu_page_callback' ],
			'dashicons-smiley',
			65
		);
		add_submenu_page(
			'caddy',
			__( 'Settings', 'caddy' ),
			__( 'Settings', 'caddy' ),
			'manage_options',
			'caddy'
		);
		add_submenu_page(
			'caddy',
			__( 'Add-ons', 'caddy' ),
			__( 'Add-ons', 'caddy' ),
			'manage_options',
			'caddy-addons',
			[ $this, 'caddy_addons_page_callback' ]
		);
		add_submenu_page(
			'caddy',
			__( 'Resources', 'caddy' ),
			__( 'Resources', 'caddy' ),
			'manage_options',
			'caddy-resources',
			[ $this, 'caddy_resources_page_callback' ]
		);

	}

	/**
	 * Display a caddy menu page.
	 */
	public function caddy_menu_page_callback() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/caddy-admin-display.php';
	}

	/**
	 * Display a caddy add-ons submenu page.
	 */
	public function caddy_addons_page_callback() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/caddy-admin-addons-page.php';
	}

	/**
	 * Display a caddy resources submenu page.
	 */
	public function caddy_resources_page_callback() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/caddy-admin-resources-page.php';
	}

	/**
	 * Check if Caddy Pro is active and licensed.
	 */
	private function is_pro_active() {
		return caddy_pro_is_active();
	}


	/**
	 * Include tab screen files
	 */
	public function cc_include_tab_screen_files() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine which template to include
		$caddy_tab = ( ! empty( $_GET['tab'] ) ) ? esc_attr( sanitize_text_field(wp_unslash($_GET['tab'])) ) : 'settings';

		if ( 'settings' === $caddy_tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/caddy-admin-settings-page.php';
		} elseif ( 'styles' === $caddy_tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/caddy-admin-styles-page.php';
		}
	}

	/**
	 * Display addons tab html
	 */
	public function cc_addons_html_display() {
		$add_on_html_flag = false;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine page context for display logic
		if ( isset( $_GET['page'] ) && 'caddy-addons' === sanitize_text_field(wp_unslash($_GET['page'])) ) {
			$add_on_html_flag = true;

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter to determine page context for display logic
			if ( isset( $_GET['tab'] ) && 'addons' !== sanitize_text_field(wp_unslash($_GET['tab'])) ) {
				$add_on_html_flag = false;
			}
		}

		if ( $add_on_html_flag ) {
			$caddy_premium_license_status = get_option( 'caddy_premium_edd_license_status' );
			$caddy_ann_license_status     = get_transient( 'caddy_ann_license_status' );
			$caddy_ga_license_status      = get_transient( 'ga_tracking_license_status' );

			$caddy_addons_array = [
				'caddy-premium'      => [
					'icon'        => 'dashicons-awards',
					'title'       => __( 'Caddy Pro', 'caddy' ),
					'description' => __( 'Pro unlocks powerful customization features for Caddy including an in-cart "offers." tab, exclusion rules for recommendations and free shipping meter, color style management, positioning and more.', 'caddy' ),
					'btn_title'   => __( 'Get Pro', 'caddy' ),
					'btn_link'    => 'https://www.usecaddy.com/?utm_source=caddy-addons&utm_medium=plugin&utm_campaign=addon-links',
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter name.
					'activated'   => in_array( 'caddy-premium/caddy-premium.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ? 'true' : 'false',
					'license'     => ( 'valid' === $caddy_premium_license_status ) ? 'activated' : 'not_activated',
				],
				'caddy-klaviyo' => [
					'icon'        => 'dashicons-megaphone',
					'title'       => __( 'Klaviyo Integration', 'caddy' ),
					'description' => __( 'Capture emails directly in the cart and track cart actions with Klaviyo.', 'caddy' ),
					'btn_title'   => __( 'Get Add-on', 'caddy' ),
					'btn_link'    => 'https://usecaddy.com/products/klaviyo-add-on/?utm_source=caddy-addons&utm_medium=plugin&utm_campaign=addon-links',
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter name.
					'activated'   => in_array( 'caddy-klaviyo/caddy-klaviyo.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ? 'true' : 'false',
					'license'     => ( 'valid' === $caddy_ann_license_status ) ? 'activated' : 'not_activated',
				]
			];

			if ( ! empty( $caddy_addons_array ) ) {
				?>
				<div class="cc-addons-wrap">
					<?php foreach ( $caddy_addons_array as $key => $addon ) { ?>
						<div class="cc-addon">
							<span class="dashicons <?php echo esc_html( $addon['icon'] ); ?>"></span>
							<h4 class="addon-title"><?php echo esc_html( $addon['title'] ); ?></h4>
							<p class="addon-description"><?php echo esc_html( $addon['description'] ); ?></p>
							<?php if ( 'false' == $addon['activated'] ) { ?>
								<a class="button addon-button" href="<?php echo esc_url( $addon['btn_link'] ); ?>" target="_blank"><?php echo esc_html( $addon['btn_title'] ); ?></a>
							<?php } else { ?>
								<?php if ( 'activated' === $addon['license'] ) { ?>
									<span class="button button-disabled active-addon-btn"><?php esc_html_e( 'Activated', 'caddy' ); ?></span>
								<?php } else { ?>
									<span class="button button-disabled installed-addon-btn"><?php esc_html_e( 'Installed', 'caddy' ); ?></span>
								<?php } ?>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Caddy header links html
	 */
	public function caddy_header_links_html() {
		?>
		<div class="cc-header-links">
			<a href="?page=caddy"><?php echo esc_html( __( 'Settings', 'caddy' ) ); ?></a>
			| <a href="https://usecaddy.com/docs/?utm_source=caddy-plugin&amp;utm_medium=plugin&amp;utm_campaign=plugin-links"><?php echo esc_html( __( 'Documentation', 'caddy' ) ); ?></a>
			| <a href="https://wordpress.org/support/plugin/caddy/reviews/#new-post" target="_blank"><?php echo esc_html( __( 'Leave a Review', 'caddy' ) ); ?></a>
			| <a href="?page=caddy-addons"><?php echo esc_html( __( 'Get Add-ons', 'caddy' ) ); ?></a>
			| <a href="?page=caddy-resources"><?php echo esc_html( __( 'Resources', 'caddy' ) ); ?></a>
			<?php
			if ( caddy_pro_is_active() ) {
				?>
				| <a href="?page=caddy-licenses"><?php echo esc_html( __( 'Licenses', 'caddy' ) ); ?></a>
				<?php
			} else {
				?>
				| <a href="https://www.usecaddy.com" target="_blank"><?php echo esc_html( __( 'Upgrade to Pro', 'caddy' ) ); ?></a>
				<?php
			} ?>
		</div>
		<?php
	}


	/**
	 * Include header
	 */
	public function caddy_load_admin_header() {
		include plugin_dir_path( __FILE__ ) . 'partials/caddy-admin-header.php';
	}

	/**
	 * Restrict dismissal of Caddy's admin notices to administrators.
	 *
	 * Runs before the bundled notice-dismissal handler, which checks the nonce
	 * but not the user's capability. Other plugins share that handler, so only
	 * Caddy's own notice IDs are checked.
	 */
	public function guard_notice_dismissal() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The dismissal handler verifies the nonce; this only reads the notice ID.
		$option_name = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : '';

		if ( 0 === strpos( $option_name, 'notice-caddy-' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
	}

	/**
	 * Add Caddy Recommendations field to WooCommerce product data
	 */
	public function add_caddy_recommendations_field() {
		global $woocommerce, $post;
		
		?>
		<div class="options_group">
			<p class="form-field">
				<label for="caddy_recommendations"><?php esc_html_e('Caddy Recommendations', 'caddy'); ?></label>
				<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="caddy_recommendations" name="caddy_recommendations[]" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'caddy'); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval($post->ID); ?>">
					<?php
					$product_ids = get_post_meta($post->ID, '_caddy_recommendations', true);
					
					if (!empty($product_ids)) {
						foreach ($product_ids as $product_id) {
							$product = wc_get_product($product_id);
							if (is_object($product)) {
								echo '<option value="' . esc_attr($product_id) . '"' . selected(true, true, false) . '>' . wp_kses_post($product->get_formatted_name()) . '</option>';
							}
						}
					}
					?>
				</select>
				<?php 
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip already escapes output
			echo wc_help_tip(__('These products will be shown as recommendations in the Caddy cart for this product.', 'caddy')); 
			?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save Caddy Recommendations data
	 */
	public function save_caddy_recommendations_field($post_id) {
		// Check if nonce is valid
		if ( ! isset($_POST['woocommerce_meta_nonce']) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data' ) ) {
			return;
		}
		
		// Check user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		
		$recommendations = isset($_POST['caddy_recommendations']) ? array_map('intval', (array) wp_unslash($_POST['caddy_recommendations'])) : array();
		if ( update_post_meta($post_id, '_caddy_recommendations', $recommendations) ) {
			Caddy_Interactivity::clear_recommendations_cache($post_id);
		}
	}

	/**
	 * Process settings form submissions
	 * 
	 * @since 1.0.0
	 */
	public function process_settings_form() {
		// Only process if this is a form submission
		if (!isset($_POST['cc_submit_hidden']) || $_POST['cc_submit_hidden'] != 'Y') {
			return;
		}

		// Require manage_options capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Determine which form was submitted
		if (isset($_POST['caddy_general_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_general_settings_nonce'])), 'caddy-general-settings-save')) {
			// General settings form processing
			$this->process_general_settings();
		} elseif (isset($_POST['caddy_shipping_meter_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_shipping_meter_settings_nonce'])), 'caddy-shipping-meter-settings-save')) {
			// Shipping meter settings form processing
			$this->process_shipping_meter_settings();
		} elseif (isset($_POST['caddy_recommendations_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_recommendations_settings_nonce'])), 'caddy-recommendations-settings-save')) {
			// Recommendations settings form processing
			$this->process_recommendations_settings();
		} elseif (isset($_POST['caddy_display_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_display_settings_nonce'])), 'caddy-display-settings-save')) {
			// Display settings form processing
			$this->process_display_settings();
		} elseif (isset($_POST['caddy_offers_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_offers_settings_nonce'])), 'caddy-offers-settings-save')) {
			// Offers settings form processing
			$this->process_offers_settings();
		} elseif (isset($_POST['caddy_sfl_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_sfl_settings_nonce'])), 'caddy-sfl-settings-save')) {
			// Save for Later settings form processing
			$this->process_sfl_settings();
		} elseif (isset($_POST['caddy_welcome_message_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_welcome_message_settings_nonce'])), 'caddy-welcome-message-settings-save')) {
			// Welcome message settings form processing
			$this->process_welcome_message_settings();
		} elseif (isset($_POST['caddy_announcement_bar_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_announcement_bar_settings_nonce'])), 'caddy-announcement-bar-settings-save')) {
			// Announcement bar settings form processing
			$this->process_announcement_bar_settings();
		} elseif (isset($_POST['caddy_rewards_meter_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_rewards_meter_settings_nonce'])), 'caddy-rewards-meter-settings-save')) {
			// Rewards meter settings form processing
			$this->process_rewards_meter_settings();
		} elseif (isset($_POST['caddy_styles_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['caddy_styles_settings_nonce'])), 'caddy-styles-settings-save')) {
			// Styles settings form processing
			$this->process_styles_settings();
		} else {
			// No valid nonce matched — do not show success message
			return;
		}

		// Set success message
		set_transient('caddy_settings_updated', true, 30);
		
		// Get the current tab parameter from the URL
		$current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'settings';
		
		// Redirect back to the settings page with the active tab as a hash
		wp_safe_redirect(admin_url('admin.php?page=caddy&tab=' . $current_tab));
		exit;
	}

	/**
	 * Process general settings
	 */
	private function process_general_settings() {
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_disable_branding = isset($_POST['cc_disable_branding']) ? sanitize_text_field(wp_unslash($_POST['cc_disable_branding'])) : 'disabled';
		$cc_disable_branding = ( 'enabled' === $cc_disable_branding ) ? 'enabled' : 'disabled';
		update_option('cc_disable_branding', $cc_disable_branding);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_affiliate_id = !empty($_POST['cc_affiliate_id']) ? sanitize_text_field(wp_unslash($_POST['cc_affiliate_id'])) : '';
		update_option('cc_affiliate_id', $cc_affiliate_id);
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_menu_cart_widget = !empty($_POST['cc_menu_cart_widget']) ? sanitize_text_field(wp_unslash($_POST['cc_menu_cart_widget'])) : '';
		update_option('cc_menu_cart_widget', $cc_menu_cart_widget);
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_menu_saves_widget = !empty($_POST['cc_menu_saves_widget']) ? sanitize_text_field(wp_unslash($_POST['cc_menu_saves_widget'])) : '';
		update_option('cc_menu_saves_widget', $cc_menu_saves_widget);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_browse_products_url = !empty($_POST['cc_browse_products_url']) ? esc_url_raw(wp_unslash($_POST['cc_browse_products_url'])) : '';
		update_option('cc_browse_products_url', $cc_browse_products_url);

		// Process any additional general settings fields
		do_action('caddy_save_general_settings');
	}

	/**
	 * Process shipping meter settings
	 */
	private function process_shipping_meter_settings() {
		// Process shipping meter settings form fields
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_free_shipping_amount = !empty($_POST['cc_free_shipping_amount']) ? floatval($_POST['cc_free_shipping_amount']) : '';
		update_option('cc_free_shipping_amount', $cc_free_shipping_amount);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_free_shipping_tax = !empty($_POST['cc_free_shipping_tax']) ? sanitize_text_field(wp_unslash($_POST['cc_free_shipping_tax'])) : 'disabled';
		update_option('cc_free_shipping_tax', $cc_free_shipping_tax);
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_shipping_country = !empty($_POST['cc_shipping_country']) ? sanitize_text_field(wp_unslash($_POST['cc_shipping_country'])) : '';
		update_option('cc_shipping_country', $cc_shipping_country);
		
		// Process any additional shipping meter settings fields
		do_action('caddy_save_shipping_meter_settings');
	}

	/**
	 * Process recommendations settings
	 */
	private function process_recommendations_settings() {
		// Process recommendations settings form fields
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_product_recommendation = isset($_POST['cc_product_recommendation']) ? sanitize_text_field(wp_unslash($_POST['cc_product_recommendation'])) : 'disabled';
		update_option('cc_product_recommendation', $cc_product_recommendation);
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_product_recommendation_type = !empty($_POST['cc_product_recommendation_type']) ? sanitize_text_field(wp_unslash($_POST['cc_product_recommendation_type'])) : '';
		update_option('cc_product_recommendation_type', $cc_product_recommendation_type);
		
		// Process any additional recommendations settings fields
		do_action('caddy_save_recommendations_settings');
	}

	/**
	 * Process display settings
	 */
	private function process_display_settings() {
		
		// Process any additional display settings fields
		do_action('caddy_save_display_settings');
	}

	/**
	 * Process offers settings
	 */
	private function process_offers_settings() {
		
		// Process any additional offers settings fields
		do_action('caddy_save_offers_settings');
	}

	/**
	 * Process save for later settings
	 */
	private function process_sfl_settings() {

		// Only save the enable/disable setting if Pro is not active
		if ( ! caddy_pro_is_active() ) {
			// Save the enable/disable save for later setting
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by the caller before dispatching to this handler.
			if (isset($_POST['cc_enable_sfl_options'])) {
				update_option('cc_enable_sfl_options', 'enabled');
			} else {
				update_option('cc_enable_sfl_options', 'disabled');
			}
		}

		// Process any additional save for later settings fields
		do_action('caddy_save_sfl_settings');
	}

	/**
	 * Process welcome message settings
	 */
	private function process_welcome_message_settings() {
		
		// Process any additional welcome message settings fields
		do_action('caddy_save_welcome_message_settings');
	}

	/**
	 * Process announcement bar settings
	 */
	private function process_announcement_bar_settings() {
		
		// Process any additional announcement bar settings fields
		do_action('caddy_save_announcement_bar_settings');
	}

	/**
	 * Process rewards meter settings
	 */
	private function process_rewards_meter_settings() {

		// Process any additional rewards meter settings fields
		do_action('caddy_save_rewards_meter_settings');
	}

	/**
	 * Process styles settings
	 */
	private function process_styles_settings() {
		// Process styles settings form fields
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in calling function process_settings_form()
		$cc_custom_css = !empty($_POST['cc_custom_css']) ? sanitize_textarea_field(wp_unslash($_POST['cc_custom_css'])) : '';
		update_option('cc_custom_css', $cc_custom_css);
		
		// Process any additional styles settings fields
		do_action('caddy_save_styles_settings');
	}

}
