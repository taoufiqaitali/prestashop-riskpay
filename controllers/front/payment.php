<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) { exit; }
/**
 *
 * @property Riskpay $module
 */
class RiskpayPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Get order/cart info
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');


        $customer = new Customer($cart->id_customer);
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $currency = new Currency($cart->id_currency);


        $order_state = Configuration::get('RISKPAY_PENDING');
        $this->module->validateOrder($cart->id, (int)$order_state, $total, $this->module->displayName, NULL, [], (int)$currency->id, false, $customer->secure_key);
        $orderId = $this->module->currentOrder;
        $callback_url = $this->context->link->getModuleLink('riskpay', 'validation', ['id_cart' => $cart->id, 'order_id' => $orderId, 'key' => $customer->secure_key], true);
        $usdc_wallet = Configuration::get('RISKPAY_USDC_WALLET');
        $provider = Configuration::get('RISKPAY_PROVIDER');

        // Step 1: Create encrypted wallet
        $wallet_url = 'https://api.riskpay.biz/control/wallet.php?address=' . $usdc_wallet . '&callback=' . urlencode($callback_url);
        PrestaShopLogger::addLog($wallet_url, 1, null, 'Order', $orderId, true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wallet_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $wallet_response = curl_exec($ch);
        curl_close($ch);

        $wallet_data = @json_decode($wallet_response, true);
        if (!$wallet_data || empty($wallet_data['address_in'])) {
            die('RiskPay: Failed to create payment wallet.');
        }
        $encrypted_wallet = $wallet_data['address_in'];

        // Step 2: Redirect to payment page
        $payment_url = 'https://checkout.riskpay.biz/process-payment.php?address=' . $encrypted_wallet . '&provider=' . $provider . '&amount=' .(float)$total . '&' . 
        http_build_query(array(
            'currency' => 'EUR', //'$currency->iso_code',@todo:to remove
            'email' => $customer->email
        ));
        PrestaShopLogger::addLog($payment_url, 1, null, null, null, true);
        Tools::redirect($payment_url);
    }
}
