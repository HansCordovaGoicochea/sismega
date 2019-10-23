<?php

class PosOrdercomprobantesCore extends ObjectModel
{

    public $id_order;

    //datos facturacion electronica
    public $tipo_documento_electronico;
    public $numero_comprobante;
    public $hash_cpe;
    public $ruta_xml;
    public $hash_cdr;
    public $ruta_cdr;
    public $cod_sunat;
    public $msj_sunat;
    public $ruta_ticket;
    public $ruta_pdf_a4;

    public $nota_baja;
    public $numeracion_nota_baja;
    public $motivo_baja;
    public $mensaje_cdr;

    public $code_motivo_nota_credito;

    public $valor_qr_nota;
    public $ruta_pdf_a4nota;
    public $identificador_comunicacion;

    public $devolver_monto_caja; //1 DEVUELTO 0 NO DEVUELTO

    public $sub_total;
    public $impuesto;
    public $total;

    public $date_add;
    public $date_upd;
    public $estado_envio_sunat;
    public $cod_sunat_otro;
    public $ruta_xml_otro;
    public $ruta_cdr_otro;
    public $fecha_envio_comprobante;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'pos_ordercomprobantes',
        'primary' => 'id_pos_ordercomprobantes',
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT),

            //datos facturacion electronica
            'tipo_documento_electronico' => array('type' => self::TYPE_STRING),
            'numero_comprobante' => array('type' => self::TYPE_STRING),
            'hash_cpe' => array('type' => self::TYPE_STRING),
            'ruta_xml' => array('type' => self::TYPE_STRING),
            'hash_cdr' => array('type' => self::TYPE_STRING),
            'ruta_cdr' => array('type' => self::TYPE_STRING),
            'cod_sunat' => array('type' => self::TYPE_INT),

            'msj_sunat' => array('type' => self::TYPE_STRING),
            'ruta_ticket' => array('type' => self::TYPE_STRING),
            'ruta_pdf_a4' => array('type' => self::TYPE_STRING),

            'nota_baja' => array('type' => self::TYPE_STRING),
            'numeracion_nota_baja' => array('type' => self::TYPE_STRING),
            'motivo_baja' => array('type' => self::TYPE_STRING),
            'mensaje_cdr' => array('type' => self::TYPE_STRING),

            'code_motivo_nota_credito' => array('type' => self::TYPE_INT),

            'valor_qr_nota' => array('type' => self::TYPE_STRING),
            'ruta_pdf_a4nota' => array('type' => self::TYPE_STRING),
            'identificador_comunicacion' => array('type' => self::TYPE_STRING),

            'devolver_monto_caja' => array('type' => self::TYPE_BOOL), //1 DEVUELTO 0 NO DEVUELTO
            'sub_total' => array('type' => self::TYPE_FLOAT),
            'impuesto' => array('type' => self::TYPE_FLOAT),
            'total' => array('type' => self::TYPE_FLOAT),

            'date_add' => array('type' => self::TYPE_DATE),
            'date_upd' => array('type' => self::TYPE_DATE),
            'estado_envio_sunat' => array('type' => self::TYPE_BOOL), //1 enviado 0 NO enviado

            'cod_sunat_otro' => array('type' => self::TYPE_INT),
            'ruta_xml_otro' => array('type' => self::TYPE_STRING),
            'ruta_cdr_otro' => array('type' => self::TYPE_STRING),
            'fecha_envio_comprobante' => array('type' => self::TYPE_DATE),
        ),
    );


    public static function getComprobantesByOrder($id_order)
    {

        return Db::getInstance()->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'pos_ordercomprobantes`
			WHERE `id_order` = '.$id_order.'
			ORDER BY 1 ASC
		');

    }
    public static function getComprobantesByOrderLimit($id_order)
    {

        return Db::getInstance()->getRow('
			SELECT *
			FROM `'._DB_PREFIX_.'pos_ordercomprobantes`
			WHERE `id_order` = '.$id_order.'
			ORDER BY 1 DESC
		');

    }
    public static function getComprobantesByOrderAndFB($id_order)
    {

        return Db::getInstance()->getRow('
			SELECT *
			FROM `'._DB_PREFIX_.'pos_ordercomprobantes`
			WHERE `id_order` = '.$id_order.' AND `tipo_documento_electronico` in ("Factura", "Boleta")
			ORDER BY 1 DESC
		');

    }
    public static function getFacturaByOrderLimit($id_order)
    {

        return Db::getInstance()->getRow('
			SELECT *
			FROM `'._DB_PREFIX_.'pos_ordercomprobantes`
			WHERE `id_order` = '.$id_order.' AND `tipo_documento_electronico` = "Factura"
			ORDER BY 1 DESC
		');

    }
    public static function getBoletaByOrderLimit($id_order)
    {

        return Db::getInstance()->getRow('
			SELECT *
			FROM `'._DB_PREFIX_.'pos_ordercomprobantes`
			WHERE `id_order` = '.$id_order.' AND `tipo_documento_electronico` = "Boleta"
			ORDER BY 1 DESC
		');

    }
    public static function getNotaCreditoByOrderLimit($id_order)
    {

        return Db::getInstance()->getRow('
			SELECT *
			FROM `'._DB_PREFIX_.'pos_ordercomprobantes`
			WHERE `id_order` = '.$id_order.' AND `tipo_documento_electronico` = "NotaCredito"
			ORDER BY 1 DESC
		');

    }

    public static function getOrdersIdInvoiceByDateTipoComp($date_from, $boleta_nota)
    {

        $sql = 'SELECT po.*
                FROM `'._DB_PREFIX_.'pos_ordercomprobantes` po INNER JOIN  `'._DB_PREFIX_.'orders` o ON (po.id_order = o.id_order)
                WHERE DATE(po.fecha_envio_comprobante) = \''.pSQL($date_from).'\'
                    '.Shop::addSqlRestriction(false, 'o') .' AND tipo_documento_electronico = \''.bqSQL($boleta_nota).'\' ORDER BY po.date_add desc';

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

//        $orders = array();
//        foreach ($result as $order) {
//            $orders[] = (int)$order['id_order'];
//        }
        return $result;
    }


}
