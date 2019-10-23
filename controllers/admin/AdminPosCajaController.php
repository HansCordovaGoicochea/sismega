<?php

class AdminPosCajaControllerCore extends AdminController
{

    public function __construct()
	{
		$this->bootstrap = true;
	 	$this->table = 'pos_caja';
		$this->className = 'PosCaja';
	 	$this->lang = false;
		$this->context = Context::getContext();
        $this->addRowAction('edit');
		$this->addRowAction('delete');
//        $this->addRowAction('view');

        parent::__construct();

//		$this->bulk_actions = array(
//			'delete' => array(
//				'text' => $this->l('Delete selected'),
//				'confirm' => $this->l('Delete selected items?'),
//				'icon' => 'icon-trash'
//			)
//		);

        $this->_select .= 'CONCAT_WS(" ",ea.firstname, ea.lastname) as empleado_apertura, IF(a.estado_apertura = 1, "Caja Abierta", "Caja Cerrada") estado_caja, estado_apertura';
        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'employee` ea ON (ea.`id_employee` = a.`id_employee` AND a.`id_shop` = '.$this->context->shop->id.')';

		$this->fields_list = array(
			'id_pos_caja' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs',),
			'nombre_caja' => array('title' => $this->l('Nombre de Caja')),
            'empleado_apertura' => array('title' => $this->l('Empleado'), 'class' => 'fixed-width-lg', 'havingFilter' => true),
            'estado_caja' => array('title' => $this->l('Estado'), 'class' => 'fixed-width-lg', 'havingFilter' => true),
//			'estado_apertura' => array('title' => $this->l('Estado'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'class' => 'fixed-width-sm')
        );

	}



	public function initPageHeaderToolbar(){
		if (empty($this->display))
			$this->page_header_toolbar_btn['new_pos_caja'] = array(
				'href' => self::$currentIndex.'&addpos_caja&token='.$this->token,
				'desc' => $this->l('Crear Caja', null, null, false),
				'icon' => 'process-icon-new'
			);
		
		parent::initPageHeaderToolbar();
	}

	public function renderForm()
    {
        if (!($obj = $this->loadObject(true)))
            return;


        $this->fields_value['id_shop'] = (int)$this->context->shop->id;

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Caja'),
                'icon' => 'icon-group'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Nombre de Caja'),
                    'name' => 'nombre_caja',
                    'required' => true
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'id_shop',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );
        $this->fields_form['input'][] = array(
            'type' => 'select',
            'label' => $this->l('Encargado'),
            'name' => 'id_employee',
            'required' => true,
            'options' => array(
                'query' => Employee::getCajeros(true),
                'id' => 'id_employee',
                'name' => 'name_employee',
                'default' => array(
                    'value' => 0,
                    'label' => $this->l('-- Elija  Empleado --')
                )
            )
        );
        $this->fields_form['input'][] = array(
            'type' => 'switch',
            'label' => $this->l('Estado'),
            'name' => 'active',
            'required' => false,
            'is_bool' => true,
            'disabled' => true,
            'values' => array(
                array(
                    'id' => 'estado_on',
                    'value' => 1,
                    'label' => 'Abierto'
                ),
                array(
                    'id' => 'estado_off',
                    'value' => 0,
                    'label' => 'Cerrado'
                )
            ),
            'hint' => $this->l('Este campo nos indica si la caja esta abierta.')
        );
        return parent::renderForm();
    }
}
