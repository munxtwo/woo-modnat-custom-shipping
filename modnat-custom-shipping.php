<?php
/*
Plugin Name: WooCommerce Modnat Custom Shipping Methods
Plugin URI: https://modnat.com.tw
Description: Custom Shipping Methods for WooCommerce
Version: 1.0.2
Author: Modern Nature
Author URI: https://modnat.com.tw
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/*
	 * Initializes the custom shipping methods.
	 */
	add_action('woocommerce_shipping_init', 'custom_shipping_methods');
	function custom_shipping_methods() {
		require_once("class-seven-eleven-shipping-method.php");
		require_once("class-cvs-shipping-method.php");
	}

	/*
	 * Adds the custom shipping methods to the list of available shipping methods.
	 */
	add_filter('woocommerce_shipping_methods', 'custom_add_shipping_methods');
	function custom_add_shipping_methods($methods) {
        $methods['seven_eleven_shipping_method'] = 'WC_Seven_Eleven_Shipping_Method';
		$methods['cvs_shipping_method'] = 'WC_CVS_Shipping_Method';
        return $methods;
    }

	/*
	 * Performs custom validation of custom shipping specific fields during checkout.
	 */
	add_action('woocommerce_checkout_process', 'action_woocommerce_checkout_validate_fields');
	function action_woocommerce_checkout_validate_fields() {
		$shipping_method = WC()->session->get('chosen_shipping_methods');
		if (empty($_POST['store_id'])) {
			if ($shipping_method[0] === 'seven_eleven_shipping_method' || $shipping_method[0] === 'cvs_shipping_method') {
				wc_add_notice(__('Please first select a store to proceed.', 'woo_modnat_custom_shipping'), 'error');
			}
		}
	}

	/*
	 * Saves the populated custom shipping specific fields during checkout.
	 */
	add_action('woocommerce_checkout_update_order_meta', 'action_woocommerce_checkout_save_fields');
	function action_woocommerce_checkout_save_fields($order_id) {
		if(!empty($_POST['store_id']) && !empty($_POST['store_address'])) {
			update_post_meta($order_id, 'shipping_storeId', wc_clean($_POST['store_id']));
			update_post_meta($order_id, 'shipping_storeName', wc_clean($_POST['store_name']));
			update_post_meta($order_id, 'shipping_storeAddress', wc_clean($_POST['store_address']));
		}
	}

	/*
	 * Overrides the formatted shipping address displayed in the order.
	 */
	add_filter('woocommerce_order_formatted_shipping_address', 'custom_order_formatted_shipping_address', 10, 2);
	function custom_order_formatted_shipping_address($address, $order) {
		if (!empty(get_post_meta($order->get_id(), 'shipping_storeName', true))) {
			$address = array(
				'store_id' => get_post_meta($order->get_id(), 'shipping_storeId', true),
				'store_name' => get_post_meta($order->get_id(), 'shipping_storeName', true),
				'store_address' => get_post_meta($order->get_id(), 'shipping_storeAddress', true)
	    	);
		}

	    return $address;
	}

	/*
	 * Modifies the formatting of the address.
	 */
	add_filter('woocommerce_localisation_address_formats', 'custom_address_format');
	function custom_address_format($formats) {
		if (is_admin()) {
			$formats['default'] = "{store_id}\n{store_name}\n{store_address}\n{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}";
		} else {
			$formats['default'] = "{store_name}\n{store_address}\n{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}";
		}

		return $formats;
	}

	/*
	 * Replaces replacement string with actual data.
	 */
	add_filter('woocommerce_formatted_address_replacements', 'custom_formatted_address_replacements', 10, 2);
	function custom_formatted_address_replacements($replacements, $args) {
		if (isset($args['store_name'])) {
			$replacements['{store_id}'] = $args['store_id'];
			$replacements['{store_name}'] = $args['store_name'];
			$replacements['{store_address}'] = $args['store_address'];
		}

	    return $replacements;
	}

	/*
	 * Adds custom checkout fields to store custom shipping method specific info.
	 */
	add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');
	function custom_override_checkout_fields($fields) {
		$fields['billing']['store_id'] = array(
			'default'       => '',
			'label'         => __('Store Id', 'woo_modnat_custom_shipping'),
			'class'         => array('hidden'),
			'label_class'   => array('hidden')
		);
		$fields['billing']['store_name'] = array(
			'default'       => '',
			'label'         => __('Store Name', 'woo_modnat_custom_shipping'),
			'clear'         => true
		);
		$fields['billing']['store_address'] = array(
			'default'       => '',
			'label'         => __('Store Address', 'woo_modnat_custom_shipping'),
			'clear'         => true
		);

		return $fields;
	}

	/*
	 * Add custom html to display shipping method map.
	 */
	add_action('woocommerce_review_order_after_shipping', 'action_woocommerce_add_map_after_shipping');
	function action_woocommerce_add_map_after_shipping() {
		// Get TW shipping zone
		$packages = WC()->cart->get_shipping_packages();
		foreach ( $packages as $i => $package ) {
			if ($package['destination']['country'] === 'TW') {
				$zone = WC_Shipping_Zones::get_zone_matching_package($package);
				break;
			}
		}

		// Hide fields if zone not set
		if (!isset($zone)) {
			hide_custom_fields();
			return;
		}

		// Get selected shipping method
		$chosen_method = WC()->session->get('chosen_shipping_methods');

		// Get previous selected shipping method
		$prevSelectedShipping = get_post_meta(get_current_user_id(), '_prev_selected_shipping', true);

		// Display html form for map
		parse_str($_POST['post_data'], $post_data);
		if ($chosen_method[0] === "seven_eleven_shipping_method" || $chosen_method[0] === "cvs_shipping_method") {
			$chosen_method_object = get_chosen_shipping_method_instance($zone, $chosen_method[0]);

			// Set existing store id if a store was previously selected
			$existingStoreId = '';
			if (!empty($prevSelectedShipping) && $prevSelectedShipping === $chosen_method[0] &&
					isset($post_data['store_id']) && $post_data['store_id'] !== '') {
				$existingStoreId = $post_data['store_id'];
			} else {
				hide_custom_fields();
			}

			// Get form
			$html = $chosen_method_object->get_map_form_html($existingStoreId);

			echo '
			<tr class="shipping_option">
			<th>' . $chosen_method_object->title . '</th>
			<td>
			'.$html.'
			</td>
			</tr>
			';

			?>
			<script type="text/javascript">
				if (document.getElementById("__paymentButton") !== null
					&& typeof document.getElementById("__paymentButton") !== "undefined") {
					document.getElementById("__paymentButton").onclick = function() {
						map = window.open("", "mapForm", "width=1000,height=600,toolbar=0");
						if (map) {
							document.getElementById("mapFormId").submit();
						}
					};
				}
			</script>
			<?php
		} else {
			// Hide custom fields for other shipping methods
			hide_custom_fields();
		}

		// Set previous selected shipping method to current one
		update_post_meta(get_current_user_id(), '_prev_selected_shipping', $chosen_method[0]);
	}

	/*
	 * Javascript to hide custom fields.
	 */
	function hide_custom_fields() {
		?>
		<script type="text/javascript">
			document.getElementById('store_id').setAttribute("readonly", true);
			document.getElementById('store_id').value = '';
			document.getElementById('store_id_field').style.display = 'none';
			document.getElementById('store_name').setAttribute("readonly", true);
			document.getElementById('store_name').value = '';
			document.getElementById('store_name_field').style.display = 'none';
			document.getElementById("store_name_field").querySelector("div").classList.add("fl-is-active");
			document.getElementById('store_address').setAttribute("readonly", true);
			document.getElementById('store_address').value = '';
			document.getElementById('store_address_field').style.display = 'none';
			document.getElementById("store_address_field").querySelector("div").classList.add("fl-is-active");
		</script>
		<?php
	}

	/*
	 * Gets the shipping method instance of the current chosen shipping method.
	 */
	function get_chosen_shipping_method_instance($zone, $chosen_method) {
		$shipping_methods = $zone->get_shipping_methods();
		foreach ($shipping_methods as $shipping_method) {
			if ($shipping_method->id === $chosen_method) {
				return $shipping_method;
			}
	    }
	}

}
?>
