<?php

namespace OneTeamSoftware\WooCommerce\ShippingDiscount;

if (!defined('ABSPATH')) { 
    exit; // Exit if accessed directly
}

class ShippingDiscount
{
	private $id;
	private $mainMenuId;

    public function __construct() 
    {
		$this->id = 'wc-shipping-discount';
		$this->mainMenuId = 'oneteamsoftware';
    }

    public function register() 
    {
        add_action('woocommerce_shipping_init', array($this, 'addShippingMethod'));
		add_filter('woocommerce_shipping_methods', array($this, 'getShippingMethods'));
		
		if (is_admin()) {
			require_once(__DIR__ . '/Admin/OneTeamSoftware.php');
			\OneTeamSoftware\WooCommerce\Admin\OneTeamSoftware::instance()->register();
			
			add_action('admin_menu', array($this, 'onAdminMenu'));
		}
    }
	
	public function onAdminMenu()
	{
		add_submenu_page($this->mainMenuId, __('Shipping Discount', $this->id), __('Shipping Discount', $this->id), 'manage_options', 'admin.php?page=wc-settings&tab=shipping&section=' . $this->id);
	}

    public function addShippingMethod() 
    {
        include_once __DIR__ . '/ShippingMethod.php';
    }

    public function getShippingMethods($methods) 
    {
        $methods['wc-shipping-discount'] = '\OneTeamSoftware\WooCommerce\ShippingDiscount\ShippingMethod';
        return $methods;
    }
};

(new ShippingDiscount())->register();