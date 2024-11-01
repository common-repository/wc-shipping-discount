<?php
/**
 * Plugin Name: Shipping Discount for WooCommerce
 * Plugin URI: https://1teamsoftware.com/product/woocommerce-shipping-discount/
 * Description: Applies discount to shipping rates based on the rules
 * Tested up to: 6.6
 * Version: 1.0.15
 * Author: OneTeamSoftware
 * Author URI: http://oneteamsoftware.com/
 * Developer: OneTeamSoftware
 * Developer URI: http://oneteamsoftware.com/
 * Text Domain: wc-shipping-discount
 * Domain Path: /languages
 *
 * Copyright: © 2024 FlexRC, 604-1097 View St, V8V 0G9, Canada. Voice 604 800-7879 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace OneTeamSoftware\WooCommerce\ShippingDiscount;

if (!defined('ABSPATH')) { 
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    include_once 'includes/ShippingDiscount.php';
}

