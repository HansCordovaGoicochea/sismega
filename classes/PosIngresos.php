<?php

class PosIngresosCore extends ObjectModel
{

    public $fecha;
    public $descripcion;
    public $monto;
    public $id_pos_caja;
    public $id_shop;
    public $id_employee;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'pos_ingresos',
        'primary' => 'id_pos_ingresos',
        'fields' => array(
            // Lang fields
            'fecha' => array('type' => self::TYPE_DATE),
            'descripcion' => array('type' => self::TYPE_STRING),
            'monto' => array('type' => self::TYPE_FLOAT),
            'id_pos_caja' => array('type' => self::TYPE_INT, 'required' => true),
            'id_employee' => array('type' => self::TYPE_INT),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        ),
    );

    public static function getDateFromDateTOIngresos($shop, $date_from, $date_to )
    {
//        d($id_caja);
        $sql =
            'select * from tm_pos_ingresos pg
                WHERE pg.id_shop = ' . $shop . ' and pg.fecha >= \'' . $date_from . '\' and pg.fecha <= \'' . $date_to . '\' order by id_pos_ingresos desc
                ';
//
//        var_dump($sql);
//        echo '<br/>';
//        d($sql);

        return Db::getInstance()->executeS($sql);
    }


}
