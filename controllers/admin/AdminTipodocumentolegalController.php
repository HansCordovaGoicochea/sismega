<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminTipoDocumentoController
 *
 * @author César
 */
class AdminTipodocumentolegalControllerCore extends AdminController {
    //put your code here
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Tipodocumentolegal';
        $this->table = 'tipodocumentolegal';
        
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        
        $this->context = Context::getContext();
        
        $this->fields_list = array(
            'id_tipodocumentolegal' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'nombre' => array('title' => $this->l('Nombre')),
            'descripcion' => array('title' => $this->l('Descripcion'))
        );
        
        parent::__construct();
    }
    
    public function  renderForm(){
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

                    ),
                array(
                    'type'=>'text',
                    'label'=>$this->l('Descripcion'),
                    'name'=>'descripcion',

                    ),
                array(
                    'type'=>'text',
                    'label'=>$this->l('Cod. Sunat'),
                    'name'=>'cod_sunat',
                    'required'=>true,

                ),
            ),
            'submit' => array(
                'title' => $this->l('Guardar'),
            )
        );
        return parent::renderForm();
    }
}
