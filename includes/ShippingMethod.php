<?php
namespace OneTeamSoftware\WooCommerce\ShippingDiscount;

//declare(strict_types=1);

class ShippingMethod extends \WC_Shipping_Method
{
	// we will store number of instances of this class in order to prevent duplicated binding of some of the filters
	static $instancesCount = 0;

	/**
	 * Constructor
	 */
	public function __construct() 
	{
		$this->id = 'wc-shipping-discount'; 
		$this->method_title = __('Shipping Discount', $this->id);  
		$this->method_description = sprintf('%s<br/><br/>%s <a href="%s" target="_blank">%s</a>.<br/>%s <a href="%s" target="_blank">%s</a>.', 
		__('Configure shipping discount based on the amount spent for the items that are matching configured conditions', $this->id),
		__('Do you have any questions or requests?', $this->id), 
		'https://1teamsoftware.com/contact-us/', 
		__('We are here to help you!', $this->id),
		 __('Can you recommend <strong>Shipping Discount</strong> plugin to others?', $this->id), 
		 'https://wordpress.org/support/plugin/wc-shipping-discount/reviews/', 
		 __('Please take a moment to leave your review', $this->id));

		
		self::$instancesCount++;

		$this->init();
	}

	/**
	 * Destructor
	 */
	function __destruct()
	{
		self::$instancesCount--;
	}

	/**
	 * Initialize some of the stuff
	 */
	protected function init()
	{
		// Load the settings API
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		if (empty($this->settings) || !is_array($this->settings)) {
			$this->settings = [];
		}

		if (is_admin()) {
			// Save settings in admin if you have any defined
			add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
		}

		// prevent duplicated execution of the package rates that happens without this hack
		if (self::$instancesCount == 1) {
			if ($this->settings['enabled'] == 'yes') {
				add_filter('woocommerce_package_rates', array($this, 'onPackageRates'), 2, 100);
			}
		}		
	}

	/**
	 * Returns discounted rates when woocommerce asks for the rates
	 */
	public function onPackageRates($rates, $package)
	{
		$this->debug('onPackageRates');

		$shippingClassPackages = $this->getShippingClassPackages($package);

		foreach ($shippingClassPackages as $shippingClass => $shippingClassPackage) {
			$rates = $this->getDiscountedShippingRates($shippingClass, $shippingClassPackage, $rates);
		}

		return $rates;
	}

	/**
	 * Returns fields for the plugin settings form
	 */
	public function init_form_fields() 
	{
		$this->form_fields = array(	
			'enabled' => array(
				'title' => __('Enable', $this->id),
				'type' => 'checkbox',
				'description' => __('Enable shipping discount.', $this->id),
				'default' => 'yes'
			),
			'debug' => array(
				'title' => __('Debug Mode', $this->id),
				'label' => __('Enable debug mode', $this->id),
				'type' => 'checkbox',
				'default' => 'no',
				'desc_tip' => true,
				'description' => __('Enable debug mode to show debugging information on the cart/checkout.', $this->id)
			),
			'rules' => array(
				'type' => 'rules',
			),
		);
		
		$this->form_fields = apply_filters($this->id . '_settings', $this->form_fields);
	}

	/**
	 * Returns HTML for the "shippingClasses"
	 */
	function generate_rules_html($key)
	{
		$shippingClasses = $this->getShippingClasses();

		ob_start();
?>
<style type="text/css">
#shippingDiscountRules table th {
	text-align: center;
	font-weight: bold; 
}
#shippingDiscountRules table td {
	text-align: left;
}
#shippingDiscountRules .title {
	font-weight: bold; 
	text-align: left;
}
#shippingDiscountRules table td.checkbox {
	text-align: center;
}
</style>

<table>
	<tr valign="top" id="shippingDiscountRules">
		<td class="forminp" id="<?php echo $this->id; ?>_shippingDiscountRules">
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Active', $this->id)?></th>
						<th><?php _e('Shipping Class', $this->id)?></th>
						<th><?php _e('Min Order Amount', $this->id)?></th>
						<th><?php _e('% of Order Amount Discount', $this->id)?></th>
						<th><?php _e('Deduct Sale Discount', $this->id)?></th>
						<th><?php _e('Deduct Coupon Discount', $this->id)?></th>
					</tr>
				</thead>

				<tbody>
<?php
				foreach ($shippingClasses as $slug => $shippingClassName) {
					$rule = array(
						'enabled' => 'no',
						'min_amount' => '',
						'percentage_discount' => '',
						'deduct_sale_discount' => 'no',
						'deduct_coupon_discount' => 'no'
					);
					if (!empty($this->settings['rules'][$slug])) {
						$rule = array_merge($rule, $this->settings['rules'][$slug]);
					}
					
					?>
										<tr>
											<td class="checkbox"><input type="checkbox" name="rules[<?php echo $slug; ?>][enabled]" value="yes" <?php checked($rule['enabled'] == 'yes', true); ?> /></td>
											<td class="title"><?php _e($shippingClassName, $this->id)?></td>
											<td><input type="text" name="rules[<?php echo $slug; ?>][min_amount]" value="<?php echo $rule['min_amount'] ?>" /></td>
											<td><input type="text" name="rules[<?php echo $slug; ?>][percentage_discount]" value="<?php echo $rule['percentage_discount'] ?>" /></td>
											<td class="checkbox"><input type="checkbox" name="rules[<?php echo $slug; ?>][deduct_sale_discount]" value="yes" <?php checked($rule['deduct_sale_discount'] == 'yes', true); ?> /></td>
											<td class="checkbox"><input type="checkbox" name="rules[<?php echo $slug; ?>][deduct_coupon_discount]" value="yes" <?php checked($rule['deduct_coupon_discount'] == 'yes', true); ?> /></td>
										</tr>
<?php
				}
?>
				</tbody>
				<tfoot>
					<td colspan="6">
						<ul>
							<li><strong><?php _e('Active', $this->id)?></strong> <?php _e('- rule should be active in order to be considered.', $this->id)?>
							<li><strong><?php _e('Shipping Class', $this->id)?></strong> <?php _e('- rule will be applied only to the products with a given shipping class.', $this->id)?>
							<li><strong><?php _e('Min Order Amount', $this->id)?></strong> <?php _e('- total amount spent for the products with a given shipping class should be more than that value in order for discount to activate.', $this->id)?>
							<li><strong><?php _e('% of Order Amount Discount', $this->id)?></strong> <?php _e('- Percentage of total amount spent for the products that match this rule, that will be used as a discount that will be deducted from the shipping rate.', $this->id)?>
							<li><strong><?php _e('Deduct Sale Discount', $this->id)?></strong> <?php _e('- Reduce shipping discount on total amount saved because of the product sale price.', $this->id)?>
							<li><strong><?php _e('Deduct Coupon Discount', $this->id)?></strong> <?php _e('- Reduce shipping discount on the amount saved because of the applied coupons.', $this->id)?>														
						</ul>
					</td>
				</tfoot>
			</table>
		</td>
	</tr>
</table>

<?php
		return ob_get_clean();
	}

	/**
	 * Validates shipping discount rules
	 */
	public function validate_rules_field($key)
	{
		// REMEMBER TO SANITIZE INPUT:
		// https://developer.wordpress.org/plugins/security/securing-input/
		$key = sanitize_key($key);

		if (empty($_POST[$key]) || !is_array($_POST[$key])) {
			return array();
		}

		$rules = array();
		foreach ($_POST[$key] as $shippingClass => $rule) {
			$shippingClass = sanitize_key($shippingClass);

			foreach ($rule as $propertyName => $value) {
				$propertyName = sanitize_key($propertyName);

				// validate checkboxes
				if (in_array($propertyName, array('enabled', 'deduct_sale_discount', 'deduct_coupon_discount'))) {
					if ($value == 'yes') {
						$rules[$shippingClass][$propertyName] = $value;
					}
				// validate numeric field
				} else if (in_array($propertyName, array('min_amount', 'percentage_discount'))) {
					if (is_numeric($value)) {
						$rules[$shippingClass][$propertyName] = floatval($value);
					}
				}
			}
		}

		return $rules;
	}
	
	/**
	 * Returns array of shipping classes in the format: slug => name 
	 */
	protected function getShippingClasses()
	{
		$shippingClasses = array();
		$classes = WC()->shipping->get_shipping_classes();

		foreach ($classes as $shippingClass => $class) {
			$shippingClasses[$class->slug] = $class->name;
		}

		return $shippingClasses;
	}
	
	/**
	 * Returns packages grouped by shipping class
	 */
	protected function getShippingClassPackages($package)
	{
		$newPackageBase = $package;
		$newPackageBase['contents'] = array();

		$shippingClassPackages = array();

		foreach ($package['contents'] as $itemId => $item) { 
			$shippingClass = $item['data']->get_shipping_class();

			if (empty($shippingClassPackages[$shippingClass])) {
				$shippingClassPackages[$shippingClass] = $newPackageBase;
			}

			$shippingClassPackages[$shippingClass]['contents'][$itemId] = $item;
		}

		return $shippingClassPackages;
	}

	/**
	 * Discounts shipping rates based on the rules for a given shipping class and package contents
	 */
	protected function getDiscountedShippingRates($shippingClass, $shippingClassPackage, $rates)
	{
		$this->debug('getDiscountedShippingRates for ' . $shippingClass);

		$rule = null;
		if (!empty($this->settings['rules'][$shippingClass])) {
			$rule = $this->settings['rules'][$shippingClass];
		}

		$shippingDiscount = $this->getShippingDiscount($rule, $shippingClassPackage);

		if (!empty($shippingDiscount)) {
			// reduce discount on the amount of all the applied coupons, if feature is active
			if ($rule['deduct_coupon_discount'] == 'yes') {
				$couponDiscount = $this->getCouponDiscount($shippingClassPackage);
				if (!empty($couponDiscount)) {
					$shippingDiscount -= $couponDiscount;
				}	
			}

			// reduce discount on the amount of all the sale discounts, if feature is active
			if ($rule['deduct_sale_discount'] == 'yes') {
				$saleDiscount = $this->getSaleDiscount($shippingClassPackage);
				if (!empty($saleDiscount)) {
					$shippingDiscount -= $saleDiscount;
				}
			}

			if ($shippingDiscount < 0) {
				$shippingDiscount = 0;
			}
		}

		$this->debug('Final Shipping Discount: ' . $shippingDiscount);

		// adjust shipping rates
		if (!empty($shippingDiscount)) {
			foreach ($rates as $key => $rate) {
				$this->debug('Original Rate: ' . print_r($rate, true));
	
				$rates[$key] = $this->getDiscountedShippingRate($rate, $shippingDiscount);
			}		
		}
		
		return $rates;
	}

	/**
	 * Returns shipping rate object adjusted on a given discount
	 */
	protected function getDiscountedShippingRate($rate, $shippingDiscount)
	{
		// do not attempt to adjust free shipping
		if (empty($rate->cost) || $rate->cost <= 0) {
			return $rate;
		}

		$originalCost = $rate->cost;
		$rate->cost -= $shippingDiscount;

		if ($rate->cost < 0) {
			$rate->cost = 0;
		} 

		// we also have to adjust taxes
		$multiplyTaxBy = $rate->cost / $originalCost;
		$this->debug('Multiply Tax By: ' . $multiplyTaxBy);

		if (!empty($rate->taxes)) {
			$taxes = $rate->taxes;
			foreach ($taxes as $taxId => $tax) {
				$taxes[$taxId] = floatval($tax) * $multiplyTaxBy;
			}

			$rate->set_taxes($taxes);
		}

		$this->debug('Discounted Rate: ' . print_r($rate, true));

		return $rate;
	}

	/**
	 * Returns discount for a given rule based on the package contents
	 */
	protected function getShippingDiscount($rule, $package)
	{
		$this->debug('getShippingDiscount');

		if (empty($rule['enabled']) || $rule['enabled'] != 'yes') {
			$this->debug('This shipping class is not enabled');

			return 0;
		}

		$packageAmount = $this->getPackageAmount($package);
		
		$this->debug('Value of the items: ' . $packageAmount);

		$minAmount = 0;
		if (!empty($rule['min_amount'])) {
			$minAmount = floatval($rule['min_amount']);
		}

		if ($packageAmount < $minAmount) {
			$this->debug('It does not qualify');

			return 0;
		}

		// calculate percentage of the order amount discount
		$percentageDiscount = floatval($rule['percentage_discount']) / 100;
		$shippingDiscount = $packageAmount * $percentageDiscount;

		$this->debug('Shipping Discount: ' . $shippingDiscount);

		return $shippingDiscount;
	} 

	/**
	 * Calculates total amount of the package
	 */
	protected function getPackageAmount($package) 
	{
		$this->debug('getPackageAmount');

		$packageAmount = 0;
		foreach ($package['contents'] as $itemId => $values) { 
			$productItem = $values['data']; 

			$this->debug('Product item: ' . $itemId . ',' . $productItem->get_name() . ', price: ' . $productItem->get_price() . ', regular price: ' . $productItem->get_regular_price() . ', qty: ' . $values['quantity']);

			$packageAmount = $packageAmount + $productItem->get_price() * $values['quantity']; 
		}

		return $packageAmount;
	}
	
	/**
	 * Calculates total sale discount of the package
	 */
	protected function getSaleDiscount($package)
	{
		$this->debug('getSaleDiscount');

		$saleDiscount = 0;
		foreach ($package['contents'] as $itemId => $values) { 
			$productItem = $values['data'];

			$saleDiscount += ($productItem->get_regular_price() - $productItem->get_price()) * $values['quantity'];
		}

		$this->debug('Sale Discount: ' . $saleDiscount);

		return $saleDiscount;
	}

	/**
	 * Calculates total coupon discount of the package
	 */
	protected function getCouponDiscount($package)
	{
		$this->debug('getCouponDiscount');

		$items = array();
		foreach ($package['contents'] as $itemId => $values) { 
			$item = new \stdClass();
			$item->packageName = $itemId;
			$item->object = $values;
			$item->product = $values['data'];
			$item->quantity = $values['quantity'];
			$item->price = wc_add_number_precision_deep($item->product->get_price() * $item->quantity);
			$items[$itemId] = $item;			
		}

		$discounts = new \WC_Discounts();
		$discounts->set_items($items);

		if (!empty($package['applied_coupons'])) {
			$coupons = $package['applied_coupons'];
			$this->debug('Applied  Coupons: ' . print_r($coupons, true));

			foreach ($coupons as $coupon) {
				$discounts->apply_coupon(new \WC_Coupon($coupon));
			}
		}

		$couponDiscount = 0;
		$discounts = $discounts->get_discounts_by_item(false);
		foreach ($discounts as $discount) {
			$couponDiscount += $discount;
		}

		$this->debug('Coupon Discount: ' . $couponDiscount);

		return $couponDiscount;
	}

	/**
	 * Displays debug messages
	 */
	protected function debug($message, $type = 'notice') 
	{
		if ($this->settings['debug'] == 'yes' ) {
			wc_add_notice($message, $type);
		}
	}
}



