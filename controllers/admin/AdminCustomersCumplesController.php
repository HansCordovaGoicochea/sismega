<?php

use Sunat\Sunat;
$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
require $baseDir.'/vendor/getSunat/autoload.php';

/**
 * @property Customer $object
 */
class AdminCustomersCumplesControllerCore extends AdminController
{
    protected $delete_mode;

    protected $_defaultOrderBy = 'date_add';
    protected $_defaultOrderWay = 'DESC';
    protected $can_add_customer = true;
    protected static $meaning_status = array();

    public function __construct()
    {
        $this->bootstrap = true;
        $this->required_database = true;
        $this->table = 'customer';
        $this->className = 'Customer';
        $this->lang = false;
        $this->deleted = true;
        $this->explicitSelect = true;

        $this->allow_export = true;

//        $this->list_simple_header = true;

        parent::__construct();


        $this->default_form_language = $this->context->language->id;


        $documents_array = array();
        $documents = Tipodocumentolegal::getAllTipDoc();
        foreach ($documents as $document) {
            $documents_array[$document['id_tipodocumentolegal']] = $document['nombre'];
        }

        $this->_join .= 'LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.')';
        $this->_join .= 'LEFT JOIN '._DB_PREFIX_.'tipodocumentolegal tdl ON (a.id_document = tdl.id_tipodocumentolegal)';
        $this->_use_found_rows = false;
        $this->_where .= " AND id_customer > 1 ";
        if ($birthday = Tools::getValue('birthday')){
//            d(Tools::getFormatFechaGuardar($birthday));
            $this->_where .= "
            AND MONTH(a.birthday) = EXTRACT(MONTH FROM '".Tools::getFormatFechaGuardar($birthday)."')
            AND DAY(a.birthday) = EXTRACT(DAY FROM '".Tools::getFormatFechaGuardar($birthday)."')";
        }else{
            $this->_where .= "
            AND MONTH(a.birthday) = EXTRACT(MONTH FROM CURDATE())
            AND DAY(a.birthday) = EXTRACT(DAY FROM CURDATE())";
        }


        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'class' => 'hide',
                'align' => 'hide',
                'search' => false,
            ),
//            'date_add' => array(
//                'title' => $this->trans('Registration', array(), 'Admin.Orderscustomers.Feature'),
//                'type' => 'date',
//                'align' => 'text-right'
//            ),
            'firstname' => array(
                'title' => $this->trans('Cliente', array(), 'Admin.Global'),
                'search' => false,
                'remove_onclick' => false,
            ),
//            'tipo_documento' => array(
//                'type' => 'select',
//                'title' => $this->trans('T/D', array(), 'Admin.Global'),
//                'filter_key' => 'a!id_document',
//                'list' => $documents_array,
//                'filter_type' => 'int',
//                'order_key' => 'tdl!nombre'
//            ),
            'num_document' => array(
                'title' => $this->trans('Num. Documento', array(), 'Admin.Global'),
                'remove_onclick' => false,
                'search' => false,

            ),
            'birthday' => array(
                'title' => $this->trans('Fecha Nacimiento', array(), 'Admin.Orderscustomers.Feature'),
                'type' => 'date',
                'remove_onclick' => false,
                'search' => false,
//                'align' => 'text-right'
            ),
        );

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_CUSTOMER;

        $this->_select = '
        a.date_add, gl.name as title, tdl.nombre as tipo_documento, (
            SELECT SUM(total_paid_real)
            FROM '._DB_PREFIX_.'orders o
            WHERE o.id_customer = a.id_customer
            '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
            AND o.valid = 1
        ) as total_spent, (
            SELECT c.date_add FROM '._DB_PREFIX_.'guest g
            LEFT JOIN '._DB_PREFIX_.'connections c ON c.id_guest = g.id_guest
            WHERE g.id_customer = a.id_customer
            ORDER BY c.date_add DESC
            LIMIT 1
        ) as connect';

    }

    public function renderList()
    {
        $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        $this->tpl_list_vars['POST'] = $_POST;

        return parent::renderList();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitFilterCumples')
            && ($birthday = Tools::getValue('birthday'))) {
//            d($birthday);
            $this->tpl_list_vars['birthday'] = $birthday;
            Tools::redirectAdmin(self::$currentIndex.'&birthday='.$birthday.'&token='.$this->token);
        }

        parent::postProcess();
    }



    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }
}
