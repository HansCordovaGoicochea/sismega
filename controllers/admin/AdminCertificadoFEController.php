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
class AdminCertificadoFEControllerCore extends AdminController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'certificadofe';
        $this->className = 'Certificadofe';
        $this->lang = false;
        $this->multishop_context = Shop::CONTEXT_ALL;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
//        $this->addRowAction('view');

        $this->context = Context::getContext();

        parent::__construct();

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->fields_list = array(
            'id_certificadofe' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'nombre' => array(
                'title' => $this->l('Nombre')
            ),
            'archivo' => array(
                'title' => $this->l('Certificado')
            ),
            'fecha_caducidad' => array(
                'title' => $this->l('Vence'),
                'type' => 'date',
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'active' => array(
                'title' => $this->trans('Enabled', array(), 'Admin.Global'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!active'
            ),

        );

//        $this->shopLinkType = 'shop';
//        $this->shopShareDatas = Shop::SHARE_ORDER;
        
    }
    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
            $this->page_header_toolbar_btn['new_caja'] = array(
                'href' => self::$currentIndex.'&addcertificadofe&token='.$this->token,
                'desc' => $this->l('Subir Certificado', null, null, false),
                'icon' => 'process-icon-new'
            );

        parent::initPageHeaderToolbar();
    }
    public function renderForm()
    {

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Certificado'),
                'icon' => 'icon-group'
            ),
            'input' => array(
                array(
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        $objFactura = new Certificadofe((int)Tools::getValue('id_certificadofe'));
        $arrary_tiendas = Shop::getContextListShopID(false);
        $this->context->smarty->assign(array(
            'nro_tiendas'=>count($arrary_tiendas),
            'objFactura'=>$objFactura,
        ));

        return parent::renderForm();
    }
    public function renderView()
    {
        return parent::renderView();
    }
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/waitMe.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/waitMe.min.js');

    }
    public function ajaxProcessAddCertificado()
    {

        try {
            if (Tools::getValue('id_certificadofe')) {
                $objFactura = new Certificadofe((int)Tools::getValue('id_certificadofe'));
            }
            else{
                $objFactura = new Certificadofe();
            }
//            d(Tools::getValue('total'));

            $objFactura->nombre = Tools::getValue('nombre');
//d($objFactura);
            $objFactura->clave_certificado = Tools::getValue('clave_certificado');

//            d(Tools::getValue('fecha_caducidad'));
            $objFactura->id_shop = $this->context->shop->id;
            $objFactura->id_employee = $this->context->employee->id;
            $objFactura->user_sunat = Tools::getValue('user_sunat');
            $objFactura->pass_sunat = Tools::getValue('pass_sunat');
            $objFactura->web_service_sunat = Tools::getValue('web_service_sunat');
            $objFactura->archivo = '';
            $objFactura->fecha_caducidad = Tools::getValue('fecha_caducidad');
            $objFactura->active = Tools::getValue('active');

            if (Tools::getValue('id_certificadofe'))
                $objFactura->update();
            else
                $objFactura->add();


            $tienda_actual = new Shop((int)$this->context->shop->id);

            $nombre_virtual_uri = $tienda_actual->virtual_uri;
            // para guardar el documento
            $ruta = '../'.$nombre_virtual_uri.'certificado/';
            if (!file_exists($ruta)) {
                mkdir($ruta, 0777, true);
            }
//            d($ruta);
            $campopdf = '';
//d($nombre_virtual_uri);
            if (isset($_FILES['archivo'])) {
                $tot = count($_FILES["archivo"]["name"]);
                //este for recorre el arreglo
                for ($i = 0; $i < $tot; $i++) {
                    //con el indice $i, poemos obtener la propiedad que desemos de cada archivo
                    //para trabajar con este
                    $tmp_name = $_FILES["archivo"]["tmp_name"];
                    $name = $_FILES["archivo"]["name"];
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $nombre_virtual_uri = str_replace('/','',$nombre_virtual_uri);
                    $nombre_acchivo_sub = $nombre_virtual_uri.'.'.$ext;
//                    d($tienda_actual->virtual_uri);
//                    d($nombre_virtual_uri);
                    $fichero_subido = $ruta . basename($nombre_virtual_uri!=''?$nombre_acchivo_sub:'certificado.'.$ext) ;
                    if (move_uploaded_file($tmp_name, $fichero_subido)) {
                        //si sube que se llene la data
                        $campopdf .= $name . ';';
                        $to_return = array('error' => false);
                    } else {
                        $to_return = array('error' => true);
                    }

                }
            }
//            d($fichero_subido);
            if (!empty($fichero_subido)){
                $objFactura = new Certificadofe($objFactura->id);
                $objFactura->archivo = $fichero_subido;
                $objFactura->update();
            }else{
                $objFactura = new Certificadofe($objFactura->id);
                $objFactura->archivo = Tools::getValue('nombre_archivo');;
                $objFactura->update();
            }


            $to_return = array('errors' => false, 'correcto' => 'Datos grabados correctamente', 'id_certificadofe' => $objFactura->id);

            echo Tools::jsonEncode($to_return);
            die();
        } catch (Exception $e) {
            // not a MySQL exception
//            $e->getMessage();
            $to_return = array('errors' => true, 'incorrecto' => $e->getMessage());

            echo Tools::jsonEncode($to_return);
            die();
        }

    }
}