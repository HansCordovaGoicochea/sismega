<?php

class AdminReporteVentasControllerCore extends AdminController {

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
        $ultimoDia = Tools::getValue('fecha_termino');

        if( !$primerDia && $primerDia == '' && !$ultimoDia  && $ultimoDia==''){
            $date_current=getdate();
            //d($date_current);
            $year=$date_current['year'];
            $month=$date_current['mon'];
            $day=$date_current['mday'];
            // Obtenemos el numero de la semana
            $semana=date("W",mktime(0,0,0,$month,$day,$year));
            // Obtenemos el día de la semana de la fecha dada
            $diaSemana=date("w",mktime(0,0,0,$month,$day,$year));
            // el 0 equivale al domingo...
            if($diaSemana==0)
                $diaSemana=7;
            // A la fecha recibida, le restamos el dia de la semana y obtendremos el lunes
//            $primerDia=date("Y-m-d",mktime(0,0,0,$month,$day-$diaSemana+1,$year));
//            // A la fecha recibida, le sumamos el dia de la semana menos siete y obtendremos el domingo
//            $ultimoDia=date("Y-m-d",mktime(0,0,0,$month,$day+(7-$diaSemana),$year));
//
            // A la fecha recibida, le restamos el dia de la semana y obtendremos el lunes
            $primerDia=date("Y-m-d");
            // A la fecha recibida, le sumamos el dia de la semana menos siete y obtendremos el domingo
            $ultimoDia=date("Y-m-d");
        }
        else{
            $primerDia = str_replace('/', '-', $primerDia);
            $primerDia= date('Y-m-d', strtotime($primerDia));
            $ultimoDia = str_replace('/', '-', $ultimoDia);
            $ultimoDia= date('Y-m-d', strtotime($ultimoDia));
        }

        $aperturas_cajas = PosArqueoscaja::getAllByDates($this->context->shop->id, $primerDia, $ultimoDia);
//        d($aperturas_cajas);


       foreach ($aperturas_cajas as &$item) {
           if ((int)$item['estado'] == 1){
               $month = date('m');
               $year = date('Y');
               $day = date("d", mktime(23, 59, 59, $month+1, 0, $year));
               $item['fecha_cierre'] = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year));
           }

          $array_con_operaciones_efectivo = Order::getOrdersDateFromDateTOEfectivo($this->context->shop->id, $item['fecha_apertura'], $item['fecha_cierre']);
          $array_con_operaciones_visa = Order::getOrdersDateFromDateTOVisa($this->context->shop->id, $item['fecha_apertura'], $item['fecha_cierre']);
          $array_con_operaciones_izipay = Order::getOrdersDateFromDateTOIzipay($this->context->shop->id, $item['fecha_apertura'], $item['fecha_cierre']);
          $array_con_operaciones_porcobrar = Order::getOrdersDateFromDateTOPorCobrar($this->context->shop->id, $item['fecha_apertura'], $item['fecha_cierre']);
          $array_con_operaciones_egresos =PosGastos::getDateFromDateTOEgresos($this->context->shop->id, $item['fecha_apertura'], $item['fecha_cierre']);
          $array_con_operaciones_adelantos =ReservarCita::getDateFromDateTOAdelantos($this->context->shop->id, $item['fecha_apertura'], $item['fecha_cierre']);
//           d($array_con_operaciones_porcobrar);

            $item['efectivo'] = $array_con_operaciones_efectivo;
            $item['visa'] = $array_con_operaciones_visa;
            $item['izipay'] = $array_con_operaciones_izipay;
            $item['porcobrar'] = $array_con_operaciones_porcobrar;
            $item['egresos'] = $array_con_operaciones_egresos;
            $item['adelantos'] = $array_con_operaciones_adelantos;

           $empleado_apertura = new Employee((int)$item['id_employee_apertura']);
           $item['empleado_apertura'] = $empleado_apertura;

       }
       unset($item);


       $this->display='ReporteVentas';
       $this->tpl_folder='controllers'.DIRECTORY_SEPARATOR.Tools::toUnderscoreCase(substr($this->controller_name, 5)).'/';
//       d($this->tpl_folder);
       $this->context->smarty->assign(array(
           'fecha_inicio'=>$primerDia,
           'fecha_termino'=>$ultimoDia,
           'context_shop'=>$this->context->shop->id,
           'aperturas_cajas'=>$aperturas_cajas,

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

        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/jspdf.debug.js');
        $this->addJs(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/js/jspdf.plugin.autotable.js');

    }

}
