<?php

use PrestaShop\PrestaShop\Core\Stock\StockManager as StockManagerAche;
use PrestaShop\PrestaShop\Adapter\StockManager;


$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
//d($baseDir);
require $baseDir.'/vendor/xmlseclibs/xmlseclibs.php';
require $baseDir.'/vendor/xmlseclibs/CustomHeaders.php';
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecEnc;

/**
 * @property Order $object
 */
class AdminSunatPendienteControllerCore extends AdminController
{
    public $toolbar_title;

    protected $statuses_array = array();
    protected $service_consulta_sunat = "https://www.sunat.gob.pe/ol-it-wsconscpegem/billConsultService?wsdl";
    protected $existeCajasAbiertas;
    protected $existeCaja;
    protected $nombre_access;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order';
        $this->className = 'Order';
        $this->lang = false;
        $this->addRowAction('view');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();
//        $this->addRowAction('pagar_order');

        //botones cuando existe comprobante
//        $this->addRowAction('enviar_mail');
        $this->addRowAction('descargar_xml');
        $this->addRowAction('descargar_pdf');

        ////////////////////////

//        $this->addRowAction('comunicacion_baja');

        parent::__construct();





        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            return $this->errors[] = $this->trans('Tiene que seleccionar una tienda antes.', array(), 'Admin.Orderscustomers.Notification');
        }

        $this->_select = '
        (a.total_paid_tax_incl - a.total_paid_tax_excl) as igv_order2,
        a.total_paid_tax_incl as total_paid_tax_incl,
		a.total_paid_tax_excl as total_paid_tax_excl,
		a.id_customer,

		a.id_currency,
		
		a.id_order AS id_pdf,
		a.id_order as `id_xml`,
        a.id_order as `id_pdf2`,
        a.id_order as `id_pdf2Bol`,
        a.id_order as `id_cdrxml`,
        
        
		c.num_document AS `doc_cliente`,
		CONCAT(c.`firstname`, " (", c.num_document, ") ") AS `customer`,
		osl.`name` AS `osname`,
		os.`color`,
		country_lang.name as cname,
		IF(a.valid, 1, 0) badge_success,
		IF (poc.tipo_documento_electronico != "", poc.tipo_documento_electronico, "Ticket") comprobante,
		IF (poc.numero_comprobante  != "", poc.numero_comprobante, a.nro_ticket) nro_comprobante,
        (a.total_paid_tax_incl - IFNULL((select SUM(op.amount) from `'._DB_PREFIX_.'order_payment` op where op.order_reference = a.reference), 0)) as deuda,
        IFNULL((select SUM(op.amount) from `'._DB_PREFIX_.'order_payment` op where op.order_reference = a.reference), 0) as `pagado`,
        IF (a.`id_employee`, CONCAT_WS(" ",emp.firstname, emp.lastname), "Venta desde la Web") as empleado,
        motivo_anulacion
		';

        $this->_join = '

		LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
		LEFT JOIN `'._DB_PREFIX_.'address` address ON address.id_address = a.id_address_delivery
		LEFT JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
		LEFT JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = '.(int)$this->context->language->id.')
		LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = a.`current_state`)
		LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$this->context->language->id.')
		LEFT JOIN `'._DB_PREFIX_.'pos_ordercomprobantes` poc ON (poc.`id_order` = a.`id_order`)
        LEFT JOIN `'._DB_PREFIX_.'employee` emp ON (emp.`id_employee` = a.`id_employee`)';

        $this->_orderBy = 'a.id_order';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status) {
            if ($status['id_order_state'] == 1 || $status['id_order_state'] == 2 || $status['id_order_state'] == 6 || $status['id_order_state'] == 14 || $status['id_order_state'] == 15 || $status['id_order_state'] == 16)
                $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        $this->fields_list = array(
            'id_order' => array(
                'class' => 'hide',
                'align' => 'hide',
            ),
            'date_add' => array(
                'title' => $this->trans('Fecha de Creación', array(), 'Admin.Global'),
//                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ),
//            'comprobante' => array(
//                'title' => $this->trans('Comprobante', array(), 'Admin.Global'),
//                'havingFilter' => true
//            ),
            'nro_comprobante' => array(
                'title' => $this->trans('N° Comp.', array(), 'Admin.Global'),
                'havingFilter' => true
            ),
            'customer' => array(
                'title' => $this->trans('Customer', array(), 'Admin.Global'),
                'havingFilter' => true,
            ),
//            'doc_cliente' => array(
//                'title' => $this->trans('DNI/RUC', array(), 'Admin.Global'),
//                'havingFilter' => true,
//            ),
//            'empleado' => array(
//                'title' => $this->trans('Empleado', array(), 'Admin.Global'),
//                'havingFilter' => true,
//            ),
        );

        $this->fields_list = array_merge($this->fields_list, array(
            'osname' => array(
                'title' => $this->trans('Status', array(), 'Admin.Global'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname',
                'tooltip' => 'motivo_anulacion'
            ),
        ));

        $this->fields_list = array_merge($this->fields_list, array(
            'total_paid_tax_incl' => array(
                'title' => $this->trans('Total', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency',
                'badge_success' => true,
                'filter_key' => 'a!total_paid_tax_incl'
            ),
            'deuda' => array(
                'title' => $this->trans('Debe', array(), 'Admin.Global'),
                'havingFilter' => true,
                'type' => 'price',
                'search' => false,
            ),
            'pagado' => array(
                'title' => $this->trans('Pagó', array(), 'Admin.Global'),
                'havingFilter' => true,
                'search' => false,
                'type' => 'price',
            ),

//            'date_upd' => array(
//                'title' => $this->trans('Fecha de Modificación', array(), 'Admin.Global'),
//                'align' => 'text-right',
//                'type' => 'datetime',
//                'filter_key' => 'a!date_upd'
//            ),

        ));

        $this->fields_list = array_merge($this->fields_list, array(
//            'id_pdf2' => array(
//                'title' => $this->trans('PDF Fac.', array(), 'Admin.Global'),
//                'align' => 'text-center',
//                'callback' => 'printPDF2Icons',
//                'orderby' => false,
//                'search' => false,
//                'remove_onclick' => true
//            ),
//            'id_xml' => array(
//                'title' => $this->trans('XML', array(), 'Admin.Global'),
//                'align' => 'text-center',
//                'callback' => 'printXMLIcons',
//                'orderby' => false,
//                'search' => false,
//                'remove_onclick' => true
//            ),
//            'id_cdrxml' => array(
//                'title' => $this->trans('CDR', array(), 'Admin.Global'),
//                'align' => 'text-center',
//                'callback' => 'printCDRIcons',
//                'orderby' => false,
//                'search' => false,
//                'remove_onclick' => true
//            )
        ));

        $this->_where = Shop::addSqlRestriction(false, 'a');
        $this->_where = ' AND current_state = 2 AND cod_sunat > 0';
        $this->_group = " GROUP BY a.id_order";

        if (Tools::isSubmit('id_order')) {
            // Save context (in order to apply cart rule)
            $order = new Order((int)Tools::getValue('id_order'));
            $this->context->cart = new Cart($order->id_cart);
            $this->context->customer = new Customer($order->id_customer);
        }

//        $this->bulk_actions = array(
//            'updateOrderStatus' => array('text' => $this->trans('Change Order Status', array(), 'Admin.Orderscustomers.Feature'), 'icon' => 'icon-refresh')
//        );
    }

    public function displayDescargar_xmlLink($token = null, $id)
    {
        //        d($id);
        $factura = new Order((int)$id);

        $btn_icon = '';
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($factura->id);
        if (!empty($doc))
            return $btn_icon = '<a class="edit btn-default pointer descargar_xml" download href="' . $doc['ruta_xml'] . '"><i class="fa fa-file-code-o"></i>&nbsp; Descargar XML</a>';

        return false;
    }

    public function displayDescargar_cdrLink($token = null, $id)
    {
        //        d($id);
        $factura = new Order((int)$id);
        $btn_icon = '';
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($factura->id);

        if (!empty($doc))
            return $btn_icon = '<a class="edit btn-default pointer descargar_xml" download href="' . $doc['ruta_cdr'] . '"><i class="fa fa-file-archive-o"></i>&nbsp; Descargar CDR</a>';

        return false;
    }

    public function displayDescargar_pdfLink($token = null, $id)
    {
        //        d($id);
        $factura = new Order((int)$id);
        $btn_icon = '';
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($factura->id);

        if (!empty($doc))
            return $btn_icon = '<a class="edit btn-default pointer descargar_xml" download href="' . $doc['ruta_pdf_a4'] . '"><i class="fa fa-file-pdf-o"></i>&nbsp; Descargar PDF</a>';

        return false;
    }

    public function displayGenerar_nota_creditoLink($token = null, $id)
    {
        $orden = new Order((int)$id);

        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($orden->id);
        $tipo_comprobante = "";
        $html = false;
        if (!empty($doc)) {
            $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
            $tipo_comprobante = $objComprobantes->tipo_documento_electronico;
            $this->context->smarty->assign(array(
                "estado" => $orden->current_state,
                "id_order" => $orden->id,
                "tipo_comprobante" => $tipo_comprobante,
                "numerocomprobante" => $objComprobantes->numero_comprobante,
                "montototal" => $objComprobantes->total,
            ));

            $html = $this->context->smarty->fetch('controllers/orders/list_action_anularnota_credito.tpl');

            if ($tipo_comprobante != "Factura"){
                return false;
            }
        }
        if ($orden->current_state == 14){
            return false;
        }
        if ($orden->current_state == 15){
            return false;
        }
        if ($orden->current_state == 6){
            return false;
        }
        if (!$this->existeCajasAbiertas){
            return false;
        }
        if (!$this->existeCajasAbiertas){
             return false;
        }

        return $html;
    }
    //fu cion ajax solo dando click en el boton de la lista de ver pdf
    public function ajaxProcessEliminarPedidoNotaCredito()
    {
        //        d(Tools::getAllValues());
        $order = new Order((int)Tools::getValue('id_order'));
        $doc = PosOrdercomprobantes::getFacturaByOrderLimit($order->id);


        if (!empty($doc)){
            $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
            if ($objComprobantes->tipo_documento_electronico == 'Factura') {
                $date1 = new DateTime($objComprobantes->fecha_envio_comprobante);
                $date2 = new DateTime(date('Y-m-d'));
                $diff = $date1->diff($date2);
                $fecha_actual = date("d-m-Y");
                $dias_posteriores = date("d-m-Y",strtotime($fecha_actual."- 13 days"));
//                d($diff);
                if ($diff->d <= 13) {

                    $this->generarNotaCredito($objComprobantes, $order);

                } else {
                    $this->errors[] = "No puede generar una Nota de Crédito a un documento con fecha anterior a ".$dias_posteriores;

                    return die(Tools::jsonEncode(array('respuesta' => 'error', 'msg' =>  $this->errors)));
                }
            }
        }else{
            $this->errors[] = "No existe un comprobante valido para generar la Nota de Crédito";

            return die(Tools::jsonEncode(array('respuesta' => 'error', 'msg' =>  $this->errors)));
        }

        //si solo si esta pagado
        $new_os = new OrderState((int)Configuration::get('PS_OS_CANCELED'), $order->id_lang);
        $old_os = $order->getCurrentOrderState();

        if (Tools::getValue('id_caja') && (int)Tools::getValue('id_caja') > 0){
            $objCaja = new PosArqueoscaja((int)Tools::getValue('id_caja'));
            foreach ($order->getOrderPaymentCollection() as $payment){
                if ((int)$payment->es_cuenta == 1) { // 1 es caja
                    $monto_inicial = $objCaja->monto_operaciones;
                    $objCaja->monto_operaciones = (float)$monto_inicial - (float)$payment->amount;
                    $objCaja->update();
                }
            }

            if (!empty($doc)) {
                $objComprobantes = new PosOrdercomprobantes((int)$doc['id_pos_ordercomprobantes']);
                $objComprobantes->devolver_monto_caja = 1;
                $objComprobantes->update();
            }
        }

        $order->setCurrentState(Configuration::get('PS_OS_CANCELED'), $this->context->employee->id);

        PrestaShopLogger::addLog($this->trans('Venta anulada NOTACREDITO / IP: %ip%', array('%ip%' => Tools::getRemoteAddr()), 'Admin.Advparameters.Feature'), 1, null, 'Order', $order->id, true, (int)$this->context->employee->id);

        if ($old_os->id == 2){
            // @since 1.5.0 : gets the stock manager
            $manager = null;
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $manager = StockManagerFactory::getManager();
            }
//            d($manager);
            $employee = $order->id_employee;

            // foreach products of the order
            foreach ($order->getProductsDetail() as $product) {

                // @since.1.5.0 : if the order was shipped, and is not anymore, we need to restock products

                // if the product is a pack, we restock every products in the pack using the last negative stock mvts
                if (Pack::isPack($product['product_id'])) {
                    $pack_products = Pack::getItems($product['product_id'], Configuration::get('PS_LANG_DEFAULT', null, null, $order->id_shop));
                    foreach ($pack_products as $pack_product) {

                        $mvts = StockMvt::getNegativeStockMvts($order->id, $pack_product->id, 0, $pack_product->pack_quantity * $product['product_quantity']);
                        foreach ($mvts as $mvt) {
                            $manager->addProduct(
                                $pack_product->id,
                                0,
                                new Warehouse($mvt['id_warehouse']),
                                $mvt['physical_quantity'],
                                null,
                                $mvt['price_te'],
                                true,
                                null,
                                $employee
                            );
                        }
                        if (!StockAvailable::dependsOnStock($product['id_product'])) {
                            StockAvailable::updateQuantity($pack_product->id, 0, (float)$pack_product->pack_quantity * $product['product_quantity'], $order->id_shop);
                        }

                    }
                } else {
                    // else, it's not a pack, re-stock using the last negative stock mvts

                    $mvts = StockMvt::getNegativeStockMvts(
                        $order->id,
                        $product['product_id'],
                        $product['product_attribute_id'],
                        ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return'])
                    );

                    foreach ($mvts as $mvt) {
                        $manager->addProduct(
                            $product['product_id'],
                            $product['product_attribute_id'],
                            new Warehouse($mvt['id_warehouse']),
                            $mvt['physical_quantity'],
                            null,
                            $mvt['price_te'],
                            true
                        );
                    }
                }
            }
            // Save movement if :
            // not Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
            // new_os->shipped != old_os->shipped
            if (Validate::isLoadedObject($old_os) && Validate::isLoadedObject($new_os) && $new_os->shipped != $old_os->shipped && !Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $product_quantity = (float) ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return']);

                if ($product_quantity > 0) {
                    (new StockManagerAche)->saveMovement(
                        (int)$product['product_id'],
                        (int)$product['product_attribute_id'],
                        (float)$product_quantity * ($new_os->shipped == 1 ? -1 : 1),
                        array(
                            'id_order' => $order->id,
                            'id_stock_mvt_reason' => ($new_os->shipped == 1 ? Configuration::get('PS_STOCK_CUSTOMER_ORDER_REASON') : Configuration::get('PS_STOCK_CUSTOMER_ORDER_CANCEL_REASON'))
                        )
                    );
                }
            }

        }

        $order->motivo_anulacion = Tools::getValue('motivo_anulacion');
        $order->update();

        $order->setCurrentState(15, $this->context->employee->id); //estado 15 NOTA CREDITO

        return die(Tools::jsonEncode(array('errors' => true, 'estado' => 'disponible')));

    }

    protected function generarNotaCredito($objComprobantes, $order){
        $doc_notacredito= PosOrdercomprobantes::getNotaCreditoByOrderLimit($order->id);
        if (!empty($doc_notacredito)){
            $objComprobanteNotaCredito = new PosOrdercomprobantes($doc_notacredito['id_pos_ordercomprobantes']);
        }else{
            $objComprobanteNotaCredito = new PosOrdercomprobantes();
            $objComprobanteNotaCredito->fecha_envio_comprobante = date('Y-m-d H:i:s');
        }

        $tienda_actual = new Shop((int)$this->context->shop->id); //
        $nombre_virtual_uri = $tienda_actual->virtual_uri;
        $tipo_comprobante = "NotaCredito";
        $arr = Certificadofe::getCertificado();
        if ($arr && (int)$arr > 0){
            $objCerti = new Certificadofe((int)$arr); // buscar el certificado
            if (!(bool)$objCerti->active){
                $this->errors[] = "La Nota de Crédito no se pudo enviar: No hay un certificado valido";
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
        }else{
            $this->errors[] = "La  Nota de Crédito no se pudo enviar: No hay un certificado valido";
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        // comprobanr si ya existe una numeracion para el comprobante
        if (!$objComprobanteNotaCredito->numero_comprobante && $objComprobanteNotaCredito->numero_comprobante == ""){

            $objComprobanteNotaCredito->id_order = $order->id;
            $objComprobanteNotaCredito->tipo_documento_electronico = $tipo_comprobante;
            $objComprobanteNotaCredito->sub_total = $order->total_paid_tax_excl;
            $objComprobanteNotaCredito->impuesto = (float)($order->total_paid_tax_incl - $order->total_paid_tax_excl);
            $objComprobanteNotaCredito->total = $order->total_paid_tax_incl;
            $objComprobanteNotaCredito->code_motivo_nota_credito = Tools::getValue('code_motivo_nota_credito');

            //creamos la numeracion
            $numeracion_documento = NumeracionDocumento::getNumTipoDoc($tipo_comprobante);
            if (empty($numeracion_documento)){
                $this->errors[] = "No existe numeración cree una <a href='index.php?controller=AdminNumeracionDocumentos&addnumeracion_documentos&token=".Tools::getAdminTokenLite("AdminNumeracionDocumentos")."&nombre=".$tipo_comprobante."' target='_blank'>&nbsp; -> Crear Numeración para los Comprobantes Electrónicos</a>";
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
            else{
                $objNu2 = new NumeracionDocumento((int)$numeracion_documento["id_numeracion_documentos"]);
                $objNu2->correlativo = ($numeracion_documento["correlativo"]+1);
                $objNu2->update();
            }

            $serie = $objNu2->serie;
            $numeracion = $objNu2->correlativo;
            $numero_comprobante = $serie."-".$numeracion;

            $objComprobanteNotaCredito->numero_comprobante = $numero_comprobante;

        }
        else{
            // hacer que se consulta a la sunat el comprobante
            $numero_comprobante = $objComprobanteNotaCredito->numero_comprobante;
            $array_num = explode("-", $numero_comprobante);
            $serie = $array_num[0];
            $numeracion = $array_num[1];
            $numero_comprobante = $serie."-".$numeracion;
        }

        $CLIENTE = new Customer((int)$order->id_customer);
        $nro_documento_cliente = $CLIENTE->num_document; // numero de documento del cliente
        $razon_social_nombre_cliente = $CLIENTE->firstname; // razon_social o nombre del cliente
        $direccion_cliente = $CLIENTE->direccion;

        if ($objComprobantes->tipo_documento_electronico == "Factura"){
            $archivo = PS_SHOP_RUC . "-07-" . $numero_comprobante; // nombre del archivo  del comprobante
            $tipo_documento = "07"; //cod de comprobante electronico
            $tipo_code_doc_cliente = "6"; // codigo de documento de identidad
        }
        else{
            $this->errors[] = $this->trans('Error: Tipo de comprobante no válido!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        $monbre_archivo = $objComprobanteNotaCredito->tipo_documento_electronico.'_'.PS_SHOP_RUC.'-'.$tipo_documento.'-'.$objComprobanteNotaCredito->numero_comprobante.'.pdf';

        $tax_amount_total = number_format((float)$order->total_paid_tax_incl - (float)$order->total_paid_tax_excl, 2, '.', '');

        $hoy = getdate();
        $f = $hoy['year'].'-'.$hoy['mon'].'-'.$hoy['mday'];
        $valor_qr = PS_SHOP_RUC.' | NOTA DE CREDITO | '.$serie.' | '.$numeracion.' | '.$tax_amount_total.' | '.$order->total_paid_tax_incl.' | '.$f.' | '.$tipo_code_doc_cliente.' | '.$nro_documento_cliente.' | ';


        //creamos las RUTAS de los documentos
        // creamos la carpeta donde se guardara el XML
        $ruta_general_xml = "archivos_sunat/notacredito/".PS_SHOP_RUC."/xml/";
        if (!file_exists($ruta_general_xml)) {
            mkdir($ruta_general_xml, 0777, true);
        }
        $ruta_general_cdr = "archivos_sunat/notacredito/".PS_SHOP_RUC."/cdr/";
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

            $objComprobanteNotaCredito->cod_sunat = 9999;

            $this->errors[] = $this->trans('Error algunos campos del cliente estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
        }
        else{

            $objComprobanteNotaCredito->cod_sunat = 9999;
            $this->errors[] = $this->trans('Error algunos campos del Emisor estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
        }
        else{
            $objComprobanteNotaCredito->cod_sunat = 9999;
            $this->errors[] = $this->trans('Error algunos campos de las rutas estan vacios!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        if (!empty($doc_notacredito)){
            $objComprobanteNotaCredito->update();
        }else{
            $objComprobanteNotaCredito->add();
        }
        $objComprobanteNotaCredito->tipo_comprobante_modificado = "01";
        $objComprobanteNotaCredito->num_comprobante_modificado = $objComprobantes->numero_comprobante;

        $datos_comprobante = Apisunat_2_1::crear_cabecera($emisor, $order, $objComprobanteNotaCredito, $tipo_documento, $receptor);


        $ruta_a4 = 'documentos_pdf_a4/notas/'.$tienda_actual->virtual_uri;
        if (!file_exists($ruta_a4)) {
            mkdir($ruta_a4, 0777, true);
        }

        $pdf = new PDF($objComprobanteNotaCredito, ucfirst('ComprobanteElectronicopdfa4credito'), Context::getContext()->smarty,'P');
        $pdf->Guardar("A4-".$monbre_archivo, $valor_qr, 'a4');

        $resp["ruta_pdf_a4"] = $ruta_a4."A4-".$monbre_archivo;
        $resp["numero_comprobante"] = $objComprobanteNotaCredito->numero_comprobante;

        $objComprobanteNotaCredito->ruta_pdf_a4 =  $ruta_a4."A4-".$monbre_archivo;
        $objComprobanteNotaCredito->update();

        $resp = ProcesarComprobante::procesar_nota_de_credito($datos_comprobante, $objComprobanteNotaCredito, $rutas);

        if ($resp['result'] == 'error') {
            $objComprobanteNotaCredito->cod_sunat =  $resp["cod_sunat"];
            $objComprobanteNotaCredito->msj_sunat =  $resp["msj_sunat"];
            $objComprobanteNotaCredito->update();
            $this->errors[] = $resp["cod_sunat"].' - '.$resp['msj_sunat'];
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        $objComprobanteNotaCredito->ruta_pdf_a4 =  $ruta_a4."A4-".$monbre_archivo;
        $objComprobanteNotaCredito->hash_cpe =  $resp["hash_cpe"];
        $objComprobanteNotaCredito->ruta_xml =  $rutas["ruta_xml"].".zip";
        $objComprobanteNotaCredito->hash_cdr =  $resp["hash_cdr"];
        $objComprobanteNotaCredito->ruta_cdr =  $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
        $objComprobanteNotaCredito->cod_sunat =  $resp["cod_sunat"];
        $objComprobanteNotaCredito->msj_sunat =  $resp["msj_sunat"];
        $objComprobanteNotaCredito->update();


    }


    public function displayAnular_ventaLink($token = null, $id)
    {
        $orden = new Order((int)$id);

        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($orden->id);
        $tipo_comprobante = "";
        if (!empty($doc)) {
            $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
            $tipo_comprobante = $objComprobantes->tipo_documento_electronico;
            return false;
        }
        if ($orden->current_state == 6){
            return false;
        }
        if (!$this->existeCajasAbiertas){
            return false;
        }
        if (!$this->existeCajasAbiertas){
            return false;
        }
        $this->context->smarty->assign(array(
            "estado" => $orden->current_state,
            "id_order" => $orden->id,
            "tipo_comprobante" => $tipo_comprobante,
        ));
//
        return $this->context->smarty->fetch('controllers/orders/list_action_anular.tpl');
    }


    public function displayAnular_comunicacion_bajaLink($token = null, $id)
    {
        $orden = new Order((int)$id);

        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($orden->id);
        $tipo_comprobante = "";
        $html = false;
        if (!empty($doc)) {
            $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
            $tipo_comprobante = $objComprobantes->tipo_documento_electronico;

            $this->context->smarty->assign(array(
                "estado" => $orden->current_state,
                "id_order" => $orden->id,
                "tipo_comprobante" => $tipo_comprobante,
                "numerocomprobante" => $objComprobantes->numero_comprobante,
                "montototal" => $objComprobantes->total,
            ));

            $html = $this->context->smarty->fetch('controllers/orders/list_action_anular_baja.tpl');
            if ($tipo_comprobante != "Factura" && $tipo_comprobante != "NotaCredito"){
                return false;
            }
        }
        if ($orden->current_state == 14){
            return false;
        }
        if ($orden->current_state == 6){
            return false;
        }
        if (!$this->existeCajasAbiertas){
            return false;
        }
        if (!$this->existeCajasAbiertas){
             return false;
        }

//
        return $html;
    }


    //fu cion ajax solo dando click en el boton de la lista de ver pdf
    public function ajaxProcessEliminarPedido()
    {
        //        d(Tools::getAllValues());
        $order = new Order((int)Tools::getValue('id_order'));
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);

//        No puede anular un documento con fecha anterior a 2019-09-02
        if (!empty($doc)){
            $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);

            if ($objComprobantes->tipo_documento_electronico == 'Factura' || $objComprobantes->tipo_documento_electronico == 'NotaCredito') {
                $date1 = new DateTime($objComprobantes->fecha_envio_comprobante);
                $date2 = new DateTime(date('Y-m-d'));
                $diff = $date1->diff($date2);

                $fecha_actual = date("d-m-Y");
                $dias_posteriores = date("d-m-Y",strtotime($fecha_actual."- 7 days"));

                if ($diff->d <= 7) {

                    $this->declararBajaComprobante($objComprobantes, $order);

                } else {
                    $this->errors[] = "No puede anular un documento con fecha anterior a ".$dias_posteriores;

                    return die(Tools::jsonEncode(array('respuesta' => 'error', 'msg' =>  $this->errors)));
                }
            }
        }

        //si solo si esta pagado
        $new_os = new OrderState((int)Configuration::get('PS_OS_CANCELED'), $order->id_lang);
        $old_os = $order->getCurrentOrderState();

        if (Tools::getValue('id_caja') && (int)Tools::getValue('id_caja') > 0){
            $objCaja = new PosArqueoscaja((int)Tools::getValue('id_caja'));
            foreach ($order->getOrderPaymentCollection() as $payment){
                if ((int)$payment->es_cuenta == 1) { // 1 es caja
                    $monto_inicial = $objCaja->monto_operaciones;
                    $objCaja->monto_operaciones = (float)$monto_inicial - (float)$payment->amount;
                    $objCaja->update();
                }
            }

            if (!empty($doc)) {
                $objComprobantes = new PosOrdercomprobantes((int)$doc['id_pos_ordercomprobantes']);
                $objComprobantes->devolver_monto_caja = 1;
                $objComprobantes->update();
            }
        }

        $order->setCurrentState(Configuration::get('PS_OS_CANCELED'), $this->context->employee->id);

        PrestaShopLogger::addLog($this->trans('Venta anulada / IP: %ip%', array('%ip%' => Tools::getRemoteAddr()), 'Admin.Advparameters.Feature'), 1, null, 'Order', $order->id, true, (int)$this->context->employee->id);

        if ($old_os->id == 2){
            // @since 1.5.0 : gets the stock manager
            $manager = null;
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $manager = StockManagerFactory::getManager();
            }
//            d($manager);
            $employee = $order->id_employee;

            // foreach products of the order
            foreach ($order->getProductsDetail() as $product) {

                // @since.1.5.0 : if the order was shipped, and is not anymore, we need to restock products

                // if the product is a pack, we restock every products in the pack using the last negative stock mvts
                if (Pack::isPack($product['product_id'])) {
                    $pack_products = Pack::getItems($product['product_id'], Configuration::get('PS_LANG_DEFAULT', null, null, $order->id_shop));
                    foreach ($pack_products as $pack_product) {

                        $mvts = StockMvt::getNegativeStockMvts($order->id, $pack_product->id, 0, $pack_product->pack_quantity * $product['product_quantity']);
                        foreach ($mvts as $mvt) {
                            $manager->addProduct(
                                $pack_product->id,
                                0,
                                new Warehouse($mvt['id_warehouse']),
                                $mvt['physical_quantity'],
                                null,
                                $mvt['price_te'],
                                true,
                                null,
                                $employee
                            );
                        }
                        if (!StockAvailable::dependsOnStock($product['id_product'])) {
                            StockAvailable::updateQuantity($pack_product->id, 0, (float)$pack_product->pack_quantity * $product['product_quantity'], $order->id_shop);
                        }

                    }
                } else {
                    // else, it's not a pack, re-stock using the last negative stock mvts

                    $mvts = StockMvt::getNegativeStockMvts(
                        $order->id,
                        $product['product_id'],
                        $product['product_attribute_id'],
                        ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return'])
                    );

                    foreach ($mvts as $mvt) {
                        $manager->addProduct(
                            $product['product_id'],
                            $product['product_attribute_id'],
                            new Warehouse($mvt['id_warehouse']),
                            $mvt['physical_quantity'],
                            null,
                            $mvt['price_te'],
                            true
                        );
                    }
                }
            }
            // Save movement if :
            // not Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
            // new_os->shipped != old_os->shipped
            if (Validate::isLoadedObject($old_os) && Validate::isLoadedObject($new_os) && $new_os->shipped != $old_os->shipped && !Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $product_quantity = (float) ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return']);

                if ($product_quantity > 0) {
                    (new StockManagerAche)->saveMovement(
                        (int)$product['product_id'],
                        (int)$product['product_attribute_id'],
                        (float)$product_quantity * ($new_os->shipped == 1 ? -1 : 1),
                        array(
                            'id_order' => $order->id,
                            'id_stock_mvt_reason' => ($new_os->shipped == 1 ? Configuration::get('PS_STOCK_CUSTOMER_ORDER_REASON') : Configuration::get('PS_STOCK_CUSTOMER_ORDER_CANCEL_REASON'))
                        )
                    );
                }
            }

        }

        $order->motivo_anulacion = Tools::getValue('motivo_anulacion');
        $order->update();


        return die(Tools::jsonEncode(array('errors' => true, 'estado' => 'disponible')));

    }

    protected function declararBajaComprobante($objComprobantes, $order){
        $tienda_actual = new Shop((int)$this->context->shop->id); //
        $nombre_virtual_uri = $tienda_actual->virtual_uri;
        $tipo_comprobante = $objComprobantes->tipo_documento_electronico;
        $arr = Certificadofe::getCertificado();
        if ($arr && (int)$arr > 0){
            $objCerti = new Certificadofe((int)$arr); // buscar el certificado
            if (!(bool)$objCerti->active){
                $this->errors[] = "La ".$tipo_comprobante." no se pudo enviar: No hay un certificado valido";
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
        }else{
            $this->errors[] = "La ".$tipo_comprobante." no se pudo enviar: No hay un certificado valido";
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        if (!$objComprobantes->numeracion_nota_baja && $objComprobantes->numeracion_nota_baja == ""){
            $correlativo_comanda1 = NumeracionDocumento::getNumTipoDoc('ComunicacionBaja');
            if (empty($correlativo_comanda1)){
                $this->errors[] = "No existe numeración cree una <a href='index.php?controller=AdminNumeracionDocumentos&addnumeracion_documentos&token=".Tools::getAdminTokenLite("AdminNumeracionDocumentos")."&nombre=".$objComprobantes->tipo_documento_electronico."' target='_blank'>&nbsp; -> Crear Numeración para los Comprobantes Electrónicos</a>";
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
            else{
                $objNu2 = new NumeracionDocumento((int)$correlativo_comanda1['id_numeracion_documentos']);

                $objNu2->correlativo = ($correlativo_comanda1['correlativo']+1);
                $objNu2->update();

                $serie_baja = $objNu2->serie;
                $numeracion_baja = $objNu2->correlativo;
                $date = date('Ymd');
                $numero_comprobante = $serie_baja.'-'.$date.'-'.$numeracion_baja;
                $objComprobantes->nota_baja = "ComunicacionBaja";
                $objComprobantes->numeracion_nota_baja = $numero_comprobante;
                $objComprobantes->motivo_baja = Tools::getValue('motivo_anulacion');
                $nombre_xml_comprobante = PS_SHOP_RUC.'-'.$numero_comprobante;

            }
        }
        else{
            // hacer que se consulta a la sunat el comprobante
            $numero_comprobante = $objComprobantes->numeracion_nota_baja;
            $nombre_xml_comprobante = PS_SHOP_RUC.'-'.$numero_comprobante;
        }

        $CLIENTE = new Customer((int)$order->id_customer);
        $nro_documento_cliente = $CLIENTE->num_document; // numero de documento del cliente
        $razon_social_nombre_cliente = $CLIENTE->firstname; // razon_social o nombre del cliente
        $direccion_cliente = $CLIENTE->direccion;

        if ($tipo_comprobante == "Factura" || $tipo_comprobante == "NotaCredito"){
            $archivo = $nombre_xml_comprobante;  // nombre del archivo  del comprobante
            $tipo_documento = "Baja"; //cod de comprobante electronico
            $tipo_code_doc_cliente = "6"; // codigo de documento de identidad
        }
        else{
            $this->errors[] = $this->trans('Error: Tipo de comprobante no válido!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        //creamos las RUTAS de los documentos
        // creamos la carpeta donde se guardara el XML
        $ruta_general_xml = "archivos_sunat/baja/".PS_SHOP_RUC."/xml/";
        if (!file_exists($ruta_general_xml)) {
            mkdir($ruta_general_xml, 0777, true);
        }
        $ruta_general_cdr = "archivos_sunat/baja/".PS_SHOP_RUC."/cdr/";
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
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        $objComprobantes->update();


        $datos_comprobante = Apisunat_2_1::crear_cabecera($emisor, $order, $objComprobantes, $tipo_documento, $receptor);

        $resp = ProcesarComprobante::procesar_baja_sunat($datos_comprobante, $objComprobantes, $rutas);

        if ($resp['result'] == 'error') {
            $this->errors[] = $resp["cod_sunat"].' - '.$resp['msj_sunat'];
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

    }


    public function processExport($text_delimiter = '"')
    {


        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
        );


        $this->fields_list = array_merge($this->fields_list, array(
            'numero_comprobante' => array(
                'title' => $this->trans('Comprobante', array(), 'Admin.Global'),
                'align' => 'text-center',
            ),
            'customer' => array(
                'title' => $this->trans('Customer', array(), 'Admin.Global'),
                'havingFilter' => true,
            ),
            'doc_cliente' => array(
                'title' => $this->trans('N° Doc.', array(), 'Admin.Global'),
                'havingFilter' => true,
            ),
        ));

        $this->fields_list = array_merge($this->fields_list, array(

            'total_paid_tax_excl' => array(
                'title' => $this->trans(' SubTotal', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
//                'callback' => 'setOrderCurrency',
//                'badge_success' => true
                'total_suma_excl'=>true,
            ),
            'igv_order2' => array(
                'title' => $this->trans('IGV', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
                'search' => false,
//                'callback' => 'setOrderCurrency',
//                'badge_success' => true
                'total_paid_tax'=>true,
            ),
            'total_paid_tax_incl' => array(
                'title' => $this->trans('Total', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
//                'callback' => 'setOrderCurrency',
                'badge_success' => true,
                'total_suma_incl'=>true,
            ),
//            'payment' => array(
//                'title' => $this->trans('Payment', array(), 'Admin.Global')
//            ),
            'osname' => array(
                'title' => $this->trans('Status', array(), 'Admin.Global'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname'
            ),
        ));

        $this->fields_list = array_merge($this->fields_list, array(
            'date_add' => array(
                'title' => $this->trans('Fecha de Creación', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ),
            'date_upd' => array(
                'title' => $this->trans('Fecha de Modificación', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_upd'
            ),

        ));
        // YOUR EXPORT FIELDS CODE HERE

        parent::processExport($text_delimiter);
    }


    public function displayConsultar_ticketLink($token = null, $id, $name = null)
    {
        $order = new Order((int)$id);
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
        $objComprobante = new PosOrdercomprobantes((int)$doc['id_pos_ordercomprobantes']);
        if (empty($doc)){
            return false;
        }

        if ($objComprobante->nota_baja != "ComunicacionBaja"){
            return false;
        }

        if (!array_key_exists('Consultar_ticket', self::$cache_lang))
            self::$cache_lang['Consultar_ticket'] = $this->l('Consultar CDR', 'Helper');

        $btn_icon = '<a onclick="consultarticketcdr('.$order->id.')" title="Consultar CDR" id="consultar_ticket" class="pointer">
                            <i class="icon-eye"></i> Consultar CDR
                        </a>
                             
                            <script>
	
                                   function consultarticketcdr(id){
                                        $.ajax({
                                            type:"POST",
                                            url: "'.$this->context->link->getAdminLink('AdminOrders').'",
                                            async: true,
                                            dataType: "json",
                                            data : {
                                                ajax: "1",
                                                token: "'.Tools::getAdminTokenLite('AdminOrders').'",
                                                tab: "AdminOrders",
                                                action: "consultarTicket",
                                                id_order: id,
                                            },
                                            success : function(res)
                                            {
                                                if (res.response === "ok"){
                                                    $.each(res.mensaje, function(k, v) {
                                                        $.growl.notice({ title: "", message:v});
                                                    });
                                                }else{
                                                     $.each(res.mensaje, function(k, v) {
                                                         $.growl.error({ title: "", message:v});
                                                    });
                                                }
                                                
                                            },
                                        });
                                    }
                                   
                        </script>
                            ';

        return $btn_icon;

    }


    //funcion ajax solo dando click en el boton de la lista de ver pdf
    public function ajaxProcessConsultarticketcdr()
    {
        /****
        CLASE	             DESCRIPCION	                                                         ACTIVO POR DEFECTO
        ErrorExcepcion	     Errores entre 100 y 1999 inclusive	                                              SI
        ErrorRechazo	     Errores entre 2000 y 3999 inclusive                                              SI
        ErrorObservaciones	 Errores mayores a 4000	                                                          SI
        Error2324	         Error para el error 2324.
        Pone como aceptado un comprobante que ya fue comunicado como baja anteriormente  SI
        Error1033	         Error para el error 1033.
        Permite recuperar el cdr de un comprobante que ya fue enviado anteriormente	  NO
         ****//////
        $order = new Order((int)Tools::getValue('id_order'));
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
        if (!empty($doc)){
            $objComprobantes = new PosOrdercomprobantes((int)$doc['id_pos_ordercomprobantes']);
            $arr = Certificadofe::getCertificado();
            $objCerti = new Certificadofe((int)$arr); // buscar el certificado
            $numero_comprobante = $objComprobantes->numeracion_nota_baja;
            $nombre_xml_comprobante = PS_SHOP_RUC . '-' . $numero_comprobante;
            //creamos las RUTAS de los documentos
            // creamos la carpeta donde se guardara el XML
            $ruta_general_cdr = "archivos_sunat/baja/" . PS_SHOP_RUC . "/cdr/";
            $ruta_cdr = $ruta_general_cdr;

            $resp_cdr = ProcesarComprobante::consultar_envio_ticket($objCerti->user_sunat, $objCerti->pass_sunat, $objComprobantes->identificador_comunicacion, $nombre_xml_comprobante, $ruta_cdr, $objCerti->web_service_sunat);

            if ($resp_cdr['respuesta'] == 'ok') {
                $objComprobantes->mensaje_cdr = $resp_cdr['msj_sunat'];
                $objComprobantes->ruta_cdr_otro = $resp_cdr['ruta_cdr'];
                $objComprobantes->update();

                $this->errors[] = $objComprobantes->mensaje_cdr;
            }

            die(Tools::jsonEncode(array('response' => 'ok', 'mensaje' => $this->errors)));

        }else{
            $this->errors[] = "No existe alguna comprobante creado";
            die(Tools::jsonEncode(array('response' => 'error', 'mensaje' => $this->errors)));
        }
    }

    public function displayConsultar_cdrLink($token = null, $id, $name = null)
    {
        $order = new Order((int)$id);
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
        $objComprobante = new PosOrdercomprobantes((int)$doc['id_pos_ordercomprobantes']);
        if (empty($doc)){
            return false;
        }
        if ($objComprobante->tipo_documento_electronico != "Factura" && $objComprobante->tipo_documento_electronico != "Boleta" && $objComprobante->tipo_documento_electronico != "NotaCredito"){
            return false;
        }
        if ($objComprobante->nota_baja != ""){
            return false;
        }

        if (!array_key_exists('Consultar_cdr', self::$cache_lang))
            self::$cache_lang['Consultar_cdr'] = $this->l('Consultar SUNAT', 'Helper');

        $btn_icon = '<a onclick="consultarCDR('.$order->id.')" title="Consultar SUNAT" id="consultar_cdr" class="pointer">
                            <i class="icon-eye"></i> Consultar SUNAT
                        </a>
                             
                            <script>
	
                                   function consultarCDR(id){
                                        $.ajax({
                                            type:"POST",
                                            url: "'.$this->context->link->getAdminLink('AdminOrders').'",
                                            async: true,
                                            dataType: "json",
                                            data : {
                                                ajax: "1",
                                                token: "'.Tools::getAdminTokenLite('AdminOrders').'",
                                                tab: "AdminOrders",
                                                action: "consultarCDR",
                                                id_order: id,
                                            },
                                            success : function(res)
                                            {
                                                if (res.response === "ok"){
                                                    $.each(res.mensaje, function(k, v) {
                                                        $.growl.notice({ title: "", message:v});
                                                    });
                                                }else{
                                                     $.each(res.mensaje, function(k, v) {
                                                         $.growl.error({ title: "", message:v});
                                                    });
                                                }
                                                
                                            },
                                        });
                                    }
                                   
                        </script>
                            ';

        return $btn_icon;

    }

    //funcion ajax solo dando click en el boton de la lista de ver pdf
    public function ajaxProcessConsultarCDR()
    {
        /****
        CLASE	             DESCRIPCION	                                                         ACTIVO POR DEFECTO
        ErrorExcepcion	     Errores entre 100 y 1999 inclusive	                                              SI
        ErrorRechazo	     Errores entre 2000 y 3999 inclusive                                              SI
        ErrorObservaciones	 Errores mayores a 4000	                                                          SI
        Error2324	         Error para el error 2324.
        Pone como aceptado un comprobante que ya fue comunicado como baja anteriormente  SI
        Error1033	         Error para el error 1033.
        Permite recuperar el cdr de un comprobante que ya fue enviado anteriormente	  NO
         ****//////
        $order = new Order((int)Tools::getValue('id_order'));
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
        if (!empty($doc)){
            $objComprobante = new PosOrdercomprobantes((int)$doc['id_pos_ordercomprobantes']);
            $shop = Context::getContext()->shop;
            $RUC= PS_SHOP_RUC;

            $webservice_consulta = $this->service_consulta_sunat;
            $arr = Certificadofe::getIdCertife(Context::getContext()->shop->id);
            $objCerti = new Certificadofe((int)$arr); // buscar el certificado
            $user= $objCerti->user_sunat;
            $pass= $objCerti->pass_sunat;
//        $user= "20604065896CORPOMED";
//        $pass= "Fac1251ele";

            $headers = new CustomHeaders($user, $pass); // enviar el header de seguridad
            try {
                $client = new SoapClient($webservice_consulta, ['cache_wsdl' => WSDL_CACHE_NONE, 'trace' => TRUE, 'soap_version' => SOAP_1_1]);
                $client->__setSoapHeaders([$headers]);
//        $fcs = $client->__getFunctions(); // mostrar las funciones que tiene el web service
//        $fcs = $client->__getTypes(); // mostrar las funciones que tiene el web service
//d($fcs);

                $tipo_comprobante = "01";
                $numeracion_factura = explode("-", $objComprobante->numero_comprobante);
                $serie = $numeracion_factura[0];
                $numeracion =$numeracion_factura[1];

                if ($objComprobante->tipo_documento_electronico == "NotaCredito"){
                    $tipo_comprobante = "07";
                    $numeracion_nota = explode("-", $objComprobante->numeracion_nota_baja);
                    $numeracion =$numeracion_nota[1];
                }

                if ($objComprobante->tipo_documento_electronico == 'Boleta'){
                    $tipo_comprobante = "03";
                }

                $params = array( 'rucComprobante' => $RUC, 'tipoComprobante' => $tipo_comprobante, 'serieComprobante' => $serie, 'numeroComprobante' => $numeracion);
//        $params = array( 'rucComprobante' => "20604065896", 'tipoComprobante' => "03", 'serieComprobante' => "B001", 'numeroComprobante' => "4111");

                $status = $client->getStatus($params);
            } catch (SoapFault $e) {
                $this->errors[] = $e->faultstring;
                die(Tools::jsonEncode(array('response' => 'ok', 'mensaje' => $this->errors)));
            }

            if ((int)$status->status->statusCode == 1 || (int)$status->status->statusCode == 2 || (int)$status->status->statusCode == 3){
                // El comprobante existe y está aceptado. 1
                // El comprobante existe pero está rechazado. 2
                // El comprobante existe existe pero está de baja. 3

                if($objComprobante->nota_baja == "Baja"){
                    $status = $client->getStatus($params);

                    $arr = Certificadofe::getCertificado();
                    $objCerti = new Certificadofe((int)$arr); // buscar el certificado
                    $numero_comprobante = $objComprobante->numeracion_nota_baja;
                    $nombre_xml_comprobante = PS_SHOP_RUC.'-'.$numero_comprobante;
                    //creamos las RUTAS de los documentos
                    // creamos la carpeta donde se guardara el XML
                    $ruta_general_cdr = "archivos_sunat/baja/".PS_SHOP_RUC."/cdr/";
                    $ruta_cdr = $ruta_general_cdr;

                    $resp_cdr = ProcesarComprobante::consultar_envio_ticket($objCerti->user_sunat, $objCerti->pass_sunat,  $objComprobante->identificador_comunicacion, $nombre_xml_comprobante, $ruta_cdr, $objCerti->web_service_sunat);

                    if ($resp_cdr['respuesta'] == 'ok'){
                        $objComprobante->mensaje_cdr = $resp_cdr['msj_sunat'];
                        $objComprobante->ruta_cdr_otro = $resp_cdr['ruta_cdr'];
                        $objComprobante->update();

                        $this->errors[] = $resp_cdr['msj_sunat'];
                    }

                    $this->errors[] = $status->status->statusMessage;
                    die(Tools::jsonEncode(array('response' => 'ok', 'mensaje' => $this->errors)));
                }

                $statusCDR = $client->getStatusCdr($params);

                if ((int)$statusCDR->statusCdr->statusCode == 4) {
                    $this->errors[] = $statusCDR->statusCdr->statusMessage;

                    $filename_zip = $RUC."-".$tipo_comprobante."-".$serie."-".$numeracion;

                    if ($objComprobante->nota_baja == "NotaCredito"){
                        $url_cdr = 'archivos_sunat/notacredito/'.$RUC.'/R-'.$filename_zip.".zip";
                    }else{
                        $url_cdr = 'archivos_sunat/'.$RUC.'/R-'.$filename_zip.".zip";
                    }
                    if (!file_exists($url_cdr)) {
                        // recibir la respuesta que te da SUNAT
                        $ifp = fopen( $url_cdr, "wb" );
                        fwrite( $ifp, $statusCDR->statusCdr->content );
                        fclose( $ifp );
                    }

                    //leer el ZIP de respuesta aun no esta
                    $zip = zip_open($url_cdr);

                    if($zip)
                    {

                        //la función zip_read sirve para leer el contenido de nuestro archivo ZIP
                        while ($zip_entry = zip_read($zip))
                        {
                            // la función zip_entry_name devuelve el nombre de cada uno de nuestros archivos.
                            if(zip_entry_open($zip, $zip_entry) && 'R-'.$filename_zip.'.xml' == zip_entry_name($zip_entry))
                            {
                                //la función zip_entry_read lee el contenido del fichero
                                $contenido = zip_entry_read($zip_entry,8086);
//                        d($contenido);
                                $response = str_replace(['cbc:', 'ext:', 'cac:', 'ds:', 'sac:', 'ar:'], ['', '', '', '', ''], $contenido);

                                $res = simplexml_load_string($response);

                                $objComprobante->cod_sunat = $res->DocumentResponse->Response->ResponseCode;
                                if ((int)$objComprobante->cod_sunat >= 2000 && (int)$objComprobante->cod_sunat <= 3999){
                                    $order->setCurrentState(16, $this->context->employee->id); //RECHAZADO POR SUNAT
//                                    $this->errors[] = "Cambiar de estado a rechazo por sunat";
                                }
                                $objComprobante->msj_sunat = $res->DocumentResponse->Response->Description;
                                $this->errors[] = $this->trans($res->DocumentResponse->Response->Description, array(), 'Admin.Global');

                            }
                            zip_entry_close('R-'.$filename_zip.".zip");
                        }
                    }
                    zip_close($zip);

                    if ($objComprobante->nota_baja == "NotaCredito"){
                        $url = 'archivos_sunat/notacredito/'.$RUC.'/R-'.$filename_zip.".zip";
                        $url_xml = 'archivos_sunat/notacredito/'.$RUC.'/'.$filename_zip.".zip";
                        $objComprobante->ruta_cdr = $url;
                        $objComprobante->ruta_xml = $url_xml;
                    }else{
                        $url = 'archivos_sunat/'.$RUC.'/R-'.$filename_zip.".zip";
                        $url_xml = 'archivos_sunat/'.$RUC.'/'.$filename_zip.".zip";
                        $objComprobante->ruta_cdr = $url;
                        $objComprobante->ruta_xml = $url_xml;
                    }
                    $objComprobante->update();
                }
                else{
                    $this->errors[] = $statusCDR->statusCdr->statusCode.' - '.$statusCDR->statusCdr->statusMessage;
                    die(Tools::jsonEncode(array('response' => 'error', 'mensaje' => $this->errors)));
                }

                die(Tools::jsonEncode(array('response' => 'ok', 'mensaje' => $this->errors)));

            }
            elseif ((int)$status->status->statusCode == 10){// > Sólo se puede consultar facturas, notas de crédito y debito electrónicas, cuya serie empieza con "F".
                $this->errors[] = $status->status->statusCode.' - '.$status->status->statusMessage;
                die(Tools::jsonEncode(array('response' => 'error', 'mensaje' => $this->errors)));
            }
            elseif ((int)$status->status->statusCode == 11){// > El comprobante de pago electrónico no existe.
                $this->errors[] = $objComprobante->numero_comprobante . ' - ' . $status->status->statusCode.' - '.$status->status->statusMessage;
               if ($objComprobante->tipo_documento_electronico == 'Factura' || $objComprobante->tipo_documento_electronico == 'Boleta'){
                   $inicio = strtotime($objComprobante->fecha_envio_comprobante);
                   $fin = strtotime(date('Y-m-d'));
                   $dif = $fin - $inicio;
                   $diasFalt = (( ( $dif / 60 ) / 60 ) / 24);
                   $dias = ceil($diasFalt);
                   if ($dias <= 7){
                       $this->enviarComprobantes($objComprobante);
                   }else{
                       $this->errors[] = $objComprobante->numero_comprobante . ' -  Ya pasaron más de 7 dias no se puede enviar';
                       die(Tools::jsonEncode(array('response' => 'error', 'mensaje' => $this->errors)));
                   }
               }else{
                   $this->errors[] = 'No es un comprobante valido para reenvio' ;
               }

                die(Tools::jsonEncode(array('response' => 'ok', 'mensaje' => $this->errors)));
            }
            else{
                $this->errors[] = $status->status->statusCode.' - '.$status->status->statusMessage;
                die(Tools::jsonEncode(array('response' => 'error', 'mensaje' => $this->errors)));
            }
        }else{
            $this->errors[] = "No existe alguna comprobante creado";
            die(Tools::jsonEncode(array('response' => 'error', 'mensaje' => $this->errors)));
        }
    }

    protected function enviarComprobantes($objComprobantes){


//        d(Tools::getAllValues());
        $tienda_actual = new Shop((int)$this->context->shop->id); //
        $nombre_virtual_uri = $tienda_actual->virtual_uri;

        $tipo_proceso = "2"; // 1= produccion; 2 = beta

        // verificamos el certificado
        $arr = Certificadofe::getIdCertife(Context::getContext()->shop->id);
        $objCerti = new Certificadofe((int)$arr); // buscar el certificado


        if ($objComprobantes->id_order){
            $order = new Order((int)$objComprobantes->id_order);
            $tipo_comprobante = $objComprobantes->tipo_documento_electronico;
            if ($order->current_state == (int)ConfigurationCore::get("PS_OS_PAYMENT")){

                // comprobanr si ya existe una numeracion para el comprobante
                if (!$objComprobantes->numero_comprobante && $objComprobantes->numero_comprobante == ""){
                    if (empty($numeracion_documento)){
                        $this->errors[] = "No existe numeración cree una <a href='index.php?controller=AdminNumeracionDocumentos&addnumeracion_documentos&token=".Tools::getAdminTokenLite("AdminNumeracionDocumentos")."&nombre=".$tipo_comprobante."' target='_blank'>&nbsp; -> Crear Numeración para los Comprobantes Electrónicos</a>";
                        return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                    }
                    else{
                        $objNu2 = new NumeracionDocumento((int)$numeracion_documento["id_numeracion_documentos"]);
                        $objNu2->correlativo = ($numeracion_documento["correlativo"]+1);
                        $objNu2->update();

                        $serie = $objNu2->serie;
                        $numeracion = $objNu2->correlativo;
                        $numero_comprobante = $serie."-".$numeracion;

                        $objComprobantes->numero_comprobante = $numero_comprobante;
                        $objComprobantes->update();
                    }
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }
                if (!empty($doc)){
                    $objComprobantes->update();
                }else{
                    $objComprobantes->add();
                }

                $datos_comprobante = Apisunat_2_1::crear_cabecera($emisor, $order, $objComprobantes, $tipo_documento, $receptor);
//                d($datos_comprobante);

//                $ruta = _PS_BASE_URL_.__PS_BASE_URI__.'/admincaxasmarket/documentos_pdf/'.$nombre_virtual_uri;
//                $ruta_a4 = _PS_BASE_URL_.__PS_BASE_URI__.'/admincaxasmarket/documentos_pdf_a4/'.$nombre_virtual_uri;
//
//                $pdf_ticket = new PDF($order, ucfirst('ComprobanteElectronico'), Context::getContext()->smarty,'P');
//                $pdf_ticket->Guardar("Ticket-".$monbre_archivo, $valor_qr, 'ticket', $order->hash_cpe);
//
//                $pdf = new PDF($order, ucfirst('ComprobanteElectronicopdfa4'), Context::getContext()->smarty,'P');
//                $pdf->Guardar("A4-".$monbre_archivo, $valor_qr, 'a4');
//
//                $resp["ruta_ticket"] = $ruta."Ticket-".$monbre_archivo;
//                $resp["ruta_pdf_a4"] = $ruta_a4."A4-".$monbre_archivo;
                $resp["numero_comprobante"] = $objComprobantes->numero_comprobante;

                $order_detail = OrderDetail::getList($order->id);
                $resp = Apisunat_2_1::crear_xml_factura_boleta($datos_comprobante, json_decode(json_encode($order_detail)), $rutas["ruta_xml"]);
                if ($resp['respuesta'] == "error"){
                    $this->errors[] = $this->trans('Error al crear el XML '.$objComprobantes->numero_comprobante.'!!', array(), 'Admin.Orderscustomers.Notification');
                }else{
                    $resp_firma = FirmarDocumento::firmar_xml($datos_comprobante, $rutas["ruta_xml"], $rutas["ruta_firma"], $rutas["pass_firma"], $rutas["nombre_archivo"]);
                    if ($resp_firma['respuesta'] == "error"){
                        $this->errors[] = $this->trans('Error al firmar el XML '.$objComprobantes->numero_comprobante.'!!', array(), 'Admin.Orderscustomers.Notification');
                    }else{
                        $resp_envio = ProcesarComprobante::enviar_documento($datos_comprobante['EMISOR_RUC'], $datos_comprobante['EMISOR_USUARIO_SOL'], $datos_comprobante['EMISOR_PASS_SOL'],  $rutas["ruta_xml"], $rutas["ruta_cdr"], $rutas['nombre_archivo'], $rutas['ruta_ws']);
                        if ($resp_envio['respuesta'] == "error"){
                            $this->errors[] = $this->trans($resp_envio["cod_sunat"].' - '.$resp_envio["msj_sunat"].' Error al enviar el COMPROBANTE '.$objComprobantes->numero_comprobante.'!!', array(), 'Admin.Orderscustomers.Notification');
                        }else{
                            $this->confirmations[] = $this->trans($resp_envio["msj_sunat"], array(), 'Admin.Orderscustomers.Notification');
                            $objComprobantes->hash_cpe =  $resp_firma["hash_cpe"];
                            $objComprobantes->ruta_xml =  $rutas["ruta_xml"].".zip";
                            $objComprobantes->hash_cdr =  $resp_envio["hash_cdr"];
                            $objComprobantes->ruta_cdr =  $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
                            $objComprobantes->cod_sunat =  $resp_envio["cod_sunat"];
                            $objComprobantes->msj_sunat =  $resp_envio["msj_sunat"];
                            $objComprobantes->update();
                        }
                    }
                }
            } else{
                $this->errors[] = $this->trans('Error: No tiene un estado válido!!', array(), 'Admin.Orderscustomers.Notification');
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
        }
        //error si no existe la venta
        else{
            $this->errors[] = $this->trans('Error no existe una venta!!', array(), 'Admin.Orderscustomers.Notification');
        }
    }

    public function printPDF2Icons($id)
    {
//        d($id);
        $factura = new Order((int)$id);
        $btn_icon = '';
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($factura->id);

        if (!empty($doc))
            $btn_icon = '<span class="btn-group-action">
                            <span class="btn-group">
                                    <a class="btn btn-default" download href="'.$doc['ruta_pdf_a4'].'">
                                    <i class="icon-file-text"></i>
                                </a>
                                </span>
                        </span>
<script>
	
	       function getRandValue22(id){
                $.ajax({
                    type:"POST",
                    url: "'.$this->context->link->getAdminLink('AdminOrders').'",
                    async: true,
                    dataType: "json",
                    data : {
                        ajax: "1",
                        token: "'.Tools::getAdminTokenLite('AdminOrders').'",
                        tab: "AdminOrders",
                        action: "PDF",
                        id_order: id,
                    },
                    success : function(res)
                    {
				
                    },
                });
	        }
           
</script>
                            ';




        return $btn_icon;
    }

    //fu cion ajax solo dando click en el boton de la lista de ver pdf
    public function ajaxProcessPDF()
    {

        $factura = new Order((int)Tools::getValue('id_order'));
//        d($factura);
        $pdf = new PDF($factura, ucfirst('ComprobanteElectronicopdfa4'), Context::getContext()->smarty,'P');
//                                            $pdf->render();

        if ($factura->tipo_documento_electronico =='Factura'){
            $code = '01';
        }
        if ($factura->tipo_documento_electronico =='Boleta'){
            $code = '03';
        }
        $monbre_archivo = $factura->tipo_documento_electronico.'_'.PS_SHOP_RUC.'-'.$code.'-'.$factura->numero_comprobante.'.pdf';
        $valor_qr = $factura->valor_qr;
//        $pdf->Guardar($monbre_archivo,$valor_qr, 'a4');

        $pdf = new PDF($factura, ucfirst('ComprobanteElectronico'), Context::getContext()->smarty,'P');
//                                            $pdf->render();
        $pdf->Guardar($monbre_archivo,$valor_qr,'ticket', $factura->hash_cpe);

        die(Tools::jsonEncode(array('errors' => true, 'estado' => 'disponible')));

    }

    public function printXMLIcons($id)
    {
//        d($id);
        $factura = new Order((int)$id);
        $btn_icon = '';
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($factura->id);

        if (!empty($doc))
            $btn_icon = '<span class="btn-group-action">
                                <span class="btn-group">
                                        <a class="btn btn-default" download href="'.$doc['ruta_xml'].'">
                                        <i class="icon-file-text"></i>
                                    </a>
                                    
                                    </span>
                            </span>';
        return $btn_icon;
    }

    public function printCDRIcons($id)
    {
//        d($id);
        $factura = new Order((int)$id);
        $btn_icon = '';
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($factura->id);

        if (!empty($doc))
            $btn_icon = '<span class="btn-group-action">
                            <span class="btn-group">
                                    <a class="btn btn-default" download href="'.$doc['ruta_cdr'].'">
                                    <i class="icon-file-text"></i>
                                </a>
                                
                                </span>
                        </span>';
        return $btn_icon;
    }

    public function displayPagar_orderLink($token = null, $id, $name = null)
    {

        $order = new Order((int)$id);
        if ($order->current_state != 1 && $order->current_state != 12){
            return false;
        }

        if (!$this->existeCajasAbiertas){
            return false;
        }

        return '<a  href="#cajas_pago" class="cajas_pago btn-default" title="Pagar" data-idorder="'.$id.'"><i class="icon-money"></i> Pago Directo</a>';

    }

    //fu cion ajax solo dando click se paga la orden
    public function ajaxProcessPaymentOrderAche()
    {

//        d(Tools::getAllValues());
        $order = new Order((int)Tools::getValue('id_order'));
        $amount = $order->total_paid_tax_incl - $order->total_paid_real;
        $currency = new Currency((int)$order->id_currency);

        $order_has_invoice = $order->hasInvoice();
        if ($order_has_invoice) {
            $order_invoice = new OrderInvoice(Tools::getValue('payment_invoice'));
        } else {
            $order_invoice = null;
        }

        $last_caja = PosArqueoscaja::getCajaLast($this->context->shop->id);

        if (!$order->addOrderPayment($amount, "Pago en Efectivo", "", $currency, date('Y-m-d H:i:s'), $order_invoice, 0, 1, $last_caja['id_pos_arqueoscaja'], $this->context->employee->id)) {
            $this->errors[] = $this->trans('An error occurred during payment.', array(), 'Admin.Orderscustomers.Notification');
        } else {
            // actualizar la caja o la cuenta

            $objcajanew = new PosArqueoscaja((int)$last_caja['id_pos_arqueoscaja']);
            $montoinicial = $objcajanew->monto_operaciones;
            $montofinal = $montoinicial + $amount;
            $objcajanew->monto_operaciones = $montofinal;
            $objcajanew->update();

            $order->id_pos_caja = $last_caja['id_pos_caja'];///////////////////

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

            $order->update();
        }

        return die(Tools::jsonEncode(array('errors' => true, 'estado' => 'disponible', 'msg' => 'Pagado')));

    }

    public static function setOrderCurrency($echo, $tr)
    {
        $order = new Order($tr['id_order']);
        return Tools::displayPrice($echo, (int)$order->id_currency);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if ($this->display == 'view') {
            $this->page_header_toolbar_btn['back_to_list'] = array(
                'href' => Context::getContext()->link->getAdminLink('AdminOrders'),
                'desc' => $this->l('Back to list', null, null, false),
                'icon' => 'process-icon-back'
            );
        }

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_order'] = array(
                'href' => self::$currentIndex.'&addorder&token='.$this->token,
                'desc' => $this->trans('Nueva Venta', array(), 'Admin.Orderscustomers.Feature'),
                'icon' => 'process-icon-new'
            );
        }

        if ($this->display == 'add') {
            unset($this->page_header_toolbar_btn['save']);
        }

        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && isset($this->page_header_toolbar_btn['new_order'])
            && Shop::isFeatureActive()) {
            unset($this->page_header_toolbar_btn['new_order']);
        }

        $id_order = (int)Tools::getValue('id_order');
        $order = new Order((int)$id_order);
        $this->context->smarty->assign(array(
            'order' => $order,
        ));
    }

    public function renderForm()
    {
        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->errors[] = $this->trans('You have to select a shop before creating new orders.', array(), 'Admin.Orderscustomers.Notification');
        }

        if ($this->display == 'edit'){

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders').'&id_order='.Tools::getValue('id_order').'&vieworder');
        }
        if ($this->display == 'add'){
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminVender'));
        }

        $id_cart = (int)Tools::getValue('id_cart');
        $cart = new Cart((int)$id_cart);
        if ($id_cart && !Validate::isLoadedObject($cart)) {
            $this->errors[] = $this->trans('This cart does not exists', array(), 'Admin.Orderscustomers.Notification');
        }
        if ($id_cart && Validate::isLoadedObject($cart) && !$cart->id_customer) {
            $this->errors[] = $this->trans('The cart must have a customer', array(), 'Admin.Orderscustomers.Notification');
        }
        if (count($this->errors)) {
            return false;
        }

        parent::renderForm();
        unset($this->toolbar_btn['save']);
        $this->addJqueryPlugin(array('autocomplete', 'fancybox', 'typewatch', 'highlight'));

        $defaults_order_state = array('cheque' => (int)Configuration::get('PS_OS_CHEQUE'),
            'bankwire' => (int)Configuration::get('PS_OS_BANKWIRE'),
            'cashondelivery' => Configuration::get('PS_OS_COD_VALIDATION') ? (int)Configuration::get('PS_OS_COD_VALIDATION') : (int)Configuration::get('PS_OS_PREPARATION'),
            'other' => (int)Configuration::get('PS_OS_PAYMENT'));
        $payment_modules = array();
        foreach (PaymentModule::getInstalledPaymentModules() as $p_module) {
            $payment_modules[] = Module::getInstanceById((int)$p_module['id_module']);
        }

        $this->context->smarty->assign(array(
            'recyclable_pack' => (int)Configuration::get('PS_RECYCLABLE_PACK'),
            'gift_wrapping' => (int)Configuration::get('PS_GIFT_WRAPPING'),
            'cart' => $cart,
            'currencies' => Currency::getCurrenciesByIdShop(Context::getContext()->shop->id),
            'langs' => Language::getLanguages(true, Context::getContext()->shop->id),
            'payment_modules' => $payment_modules,
            'order_states' => OrderState::getOrderStates((int)Context::getContext()->language->id),
            'defaults_order_state' => $defaults_order_state,
            'show_toolbar' => $this->show_toolbar,
            'toolbar_btn' => $this->toolbar_btn,
            'toolbar_scroll' => $this->toolbar_scroll,
            'PS_CATALOG_MODE' => Configuration::get('PS_CATALOG_MODE'),
            'title' => array($this->trans('Orders', array(), 'Admin.Orderscustomers.Feature'), $this->trans('Create order', array(), 'Admin.Orderscustomers.Feature'))

        ));
        $this->content .= $this->createTemplate('form.tpl')->fetch();
    }

    public function initToolbar()
    {

        if ($this->display == 'view') {
            /** @var Order $order */
            $order = $this->loadObject();
            $customer = $this->context->customer;

            if (!Validate::isLoadedObject($order)) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));
            }

            if ((int)$order->id_customer != 1){
                $this->toolbar_title[] = $this->trans(
                    'Venta',
                    array(
                        '%reference%' => $order->reference,
                        '%firstname%' => $customer->firstname,
                        '%lastname%' => $customer->lastname,
                    ),
                    'Admin.Orderscustomers.Feature'
                );
            }else{
                $this->toolbar_title[] = $this->trans(
                    'Venta',
                    array(
                        '%reference%' => $order->reference,
                    ),
                    'Admin.Orderscustomers.Feature'
                );
            }


            $this->addMetaTitle($this->toolbar_title[count($this->toolbar_title) - 1]);

            if ($order->hasBeenShipped()) {
                $type = $this->trans('Return products', array(), 'Admin.Orderscustomers.Feature');
            } elseif ($order->hasBeenPaid()) {
                $type = $this->trans('Standard refund', array(), 'Admin.Orderscustomers.Feature');
            } else {
                $type = $this->trans('Cancel products', array(), 'Admin.Orderscustomers.Feature');
            }

            if (!$order->hasBeenShipped() && !$this->lite_display) {
                $this->toolbar_btn['new'] = array(
                    'short' => 'Create',
                    'href' => '#',
                    'desc' => $this->trans('Add a product', array(), 'Admin.Orderscustomers.Feature'),
                    'class' => 'add_product'
                );
            }

            if (Configuration::get('PS_ORDER_RETURN') && !$this->lite_display) {
                $this->toolbar_btn['standard_refund'] = array(
                    'short' => 'Create',
                    'href' => '',
                    'desc' => $type,
                    'class' => 'process-icon-standardRefund'
                );
            }

            if ($order->hasInvoice() && !$this->lite_display) {
                $this->toolbar_btn['partial_refund'] = array(
                    'short' => 'Create',
                    'href' => '',
                    'desc' => $this->trans('Partial refund', array(), 'Admin.Orderscustomers.Feature'),
                    'class' => 'process-icon-partialRefund'
                );
            }
        }
        else{
            if (Tools::getValue('filtro') && Tools::getValue('filtro') == 'Cobrar') {
                $this->toolbar_title[] = 'Cuentas por cobrar';
                $this->breadcrumbs[] = 'Cuentas por cobrar';
            }
        }
        $res = parent::initToolbar();
        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && isset($this->toolbar_btn['new']) && Shop::isFeatureActive()) {
            unset($this->toolbar_btn['new']);
        }

        return $res;
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryUI('ui.datepicker');
        $this->addJS(_PS_JS_DIR_.'vendor/d3.v3.min.js');

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/waitMe.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/waitMe.min.js');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/jwerty.js');

        if ($this->access('edit') && $this->display == 'view') {
            $this->addJS(_PS_JS_DIR_.'admin/orders.js');
            $this->addJS(_PS_JS_DIR_.'tools.js');
            $this->addJqueryPlugin('autocomplete');
        }
    }

    public function printPDFIcons($id_order, $tr)
    {
        static $valid_order_state = array();

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        if (!isset($valid_order_state[$order->current_state])) {
            $valid_order_state[$order->current_state] = Validate::isLoadedObject($order->getCurrentOrderState());
        }

        if (!$valid_order_state[$order->current_state]) {
            return '';
        }

        $this->context->smarty->assign(array(
            'order' => $order,
            'tr' => $tr
        ));

        return $this->createTemplate('_print_pdf_icon.tpl')->fetch();
    }

    public function processBulkUpdateOrderStatus()
    {
        if (Tools::isSubmit('submitUpdateOrderStatus')
            && ($id_order_state = (int)Tools::getValue('id_order_state'))) {
            if (true !== $this->access('edit')) {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            } else {
                $order_state = new OrderState($id_order_state);

                if (!Validate::isLoadedObject($order_state)) {
                    $this->errors[] = $this->trans('Order status #%id% cannot be loaded', array('%id%' => $id_order_state), 'Admin.Orderscustomers.Notification');
                } else {
                    foreach (Tools::getValue('orderBox') as $id_order) {
                        $order = new Order((int)$id_order);
                        if (!Validate::isLoadedObject($order)) {
                            $this->errors[] = $this->trans('Order #%d cannot be loaded', array('#%d' => $id_order), 'Admin.Orderscustomers.Notification');
                        } else {
                            $current_order_state = $order->getCurrentOrderState();
                            if ($current_order_state->id == $order_state->id) {
                                $this->errors[] = $this->trans('Order #%d has already been assigned this status.', array('#%d' => $id_order), 'Admin.Orderscustomers.Notification');
                            } else {
                                $history = new OrderHistory();
                                $history->id_order = $order->id;
                                $history->id_employee = (int)$this->context->employee->id;

                                $use_existings_payment = !$order->hasInvoice();
                                $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

                                $carrier = new Carrier($order->id_carrier, $order->id_lang);
                                $templateVars = array();
                                if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                                    $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                                }

                                if ($history->addWithemail(true, $templateVars)) {
                                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                        foreach ($order->getProducts() as $product) {
                                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                                StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                                            }
                                        }
                                    }
                                } else {
                                    $this->errors[] = $this->trans(
                                        'An error occurred while changing the status for order #%d, or we were unable to send an email to the customer.',
                                        array(
                                            '#%d' => $id_order,
                                        ),
                                        'Admin.Orderscustomers.Notification'
                                    );
                                }
                            }
                        }
                    }
                }
            }
            if (!count($this->errors)) {
                Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
            }
        }
    }

    public function renderList()
    {
//        $this->actions[] = 'view';


        if (!$this->existeCajasAbiertas){
            unset($this->page_header_toolbar_btn['new_order']);
            unset($this->toolbar_btn['new']);
        }


        if (Tools::isSubmit('submitBulkupdateOrderStatus'.$this->table)) {
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
            }

            $this->tpl_list_vars['updateOrderStatus_mode'] = true;
            $this->tpl_list_vars['order_statuses'] = $this->statuses_array;
            $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $this->tpl_list_vars['POST'] = $_POST;
        }

        $this->context->cookie->__set("metodo_pago", false);

        $this->context->smarty->assign(array(
            'perfil_empleado' => $this->nombre_access['name'],
            'existeCajasAbiertas' => $this->existeCajasAbiertas,
            'alarmAudio' => 'controllers/orders/helpers/list/media/noise.mp3',
        ));

        return parent::renderList();
    }

    public function postProcess()
    {



        // If id_order is sent, we instanciate a new Order object
        if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0) {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                $this->errors[] = $this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification');
            }
            ShopUrl::cacheMainDomainForShop((int)$order->id_shop);
        }

        /* Update shipping number and carrier */
        if (Tools::isSubmit('submitShippingNumber') && isset($order)) {
            if ($this->access('edit')) {
                $tracking_number = Tools::getValue('shipping_tracking_number');
                $id_carrier = Tools::getValue('shipping_carrier');
                $old_tracking_number = $order->shipping_number;

                $order_carrier = new OrderCarrier(Tools::getValue('id_order_carrier'));
                if (!Validate::isLoadedObject($order_carrier)) {
                    $this->errors[] = $this->trans('The order carrier ID is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!empty($tracking_number) && !Validate::isTrackingNumber($tracking_number)) {
                    $this->errors[] = $this->trans('The tracking number is incorrect.', array(), 'Admin.Orderscustomers.Notification');
                } else {
                    //update carrier - ONLY if changed - then refresh shipping cost
                    $old_id_carrier = $order_carrier->id_carrier;
                    if (!empty($id_carrier) && $old_id_carrier != $id_carrier) {
                        $order->id_carrier = (int) $id_carrier;
                        $order_carrier->id_carrier = (int) $id_carrier;
                        $order_carrier->update();
                        $order->refreshShippingCost();
                    }

                    //load fresh order carrier because updated just before
                    $order_carrier = new OrderCarrier((int) Tools::getValue('id_order_carrier'));

                    // update shipping number
                    // Keep these two following lines for backward compatibility, remove on 1.6 version
                    $order->shipping_number = $tracking_number;
                    $order->update();

                    // Update order_carrier
                    $order_carrier->tracking_number = pSQL($tracking_number);
                    if ($order_carrier->update()) {
                        //send mail only if tracking number is different AND not empty
                        if (!empty($tracking_number) && $old_tracking_number != $tracking_number) {
                            if ($order_carrier->sendInTransitEmail($order)) {
                                $customer = new Customer((int)$order->id_customer);
                                $carrier = new Carrier((int)$order->id_carrier, $order->id_lang);

                                Hook::exec('actionAdminOrdersTrackingNumberUpdate', array(
                                    'order' => $order,
                                    'customer' => $customer,
                                    'carrier' => $carrier
                                ), null, false, true, false, $order->id_shop);

                                Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                            } else {
                                $this->errors[] = $this->trans('An error occurred while sending an email to the customer.', array(), 'Admin.Orderscustomers.Notification');
                            }
                        }
                    } else {
                        $this->errors[] = $this->trans('The order carrier cannot be updated.', array(), 'Admin.Orderscustomers.Notification');
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        } elseif (Tools::isSubmit('submitState') && isset($order)) {
            /* Change order status, add a new entry in order history and send an e-mail to the customer if needed */
            if ($this->access('edit')) {

                $order_state = new OrderState(Tools::getValue('id_order_state'));

                if (!Validate::isLoadedObject($order_state)) {
                    $this->errors[] = $this->trans('The new order status is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } else {
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

                        $carrier = new Carrier($order->id_carrier, $order->id_lang);
                        $templateVars = array();
                        if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                            $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                        }

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

                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$order->id.'&vieworder&token='.$this->token);
                        }

                        $this->errors[] = $this->trans('An error occurred while changing order status, or we were unable to send an email to the customer.', array(), 'Admin.Orderscustomers.Notification');
                    } else {
                        $this->errors[] = $this->trans('The order has already been assigned this status.', array(), 'Admin.Orderscustomers.Notification');
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('submitMessage') && isset($order)) {
            // Add a new message for the current order and send an e-mail to the customer if needed
            if ($this->access('edit')) {
                $customer = new Customer(Tools::getValue('id_customer'));
                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = $this->trans('The customer is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!Tools::getValue('message')) {
                    $this->errors[] = $this->trans('The message cannot be blank.', array(), 'Admin.Orderscustomers.Notification');
                } else {
                    /* Get message rules and and check fields validity */
                    $rules = call_user_func(array('Message', 'getValidationRules'), 'Message');
                    foreach ($rules['required'] as $field) {
                        if (($value = Tools::getValue($field)) == false && (string)$value != '0') {
                            if (!Tools::getValue('id_'.$this->table) || $field != 'passwd') {
                                $this->errors[] = $this->trans('field %s is required.', array('%s' => $field), 'Admin.Orderscustomers.Notification');
                            }
                        }
                    }
                    foreach ($rules['size'] as $field => $maxLength) {
                        if (Tools::getValue($field) && Tools::strlen(Tools::getValue($field)) > $maxLength) {
                            $this->errors[] = $this->trans(
                                'The %1$s field is too long (%2$d chars max).',
                                array(
                                    '%1$s' => $field,
                                    '%2$d' => $maxLength,
                                ),
                                'Admin.Notifications.Error'
                            );
                        }
                    }
                    foreach ($rules['validate'] as $field => $function) {
                        if (Tools::getValue($field)) {
                            if (!Validate::$function(htmlentities(Tools::getValue($field), ENT_COMPAT, 'UTF-8'))) {
                                $this->errors[] = $this->trans('The %s field is invalid.', array('%s' => $field), 'Admin.Notifications.Error');
                            }
                        }
                    }

                    if (!count($this->errors)) {
                        //check if a thread already exist
                        $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $order->id);
                        if (!$id_customer_thread) {
                            $customer_thread = new CustomerThread();
                            $customer_thread->id_contact = 0;
                            $customer_thread->id_customer = (int)$order->id_customer;
                            $customer_thread->id_shop = (int)$this->context->shop->id;
                            $customer_thread->id_order = (int)$order->id;
                            $customer_thread->id_lang = (int)$this->context->language->id;
                            $customer_thread->email = $customer->email;
                            $customer_thread->status = 'open';
                            $customer_thread->token = Tools::passwdGen(12);
                            $customer_thread->add();
                        } else {
                            $customer_thread = new CustomerThread((int)$id_customer_thread);
                        }

                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = (int)$this->context->employee->id;
                        $customer_message->message = Tools::getValue('message');
                        $customer_message->private = Tools::getValue('visibility');

                        if (!$customer_message->add()) {
                            $this->errors[] = $this->trans('An error occurred while saving the message.', array(), 'Admin.Notifications.Error');
                        } elseif ($customer_message->private) {
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$order->id.'&vieworder&conf=11&token='.$this->token);
                        } else {
                            $message = $customer_message->message;
                            if (Configuration::get('PS_MAIL_TYPE', null, null, $order->id_shop) != Mail::TYPE_TEXT) {
                                $message = Tools::nl2br($customer_message->message);
                            }

                            $orderLanguage = new Language((int) $order->id_lang);
                            $varsTpl = array(
                                '{lastname}' => $customer->lastname,
                                '{firstname}' => $customer->firstname,
                                '{id_order}' => $order->id,
                                '{order_name}' => $order->getUniqReference(),
                                '{message}' => $message
                            );

                            if (
                            @Mail::Send(
                                (int)$order->id_lang,
                                'order_merchant_comment',
                                $this->trans(
                                    'New message regarding your order',
                                    array(),
                                    'Emails.Subject',
                                    $orderLanguage->locale
                                ),
                                $varsTpl, $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                null,
                                null,
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int)$order->id_shop)
                            ) {
                                Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=11'.'&token='.$this->token);
                            }
                        }
                        $this->errors[] = $this->trans('An error occurred while sending an email to the customer.', array(), 'Admin.Orderscustomers.Notification');
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to delete this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('partialRefund') && isset($order)) {
            // Partial refund from order
            if ($this->access('edit')) {
                if (Tools::isSubmit('partialRefundProduct') && ($refunds = Tools::getValue('partialRefundProduct')) && is_array($refunds)) {
                    $amount = 0;
                    $order_detail_list = array();
                    $full_quantity_list = array();
                    foreach ($refunds as $id_order_detail => $amount_detail) {
                        $quantity = Tools::getValue('partialRefundProductQuantity');
                        if (!$quantity[$id_order_detail]) {
                            continue;
                        }

                        $full_quantity_list[$id_order_detail] = (float)$quantity[$id_order_detail];

                        $order_detail_list[$id_order_detail] = array(
                            'quantity' => (float)$quantity[$id_order_detail],
                            'id_order_detail' => (int)$id_order_detail
                        );

                        $order_detail = new OrderDetail((int)$id_order_detail);
                        if (empty($amount_detail)) {
                            $order_detail_list[$id_order_detail]['unit_price'] = (!Tools::getValue('TaxMethod') ? $order_detail->unit_price_tax_excl : $order_detail->unit_price_tax_incl);
                            $order_detail_list[$id_order_detail]['amount'] = $order_detail->unit_price_tax_incl * $order_detail_list[$id_order_detail]['quantity'];
                        } else {
                            $order_detail_list[$id_order_detail]['amount'] = (float)str_replace(',', '.', $amount_detail);
                            $order_detail_list[$id_order_detail]['unit_price'] = $order_detail_list[$id_order_detail]['amount'] / $order_detail_list[$id_order_detail]['quantity'];
                        }
                        $amount += $order_detail_list[$id_order_detail]['amount'];
                        if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Tools::isSubmit('reinjectQuantities')) && $order_detail_list[$id_order_detail]['quantity'] > 0) {
                            $this->reinjectQuantity($order_detail, $order_detail_list[$id_order_detail]['quantity']);
                        }
                    }

                    $shipping_cost_amount = (float)str_replace(',', '.', Tools::getValue('partialRefundShippingCost')) ? (float)str_replace(',', '.', Tools::getValue('partialRefundShippingCost')) : false;

                    if ($amount == 0 && $shipping_cost_amount == 0) {
                        if (!empty($refunds)) {
                            $this->errors[] = $this->trans('Please enter a quantity to proceed with your refund.', array(), 'Admin.Orderscustomers.Notification');
                        } else {
                            $this->errors[] = $this->trans('Please enter an amount to proceed with your refund.', array(), 'Admin.Orderscustomers.Notification');
                        }
                        return false;
                    }

                    $choosen = false;
                    $voucher = 0;

                    if ((int)Tools::getValue('refund_voucher_off') == 1) {
                        $amount -= $voucher = (float)Tools::getValue('order_discount_price');
                    } elseif ((int)Tools::getValue('refund_voucher_off') == 2) {
                        $choosen = true;
                        $amount = $voucher = (float)Tools::getValue('refund_voucher_choose');
                    }

                    if ($shipping_cost_amount > 0) {
                        if (!Tools::getValue('TaxMethod')) {
                            $tax = new Tax();
                            $tax->rate = $order->carrier_tax_rate;
                            $tax_calculator = new TaxCalculator(array($tax));
                            $amount += $tax_calculator->addTaxes($shipping_cost_amount);
                        } else {
                            $amount += $shipping_cost_amount;
                        }
                    }

                    $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                    if (Validate::isLoadedObject($order_carrier)) {
                        $order_carrier->weight = (float)$order->getTotalWeight();
                        if ($order_carrier->update()) {
                            $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
                        }
                    }

                    if ($amount >= 0) {
                        if (!OrderSlip::create($order, $order_detail_list, $shipping_cost_amount, $voucher, $choosen,
                            (Tools::getValue('TaxMethod') ? false : true))) {
                            $this->errors[] = $this->trans('You cannot generate a partial credit slip.', array(), 'Admin.Orderscustomers.Notification');
                        } else {
                            Hook::exec('actionOrderSlipAdd', array('order' => $order, 'productList' => $order_detail_list, 'qtyList' => $full_quantity_list), null, false, true, false, $order->id_shop);
                            $customer = new Customer((int)($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            $orderLanguage = new Language((int) $order->id_lang);
                            @Mail::Send(
                                (int)$order->id_lang,
                                'credit_slip',
                                $this->trans(
                                    'New credit slip regarding your order',
                                    array(),
                                    'Emails.Subject',
                                    $orderLanguage->locale
                                ),
                                $params,
                                $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                null,
                                null,
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int)$order->id_shop
                            );
                        }

                        foreach ($order_detail_list as &$product) {
                            $order_detail = new OrderDetail((int)$product['id_order_detail']);
                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                StockAvailable::synchronize($order_detail->product_id);
                            }
                        }

                        // Generate voucher
                        if (Tools::isSubmit('generateDiscountRefund') && !count($this->errors) && $amount > 0) {
                            $cart_rule = new CartRule();
                            $cart_rule->description = $this->trans('Credit slip for order #%d', array('#%d' => $order->id), 'Admin.Orderscustomers.Feature');
                            $language_ids = Language::getIDs(false);
                            foreach ($language_ids as $id_lang) {
                                // Define a temporary name
                                $cart_rule->name[$id_lang] = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);
                            }

                            // Define a temporary code
                            $cart_rule->code = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);
                            $cart_rule->quantity = 1;
                            $cart_rule->quantity_per_user = 1;

                            // Specific to the customer
                            $cart_rule->id_customer = $order->id_customer;
                            $now = time();
                            $cart_rule->date_from = date('Y-m-d H:i:s', $now);
                            $cart_rule->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));
                            $cart_rule->partial_use = 1;
                            $cart_rule->active = 1;

                            $cart_rule->reduction_amount = $amount;
                            $cart_rule->reduction_tax = $order->getTaxCalculationMethod() != PS_TAX_EXC;
                            $cart_rule->minimum_amount_currency = $order->id_currency;
                            $cart_rule->reduction_currency = $order->id_currency;

                            if (!$cart_rule->add()) {
                                $this->errors[] = $this->trans('You cannot generate a voucher.', array(), 'Admin.Orderscustomers.Notification');
                            } else {
                                // Update the voucher code and name
                                foreach ($language_ids as $id_lang) {
                                    $cart_rule->name[$id_lang] = sprintf('V%1$dC%2$dO%3$d', $cart_rule->id, $order->id_customer, $order->id);
                                }
                                $cart_rule->code = sprintf('V%1$dC%2$dO%3$d', $cart_rule->id, $order->id_customer, $order->id);

                                if (!$cart_rule->update()) {
                                    $this->errors[] = $this->trans('You cannot generate a voucher.', array(), 'Admin.Orderscustomers.Notification');
                                } else {
                                    $currency = $this->context->currency;
                                    $customer = new Customer((int)($order->id_customer));
                                    $params['{lastname}'] = $customer->lastname;
                                    $params['{firstname}'] = $customer->firstname;
                                    $params['{id_order}'] = $order->id;
                                    $params['{order_name}'] = $order->getUniqReference();
                                    $params['{voucher_amount}'] = Tools::displayPrice($cart_rule->reduction_amount, $currency, false);
                                    $params['{voucher_num}'] = $cart_rule->code;
                                    $orderLanguage = new Language((int) $order->id_lang);
                                    @Mail::Send(
                                        (int)$order->id_lang,
                                        'voucher',
                                        $this->trans(
                                            'New voucher for your order #%s',
                                            array($order->reference),
                                            'Emails.Subject',
                                            $orderLanguage->locale
                                        ),
                                        $params,
                                        $customer->email,
                                        $customer->firstname.' '.$customer->lastname,
                                        null,
                                        null,
                                        null,
                                        null,
                                        _PS_MAIL_DIR_,
                                        true,
                                        (int)$order->id_shop
                                    );
                                }
                            }
                        }
                    } else {
                        if (!empty($refunds)) {
                            $this->errors[] = $this->trans('Please enter a quantity to proceed with your refund.', array(), 'Admin.Orderscustomers.Notification');
                        } else {
                            $this->errors[] = $this->trans('Please enter an amount to proceed with your refund.', array(), 'Admin.Orderscustomers.Notification');
                        }
                    }

                    // Redirect if no errors
                    if (!count($this->errors)) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=30&token='.$this->token);
                    }
                } else {
                    $this->errors[] = $this->trans('The partial refund data is incorrect.', array(), 'Admin.Orderscustomers.Notification');
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to delete this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('cancelProduct') && isset($order)) {
            // Cancel product from order
            if ($this->access('delete')) {
                if (!Tools::isSubmit('id_order_detail') && !Tools::isSubmit('id_customization')) {
                    $this->errors[] = $this->trans('You must select a product.', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!Tools::isSubmit('cancelQuantity') && !Tools::isSubmit('cancelCustomizationQuantity')) {
                    $this->errors[] = $this->trans('You must enter a quantity.', array(), 'Admin.Orderscustomers.Notification');
                } else {
                    $productList = Tools::getValue('id_order_detail');
                    if ($productList) {
                        $productList = array_map('intval', $productList);
                    }

                    $customizationList = Tools::getValue('id_customization');
                    if ($customizationList) {
                        $customizationList = array_map('intval', $customizationList);
                    }

                    $qtyList = Tools::getValue('cancelQuantity');
                    if ($qtyList) {
                        $qtyList = array_map('intval', $qtyList);
                    }

                    $customizationQtyList = Tools::getValue('cancelCustomizationQuantity');
                    if ($customizationQtyList) {
                        $customizationQtyList = array_map('intval', $customizationQtyList);
                    }

                    $full_product_list = $productList;
                    $full_quantity_list = $qtyList;

                    if ($customizationList) {
                        foreach ($customizationList as $key => $id_order_detail) {
                            $full_product_list[(int)$id_order_detail] = $id_order_detail;
                            if (isset($customizationQtyList[$key])) {
                                $full_quantity_list[(int)$id_order_detail] += $customizationQtyList[$key];
                            }
                        }
                    }

                    if ($productList || $customizationList) {
                        if ($productList) {
                            $id_cart = Cart::getCartIdByOrderId($order->id);
                            $customization_quantities = Customization::countQuantityByCart($id_cart);

                            foreach ($productList as $key => $id_order_detail) {
                                $qtyCancelProduct = abs($qtyList[$key]);
                                if (!$qtyCancelProduct) {
                                    $this->errors[] = $this->trans('No quantity has been selected for this product.', array(), 'Admin.Orderscustomers.Notification');
                                }

                                $order_detail = new OrderDetail($id_order_detail);
                                $customization_quantity = 0;
                                if (array_key_exists($order_detail->product_id, $customization_quantities) && array_key_exists($order_detail->product_attribute_id, $customization_quantities[$order_detail->product_id])) {
                                    $customization_quantity = (float)$customization_quantities[$order_detail->product_id][$order_detail->product_attribute_id];
                                }

                                if (($order_detail->product_quantity - $customization_quantity - $order_detail->product_quantity_refunded - $order_detail->product_quantity_return) < $qtyCancelProduct) {
                                    $this->errors[] = $this->trans('An invalid quantity was selected for this product.', array(), 'Admin.Orderscustomers.Notification');
                                }
                            }
                        }
                        if ($customizationList) {
                            $customization_quantities = Customization::retrieveQuantitiesFromIds(array_keys($customizationList));

                            foreach ($customizationList as $id_customization => $id_order_detail) {
                                $qtyCancelProduct = abs($customizationQtyList[$id_customization]);
                                $customization_quantity = $customization_quantities[$id_customization];

                                if (!$qtyCancelProduct) {
                                    $this->errors[] = $this->trans('No quantity has been selected for this product.', array(), 'Admin.Orderscustomers.Notification');
                                }

                                if ($qtyCancelProduct > ($customization_quantity['quantity'] - ($customization_quantity['quantity_refunded'] + $customization_quantity['quantity_returned']))) {
                                    $this->errors[] = $this->trans('An invalid quantity was selected for this product.', array(), 'Admin.Orderscustomers.Notification');
                                }
                            }
                        }

                        if (!count($this->errors) && $productList) {
                            foreach ($productList as $key => $id_order_detail) {
                                $qty_cancel_product = abs($qtyList[$key]);
                                $order_detail = new OrderDetail((int)($id_order_detail));

                                if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Tools::isSubmit('reinjectQuantities')) && $qty_cancel_product > 0) {
                                    $this->reinjectQuantity($order_detail, $qty_cancel_product);
                                }

                                // Delete product
                                $order_detail = new OrderDetail((int)$id_order_detail);
                                if (!$order->deleteProduct($order, $order_detail, $qty_cancel_product)) {
                                    $this->errors[] = $this->trans('An error occurred while attempting to delete the product.', array(), 'Admin.Orderscustomers.Notification').' <span class="bold">'.$order_detail->product_name.'</span>';
                                }
                                // Update weight SUM
                                $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                                if (Validate::isLoadedObject($order_carrier)) {
                                    $order_carrier->weight = (float)$order->getTotalWeight();
                                    if ($order_carrier->update()) {
                                        $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
                                    }
                                }

                                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && StockAvailable::dependsOnStock($order_detail->product_id)) {
                                    StockAvailable::synchronize($order_detail->product_id);
                                }
                                Hook::exec('actionProductCancel', array('order' => $order, 'id_order_detail' => (int)$id_order_detail), null, false, true, false, $order->id_shop);
                            }
                        }
                        if (!count($this->errors) && $customizationList) {
                            foreach ($customizationList as $id_customization => $id_order_detail) {
                                $order_detail = new OrderDetail((int)($id_order_detail));
                                $qtyCancelProduct = abs($customizationQtyList[$id_customization]);
                                if (!$order->deleteCustomization($id_customization, $qtyCancelProduct, $order_detail)) {
                                    $this->errors[] = $this->trans('An error occurred while attempting to delete product customization.', array(), 'Admin.Orderscustomers.Notification').' '.$id_customization;
                                }
                            }
                        }
                        // E-mail params
                        if ((Tools::isSubmit('generateCreditSlip') || Tools::isSubmit('generateDiscount')) && !count($this->errors)) {
                            $customer = new Customer((int)($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                        }

                        // Generate credit slip
                        if (Tools::isSubmit('generateCreditSlip') && !count($this->errors)) {
                            $product_list = array();
                            $amount = $order_detail->unit_price_tax_incl * $full_quantity_list[$id_order_detail];

                            $choosen = false;
                            if ((int)Tools::getValue('refund_total_voucher_off') == 1) {
                                $amount -= $voucher = (float)Tools::getValue('order_discount_price');
                            } elseif ((int)Tools::getValue('refund_total_voucher_off') == 2) {
                                $choosen = true;
                                $amount = $voucher = (float)Tools::getValue('refund_total_voucher_choose');
                            }
                            foreach ($full_product_list as $id_order_detail) {
                                $order_detail = new OrderDetail((int)$id_order_detail);
                                $product_list[$id_order_detail] = array(
                                    'id_order_detail' => $id_order_detail,
                                    'quantity' => $full_quantity_list[$id_order_detail],
                                    'unit_price' => $order_detail->unit_price_tax_excl,
                                    'amount' => isset($amount) ? $amount : $order_detail->unit_price_tax_incl * $full_quantity_list[$id_order_detail],
                                );
                            }

                            $shipping = Tools::isSubmit('shippingBack') ? null : false;

                            if (!OrderSlip::create($order, $product_list, $shipping, $voucher, $choosen)) {
                                $this->errors[] = $this->trans('A credit slip cannot be generated.', array(), 'Admin.Orderscustomers.Notification');
                            } else {
                                Hook::exec('actionOrderSlipAdd', array('order' => $order, 'productList' => $full_product_list, 'qtyList' => $full_quantity_list), null, false, true, false, $order->id_shop);
                                $orderLanguage = new Language((int) $order->id_lang);
                                @Mail::Send(
                                    (int)$order->id_lang,
                                    'credit_slip',
                                    $this->trans(
                                        'New credit slip regarding your order',
                                        array(),
                                        'Emails.Subject',
                                        $orderLanguage->locale
                                    ),
                                    $params,
                                    $customer->email,
                                    $customer->firstname.' '.$customer->lastname,
                                    null,
                                    null,
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    true,
                                    (int)$order->id_shop
                                );
                            }
                        }

                        // Generate voucher
                        if (Tools::isSubmit('generateDiscount') && !count($this->errors)) {
                            $cartrule = new CartRule();
                            $language_ids = Language::getIDs((bool)$order);
                            $cartrule->description = $this->trans('Credit card slip for order #%d', array('#%d' => $order->id), 'Admin.Orderscustomers.Feature');
                            foreach ($language_ids as $id_lang) {
                                // Define a temporary name
                                $cartrule->name[$id_lang] = 'V0C'.(int)($order->id_customer).'O'.(int)($order->id);
                            }
                            // Define a temporary code
                            $cartrule->code = 'V0C'.(int)($order->id_customer).'O'.(int)($order->id);

                            $cartrule->quantity = 1;
                            $cartrule->quantity_per_user = 1;
                            // Specific to the customer
                            $cartrule->id_customer = $order->id_customer;
                            $now = time();
                            $cartrule->date_from = date('Y-m-d H:i:s', $now);
                            $cartrule->date_to = date('Y-m-d H:i:s', $now + (3600 * 24 * 365.25)); /* 1 year */
                            $cartrule->active = 1;

                            $products = $order->getProducts(false, $full_product_list, $full_quantity_list);

                            $total = 0;
                            foreach ($products as $product) {
                                $total += $product['unit_price_tax_incl'] * $product['product_quantity'];
                            }

                            if (Tools::isSubmit('shippingBack')) {
                                $total += $order->total_shipping;
                            }

                            if ((int)Tools::getValue('refund_total_voucher_off') == 1) {
                                $total -= (float)Tools::getValue('order_discount_price');
                            } elseif ((int)Tools::getValue('refund_total_voucher_off') == 2) {
                                $total = (float)Tools::getValue('refund_total_voucher_choose');
                            }

                            $cartrule->reduction_amount = $total;
                            $cartrule->reduction_tax = true;
                            $cartrule->minimum_amount_currency = $order->id_currency;
                            $cartrule->reduction_currency = $order->id_currency;

                            if (!$cartrule->add()) {
                                $this->errors[] = $this->trans('You cannot generate a voucher.', array(), 'Admin.Orderscustomers.Notification');
                            } else {
                                // Update the voucher code and name
                                foreach ($language_ids as $id_lang) {
                                    $cartrule->name[$id_lang] = 'V'.(int)($cartrule->id).'C'.(int)($order->id_customer).'O'.$order->id;
                                }
                                $cartrule->code = 'V'.(int)($cartrule->id).'C'.(int)($order->id_customer).'O'.$order->id;
                                if (!$cartrule->update()) {
                                    $this->errors[] = $this->trans('You cannot generate a voucher.', array(), 'Admin.Orderscustomers.Notification');
                                } else {
                                    $currency = $this->context->currency;
                                    $params['{voucher_amount}'] = Tools::displayPrice($cartrule->reduction_amount, $currency, false);
                                    $params['{voucher_num}'] = $cartrule->code;
                                    $orderLanguage = new Language((int) $order->id_lang);
                                    @Mail::Send(
                                        (int)$order->id_lang,
                                        'voucher',
                                        $this->trans(
                                            'New voucher for your order #%s',
                                            array($order->reference),
                                            'Emails.Subject',
                                            $orderLanguage->locale
                                        ),
                                        $params,
                                        $customer->email,
                                        $customer->firstname.' '.$customer->lastname,
                                        null,
                                        null,
                                        null,
                                        null,
                                        _PS_MAIL_DIR_,
                                        true,
                                        (int)$order->id_shop
                                    );
                                }
                            }
                        }
                    } else {
                        $this->errors[] = $this->trans('No product or quantity has been selected.', array(), 'Admin.Orderscustomers.Notification');
                    }

                    // Redirect if no errors
                    if (!count($this->errors)) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=31&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to delete this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('messageReaded')) {
            Message::markAsReaded(Tools::getValue('messageReaded'), $this->context->employee->id);
        }
        elseif (Tools::isSubmit('submitAddPayment') && isset($order)) {
            if ($this->access('edit')) {
                if (Tools::getValue('tipo_documento_electronico_fisico')) {
                    $tipo_comprobante = Tools::getValue('tipo_documento_electronico_fisico');
                    $numeracion_documento = NumeracionDocumento::getNumTipoDoc($tipo_comprobante);
                    if (empty($numeracion_documento)) {
                        $this->errors[] = $this->trans('Porfavor cree las series y numeración para su tienda gracias. Nombre: ' . $tipo_comprobante, array(), 'Admin.Orderscustomers.Notification');
                    }
                }

                if ($this->existeCajasAbiertas){
                    $last_caja = PosArqueoscaja::getCajaLast($this->context->shop->id);
                }
                else{
                    $this->errors[] = $this->trans('No existe ninguna caja abierta!!', array(), 'Admin.Orderscustomers.Notification');
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }

                $tipo_comprobante = '';
                $amount = str_replace(',', '.', Tools::getValue('payment_amount'));

                $debe_vuelto = 0;
                $vuelto_pago = 0;
                $ultimopago = 0;
                foreach ($order->getOrderPaymentCollection() as $payment){
                    $ultimopago += $payment->amount;
                }

                if ($amount > $order->total_paid){
                    $debe_vuelto = $amount - ($order->total_paid - $ultimopago);
                    $vuelto_pago = $amount - ($order->total_paid - $ultimopago);
                    $amount = ($order->total_paid - $ultimopago);
                }
                else {
                    $ultimopago_final = $order->total_paid - $ultimopago ;

                    if($amount > $ultimopago_final){
                        $vuelto_pago = $amount - $ultimopago_final;
                        $debe_vuelto = $amount - $ultimopago_final;
                        $amount = $ultimopago_final;
                    }else{
                        $debe_vuelto = $amount - $ultimopago_final;
                    }
                }

//                d(Tools::ps_round($debe_vuelto,4));

                $currency = new Currency(Tools::getValue('payment_currency'));
                $order_has_invoice = $order->hasInvoice();
                if ($order_has_invoice) {
                    $order_invoice = new OrderInvoice(Tools::getValue('payment_invoice'));
                } else {
                    $order_invoice = null;
                }


                if (!Validate::isLoadedObject($order)) {
                    $this->errors[] = $this->trans('The order cannot be found', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!Validate::isNegativePrice($amount) || !(float)$amount) {
                    $this->errors[] = $this->trans('The amount is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!Validate::isGenericName(Tools::getValue('payment_method'))) {
                    $this->errors[] = $this->trans('The selected payment method is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!Validate::isString(Tools::getValue('payment_transaction_id'))) {
                    $this->errors[] = $this->trans('The transaction ID is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!Validate::isLoadedObject($currency)) {
                    $this->errors[] = $this->trans('The selected currency is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } elseif ($order_has_invoice && !Validate::isLoadedObject($order_invoice)) {
                    $this->errors[] = $this->trans('The invoice is invalid.', array(), 'Admin.Orderscustomers.Notification');
                } elseif (!Validate::isDate(Tools::getValue('payment_date'))) {
                    $this->errors[] = $this->trans('The date is invalid', array(), 'Admin.Orderscustomers.Notification');
                } else {
                    if (!$order->addOrderPayment($amount, Tools::getValue('payment_method'), Tools::getValue('payment_transaction_id'), $currency, Tools::getValue('payment_date'), $order_invoice, $vuelto_pago, (int)Tools::getValue('tipo_pago'), null, $this->context->employee->id)) {
                        $this->errors[] = $this->trans('An error occurred during payment.', array(), 'Admin.Orderscustomers.Notification');
                    } else {
                        // actualizar la caja o la cuenta
                        if ((int)Tools::getValue('tipo_pago') == 1){
                            $obj_caja = new PosArqueoscaja((int)$last_caja['id_pos_arqueoscaja']);
                            $montoinicial = $obj_caja->monto_operaciones;
                            $montofinal=$montoinicial + $amount;
                            $obj_caja->monto_operaciones=$montofinal;
                            $obj_caja->update();
                        }

                        if (!$order->id_employee){
                            $order->id_employee = $this->context->employee->id;
                        }

                        // cambiar de estado a pagado si hay vuelto o si el monto es >= 0
                        if (Tools::ps_round($debe_vuelto,6) >= 0) {
//                            $order->setCurrentState((int)ConfigurationCore::get('PS_OS_PAYMENT'), $order->id_employee); // cambiar el estado a pagado
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

                            $productos = OrderDetail::getList($order->id);
                            foreach ($productos as $product) {
                                if ((int)$product['es_servicio'] == 1 && $order->id_customer != 1){
                                    $objProducto = new Product((int)$product['product_id']);
                                    $customer = new Customer((int)$order->id_customer);
                                    $puntos_tmp = (int)$customer->puntos_acumulados;
                                    $customer->puntos_acumulados = $puntos_tmp + (int)$objProducto->cantidad_puntos;
                                    $customer->update();
                                }
                            }
                        }

                        $order->update();
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('submitEditNote')) {
            $note = Tools::getValue('note');
            $order_invoice = new OrderInvoice((int)Tools::getValue('id_order_invoice'));
            if (Validate::isLoadedObject($order_invoice) && Validate::isCleanHtml($note)) {
                if ($this->access('edit')) {
                    $order_invoice->note = $note;
                    if ($order_invoice->save()) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order_invoice->id_order.'&vieworder&conf=4&token='.$this->token);
                    } else {
                        $this->errors[] = $this->trans('The invoice note was not saved.', array(), 'Admin.Orderscustomers.Notification');
                    }
                } else {
                    $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
                }
            } else {
                $this->errors[] = $this->trans('Failed to upload the invoice and edit its note.', array(), 'Admin.Orderscustomers.Notification');
            }
        }
        elseif (Tools::isSubmit('submitAddOrder') && ($id_cart = Tools::getValue('id_cart')) &&
            ($module_name = Tools::getValue('payment_module_name')) &&
            ($id_order_state = Tools::getValue('id_order_state')) && Validate::isModuleName($module_name)) {
            if ($this->access('edit')) {
                if (!Configuration::get('PS_CATALOG_MODE')) {
                    $payment_module = Module::getInstanceByName($module_name);
                } else {
                    $payment_module = new BoOrder();
                }

                $cart = new Cart((int)$id_cart);
                Context::getContext()->currency = new Currency((int)$cart->id_currency);
                Context::getContext()->customer = new Customer((int)$cart->id_customer);

                $bad_delivery = false;
                if (($bad_delivery = (bool)!Address::isCountryActiveById((int)$cart->id_address_delivery))
                    || !Address::isCountryActiveById((int)$cart->id_address_invoice)) {
                    if ($bad_delivery) {
                        $this->errors[] = $this->trans('This delivery address country is not active.', array(), 'Admin.Orderscustomers.Notification');
                    } else {
                        $this->errors[] = $this->trans('This invoice address country is not active.', array(), 'Admin.Orderscustomers.Notification');
                    }
                } else {
                    $employee = new Employee((int)Context::getContext()->cookie->id_employee);
                    $payment_module->validateOrder(
                        (int)$cart->id, (int)$id_order_state,
                        $cart->getOrderTotal(true, Cart::BOTH), $payment_module->displayName, $this->trans('Manual order -- Employee:', array(), 'Admin.Orderscustomers.Feature').' '.
                        substr($employee->firstname, 0, 1).'. '.$employee->lastname, array(), null, false, $cart->secure_key
                    );
                    if ($payment_module->currentOrder) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$payment_module->currentOrder.'&vieworder'.'&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to add this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif ((Tools::isSubmit('submitAddressShipping') || Tools::isSubmit('submitAddressInvoice')) && isset($order)) {
            if ($this->access('edit')) {
                $address = new Address(Tools::getValue('id_address'));
                if (Validate::isLoadedObject($address)) {
                    // Update the address on order
                    if (Tools::isSubmit('submitAddressShipping')) {
                        $order->id_address_delivery = $address->id;
                    } elseif (Tools::isSubmit('submitAddressInvoice')) {
                        $order->id_address_invoice = $address->id;
                    }
                    $order->update();
                    $order->refreshShippingCost();

                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                } else {
                    $this->errors[] = $this->trans('This address can\'t be loaded', array(), 'Admin.Orderscustomers.Notification');
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('submitChangeCurrency') && isset($order)) {
            if ($this->access('edit')) {
                if (Tools::getValue('new_currency') != $order->id_currency && !$order->valid) {
                    $old_currency = new Currency($order->id_currency);
                    $currency = new Currency(Tools::getValue('new_currency'));
                    if (!Validate::isLoadedObject($currency)) {
                        throw new PrestaShopException('Can\'t load Currency object');
                    }

                    // Update order detail amount
                    foreach ($order->getOrderDetailList() as $row) {
                        $order_detail = new OrderDetail($row['id_order_detail']);
                        $fields = array(
                            'ecotax',
                            'product_price',
                            'reduction_amount',
                            'total_shipping_price_tax_excl',
                            'total_shipping_price_tax_incl',
                            'total_price_tax_incl',
                            'total_price_tax_excl',
                            'product_quantity_discount',
                            'purchase_supplier_price',
                            'reduction_amount',
                            'reduction_amount_tax_incl',
                            'reduction_amount_tax_excl',
                            'unit_price_tax_incl',
                            'unit_price_tax_excl',
                            'original_product_price'

                        );
                        foreach ($fields as $field) {
                            $order_detail->{$field} = Tools::convertPriceFull($order_detail->{$field}, $old_currency, $currency);
                        }

                        $order_detail->update();
                        $order_detail->updateTaxAmount($order);
                    }

                    $id_order_carrier = (int)$order->getIdOrderCarrier();
                    if ($id_order_carrier) {
                        $order_carrier = $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                        $order_carrier->shipping_cost_tax_excl = (float)Tools::convertPriceFull($order_carrier->shipping_cost_tax_excl, $old_currency, $currency);
                        $order_carrier->shipping_cost_tax_incl = (float)Tools::convertPriceFull($order_carrier->shipping_cost_tax_incl, $old_currency, $currency);
                        $order_carrier->update();
                    }

                    // Update order && order_invoice amount
                    $fields = array(
                        'total_discounts',
                        'total_discounts_tax_incl',
                        'total_discounts_tax_excl',
                        'total_discount_tax_excl',
                        'total_discount_tax_incl',
                        'total_paid',
                        'total_paid_tax_incl',
                        'total_paid_tax_excl',
                        'total_paid_real',
                        'total_products',
                        'total_products_wt',
                        'total_shipping',
                        'total_shipping_tax_incl',
                        'total_shipping_tax_excl',
                        'total_wrapping',
                        'total_wrapping_tax_incl',
                        'total_wrapping_tax_excl',
                    );

                    $invoices = $order->getInvoicesCollection();
                    if ($invoices) {
                        foreach ($invoices as $invoice) {
                            foreach ($fields as $field) {
                                if (isset($invoice->$field)) {
                                    $invoice->{$field} = Tools::convertPriceFull($invoice->{$field}, $old_currency, $currency);
                                }
                            }
                            $invoice->save();
                        }
                    }

                    foreach ($fields as $field) {
                        if (isset($order->$field)) {
                            $order->{$field} = Tools::convertPriceFull($order->{$field}, $old_currency, $currency);
                        }
                    }

                    // Update currency in order
                    $order->id_currency = $currency->id;
                    // Update exchange rate
                    $order->conversion_rate = (float)$currency->conversion_rate;
                    $order->update();
                } else {
                    $this->errors[] = $this->trans('You cannot change the currency.', array(), 'Admin.Orderscustomers.Notification');
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('submitGenerateInvoice') && isset($order)) {
            if (!Configuration::get('PS_INVOICE', null, null, $order->id_shop)) {
                $this->errors[] = $this->trans('Invoice management has been disabled.', array(), 'Admin.Orderscustomers.Notification');
            } elseif ($order->hasInvoice()) {
                $this->errors[] = $this->trans('This order already has an invoice.', array(), 'Admin.Orderscustomers.Notification');
            } else {
                $order->setInvoice(true);
                Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
            }
        }
        elseif (Tools::isSubmit('submitDeleteVoucher') && isset($order)) {
            if ($this->access('edit')) {
                $order_cart_rule = new OrderCartRule(Tools::getValue('id_order_cart_rule'));
                if (Validate::isLoadedObject($order_cart_rule) && $order_cart_rule->id_order == $order->id) {
                    if ($order_cart_rule->id_order_invoice) {
                        $order_invoice = new OrderInvoice($order_cart_rule->id_order_invoice);
                        if (!Validate::isLoadedObject($order_invoice)) {
                            throw new PrestaShopException('Can\'t load Order Invoice object');
                        }

                        // Update amounts of Order Invoice
                        $order_invoice->total_discount_tax_excl -= $order_cart_rule->value_tax_excl;
                        $order_invoice->total_discount_tax_incl -= $order_cart_rule->value;

                        $order_invoice->total_paid_tax_excl += $order_cart_rule->value_tax_excl;
                        $order_invoice->total_paid_tax_incl += $order_cart_rule->value;

                        // Update Order Invoice
                        $order_invoice->update();
                    }

                    // Update amounts of order
                    $order->total_discounts -= $order_cart_rule->value;
                    $order->total_discounts_tax_incl -= $order_cart_rule->value;
                    $order->total_discounts_tax_excl = Tools::ps_round($order->total_discounts_tax_excl - $order_cart_rule->value_tax_excl, 2);

                    $order->total_paid += $order_cart_rule->value;
                    $order->total_paid_tax_incl += $order_cart_rule->value;
                    $order->total_paid_tax_excl += $order_cart_rule->value_tax_excl;

                    // Delete Order Cart Rule and update Order
                    $order_cart_rule->delete();
                    $order->update();
                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                } else {
                    $this->errors[] = $this->trans('You cannot edit this cart rule.', array(), 'Admin.Orderscustomers.Notification');
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('submitNewVoucher') && isset($order)) {
            if ($this->access('edit')) {
                if (!Tools::getValue('discount_name')) {
                    $this->errors[] = $this->trans('You must specify a name in order to create a new discount.', array(), 'Admin.Orderscustomers.Notification');
                } else {
                    if ($order->hasInvoice()) {
                        // If the discount is for only one invoice
                        if (!Tools::isSubmit('discount_all_invoices')) {
                            $order_invoice = new OrderInvoice(Tools::getValue('discount_invoice'));
                            if (!Validate::isLoadedObject($order_invoice)) {
                                throw new PrestaShopException('Can\'t load Order Invoice object');
                            }
                        }
                    }

                    $cart_rules = array();
                    $discount_value = (float)str_replace(',', '.', Tools::getValue('discount_value'));
                    switch (Tools::getValue('discount_type')) {
                        // Percent type
                        case 1:
                            if ($discount_value < 100) {
                                if (isset($order_invoice)) {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($order_invoice->total_paid_tax_incl * $discount_value / 100, 4);
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($order_invoice->total_paid_tax_excl * $discount_value / 100, 4);

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                } elseif ($order->hasInvoice()) {
                                    $order_invoices_collection = $order->getInvoicesCollection();
                                    foreach ($order_invoices_collection as $order_invoice) {
                                        /** @var OrderInvoice $order_invoice */
                                        $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($order_invoice->total_paid_tax_incl * $discount_value / 100, 2);
                                        $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($order_invoice->total_paid_tax_excl * $discount_value / 100, 2);

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                    }
                                } else {
                                    $cart_rules[0]['value_tax_incl'] = Tools::ps_round($order->total_paid_tax_incl * $discount_value / 100, 3);
                                    $cart_rules[0]['value_tax_excl'] = Tools::ps_round($order->total_paid_tax_excl * $discount_value / 100, 3);
                                }
                            } else {
                                $this->errors[] = $this->trans('The discount value is invalid.', array(), 'Admin.Orderscustomers.Notification');
                            }
                            break;
                        // Amount type
                        case 2:
                            if (isset($order_invoice)) {
                                if ($discount_value > $order_invoice->total_paid_tax_incl) {
                                    $this->errors[] = $this->trans('The discount value is greater than the order invoice total.', array(), 'Admin.Orderscustomers.Notification');
                                } else {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($discount_value, 2);
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), 2);

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $order_invoices_collection = $order->getInvoicesCollection();
                                foreach ($order_invoices_collection as $order_invoice) {
                                    /** @var OrderInvoice $order_invoice */
                                    if ($discount_value > $order_invoice->total_paid_tax_incl) {
                                        $this->errors[] = $this->trans('The discount value is greater than the order invoice total.', array(), 'Admin.Orderscustomers.Notification').$order_invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop).')';
                                    } else {
                                        $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($discount_value, 2);
                                        $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), 2);

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                    }
                                }
                            } else {
                                if ($discount_value > $order->total_paid_tax_incl) {
                                    $this->errors[] = $this->trans('The discount value is greater than the order total.', array(), 'Admin.Orderscustomers.Notification');
                                } else {
                                    $cart_rules[0]['value_tax_incl'] = Tools::ps_round($discount_value, 2);
                                    $cart_rules[0]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), 2);
                                }
                            }
                            break;
                        // Free shipping type
                        case 3:
                            if (isset($order_invoice)) {
                                if ($order_invoice->total_shipping_tax_incl > 0) {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = $order_invoice->total_shipping_tax_incl;
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = $order_invoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $order_invoices_collection = $order->getInvoicesCollection();
                                foreach ($order_invoices_collection as $order_invoice) {
                                    /** @var OrderInvoice $order_invoice */
                                    if ($order_invoice->total_shipping_tax_incl <= 0) {
                                        continue;
                                    }
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = $order_invoice->total_shipping_tax_incl;
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = $order_invoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } else {
                                $cart_rules[0]['value_tax_incl'] = $order->total_shipping_tax_incl;
                                $cart_rules[0]['value_tax_excl'] = $order->total_shipping_tax_excl;
                            }
                            break;
                        default:
                            $this->errors[] = $this->trans('The discount type is invalid.', array(), 'Admin.Orderscustomers.Notification');
                    }

                    $res = true;
                    foreach ($cart_rules as &$cart_rule) {
                        $cartRuleObj = new CartRule();
                        $cartRuleObj->date_from = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order->date_add)));
                        $cartRuleObj->date_to = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        $cartRuleObj->name[Configuration::get('PS_LANG_DEFAULT')] = Tools::getValue('discount_name');
                        $cartRuleObj->quantity = 0;
                        $cartRuleObj->quantity_per_user = 1;
                        if (Tools::getValue('discount_type') == 1) {
                            $cartRuleObj->reduction_percent = $discount_value;
                        } elseif (Tools::getValue('discount_type') == 2) {
                            $cartRuleObj->reduction_amount = $cart_rule['value_tax_excl'];
                        } elseif (Tools::getValue('discount_type') == 3) {
                            $cartRuleObj->free_shipping = 1;
                        }
                        $cartRuleObj->active = 0;
                        if ($res = $cartRuleObj->add()) {
                            $cart_rule['id'] = $cartRuleObj->id;
                        } else {
                            break;
                        }
                    }

                    if ($res) {
                        foreach ($cart_rules as $id_order_invoice => $cart_rule) {
                            // Create OrderCartRule
                            $order_cart_rule = new OrderCartRule();
                            $order_cart_rule->id_order = $order->id;
                            $order_cart_rule->id_cart_rule = $cart_rule['id'];
                            $order_cart_rule->id_order_invoice = $id_order_invoice;
                            $order_cart_rule->name = Tools::getValue('discount_name');
                            $order_cart_rule->value = $cart_rule['value_tax_incl'];
                            $order_cart_rule->value_tax_excl = $cart_rule['value_tax_excl'];
                            $res &= $order_cart_rule->add();

                            $order->total_discounts += $order_cart_rule->value;
                            $order->total_discounts_tax_incl += $order_cart_rule->value;
                            $order->total_discounts_tax_excl += $order_cart_rule->value_tax_excl;
                            $order->total_paid -= $order_cart_rule->value;
                            $order->total_paid_tax_incl -= $order_cart_rule->value;
                            $order->total_paid_tax_excl -= $order_cart_rule->value_tax_excl;
                        }

                        // Update Order
                        $res &= $order->update();
                    }

                    if ($res) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                    } else {
                        $this->errors[] = $this->trans('An error occurred during the OrderCartRule creation', array(), 'Admin.Orderscustomers.Notification');
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }
        elseif (Tools::isSubmit('sendStateEmail') && Tools::getValue('sendStateEmail') > 0 && Tools::getValue('id_order') > 0) {
            if ($this->access('edit')) {
                $order_state = new OrderState((int)Tools::getValue('sendStateEmail'));

                if (!Validate::isLoadedObject($order_state)) {
                    $this->errors[] = $this->trans('An error occurred while loading order status.', array(), 'Admin.Orderscustomers.Notification');
                } else {
                    $history = new OrderHistory((int)Tools::getValue('id_order_history'));

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();
                    if ($order_state->id == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                    }

                    if ($history->sendEmail($order, $templateVars)) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=10&token='.$this->token);
                    } else {
                        $this->errors[] = $this->trans('An error occurred while sending the e-mail to the customer.', array(), 'Admin.Orderscustomers.Notification');
                    }
                }
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }

        elseif (Tools::isSubmit('submitNuevoCliente') && isset($order)) {
            $id_order_buscar = Tools::getValue('id_order');
            $id_nuevo_customer = Tools::getValue('id_nuevo_customer');
            if ($id_nuevo_customer == 0)
                $id_nuevo_customer = 1;


            if (Validate::isLoadedObject($order) && Validate::isCleanHtml($id_order_buscar)) {
                if ($this->access('edit')) {
                    $order->id_customer = $id_nuevo_customer;
                    if ($id_nuevo_customer > 0){
                        if ($order->save()) {
                            $this->confirmations[] = 'El cliente se a cambiado.';
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$order->id.'&vieworder&token='.$this->token);

                        } else {
                            $this->errors[] = $this->trans('No se pudo guardar el cliente.', array(), 'Admin.Orderscustomers.Notification');
                        }
                    }
                    else{
                        $this->errors[] = $this->trans('Porfavor Seleccione un Cliente Valido.', array(), 'Admin.Orderscustomers.Notification');
                    }
                }
                else {
                    $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
                }
            }
            else {
                $this->errors[] = $this->trans('Failed to upload the invoice and edit its note.', array(), 'Admin.Orderscustomers.Notification');
            }
        }
        elseif (Tools::isSubmit('submitNuevoProveedor') && isset($order)) {
            $id_order_buscar = Tools::getValue('id_order');
            $id_nuevo_proveedor = Tools::getValue('cb_proveedores');

            if (Validate::isLoadedObject($order) && Validate::isCleanHtml($id_order_buscar)) {
                if ($this->access('edit')) {
                    $order->cliente_empresa = "Empresa";
                    $order->id_supplier = $id_nuevo_proveedor;
                    if ($id_nuevo_proveedor >= 0){
                        if ($order->save()) {
//                            $this->confirmations[] = 'El cliente se a cambiado.';
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$order->id.'&vieworder&token='.$this->token);

                        } else {
                            $this->errors[] = $this->trans('The invoice note was not saved.', array(), 'Admin.Orderscustomers.Notification');
                        }
                    }
                    else{
                        $this->errors[] = $this->trans('Porfavor Seleccione una Empresa Valida.', array(), 'Admin.Orderscustomers.Notification');
                    }
                }
                else {
                    $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
                }
            }
            else {
                $this->errors[] = $this->trans('Failed to upload the invoice and edit its note.', array(), 'Admin.Orderscustomers.Notification');
            }
        }

        parent::postProcess();
    }

    public function renderbcaKpis()
    {
        $time = time();
        $kpis = array();


        /* The data generation is located in AdminStatsControllerCore */

        $helper = new HelperKpi();
        $helper->id = 'box-conversion-rate';
        $helper->icon = 'icon-sort-by-attributes-alt';
        $helper->chart = true;
        $helper->color = 'color1';
        $helper->title = $this->trans('Conversion Rate', array(), 'Admin.Global');
        $helper->subtitle = $this->trans('30 days', array(), 'Admin.Global');
        if (ConfigurationKPI::get('CONVERSION_RATE') !== false) {
            $helper->value = ConfigurationKPI::get('CONVERSION_RATE');
        }
        if (ConfigurationKPI::get('CONVERSION_RATE_CHART') !== false) {
            $helper->data = ConfigurationKPI::get('CONVERSION_RATE_CHART');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=conversion_rate';
        $helper->refresh = (bool)(ConfigurationKPI::get('CONVERSION_RATE_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-carts';
        $helper->icon = 'icon-shopping-cart';
        $helper->color = 'color2';
        $helper->title = $this->trans('Abandoned Carts', array(), 'Admin.Global');
        $helper->subtitle = $this->trans('Today', array(), 'Admin.Global');
        $helper->href = $this->context->link->getAdminLink('AdminCarts').'&action=filterOnlyAbandonedCarts';
        if (ConfigurationKPI::get('ABANDONED_CARTS') !== false) {
            $helper->value = ConfigurationKPI::get('ABANDONED_CARTS');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=abandoned_cart';
        $helper->refresh = (bool)(ConfigurationKPI::get('ABANDONED_CARTS_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-average-order';
        $helper->icon = 'icon-money';
        $helper->color = 'color3';
        $helper->title = $this->trans('Average Order Value', array(), 'Admin.Global');
        $helper->subtitle = $this->trans('30 days', array(), 'Admin.Global');
        if (ConfigurationKPI::get('AVG_ORDER_VALUE') !== false) {
            $helper->value = $this->trans('%amount% tax excl.', array('%amount%' => ConfigurationKPI::get('AVG_ORDER_VALUE')), 'Admin.Orderscustomers.Feature');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=average_order_value';
        $helper->refresh = (bool)(ConfigurationKPI::get('AVG_ORDER_VALUE_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-net-profit-visit';
        $helper->icon = 'icon-user';
        $helper->color = 'color4';
        $helper->title = $this->trans('Net Profit per Visit', array(), 'Admin.Orderscustomers.Feature');
        $helper->subtitle = $this->trans('30 days', array(), 'Admin.Orderscustomers.Feature');
        if (ConfigurationKPI::get('NETPROFIT_VISIT') !== false) {
            $helper->value = ConfigurationKPI::get('NETPROFIT_VISIT');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=netprofit_visit';
        $helper->refresh = (bool)(ConfigurationKPI::get('NETPROFIT_VISIT_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpiRow();
        $helper->kpis = $kpis;

        return $helper->generate();
    }

    public function ajaxProcessChangeMetodoPago(){

        $context = Context::getContext();
        $context->cookie->__set("metodo_pago", Tools::getValue('metodo_pago'));

    }

    public function renderView()
    {

        $order = new Order(Tools::getValue('id_order'));
        if (!Validate::isLoadedObject($order)) {
            $this->errors[] = $this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification');
        }


        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders').'&id_order='.Tools::getValue('id_order').'&vieworder');

//        d($this->context->cookie->metodo_pago);
        $metodo_pago = $this->context->cookie->metodo_pago;
        if(!$this->context->cookie->metodo_pago){
            $this->context->cookie->__set("metodo_pago", 1);
            $metodo_pago = $this->context->cookie->metodo_pago;
        }


        $customer = new Customer($order->id_customer);
        $carrier = new Carrier($order->id_carrier);
        $products = $this->getProducts($order);
        $currency = new Currency((int)$order->id_currency);
        // Carrier module call
        $carrier_module_call = null;
        if ($carrier->is_module) {
            $module = Module::getInstanceByName($carrier->external_module_name);
            if (method_exists($module, 'displayInfoByCart')) {
                $carrier_module_call = call_user_func(array($module, 'displayInfoByCart'), $order->id_cart);
            }
        }

        // Retrieve addresses information
        $addressInvoice = new Address($order->id_address_invoice, $this->context->language->id);
        if (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) {
            $invoiceState = new State((int)$addressInvoice->id_state);
        }

        if ($order->id_address_invoice == $order->id_address_delivery) {
            $addressDelivery = $addressInvoice;
            if (isset($invoiceState)) {
                $deliveryState = $invoiceState;
            }
        } else {
            $addressDelivery = new Address($order->id_address_delivery, $this->context->language->id);
            if (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) {
                $deliveryState = new State((int)($addressDelivery->id_state));
            }
        }

        $this->toolbar_title = $this->trans(
            'Order #%id% (%ref%) - %firstname% %lastname%',
            array(
                '%id%' => $order->id,
                '%ref%' => $order->reference,
                '%firstname%' => $customer->firstname,
                '%lastname%' => $customer->lastname,
            ),
            'Admin.Orderscustomers.Feature'
        );

        if (Shop::isFeatureActive()) {
            $shop = new Shop((int)$order->id_shop);
            $this->toolbar_title .= ' - '.$this->trans('Shop: %shop_name%', array('%shop_name%' => $shop->name), 'Admin.Orderscustomers.Feature');
        }

        // gets warehouses to ship products, if and only if advanced stock management is activated
        $warehouse_list = null;

        $order_details = $order->getOrderDetailList();
        foreach ($order_details as $order_detail) {
            $product = new ProductCore((int)$order_detail['product_id']);
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management) {
                $warehouses = Warehouse::getWarehousesByProductId($order_detail['product_id'], $order_detail['product_attribute_id']);

                foreach ($warehouses as $warehouse) {
                    if (!isset($warehouse_list[$warehouse['id_warehouse']])) {
                        $warehouse_list[$warehouse['id_warehouse']] = $warehouse;
                    }
                }
            }
        }


        $payment_methods = array();
        foreach (PaymentModule::getInstalledPaymentModules() as $payment) {
            $module = Module::getInstanceByName($payment['name']);
            if (Validate::isLoadedObject($module) && $module->active) {
                $payment_methods[] = $module->displayName;
            }
        }

        // display warning if there are products out of stock
        $display_out_of_stock_warning = false;
        $current_order_state = $order->getCurrentOrderState();
        if (Configuration::get('PS_STOCK_MANAGEMENT') && (!Validate::isLoadedObject($current_order_state) || ($current_order_state->delivery != 1 && $current_order_state->shipped != 1))) {
            $display_out_of_stock_warning = true;
        }

        // products current stock (from stock_available)
        foreach ($products as &$product) {
            // Get total customized quantity for current product
            $customized_product_quantity = 0;

            if (is_array($product['customizedDatas'])) {
                foreach ($product['customizedDatas'] as $customizationPerAddress) {
                    foreach ($customizationPerAddress as $customizationId => $customization) {
                        $customized_product_quantity += (float)$customization['quantity'];
                    }
                }
            }

            $product['customized_product_quantity'] = $customized_product_quantity;
            $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
            $resume = OrderSlip::getProductSlipResume($product['id_order_detail']);
            $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
            $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
            $product['amount_refundable_tax_incl'] = $product['total_price_tax_incl'] - $resume['amount_tax_incl'];
            $product['amount_refund'] = $order->getTaxCalculationMethod() ? Tools::displayPrice($resume['amount_tax_excl'], $currency) : Tools::displayPrice($resume['amount_tax_incl'], $currency);
            $product['refund_history'] = OrderSlip::getProductSlipDetail($product['id_order_detail']);
            $product['return_history'] = OrderReturn::getProductReturnDetail($product['id_order_detail']);

            // if the current stock requires a warning
            if ($product['current_stock'] <= 0 && $display_out_of_stock_warning) {
//                $this->displayWarning($this->trans('This product is out of stock: ', array(), 'Admin.Orderscustomers.Notification').' '.$product['product_name']);
            }
            if ($product['id_warehouse'] != 0) {
                $warehouse = new Warehouse((int)$product['id_warehouse']);
                $product['warehouse_name'] = $warehouse->name;
                $warehouse_location = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
                if (!empty($warehouse_location)) {
                    $product['warehouse_location'] = $warehouse_location;
                } else {
                    $product['warehouse_location'] = false;
                }
            } else {
                $product['warehouse_name'] = '--';
                $product['warehouse_location'] = false;
            }
        }

        // Package management for order
        foreach ($products as &$product) {
//            d($product['product_name']);
            $pack_items = $product['cache_is_pack'] ? Pack::getItemTable($product['id_product'], $this->context->language->id, true) : array();
            foreach ($pack_items as &$pack_item) {

                $pack_item['current_stock'] = StockAvailable::getQuantityAvailableByProduct($pack_item['id_product'], $pack_item['id_product_attribute'], $pack_item['id_shop']);
                // if the current stock requires a warning
                if ($product['current_stock'] <= 0 && $display_out_of_stock_warning) {
//                    $this->displayWarning($this->trans('Este producto, incluido en el pack ('.$product['product_name'].') esta fuera de stock: ', array(), 'Admin.Orderscustomers.Notification').' '.$pack_item['name']);
                }
                $this->setProductImageInformations($pack_item);
                if ($pack_item['image'] != null) {
                    $name = 'product_mini_'.(int)$pack_item['id_product'].(isset($pack_item['id_product_attribute']) ? '_'.(int)$pack_item['id_product_attribute'] : '').'.jpg';
                    // generate image cache, only for back office
                    $pack_item['image_tag'] = ImageManager::thumbnail(_PS_IMG_DIR_.'p/'.$pack_item['image']->getExistingImgPath().'.jpg', $name, 45, 'jpg');
                    if (file_exists(_PS_TMP_IMG_DIR_.$name)) {
                        $pack_item['image_size'] = getimagesize(_PS_TMP_IMG_DIR_.$name);
                    } else {
                        $pack_item['image_size'] = false;
                    }
                }
            }
            $product['pack_items'] = $pack_items;
        }

        $gender = new Gender((int)$customer->id_gender, $this->context->language->id);

        $history = $order->getHistory($this->context->language->id);

        foreach ($history as &$order_state) {
            $order_state['text-color'] = Tools::getBrightness($order_state['color']) < 128 ? 'white' : 'black';
        }

        $shipping_refundable_tax_excl = $order->total_shipping_tax_excl;
        $shipping_refundable_tax_incl = $order->total_shipping_tax_incl;
        $slips = OrderSlip::getOrdersSlip($customer->id, $order->id);
        foreach ($slips as $slip) {
            $shipping_refundable_tax_excl -= $slip['total_shipping_tax_excl'];
            $shipping_refundable_tax_incl -= $slip['total_shipping_tax_incl'];
        }
        $shipping_refundable_tax_excl = max(0, $shipping_refundable_tax_excl);
        $shipping_refundable_tax_incl = max(0, $shipping_refundable_tax_incl);

        $certificado = Certificadofe::getByAllShop();
        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
        $objComprobantes = null;
        if (!empty($doc)){
            $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
        }

        $cajas = PosArqueoscaja::cajasAbiertasJoinEmpleado();

        $colaboradores = Employee::getColaboradores();
        // Smarty assign
        $this->tpl_view_vars = array(
            'colaboradores' => $colaboradores,
            'metodo_pago' => $metodo_pago,
            'perfil_empleado' => $this->nombre_access['name'],
            'existeCajasAbiertas' => $this->existeCajasAbiertas,
            'objComprobantes'=>$objComprobantes,
            'certificado'=>$certificado,
            'cajas'=>$cajas,
            'order' => $order,
            'cart' => new Cart($order->id_cart),
            'customer' => $customer,
            'gender' => $gender,
            'customer_addresses' => $customer->getAddresses($this->context->language->id),
            'addresses' => array(
                'delivery' => $addressDelivery,
                'deliveryState' => isset($deliveryState) ? $deliveryState : null,
                'invoice' => $addressInvoice,
                'invoiceState' => isset($invoiceState) ? $invoiceState : null
            ),
            'customerStats' => $customer->getStats(),
            'products' => $products,
            'discounts' => $order->getCartRules(),
            'orders_total_paid_tax_incl' => $order->getOrdersTotalPaid(), // Get the sum of total_paid_tax_incl of the order with similar reference
            'total_paid' => $order->getTotalPaid(),
            'returns' => OrderReturn::getOrdersReturn($order->id_customer, $order->id),
            'shipping_refundable_tax_excl' => $shipping_refundable_tax_excl,
            'shipping_refundable_tax_incl' => $shipping_refundable_tax_incl,
            'customer_thread_message' => CustomerThread::getCustomerMessages($order->id_customer, null, $order->id),
            'orderMessages' => OrderMessage::getOrderMessages($order->id_lang),
            'messages' => CustomerThread::getCustomerMessagesOrder($order->id_customer, $order->id),
            'carrier' => new Carrier($order->id_carrier),
            'history' => $history,
            'states' => OrderState::getOrderStates($this->context->language->id),
            'warehouse_list' => $warehouse_list,
            'sources' => ConnectionsSource::getOrderSources($order->id),
            'currentState' => $order->getCurrentOrderState(),
            'currency' => new Currency($order->id_currency),
            'currencies' => Currency::getCurrenciesByIdShop($order->id_shop),
            'previousOrder' => $order->getPreviousOrderId(),
            'nextOrder' => $order->getNextOrderId(),
            'current_index' => self::$currentIndex,
            'carrierModuleCall' => $carrier_module_call,
            'iso_code_lang' => $this->context->language->iso_code,
            'id_lang' => $this->context->language->id,
            'can_edit' => ($this->access('edit')),
            'current_id_lang' => $this->context->language->id,
            'invoices_collection' => $order->getInvoicesCollection(),
            'not_paid_invoices_collection' => $order->getNotPaidInvoicesCollection(),
            'payment_methods' => $payment_methods,
            'invoice_management_active' => Configuration::get('PS_INVOICE', null, null, $order->id_shop),
            'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
            'carrier_list' => $this->getCarrierList($order),
            'recalculate_shipping_cost' => (int)Configuration::get('PS_ORDER_RECALCULATE_SHIPPING'),
            'HOOK_CONTENT_ORDER' => Hook::exec('displayAdminOrderContentOrder', array(
                    'order' => $order,
                    'products' => $products,
                    'customer' => $customer)
            ),
            'HOOK_CONTENT_SHIP' => Hook::exec('displayAdminOrderContentShip', array(
                    'order' => $order,
                    'products' => $products,
                    'customer' => $customer)
            ),
            'HOOK_TAB_ORDER' => Hook::exec('displayAdminOrderTabOrder', array(
                    'order' => $order,
                    'products' => $products,
                    'customer' => $customer)
            ),
            'HOOK_TAB_SHIP' => Hook::exec('displayAdminOrderTabShip', array(
                    'order' => $order,
                    'products' => $products,
                    'customer' => $customer)
            ),
        );

        return parent::renderView();
    }

    public function ajaxProcessSearchProducts()
    {
        Context::getContext()->customer = new Customer((int)Tools::getValue('id_customer'));
        $currency = new Currency((int)Tools::getValue('id_currency'));

        //exclude ids
        $ids_prod = Db::getInstance()->getValue('SELECT GROUP_CONCAT(product_id)  FROM tm_order_detail where id_order = '. (int)Tools::getValue('id_order'));

        if ($products = Product::searchByName((int)$this->context->language->id, pSQL(Tools::getValue('product_search')), null, $ids_prod)) {
            foreach ($products as &$product) {
                // Formatted price
                $product['formatted_price'] = Tools::displayPrice(Tools::convertPrice($product['price_tax_incl'], $currency), $currency);
                // Concret price
                $product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_incl'], $currency), 4);
                $product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_excl'], $currency), 4);
                $productObj = new Product((int)$product['id_product'], false, (int)$this->context->language->id);
                $combinations = array();
                $attributes = $productObj->getAttributesGroups((int)$this->context->language->id);

                // Tax rate for this customer
                if (Tools::isSubmit('id_address')) {
                    $product['tax_rate'] = $productObj->getTaxesRate(new Address(Tools::getValue('id_address')));
                }

                $product['warehouse_list'] = array();

                foreach ($attributes as $attribute) {
                    if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                        $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                    }
                    $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'].' - ';
                    $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
                    $combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];
                    if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
                        $price_tax_incl = Product::getPriceStatic((int)$product['id_product'], true, $attribute['id_product_attribute']);
                        $price_tax_excl = Product::getPriceStatic((int)$product['id_product'], false, $attribute['id_product_attribute']);
                        $combinations[$attribute['id_product_attribute']]['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($price_tax_incl, $currency), 4);
                        $combinations[$attribute['id_product_attribute']]['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($price_tax_excl, $currency), 4);
                        $combinations[$attribute['id_product_attribute']]['formatted_price'] = Tools::displayPrice(Tools::convertPrice($price_tax_excl, $currency), $currency);
                    }
                    if (!isset($combinations[$attribute['id_product_attribute']]['qty_in_stock'])) {
                        $combinations[$attribute['id_product_attribute']]['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct((int)$product['id_product'], $attribute['id_product_attribute'], (int)$this->context->shop->id);
                    }

                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)$product['advanced_stock_management'] == 1) {
                        $product['warehouse_list'][$attribute['id_product_attribute']] = Warehouse::getProductWarehouseList($product['id_product'], $attribute['id_product_attribute']);
                    } else {
                        $product['warehouse_list'][$attribute['id_product_attribute']] = array();
                    }

                    $product['stock'][$attribute['id_product_attribute']] = Product::getRealQuantity($product['id_product'], $attribute['id_product_attribute']);
                }

                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)$product['advanced_stock_management'] == 1) {
                    $product['warehouse_list'][0] = Warehouse::getProductWarehouseList($product['id_product']);
                } else {
                    $product['warehouse_list'][0] = array();
                }

                $product['stock'][0] = StockAvailable::getQuantityAvailableByProduct((int)$product['id_product'], 0, (int)$this->context->shop->id);

                foreach ($combinations as &$combination) {
                    $combination['attributes'] = rtrim($combination['attributes'], ' - ');
                }
                $product['combinations'] = $combinations;

                if ($product['customizable']) {
                    $product_instance = new Product((int)$product['id_product']);
                    $product['customization_fields'] = $product_instance->getCustomizationFields($this->context->language->id);
                }
            }

            $to_return = array(
                'products' => $products,
                'found' => true
            );
        } else {
            $to_return = array('found' => false);
        }

        $this->content = json_encode($to_return);
    }

    public function ajaxProcessSendMailValidateOrder()
    {
        if ($this->access('edit')) {
            $cart = new Cart((int)Tools::getValue('id_cart'));
            if (Validate::isLoadedObject($cart)) {
                $customer = new Customer((int)$cart->id_customer);
                if (Validate::isLoadedObject($customer)) {
                    $mailVars = array(
                        '{order_link}' => Context::getContext()->link->getPageLink('order', false, (int)$cart->id_lang, 'step=3&recover_cart='.(int)$cart->id.'&token_cart='.md5(_COOKIE_KEY_.'recover_cart_'.(int)$cart->id)),
                        '{firstname}' => $customer->firstname,
                        '{lastname}' => $customer->lastname
                    );
                    $cartLanguage = new Language((int) $cart->id_lang);
                    if (
                    Mail::Send(
                        (int)$cart->id_lang,
                        'backoffice_order',
                        $this->trans(
                            'Process the payment of your order',
                            array(),
                            'Emails.Subject',
                            $cartLanguage->locale
                        ),
                        $mailVars,
                        $customer->email,
                        $customer->firstname.' '.$customer->lastname,
                        null,
                        null,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        true,
                        $cart->id_shop)
                    ) {
                        die(json_encode(array('errors' => false, 'result' => $this->trans('The email was sent to your customer.', array(), 'Admin.Orderscustomers.Notification'))));
                    }
                }
            }
            $this->content = json_encode(array('errors' => true, 'result' => $this->trans('Error in sending the email to your customer.', array(), 'Admin.Orderscustomers.Notification')));
        }
    }

    public function ajaxProcessAddProductOnOrder()
    {

//        d(Tools::getAllValues());
        // Load object
        $order = new Order((int)Tools::getValue('id_order'));
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The order object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }


        $old_cart_rules = Context::getContext()->cart->getCartRules();

        if ($order->hasBeenShipped()) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('You cannot add products to delivered orders.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        $product_informations = $_POST['add_product'];

        if (isset($_POST['add_invoice'])) {
            $invoice_informations = $_POST['add_invoice'];
        } else {
            $invoice_informations = array();
        }

//        d($product_informations['product_id']);
//        d(Language::getLanguage($order->id_lang));
        $product = new Product($product_informations['product_id'], false, $order->id_lang);
        if (!Validate::isLoadedObject($product)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The product object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if (isset($product_informations['product_attribute_id']) && $product_informations['product_attribute_id']) {
            $combination = new Combination($product_informations['product_attribute_id']);
            if (!Validate::isLoadedObject($combination)) {
                die(json_encode(array(
                    'result' => false,
                    'error' => $this->trans('The combination object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
                )));
            }
        }

        // Total method
        $total_method = Cart::BOTH_WITHOUT_SHIPPING;

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

        // Save context (in order to apply cart rule)
        $this->context->cart = $cart;
        $this->context->customer = new Customer($order->id_customer);

        // always add taxes even if there are not displayed to the customer
        $use_taxes = true;

        $initial_product_price_tax_incl = Product::getPriceStatic($product->id, $use_taxes, isset($combination) ? $combination->id : null, 2, null, false, true, 1,
            false, $order->id_customer, $cart->id, $order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)});

        // Creating specific price if needed
        if ($product_informations['product_price_tax_incl'] != $initial_product_price_tax_incl) {
            $specific_price = new SpecificPrice();
            $specific_price->id_shop = 0;
            $specific_price->id_shop_group = 0;
            $specific_price->id_currency = 0;
            $specific_price->id_country = 0;
            $specific_price->id_group = 0;
            $specific_price->id_customer = $order->id_customer;
            $specific_price->id_product = $product->id;
            if (isset($combination)) {
                $specific_price->id_product_attribute = $combination->id;
            } else {
                $specific_price->id_product_attribute = 0;
            }
            $specific_price->price = $product_informations['product_price_tax_excl'];
            $specific_price->from_quantity = 1;
            $specific_price->reduction = 0;
            $specific_price->reduction_type = 'amount';
            $specific_price->reduction_tax = 0;
            $specific_price->from = '0000-00-00 00:00:00';
            $specific_price->to = '0000-00-00 00:00:00';
            $specific_price->add();
        }

        // Add product to cart
        $update_quantity = $cart->updateQty($product_informations['product_quantity'], $product->id, isset($product_informations['product_attribute_id']) ? $product_informations['product_attribute_id'] : null,
            isset($combination) ? $combination->id : null, 'up', 0, new Shop($cart->id_shop));

        if ($update_quantity < 0) {
            // If product has attribute, minimal quantity is set with minimal quantity of attribute
            $minimal_quantity = ($product_informations['product_attribute_id']) ? Attribute::getAttributeMinimalQty($product_informations['product_attribute_id']) : $product->minimal_quantity;
            die(json_encode(array('error' => $this->trans('You must add %d minimum quantity', array('%d' => $minimal_quantity), 'Admin.Orderscustomers.Notification'))));
        } elseif (!$update_quantity) {
            die(json_encode(array('error' => $this->trans('You already have the maximum quantity available for this product.', array(), 'Admin.Orderscustomers.Notification'))));
        }

        // If order is valid, we can create a new invoice or edit an existing invoice
        if ($order->hasInvoice()) {
            $order_invoice = new OrderInvoice($product_informations['invoice']);

            // Create new invoice
            if ($order_invoice->id == 0) {
                // If we create a new invoice, we calculate shipping cost
                $total_method = Cart::BOTH;

                // Create Cart rule in order to make free shipping
                if (isset($invoice_informations['free_shipping']) && $invoice_informations['free_shipping']) {
                    $cart_rule = new CartRule();
                    $cart_rule->id_customer = $order->id_customer;
                    $cart_rule->name = array(
                        Configuration::get('PS_LANG_DEFAULT') => $this->trans('[Generated] CartRule for Free Shipping', array(), 'Admin.Orderscustomers.Notification')
                    );
                    $cart_rule->date_from = date('Y-m-d H:i:s', time());
                    $cart_rule->date_to = date('Y-m-d H:i:s', time() + 24 * 3600);
                    $cart_rule->quantity = 1;
                    $cart_rule->quantity_per_user = 1;
                    $cart_rule->minimum_amount_currency = $order->id_currency;
                    $cart_rule->reduction_currency = $order->id_currency;
                    $cart_rule->free_shipping = true;
                    $cart_rule->active = 1;
                    $cart_rule->add();

                    // Add cart rule to cart and in order
                    $cart->addCartRule($cart_rule->id);
                    $values = array(
                        'tax_incl' => $cart_rule->getContextualValue(true),
                        'tax_excl' => $cart_rule->getContextualValue(false)
                    );
                    $order->addCartRule($cart_rule->id, $cart_rule->name[Configuration::get('PS_LANG_DEFAULT')], $values);
                }

                $order_invoice->id_order = $order->id;
                if ($order_invoice->number) {
                    Configuration::updateValue('PS_INVOICE_START_NUMBER', false, false, null, $order->id_shop);
                } else {
                    $order_invoice->number = Order::getLastInvoiceNumber() + 1;
                }

                $invoice_address = new Address((int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)});

                $carrier = new Carrier((int)$order->id_carrier);

                $tax_calculator = $carrier->getTaxCalculator($invoice_address);

                $order_invoice->total_paid_tax_excl = Tools::ps_round((float)$cart->getOrderTotal(false, $total_method), 4);
                $order_invoice->total_paid_tax_incl = Tools::ps_round((float)$cart->getOrderTotal($use_taxes, $total_method), 4);
                $order_invoice->total_products = (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $order_invoice->total_products_wt = (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);
                $order_invoice->total_shipping_tax_excl = (float)$cart->getTotalShippingCost(null, false);
                $order_invoice->total_shipping_tax_incl = (float)$cart->getTotalShippingCost();

                $order_invoice->total_wrapping_tax_excl = abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                $order_invoice->total_wrapping_tax_incl = abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order_invoice->shipping_tax_computation_method = (int)$tax_calculator->computation_method;

                // Update current order field, only shipping because other field is updated later
                $order->total_shipping += $order_invoice->total_shipping_tax_incl;
                $order->total_shipping_tax_excl += $order_invoice->total_shipping_tax_excl;
                $order->total_shipping_tax_incl += ($use_taxes) ? $order_invoice->total_shipping_tax_incl : $order_invoice->total_shipping_tax_excl;

                $order->total_wrapping += abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order->total_wrapping_tax_excl += abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                $order->total_wrapping_tax_incl += abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order_invoice->add();

                $order_invoice->saveCarrierTaxCalculator($tax_calculator->getTaxesAmount($order_invoice->total_shipping_tax_excl));

                $order_carrier = new OrderCarrier();
                $order_carrier->id_order = (int)$order->id;
                $order_carrier->id_carrier = (int)$order->id_carrier;
                $order_carrier->id_order_invoice = (int)$order_invoice->id;
                $order_carrier->weight = (float)$cart->getTotalWeight();
                $order_carrier->shipping_cost_tax_excl = (float)$order_invoice->total_shipping_tax_excl;
                $order_carrier->shipping_cost_tax_incl = ($use_taxes) ? (float)$order_invoice->total_shipping_tax_incl : (float)$order_invoice->total_shipping_tax_excl;
                $order_carrier->add();
            } else {
                // Update current invoice
                $order_invoice->total_paid_tax_excl += Tools::ps_round((float)($cart->getOrderTotal(false, $total_method)), 4);
                $order_invoice->total_paid_tax_incl += Tools::ps_round((float)($cart->getOrderTotal($use_taxes, $total_method)), 4);
                $order_invoice->total_products += (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $order_invoice->total_products_wt += (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);
                $order_invoice->update();
            }
        }

        // Create Order detail information
        $order_detail = new OrderDetail();
        $order_detail->createList($order, $cart, $order->getCurrentOrderState(), $cart->getProducts(), (isset($order_invoice) ? $order_invoice->id : 0), $use_taxes, (int)Tools::getValue('add_product_warehouse'), (int)$product_informations['id_colaborador']);

        // update totals amount of order
        $order->total_products += (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $order->total_products_wt += (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);

        $order->total_paid += Tools::ps_round((float)($cart->getOrderTotal(true, $total_method)), 3);
        $order->total_paid_tax_excl += Tools::ps_round((float)($cart->getOrderTotal(false, $total_method)), 3);
        $order->total_paid_tax_incl += Tools::ps_round((float)($cart->getOrderTotal($use_taxes, $total_method)), 3);

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

        StockAvailable::synchronize($product->id);

        // Update weight SUM
//        $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
//        if (Validate::isLoadedObject($order_carrier)) {
//            $order_carrier->weight = (float)$order->getTotalWeight();
//            if ($order_carrier->update()) {
//                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
//            }
//        }

        // Update Tax lines
        $order_detail->updateTaxAmount($order);

        // Delete specific price if exists
        if (isset($specific_price)) {
            $specific_price->delete();
        }

        $products = $this->getProducts($order);

        // Get the last product
        $product = end($products);
        $resume = OrderSlip::getProductSlipResume((int)$product['id_order_detail']);
        $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
        $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
        $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
        $product['return_history'] = OrderReturn::getProductReturnDetail((int)$product['id_order_detail']);
        $product['refund_history'] = OrderSlip::getProductSlipDetail((int)$product['id_order_detail']);
        if ($product['id_warehouse'] != 0) {
            $warehouse = new Warehouse((int)$product['id_warehouse']);
            $product['warehouse_name'] = $warehouse->name;
            $warehouse_location = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
            if (!empty($warehouse_location)) {
                $product['warehouse_location'] = $warehouse_location;
            } else {
                $product['warehouse_location'] = false;
            }
        } else {
            $product['warehouse_name'] = '--';
            $product['warehouse_location'] = false;
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }
//        d($new_cart_rules);
        $order = $order->refreshShippingCost();

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'product' => $product,
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'can_edit' => $this->access('edit'),
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex,
            'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
        ));

        $this->sendChangedNotification($order);
        $new_cart_rules = Context::getContext()->cart->getCartRules();
        sort($old_cart_rules);
        sort($new_cart_rules);
        $result = array_diff($new_cart_rules, $old_cart_rules);
        $refresh = false;

        $res = true;
        foreach ($result as $cart_rule) {
            $refresh = true;
            // Create OrderCartRule
            $rule = new CartRule($cart_rule['id_cart_rule']);
            $values = array(
                'tax_incl' => $rule->getContextualValue(true),
                'tax_excl' => $rule->getContextualValue(false)
            );
            $order_cart_rule = new OrderCartRule();
            $order_cart_rule->id_order = $order->id;
            $order_cart_rule->id_cart_rule = $cart_rule['id_cart_rule'];
            $order_cart_rule->id_order_invoice = $order_invoice->id;
            $order_cart_rule->name = $cart_rule['name'];
            $order_cart_rule->value = $values['tax_incl'];
            $order_cart_rule->value_tax_excl = $values['tax_excl'];
            $res &= $order_cart_rule->add();

            $order->total_discounts += $order_cart_rule->value;
            $order->total_discounts_tax_incl += $order_cart_rule->value;
            $order->total_discounts_tax_excl += $order_cart_rule->value_tax_excl;
            $order->total_paid -= $order_cart_rule->value;
            $order->total_paid_tax_incl -= $order_cart_rule->value;
            $order->total_paid_tax_excl -= $order_cart_rule->value_tax_excl;
        }

        // Update Order
        $res &= $order->update();

        $order->pagado = $order->getTotalPaid();

        die(json_encode(array(
            'result' => true,
            'view' => $this->createTemplate('_product_line.tpl')->fetch(),
            'can_edit' => $this->access('add'),
            'order' => $order,
            'invoices' => $invoice_array,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html' => $this->createTemplate('_shipping.tpl')->fetch(),
            'discount_form_html' => $this->createTemplate('_discount_form.tpl')->fetch(),
            'refresh' => $refresh
        )));
    }

    public function sendChangedNotification(Order $order = null)
    {
        if (is_null($order)) {
            $order = new Order(Tools::getValue('id_order'));
        }

        Hook::exec('actionOrderEdited', array('order' => $order));
    }

    public function ajaxProcessLoadProductInformation()
    {

        $order_detail = new OrderDetail(Tools::getValue('id_order_detail'));

        if (!Validate::isLoadedObject($order_detail)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The OrderDetail object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        $product = new Product($order_detail->product_id);
        if (!Validate::isLoadedObject($product)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The product object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        $address = new Address(Tools::getValue('id_address'));
//        d($address);
//        if (!Validate::isLoadedObject($address)) {
//            die(json_encode(array(
//                'result' => false,
//                'error' => $this->trans('The address object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
//            )));
//        }

        die(json_encode(array(
            'result' => true,
            'product' => $product,
            'tax_rate' => $product->getTaxesRate($address),
            'price_tax_incl' => Product::getPriceStatic($product->id, true, $order_detail->product_attribute_id, 2),
            'price_tax_excl' => Product::getPriceStatic($product->id, false, $order_detail->product_attribute_id, 2),
            'reduction_percent' => $order_detail->reduction_percent
        )));
    }

    public function ajaxProcessEditProductOnOrder()
    {
        // Return value
        $res = true;

        $order = new Order((int)Tools::getValue('id_order'));
        $order_detail = new OrderDetail((int)Tools::getValue('product_id_order_detail'));
        if (Tools::isSubmit('product_invoice')) {
            $order_invoice = new OrderInvoice((int)Tools::getValue('product_invoice'));
        }

        // Check fields validity
        $this->doEditProductValidation($order_detail, $order, isset($order_invoice) ? $order_invoice : null);

        // If multiple product_quantity, the order details concern a product customized
        $product_quantity = 0;
        if (is_array(Tools::getValue('product_quantity'))) {
            foreach (Tools::getValue('product_quantity') as $id_customization => $qty) {
                // Update quantity of each customization
                Db::getInstance()->update('customization', array('quantity' => (float)$qty), 'id_customization = '.(int)$id_customization);
                // Calculate the real quantity of the product
                $product_quantity += $qty;
            }
        } else {
            $product_quantity = Tools::getValue('product_quantity');
        }

        $product_price_tax_incl = Tools::ps_round(Tools::getValue('product_price_tax_incl'), 4);
        $product_price_tax_excl = Tools::ps_round(Tools::getValue('product_price_tax_excl'), 4);
        $total_products_tax_incl = $product_price_tax_incl * $product_quantity;
        $total_products_tax_excl = $product_price_tax_excl * $product_quantity;

        // Calculate differences of price (Before / After)
        $diff_price_tax_incl = $total_products_tax_incl - $order_detail->total_price_tax_incl;
        $diff_price_tax_excl = $total_products_tax_excl - $order_detail->total_price_tax_excl;

        // Apply change on OrderInvoice
        if (isset($order_invoice)) {
            // If OrderInvoice to use is different, we update the old invoice and new invoice
            if ($order_detail->id_order_invoice != $order_invoice->id) {
                $old_order_invoice = new OrderInvoice($order_detail->id_order_invoice);
                // We remove cost of products
                $old_order_invoice->total_products -= $order_detail->total_price_tax_excl;
                $old_order_invoice->total_products_wt -= $order_detail->total_price_tax_incl;

                $old_order_invoice->total_paid_tax_excl -= $order_detail->total_price_tax_excl;
                $old_order_invoice->total_paid_tax_incl -= $order_detail->total_price_tax_incl;

                $res &= $old_order_invoice->update();

                $order_invoice->total_products += $order_detail->total_price_tax_excl;
                $order_invoice->total_products_wt += $order_detail->total_price_tax_incl;

                $order_invoice->total_paid_tax_excl += $order_detail->total_price_tax_excl;
                $order_invoice->total_paid_tax_incl += $order_detail->total_price_tax_incl;

                $order_detail->id_order_invoice = $order_invoice->id;
            }
        }

        if ($diff_price_tax_incl != 0 && $diff_price_tax_excl != 0) {
            $order_detail->unit_price_tax_excl = $product_price_tax_excl;
            $order_detail->unit_price_tax_incl = $product_price_tax_incl;

            $order_detail->total_price_tax_incl += $diff_price_tax_incl;
            $order_detail->total_price_tax_excl += $diff_price_tax_excl;

            if (isset($order_invoice)) {
                // Apply changes on OrderInvoice
                $order_invoice->total_products += $diff_price_tax_excl;
                $order_invoice->total_products_wt += $diff_price_tax_incl;

                $order_invoice->total_paid_tax_excl += $diff_price_tax_excl;
                $order_invoice->total_paid_tax_incl += $diff_price_tax_incl;
            }

            // Apply changes on Order
            $order = new Order($order_detail->id_order);
            $order->total_products += $diff_price_tax_excl;
            $order->total_products_wt += $diff_price_tax_incl;

            $order->total_paid += $diff_price_tax_incl;
            $order->total_paid_tax_excl += $diff_price_tax_excl;
            $order->total_paid_tax_incl += $diff_price_tax_incl;

            $res &= $order->update();
        }

        $old_quantity = $order_detail->product_quantity;

        $order_detail->product_quantity = $product_quantity;
        $order_detail->reduction_percent = 0;

        // update taxes
        $res &= $order_detail->updateTaxAmount($order);

        // Save order detail
        $res &= $order_detail->update();
//        d($order->getIdOrderCarrier());
        // Update weight SUM
        $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
        if (Validate::isLoadedObject($order_carrier)) {
            $order_carrier->weight = (float)$order->getTotalWeight();
            $res &= $order_carrier->update();
            if ($res) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
            }
        }

        // Save order invoice
        if (isset($order_invoice)) {
            $res &= $order_invoice->update();
        }

        // Update product available quantity
        StockAvailable::updateQuantity($order_detail->product_id, $order_detail->product_attribute_id, ($old_quantity - $order_detail->product_quantity), $order->id_shop);

        $products = $this->getProducts($order);
        // Get the last product
        $product = $products[$order_detail->id];
        $resume = OrderSlip::getProductSlipResume($order_detail->id);
        $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
        $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
        $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
        $product['refund_history'] = OrderSlip::getProductSlipDetail($order_detail->id);
        if ($product['id_warehouse'] != 0) {
            $warehouse = new Warehouse((int)$product['id_warehouse']);
            $product['warehouse_name'] = $warehouse->name;
            $warehouse_location = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
            if (!empty($warehouse_location)) {
                $product['warehouse_location'] = $warehouse_location;
            } else {
                $product['warehouse_location'] = false;
            }
        } else {
            $product['warehouse_name'] = '--';
            $product['warehouse_location'] = false;
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }

        $order = $order->refreshShippingCost();


        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'product' => $product,
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'can_edit' => $this->access('edit'),
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex,
            'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
        ));

        if (!$res) {
            die(json_encode(array(
                'result' => $res,
                'error' => $this->trans('An error occurred while editing the product line.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }


        if (is_array(Tools::getValue('product_quantity'))) {
            $view = $this->createTemplate('_customized_data.tpl')->fetch();
        } else {
            $view = $this->createTemplate('_product_line.tpl')->fetch();
        }

        $this->sendChangedNotification($order);

        $order->pagado = $order->getTotalPaid();

        die(json_encode(array(
            'result' => $res,
            'view' => $view,
            'can_edit' => $this->access('add'),
            'invoices_collection' => $invoice_collection,
            'order' => $order,
            'invoices' => $invoice_array,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html' => $this->createTemplate('_shipping.tpl')->fetch(),
            'customized_product' => is_array(Tools::getValue('product_quantity'))
        )));
    }

    public function ajaxProcessDeleteProductLine()
    {
        $res = true;

        $order_detail = new OrderDetail((int)Tools::getValue('id_order_detail'));
        $order = new Order((int)Tools::getValue('id_order'));

        $this->doDeleteProductLineValidation($order_detail, $order);

        // Update OrderInvoice of this OrderDetail
        if ($order_detail->id_order_invoice != 0) {
            $order_invoice = new OrderInvoice($order_detail->id_order_invoice);
            $order_invoice->total_paid_tax_excl -= $order_detail->total_price_tax_excl;
            $order_invoice->total_paid_tax_incl -= $order_detail->total_price_tax_incl;
            $order_invoice->total_products -= $order_detail->total_price_tax_excl;
            $order_invoice->total_products_wt -= $order_detail->total_price_tax_incl;
            $res &= $order_invoice->update();
        }

        // Update Order
        $order->total_paid -= $order_detail->total_price_tax_incl;
        $order->total_paid_tax_incl -= $order_detail->total_price_tax_incl;
        $order->total_paid_tax_excl -= $order_detail->total_price_tax_excl;
        $order->total_products -= $order_detail->total_price_tax_excl;
        $order->total_products_wt -= $order_detail->total_price_tax_incl;

        $res &= $order->update();

        // Reinject quantity in stock
        $this->reinjectQuantity($order_detail, $order_detail->product_quantity, true);

        // Update weight SUM
        $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
        if (Validate::isLoadedObject($order_carrier)) {
            $order_carrier->weight = (float)$order->getTotalWeight();
            $res &= $order_carrier->update();
            if ($res) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
            }
        }

        if (!$res) {
            die(json_encode(array(
                'result' => $res,
                'error' => $this->trans('An error occurred while attempting to delete the product line.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }

        $order = $order->refreshShippingCost();

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex
        ));

        $this->sendChangedNotification($order);
        $order->pagado = $order->getTotalPaid();

        die(json_encode(array(
            'result' => $res,
            'order' => $order,
            'invoices' => $invoice_array,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html' => $this->createTemplate('_shipping.tpl')->fetch()
        )));
    }

    protected function doEditProductValidation(OrderDetail $order_detail, Order $order, OrderInvoice $order_invoice = null)
    {
        if (!Validate::isLoadedObject($order_detail)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The Order Detail object could not be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if (!empty($order_invoice) && !Validate::isLoadedObject($order_invoice)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The invoice object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if (!Validate::isLoadedObject($order)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The order object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if ($order_detail->id_order != $order->id) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('You cannot edit the order detail for this order.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        // We can't edit a delivered order
        if ($order->hasBeenDelivered()) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('You cannot edit a delivered order.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if (!empty($order_invoice) && $order_invoice->id_order != Tools::getValue('id_order')) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('You cannot use this invoice for the order', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        // Clean price
        $product_price_tax_incl = str_replace(',', '.', Tools::getValue('product_price_tax_incl'));
        $product_price_tax_excl = str_replace(',', '.', Tools::getValue('product_price_tax_excl'));

//        d($product_price_tax_excl);
        if (!Validate::isPrice($product_price_tax_incl) || !Validate::isPrice($product_price_tax_excl)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('Invalid price', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if (!is_array(Tools::getValue('product_quantity')) && !Validate::isUnsignedFloat(Tools::getValue('product_quantity'))) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('Invalid quantity', array(), 'Admin.Orderscustomers.Notification')
            )));
        } elseif (is_array(Tools::getValue('product_quantity'))) {
            foreach (Tools::getValue('product_quantity') as $qty) {
                if (!Validate::isUnsignedFloat($qty)) {
                    die(json_encode(array(
                        'result' => false,
                        'error' => $this->trans('Invalid quantity', array(), 'Admin.Orderscustomers.Notification')
                    )));
                }
            }
        }

    }

    protected function doDeleteProductLineValidation(OrderDetail $order_detail, Order $order)
    {
        if (!Validate::isLoadedObject($order_detail)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The Order Detail object could not be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if (!Validate::isLoadedObject($order)) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('The order object cannot be loaded.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        if ($order_detail->id_order != $order->id) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('You cannot delete the order detail.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        // We can't edit a delivered order
        if ($order->hasBeenDelivered()) {
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('You cannot edit a delivered order.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getProducts($order)
    {
        $products = $order->getProducts();

        foreach ($products as &$product) {
            if ($product['image'] != null) {
                $name = 'product_mini_'.(int)$product['product_id'].(isset($product['product_attribute_id']) ? '_'.(int)$product['product_attribute_id'] : '').'.jpg';
                // generate image cache, only for back office
                $product['image_tag'] = ImageManager::thumbnail(_PS_IMG_DIR_.'p/'.$product['image']->getExistingImgPath().'.jpg', $name, 45, 'jpg');
                if (file_exists(_PS_TMP_IMG_DIR_.$name)) {
                    $product['image_size'] = getimagesize(_PS_TMP_IMG_DIR_.$name);
                } else {
                    $product['image_size'] = false;
                }
            }
        }

        ksort($products);

        return $products;
    }

    /**
     * @param OrderDetail $order_detail
     * @param int $qty_cancel_product
     * @param bool $delete
     */
    protected function reinjectQuantity($order_detail, $qty_cancel_product, $delete = false)
    {
        // Reinject product
        $reinjectable_quantity = (float)$order_detail->product_quantity - (float)$order_detail->product_quantity_reinjected;
        $quantity_to_reinject = $qty_cancel_product > $reinjectable_quantity ? $reinjectable_quantity : $qty_cancel_product;
        // @since 1.5.0 : Advanced Stock Management
        $product_to_inject = new Product($order_detail->product_id, false, (int)$this->context->language->id, (int)$order_detail->id_shop);

        $product = new Product($order_detail->product_id, false, (int)$this->context->language->id, (int)$order_detail->id_shop);

        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management && $order_detail->id_warehouse != 0) {
            $manager = StockManagerFactory::getManager();
            $movements = StockMvt::getNegativeStockMvts(
                $order_detail->id_order,
                $order_detail->product_id,
                $order_detail->product_attribute_id,
                $quantity_to_reinject
            );
            $left_to_reinject = $quantity_to_reinject;
            foreach ($movements as $movement) {
                if ($left_to_reinject > $movement['physical_quantity']) {
                    $quantity_to_reinject = $movement['physical_quantity'];
                }

                $left_to_reinject -= $quantity_to_reinject;
                if (Pack::isPack((int)$product->id)) {
                    // Gets items
                    if ($product->pack_stock_type == Pack::STOCK_TYPE_PRODUCTS_ONLY
                        || $product->pack_stock_type == Pack::STOCK_TYPE_PACK_BOTH
                        || ($product->pack_stock_type == Pack::STOCK_TYPE_DEFAULT
                            && Configuration::get('PS_PACK_STOCK_TYPE') > 0)
                    ) {
                        $products_pack = Pack::getItems((int)$product->id, (int)Configuration::get('PS_LANG_DEFAULT'));
                        // Foreach item
                        foreach ($products_pack as $product_pack) {
                            if ($product_pack->advanced_stock_management == 1) {
                                $manager->addProduct(
                                    $product_pack->id,
                                    $product_pack->id_pack_product_attribute,
                                    new Warehouse($movement['id_warehouse']),
                                    $product_pack->pack_quantity * $quantity_to_reinject,
                                    null,
                                    $movement['price_te'],
                                    true
                                );
                            }
                        }
                    }

                    if ($product->pack_stock_type == Pack::STOCK_TYPE_PACK_ONLY
                        || $product->pack_stock_type == Pack::STOCK_TYPE_PACK_BOTH
                        || ($product->pack_stock_type == Pack::STOCK_TYPE_DEFAULT
                            && (Configuration::get('PS_PACK_STOCK_TYPE') == Pack::STOCK_TYPE_PACK_ONLY
                                || Configuration::get('PS_PACK_STOCK_TYPE') == Pack::STOCK_TYPE_PACK_BOTH)
                        )
                    ) {
                        $manager->addProduct(
                            $order_detail->product_id,
                            $order_detail->product_attribute_id,
                            new Warehouse($movement['id_warehouse']),
                            $quantity_to_reinject,
                            null,
                            $movement['price_te'],
                            true
                        );
                    }
                } else {
                    $manager->addProduct(
                        $order_detail->product_id,
                        $order_detail->product_attribute_id,
                        new Warehouse($movement['id_warehouse']),
                        $quantity_to_reinject,
                        null,
                        $movement['price_te'],
                        true
                    );
                }
            }

            $id_product = $order_detail->product_id;
            if ($delete) {
                $order_detail->delete();
            }
            StockAvailable::synchronize($id_product);
        } elseif ($order_detail->id_warehouse == 0) {
            StockAvailable::updateQuantity(
                $order_detail->product_id,
                $order_detail->product_attribute_id,
                $quantity_to_reinject,
                $order_detail->id_shop,
                true,
                array(
                    'id_order' => $order_detail->id_order,
                    'id_stock_mvt_reason' => Configuration::get('PS_STOCK_CUSTOMER_RETURN_REASON')
                )
            );

            // sync all stock
            (new StockManager())->updatePhysicalProductQuantity(
                (int)$order_detail->id_shop,
                (int)Configuration::get('PS_OS_ERROR'),
                (int)Configuration::get('PS_OS_CANCELED'),
                null,
                (int)$order_detail->id_order
            );

            if ($delete) {
                $order_detail->delete();
            }
        } else {
            $this->errors[] = $this->trans('This product cannot be re-stocked.', array(), 'Admin.Orderscustomers.Notification');
        }
    }

    /**
     * @param OrderInvoice $order_invoice
     * @param float $value_tax_incl
     * @param float $value_tax_excl
     */
    protected function applyDiscountOnInvoice($order_invoice, $value_tax_incl, $value_tax_excl)
    {
        // Update OrderInvoice
        $order_invoice->total_discount_tax_incl += $value_tax_incl;
        $order_invoice->total_discount_tax_excl += $value_tax_excl;
        $order_invoice->total_paid_tax_incl -= $value_tax_incl;
        $order_invoice->total_paid_tax_excl -= $value_tax_excl;
        $order_invoice->update();
    }

    public function ajaxProcessChangePaymentMethod()
    {
        $customer = new Customer(Tools::getValue('id_customer'));
        $modules = Module::getAuthorizedModules($customer->id_default_group);
        $authorized_modules = array();

        if (!Validate::isLoadedObject($customer) || !is_array($modules)) {
            die(json_encode(array('result' => false)));
        }

        foreach ($modules as $module) {
            $authorized_modules[] = (int)$module['id_module'];
        }

        $payment_modules = array();

        foreach (PaymentModule::getInstalledPaymentModules() as $p_module) {
            if (in_array((int)$p_module['id_module'], $authorized_modules)) {
                $payment_modules[] = Module::getInstanceById((int)$p_module['id_module']);
            }
        }

        $this->context->smarty->assign(array(
            'payment_modules' => $payment_modules,
        ));

        die(json_encode(array(
            'result' => true,
            'view' => $this->createTemplate('_select_payment.tpl')->fetch(),
        )));
    }

    /**
     *
     * This method allow to add image information on a package detail
     * @param array &pack_item
     */
    protected function setProductImageInformations(&$pack_item)
    {
        if (isset($pack_item['id_product_attribute']) && $pack_item['id_product_attribute']) {
            $id_image = Db::getInstance()->getValue('
                SELECT `image_shop`.id_image
                FROM `'._DB_PREFIX_.'product_attribute_image` pai'.
                Shop::addSqlAssociation('image', 'pai', true).'
                WHERE id_product_attribute = '.(int)$pack_item['id_product_attribute']);
        }

        if (!isset($id_image) || !$id_image) {
            $id_image = Db::getInstance()->getValue('
                SELECT `image_shop`.id_image
                FROM `'._DB_PREFIX_.'image` i'.
                Shop::addSqlAssociation('image', 'i', true, 'image_shop.cover=1').'
                WHERE i.id_product = '.(int)$pack_item['id_product']
            );
        }

        $pack_item['image'] = null;
        $pack_item['image_size'] = null;

        if ($id_image) {
            $pack_item['image'] = new Image($id_image);
        }
    }

    /**
     * Get available carrier list for an order
     * @param Object $order
     * @return array $delivery_option_list_formated
     */
    protected function getCarrierList($order)
    {
        $cart = $this->context->cart;
        $address = new Address((int) $cart->id_address_delivery);
        return Carrier::getCarriersForOrder(Address::getZoneById((int) $address->id), null, $cart);
    }

    public function ajaxProcessGetOrdersTimes(){
        $ordenes = Order::getOrdersTimes();
        die(Tools::jsonEncode(array('errors' => false, 'ordenes' => $ordenes)));
    }

    public function initProcess()
    {
        parent::initProcess();
//        $action = Tools::getValue('submitAction');
//        $this->action = $action;

    }

    public function ajaxProcessRealizarXMLComprobante(){

//        d(Tools::getAllValues());
        $tienda_actual = new Shop((int)$this->context->shop->id); //
        $nombre_virtual_uri = $tienda_actual->virtual_uri;
        $tipo_comprobante = Tools::getValue("tipo_comprobante");
        $arr = Certificadofe::getCertificado();
        if ($arr && (int)$arr > 0){
            $objCerti = new Certificadofe((int)$arr); // buscar el certificado
            if (!(bool)$objCerti->active){
                $this->errors[] = "La ".$tipo_comprobante." no se pudo enviar: No hay un certificado valido";
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
        }else{
            $this->errors[] = "La ".$tipo_comprobante." no se pudo enviar: No hay un certificado valido";
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

        if (($id_order = Tools::getValue("id_order"))){

            $order = new Order((int)$id_order);
            $id_cliente = Tools::getValue("id_customer");

            if ($cliente = Customer::getCustomerByDocumento(Tools::getValue('nro_documento'))){
                $id_cliente = $cliente['id_customer'];
            }

            if ($id_cliente){
                $order->id_customer = $id_cliente;
                $customer = new Customer((int)$id_cliente);
                $customer->direccion = Tools::getValue('direccion') !== ""?Tools::getValue('direccion'):"no hay direccion";
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

                $customer->firstname = Tools::getValue('nombre');
                $customer->lastname = "";
                $customer->email = '';
                $pass = $this->get('hashing')->hash("123456789", _COOKIE_KEY_);
                $customer->passwd = $pass;
                $customer->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_FRONT').'minutes'));
                $customer->newsletter = 0;
                $customer->optin = 0;
                $customer->outstanding_allow_amount = 0;
                $customer->show_public_prices = 0;
                $customer->max_payment_days = 0;
                $customer->secure_key = md5(uniqid(rand(), true));
                $customer->active = 1;
                $customer->is_guest = 0;
                $customer->deleted = 0;
                $customer->id_document = Tools::getValue('tipo_documento');
                $customer->num_document = Tools::getValue('nro_documento');
                $customer->telefono = "";
                $customer->direccion = Tools::getValue('direccion') !== ""?Tools::getValue('direccion'):"no hay direccion";
                $customer->add();
                $customer->updateGroup(array($customer->id_default_group));

                $order->id_customer = $customer->id;
            }
            $order->update();

            $prods =  Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                        SELECT *
                        FROM `'._DB_PREFIX_.'order_detail` od
                        WHERE od.`id_order` = '.(int)$order->id);

            foreach ($prods as $prod) {
                if ((int)$prod['id_tax_rules_group'] == 0){
                    $respuesta["respuesta"] = "error";
                    $this->errors[] = "HAY PRODUCTOS SIN IGV";
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }
            }


            if ($order->current_state == (int)ConfigurationCore::get("PS_OS_PAYMENT")){

                $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
                if (!empty($doc)){
                    $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
                }else{
                    $objComprobantes = new PosOrdercomprobantes();
                    $objComprobantes->fecha_envio_comprobante = date('Y-m-d H:i:s');
                }

                // comprobanr si ya existe una numeracion para el comprobante
                if (!$objComprobantes->numero_comprobante && $objComprobantes->numero_comprobante == ""){

                    $objComprobantes->id_order = $order->id;
                    $objComprobantes->tipo_documento_electronico = $tipo_comprobante;
                    $objComprobantes->sub_total = $order->total_paid_tax_excl;
                    $objComprobantes->impuesto = (float)($order->total_paid_tax_incl - $order->total_paid_tax_excl);
                    $objComprobantes->total = $order->total_paid_tax_incl;

                    //creamos la numeracion
                    $numeracion_documento = NumeracionDocumento::getNumTipoDoc($tipo_comprobante);
                    if (empty($numeracion_documento)){
                        $this->errors[] = "No existe numeración cree una <a href='index.php?controller=AdminNumeracionDocumentos&addnumeracion_documentos&token=".Tools::getAdminTokenLite("AdminNumeracionDocumentos")."&nombre=".$tipo_comprobante."' target='_blank'>&nbsp; -> Crear Numeración para los Comprobantes Electrónicos</a>";
                        return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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

                $date1 = new DateTime($objComprobantes->fecha_envio_comprobante);
                $date2 = new DateTime(date('Y-m-d'));
                $diff = $date1->diff($date2);

                $fecha_actual = date("d-m-Y");
                $dias_posteriores = date("d-m-Y",strtotime($fecha_actual."- 7 days"));

                if ($diff->d > 7) {
                    $this->errors[] = "No puede enviar un documento con fecha anterior a ".$dias_posteriores;
                    return die(Tools::jsonEncode(array('respuesta' => 'error', 'msg' =>  $this->errors)));
                }


//            d($numero_comprobante);

                // armamos la numeracion
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
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
                    return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
                }
                if (!empty($doc)){
                    $objComprobantes->update();
                }else{
                    $objComprobantes->add();
                }


                $datos_comprobante = Apisunat_2_1::crear_cabecera($emisor, $order, $objComprobantes, $tipo_documento, $receptor);

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

//                    $order->setCurrentState(2, $this->context->employee->id); //estado 2 pago aceptado temporal

                if ($tipo_comprobante == "Factura"){

                    $resp = ProcesarComprobante::procesar_factura($datos_comprobante, $objComprobantes, $rutas);

                    $objComprobantes->ruta_ticket =  $ruta."Ticket-".$monbre_archivo;
                    $objComprobantes->ruta_pdf_a4 =  $ruta_a4."A4-".$monbre_archivo;
                    $objComprobantes->hash_cpe =  $resp["hash_cpe"];
                    $objComprobantes->ruta_xml =  $rutas["ruta_xml"].".zip";
                    $objComprobantes->hash_cdr =  $resp["hash_cdr"];
                    $objComprobantes->ruta_cdr =  $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
                    $objComprobantes->cod_sunat =  $resp["cod_sunat"];
                    $objComprobantes->msj_sunat =  $resp["msj_sunat"];
                    $objComprobantes->update();

                    return die(json_encode($resp));

                }else if ($tipo_comprobante == "Boleta"){

                    $resp = ProcesarComprobante::procesar_boleta($datos_comprobante, $objComprobantes, $rutas);

                    $objComprobantes->hash_cpe =  $resp["hash_cpe"];
                    $objComprobantes->ruta_xml =  $rutas["ruta_xml"].".zip";
                    $objComprobantes->hash_cdr =  $resp["hash_cdr"];
                    $objComprobantes->ruta_cdr =  $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
                    $objComprobantes->cod_sunat =  $resp["cod_sunat"];
                    $objComprobantes->msj_sunat =  $resp["msj_sunat"];
                    $objComprobantes->update();

                    return die(json_encode($resp));
                }else{
                    return die(json_encode(false));
                }
            }else{
                $this->errors[] = $this->trans('Error: No tiene un estado válido!!', array(), 'Admin.Orderscustomers.Notification');
                return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
            }
        }
        //error si no existe la venta
        else{
            $this->errors[] = $this->trans('Error no existe una venta!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

    }

    public function ajaxProcessGuardarClienteOrder(){

//        d(Tools::getAllValues());

        if (($id_order = Tools::getValue("id_order"))){
            $order = new Order((int)$id_order);
            $id_cliente = Tools::getValue("id_customer");

            if ($cliente = Customer::getCustomerByDocumento(Tools::getValue('nro_documento'))){
                $id_cliente = $cliente['id_customer'];
            }
            if ($id_cliente){
                $order->id_customer = $id_cliente;
                $customer = new Customer((int)$id_cliente);
                $customer->direccion = Tools::getValue('direccion') !== ""?Tools::getValue('direccion'):"no hay direccion";
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

                $customer->firstname = Tools::getValue('nombre');
                $customer->lastname = "";
                $customer->email = '';
                $pass = $this->get('hashing')->hash("123456789", _COOKIE_KEY_);
                $customer->passwd = $pass;
                $customer->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_FRONT').'minutes'));
                $customer->newsletter = 0;
                $customer->optin = 0;
                $customer->outstanding_allow_amount = 0;
                $customer->show_public_prices = 0;
                $customer->max_payment_days = 0;
                $customer->secure_key = md5(uniqid(rand(), true));
                $customer->active = 1;
                $customer->is_guest = 0;
                $customer->deleted = 0;
                $customer->id_document = Tools::getValue('tipo_documento');
                $customer->num_document = Tools::getValue('nro_documento');
                $customer->telefono = "";
                $customer->direccion = Tools::getValue('direccion') !== ""?Tools::getValue('direccion'):"no hay direccion";
                $customer->add();
                $customer->updateGroup(array($customer->id_default_group));

                $order->id_customer = $customer->id;
            }
            $order->update();
            $order->cliente = $customer->firstname;

            return die(json_encode($order));
        }
        //error si no existe la venta
        else{
            $this->errors[] = $this->trans('Error no existe una venta!!', array(), 'Admin.Orderscustomers.Notification');
            return die(Tools::jsonEncode(array('result' => "error", 'msg' => $this->errors)));
        }

    }

    public function ajaxProcessSendMailValidateOrderDocs()
    {
//        d($this->tabAccess['edit']);
        if ($this->tabAccess['edit']) {
//            d(Tools::getValue('id_order'));
            $order = new Order((int)Tools::getValue('id_order'));
            $objComprobantes = new PosOrdercomprobantes((int)Tools::getValue('id_pos_ordercomprobantes'));


            if (Validate::isLoadedObject($order)) {

                $customer = new Customer((int)$order->id_customer);
//                d($customer);

                if (Validate::isLoadedObject($customer)) {
//                    if ($order->cliente_empresa == 'Empresa'){
//
//                    }
//                    else if ($order->cliente_empresa == 'Cliente'){
//
//                    }
                    $arraycorreo = explode(';', Tools::getValue('correos'));



                    if($objComprobantes->nota_baja != "" && $objComprobantes->nota_baja == "ComunicacionBaja"){
                        $files[] = $objComprobantes->ruta_ticket;
                        $files[] = $objComprobantes->ruta_pdf_a4;
                        $files[] = $objComprobantes->ruta_xml_otro;
                    }else{
                        if($objComprobantes->tipo_documento_electronico != "" && $objComprobantes->tipo_documento_electronico == "NotaCredito") {
                        }else{
                            $files[] = $objComprobantes->ruta_ticket;
                        }
                        $files[] = $objComprobantes->ruta_pdf_a4;
                        $files[] = $objComprobantes->ruta_xml;
                        $files[] = $objComprobantes->ruta_cdr;
                    }


//                    $array2 = array_pop($files);
//                    d($files);
//                    $ruta = 'documentos_email/';
                    $semi_rand = md5(time());
                    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
//                    d($files);


                    for($x=0;$x<(count($files));$x++){
                        $file = fopen($files[$x],"rb");
                        $data = fread($file,filesize($files[$x]));
                        fclose($file);
                        $extencion=explode('.',$files[$x]);
                        $data = chunk_split(base64_encode($data));
                        $adjuntoArchivos[$x]['content'] = file_get_contents($files[$x]);
                        $nombre_archivo = explode("/", $files[$x]);
                        $adjuntoArchivos[$x]['name'] = end($nombre_archivo);
                        $adjuntoArchivos[$x]['mime']="application/"+$extencion[1];
                    }
//                 d($adjuntoArchivos);

                 if($objComprobantes->nota_baja != "" && $objComprobantes->nota_baja == "ComunicacionBaja"){
                        $formato_page = 'comunicacion_baja_email';


                        $titulo_m = 'Comunicacion de baja del Comprobante Nro. ' . $objComprobantes->numero_comprobante . ' - ' . $customer->firstname;

                        $mailVars = array(
                            '{nombre}' =>$customer->firstname,
                            '{firstname}' => $customer->firstname,
                            '{lastname}' => $customer->lastname,
                            '{numero_comprobante}' => $objComprobantes->numero_comprobante,
                            '{numeracion_nota_baja}' => $objComprobantes->numeracion_nota_baja,
                            '{mensaje_anulacion}' => $objComprobantes->tipo_documento_electronico == "Factura"?"con la Comunicación de Baja":"en un Resumen diario",
//                        '{total}' => $detalleCotiza[0]['total_con_impuesto'],
//                        '{attached_file}'=>'Por favor ver Archivos Adjuntos'
                        );
                        $nombre_cliente = $customer->firstname.' '.$customer->lastname;



                    }else{
                        if($objComprobantes->tipo_documento_electronico != "" && $objComprobantes->tipo_documento_electronico == "NotaCredito") {
                            $formato_page = 'nota_credito_email';
                            $factura = PosOrdercomprobantes::getFacturaByOrderLimit($objComprobantes->id_order);
                            $titulo_m = 'Nota de Credito del Comprobante Nro. ' . $factura['numero_comprobante'] . ' - ' . $customer->firstname;
                            $mailVars = array(
                                '{nombre}' =>$customer->firstname,
                                '{firstname}' => $customer->firstname,
                                '{lastname}' => $customer->lastname,
                                '{numero_comprobante}' => $factura['numero_comprobante'],
                                '{numeracion_nota_baja}' => $objComprobantes->numero_comprobante,

                            );
                        }else{
                            $formato_page = 'comprobantes_electronicos';
                            $titulo_m = 'Adjuntos Nro. ' . $objComprobantes->numero_comprobante . ' - ' . $customer->firstname ;

                            $mailVars = array(
                                '{nombre}' =>$customer->firstname,
                                '{firstname}' => $customer->firstname,
                                '{lastname}' => $customer->lastname,
//                        '{total}' => $detalleCotiza[0]['total_con_impuesto'],
//                        '{attached_file}'=>'Por favor ver Archivos Adjuntos'
                            );
                        }



                        $nombre_cliente = $customer->firstname.' '.$customer->lastname;

                    }


//                    d($correo);
//                    d($arraycorreo);
//                    d(_PS_MAIL_DIR_);
                    $correo='';
//                    d( $this->context->language->id);
                    $cart = new Shop((int)Context::getContext()->shop->id);
                    foreach ($arraycorreo as $key => $email) {
                        if ($email) {
//                            echo $email.'<br/>';
//                            d($email);
                            if ($key == 0){
                                $customer->email = $email;
                                $customer->update();
                            }


                            Mail::Send($this->context->language->id, $formato_page, Mail::l($titulo_m, 1), $mailVars, $email, $nombre_cliente, null, null, $adjuntoArchivos, null, _PS_MAIL_DIR_, true, $cart->id);
                            $correo = $email . '; ';
                        }
                    }
//                    d($correo);

                    die(Tools::jsonEncode(array('errors' => false, 'result' => $this->l('El correo fue enviado correctamente a "' . $correo . '". '))));
                    //Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
                }
            }
            $this->content = Tools::jsonEncode(array('errors' => true, 'result' => $this->l('Error in sending the email to your customer.')));
        }
    }

    public function ajaxProcessSumarMontos()
    {

        $order = new Order((int)Tools::getValue('id_order'));
        $order_details = $order->getProductsDetail();

        $total_paid = 0;
        $total_paid_tax_incl = 0;
        $total_paid_tax_excl = 0;
        $total_products = 0;
        $total_products_wt = 0;
        foreach ($order_details as $order_detail) {
            $total_paid += $order_detail['total_price_tax_incl'];
            $total_paid_tax_incl += $order_detail['total_price_tax_incl'];
            $total_paid_tax_excl += $order_detail['total_price_tax_excl'];
            $total_products += $order_detail['total_price_tax_excl'];
            $total_products_wt += $order_detail['total_price_tax_incl'];
        }

        $total_discounts = 0;
        $total_discounts_tax_incl = 0;
        $total_discounts_tax_excl = 0;
        // discount
        $discounts = $order->getCartRules();
        foreach ($discounts as $discount) {

            $total_discounts += $discount['value'];
            $total_discounts_tax_incl += $discount['value'];
            $total_discounts_tax_excl += $discount['value_tax_excl'];

            $total_paid -= $discount['value'];
            $total_paid_tax_incl -= $discount['value'];
            $total_paid_tax_excl -= $discount['value_tax_excl'];
        }

        $order->total_discounts = $total_discounts;
        $order->total_discounts_tax_incl = $total_discounts_tax_incl;
        $order->total_discounts_tax_excl = $total_discounts_tax_excl;

        // Update Order
        $order->total_paid = $total_paid;
        $order->total_paid_tax_incl = $total_paid_tax_incl;
        $order->total_paid_tax_excl = $total_paid_tax_excl;
        $order->total_products = $total_products;
        $order->total_products_wt = $total_products_wt;


        $res = $order->update();

        if (!$res) {
            die(json_encode(array(
                'result' => $res,
                'error' => $this->trans('A ocurrido un error al tratar de volver a sumar los montos.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }

        $order = $order->refreshShippingCost();

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex
        ));

        $order->pagado = $order->getTotalPaid();

        die(json_encode(array(
            'result' => $res,
            'order' => $order,
            'invoices' => $invoice_array,
        )));
    }

    public function ajaxProcessGetDetailAndPayments()
    {

        $order = new Order((int)Tools::getValue('id_order'));
        $pagos = $order->getOrderPayments();
        foreach ($pagos as &$item) {
            if ($item->es_cuenta == 1){
                $caja = PosArqueoscaja::cajaByID($item->id_cuenta);
                $item->caja_empleado = $caja['empleado'];
            }
            if ($item->es_cuenta == 2){
                $caja = new PosCuentasbanco((int)$item->id_cuenta);
                $item->caja_empleado = $caja->nombre_tarjeta;
            }

        }
        unset($item);

        $detalle = $order->getOrderDetailList();
        foreach ($detalle as &$item) {
            $item['link'] = $this->context->link->getAdminLink('AdminProducts', true, ['id_product' => $item['product_id'], 'updateproduct' => '1']);
        }
        unset($item);


        die(Tools::jsonEncode(array('errors' => true, 'pagos' => $pagos, 'detalle' => $detalle)));
    }

    public function ajaxProcessGuardarContado(){
        $order = new Order((int)Tools::getValue('id_order'));
        //d($order);
        //die();
        if($order) {
            $order->es_credito = Tools::getValue('es_credito');
            $order->update();
            $this->ajaxDie(json_encode(array("result" => "ok", "order" => $order)));
        }else{
            $this->ajaxDie(json_encode(array("result" => "error")));
        }
    }


}
