<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once('class-store-map-form.php');

if ( ! class_exists( 'WC_CVS_Shipping_Method' ) ) {
	/*
	 * Custom shipping method for CVS.
	 */
	class WC_CVS_Shipping_Method extends WC_Shipping_Method {

		const DESKTOP_SERVICE_URL = 'http://cvs.map.com.tw/default.asp';
		const MOBILE_SERVICE_URL = 'http://mcvs.map.com.tw/default.asp';
		const CVS_NAME = 'https://modnat.com.tw';

		/*
		 * Constructor.
		 */
		public function __construct($instance_id = 0) {
			$this->instance_id = absint( $instance_id );
			$this->id = 'cvs_shipping_method';
			$this->method_title = __( 'CVS Shipping Method', 'woocommerce' );

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
			$this->flatrate_fee = $this->get_option( 'flatrate_fee' );
			$this->freeshipping_threshold = $this->get_option( 'freeshipping_threshold' );
		}

		/*
		 * Initializes the admin setting fields.
		 */
		public function init_form_fields() {
			$this->instance_form_fields = array(
				'enabled' => array(
					'title' 	=> __( 'Enable/Disable', 'woocommerce' ),
					'type' 		=> 'checkbox',
					'label' 	=> __( 'Enable CVS Shipping', 'woocommerce' ),
					'default' => 'no'
				),
				'title' => array(
					'title' 		  => __( 'Method Title', 'woocommerce' ),
					'type' 			  => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'		  => __( 'CVS Shipping', 'woocommerce' ),
				),
				'flatrate_fee' => array(
					'title' 		  => 'Flat rate fee (TWD)',
					'type' 			  => 'number',
					'description' 	  => 'This sets the flat rate fee to charge for this shipping method.',
					'default'		  => '50',
				),
				'freeshipping_threshold' => array(
					'title' 		  => 'Free shipping threshold amount (TWD)',
					'type' 			  => 'number',
					'description' 	  => 'This sets the cart total amount threshold for free shipping.',
					'default'		  => '10000',
				),
			);
		}

        /**
         * Calculates shipping cost.
         */
        public function calculate_shipping($package = array()) {
			$cart_total = WC()->cart->cart_contents_total;
			if ($cart_total >= $this->freeshipping_threshold) {
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

			$formObj = new StoreMapForm();
			$formObj->ServiceURL = add_query_arg( array(
									    'cvsname' => self::CVS_NAME,
									    'cvsspot' => $existingStoreId,
									), (wp_is_mobile() ? self::MOBILE_SERVICE_URL : self::DESKTOP_SERVICE_URL) );

			// Return form html
			return $formObj->StoreMap('Select CVS store', 'mapForm');
		}
	}
}

?>
