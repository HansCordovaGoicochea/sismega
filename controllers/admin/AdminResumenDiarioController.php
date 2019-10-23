<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminCajaController
 *
 * @author 01101801
 */


$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
//d($baseDir);
require $baseDir.'/vendor/xmlseclibs/xmlseclibs.php';
require $baseDir.'/vendor/xmlseclibs/CustomHeaders.php';

use PrestaShop\PrestaShop\Core\Stock\StockManager as StockManagerAche;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecEnc;

class AdminResumenDiarioControllerCore extends AdminController
{

    public function __construct()
	{

		$this->bootstrap = true;
	 	$this->table = 'resumen_diario';
		$this->className = 'ResumenDiario';
	 	$this->lang = false;
		$this->context = Context::getContext();
        $this->addRowAction('edit');
		$this->addRowAction('delete');
//        $this->addRowAction('view');

        parent::__construct();

        $this->bulk_actions = array(
			'delete' => array(
				'text' => $this->l('Delete selected'),
				'confirm' => $this->l('Delete selected items?'),
				'icon' => 'icon-trash'
			)
		);

        $this->_orderBy = 'id_resumen_diario';
        $this->_orderWay = 'DESC';
		$this->fields_list = array(
                'id_resumen_diario' => array(
                            'title' => $this->l('ID'),
                            'align' => 'center',
                            'class' => 'fixed-width-xs'
                ),
                'identificador_resumen_diario' => array(
                            'title' => $this->l('NRO'),
                            'align' => 'center',
                            'type' => 'date'
                ),
                'fecha_generacion_resumen_diario' => array(
                            'title' => $this->l('Fecha Generación'),
                            'align' => 'center',
                            'type' => 'date'
                ),
                'fecha_emision_comprobantes' => array(
                            'title' => $this->l('Fecha Emision Comp.'),
                            'align' => 'center',
                            'type' => 'date'
                ),
                'nro_ticket' => array(
                            'title' => $this->l('Ticket'),
                            'align' => 'center',
                ),
			);

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_ORDER;
		
	}

        public function initPageHeaderToolbar()
	{
		if (empty($this->display))
			$this->page_header_toolbar_btn['new_resumen_diario'] = array(
				'href' => self::$currentIndex.'&addresumen_diario&token='.$this->token,
				'desc' => $this->l('Crear Resumen Diario', null, null, false),
				'icon' => 'process-icon-new'
			);
		
		parent::initPageHeaderToolbar();
	}
        public function renderForm()
	{

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->l('Resumen Diario'),
				'icon' => 'icon-group'
			),
			'input' => array(),
			'submit' => array(
				'title' => $this->l('Save'),
			)
		);



            $arrary_tiendas = Shop::getContextListShopID(false);

            $objResumenDiario = new ResumenDiario((int)Tools::getValue('id_resumen_diario'));

            $this->context->smarty->assign(array(
                'nro_tiendas'=>count($arrary_tiendas),
                'objResumenDiario'=>$objResumenDiario,
//                'correlativo_resumen'=>$correlativo_resumen,
            ));

            return parent::renderForm();
        }
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/waitMe.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/waitMe.min.js');

    }

    public function ajaxProcessTraerComprobantes()
    {
        $tipo_comprobante = Tools::getValue('tipo_comprobante');
        $fecha_comprobantes = Tools::getFormatFechaGuardar(Tools::getValue('fecha_comprobantes'));


        $comprobantes = PosOrdercomprobantes::getOrdersIdInvoiceByDateTipoComp($fecha_comprobantes, $tipo_comprobante);
//
//        $comprobantes = array();
//        foreach ($all_comb as $key=>$comprobante){
//            $objOrder = new Order((int)$comprobante);
//            $comprobantes[$key] = $objOrder;
//        }

        $to_return = array('Objcomprobantes' => $comprobantes, 'found' => true);
        die(Tools::jsonEncode($to_return));
    }
    public function ajaxProcessTraerDatosComprobante()
    {
        $id_pos_ordercomprobantes = Tools::getValue('id_pos_ordercomprobantes');
        $objordercomprobantes = new PosOrdercomprobantes((int)$id_pos_ordercomprobantes);
        $objOrder = new Order((int)$objordercomprobantes->id_order);
//d($objOrder);

        $to_return = array('Objcomprobante' => $objOrder, 'found' => true);
        die(Tools::jsonEncode($to_return));
    }

    public function ajaxProcessGuardarResumenDiario()
    {

//        $correlativo_resumen = NumeracionDocumento::getDatos('ResumenDiario', $caja['id_apertura_caja']);
        $correlativo_resumen = NumeracionDocumento::getNumTipoDoc('ResumenDiario');

        try {
            if (Tools::getValue('id_resumen_diario')) {
                $objResumenDiario = new ResumenDiario((int)Tools::getValue('id_resumen_diario'));
//                $objResumenDiario->identificador_resumen_diario = $objResumenDiario->identificador_resumen_diario;
            }
            else{
                //actualizar la numeracion
                $objNC = new NumeracionDocumento((int)$correlativo_resumen['id_numeracion_documentos']);
                $numero_siguiente = $correlativo_resumen['correlativo']+1;
                $date_now = Date('Ymd');
                $objNC->correlativo = $numero_siguiente;
                $objNC->update();
                // fin actualizar la numeracion

                $date_emision = str_replace("-","",Tools::getFormatFechaGuardar(Tools::getValue('fecha_generacion')));
                $objResumenDiario = new ResumenDiario();
                $objResumenDiario->identificador_resumen_diario = 'RC-'.$date_emision.'-'.$numero_siguiente;
            }
            $objResumenDiario->fecha_generacion_resumen_diario = Tools::getFormatFechaGuardar(Tools::getValue('fecha_generacion'));
            $objResumenDiario->fecha_emision_comprobantes = Tools::getFormatFechaGuardar(Tools::getValue('fecha_emision_comprobantes'));
            $objResumenDiario->nota_resumen_diario = Tools::getValue('nota');
            $objResumenDiario->id_shop = $this->context->shop->id;
            $objResumenDiario->id_employee = $this->context->employee->id;

            if (Tools::getValue('id_resumen_diario'))
                $result = $objResumenDiario->update();
            else
                $result = $objResumenDiario->add();


            if ($result) {
                if (Tools::getValue('detalleresumen')){
                    $objDetalle = json_decode((Tools::getValue('detalleresumen')));
                    $arrayobjetos = $objDetalle->valor;

                    foreach ($arrayobjetos as $clave => $valor) {
                        if (!empty($valor->txt_id_resumen_diario_detalle)) {
                            $obj = new ResumenDiariodetalle($valor->txt_id_resumen_diario_detalle);
                        } else {
                            $obj = new ResumenDiariodetalle();
                        }

                        $objComprobante = new PosOrdercomprobantes((int)$valor->id_cb_comprobantes);
                        $objOrder = new Order((int)$objComprobante->id_order);

                        $obj->id_resumen_diario = $objResumenDiario->id;
                        $obj->line_id = $clave+1;
                        $obj->tipo_documento = $valor->cb_tipo_comprobante;
                        $obj->codigo_tipo_documento = $valor->txt_codigo_documento;
                        $obj->serie_correlativo = $valor->valor_cb_comprobantes;
                        $obj->status_comprobante = $valor->cb_estado_comprobante;
                        $obj->id_currency = $objOrder->id_currency;
                        $obj->importe_total = $objOrder->total_paid_tax_incl;
                        $obj->importe_operaciones_gravadas = Tools::truncate_number(OrderDetail::getTotalPorCodigo($objOrder->id, '10'), 2);
                        $obj->importe_operaciones_exoneradas = 0;
                        $obj->importe_operaciones_inafectas = Tools::truncate_number(OrderDetail::getTotalPorCodigo($objOrder->id, '30'), 2);
                        $obj->importe_otras_operaciones = 0;
                        $obj->total_igv = (float)($objOrder->total_paid_tax_incl - $objOrder->total_paid_tax_excl);
                        $obj->total_impuestos_otros = 0;

                        if ($valor->cb_tipo_comprobante == 'NotaCredito'){
                            $obj->codigo_tipo_documento_referencia = '01';
//                            $obj->codigo_tipo_documento_referencia = $objComprobante->tipo_documento_electronico == 'Boleta'?'03':'01';
                            $obj->serie_correlativo_referenciar = $objComprobante->numero_comprobante;
                        }

                        $obj->id_pos_ordercomprobantes = $valor->id_cb_comprobantes;
                        $obj->motivo = $valor->txt_motivo;


                        if (!empty($valor->txt_id_resumen_diario_detalle)) {
                            $result_detail = $obj->update();
                        } else {
                            $result_detail = $obj->add();
                        }

                        $arrayid_seccion[$clave] = array('clave_seccion' => $obj->id);
                    }
                }

//                echo ($xml_doc);
//                d($xml_doc);
                $MENSAJE = '';
                $arr = Certificadofe::getIdCertife(Context::getContext()->shop->id);
                if ($arr == 0){
                    $MENSAJE = 'Porfavor suba un certificado para firmar los DOCUMENTOS!'.'<a href="index.php?controller=AdminCertificadoFE&addcertificadofe&token='.Tools::getAdminTokenLite('AdminCertificadoFE').'" target="_blank">&nbsp; -> Subir Certificado</a>';
                }
                else{
                    $objCerti = new Certificadofe((int)$arr); // buscar el certificado

                    $cabecera = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?><SummaryDocuments xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:SummaryDocuments-1" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>';

                    $xml = new SimpleXMLElement($cabecera);

                    $UBLExtensionsXml = $xml->addChild('UBLExtensions',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
                    $UBLExtensionXml = $UBLExtensionsXml->addChild('UBLExtension',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
                    $ExtensionContentXml = $UBLExtensionXml->addChild('ExtensionContent',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');

                    $xml->addChild('UBLVersionID','2.0','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $xml->addChild('CustomizationID','1.1','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $xml->addChild('ID', $objResumenDiario->identificador_resumen_diario,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $xml->addChild('ReferenceDate', $objResumenDiario->fecha_emision_comprobantes,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $xml->addChild('IssueDate',"$objResumenDiario->fecha_generacion_resumen_diario",'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                    $SignatureXml = $xml->addChild('Signature',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $SignatureXml->addChild('ID',PS_SHOP_RUC,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $SignatoryPartyXml = $SignatureXml->addChild('SignatoryParty',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $PartyIdentificationXml = $SignatoryPartyXml->addChild('PartyIdentification',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $PartyIdentificationXml->addChild('ID',PS_SHOP_RUC,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $PartyNameXml = $SignatoryPartyXml->addChild('PartyName',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $PartyNameXml->addChild('Name',PS_SHOP_RAZON_SOCIAL,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $DigitalSignatureAttachmentXml = $SignatureXml->addChild('DigitalSignatureAttachment',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $ExternalReferenceXml = $DigitalSignatureAttachmentXml->addChild('ExternalReference',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $ExternalReferenceXml->addChild('URI','SIGN','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                    $AccountingSupplierPartyXml = $xml->addChild('AccountingSupplierParty',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $AccountingSupplierPartyXml->addChild('CustomerAssignedAccountID', PS_SHOP_RUC,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $AccountingSupplierPartyXml->addChild('AdditionalAccountID',"6",'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                    $PartyXml = $AccountingSupplierPartyXml->addChild('Party',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $RegistrationNameXml = $PartyXml->addChild('PartyLegalEntity',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                    $RegistrationNameXml->addChild('RegistrationName',PS_SHOP_RAZON_SOCIAL,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                    $resumen_details = ResumenDiariodetalle::getDetalleFacturaID($objResumenDiario->id);
                    foreach ($resumen_details as $key=>$value )
                    {
                        $objComprobante = new PosOrdercomprobantes((int)$value['id_pos_ordercomprobantes']);
                        $objOrders = new Order((int)$objComprobante->id_order);

                        if ($objOrders->id_currency == 1){
                            $currency = 'PEN';
                        }
                        else{
                            $currency = 'USD';
                        }

                        $InvoiceLineXml = $xml->addChild('SummaryDocumentsLine',null,'urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1');
                        $InvoiceLineXml->addChild('LineID', $key+1,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                        $InvoiceLineXml->addChild('DocumentTypeCode', $value['codigo_tipo_documento'],'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        $order = $objOrders;

                        $numero_id = $objComprobante->numero_comprobante;
                        $customer = new Customer((int)$order->id_customer);
                        $ruc_sup = $customer->num_document; // numero de documento del cliente
                        $nombre_empresa = $customer->firstname; // razon_social o nombre del cliente
                        $direccion_cliente = $customer->direccion;
                        $tipo_documento_legal = new Tipodocumentolegal((int)$customer->id_document);
                        //d($tipo_documento_legal);
                        if ((int)$order->id_customer !== 1){
                            $tipo_code_doc_sup = $tipo_documento_legal->cod_sunat; // codigo de documento de identidad
                        }else{
                            $tipo_code_doc_sup = "0"; // codigo de documento de identidad
                        }

                        if ($value['tipo_documento'] == 'Boleta'){
                            $invoice_type_code = '03';//cod de comprobante electronico
                        }
                        else{
                            $invoice_type_code = '07';//cod de comprobante electronico
                        }

                        $InvoiceLineXml->addChild('ID', $numero_id,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        $AccountingCustomerPartyXml = $InvoiceLineXml->addChild('AccountingCustomerParty',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                        $AccountingCustomerPartyXml->addChild('CustomerAssignedAccountID',$ruc_sup,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'); //ruc del cliente
                        $AccountingCustomerPartyXml->addChild('AdditionalAccountID',$tipo_code_doc_sup,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'); //codigo de tipo de documento si es dni o ruc

                        if ($value['tipo_documento'] == 'NotaCredito'){
                            ////// nofunciona
                            $BillingReferenceXml = $InvoiceLineXml->addChild('BillingReference',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                            $InvoiceDocumentReferenceXml = $BillingReferenceXml->addChild('InvoiceDocumentReference', null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                            $InvoiceDocumentReferenceXml->addChild('ID', $objComprobante->numero_comprobante,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                            $InvoiceDocumentReferenceXml->addChild('DocumentTypeCode', $invoice_type_code,'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                        }

                        $StatusXml = $InvoiceLineXml->addChild('Status',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                        $StatusXml->addChild('ConditionCode', $value['status_comprobante'],'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        $TotalAmountXml = $InvoiceLineXml->addChild('TotalAmount',number_format((float)$order->total_paid_tax_incl, 2, '.', ''),'urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1');
                        $TotalAmountXml->addAttribute('currencyID',$currency);


                        $BillingPaymentXml = $InvoiceLineXml->addChild('BillingPayment',null,'urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1');
                        $BillingPaymentXmlc = $BillingPaymentXml->addChild('PaidAmount', number_format((float)$order->total_paid_tax_excl, 2, '.', ''),'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                        $BillingPaymentXmlc->addAttribute('currencyID',$currency);
                        $BillingPaymentXml->addChild('InstructionID', '01','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        //impuesto igv
                        $TaxTotalXml = $InvoiceLineXml->addChild('TaxTotal',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                        $currATT = $TaxTotalXml->addChild('TaxAmount',number_format((float)$order->total_paid_tax_incl - (float)$order->total_paid_tax_excl, 2, '.', ''),'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'); //monto de impuestos
                        $currATT->addAttribute('currencyID',$currency);
                        $TaxSubtotalXml = $TaxTotalXml->addChild('TaxSubtotal',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                        $currATT2 = $TaxSubtotalXml->addChild('TaxAmount',number_format((float)$order->total_paid_tax_incl - (float)$order->total_paid_tax_excl, 2, '.', ''),'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'); //monto de impuestos
                        $currATT2->addAttribute('currencyID',$currency);
                        $TaxCategoryXml = $TaxSubtotalXml->addChild('TaxCategory',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                        $TaxSchemeXml = $TaxCategoryXml->addChild('TaxScheme',null,'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                        $TaxSchemeXml->addChild('ID','1000','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                        $TaxSchemeXml->addChild('Name','IGV','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                        $TaxSchemeXml->addChild('TaxTypeCode','VAT','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');


                    }

                    $xml = dom_import_simplexml($xml)->ownerDocument;
                    $xml->formatOutput = true;
                    $xml_doc = $xml->saveXML();

                    $nombre_archivo = PS_SHOP_RUC.'-'.$objResumenDiario->identificador_resumen_diario;
                    $ruta_xml = "archivos_sunat/resumen/".$nombre_archivo;
                    $ruta_cdr = "archivos_sunat/resumen/";

                    if (!file_exists('archivos_sunat/resumen/')) {
                        mkdir('archivos_sunat/resumen/', 0777, true);
                    }

                    $fp = fopen("archivos_sunat/resumen/".$nombre_archivo.".xml","wb");
                    fwrite($fp,$xml_doc);
                    fclose($fp);

                    $rutas = array();
                    $rutas['ruta_comprobantes'] = $nombre_archivo;
                    $rutas['nombre_archivo'] = $nombre_archivo;
                    $rutas['ruta_xml'] = $ruta_xml;
                    $rutas['ruta_cdr'] = $ruta_cdr;
                    $rutas['ruta_firma'] = $objCerti->archivo;
                    $rutas['pass_firma'] = $objCerti->clave_certificado;
                    $rutas['ruta_ws'] = $objCerti->web_service_sunat;

                    $resp_firma = FirmarDocumento::firmar_xml(null, $rutas["ruta_xml"], $rutas["ruta_firma"], $rutas["pass_firma"], $rutas["nombre_archivo"]);
//
                    if ($resp_firma['respuesta'] == "error"){
                        $objResumenDiario->cod_sunat = $resp_firma['cod_sunat'];
                        $objResumenDiario->msj_sunat = $resp_firma['msj_sunat'];
                        $objResumenDiario->respuesta = "error";
                        $objResumenDiario->update();
                        return die(json_encode($resp_firma));
                    }

                    $resp_envio = ProcesarComprobante::enviar_resumen_boletas($objCerti->user_sunat, $objCerti->pass_sunat,  $rutas["ruta_xml"], $rutas['nombre_archivo'], $rutas['ruta_ws']);

                    if ($resp_envio['respuesta'] == "error"){
                        $objResumenDiario->cod_sunat = $resp_envio['cod_sunat'];
                        $objResumenDiario->msj_sunat = $resp_envio['msj_sunat'];
                        $objResumenDiario->respuesta = "error";
                        $objResumenDiario->update();
                        return die(json_encode($resp_envio));
                    }
                    $objResumenDiario->respuesta = "ok";
                    $objResumenDiario->ruta_xml = $ruta_xml;
                    $objResumenDiario->cod_sunat = $resp_envio['cod_sunat'];
                    $objResumenDiario->msj_sunat = $resp_envio['msj_sunat'];
                    $objResumenDiario->nro_ticket = $resp_envio['cod_ticket'];
                    $objResumenDiario->update();


                    //cambiar orderstate y history state
                    foreach ($resumen_details as $key=>$value ) {
                        $objComprobante = new PosOrdercomprobantes((int)$value['id_pos_ordercomprobantes']);
                        $order = new Order((int)$objComprobante->id_order);

                        //si solo si esta pagado
                        $new_os = new OrderState((int)Configuration::get('PS_OS_CANCELED'), $order->id_lang);
                        $old_os = $order->getCurrentOrderState();
//                        if (Tools::getValue('id_caja') && (int)Tools::getValue('id_caja') > 0){
//                            $objCaja = new PosArqueoscaja((int)Tools::getValue('id_caja'));
//                            foreach ($order->getOrderPaymentCollection() as $payment){
//                                if ((int)$payment->es_cuenta == 1) { // 1 es caja
//                                    $monto_inicial = $objCaja->monto_operaciones;
//                                    $objCaja->monto_operaciones = (float)$monto_inicial - (float)$payment->amount;
//                                    $objCaja->update();
//                                }
//                            }
//
//                            if (!empty($doc)) {
//                                $objComprobantes = new PosOrdercomprobantes((int)$doc['id_pos_ordercomprobantes']);
//                                $objComprobantes->devolver_monto_caja = 1;
//                                $objComprobantes->update();
//                            }
//                        }
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


                        $objComprobante->motivo_baja = Tools::getValue('nota');
                        $objComprobante->update();

                        $order->motivo_anulacion = Tools::getValue('nota');
                        $order->update();

                        $order->setCurrentState(14, $this->context->employee->id); //estado 14 comunicacion de baja
                    }

//                    $resp_cdr = ProcesarComprobante::consultar_envio_ticket_resumen($objCerti->user_sunat, $objCerti->pass_sunat, $objResumenDiario->identificador_resumen_diario, $rutas['nombre_archivo'], $rutas['ruta_cdr'], $rutas['ruta_ws']);
//
//                    $objResumenDiario->cod_sunat = $resp_cdr['cod_sunat'];
//                    $objResumenDiario->mensaje_cdr = $resp_cdr['msj_sunat'];
//                    if ($resp_cdr['respuesta'] == 'ok'){
//                        $objResumenDiario->mensaje_cdr = $resp_cdr['msj_sunat'];
//                        $objResumenDiario->ruta_cdr = $resp_cdr['ruta_cdr'];
//                        $objResumenDiario->hash_cdr = $resp_cdr['hash_cdr'];
//                    }
                    $objResumenDiario->update();

                }
                $to_return = array('errors' => true, 'correcto' => 'Datos grabados correctamente','mensaje_soap'=>$MENSAJE, 'id_resumen_diario' => $objResumenDiario->id, 'detalle' => $arrayid_seccion, 'identificador' => $objResumenDiario->identificador_resumen_diario);
                echo Tools::jsonEncode($to_return);
                die();
            }
            else{
                //actualizar la numeracion
                $objNC = new NumeracionDocumento((int)$correlativo_resumen['id_numeracion_comanda']);
                $numero_siguiente = $correlativo_resumen['correlativo']-1;
                $date_now = Date('Ymd');
                $objNC->correlativo = $numero_siguiente;
                $objNC->update();

                PrestaShopLogger::addLog(
                    __METHOD__ . '::' . __LINE__ . ':: could not save transaction to database',
                    2,
                    null,
                    'Resumen Diario',
                    null,
                    true
                );

                $this->exitOrRedirect(self::REDIRECT);

                return;
            }
        }
        catch (Exception $e){
            $e->getMessage();
        }

    }

    public function ajaxProcessTraerDatosDetalleResumen()
    {

        $objDetalle = ResumenDiariodetalle::getDetalleFacturaID(Tools::getValue('id_resumen_diario'));
//        d($objDetalle);
        $to_return = array('objDetalle' => $objDetalle, 'found' => true);
        die(Tools::jsonEncode($to_return));
    }

    public function ajaxProcessConsultarCDRTicket()
    {
        $id_resumen_diario = Tools::getValue('id_resumen_diario');
        $objResumenDiario = new ResumenDiario((int)$id_resumen_diario);
        $nombre_archivo = PS_SHOP_RUC.'-'.$objResumenDiario->identificador_resumen_diario;

        $arr = Certificadofe::getIdCertife(Context::getContext()->shop->id);
        if ($arr == 0){
            $this->errors[] = 'Porfavor suba un certificado para firmar los DOCUMENTOS!'.'<a href="index.php?controller=AdminCertificadoFE&addcertificadofe&token='.Tools::getAdminTokenLite('AdminCertificadoFE').'" target="_blank">&nbsp; -> Subir Certificado</a>';
        }
        else{
            $objCerti = new Certificadofe((int)$arr);

            $service = $objCerti->web_service_sunat; // webservice a la que se van a enviar los ZIP
            $user= $objCerti->user_sunat;
            $pass= $objCerti->pass_sunat;
            if (!$service && !$user && !$pass){

                $this->errors[] = 'Porfavor llene los datos para generar la factura electronica -> '.'<a href="index.php?controller=AdminShop&shop_id='.Context::getContext()->shop->id.'&updateshop&token='.Tools::getAdminTokenLite('AdminShop').'" target="_blank">&nbsp; -> Ir a completar Datos de la tienda</a>';
            }
            else{

                $ruta_cdr = "archivos_sunat/resumen/";
                $rutas = array();
                $rutas['ruta_comprobantes'] = $nombre_archivo;
                $rutas['nombre_archivo'] = $nombre_archivo;
                $rutas['ruta_cdr'] = $ruta_cdr;
                $rutas['ruta_firma'] = $objCerti->archivo;
                $rutas['pass_firma'] = $objCerti->clave_certificado;
                $rutas['ruta_ws'] = $objCerti->web_service_sunat;

                $resp_cdr = ProcesarComprobante::consultar_envio_ticket_resumen($objCerti->user_sunat, $objCerti->pass_sunat, $objResumenDiario->identificador_resumen_diario, $rutas['nombre_archivo'], $rutas['ruta_cdr'], $rutas['ruta_ws']);

                $objResumenDiario->cod_sunat = $resp_cdr['cod_sunat'];
                $objResumenDiario->mensaje_cdr = $resp_cdr['msj_sunat'];
                if ($resp_cdr['respuesta'] == 'ok'){
                    $objResumenDiario->mensaje_cdr = $resp_cdr['msj_sunat'];
                    $objResumenDiario->ruta_cdr = $resp_cdr['ruta_cdr'];
                    $objResumenDiario->hash_cdr = $resp_cdr['hash_cdr'];
                    $to_return = array('respuesta' => 'ok', 'correcto' => $objResumenDiario->mensaje_cdr, 'id_resumen_diario' => $objResumenDiario->id);
                }else{
                    $to_return = array('respuesta' => 'error', 'correcto' => $objResumenDiario->mensaje_cdr, 'id_resumen_diario' => $objResumenDiario->id);
                }
                $objResumenDiario->update();

                echo Tools::jsonEncode($to_return);
                die();
            }
        }

    }

}
