<?php
/**
 * 2007-2018 PrestaShop
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
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class AdminPdfControllerCore extends AdminController
{
    public function postProcess()
    {
        parent::postProcess();

        // We want to be sure that displaying PDF is the last thing this controller will do
        exit;
    }

    public function initProcess()
    {
        parent::initProcess();
        $this->checkCacheFolder();
        $access = Profile::getProfileAccess($this->context->employee->id_profile, (int)Tab::getIdFromClassName('AdminOrders'));
        if ($access['view'] === '1' && ($action = Tools::getValue('submitAction'))) {
            $this->action = $action;
        } else {
            $this->errors[] = $this->trans('You do not have permission to view this.', array(), 'Admin.Notifications.Error');
        }
    }

    public function checkCacheFolder()
    {
        if (!is_dir(_PS_CACHE_DIR_.'tcpdf/')) {
            mkdir(_PS_CACHE_DIR_.'tcpdf/');
        }
    }

    public function processGenerateInvoicePdf()
    {
        if (Tools::isSubmit('id_order')) {
            $this->generateInvoicePDFByIdOrder(Tools::getValue('id_order'));
        } elseif (Tools::isSubmit('id_order_invoice')) {
            $this->generateInvoicePDFByIdOrderInvoice(Tools::getValue('id_order_invoice'));
        }  elseif (Tools::isSubmit('id_ventarapida')){
            $this->generateInvoicePDFByVentaRapida(Tools::getValue('id_ventarapida'),'FacturaVentaRapida','P');
        } elseif (Tools::isSubmit('id_order_fisico')){
            $this->generateInvoicePDFByComprobanteFisico(Tools::getValue('id_order_fisico'),'ComprobanteFisico','P');
        } elseif (Tools::isSubmit('id_pos_transferencias')){
            $this->generateInvoicePDFByTransferencia(Tools::getValue('id_pos_transferencias'),'Transferencia');
        } elseif (Tools::isSubmit('id_pos_ordencompra')){
            $this->generateInvoicePDFByOrdencompra(Tools::getValue('id_pos_ordencompra'),'Ordencompra');
        } elseif (Tools::isSubmit('acumulativoreport')){
            $this->generateInvoicePDFByVentasAcumulativo(Tools::getValue('acumulativoreport'),'VentasAcumulativo','P');
        } elseif (Tools::isSubmit('ventascierrecaja')){
            $this->generateInvoicePDFByVentasCierreCaja(Tools::getValue('ventascierrecaja'),'VentasCierreCaja','P');
        }else {
            die($this->trans('The order ID -- or the invoice order ID -- is missing.', array(), 'Admin.Orderscustomers.Notification'));
        }
    }

    public function generateInvoicePDFByVentasCierreCaja($id_operacion_caja, $tpl, $documento)
    {
//        $order = new Order((int)$id_order);
        $operaciones_caja = new PosArqueoscaja((int)$id_operacion_caja);
//      d($operaciones_caja);
        if (!Validate::isLoadedObject($operaciones_caja)) {
            die($this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }
        $date1 = date_create($operaciones_caja->fecha_apertura);
        if ((int)$operaciones_caja->estado == 1){
            $month = date('m');
            $year = date('Y');
            $day = date("d", mktime(23, 59, 59, $month+1, 0, $year));
            $date2 = date_create(date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year)));
        }else{
            $date2 = date_create($operaciones_caja->fecha_cierre);
        }

        $fecha_apertura = date_format($date1, 'd-m-');
        $hora_apertura = date_format($date1, 'gis A');
        $fecha_cierre = date_format($date2, 'd-m-');
        $hora_cierre = date_format($date2, 'gis A');

        $monbre_archivo= str_replace(' ', '','reporte_'.$fecha_apertura.$hora_apertura.'-'.$fecha_cierre.$hora_cierre.'.pdf');

//      d($monbre_archivo);
        $this->GuardaPDF($operaciones_caja, $tpl, 'P',$monbre_archivo);
        $this->generatePDF($operaciones_caja, $tpl);


    }

    public function generateInvoicePDFByVentasAcumulativo($id_operacion_caja, $tpl, $documento)
    {
//        $order = new Order((int)$id_order);
        $operaciones_caja = new PosArqueoscaja((int)$id_operacion_caja);

        if (!Validate::isLoadedObject($operaciones_caja)) {
            die($this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }
        $date1 = date_create($operaciones_caja->fecha_apertura);
        if ((int)$operaciones_caja->estado == 1){
            $month = date('m');
            $year = date('Y');
            $day = date("d", mktime(23, 59, 59, $month+1, 0, $year));
            $date2 = date_create(date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year)));
        }else{
            $date2 = date_create($operaciones_caja->fecha_cierre);
        }


        $fecha_apertura = date_format($date1, 'd-m-');
        $hora_apertura = date_format($date1, 'gis A');
        $fecha_cierre = date_format($date2, 'd-m-');
        $hora_cierre = date_format($date2, 'gis A');

        $monbre_archivo= str_replace(' ', '','reporte_acumulativo_'.$fecha_apertura.$hora_apertura.'-'.$fecha_cierre.$hora_cierre.'.pdf');

//      d($monbre_archivo);
        $this->GuardaPDF($operaciones_caja, $tpl, 'P',$monbre_archivo);
        $this->generatePDF($operaciones_caja, $tpl);


    }

    public function GenerateInvoicePDFByTransferencia($id, $template)
    {
        $transferencia = new PosTransferencias((int)$id);

        if (!Validate::isLoadedObject($transferencia)) {
            die($this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $this->generatePDF($transferencia, $template);
    }

    public function GenerateInvoicePDFByOrdencompra($id, $template)
    {

        $ordencompra = new PosOrdencompra((int)$id);

        if (!Validate::isLoadedObject($ordencompra)) {
            die($this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }
        $this->generatePDF($ordencompra, $template);
    }

    public function processGenerateOrderSlipPDF()
    {
        $order_slip = new OrderSlip((int)Tools::getValue('id_order_slip'));
        $order = new Order((int)$order_slip->id_order);

        if (!Validate::isLoadedObject($order)) {
            die($this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $order->products = OrderSlip::getOrdersSlipProducts($order_slip->id, $order);
        $this->generatePDF($order_slip, PDF::TEMPLATE_ORDER_SLIP);
    }

    public function processGenerateDeliverySlipPDF()
    {
        if (Tools::isSubmit('id_order')) {
            $this->generateDeliverySlipPDFByIdOrder((int)Tools::getValue('id_order'));
        } elseif (Tools::isSubmit('id_order_invoice')) {
            $this->generateDeliverySlipPDFByIdOrderInvoice((int)Tools::getValue('id_order_invoice'));
        } elseif (Tools::isSubmit('id_delivery')) {
            $order = Order::getByDelivery((int)Tools::getValue('id_delivery'));
            $this->generateDeliverySlipPDFByIdOrder((int)$order->id);
        } else {
            die($this->trans('The order ID -- or the invoice order ID -- is missing.', array(), 'Admin.Orderscustomers.Notification'));
        }
    }

    public function processGenerateInvoicesPDF()
    {
        $order_invoice_collection = OrderInvoice::getByDateInterval(Tools::getValue('date_from'), Tools::getValue('date_to'));

        if (!count($order_invoice_collection)) {
            die($this->trans('No invoice was found.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $this->generatePDF($order_invoice_collection, PDF::TEMPLATE_INVOICE);
    }

    public function processGenerateInvoicesPDF2()
    {
        $order_invoice_collection = array();
        foreach (explode('-', Tools::getValue('id_order_state')) as $id_order_state) {
            if (is_array($order_invoices = OrderInvoice::getByStatus((int)$id_order_state))) {
                $order_invoice_collection = array_merge($order_invoices, $order_invoice_collection);
            }
        }

        if (!count($order_invoice_collection)) {
            die($this->trans('No invoice was found.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $this->generatePDF($order_invoice_collection, PDF::TEMPLATE_INVOICE);
    }

    public function processGenerateOrderSlipsPDF()
    {
        $id_order_slips_list = OrderSlip::getSlipsIdByDate(Tools::getValue('date_from'), Tools::getValue('date_to'));
        if (!count($id_order_slips_list)) {
            die($this->trans('No order slips were found.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $order_slips = array();
        foreach ($id_order_slips_list as $id_order_slips) {
            $order_slips[] = new OrderSlip((int)$id_order_slips);
        }

        $this->generatePDF($order_slips, PDF::TEMPLATE_ORDER_SLIP);
    }

    public function processGenerateDeliverySlipsPDF()
    {
        $order_invoice_collection = OrderInvoice::getByDeliveryDateInterval(Tools::getValue('date_from'), Tools::getValue('date_to'));

        if (!count($order_invoice_collection)) {
            die($this->trans('No invoice was found.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $this->generatePDF($order_invoice_collection, PDF::TEMPLATE_DELIVERY_SLIP);
    }

    public function processGenerateSupplyOrderFormPDF()
    {
        if (!Tools::isSubmit('id_supply_order')) {
            die($this->trans('The supply order ID is missing.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $id_supply_order = (int)Tools::getValue('id_supply_order');
        $supply_order = new SupplyOrder($id_supply_order);

        if (!Validate::isLoadedObject($supply_order)) {
            die($this->trans('The supply order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $this->generatePDF($supply_order, PDF::TEMPLATE_SUPPLY_ORDER_FORM);
    }

    public function generateDeliverySlipPDFByIdOrder($id_order)
    {
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) {
            throw new PrestaShopException('Can\'t load Order object');
        }

        $order_invoice_collection = $order->getInvoicesCollection();
        $this->generatePDF($order_invoice_collection, PDF::TEMPLATE_DELIVERY_SLIP);
    }

    public function generateDeliverySlipPDFByIdOrderInvoice($id_order_invoice)
    {
        $order_invoice = new OrderInvoice((int)$id_order_invoice);
        if (!Validate::isLoadedObject($order_invoice)) {
            throw new PrestaShopException('Can\'t load Order Invoice object');
        }

        $this->generatePDF($order_invoice, PDF::TEMPLATE_DELIVERY_SLIP);
    }

    public function generateInvoicePDFByIdOrder($id_order)
    {
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) {
            die($this->trans('The order cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }

        $order_invoice_list = $order->getInvoicesCollection();
        Hook::exec('actionPDFInvoiceRender', array('order_invoice_list' => $order_invoice_list));
        $this->generatePDF($order_invoice_list, PDF::TEMPLATE_INVOICE);
    }

    public function generateInvoicePDFByIdOrderInvoice($id_order_invoice)
    {
        $order_invoice = new OrderInvoice((int)$id_order_invoice);
        if (!Validate::isLoadedObject($order_invoice)) {
            die($this->trans('The order invoice cannot be found within your database.', array(), 'Admin.Orderscustomers.Notification'));
        }

        Hook::exec('actionPDFInvoiceRender', array('order_invoice_list' => array($order_invoice)));
        $this->generatePDF($order_invoice, PDF::TEMPLATE_INVOICE);
    }

    //yo
    public function generateInvoicePDFByVentaRapida($idFactura, $tpl, $orientacion)
    {

        $idFactura = new Order((int)$idFactura);
//        $idFactura = new OrderInvoice((int)$idFactura);
//        d($idFactura);
        if (!Validate::isLoadedObject($idFactura))
            die(Tools::displayError('The order invoice cannot be found within your database.'));

        $tienda_actual = new Shop((int)$this->context->shop->id);
        $nombre_virtual_uri = $tienda_actual->virtual_uri;

        $correlativo_comanda = NumeracionDocumento::getNumTipoDoc('Ticket');
        if (empty($correlativo_comanda)){
            $objNC = new NumeracionDocumento();
            $objNC->serie = '';
            $objNC->correlativo = 0;
            $objNC->nombre = 'Ticket';
            $objNC->id_shop = Context::getContext()->shop->id;
            $objNC->add();
            $correlativo_comanda = NumeracionDocumento::getNumTipoDoc('Ticket');
        }else{
            $correlativo_comanda = NumeracionDocumento::getNumTipoDoc('Ticket');
        }

//        d($idFactura->nro_ticket);
        if (!$idFactura->nro_ticket){
            $co = new NumeracionDocumento((int)$correlativo_comanda['id_numeracion_documentos']);
            $co->correlativo = ($correlativo_comanda['correlativo']+1);
            $co->update();
            $numero_de_ticket = $correlativo_comanda['correlativo'];
            $monbre_archivo='Ticket_numero_'.($numero_de_ticket+1).'.pdf';
            $idFactura->nro_ticket = ($numero_de_ticket+1);
            $this->context->smarty->assign(array('numero_de_ticket'=>$numero_de_ticket+1));

        }else{
            $numero_de_ticket = $idFactura->nro_ticket;
            $monbre_archivo='Ticket_numero_'.($numero_de_ticket).'.pdf';
            $this->context->smarty->assign(array('numero_de_ticket'=>$numero_de_ticket));
        }

        $ruta = 'documentos_pdf/'.$nombre_virtual_uri;
        if (!file_exists($ruta)) {
            mkdir($ruta, 0777, true);
        }

        $idFactura->ruta_ticket_normal = $ruta.$monbre_archivo;
        if (!$idFactura->id_employee){
            $idFactura->id_employee = $this->context->employee->id;
        }
        $idFactura->update();

        $this->GuardaPDF($idFactura, $tpl, $orientacion,$monbre_archivo);
        $this->generatePDF($idFactura, $tpl, $orientacion);

    }
    //yo
    public function generateInvoicePDFByComprobanteFisico($idorder, $tpl, $orientacion)
    {

        $tienda_actual = new Shop((int)$this->context->shop->id);
        $nombre_virtual_uri = $tienda_actual->virtual_uri;
        $tipo_comprobante = Tools::getValue('documento');
        $order = new Order((int)$idorder);
        if (!Validate::isLoadedObject($order))
            die(Tools::displayError('The order invoice cannot be found within your database.'));

        $doc = PosOrdercomprobantes::getComprobantesByOrderLimit($order->id);
        if (!empty($doc)){
            $objComprobantes = new PosOrdercomprobantes($doc['id_pos_ordercomprobantes']);
        }else{
            $objComprobantes = new PosOrdercomprobantes();
        }

        if (!$objComprobantes->numero_comprobante && $objComprobantes->numero_comprobante == ""){
            $objComprobantes->id_order = $order->id;
            $objComprobantes->tipo_documento_electronico = $tipo_comprobante;
            $objComprobantes->sub_total = $order->total_paid_tax_excl;
            $objComprobantes->impuesto = (float)($order->total_paid_tax_incl - $order->total_paid_tax_excl);
            $objComprobantes->total = $order->total_paid_tax_incl;

            //creamos la numeracion
            $numeracion_documento = NumeracionDocumento::getNumTipoDoc($tipo_comprobante);
            if (empty($numeracion_documento)){
                die('Porfavor cree las series y numeración para su tienda gracias. Nombre: '.$tipo_comprobante );
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

        $monbre_archivo = $objComprobantes->tipo_documento_electronico.'_'.PS_SHOP_RUC.'-'.$objComprobantes->numero_comprobante.'.pdf';


        $ruta_a4 = 'documentos_pdf_a4/fisico/'.$tienda_actual->virtual_uri;
        if (!file_exists($ruta_a4)) {
            mkdir($ruta_a4, 0777, true);
        }

        $objComprobantes->ruta_pdf_a4 =  $ruta_a4.$monbre_archivo;
        if (empty($doc)){
            $objComprobantes->add();
        }


        $this->GuardaPDF($objComprobantes, $tpl, $orientacion,$monbre_archivo);
        $this->generatePDF($objComprobantes, $tpl, $orientacion);

    }

    public function generatePDF($object, $template)
    {
        $pdf = new PDF($object, $template, Context::getContext()->smarty);
        $pdf->render();
    }

    public function GuardaPDF($object, $template, $orientacion = 'P', $nombre)
    {

        $pdf = new PDF($object, ucfirst($template), Context::getContext()->smarty,$orientacion);
        $pdf->visualizarGuarda($object,$nombre);

    }
}
