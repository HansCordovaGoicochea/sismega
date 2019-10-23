<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Caja
 *
 * @author 01101801
 */
class ResumenDiarioCore extends ObjectModel
{

    public $identificador_resumen_diario;
    public $fecha_generacion_resumen_diario; // Fecha de generacion del resumen -->
    public $fecha_emision_comprobantes; //<!-- Fecha de emision de los documentos -->
    public $nota_resumen_diario;
    public $id_shop;
    public $id_employee;
    public $mensaje_cdr;
    public $nro_ticket;
    public $cod_sunat;
    public $msj_sunat;
    public $respuesta;
    public $ruta_xml;
    public $ruta_cdr;
    public $hash_cdr;
    public $date_add;
    public $date_upd;



    /**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'resumen_diario',
		'primary' => 'id_resumen_diario',
		'fields' => array(
			// Lang fields
            'identificador_resumen_diario' => array('type' => self::TYPE_STRING),
            'fecha_generacion_resumen_diario' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'fecha_emision_comprobantes' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'nota_resumen_diario' => array('type' => self::TYPE_STRING),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'mensaje_cdr' => array('type' => self::TYPE_STRING),
            'nro_ticket' => array('type' => self::TYPE_STRING),
            'cod_sunat' => array('type' => self::TYPE_INT),
            'msj_sunat' => array('type' => self::TYPE_STRING),
            'respuesta' => array('type' => self::TYPE_STRING),
            'ruta_xml' => array('type' => self::TYPE_STRING),
            'ruta_cdr' => array('type' => self::TYPE_STRING),
            'hash_cdr' => array('type' => self::TYPE_STRING),

            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
		),
	);
}
