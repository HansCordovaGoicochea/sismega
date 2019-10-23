<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminNumeracionComandaController
 *
 * @author César
 */
class AdminNumeracionDocumentosControllerCore extends AdminController {
    //put your code here
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'NumeracionDocumento';
        $this->table = 'numeracion_documentos';
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->context = Context::getContext();

        parent::__construct();
        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            return $this->errors[] = $this->trans('Tiene que seleccionar una tienda antes.', array(), 'Admin.Orderscustomers.Notification');
        }

        $this->_where = Shop::addSqlRestriction(false, 'a');

        $this->fields_list = array(
            'id_numeracion_documentos' => array('title' => $this->l('ID'), 'align' => 'center'),
            'nombre' => array('title' => $this->l('Nombre'), 'align' => 'center'),
            'serie' => array('title' => $this->l('Serie'), 'align' => 'center'),
            'correlativo' => array('title' => $this->l('Correlativo'), 'align' => 'center'),
        );


    }
    
    public function  renderForm(){


        $this->fields_value['id_shop'] = (int)$this->context->shop->id;

        $this->fields_form = array(
            'legend' => array(
                'tittle'=>$this->l('Texto'),
                'icon'=>'icon_group'
            ),
            'input' => array(
                array(
                    'type'=>'text',
                    'label'=>$this->l('Nombre'),
                    'name'=>'nombre',
                    'required'=>true,
                    'desc' => 'Boleta, Factura, NotaCredito, ComunicacionBaja, ResumenDiario',
                    ),
                array(
                    'type'=>'text',
                    'label'=>$this->l('Serie'),
                    'name'=>'serie',
                    'required'=>true,
                    'maxlength' => 4,
                    'hint'=>'Para Boleta (Bxxx), para Factura (Fxxx), Nota de credito (FCxx), Comunicación Baja (RA), Resumen diario de Boletas (RC)',
                    'desc' => 'Para Boleta (Bxxx), para Factura (Fxxx), Nota de credito (FCxx), Comunicación Baja (RA), Resumen diario de Boletas (RC)',
                    ),
                array(
                    'type'=>'numeric',
                    'label'=>$this->l('Correlativo'),
                    'name'=>'correlativo',
                    'required'=>true,
                    'hint'=>'Número anterior del que quiere empezar o continuar'
                    ),
                array(
                    'type' => 'hidden',
                    'name' => 'id_shop',
                ),
            ),

            'submit' => array(
                'title' => $this->l('Guardar'),
            )
        );

        return parent::renderForm();
    }
    

}

