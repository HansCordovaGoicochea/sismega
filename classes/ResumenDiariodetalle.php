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
class ResumenDiariodetalleCore extends ObjectModel
{

    public $id_resumen_diario;
    public $line_id;
    public $tipo_documento; //<!-- Tipo de documento - Boleta - NotaCredito -->
    public $codigo_tipo_documento; //<!-- Tipo de documento - Catalogo No. 01 -->
    public $serie_correlativo; // <!-- Serie y número de comprobante -->
//    public $numero_documento_cliente; //<!-- Numero de documento de identidad -->
//    public $codigo_tipo_documento_cliente; //<!-- Tipo de documento de identidad - Catalogo No. 06 -->
    public $status_comprobante; //<!-- (Codigo de operacion del item - catalogo No. 19) -->
    public $id_currency; //moneda
    public $importe_total; // <!-- Importe total de la venta, sesion en uso o del servicio prestado -->
    public $importe_operaciones_gravadas; //<!-- Total valor de venta - operaciones gravadas -->
    public $importe_operaciones_exoneradas; //<!-- Total valor de venta - operaciones exoneradas -->
    public $importe_operaciones_inafectas; //<!-- Total valor de venta - operaciones inafectas -->
    public $importe_otras_operaciones; //<!-- Importe total de sumatoria otros cargos del item -->
    public $total_igv; //<!-- Total IGV -->
    public $total_impuestos_otros; //<!-- Total Otros tributos -->

    //solamente para notas de credito
    public $codigo_tipo_documento_referencia; //<!-- Tipo de comprobante modificado - Catalogo No. 01 -->
    public $serie_correlativo_referenciar; // <!-- Serie y numero de comprobante modificado -->

    public $id_pos_ordercomprobantes;
    public $motivo;
    public $devolver_montos;


    /**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'resumen_diariodetalle',
		'primary' => 'id_resumen_diariodetalle',
		'fields' => array(
			// Lang fields
                'id_resumen_diario' => array('type' => self::TYPE_INT),
                'id_pos_ordercomprobantes' => array('type' => self::TYPE_INT),
                'line_id' => array('type' => self::TYPE_INT),
                'tipo_documento' => array('type' => self::TYPE_STRING),
                'codigo_tipo_documento' => array('type' => self::TYPE_STRING),
                'serie_correlativo' => array('type' => self::TYPE_STRING),
                'status_comprobante' => array('type' => self::TYPE_STRING),
                'id_currency' => array('type' => self::TYPE_INT),
                'importe_total' => array('type' => self::TYPE_FLOAT),
                'importe_operaciones_gravadas' => array('type' => self::TYPE_FLOAT),
                'importe_operaciones_exoneradas' => array('type' => self::TYPE_FLOAT),
                'importe_operaciones_inafectas' => array('type' => self::TYPE_FLOAT),
                'importe_otras_operaciones' => array('type' => self::TYPE_FLOAT),
                'total_igv' => array('type' => self::TYPE_FLOAT),
                'total_impuestos_otros' => array('type' => self::TYPE_FLOAT),
                'motivo' => array('type' => self::TYPE_STRING),
                'devolver_montos' => array('type' => self::TYPE_STRING),

                //solamente para notas de credito
                'codigo_tipo_documento_referencia' => array('type' => self::TYPE_STRING),
                'serie_correlativo_referenciar' => array('type' => self::TYPE_STRING),


		),
	);

    public static function getDetalleFacturaID($id_resumen_diario)
    {
//        d($id_resumen_diario);
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'resumen_diariodetalle` WHERE `id_resumen_diario` = \''.pSQL($id_resumen_diario).'\'
					';
        return Db::getInstance()->ExecuteS($sql);
    }
}
