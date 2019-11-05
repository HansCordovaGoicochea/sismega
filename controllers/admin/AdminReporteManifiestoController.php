<?php

class AdminReporteManifiestoControllerCore extends AdminController {

   public function __construct() {
       $this->bootstrap = true;
       $this->explicitSelect = true;
       $this->context = Context::getContext();
       parent::__construct();


   }
   public function display()
    {
        $shop_context = (!Shop::isFeatureActive() || Shop::getContext() == Shop::CONTEXT_SHOP);
        if (!$shop_context) {
            $this->errors[] = "Tienes activado el modo multitienda. Debes seleccionar una tienda.";
            return parent::display();
        }

        $primerDia = Tools::getValue('fecha_inicio');
        $nombre_producto = Tools::getValue('nombre_producto');

        if( !$primerDia && $primerDia == ''){
            $primerDia=date("Y-m-d");
        }
        else{
            $primerDia = str_replace('/', '-', $primerDia);
            $primerDia= date('Y-m-d', strtotime($primerDia));
        }

        $order_detail = OrderDetail::getDeailtServiciosByDate($primerDia, Tools::getValue('id_product', 0));

//        d($order_detail);
       $this->display='ReporteManifiesto';
       $this->tpl_folder='controllers'.DIRECTORY_SEPARATOR.Tools::toUnderscoreCase(substr($this->controller_name, 5)).'/';
//       d($this->tpl_folder);
       $this->context->smarty->assign(array(
           'fecha_inicio'=>$primerDia,
           'context_shop'=>$this->context->shop->id,
           'order_detail'=>$order_detail,
           'id_product'=>Tools::getValue('id_product', 0),
           'nombre_producto'=>Tools::getValue('nombre_producto', ""),
        )
       );
       parent::display();
   }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS(_PS_JS_DIR_.'jszip.min.js');
        $this->addJS(_PS_JS_DIR_.'shieldui-all.min.js');
        $this->addCSS(_PS_CSS_DIR_.'all.min.css');

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/waitMe.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/waitMe.min.js');

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/template/controllers/reporte_manifiesto/css/select2-reserva.css');
        $this->addjQueryPlugin(array(
            'select2',
        ));
        $this->addJS(_PS_JS_DIR_.'jquery/plugins/select2/select2_locale_es.js');

        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/print.min.css');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/print.min.js');
    }

}
