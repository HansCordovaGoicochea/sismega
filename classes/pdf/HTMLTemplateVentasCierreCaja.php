<?php


class HTMLTemplateVentasCierreCajaCore extends HTMLTemplate
{
    public $operaciones_caja;
    /**
     * @var Context
     */
    public $context;


    public function __construct(PosArqueoscaja $operacionescaja, $smarty, $bulk_mode = false)
    {
//        d($operacionescaja);
        $this->operaciones_caja = $operacionescaja;

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
        $this->context = Context::getContext();
        $id_lang = Context::getContext()->language->id;
        $this->shop = new Shop((int)Context::getContext()->shop->id);
    }

    /**
     * Returns the template's HTML header
     *
     * @return string HTML header
     */
//    public function getHeader()
//    {
//        $this->assignCommonHeaderData();
//        $this->smarty->assign(array('header' => Context::getContext()->getTranslator()->trans('Invoice', array(), 'Shop.Pdf')));
//
//        return $this->smarty->fetch($this->getTemplate('header'));
//    }

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

        $shop = new Shop((int)Context::getContext()->shop->id);
        $country = new Country((int)171);

        if ((int)$this->operaciones_caja->estado == 1){
            $month = date('m');
            $year = date('Y');
            $day = date("d", mktime(23, 59, 59, $month+1, 0, $year));
            $this->operaciones_caja->fecha_cierre = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year));
        }

        $array_con_operaciones_efectivo = Order::getOrdersDateFromDateTOEfectivo($this->context->shop->id, $this->operaciones_caja->fecha_apertura, $this->operaciones_caja->fecha_cierre);
        $array_con_operaciones_visa = Order::getOrdersDateFromDateTOVisa($this->context->shop->id, $this->operaciones_caja->fecha_apertura, $this->operaciones_caja->fecha_cierre);
        $array_con_operaciones_izipay = Order::getOrdersDateFromDateTOIzipay($this->context->shop->id, $this->operaciones_caja->fecha_apertura, $this->operaciones_caja->fecha_cierre);
        $array_con_operaciones_porcobrar = Order::getOrdersDateFromDateTOPorCobrar($this->context->shop->id, $this->operaciones_caja->fecha_apertura, $this->operaciones_caja->fecha_cierre);
        $array_con_operaciones_egresos =PosGastos::getDateFromDateTOEgresos($this->context->shop->id, $this->operaciones_caja->fecha_apertura, $this->operaciones_caja->fecha_cierre);
        $array_con_operaciones_adelantos =ReservarCita::getDateFromDateTOAdelantos($this->context->shop->id, $this->operaciones_caja->fecha_apertura, $this->operaciones_caja->fecha_cierre);

        $empleado_apertura = new Employee((int)$this->operaciones_caja->id_employee_apertura);


        $logo = '';
        $id_shop = (int)$this->shop->id;
        if (Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop);
        } elseif (Configuration::get('PS_LOGO', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop);
        }
//d($logo);
//        d($orders_cierre_caja);
//        d($this->operaciones_caja);
        $data = array(
            'operacion_caja' => $this->operaciones_caja,
            'tienda'=>$shop,
            'empleado' => Context::getContext()->employee,
            'logo'=>$logo,
            'efectivo' => $array_con_operaciones_efectivo,
            'visa' => $array_con_operaciones_visa,
            'izipay' =>$array_con_operaciones_izipay,
            'porcobrar' => $array_con_operaciones_porcobrar,
            'egresos' => $array_con_operaciones_egresos,
            'adelantos' => $array_con_operaciones_adelantos,
            'empleado_apertura'=> $empleado_apertura,
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
        $var_documento = 'VentasCierreCaja';
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
        return 'VentasCierreCaja.pdf';
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

        return sprintf(
                $format,
                Configuration::get('PS_INVOICE_PREFIX', $id_lang, null, $id_shop),
                $this->order_invoice->number,
                date('Y', strtotime($this->order_invoice->date_add))
            ).'.pdf';
    }
}



