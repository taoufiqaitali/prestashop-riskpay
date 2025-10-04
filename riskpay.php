<?php
if (!defined('_PS_VERSION_')) {
	exit;
}

class RiskPay extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'riskpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'Taoufiq Ait Ali';
		$this->controllers = array('payment', 'validation');
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		$this->need_instance = 0;
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('RiskPay');
		$this->description = $this->l('Accept payments with RiskPay.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall RiskPay?');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function getContent()
	{
		$output = '';
			if (Tools::isSubmit('submitRiskPay')) {
				$wallet = Tools::getValue('RISKPAY_USDC_WALLET');
				$provider = Tools::getValue('RISKPAY_PROVIDER');
				if (!$wallet) {
					$output .= $this->displayError($this->l('USDC Wallet address is required.'));
				} elseif (!$provider) {
					$output .= $this->displayError($this->l('Provider is required.'));
				} else {
					Configuration::updateValue('RISKPAY_USDC_WALLET', $wallet);
					Configuration::updateValue('RISKPAY_PROVIDER', $provider);
					$output .= $this->displayConfirmation($this->l('Settings updated'));
				}
			}
			return $output.$this->renderForm();
	}

	public function renderForm()
	{
			$fields_form = array(
				'form' => array(
					'legend' => array(
						'title' => $this->l('RiskPay Settings'),
					),
					'input' => array(
						array(
							'type' => 'text',
							'label' => $this->l('USDC Wallet Address'),
							'name' => 'RISKPAY_USDC_WALLET',
							'size' => 60,
							'required' => true,
						),
						array(
							'type' => 'select',
							'label' => $this->l('Provider'),
							'name' => 'RISKPAY_PROVIDER',
							'required' => true,
							'options' => array(
								'query' => array_map(function($provider) {
									return array('id' => $provider, 'name' => $provider);
								}, array(
									'alchemypay', 'banxa', 'bitnovo', 'changenow', 'coinbase', 'finchpay', 'guardarian',
									'interac', 'kado', 'mercuryo', 'moonpay', 'particle', 'rampnetwork', 'revolut',
									'robinhood', 'sardine', 'simpleswap', 'simplex', 'stripe', 'swipelux', 'topper',
									'transak', 'transfi', 'unlimit', 'upi', 'utorg', 'wert'
								)),
								'id' => 'id',
								'name' => 'name',
							),
						),
					),
					'submit' => array(
						'title' => $this->l('Save'),
					),
				),
			);
			$helper = new HelperForm();
			$helper->module = $this;
			$helper->name_controller = $this->name;
			$helper->token = Tools::getAdminTokenLite('AdminModules');
			$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
			$helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
			$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
			$helper->title = $this->displayName;
			$helper->submit_action = 'submitRiskPay';
			$helper->fields_value['RISKPAY_USDC_WALLET'] = Tools::getValue('RISKPAY_USDC_WALLET', Configuration::get('RISKPAY_USDC_WALLET'));
			$helper->fields_value['RISKPAY_PROVIDER'] = Tools::getValue('RISKPAY_PROVIDER', Configuration::get('RISKPAY_PROVIDER'));
			return $helper->generateForm(array($fields_form));
	}

	public function install()
	{
		try {
			/* add pending order state */
			$OrderPending              = new OrderState();
			$OrderPending->name        = array_fill(0, 10, 'AWAITING RISKPAY PAYMENT');
			$OrderPending->send_email  = 0;
			$OrderPending->invoice     = 0;
			$OrderPending->color       = 'RoyalBlue';
			$OrderPending->unremovable = true;
			$OrderPending->logable     = 0;
			$OrderPending->add();
		} catch (\Exception $e) {
			// no need to do anything, the state already exists
		}
        

        Configuration::updateValue('RISKPAY_PENDING', $OrderPending->id);
		return parent::install() && $this->registerHook('payment') && $this->registerHook('paymentReturn') && $this->registerHook('paymentOptions');
	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	// PrestaShop 1.6 payment hook
	public function hookPayment($params)
	{
		if (!$this->active) {
			return;
		}
		$wallet = Configuration::get('RISKPAY_USDC_WALLET');
		$provider = Configuration::get('RISKPAY_PROVIDER');
		if (empty($wallet) || empty($provider)) {
			return;
		}
		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_riskpay' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
	}

	// PrestaShop 1.7+ paymentOptions hook
	public function hookPaymentOptions($params)
	{
		if (!$this->active) {
			return [];
		}
	
		$wallet = Configuration::get('RISKPAY_USDC_WALLET');
		$provider = Configuration::get('RISKPAY_PROVIDER');
		if (empty($wallet) || empty($provider)) {
			return [];
		}
		$payment_options = [];

		$option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
		$option->setCallToActionText($this->l('Pay by Credit Card'));
		//$option->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));
		$option->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true));
		$payment_options[] = $option;
		return $payment_options;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active) {
			return;
		}
		return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
	}
}