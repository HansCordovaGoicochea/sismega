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
class HTMLTemplateComprobanteElectronicopdfa4creditoCore extends HTMLTemplate
{
    public $comprobante;
    public $order;
    public $order_invoice;
    public $available_in_your_account = false;

    /**
     * @param OrderInvoice $order_invoice
     * @param $smarty
     * @throws PrestaShopException
     */
    public function __construct(PosOrdercomprobantes $comprobante, $smarty, $bulk_mode = false)
    {

        $this->comprobante = new PosOrdercomprobantes((int)$comprobante->id);
        $this->order = new Order((int)$this->comprobante->id_order);
        $this->order_invoice = $this->order;
        $order_invoice = $this->order;

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

        $invoice_address = new Address((int)$this->order->id_address_invoice);
        $shop = new Shop((int)Context::getContext()->shop->id);
        $country = new Country((int)171);
        $customer = new Customer((int)$this->order->id_customer);
        $carrier = new Carrier((int)$this->order->id_carrier);
        $order_details = $this->order_invoice->getProducts();
        $total_taxes = $this->order_invoice->total_paid_tax_incl - $this->order_invoice->total_paid_tax_excl;
        $order = new Order(Tools::getValue('id_order'));

        $footer = array(
            'products_before_discounts_tax_excl' => $this->order_invoice->total_products,
            'total_taxes' => $total_taxes,
            'total_paid_tax_excl' => $this->order_invoice->total_paid_tax_excl,
            'total_paid_tax_incl' => $this->order_invoice->total_paid_tax_incl
        );

        foreach ($footer as $key => $value) {
            $footer[$key] = Tools::ps_round($value, _PS_PRICE_COMPUTE_PRECISION_, $this->order->round_mode);
        }

        $supplier = '';
        $customer = '';
        $address_supplier = '';

        $customer = new Customer((int)$this->order->id_customer);

//        d($supplier);
        $logo = '';
        $id_shop = (int)$this->shop->id;
        if (Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop);
        } elseif (Configuration::get('PS_LOGO', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop);
        }
//        d($logo);
        $con = ContactCore::getCategoriesContacts();
        $objC = new Contact((int)$con[1]['id_contact']);
//        d($objC);
        $this->context = Context::getContext();


        $address_shop = new Address();
        $address_shop->company = Configuration::get('PS_SHOP_NAME', null, null, $id_shop);
        $address_shop->address1 = Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop);
        $address_shop->address2 = Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop);
        $address_shop->postcode = Configuration::get('PS_SHOP_CODE', null, null, $id_shop);
        $address_shop->city = Configuration::get('PS_SHOP_CITY', null, null, $id_shop);
        $address_shop->phone = Configuration::get('PS_SHOP_PHONE', null, null, $id_shop);
        $address_shop->id_country = Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop);

        $com_relacionado = PosOrdercomprobantes::getComprobantesByOrderAndFB($this->comprobante->id_order);
        $data = array(
            'PS_SHOP_NAME' => Configuration::get('PS_SHOP_NAME'),
            'PS_SHOP_RAZON_SOCIAL' => Configuration::get('PS_SHOP_RAZON_SOCIAL'),
            'PS_SHOP_RUC' => Configuration::get('PS_SHOP_RUC'),
            'address_shop' => $address_shop,
            'order' => $this->order,
            'comprobante_relacionado' => $com_relacionado,
            'comprobante' => $this->comprobante,
            'order_order' => $order,
            'orders_total_paid_tax_incl' => $order->getOrdersTotalPaid(),
            'order_invoice' => $this->order_invoice,
            'order_details' => $order_details,
            'customer' => $customer,
            'supplier' => $supplier,
            'address_supplier' => $address_supplier,
            'tienda' => $shop,
            'footer' => $footer,
            'empleado' => Context::getContext()->employee,
            'total_paid' => $order->getTotalPaid(),
            'logo' => $logo,
            'contacto' => $objC,
        );

        if (Tools::getValue('debug')) {
            die(json_encode($data));
        }

        $this->smarty->assign($data);
//        d($data);
//    d($this->smarty->fetch($this->getTemplateByCountry($country->iso_code)));
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
        $var_documento = 'ComprobanteElectronicopdfa4credito';
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
        return 'ComprobanteElectronicopdfa4credito.pdf';
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
    function numtoletras($xcifra)
    {
        $xarray = array(0 => "Cero",
            1 => "UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE",
            "DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE",
            "VEINTI", 30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA", 70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA",
            100 => "CIENTO", 200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS", 500 => "QUINIENTOS", 600 => "SEISCIENTOS", 700 => "SETECIENTOS", 800 => "OCHOCIENTOS", 900 => "NOVECIENTOS"
        );
//
        $xcifra = trim($xcifra);
        $xlength = strlen($xcifra);
        $xpos_punto = strpos($xcifra, ".");
        $xaux_int = $xcifra;
        $xdecimales = "00";
        if (!($xpos_punto === false)) {
            if ($xpos_punto == 0) {
                $xcifra = "0" . $xcifra;
                $xpos_punto = strpos($xcifra, ".");
            }
            $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
            $xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
        }

        $XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
        $xcadena = "";
        for ($xz = 0; $xz < 3; $xz++) {
            $xaux = substr($XAUX, $xz * 6, 6);
            $xi = 0;
            $xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
            $xexit = true; // bandera para controlar el ciclo del While
            while ($xexit) {
                if ($xi == $xlimite) { // si ya llegó al límite máximo de enteros
                    break; // termina el ciclo
                }

                $x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
                $xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)
                for ($xy = 1; $xy < 4; $xy++) { // ciclo para revisar centenas, decenas y unidades, en ese orden
                    switch ($xy) {
                        case 1: // checa las centenas
                            if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas

                            } else {
                                $key = (int) substr($xaux, 0, 3);
                                if (TRUE === array_key_exists($key, $xarray)){  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
                                    $xseek = $xarray[$key];
                                    $xsub = subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)
                                    if (substr($xaux, 0, 3) == 100)
                                        $xcadena = " " . $xcadena . " CIEN " . $xsub;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                    $xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
                                }
                                else { // entra aquí si la centena no fue numero redondo (101, 253, 120, 980, etc.)
                                    $key = (int) substr($xaux, 0, 1) * 100;
                                    $xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
                                    $xcadena = " " . $xcadena . " " . $xseek;
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 0, 3) < 100)
                            break;
                        case 2: // checa las decenas (con la misma lógica que las centenas)
                            if (substr($xaux, 1, 2) < 10) {

                            } else {
                                $key = (int) substr($xaux, 1, 2);
                                if (TRUE === array_key_exists($key, $xarray)) {
                                    $xseek = $xarray[$key];
                                    $xsub = $this->subfijo($xaux);
                                    if (substr($xaux, 1, 2) == 20)
                                        $xcadena = " " . $xcadena . " VEINTE " . $xsub;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                    $xy = 3;
                                }
                                else {
                                    $key = (int) substr($xaux, 1, 1) * 10;
                                    $xseek = $xarray[$key];
                                    if (20 == substr($xaux, 1, 1) * 10)
                                        $xcadena = " " . $xcadena . " " . $xseek;
                                    else
                                        $xcadena = " " . $xcadena . " " . $xseek . " Y ";
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 1, 2) < 10)
                            break;
                        case 3: // checa las unidades
                            if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada

                            } else {
                                $key = (int) substr($xaux, 2, 1);
                                $xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
                                $xsub = $this->subfijo($xaux);
                                $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                            } // ENDIF (substr($xaux, 2, 1) < 1)
                            break;
                    } // END SWITCH
                } // END FOR
                $xi = $xi + 3;
            } // ENDDO

            if (substr(trim($xcadena), -5, 5) == "ILLON") // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
                $xcadena.= " DE";

            if (substr(trim($xcadena), -7, 7) == "ILLONES") // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
                $xcadena.= " DE";

            // ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
            if (trim($xaux) != "") {
                switch ($xz) {
                    case 0:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena.= "UN BILLON ";
                        else
                            $xcadena.= " BILLONES ";
                        break;
                    case 1:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena.= "UN MILLON ";
                        else
                            $xcadena.= " MILLONES ";
                        break;
                    case 2:
                        if ($xcifra < 1) {
                            $xcadena = "CON CERO $xdecimales/100 ";
                        }
                        if ($xcifra >= 1 && $xcifra < 2) {
                            $xcadena = "CON $xdecimales/100 ";
                        }
                        if ($xcifra >= 2) {
                            $xcadena.= "CON $xdecimales/100 "; //
                        }
                        break;
                } // endswitch ($xz)
            } // ENDIF (trim($xaux) != "")
            // ------------------      en este caso, para México se usa esta leyenda     ----------------
            $xcadena = str_replace("VEINTI ", "VEINTI", $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
            $xcadena = str_replace("UN UN", "UN", $xcadena); // quito la duplicidad
            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
            $xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena); // corrigo la leyenda
            $xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena); // corrigo la leyenda
            $xcadena = str_replace("DE UN", "UN", $xcadena); // corrigo la leyenda
        } // ENDFOR ($xz)
        return trim($xcadena);
    }

// END FUNCTION

    function subfijo($xx)
    { // esta función regresa un subfijo para la cifra
        $xx = trim($xx);
        $xstrlen = strlen($xx);
        if ($xstrlen == 1 || $xstrlen == 2 || $xstrlen == 3)
            $xsub = "";
        //
        if ($xstrlen == 4 || $xstrlen == 5 || $xstrlen == 6)
            $xsub = "MIL";
        //
        return $xsub;
    }
}



