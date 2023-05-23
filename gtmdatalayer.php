<?php   
if (!defined('_PS_VERSION_'))
	exit;

class GtmDataLayer extends Module
{
	public function __construct()
	{
		$this->name = 'gtmdatalayer';
		$this->tab = 'analytics_stats';
		$this->version = '1.1';
		$this->author = 'petrovv77';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Data Layer Module');
		$this->description = $this->l('Adds data layer data for use by Google Tag Manager.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		// Only if we need to configure the module
		//if (!Configuration::get('MYMODULE_NAME'))
		//	$this->warning = $this->l('No name provided');
	}

	public function install()
	{
		if (!parent::install() ||
			!$this->registerHook('orderConfirmation'))
			return false;
		if (!Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'data_layer` (
				`id_data_layer` int(11) NOT NULL AUTO_INCREMENT,
				`id_order` int(11) NOT NULL,
				`sent` tinyint(1) DEFAULT NULL,
				`date_add` datetime DEFAULT NULL,
				PRIMARY KEY (`id_data_layer`),
				KEY `id_order` (`id_order`),
				KEY `sent` (`sent`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'))
			return $this->uninstall();
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;

		return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'data_layer`');
	}


	/**
	* To track transactions
	*/
	public function hookOrderConfirmation($params)
	{
		if (version_compare(_PS_VERSION_, '1.7', '<')) {
    			$order = $params['objOrder'];
		} else {
    			$order = $params['order'];
		}
		
		if (Validate::isLoadedObject($order))
		{
			$gtm_order_sent = Db::getInstance()->getValue('SELECT sent FROM `'._DB_PREFIX_.'data_layer` WHERE id_order = '.(int)$order->id);
			if ($gtm_order_sent === false)
			{
				$order_products = array();
				$cart = new Cart($order->id_cart);
				foreach ($cart->getProducts() as $order_product) {
					$order_products[] = "{
						'item_name': '".str_replace ("'", '"', $order_product['name'])."',
						'item_id': '{$order_product['id_product']}',
						'price': '".number_format($order_product['price_wt'], 2)."',
						'quantity': {$order_product['cart_quantity']}
					}";
				}
				
				$products_string = implode(',', $order_products);
               
				Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'data_layer` (id_order, sent, date_add) VALUES ('.(int)$order->id.', 1, NOW())');

				$data_layer = "
					<script>
					window.dataLayer = window.dataLayer || [];
					dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
					dataLayer.push({
  						'event': 'purchase',
  						'ecommerce': {
							'transaction_id': '$order->id',
							'value': '".number_format($order->total_paid, 2, '.', '')."',
							'tax': '".($order->total_paid_tax_incl - $order->total_paid_tax_excl)."',
							'shipping': '".number_format($order->total_shipping, 2)."',
							'currency': 'EUR',
							'items': [$products_string]
  						}
					});
					</script>";
          			return $data_layer;
			}
		}
	}
}
