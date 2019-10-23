<?php


/**
 * Description of AdminCajaController
 *
 * @author 01101801
 */
class AdminPosArqueoscajaControllerCore extends AdminController
{
    protected $restrict_edition = false;
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'pos_arqueoscaja';
        $this->className = 'PosArqueoscaja';
        $this->lang = false;
        $this->context = Context::getContext();
//        $this->addRowAction('edit');
//        $this->addRowAction('delete');
        $this->addRowAction('cerrar_caja');
        $this->allow_export = true;

        parent::__construct();

        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            return $this->errors[] = $this->trans('Tiene que seleccionar una tienda antes.', array(), 'Admin.Orderscustomers.Notification');
        }

//        $this->bulk_actions = array(
//            'delete' => array(
//                'text' => $this->l('Delete selected'),
//                'confirm' => $this->l('Delete selected items?'),
//                'icon' => 'icon-trash'
//            )
//        );

        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'employee` ea ON (ea.`id_employee` = a.`id_employee_apertura` AND a.`id_shop` = '.$this->context->shop->id.')';
        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'employee` ec ON (ec.`id_employee` = a.`id_employee_cierre` AND a.`id_shop` = '.$this->context->shop->id.')';
        $this->_select .= '
        CONCAT_WS(" ",ea.firstname, ea.lastname) as empleado_apertura, 
        CONCAT_WS(" ",ea.firstname, ea.lastname) as empleado_cierre, 
        IF(a.estado = 1, "Arqueo Abierto", "Arqueo Cerrado") estado_caja, 
        estado, 
        (( SELECT SUM(op.amount)
FROM tm_orders o INNER JOIN tm_order_payment op
ON (o.reference = op.order_reference)
where o.current_state in (1, 2)  AND tipo_pago = 1 AND op.date_add BETWEEN a.fecha_apertura AND IF(a.fecha_cierre = 0, CURRENT_TIMESTAMP(), a.fecha_cierre) ) + 
IFNULL((select SUM(rc.adelanto) from tm_reservar_cita rc WHERE rc.date_upd BETWEEN a.fecha_apertura AND IF(a.fecha_cierre = 0, CURRENT_TIMESTAMP(), a.fecha_cierre) AND estado_actual = 0 AND adelanto > 0), 0)) 
 as ventas, 
        ( SELECT SUM(monto)
FROM tm_pos_gastos
where fecha BETWEEN a.fecha_apertura AND IF(a.fecha_cierre = 0, CURRENT_TIMESTAMP(), a.fecha_cierre) ) as gastos,       
        a.id_pos_arqueoscaja as id_button_cierre,
        ((monto_apertura + ( SELECT SUM(op.amount)
FROM tm_orders o INNER JOIN tm_order_payment op
ON (o.reference = op.order_reference)
where o.current_state in (1, 2)  AND tipo_pago = 1 AND op.date_add BETWEEN a.fecha_apertura AND IF(a.fecha_cierre = 0, CURRENT_TIMESTAMP(), a.fecha_cierre) ) + IFNULL((select SUM(rc.adelanto) from tm_reservar_cita rc WHERE rc.date_upd BETWEEN a.fecha_apertura AND IF(a.fecha_cierre = 0, CURRENT_TIMESTAMP(), a.fecha_cierre) AND estado_actual = 0 AND adelanto > 0), 0)) - IFNULL(( SELECT SUM(monto)
FROM tm_pos_gastos
where fecha BETWEEN a.fecha_apertura AND IF(a.fecha_cierre = 0, CURRENT_TIMESTAMP(), a.fecha_cierre) ), 0)) as cierre_sistema
        
        
        ';

        $this->_where = Shop::addSqlRestriction(false, 'a');

        $this->fields_list = array(
//            'id_pos_arqueoscaja' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'fecha_apertura' => array('title' => $this->l('Fecha Apertura'),  'type' => 'datetime', 'remove_onclick' => true),
            'empleado_apertura' => array('title' => $this->l('Cajero'),  'havingFilter' => true, 'remove_onclick' => true),
            'monto_apertura' => array('title' => $this->l('Monto Apertura'),  'type' => 'price', 'remove_onclick' => true),
            'ventas' => array('title' => $this->l('Ingresos'),  'type' => 'price',  'havingFilter' => true, 'remove_onclick' => true),
            'gastos' => array('title' => $this->l('Egresos'),  'type' => 'price',  'havingFilter' => true, 'remove_onclick' => true),
            'cierre_sistema' => array('title' => $this->l('Saldo Caja'),  'type' => 'price', 'remove_onclick' => true),
            'monto_cierre' => array('title' => $this->l('Cierre real'),  'type' => 'price', 'remove_onclick' => true),
            'fecha_cierre' => array('title' => $this->l('Fecha Cierre'),  'type' => 'datetime', 'remove_onclick' => true),
//            'estado_caja' => array('title' => $this->l('Estado'), 'align' => 'center', 'class' => 'fixed-width-sm', ),

        );

//        $nombre_access = Profile::getProfile($this->context->employee->id_profile);
//        if (isset($nombre_access['name']) && $nombre_access['name'] == "Cajero"){
//            $this->_where .= ' and a.id_employee_apertura = '.(int)$this->context->employee->id;
////          $this->_where .= 'AND tipo_documento_electronico in ("Boleta", "Factura") or current_state not in (2, 6) ';
//        }

        $this->_orderBy = 'fecha_apertura';
        $this->_orderWay = 'DESC';

    }
    public function displayCerrar_cajaLink($token = null, $id)
    {
        $arqueo = new PosArqueoscaja((int)$id);

        if ($arqueo->estado == 0){
//            if (($key = array_search($this->action, $this->actions)) !== false) {
//                unset($this->actions[$key]);
//            }
            return false;
        }else{
            $html = '<span class="btn-group-action">
                        <span class="btn-group">
                            <a class="btn btn-danger cierre" data-id_pos_arqueoscaja="' . $arqueo->id . '">
                                <i class="fa fa-level-up fa-lg"></i>&nbsp;' . $this->l('Cerrar Caja') . '
                            </a>
                        </span>
                    </span>';
            return $html;
        }

    }

    public function initPageHeaderToolbar()
    {

        if ($this->display == 'view') {
            $this->page_header_toolbar_btn['back_to_list'] = array(
                'href' => Context::getContext()->link->getAdminLink('AdminPosArqueoscaja'),
                'desc' => $this->l('Back to list', null, null, false),
                'icon' => 'process-icon-back'
            );
        }


        if (!$this->display) {
            $this->page_header_toolbar_btn['nuevo_arqueo_caja'] = array(
//            'href' => self::$currentIndex . '&token=' . $this->token.'&action=nuevoarqueo',
                'desc' => 'Nuevo Arqueo Caja',
                'icon' => 'process-icon-new',
//            'js' => 'window.open(\''.self::$currentIndex.'&add'.$this->table.'&token='.$this->token.'\',\''.'popupwindow \''.',\''.'width=500\',\'height=500\',\'scrollbars\',\'resizable\');',
                'js' => "abrirModalArqueo('#nuevo_arqueo" . $this->table . "');",
//            'modal_target' => '#nuevo_arqueo'.$this->table,
            );
        }

        parent::initPageHeaderToolbar(); // TODO: Change the autogenerated stub
    }


    public function initToolbar()
    {
        parent::initToolbar();

        unset($this->toolbar_btn['new']);

    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/datatables.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/datatables.min.js');

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/waitMe.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/waitMe.min.js');

        $this->addJS(_PS_JS_DIR_.'vendor/spin.js');
        $this->addJS(_PS_JS_DIR_.'vendor/ladda.js');
    }

    public function renderList()
    {

        $exist = count(PosCaja::getCajas());
        $this->context->smarty->assign(array(
            "exist_cajas" => $exist,
        ));

        return parent::renderList(); // TODO: Change the autogenerated stub
    }

    //metodo para abrir caja
    public function ajaxProcessAbrirCaja()
    {

//        d(Tools::getAllValues());
        if (Tools::getValue('id_pos_caja')){
            $obj_caja = new PosCaja((int)Tools::getValue('id_pos_caja'));
            $obj = new PosArqueoscaja();
            $obj->nombre_caja = $obj_caja->nombre_caja." - ".$this->context->shop->name;
            $obj->monto_apertura = (float)Tools::getValue('monto_apertura');
            $obj->monto_operaciones = (float)Tools::getValue('monto_apertura');
            $obj->fecha_apertura = date('Y-m-d H:i:s');
            $obj->nota_apertura = Tools::getValue('nota_apertura');
            $obj->estado = 1; // 0 cerrada 1 abierta
            $obj->id_employee_apertura = $this->context->employee->id;
            $obj->id_shop = $this->context->shop->id;
            $obj->id_pos_caja = $obj_caja->id;
            $res = $obj->add();

            $obj_caja->estado_apertura = 1;
            $obj_caja->update();

            if (!$res) {
                die(json_encode(array(
                    'result' => $res,
                    'error' => $this->trans('A ocurrido un error al hacer la apertura.', array(), 'Admin.Orderscustomers.Notification')
                )));
            }

            die(json_encode(array(
                'result' => $res,
                'obj' => $obj,
            )));
        }else{
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('No hay ninguna caja creada.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }
    }

    //metodo para cerrar caja
    public function ajaxProcessCerrarCaja()
    {

//        d(Tools::getAllValues());
        if (Tools::getValue('id_pos_arqueoscaja')){

            $obj = new PosArqueoscaja((int)Tools::getValue('id_pos_arqueoscaja'));

            $obj_caja = new PosCaja((int)$obj->id_pos_caja);
            $obj_caja->estado_apertura = 0;
            $obj_caja->update();

            $obj->monto_cierre = (float)Tools::getValue('monto_cierre');
            $obj->fecha_cierre = date('Y-m-d H:i:s');
            $obj->nota_cierre = Tools::getValue('nota_cierre');
            $obj->estado = 0; // 0 cerrada 1 abierta
            $obj->id_employee_cierre = $this->context->employee->id;
            $res = $obj->update();

            if (!$res) {
                die(json_encode(array(
                    'result' => $res,
                    'error' => $this->trans('A ocurrido un error al hacer el cierre.', array(), 'Admin.Orderscustomers.Notification')
                )));
            }

            die(json_encode(array(
                'result' => $res,
                'obj' => $obj,
            )));
        }else{
            die(json_encode(array(
                'result' => false,
                'error' => $this->trans('No hay ninguna caja creada.', array(), 'Admin.Orderscustomers.Notification')
            )));
        }
    }

}