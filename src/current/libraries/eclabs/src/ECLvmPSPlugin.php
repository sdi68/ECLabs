<?php
/**
 * @package        Econsult Labs Library
 * @version          __DEPLOYMENT_VERSION__
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2025 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use CurrencyDisplay;
use Exception;

use Joomla\CMS\Log\Log;
use VirtueMartCart;
use VmConfig;
use VmModel;
use vmPSPlugin;
use vmText;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';

try
{
    ECLAutoLoader::registerJoomla3Stub('SubscriberInterface', 'Joomla\Event','interface',JPATH_LIBRARIES . '/vendor/joomla/event/src/');
}
catch (Exception $e)
{
    Log::add($e->getMessage(),Log::ERROR,"ECLvmPSPlugin");
}

if (!class_exists('vmPSPlugin'))
	require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/plugins/vmpsplugin.php';

if (!class_exists('VmConfig')) {
    require(JPATH_ROOT . '/administrator/components/com_virtuemart/helpers/config.php');
    VmConfig::loadConfig();
}

/**
 * @package     ECLabs\Library
 *
 * @since 1.0.15
 */
abstract class ECLvmPSPlugin extends vmPSPlugin implements \Joomla\Event\SubscriberInterface
{
    use Traits\ECLPlugin;

    /**
     * @param $subject
     * @param $config
     * @since 1.0.15
     *
     */
    public function __construct(&$subject, $config = array())
    {
        $this->_setJVersion();
        parent::__construct($subject,$config);
		$id = $config['id'];
		$this->getShipmentParams($id);
    }

	/**
	 * Получает параметры плагина доставки
	 * @param int $id Идентификатор метода доставки
	 *
	 * @since 1.0.15
	 */
	protected final function getShipmentParams($id): void
	{
		$m = VmModel::getModel('shipmentmethod');
		$params = "";
		foreach ($m->getShipments(true) as $method) {
			if($method->shipment_jplugin_id == $id) {
				$params = $method->shipment_params;
				break;
			}
		}
		if($params) {
			foreach (explode("|",$params) as $tmp) {
				if(str_starts_with($tmp, "logging")) {
					$this->enabled_log = (bool)str_replace('"','',explode("=",$tmp)[1]);
				}
			}
		}

	}


    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the shipment-specific data.
     *
     * @param   integer  $virtuemart_order_id           The order ID
     * @param   integer  $virtuemart_shipmentmethod_id  The selected shipment method id
     * @param   string   $shipment_name                 Shipment Name
     *
     * @return mixed Null for shipments that aren't active, text (HTML) otherwise
     * @author Valérie Isaksen
     * @author Max Milbers
     * @since 1.0.15
     */
    public function plgVmOnShowOrderFEShipment(int $virtuemart_order_id, int $virtuemart_shipmentmethod_id, string &$shipment_name): mixed
    {
        return $this->onShowOrderFE($virtuemart_order_id, $virtuemart_shipmentmethod_id, $shipment_name);
    }

	/**
	 * This event is fired after the order has been stored; it gets the shipment method-
	 * specific data.
	 *
	 * @param   VirtueMartCart  $cart   the cart
	 * @param   array           $order  The actual order saved in the DB
	 *
	 * @return mixed Null when this method was not selected, otherwise true
	 * @author Valerie Isaksen
	 * @since  1.0.15
	 */
    public function plgVmConfirmedOrder(VirtueMartCart $cart, array $order):mixed
    {

        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_shipmentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->shipment_element)) {
            return FALSE;
        }
        return $method;
    }

    /**
     * This method is fired when showing the order details in the backend.
     * It displays the shipment-specific data.
     * NOTE, this plugin should NOT be used to display form fields, since it's called outside
     * a form! Use plgVmOnUpdateOrderBE() instead!
     *
     * @param   integer  $virtuemart_order_id           The order ID
     * @param   integer  $virtuemart_shipmentmethod_id  The order shipment method ID
     *
     * @return string|null Null for shipments that aren't active, text (HTML) otherwise
     * @author Valerie Isaksen
     * @since 1.0.0
     */
    public function plgVmOnShowOrderBEShipment(int $virtuemart_order_id, int $virtuemart_shipmentmethod_id)
    {

        if (!($this->selectedThisByMethodId($virtuemart_shipmentmethod_id))) {
            return NULL;
        }

	    return $this->getOrderShipmentHtml($virtuemart_order_id);
    }

    /**
     * @param $virtuemart_order_id
     * @return string
     * @since 1.0.15
     */
    abstract public function getOrderShipmentHtml($virtuemart_order_id):string;



    /**
     * @param $unit
     *
     * @return string
     *
     * @since 1.0.15
     */
    protected function renderPackagingUnit($unit): string
    {
        return vmText::_('COM_VIRTUEMART_UNIT_SYMBOL_' . $unit);
    }

    /**
     * @param $product
     * @param $productDisplayShipments
     *
     * @return false|void
     *
     * @since 1.0.15
     */
    public function plgVmOnProductDisplayShipment($product, &$productDisplayShipments){

        if ($this->getPluginMethods($product->virtuemart_vendor_id) === 0) {

            return FALSE;
        }

        $html = array();

        $currency = CurrencyDisplay::getInstance();

        foreach ($this->methods as $this->_currentMethod) {

            if ($this->_currentMethod->show_on_pdetails) {

                if (!isset($cart)) {
                    $cart = VirtueMartCart::getCart();
                    $cart->products['virtual'] = $product;
                    $cart->_productAdded = true;
                    $cart->prepareCartData();
                }
                if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {

                    $product->prices['shipmentPrice'] = $this->getCosts($cart, $this->_currentMethod, $cart->cartPrices);

                    if (isset($product->prices['VatTax']) and count($product->prices['VatTax']) > 0) {
                        reset($product->prices['VatTax']);
                        $rule = current($product->prices['VatTax']);
                        if (isset($rule[1])) {
                            $product->prices['shipmentTax'] = $product->prices['shipmentPrice'] * $rule[1] / 100.0;
                            $product->prices['shipmentPrice'] = $product->prices['shipmentPrice'] * (1 + $rule[1] / 100.0);
                        }
                    }

                    $html[$this->_currentMethod->virtuemart_shipmentmethod_id] = $this->renderByLayout('default', array("method" => $this->_currentMethod, "cart" => $cart, "product" => $product, "currency" => $currency));
                }
            }
        }
        if (isset($cart)) {
            unset($cart->products['virtual']);
            $cart->_productAdded = true;
            $cart->prepareCartData();
        }


        $productDisplayShipments[] = $html;

    }

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     *
     * @author Valérie Isaksen
     * @since 1.0.15
     */
    public function plgVmOnStoreInstallShipmentPluginTable($jplugin_id)
    {

        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * @param VirtueMartCart $cart
     * @return null
     * @since 1.0.15
     */
    public function plgVmOnSelectCheckShipment(VirtueMartCart $cart)
    {
        return $this->OnSelectCheck($cart);
    }

    /**
     * @param VirtueMartCart $cart
     * @param array $cart_prices
     * @param                $cart_prices_name
     * @return bool|null
     * @since 1.0.15
     */
    public function plgVmOnSelectedCalculatePriceShipment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name): ?bool
    {
         return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    /**
     * plgVmOnCheckAutomaticSelected
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     *
     * @param VirtueMartCart  $cart  cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     * @author Valerie Isaksen
     * @since 1.0.15
     */
    public function plgVmOnCheckAutomaticSelectedShipment(VirtueMartCart $cart, array $cart_prices, &$shipCounter)
    {

        return $this->onCheckAutomaticSelected($cart, $cart_prices, $shipCounter);
    }

    /**
     * @param VirtueMartCart $cart
     *
     * @return false|void|null
     *
     * @since 1.0.15
     */
    public function plgVmOnCheckoutCheckDataShipment(VirtueMartCart $cart)
    {

        if (empty($cart->virtuemart_shipmentmethod_id)) return false;

        $virtuemart_vendor_id = 1; //At the moment one, could make sense to use the cart vendor id
        if ($this->getPluginMethods($virtuemart_vendor_id) === 0) {
            return NULL;
        }

        foreach ($this->methods as $this->_currentMethod) {
            if ($cart->virtuemart_shipmentmethod_id == $this->_currentMethod->virtuemart_shipmentmethod_id) {
                if (!$this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {
                    return false;
                }
                break;
            }
        }
    }

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param            $order_number
	 * @param   integer  $method_id  method used for this order
	 *
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 * @since  1.0.15
	 */
    public function plgVmonShowOrderPrint($order_number, int $method_id): mixed
    {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    /**
     * @param $name
     * @param $id
     * @param $dataOld
     *
     * @return bool
     *
     * @since 1.0.15
     */
    public function plgVmDeclarePluginParamsShipment($name, $id, &$dataOld): bool
    {
        return $this->declarePluginParams('shipment', $name, $id, $dataOld);
    }

    /**
     * @param $data
     * @param $table
     * @return bool
     * @author Max Milbers
     * @since 1.0.15
     */
    public function plgVmSetOnTablePluginShipment(&$data, &$table): bool
    {

        $name = $data['shipment_element'];
        $id = $data['shipment_jplugin_id'];

        if (!empty($this->_psType) and !$this->selectedThis($this->_psType, $name, $id)) {
            return FALSE;
        } else {
            return $this->setOnTablePluginParams($name, $id, $table);
        }
    }



    /**
	 * @inheritDoc
     * @since 1.0.15
	 */
	protected function _addMedia(): void
	{

	}

    /**
     *
     * @return string[]
     *
     * @since 1.0.15
     */
    public static function getSubscribedEvents(): array
    {
		return array();
		//TODO Зарезервировано на будущее

//        return [
//            'plgVmOnShowOrderFEShipment' => 'plgVmOnShowOrderFEShipment4',
//            'plgVmConfirmedOrder' => 'plgVmConfirmedOrder4',
//            'plgVmOnShowOrderBEShipment' => 'plgVmOnShowOrderBEShipment4',
//            'plgVmOnProductDisplayShipment' => 'plgVmOnProductDisplayShipment4',
//            'plgVmOnStoreInstallShipmentPluginTable' => 'plgVmOnStoreInstallShipmentPluginTable4',
//	        'plgVmOnSelectCheckShipment' => 'plgVmOnSelectCheckShipment4',
//	        'plgVmDisplayListFEShipment' => 'plgVmDisplayListFEShipment4',
//	        'plgVmOnSelectedCalculatePriceShipment' => 'plgVmOnSelectedCalculatePriceShipment4',
//	        'plgVmOnCheckAutomaticSelectedShipment' => 'plgVmOnCheckAutomaticSelectedShipment4',
//	        'plgVmOnCheckoutCheckDataShipment' => 'plgVmOnCheckoutCheckDataShipment4',
//	        'plgVmonShowOrderPrint' => 'plgVmonShowOrderPrint4',
//	        'plgVmDeclarePluginParamsShipment' => 'plgVmDeclarePluginParamsShipment4',
//	        'plgVmDeclarePluginParamsShipmentVM3' => 'plgVmDeclarePluginParamsShipmentVM3_4',
//	        'plgVmSetOnTablePluginShipment' => 'plgVmSetOnTablePluginShipment4'
//        ];
    }

//    /**
//     * Обертка для обработки события plgVmOnShowOrderFEShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnShowOrderFEShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnShowOrderFEShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmConfirmedOrder для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmConfirmedOrder4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmConfirmedOrder", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmOnShowOrderBEShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnShowOrderBEShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnShowOrderBEShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmOnProductDisplayShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnProductDisplayShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnProductDisplayShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmOnStoreInstallShipmentPluginTable для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnStoreInstallShipmentPluginTable4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnStoreInstallShipmentPluginTable", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmOnSelectCheckShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnSelectCheckShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnSelectCheckShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmDisplayListFEShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmDisplayListFEShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmDisplayListFEShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmOnSelectedCalculatePriceShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnSelectedCalculatePriceShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnSelectedCalculatePriceShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmOnCheckAutomaticSelectedShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnCheckAutomaticSelectedShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnCheckAutomaticSelectedShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmOnCheckoutCheckDataShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmOnCheckoutCheckDataShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmOnCheckoutCheckDataShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmonShowOrderPrint для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmonShowOrderPrint4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmonShowOrderPrint", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmDeclarePluginParamsShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmDeclarePluginParamsShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmDeclarePluginParamsShipment", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmDeclarePluginParamsShipmentVM3 для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmDeclarePluginParamsShipmentVM3_4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmDeclarePluginParamsShipmentVM3", $event);
//    }
//
//    /**
//     * Обертка для обработки события plgVmSetOnTablePluginShipment для Joomla 4
//     * @param Event $event
//     *
//     * @return Event|true
//     * @since 1.0.15
//     */
//    public function plgVmSetOnTablePluginShipment4(Event $event)
//    {
//        return $this->_runEventHandler("plgVmSetOnTablePluginShipment", $event);
//    }
}