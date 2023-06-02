<?php   
if (!defined('_PS_VERSION_'))
	exit;

class GtmDataLayer extends Module
{
	public function __construct()
	{
		$this->name = 'gtmdatalayer';
		$this->table_name = 'gtm_data_layer';
		$this->tab = 'analytics_stats';
		$this->version = '1.4';
		$this->author = 'petrovv77';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('GTM Data Layer Module');
		$this->description = $this->l('Adds data layer data for use by Google Tag Manager.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		// Only if we need to configure the module
		//if (!Configuration::get('MYMODULE_NAME'))
		//	$this->warning = $this->l('No name provided');
	}

	public function install()
	{
		if (!parent::install() ||
			!$this->registerHook('orderConfirmation'))	// paymentReturn has objOrder but is locked to the single module that was used for payment, uses it's own confirmation tpl
			return false;
		if (!Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.$this->table_name.'` (
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

		return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.$this->table_name.'`');
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
			$gtm_order_sent = Db::getInstance()->getValue('SELECT sent FROM `'._DB_PREFIX_.$this->table_name.'` WHERE id_order = '.(int)$order->id);
			if ($gtm_order_sent === false)
			{
				$order_products = array();
				$cart = new Cart($order->id_cart);
				foreach ($cart->getProducts() as $order_product) {
					// https://developers.google.com/analytics/devguides/collection/ga4/ecommerce?client_type=gtm#make_a_purchase_or_issue_a_refund
					$item_name = $order_product['name'];
					$item_id = $order_product['id_product'];
					$price = number_format($order_product['price_wt'], 2, '.', '');
					$quantity = $order_product['cart_quantity'];
					$order_products[] = ['item_name' => (string)$item_name, 'item_id' => (string)$item_id, 'price' => (float)$price, 'quantity' => (int)$quantity];
				}
				
				$products_json = json_encode($order_products);
               
				Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.$this->table_name.'` (id_order, sent, date_add) VALUES ('.(int)$order->id.', 1, NOW())');

				$dl = new stdClass();
				$dl->transaction_id = (string)$order->id;
				$dl->value = (float)number_format($order->total_paid, 2, '.', '');
				$dl->tax = (float)($order->total_paid_tax_incl - $order->total_paid_tax_excl);
				$dl->shipping = (float)number_format($order->total_shipping, 2);
				$dl->currency = (string)$params['currencyObj']->iso_code;
				$dl->items = $products_json;
				
				Media::addJsDef(array('dl' => $dl));
          			$this->context->controller->addJS($this->_path.'views/js/gtmdatalayer.js');
			}
		}
	}
}
