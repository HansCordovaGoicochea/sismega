<?php


use PrestaShop\PrestaShop\Core\Stock\StockManager;
use PrestaShop\PrestaShop\Adapter\StockManager as StockManagerAdapter;

/**
 * @property Order $object
 */
class AdminCuentasCobrarControllerCore extends AdminController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order';
        $this->className = 'Order';
        $this->lang = false;
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();
        $this->addRowAction('ir_pagar');

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
        
		CONCAT(c.`firstname`, " (", c.num_document, ") ") AS `customer`,
				c.num_document AS `doc_cliente`,
		osl.`name` AS `osname`,
		os.`color`,
		IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = a.id_customer AND so.id_order < a.id_order LIMIT 1) > 0, 0, 1) as new,
		country_lang.name as cname,
		IF(a.valid, 1, 0) badge_success,
		IF (poc.tipo_documento_electronico != "", poc.tipo_documento_electronico, "Ticket") comprobante,
		IF (poc.numero_comprobante  != "", poc.numero_comprobante, a.nro_ticket) nro_comprobante,
        (a.total_paid_tax_incl - IFNULL((select SUM(op.amount) from `'._DB_PREFIX_.'order_payment` op where op.order_reference = a.reference), 0)) as deuda,
        IFNULL((select SUM(op.amount) from `'._DB_PREFIX_.'order_payment` op where op.order_reference = a.reference), 0) as `pagado`,
        IF (a.`id_employee`, CONCAT_WS(" ",emp.firstname, emp.lastname), "Venta desde la Web") as empleado,
        motivo_anulacion,
        CONCAT_WS(" ",ea.firstname, ea.lastname) as colaborador
		';

        $this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'employee` ea ON (ea.`id_employee` = a.`id_colaborador`)
		LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
		LEFT JOIN `'._DB_PREFIX_.'address` address ON address.id_address = a.id_address_delivery
		LEFT JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
		LEFT JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = '.(int)$this->context->language->id.')
		LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = a.`current_state`)
		LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$this->context->language->id.')
		LEFT JOIN `'._DB_PREFIX_.'pos_ordercomprobantes` poc ON (poc.`id_order` = a.`id_order`)
        LEFT JOIN `'._DB_PREFIX_.'employee` emp ON (emp.`id_employee` = a.`id_employee`)';


        $this->_orderBy = 'date_add';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;


        $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status) {
            if ($status['id_order_state'] == 1 || $status['id_order_state'] == 2 || $status['id_order_state'] == 6)
                $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'hide',
                'class' => 'hide',
                'remove_onclick' => true,
            ),
            'customer' => array(
                'title' => $this->trans('Customer', array(), 'Admin.Global'),
                'havingFilter' => true,
                'remove_onclick' => true,
            ),
            'date_add' => array(
                'title' => $this->trans('Fecha', array(), 'Admin.Global'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
                'remove_onclick' => true,
            ),
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
                'tooltip' => 'motivo_anulacion',
                'remove_onclick' => true,
            ),
        ));

        $this->fields_list = array_merge($this->fields_list, array(
            'total_paid_tax_incl' => array(
                'title' => $this->trans('Total', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
                'search' => false,
//                'callback' => 'setOrderCurrency',
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

        $this->_where = Shop::addSqlRestriction(false, 'a');
        $this->_where .= " AND a.current_state in (1)";

        if (Tools::isSubmit('id_order')) {
            // Save context (in order to apply cart rule)
            $order = new Order((int)Tools::getValue('id_order'));
            $this->context->cart = new Cart($order->id_cart);
            $this->context->customer = new Customer($order->id_customer);
        }
//
//        d(Tools::toUnderscoreCase(substr($this->controller_name, 5)));
//

    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);

    }

    public function displayIr_pagarLink($token = null, $id, $name = null)
    {
        return '<a href="'.Context::getContext()->link->getAdminLink('AdminOrders').'&'.$this->identifier.'='.$id.'&view'.$this->table.'" title="Ir a pagar" >
	<i class="icon-money"></i> '.Context::getContext()->getTranslator()->trans('Pagar', array(), 'Admin.Actions').'
</a>
';

    }

    public function renderForm()
    {
        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->errors[] = $this->trans('You have to select a shop before creating new orders.', array(), 'Admin.Orderscustomers.Notification');
        }

        if ($this->display == 'edit'){
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders').'&id_order='.Tools::getValue('id_order').'&vieworder');
        }
    }

    public function ajaxProcessGetDetailAndPayments()
    {

        $order = new Order((int)Tools::getValue('id_order'));
        $pagos = $order->getOrderPayments();

        $detalle = $order->getOrderDetailList();
        foreach ($detalle as &$item) {
            $item['link'] = $this->context->link->getAdminLink('AdminProducts', true, ['id_product' => $item['product_id'], 'updateproduct' => '1']);
        }
        unset($item);


        die(Tools::jsonEncode(array('errors' => true, 'pagos' => $pagos, 'detalle' => $detalle)));
    }
}
