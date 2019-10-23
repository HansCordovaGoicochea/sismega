<?php

class PosCajaCore extends ObjectModel
{

    public $nombre_caja;
    public $id_employee;
    public $id_shop;
    public $estado_apertura; // 0 cerrada 1 abierta
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'pos_caja',
        'primary' => 'id_pos_caja',
        'fields' => array(
            // Lang fields
            'nombre_caja' => array('type' => self::TYPE_STRING),
            'id_employee' => array('type' => self::TYPE_INT),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'estado_apertura' => array('type' => self::TYPE_BOOL),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        ),
    );

    public static function getCajas()
    {

        return Db::getInstance()->executeS('
			SELECT *
			FROM `' . _DB_PREFIX_ . 'pos_caja`
			WHERE estado_apertura = 0 AND `id_shop` = ' . Context::getContext()->shop->id . '
		');

    }
}
