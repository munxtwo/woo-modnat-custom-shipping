<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once('class-store-map-form.php');

if ( ! class_exists( 'WC_Seven_Eleven_Shipping_Method' ) ) {
	/*
	 * Custom shipping method for 7-11.
	 */
	class WC_Seven_Eleven_Shipping_Method extends WC_Shipping_Method {

		const SERVICE_URL = 'http://ec.shopping7.com.tw/ec3gmap/emap/eServiceMap.php';

		/*
		 * Constructor.
		 */
		public function __construct($instance_id = 0) {
			$this->instance_id = absint( $instance_id );
			$this->id = 'seven_eleven_shipping_method';
			$this->method_title = __('7-11 Shipping Method', 'woo_modnat_custom_shipping' );

			$this->supports  = array(
            	'shipping-zones',
            	'instance-settings',
                'instance-settings-modal',
             );

			// Load the settings
			$this->init_form_fields();

			add_action('woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ));

			// Define user set variables
			$this->enabled = $this->get_option( 'enabled' );
			$this->title = $this->get_option( 'title' );
			$this->eshop_uid = $this->get_option( 'eshop_uid' );
			$this->eshop_id = $this->get_option( 'eshop_id' );
			$this->eshop_servicetype = $this->get_option( 'eshop_servicetype' );
			$this->eshop_hasoutside = $this->get_option( 'eshop_hasoutside' );
			$this->flatrate_fee = $this->get_option( 'flatrate_fee' );
			$this->freeshipping_threshold = $this->get_option( 'freeshipping_threshold' );
		}

		/*
		 * Initializes the admin setting fields.
		 */
		public function init_form_fields() {
			$this->instance_form_fields = array(
				'enabled' => array(
					'title' 	=> __('Enable/Disable', 'woo_modnat_custom_shipping'),
					'type' 		=> 'checkbox',
					'label' 	=> __('Enable 7-11 Shipping', 'woo_modnat_custom_shipping'),
					'default' => 'no'
				),
				'title' => array(
					'title' 		  => __('Method Title', 'woo_modnat_custom_shipping'),
					'type' 			  => 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'woo_modnat_custom_shipping'),
					'default'		  => __('7-11 Shipping', 'woo_modnat_custom_shipping'),
				),
				'eshop_uid' => array(
					'title' 		  => 'uid',
					'type' 			  => 'text',
					'default'		  => '829',
				),
				'eshop_id' => array(
					'title' 		  => 'eshopid',
					'type' 			  => 'text',
					'default'		  => '208',
				),
				'eshop_servicetype' => array(
					'title' 		  => __('Service Type', 'woo_modnat_custom_shipping'),
					'type' 			  => 'select',
					'options'		  => array('1' => '取貨付款', '3' => '取貨不付款'),
				),
				'eshop_hasoutside' => array(
					'title' 		  => __('Has Outside', 'woo_modnat_custom_shipping'),
					'type' 			  => 'select',
					'options'		  => array('1' => '顯示本島 + 離島全部門市',
					'2' => '顯示本島 + 澎湖 + 綠島門市 ( 不含連江、金門門市 )',
					'3' => '顯示本島門市'),
				),
				'flatrate_fee' => array(
					'title' 		  => __('Flat rate fee (TWD)', 'woo_modnat_custom_shipping'),
					'type' 			  => 'number',
					'description' 	  => __('This sets the flat rate fee to charge for this shipping method.', 'woo_modnat_custom_shipping'),
					'default'		  => '50',
				),
				'freeshipping_threshold' => array(
					'title' 		  => __('Free shipping threshold amount (TWD)', 'woo_modnat_custom_shipping'),
					'type' 			  => 'number',
					'description' 	  => __('This sets the cart total amount threshold for free shipping.', 'woo_modnat_custom_shipping'),
					'default'		  => '10000',
				),
			);
		}

        /**
         * Calculates shipping cost.
         */
        public function calculate_shipping($package = array()) {
			// Check if coupon with free shipping was applied
			$has_coupon = false;
			if ($coupons = WC()->cart->get_coupons()) {
				foreach($coupons as $coupon) {
					if ($coupon->is_valid() && $coupon->get_free_shipping()) {
						$has_coupon = true;
						break;
					}
				}
			}

			$cart_total = WC()->cart->cart_contents_total;
			if ($cart_total >= $this->freeshipping_threshold || $has_coupon) {
				$cost = 0;
			} else {
				$cost = $this->flatrate_fee;
			}
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => $cost
            );
            $this->add_rate( $rate );
        }

		/*
		 * Constructs the map form html and returns it.
		 */
		function get_map_form_html($existingStoreId = '') {
			if (!defined('Plugin_URL')) {
				define('Plugin_URL', plugins_url());
			}

			$serverReplyUrl = Plugin_URL."/woo-modnat-custom-shipping/getResponse.php";
			$formObj = new StoreMapForm();
			$formObj->ServiceURL = self::SERVICE_URL;
			$formObj->PostParams = array(
				'uid' => $this->eshop_uid,
				'eshopid' => $this->eshop_id,
				'servicetype' => $this->eshop_servicetype,
				'url' => $serverReplyUrl,
				'tempvar' => '',
				'storeid' => $existingStoreId,
				'display' => (wp_is_mobile() ? 'touch' : 'page'),
				'charset' => 'utf-8',
				'hasoutside' => $this->eshop_hasoutside
			);

			// Return form html
			return $formObj->StoreMap(__('Select store', 'woo_modnat_custom_shipping'), 'mapForm');
		}
	}
}

?>
