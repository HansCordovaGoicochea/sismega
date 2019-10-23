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
class AdminReporteServiciosColaboradorControllerCore extends AdminController
{

    protected $_default_pagination = 20;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order_detail';
        $this->className = 'OrderDetail';
        $this->lang = false;
        $this->multishop_context = Shop::CONTEXT_ALL;
//        $this->addRowAction('edit');
//        $this->addRowAction('delete');
//        $this->addRowAction('view');

        $this->context = Context::getContext();

        parent::__construct();


        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'employee` ea ON (ea.`id_employee` = a.`id_colaborador`) ';
        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = a.`id_order` AND o.`id_shop` = '.$this->context->shop->id.') ';
        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'customer` ec ON (ec.`id_customer` = o.`id_customer`) ';

        $this->_select .= 'CONCAT_WS(" ",ea.firstname, ea.lastname) as colaborador, ec.firstname as cliente, o.date_add as fecha, SUM(product_quantity) as cantidad, sum(a.total_price_tax_incl) as total_servicio';
        $this->_where = ' AND a.id_colaborador = '. Tools::getValue('id_colaborador').' AND valid = 1 AND o.date_add BETWEEN '.Tools::getValue('fi').' AND '.Tools::getValue('ff');


//        $this->_orderBy = 'o.date_add';
//        $this->_orderWay = 'DESC';
        $this->_group = ' group by a.product_id';
//        $this->list_simple_header = true;
//        $this->allow_export = true;

        $this->fields_list = array(
            'id_order_detail' => array(
                'title' => $this->l('ID'),
                'align' => 'hide',
                'class' => 'hide',
                'remove_onclick' => true,
                'search' => false
            ),
//            'colaborador' => array(
//                'title' => $this->l('Colaborador'),
//                'remove_onclick' => true,
//                'search' => false
//            ),
//            'fecha' => array(
//                'title' => $this->l('Fecha'),
//                'remove_onclick' => true,
//                'search' => false
//            ),
//            'cliente' => array(
//                'title' => $this->l('Cliente'),
//                'remove_onclick' => true,
//                'search' => false
//            ),
            'product_name' => array(
                'title' => $this->l('Producto/Servicio'),
                'remove_onclick' => true,
                'search' => false
            ),
            'cantidad' => array(
                'title' => $this->l('Cantidad'),
                'remove_onclick' => true,
                'search' => false
            ),
            'total_servicio' => array(
                'title' => $this->l('Importe'),
                'type' => 'price',
                'remove_onclick' => true,
                'search' => false
            ),

        );

    }

//    public function renderList()
//    {
//        //retrieve datas list
//        $this->getList($this->context->language->id);
//        $total_ps = 0;
//        $total_importe = 0;
//
//        foreach ($this->_list as $k => $v) {
//            $total_ps += $this->_list[$k]['cantidad'];
//            $total_importe += $this->_list[$k]['total_servicio'];
//        }
//
//
////        $helper = new HelperList($this);
////        $helper->title = 'Prod./Serv.: '. $total_ps .' - Importe: '. Tools::displayPrice($total_importe, 1);
////        d($helper->title);
//
//
//        return parent::renderList();
//    }


    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);

    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();


            $this->page_header_toolbar_btn['back_to_list'] = array(
                'href' => Context::getContext()->link->getAdminLink('AdminDashboard'),
                'desc' => $this->l('Back to list', null, null, false),
                'icon' => 'process-icon-back'
            );
        $this->getList($this->context->language->id);
        $total_ps = 0;
        $total_importe = 0;

        foreach ($this->_list as $k => $v) {
            $total_ps += $this->_list[$k]['cantidad'];
            $total_importe += $this->_list[$k]['total_servicio'];
        }


//        array_pop($this->toolbar_title);
//        $this->toolbar_title[] = 'Prod./Serv.: 6 - Importe: S/121.00';
        $this->page_header_toolbar_title = 'Prod./Serv.: '. $total_ps .' - Importe: '. Tools::displayPrice($total_importe, 1);
//d($this->page_header_toolbar_title);
    }

}