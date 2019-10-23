<?php

use PrestaShop\PrestaShop\Core\Product\Search\Pagination;
use Sunat\Sunat;
$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
require $baseDir.'/vendor/getSunat/autoload.php';

//d($baseDir);
require $baseDir.'/vendor/xmlseclibs/xmlseclibs.php';
require $baseDir.'/vendor/xmlseclibs/CustomHeaders.php';
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecEnc;

class AdminVenderControllerCore extends AdminController {

    protected $service_consulta_comprobantes_sunat = "https://www.sunat.gob.pe/ol-it-wsconscpegem/billConsultService?wsdl";
    protected $service_consulta_datos_sunat = "http://e-consultaruc.sunat.gob.pe/cl-ti-itmrconsruc/jcrS00Alias";
   protected $existeCajasAbiertas;
   protected $existeCaja;
   protected $nombre_access;

   public function __construct() {
       $this->bootstrap = true;
       $this->context = Context::getContext();
       $this->existeCajasAbiertas = PosArqueoscaja::existenCajasAbiertas();
       //d($this->context->cookie->admin_caja);
       if($this->context->cookie->admin_caja)
            $this->existeCaja = PosArqueoscaja::existeCaja($this->context->cookie->admin_caja);
       $this->nombre_access = Profile::getProfile(Context::getContext()->employee->id_profile);
       parent::__construct();

   }
   public function display()
    {

//       if ($this->nombre_access['name'] == 'Administrador' || $this->nombre_access['name'] == 'SuperAdmin' || $this->nombre_access['name'] == 'Cajero'){

               if (!$this->existeCajasAbiertas){
                   $this->display='error_caja_sincaja';
               }
               else {
                   $this->display='vender';
                   if (($this->nombre_access['name'] == 'Administrador' || $this->nombre_access['name'] == 'SuperAdmin') && $this->existeCajasAbiertas && $this->existeCaja){
                       $this->context->smarty->assign(array(
                           'cookie_admin_caja' => $this->context->cookie->admin_caja,
                       ));
                   }
               }



//       }else{
//           $this->errors[] = $this->trans('You do not have permission to access this module.', array(), 'Admin.Modules.Notification');
//           return parent::display();
//       }

        $shop_context = (!Shop::isFeatureActive() || Shop::getContext() == Shop::CONTEXT_SHOP);
        if (!$shop_context) {
            $this->errors[] = "Tienes activado el modo multitienda. Debes seleccionar una tienda para poder vender";
            $this->tpl_view_vars = array('shop_context' => $shop_context);
            return parent::display();
        }

       $this->tpl_folder='controllers'.DIRECTORY_SEPARATOR.Tools::toUnderscoreCase(substr($this->controller_name, 5)).'/';


        $exist_cert = 0;
        $certificado = Certificadofe::getByAllShop();
        if (!empty($certificado) && (bool)$certificado['active']){
            $exist_cert = 1;
            $objCerti = new Certificadofe((int)$certificado['id_certificadofe']); // buscar el certificado
            if (!isset($objCerti)){
                $exist_cert = 0;
            }
        }
//        d($numeracion_doc_boleta);

       $colaboradores = Employee::getColaboradores();

       $detect = new Mobile_Detect;
       $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
       $this->context->smarty->assign(array(
           'deviceType' => $deviceType,
           'tpl_folder' => __PS_BASE_URI__ . $this->admin_webpath .'/themes/default/template/'. $this->tpl_folder,
           'perfil_empleado' => $this->nombre_access['name'],
           'existeCajasAbiertas' => $this->existeCajasAbiertas,
           'existeCertificado' => $exist_cert,
           'colaboradores' => $colaboradores,

        ));

       parent::display();
   }


    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/waitMe.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/waitMe.min.js');

//        $this->addJqueryPlugin(array('autocomplete'));
//        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/jwerty.js');

        if (_PS_MODE_DEV_){
            $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/vue.js');
        }else{
            $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/vue.min.js');
        }

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/select2.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/select2.min.js');
    }
    public function ajaxProcessSearchProducts()
    {

        $currency = new Currency((int)$this->context->currency->id);
        if ($products = Product::searchByName((int)$this->context->language->id, pSQL(Tools::getValue('q')))) {
//            foreach ($products as &$product) {
//                // Formatted price
////                $product['formatted_price'] = Tools::displayPrice(Tools::convertPrice($product['price_tax_incl'], $currency), $currency);
//                // Concret price
//                $product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_incl'], $currency), 2);
//                $product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_excl'], $currency), 2);
//            }

            $to_return = array(
                'products' => $products,
                'found' => true
            );
        } else {
            $to_return = array('found' => false);
        }

        $this->content = json_encode($to_return);
    }

    public function ajaxProcessRealizarVenta()
    {
//        d($this->context->cookie->admin_caja);
        if ($this->existeCajasAbiertas){
            $last_caja = PosArqueoscaja::getCajaLast($this->context->shop->id);
        }
        else{
            $this->errors[] = $this->trans('No existe ninguna caja abierta!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }


        //crear ticket y pdf electronico y enviar a SUNAT
        if (Tools::getValue('tipo_comprobante') && Tools::getValue('tipo_comprobante') != ''){
            $tipo_comprobante = Tools::getValue('tipo_comprobante');

            // verificamos el certificado
            $certificado = Certificadofe::getByAllShop();
            if (!empty($certificado) && (bool)$certificado['active']){
                $objCerti = new Certificadofe((int)$certificado['id_certificadofe']); // buscar el certificado
                if (!isset($objCerti)){
                    $this->errors[] = $this->trans('No existe un certificado valido', array(), 'Admin.Orderscustomers.Notification');
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }

            }else{
                //creamos la numeracion
                $numeracion_documento = NumeracionDocumento::getNumTipoDoc($tipo_comprobante);
                if (empty($numeracion_documento)){
                    $this->errors[] = 'Porfavor cree las series y numeración para su tienda gracias. Nombre: <a target="_blank" href="'.$this->context->link->getAdminLink('AdminNumeracionDocumentos').'&addnumeracion_documentos&nombre='.$tipo_comprobante.'">'.$tipo_comprobante.'</a>';
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }
            }

        }

        $tienda_actual = new Shop((int)$this->context->shop->id);
        $nombre_virtual_uri = $tienda_actual->virtual_uri;

        $id_customer = Tools::getValue('id_customer');
        $nombre_legal = Tools::getValue('nombre_legal');
        $numero_doc = Tools::getValue('numero_doc');
        $direccion_cliente = Tools::getValue('direccion_cliente');
        $fecha_nacimiento = Tools::getValue('fecha_nacimiento');
        $celular_cliente = Tools::getValue('celular_cliente');
        $cb_tipo_documento = Tools::getValue('cb_tipo_documento');
        if ($id_customer){
            $customer = new Customer((int)$id_customer);
            if ($direccion_cliente != 'No Definido') $customer->direccion = $direccion_cliente;
            $customer->telefono_celular = $celular_cliente;
            $customer->birthday = $fecha_nacimiento;
            $customer->update();
        }
        else{
            $customer = new Customer();
            $customer->id_shop_group = Context::getContext()->shop->id_shop_group;
            $customer->id_shop = Context::getContext()->shop->id;
            $customer->id_gender = 0;
            $customer->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
            $customer->id_lang = Context::getContext()->language->id;
            $customer->id_risk = 0;
            $customer->firstname = $nombre_legal;
            $customer->lastname = "";
            $pass = $this->get('hashing')->hash("123456789", _COOKIE_KEY_);
            $customer->passwd = $pass;
            $customer->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_FRONT').'minutes'));
            $customer->newsletter = 0;
            $customer->optin = 0;
            $customer->outstanding_allow_amount = 0;
            $customer->show_public_prices = 0;
            $customer->max_payment_days = 0;
//                d(md5(uniqid(rand(), true)));
            $customer->secure_key = md5(uniqid(rand(), true));
            $customer->active = 1;
            $customer->is_guest = 0;
            $customer->deleted = 0;
            $td = strlen(trim($numero_doc)) == 11 ? '6' : '1';
            $tipo_doc = Tipodocumentolegal::getByCodSunat($td);
            $customer->id_document = $cb_tipo_documento;
            $customer->num_document = $numero_doc;
            $customer->telefono_celular = $celular_cliente;
            $customer->birthday = $fecha_nacimiento;
            if ($direccion_cliente != 'No Definido') $customer->direccion = $direccion_cliente;
            $customer->add();
            $customer->updateGroup(array($customer->id_default_group));

            $_POST['id_customer'] = $customer->id;
        }

//        d(Tools::getAllValues());

        /***
         1 sin pago
         2 pagar
            si se selecciono algun tipo de comprobante enviar crear XML y enviar a SUNAT
            imprimir ticket
         3 pagar y refrescar pagina
        ***/
        $tipo_venta = (int)Tools::getValue('tipo_venta');
        $array_pagos = Tools::getValue('array_pagos');

        $pedido = $this->crearCarroPedido();

        if ($pedido['success'] == 'ok'){
            $order = new Order((int)$pedido['order']->id);


            if ($tipo_venta == 1 || $tipo_venta == 2 || $tipo_venta == 3){

                //pagar la orden creada
                $sum_p_array = array_sum(array_column($array_pagos, 'monto'));
                if ($sum_p_array > 0){
                    foreach ($array_pagos as $array_pago) {
                        $amount = str_replace(',', '.', $array_pago['monto']);

                        $vuelto_pago = 0;
                        $ultimopago = 0;
                        foreach ($order->getOrderPaymentCollection() as $payment){
                            $ultimopago += $payment->amount;
                        }

                        if ($amount > $order->total_paid){
                            $vuelto_pago = $amount - $order->total_paid;
                            $amount = $order->total_paid;
                        } else {
                            $ultimopago_final = $order->total_paid - $ultimopago ;
                            if($amount > $ultimopago_final){
                                $vuelto_pago = $amount - $ultimopago_final;
                                $amount = $ultimopago_final;
                            }
                        }

                        $currency = new Currency($order->id_currency);
                        $order_invoice = null;

                        if (!Validate::isLoadedObject($order)) {
                            $this->errors[] = $this->trans('The order cannot be found', array(), 'Admin.Orderscustomers.Notification');
                        } elseif (!Validate::isNegativePrice($amount) || !(float)$amount) {
                            $this->errors[] = $this->trans('The amount is invalid.', array(), 'Admin.Orderscustomers.Notification');
                        } elseif (!Validate::isLoadedObject($currency)) {
                            $this->errors[] = $this->trans('The selected currency is invalid.', array(), 'Admin.Orderscustomers.Notification');
                        } elseif (!Validate::isDate($array_pago['fecha'])) {
                            $this->errors[] = $this->trans('The date is invalid', array(), 'Admin.Orderscustomers.Notification');
                        } else {
                            if (!$order->addOrderPayment($amount, $array_pago['name_pay'], null, $currency, $array_pago['fecha'], $order_invoice, $vuelto_pago, $array_pago['id_metodo_pago'], null, $this->context->employee->id)) {
                                $this->errors[] = $this->trans('An error occurred during payment.', array(), 'Admin.Orderscustomers.Notification');

                            } else {
                                $suma_pagos = 0;

                                foreach ($order->getOrderPaymentCollection() as $payment) {
                                    $suma_pagos += $payment->amount;
                                }

                                if ($suma_pagos >= $order->total_paid_tax_incl){
                                    //pago correcto
                                    $order_state = new OrderState((int)ConfigurationCore::get('PS_OS_PAYMENT'), (int)$this->context->language->id);
                                    $current_order_state = $order->getCurrentOrderState();

                                    if ($current_order_state->id != $order_state->id) {
                                        // Create new OrderHistory
                                        $history = new OrderHistory();
                                        $history->id_order = $order->id;
                                        $history->id_employee = (int)$this->context->employee->id;

                                        $use_existings_payment = false;
                                        if (!$order->hasInvoice()) {
                                            $use_existings_payment = true;
                                        }
                                        $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

                                        // Save all changes
                                        if ($history->addWithemail(true)) {
                                            // synchronizes quantities if needed..
                                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                                foreach ($order->getProducts() as $product) {
                                                    if (StockAvailable::dependsOnStock($product['product_id'])) {
                                                        StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($array_pago['tipo'] == 'efectivo'){
                                    $obj_caja = new PosArqueoscaja((int)$last_caja['id_pos_arqueoscaja']);
                                    $monto_temp = $obj_caja->monto_operaciones;
                                    $obj_caja->monto_operaciones = $monto_temp + $amount;
                                    $obj_caja->update();
                                }

                            }
                        }
                    }
                }

                if ($tipo_venta == 1){
                    //crear orden sin pago
                    $rsp['reload'] = 'ok';
                }
                if ($tipo_venta == 2){
                    //no actualizar la pagina y verificar si selecciono algun comprobante
                    $rsp['reload'] = 'ko';
                }
                elseif($tipo_venta == 3){
                    //actualizar la pagina
                    $rsp['reload'] = 'ok';
                }
            }

            $productos = Tools::getValue('productos');
            foreach ($productos as $product) {
                if ((int)$product['es_servicio'] == 1 && $order->current_state == (int)ConfigurationCore::get("PS_OS_PAYMENT") && $order->id_customer != 1){
                    $objProducto = new Product((int)$product['id']);
                    $customer = new Customer((int)$order->id_customer);
                    $puntos_tmp = (int)$customer->puntos_acumulados;
                    $customer->puntos_acumulados = $puntos_tmp + (int)$objProducto->cantidad_puntos;
                    $customer->update();
                }
            }

            //crear ticket venta
            $this->crearTicketVenta($order);

            $caja = new PosArqueoscaja((int)$last_caja['id_pos_arqueoscaja']);
            $rsp['response'] = 'ok';
            $rsp['msg'] = '¡Venta Ralizada!';
            $rsp['cart'] = $pedido['cart'];
            $rsp['caja_actual'] =  $caja;
            $order_final_actualizado = new Order((int)$order->id);
            $comprobantes = PosOrdercomprobantes::getComprobantesByOrder((int)$order->id);
            $rsp['order'] =  $order_final_actualizado;
            $rsp['errores'] =  $this->errors;
            $rsp['confirmaciones'] =  $this->confirmations;
            $rsp['comprobantes'] =  $comprobantes;
            $rsp['link_venta'] =  $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.$order_final_actualizado->id;

            //        d("dfdfdf");
            $this->ajaxDie(
                json_encode($rsp)
            );
        }
        else{
            $this->ajaxDie(
                json_encode(
                    array(
                        'result' => 'error',
                        'msg' => '¡Error al crear la orden!',
                    )
                )
            );
        }
    }
    protected function crearCarroPedido(){

//       d(Tools::getAllValues());
        $productos = Tools::getValue('productos');

        if(!empty($productos) && count($productos) > 0){
            $cart = new Cart();
            $cart->id_customer = Tools::getValue('id_customer') ? Tools::getValue('id_customer') : 1; // verificar esto del cliente
            $cart->id_address_delivery = (int)  (Address::getFirstCustomerAddressId($cart->id_customer));
            $cart->id_address_invoice = $cart->id_address_delivery;
            $cart->id_lang = (int)($this->context->language->id);
            $cart->id_currency = (int)($this->context->currency->id);
            $cart->id_carrier = 1;
            $cart->recyclable = 0;
            $cart->gift = 0;
            $cart->add();
//            $this->context->cookie->id_cart = (int)($cart->id);
//            $cart->update();

            $id_cart = $cart->id;
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'customization` WHERE `id_cart` = '.(int)$id_cart);
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_cart_rule` WHERE `id_cart` = '.(int)$id_cart);
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_product` WHERE `id_cart` = '.(int)$id_cart);

            foreach($productos as $key=>$product){
                $prod = new Product((int)($product['id']), true, (int)($this->context->language->id));
                $prod_update = $cart->updateQty((float)($product['quantity']), (int)($product['id']),null, false, 'up', 0 , new Shop((int)$cart->id_shop));

                //modificar el precio
                SpecificPrice::deleteByIdCart((int)$id_cart, (int)$product['id'], 0);
                $impuesto_percentaje = (float)( 1 + ($prod->tax_rate / 100));
                if ($impuesto_percentaje > 0){
                    $specific_price = new SpecificPrice();
                    $specific_price->id_cart = (int)$id_cart;
                    $specific_price->id_shop = 0;
                    $specific_price->id_shop_group = 0;
                    $specific_price->id_currency = 0;
                    $specific_price->id_country = 0;
                    $specific_price->id_group = 0;
                    $specific_price->id_customer = (int)$this->context->customer->id;
                    $specific_price->id_product = (int)$product['id'];
                    $specific_price->id_product_attribute = 0;
                    $specific_price->price = round((float)$product['price'] / $impuesto_percentaje, 6);
                    $specific_price->from_quantity = 1;
                    $specific_price->reduction = 0;
                    $specific_price->reduction_type = 'amount';
                    $specific_price->reduction_tax = PS_TAX_EXC;
                    $specific_price->from = '0000-00-00 00:00:00';
                    $specific_price->to = '0000-00-00 00:00:00';
                    $specific_price->add();
                }

            }

            $summary = $cart->getSummaryDetails($this->context->language->id,true);
            $total = (string) $summary['total_price'];
            $cashondelivery = Module::getInstanceByName("ps_checkpayment");


            if($cashondelivery->validateOrder(
                (int)$cart->id,
                Configuration::get('PS_OS_CHEQUE'),
                $total,
                "Venta Rapida",
                null,
                array(),
                $cart->id_currency
            )) {

                $result['orderid'] = (string)$cashondelivery->currentOrder;
                $last_caja = PosArqueoscaja::getCajaLast($this->context->shop->id);
                $order = new Order((int)$result['orderid']);

                if ($total == 0){
                    //pago correcto
                    $order_state = new OrderState((int)ConfigurationCore::get('PS_OS_PAYMENT'), (int)$this->context->language->id);
                    $current_order_state = $order->getCurrentOrderState();

                    if ($current_order_state->id != $order_state->id) {
                        // Create new OrderHistory
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->id_employee = (int)$this->context->employee->id;

                        $use_existings_payment = false;
                        if (!$order->hasInvoice()) {
                            $use_existings_payment = true;
                        }
                        $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

                        // Save all changes
                        if ($history->addWithemail(true)) {
                            // synchronizes quantities if needed..
                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                foreach ($order->getProducts() as $product) {
                                    if (StockAvailable::dependsOnStock($product['product_id'])) {
                                        StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                                    }
                                }
                            }
                        }
                    }

                    if (Tools::getValue('puntos_cliente') && (int)Tools::getValue('puntos_cliente') >= 6){
                        $customer = new Customer((int)$order->id_customer);
                        $puntos_tmp = (int)$customer->puntos_acumulados;
                        $customer->puntos_acumulados = $puntos_tmp - 6;
                        $customer->update();
                    }
                }


                $order->id_pos_caja = $last_caja['id_pos_caja'];
                $order->id_employee = $this->context->employee->id;
                $order->id_colaborador = Tools::getValue('id_colaborador_general');
                $order->colaborador_name = Tools::getValue('colaborador_name_general');
                $order->update();

                $ordeD = OrderDetail::getList($order->id);
                foreach ($ordeD as $k => $val) {
                    foreach(Tools::getValue('productos') as $key=>$product) {
                        $oderDetalle = new OrderDetail((int)$val['id_order_detail']);
                        if ($oderDetalle->product_id === $product['id']){
//                            $oderDetalle->product_name = $product['title'];
                            $oderDetalle->id_colaborador = $product['id_colaborador'];
                            $oderDetalle->colaborador_name = $product['colaborador_name'];
                            $oderDetalle->es_servicio = (int)$product['es_servicio'];
                            $oderDetalle->update();
                        }
                    }
                }

                return array('success' => 'ok', 'order' => $order, 'cart' => $this->context->cart);
            }else{
                return array('success' => 'failed', 'msg' => '¡Error al Ralizadar la venta!');
            }

        }else{
            return array('success' => 'failed', 'msg' => '¡Error al Ralizadar la venta!');
        }
    }

    protected function crearTicketVenta($order){
        $nombre_virtual_uri = $this->context->shop->virtual_uri;
//        $this->confirmations[] = "Entre al ticket venta";

        $correlativo_comanda = NumeracionDocumento::getNumTipoDoc('Ticket');
        if (empty($correlativo_comanda)){
            $objNC = new NumeracionDocumento();
            $objNC->serie = '';
            $objNC->correlativo = 0;
            $objNC->nombre = 'Ticket';
            $objNC->id_shop = Context::getContext()->shop->id;
            $objNC->add();
            $correlativo_comanda = NumeracionDocumento::getNumTipoDoc('Ticket');
        }
        else{
            $correlativo_comanda = NumeracionDocumento::getNumTipoDoc('Ticket');
        }

        if (!$order->nro_ticket){
            $co = new NumeracionDocumento((int)$correlativo_comanda['id_numeracion_documentos']);
            $co->correlativo = ($correlativo_comanda['correlativo']+1);
            $co->update();
            $numero_de_ticket = $correlativo_comanda['correlativo'];
            $monbre_archivo='Ticket_numero_'.($numero_de_ticket+1).'.pdf';
            $order->nro_ticket = ($numero_de_ticket+1);

        }
        else{
            $numero_de_ticket = $order->nro_ticket;
            $monbre_archivo='Ticket_numero_'.($numero_de_ticket).'.pdf';
        }

        $ruta = 'archivos_sunat/'.$nombre_virtual_uri;
        $ruta_documentos = 'documentos_pdf/'.$nombre_virtual_uri;
        if (!file_exists($ruta)) {
            mkdir($ruta, 0777, true);
        }
        if (!file_exists($ruta_documentos)) {
            mkdir($ruta_documentos, 0777, true);
        }

        $order->ruta_ticket_normal = $ruta_documentos.'/'.$monbre_archivo;
        $order->update();

        $pdf_ticket = new PDF($order, ucfirst('FacturaVentaRapida'), Context::getContext()->smarty,'P');
        $pdf_ticket->Guardar($monbre_archivo, "", 'ticket', "");

//        $this->confirmations[] = "Llegue al final";
//        $this->confirmations[] = $order;

    }

    protected function crearComprobanteElectronico($order, $objCerti){
//        $this->confirmations[] = "Todo correcto";

        $tipo_comprobante = Tools::getValue("tipo_comprobante");
        $tienda_actual = $this->context->shop;

        $objComprobantes = new PosOrdercomprobantes();
        $objComprobantes->id_order = $order->id;
        $objComprobantes->tipo_documento_electronico = $tipo_comprobante;
        $objComprobantes->sub_total = $order->total_paid_tax_excl;
        $objComprobantes->impuesto = (float)($order->total_paid_tax_incl - $order->total_paid_tax_excl);
        $objComprobantes->total = $order->total_paid_tax_incl;
        $objComprobantes->fecha_envio_comprobante = date('Y-m-d H:i:s');

        $prods = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'order_detail` od WHERE od.`id_order` = '.(int)$order->id);

        foreach ($prods as $prod) {
            if ((int)$prod['id_tax_rules_group'] == 0){
                $this->errors[] = "La ".Tools::getValue('tipo_comprobante')." no se pudo enviar: HAY PRODUCTOS SIN IGV";
                $this->errors[] = "HAY PRODUCTOS SIN IGV";
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
        }

        if ($order->current_state == (int)ConfigurationCore::get("PS_OS_PAYMENT")){
//            $this->confirmations[] = "Todo correcto 2";

            // comprobanr si ya existe una numeracion para el comprobante
            if (!$objComprobantes->numero_comprobante && $objComprobantes->numero_comprobante == ""){

                //creamos la numeracion
                $numeracion_documento = NumeracionDocumento::getNumTipoDoc($tipo_comprobante);
                if (empty($numeracion_documento)){
                    $sql = 'SELECT * FROM `'._DB_PREFIX_.'numeracion_documentos` WHERE `nombre` = \''.$tipo_comprobante.'\' ORDER BY `id_numeracion_documentos` desc';
                    $ultimo = Db::getInstance()->getRow($sql);

                    $objNu2 = new NumeracionDocumento();
                    $objNu2->correlativo = 1;
                    $objNu2->nombre = $tipo_comprobante;
                    $objNu2->id_shop = $this->context->shop->id;
                    if (!empty($ultimo)) {
                        $numero_old = intval(Tools::entero($ultimo['serie']));
                        $numero_nuevo = $numero_old + 1;

                        $objNu2->serie = substr($tipo_comprobante, 0, 1).Tools::zero_fill($numero_nuevo,3);

                    }
                    else{
                        $objNu2->serie = substr($tipo_comprobante, 0, 1).'001';
                    }
                    $objNu2->add();
                }
                else{
                    $objNu2 = new NumeracionDocumento((int)$numeracion_documento["id_numeracion_documentos"]);
                    $objNu2->correlativo = ($numeracion_documento["correlativo"]+1);
                    $objNu2->update();
                }

                $serie = $objNu2->serie;
                $numeracion = $objNu2->correlativo;
                $numero_comprobante = $serie."-".$numeracion;

                $objComprobantes->numero_comprobante = $numero_comprobante;

            }
            else{
                // hacer que se consulta a la sunat el comprobante
                $numero_comprobante = $objComprobantes->numero_comprobante;
                $array_num = explode("-", $numero_comprobante);
                $serie = $array_num[0];
                $numeracion = $array_num[1];
                $numero_comprobante = $serie."-".$numeracion;
            }
//            d($numero_comprobante);

            // armamos la numeracion
            $tipo_documento = "";
            //d($tipo_comprobante);
            $CLIENTE = new Customer((int)$order->id_customer);
            $nro_documento_cliente = $CLIENTE->num_document; // numero de documento del cliente
            $razon_social_nombre_cliente = $CLIENTE->firstname; // razon_social o nombre del cliente
            $direccion_cliente = $CLIENTE->direccion;

            if ($tipo_comprobante == "Factura"){
                $archivo = PS_SHOP_RUC . "-01-" . $numero_comprobante;  // nombre del archivo  del comprobante
                $tipo_documento = "01"; //cod de comprobante electronico
                $tipo_code_doc_cliente = "6"; // codigo de documento de identidad
            }
            else if ($tipo_comprobante == "Boleta"){
                $archivo = PS_SHOP_RUC . "-03-" . $numero_comprobante; // nombre del archivo  del comprobante
                $tipo_documento = "03"; //cod de comprobante electronico

                $tipo_documento_legal = new Tipodocumentolegal((int)$CLIENTE->id_document);
                //d($tipo_documento_legal);
                if ((int)$order->id_customer !== 1){
                    $tipo_code_doc_cliente = $tipo_documento_legal->cod_sunat; // codigo de documento de identidad
                }else{
                    $tipo_code_doc_cliente = "0"; // codigo de documento de identidad
                }
            }
            else{
                $this->errors[] = $this->trans('Error: Tipo de comprobante no válido!!', array(), 'Admin.Orderscustomers.Notification');
            }

            $monbre_archivo = $objComprobantes->tipo_documento_electronico.'_'.PS_SHOP_RUC.'-'.$tipo_documento.'-'.$objComprobantes->numero_comprobante.'.pdf';

            $tax_amount_total = number_format((float)$order->total_paid_tax_incl - (float)$order->total_paid_tax_excl, 2, '.', '');

            $valor_qr = PS_SHOP_RUC.' | '.strtoupper($objComprobantes->tipo_documento_electronico).' | '.$serie.' | '.$numeracion.' | '.$tax_amount_total.' | '.$order->total_paid_tax_incl.' | '.Tools::getFormatFechaGuardar($objComprobantes->fecha_envio_comprobante).' | '.$tipo_code_doc_cliente.' | '.$nro_documento_cliente.' | ';
            ///////////

            //creamos las RUTAS de los documentos
            // creamos la carpeta donde se guardara el XML
            $ruta_general_xml = "archivos_sunat/".PS_SHOP_RUC."/xml/";
            if (!file_exists($ruta_general_xml)) {
                mkdir($ruta_general_xml, 0777, true);
            }
            $ruta_general_cdr = "archivos_sunat/".PS_SHOP_RUC."/cdr/";
            if (!file_exists($ruta_general_cdr)) {
                mkdir($ruta_general_cdr, 0777, true);
            }

            $ruta_xml = $ruta_general_xml.$archivo;
            $ruta_cdr = $ruta_general_cdr;


            //d($razon_social_nombre_cliente);
            if (trim($tipo_code_doc_cliente) != "" &&
                trim($nro_documento_cliente) != "" &&
                trim($razon_social_nombre_cliente) != ""){
                $receptor = array();
                $receptor['TIPO_DOCUMENTO_CLIENTE'] = $tipo_code_doc_cliente;
                $receptor['NRO_DOCUMENTO_CLIENTE'] = $nro_documento_cliente;
                $receptor['RAZON_SOCIAL_CLIENTE'] = $razon_social_nombre_cliente;
                $receptor['DIRECCION_CLIENTE'] = $direccion_cliente;
            }else{

                $objComprobantes->cod_sunat = 9999;

                $this->errors[] = $this->trans('Error algunos campos del cliente estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
            }

            if (trim(PS_SHOP_RUC) != "" &&
                trim(PS_SHOP_NAME) != "" &&
                trim(PS_SHOP_RAZON_SOCIAL) != "" &&
                trim($objCerti->user_sunat) != "" &&
                trim($objCerti->pass_sunat) != ""){
                $emisor = array();
                $emisor['ruc'] = PS_SHOP_RUC;
                $emisor['tipo_doc'] = "6";
                $emisor['nom_comercial'] = Tools::eliminar_tildes(PS_SHOP_NAME);
                $emisor['razon_social'] = Tools::eliminar_tildes(PS_SHOP_RAZON_SOCIAL);
                $emisor['codigo_ubigeo'] = "060101";
                $emisor['direccion'] = Configuration::get('PS_SHOP_ADDR1', $this->context->language->id, null, $tienda_actual->id,'NO DEFINIDO');
                $emisor['direccion_departamento'] = "CAJAMARCA";
                $emisor['direccion_provincia'] = "CAJAMARCA";
                $emisor['direccion_distrito'] = "CAJAMARCA";
                $emisor['direccion_codigo_pais'] = "PE";
                $emisor['usuario_sol'] = $objCerti->user_sunat;
                $emisor['clave_sol'] = $objCerti->pass_sunat;
//                $emisor['tipo_proceso'] = $tipo_proceso;
            }else{

                $objComprobantes->cod_sunat = 9999;
                $this->errors[] = $this->trans('Error algunos campos del Emisor estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
            }

            if (trim($archivo) != "" &&
                trim($ruta_xml) != "" &&
                trim($ruta_cdr) != "" &&
                trim($objCerti->archivo) != "" &&
                trim($objCerti->clave_certificado) != "" &&
                trim($objCerti->web_service_sunat) != ""){
                $rutas = array();
                $rutas['ruta_comprobantes'] = $archivo;
                $rutas['nombre_archivo'] = $archivo;
                $rutas['ruta_xml'] = $ruta_xml;
                $rutas['ruta_cdr'] = $ruta_cdr;
                $rutas['ruta_firma'] = $objCerti->archivo;
                $rutas['pass_firma'] = $objCerti->clave_certificado;
                $rutas['ruta_ws'] = $objCerti->web_service_sunat;
            }else{
                $objComprobantes->cod_sunat = 9999;
                $this->errors[] = $this->trans('Error algunos campos de las rutas estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
            }
            $objComprobantes->add();

            $ruta = 'documentos_pdf/'.$tienda_actual->virtual_uri;
            $ruta_a4 = 'documentos_pdf_a4/'.$tienda_actual->virtual_uri;
            if (!file_exists($ruta)) {
                mkdir($ruta, 0777, true);
            }
            if (!file_exists($ruta_a4)) {
                mkdir($ruta_a4, 0777, true);
            }


            $pdf_ticket = new PDF($objComprobantes, ucfirst('ComprobanteElectronico'), Context::getContext()->smarty,'P');
            $pdf_ticket->Guardar("Ticket-".$monbre_archivo, $valor_qr, 'ticket', $objComprobantes->hash_cpe);

            $pdf = new PDF($objComprobantes, ucfirst('ComprobanteElectronicopdfa4'), Context::getContext()->smarty,'P');
            $pdf->Guardar("A4-".$monbre_archivo, $valor_qr, 'a4');

            $resp["ruta_ticket"] = $ruta."Ticket-".$monbre_archivo;
            $resp["ruta_pdf_a4"] = $ruta_a4."A4-".$monbre_archivo;
            $resp["numero_comprobante"] = $objComprobantes->numero_comprobante;

            $objComprobantes->ruta_ticket =  $ruta."Ticket-".$monbre_archivo;
            $objComprobantes->ruta_pdf_a4 =  $ruta_a4."A4-".$monbre_archivo;

            $objComprobantes->update();

        }
        else{
            $this->errors[] = $this->trans('La venta no esta pagada!!', array(), 'Admin.Orderscustomers.Notification');
        }
    }

    public function  ajaxProcessEnviarSunat(){


        $order = new Order((int)Tools::getValue('id_order'));
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);

        // verificamos el certificado
        $certificado = Certificadofe::getByAllShop();
        if (!empty($certificado) && (bool)$certificado['active']) {
            $objCerti = new Certificadofe((int)$certificado['id_certificadofe']); // buscar el certificado
        } else {
            $this->errors[] = $this->trans('No existe un certificado valido', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        if (empty($doc)) {
            //crear ticket y pdf electronico
            if (Tools::getValue('tipo_comprobante') && Tools::getValue('tipo_comprobante') != '') {
                $this->crearComprobanteElectronico($order, $objCerti);
            }
        }

        if ($order->current_state == (int)ConfigurationCore::get("PS_OS_PAYMENT")){
//            $this->confirmations[] = "Todo correcto 2";
            $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
            if (!empty($doc)) {
                $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
                $tipo_comprobante = $objComprobantes->tipo_documento_electronico;
                $numero_comprobante = $objComprobantes->numero_comprobante;
                $tienda_actual = $this->context->shop;

                $CLIENTE = new Customer((int)$order->id_customer);
                $nro_documento_cliente = $CLIENTE->num_document; // numero de documento del cliente
                $razon_social_nombre_cliente = $CLIENTE->firstname; // razon_social o nombre del cliente
                $direccion_cliente = $CLIENTE->direccion;

                $tipo_code_doc_cliente = "";
                $tipo_documento = "";
                if ($tipo_comprobante == "Factura"){
                    $archivo = PS_SHOP_RUC . "-01-" . $numero_comprobante;  // nombre del archivo  del comprobante
                    $tipo_documento = "01"; //cod de comprobante electronico
                    $tipo_code_doc_cliente = "6"; // codigo de documento de identidad
                }
                else if ($tipo_comprobante == "Boleta"){
                    $archivo = PS_SHOP_RUC . "-03-" . $numero_comprobante; // nombre del archivo  del comprobante
                    $tipo_documento = "03"; //cod de comprobante electronico

                    $tipo_documento_legal = new Tipodocumentolegal((int)$CLIENTE->id_document);
                    //d($tipo_documento_legal);
                    if ((int)$order->id_customer !== 1){
                        $tipo_code_doc_cliente = $tipo_documento_legal->cod_sunat; // codigo de documento de identidad
                    }else{
                        $tipo_code_doc_cliente = "0"; // codigo de documento de identidad
                    }
                }
                else{
                    $this->errors[] = $this->trans('Error: Tipo de comprobante no válido!!', array(), 'Admin.Orderscustomers.Notification');
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }

                $receptor = array();
                //d($razon_social_nombre_cliente);
                if (trim($tipo_code_doc_cliente) != "" &&
                    trim($nro_documento_cliente) != "" &&
                    trim($razon_social_nombre_cliente) != ""){
                    $receptor['TIPO_DOCUMENTO_CLIENTE'] = $tipo_code_doc_cliente;
                    $receptor['NRO_DOCUMENTO_CLIENTE'] = $nro_documento_cliente;
                    $receptor['RAZON_SOCIAL_CLIENTE'] = $razon_social_nombre_cliente;
                    $receptor['DIRECCION_CLIENTE'] = $direccion_cliente;
                }else{

                    $objComprobantes->cod_sunat = 9999;
                    $this->errors[] = $this->trans('Error algunos campos del cliente estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
                }

                $emisor = array();
                if (trim(PS_SHOP_RUC) != "" &&
                    trim(PS_SHOP_NAME) != "" &&
                    trim(PS_SHOP_RAZON_SOCIAL) != "" &&
                    trim($objCerti->user_sunat) != "" &&
                    trim($objCerti->pass_sunat) != ""){
                    $emisor['ruc'] = PS_SHOP_RUC;
                    $emisor['tipo_doc'] = "6";
                    $emisor['nom_comercial'] = Tools::eliminar_tildes(PS_SHOP_NAME);
                    $emisor['razon_social'] = Tools::eliminar_tildes(PS_SHOP_RAZON_SOCIAL);
                    $emisor['codigo_ubigeo'] = "060101";
                    $emisor['direccion'] = Configuration::get('PS_SHOP_ADDR1', $this->context->language->id, null, $tienda_actual->id,'NO DEFINIDO');
                    $emisor['direccion_departamento'] = "CAJAMARCA";
                    $emisor['direccion_provincia'] = "CAJAMARCA";
                    $emisor['direccion_distrito'] = "CAJAMARCA";
                    $emisor['direccion_codigo_pais'] = "PE";
                    $emisor['usuario_sol'] = $objCerti->user_sunat;
                    $emisor['clave_sol'] = $objCerti->pass_sunat;

                }else{

                    $objComprobantes->cod_sunat = 9999;
                    $this->errors[] = $this->trans('Error algunos campos del Emisor estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
                }
                $objComprobantes->update();

                //creamos las RUTAS de los documentos
                // creamos la carpeta donde se guardara el XML
                $ruta_general_xml = "archivos_sunat/".PS_SHOP_RUC."/xml/";
                if (!file_exists($ruta_general_xml)) {
                    mkdir($ruta_general_xml, 0777, true);
                }
                $ruta_general_cdr = "archivos_sunat/".PS_SHOP_RUC."/cdr/";
                if (!file_exists($ruta_general_cdr)) {
                    mkdir($ruta_general_cdr, 0777, true);
                }

                $ruta_xml = $ruta_general_xml.$archivo;
                $ruta_cdr = $ruta_general_cdr;

                $rutas = array();
                if (trim($objCerti->archivo) != "" &&
                    trim($objCerti->clave_certificado) != "" &&
                    trim($objCerti->web_service_sunat) != ""){
                    $rutas['ruta_comprobantes'] = $archivo;
                    $rutas['nombre_archivo'] = $archivo;
                    $rutas['ruta_xml'] = $ruta_xml;
                    $rutas['ruta_cdr'] = $ruta_cdr;
                    $rutas['ruta_firma'] = $objCerti->archivo;
                    $rutas['pass_firma'] = $objCerti->clave_certificado;
                    $rutas['ruta_ws'] = $objCerti->web_service_sunat;
                }else{
                    $objComprobantes->cod_sunat = 9999;
                    $this->errors[] = $this->trans('Error algunos campos de las rutas estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
                }

                if (!empty($this->errors)) {
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }

                $datos_comprobante = Apisunat_2_1::crear_cabecera($emisor, $order, $objComprobantes, $tipo_documento, $receptor);

                if ($tipo_comprobante == "Factura"){

                    $resp = ProcesarComprobante::procesar_factura($datos_comprobante, $objComprobantes, $rutas);

                    $objComprobantes->hash_cpe =  $resp["hash_cpe"];
                    $objComprobantes->ruta_xml =  $rutas["ruta_xml"].".zip";
                    $objComprobantes->hash_cdr =  $resp["hash_cdr"];
                    $objComprobantes->ruta_cdr =  $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
                    $objComprobantes->cod_sunat =  $resp["cod_sunat"];
                    $objComprobantes->msj_sunat =  $resp["msj_sunat"];
                    $objComprobantes->estado_envio_sunat =  1;
                    $objComprobantes->update();

                    $order_final_actualizado = new Order((int)$order->id);
                    $comprobantes = PosOrdercomprobantes::getComprobantesByOrder((int)$order->id);
                    $resp['order'] =  $order_final_actualizado;
                    $resp['comprobantes'] =  $comprobantes;

                    return die(json_encode($resp));

                }else if ($tipo_comprobante == "Boleta"){

                    $resp = ProcesarComprobante::procesar_boleta($datos_comprobante, $objComprobantes, $rutas);
                    $objComprobantes->hash_cpe =  $resp["hash_cpe"];
                    $objComprobantes->ruta_xml =  $rutas["ruta_xml"].".zip";
                    $objComprobantes->hash_cdr =  $resp["hash_cdr"];
                    $objComprobantes->ruta_cdr =  $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
                    $objComprobantes->cod_sunat =  $resp["cod_sunat"];
                    $objComprobantes->msj_sunat =  $resp["msj_sunat"];
                    $objComprobantes->estado_envio_sunat =  1;
                    $objComprobantes->update();

                    $order_final_actualizado = new Order((int)$order->id);
                    $comprobantes = PosOrdercomprobantes::getComprobantesByOrder((int)$order->id);
                    $resp['order'] =  $order_final_actualizado;
                    $resp['comprobantes'] =  $comprobantes;

                    return die(json_encode($resp));

                }else{
                    return die(json_encode(false));
                }
            }
            else{
                $this->errors[] = $this->trans('No existe una numeración!!', array(), 'Admin.Orderscustomers.Notification');
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
        }
        else{
            $this->errors[] = $this->trans('La venta no esta pagada!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

    }

    protected function check_url($url) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        return $headers['http_code'];
    }

    public function ajaxProcessGetDataSunat(){

//        d(Tools::getAllValues());
        $cliente = new Sunat(false, false);

        $ruc = (isset($_REQUEST["nruc"])) ? $_REQUEST["nruc"] : false;
//	d($ruc);
        $data = $cliente->search($ruc, true);
//        $data_json = json_encode($data);
        $data_json = json_decode($data, true);
//        d($data_json['result']['RazonSocial']);
//        d($data_json['result']['RazonSocial']);
//        d($data_json['result']['Direccion']);
        if ($data_json['tipo_msg'] == 'encontrado'){
            $customer = new Customer();
            $customer->id_shop_group = Context::getContext()->shop->id_shop_group;
            $customer->id_shop = Context::getContext()->shop->id;
            $customer->id_gender = 0;
            $customer->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
            $customer->id_lang = Context::getContext()->language->id;
            $customer->id_risk = 0;
            $customer->firstname = $data_json['result']['RazonSocial'];
            $customer->lastname = "";
            $pass = $this->get('hashing')->hash("123456789", _COOKIE_KEY_);
            $customer->passwd = $pass;
            $customer->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_FRONT').'minutes'));
            $customer->newsletter = 0;
            $customer->optin = 0;
            $customer->outstanding_allow_amount = 0;
            $customer->show_public_prices = 0;
            $customer->max_payment_days = 0;
//                d(md5(uniqid(rand(), true)));
            $customer->secure_key = md5(uniqid(rand(), true));
            $customer->active = 1;
            $customer->is_guest = 0;
            $customer->deleted = 0;
            $td = strlen(trim($ruc)) == 8 ? '1' : '6';
            $tipo_doc = Tipodocumentolegal::getByCodSunat($td);
            $customer->id_document = $tipo_doc['id_tipodocumentolegal'];
            $customer->num_document = $ruc;
            $customer->direccion = $data_json['result']['Direccion'] == '-' ? '': $data_json['result']['Direccion'] ;
            $customer->add();
            $customer->updateGroup(array($customer->id_default_group));

            $data_json['cliente'] = $customer;
            $data_json['cliente']->cod_sunat = $td;
            $data_json['cliente']->id_customer = $customer->id;
        }

        die(json_encode($data_json));
    }

    public function ajaxProcessSearchClientes(){


        if ($clientes = Customer::searchClienteByDocumento(pSQL(Tools::getValue('cliente_search')), Tools::getValue('cb_tipo_documento'))) {

            $reservas = ReservarCita::getCitasByCliente(pSQL(Tools::getValue('cliente_search')));

            $id_order = Order::getOrdersIdByCustomerNow($clientes['id_customer']);
            $order = false;
            if ((int)$id_order > 0){
                $order = new Order((int)$id_order);
            }
            $rtn = array(
                "success" 	=> true,
                "result" 	=> $clientes,
                "reservas" 	=> $reservas,
                "order" 	=> $order,
            );
            die(json_encode($rtn));
        }else{
            $rtn = array(
                "success" 	=> false,
                "msg" 		=> "No hay clientes."
            );
            die(json_encode($rtn));
        }

    }

    public function ajaxProcessElegirCajaVender(){

        $context = Context::getContext();
        $context->cookie->__set("admin_caja",Tools::getValue('id_pos_arqueoscaja'));

    }

    public function ajaxProcessAddProductOnOrder()
    {

        $var_order = Tools::getValue('order');
        $productos = Tools::getValue('productos');

        $order = new Order((int)$var_order['id']);
        //order detail
        $order_detail = OrderDetail::getList($order->id);
//                d($order_detail);
        $childids = array_column($order_detail, 'product_id');

        $crear_cart = false;
        foreach($productos as $key=>$product){
            if(in_array($product['id'], $childids)) {
                //el producto esta en la orden

                $value2 = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'order_detail` WHERE `product_id` = '.(int)$product['id'].' and `id_order` = '.(int)$order->id);
                $cart = new Cart((int)$order->id_cart);
                $productToAdd = new Product((int)($product['id']), true, (int)($this->context->language->id));
                $updateQuantity = $cart->updateQty((int)($product['quantity']), (int)($product['id']),null, false, 'up', 0 , new Shop((int)$cart->id_shop));

                $order_detail2 = new OrderDetail((int)$value2['id_order_detail']);
                $cart_product_quantity = (float)$product['quantity'] + (float)$value2['product_quantity'];

                $product_price_tax_incl = Tools::ps_round($product['price'], 2);
                $product_price_tax_excl = Tools::ps_round(((float)($product['price'])/1.18), 2);
                $total_products_tax_incl = $product_price_tax_incl * $cart_product_quantity;
                $total_products_tax_excl = $product_price_tax_excl * $cart_product_quantity;

                // Calculate differences of price (Before / After)
                $diff_price_tax_incl = $total_products_tax_incl - $order_detail2->total_price_tax_incl;
                $diff_price_tax_excl = $total_products_tax_excl - $order_detail2->total_price_tax_excl;


                if ($diff_price_tax_incl != 0 && $diff_price_tax_excl != 0) {
                    $order_detail2->unit_price_tax_excl = $product_price_tax_excl;
                    $order_detail2->unit_price_tax_incl = $product_price_tax_incl;

                    $order_detail2->total_price_tax_incl += $diff_price_tax_incl;
                    $order_detail2->total_price_tax_excl += $diff_price_tax_excl;

                    // Apply changes on Order
                    $order = new Order($order_detail2->id_order);
                    $order->total_products += $diff_price_tax_excl;
                    $order->total_products_wt += $diff_price_tax_incl;

                    $order->total_paid += $diff_price_tax_incl;
                    $order->total_paid_tax_excl += $diff_price_tax_excl;
                    $order->total_paid_tax_incl += $diff_price_tax_incl;

                    $order->update();
                }

                $old_quantity = $order_detail2->product_quantity;

                $order_detail2->product_quantity = $cart_product_quantity;
                $order_detail2->reduction_percent = 0;

                // update taxes
                $order_detail2->updateTaxAmount($order);

                // Save order detail
                $order_detail2->update();

                // Update product available quantity
                StockAvailable::updateQuantity($order_detail2->product_id, $order_detail2->product_attribute_id, ($old_quantity - $order_detail2->product_quantity), $order->id_shop);

            }
            else{
                // Create new cart
                $cart = new Cart();
                $cart->id_shop_group = $order->id_shop_group;
                $cart->id_shop = $order->id_shop;
                $cart->id_customer = $order->id_customer;
                $cart->id_carrier = $order->id_carrier;
                $cart->id_address_delivery = $order->id_address_delivery;
                $cart->id_address_invoice = $order->id_address_invoice;
                $cart->id_currency = $order->id_currency;
                $cart->id_lang = $order->id_lang;
                $cart->secure_key = $order->secure_key;

                // Save new cart
                $cart->add();

                $productToAdd = new Product((int)($product['id']), true, (int)($this->context->language->id));
                $updateQuantity = $cart->updateQty((int)($product['quantity']), (int)($product['id']),null, false, 'up', 0 , new Shop((int)$cart->id_shop));
                // Create Order detail information
                // Total method
                $total_method = Cart::BOTH_WITHOUT_SHIPPING;
                $use_taxes = true;
                $order_detail = new OrderDetail();
                $order_detail->createList($order, $cart, $order->getCurrentOrderState(), $cart->getProducts(), (isset($order_invoice) ? $order_invoice->id : 0), $use_taxes, 0);

                // update totals amount of order
                $order->total_products += (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $order->total_products_wt += (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);

                $order->total_paid += Tools::ps_round((float)($cart->getOrderTotal(true, $total_method)), 2);
                $order->total_paid_tax_excl += Tools::ps_round((float)($cart->getOrderTotal(false, $total_method)), 2);
                $order->total_paid_tax_incl += Tools::ps_round((float)($cart->getOrderTotal($use_taxes, $total_method)), 2);

                if (isset($order_invoice) && Validate::isLoadedObject($order_invoice)) {
                    $order->total_shipping = $order_invoice->total_shipping_tax_incl;
                    $order->total_shipping_tax_incl = $order_invoice->total_shipping_tax_incl;
                    $order->total_shipping_tax_excl = $order_invoice->total_shipping_tax_excl;
                }
                // discount
                $order->total_discounts += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
                $order->total_discounts_tax_excl += (float)abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS));
                $order->total_discounts_tax_incl += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));

                // Save changes of order
                $order->update();

                StockAvailable::synchronize($product['id']);
                // Update Tax lines
                $order_detail->updateTaxAmount($order);


            }
        }
        unset($product);

        $ordeD = OrderDetail::getList($order->id);
        foreach ($ordeD as $k => $val) {
            foreach($productos as $key=>$product) {
                $oderDetalle = new OrderDetail((int)$val['id_order_detail']);
                if ($oderDetalle->product_id === $product['id']){
//                            $oderDetalle->product_name = $product['title'];
                    $oderDetalle->id_colaborador = $product['id_colaborador'];
                    $oderDetalle->colaborador_name = $product['colaborador_name'];
                    $oderDetalle->es_servicio = (int)$product['es_servicio'];
                    $oderDetalle->update();
                }
            }
        }

        //crear ticket venta
        $this->crearTicketVenta($order);

        die(json_encode(array(
            'success' => "ok",
            'result' => "Guardado",
            'order' => $order
        )));

    }

}
