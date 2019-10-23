<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5
 */
class HTMLTemplateFacturaVentaRapidaCore extends HTMLTemplate
{
    public $order;
    public $order_invoice;
    public $available_in_your_account = false;

    /**
     * @param OrderInvoice $order_invoice
     * @param $smarty
     * @throws PrestaShopException
     */
    public function __construct(Order $order_invoice, $smarty, $bulk_mode = false)
    {

        $this->order_invoice = $order_invoice;
        $this->order = new Order((int)$this->order_invoice->id);

        $this->smarty = $smarty;

        // If shop_address is null, then update it with current one.
        // But no DB save required here to avoid massive updates for bulk PDF generation case.
        // (DB: bug fixed in 1.6.1.1 with upgrade SQL script to avoid null shop_address in old orderInvoices)
        if (!isset($this->order_invoice->shop_address) || !$this->order_invoice->shop_address) {
            $this->order_invoice->shop_address = OrderInvoice::getCurrentFormattedShopAddress((int)$this->order->id_shop);
            if (!$bulk_mode) {
                OrderInvoice::fixAllShopAddresses();
            }
        }

        // header informations
        $this->date = Tools::displayDate($order_invoice->date_add);

        $id_lang = Context::getContext()->language->id;
//        $this->title = $order_invoice->getInvoiceNumberFormatted($id_lang);
//d($this->order->id_shop);
        $this->shop = new Shop((int)$this->order->id_shop);


//        d($this->shop);
    }



    /**
     * Compute layout elements size
     *
     * @param $params Array Layout elements
     *
     * @return Array Layout elements columns size
     */
    protected function computeLayout($params)
    {


    }

    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     */
    public function getContent()
    {
        $invoice_address = new Address((int)$this->order->id_address_invoice);
        $shop = new Shop((int)Context::getContext()->shop->id);
        $country = new Country((int)171);
        $customer = new Customer((int)$this->order->id_customer);
        $carrier = new Carrier((int)$this->order->id_carrier);
        $order_details = $this->order_invoice->getProducts();
        $total_taxes = $this->order_invoice->total_paid_tax_incl - $this->order_invoice->total_paid_tax_excl;
        $order = new Order(Tools::getValue('id_ventarapida'));
//        d($order);
        $footer = array(
            'products_before_discounts_tax_excl' => $this->order_invoice->total_products,
            'total_taxes' => $total_taxes,
            'total_paid_tax_excl' => $this->order_invoice->total_paid_tax_excl,
            'total_paid_tax_incl' => $this->order_invoice->total_paid_tax_incl
        );

        foreach ($footer as $key => $value) {
            $footer[$key] = Tools::ps_round($value, _PS_PRICE_COMPUTE_PRECISION_, $this->order->round_mode);
        }

        $customer = new Customer((int)$this->order->id_customer);

        $this->context = Context::getContext();
//        d($address_supplier);
//        d($supplier);
        $id_shop = $this->shop->id;
        $address_shop = new Address();
        $address_shop->company = Configuration::get('PS_SHOP_NAME', null, null, $id_shop);
        $address_shop->address1 = Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop);
        $address_shop->address2 = Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop);
        $address_shop->postcode = Configuration::get('PS_SHOP_CODE', null, null, $id_shop);
        $address_shop->city = Configuration::get('PS_SHOP_CITY', null, null, $id_shop);
        $address_shop->phone = Configuration::get('PS_SHOP_PHONE', null, null, $id_shop);
        $address_shop->id_country = Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop);

        $logo = '';
        $id_shop = (int)$this->shop->id;
        if (Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop);
        } elseif (Configuration::get('PS_LOGO', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop);
        }

        $data = array(
            'PS_SHOP_NAME' => Configuration::get('PS_SHOP_NAME'),
            'PS_SHOP_RAZON_SOCIAL' => Configuration::get('PS_SHOP_RAZON_SOCIAL'),
            'PS_SHOP_RUC' => Configuration::get('PS_SHOP_RUC'),
            'order' => $this->order,
            'order_order' => $order,
            'orders_total_paid_tax_incl' => $order->getOrdersTotalPaid(),
            'order_invoice' => $this->order_invoice,
            'order_details' => $order_details,
            'customer' => $customer,
            'tienda'=>$shop,
            'footer' => $footer,
            'empleado' => Context::getContext()->employee,
            'total_paid' => $order->getTotalPaid(),
            'tipo_dispositivo' => Tools::getValue('tipo_dispositivo'),
            'address_shop' => $address_shop,
            'logo' => $logo,
        );

        if (Tools::getValue('debug')) {
            die(json_encode($data));
        }

        $this->smarty->assign($data);

        return $this->smarty->fetch($this->getTemplateByCountry($country->iso_code));
    }

    /**
     * Returns the tax tab content
     *
     * @return String Tax tab html content
     */


    /**
     * Returns different tax breakdown elements
     *
     * @return Array Different tax breakdown elements
     */

    /**
     * Returns the invoice template associated to the country iso_code
     *
     * @param string $iso_country
     */
    protected function getTemplateByCountry($iso_country)
    {
        $var_documento = 'FacturaVentaRapida';
//                $file = Configuration::get('PS_INVOICE_MODEL');
        //d($file);
        // try to fetch the iso template
        $template = $this->getTemplate($var_documento);

        // else use the default one
        if (!$template)
            $template = $this->getTemplate($var_documento);

        return $template;
    }

    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     */
    public function getBulkFilename()
    {
        return 'FacturaVentaRapida.pdf';
    }

    /**
     * Returns the template filename
     *
     * @return string filename
     */
    public function getFilename()
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int)$this->order->id_shop;
        $format = '%1$s%2$06d';

        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s-%2$06d' : '%1$s%2$06d-%3$s';
        }

        return 'Ticket_numero_'.$this->order->nro_ticket.'.pdf';
    }
}



