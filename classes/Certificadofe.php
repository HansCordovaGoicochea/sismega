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
class CertificadofeCore extends ObjectModel
{
    public $id;
    public $nombre;
    public $archivo;
    public $id_shop;
    public $id_employee;
    public $date_add;
    public $date_upd;

    public $clave_certificado;
    public $user_sunat;
    public $pass_sunat;
    public $web_service_sunat;
    public $fecha_caducidad;
    public $active;

	public static $definition = array(
		'table' => 'certificadofe',
		'primary' => 'id_certificadofe',
		'fields' => array(
			// Lang fields
            'nombre' => array('type' => self::TYPE_STRING),
            'archivo' => array('type' => self::TYPE_STRING),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),

            'clave_certificado' => array('type' => self::TYPE_STRING, 'required' => true),
            'user_sunat' => array('type' => self::TYPE_STRING, 'required' => true),
            'pass_sunat' => array('type' => self::TYPE_STRING, 'required' => true),
            'web_service_sunat' => array('type' => self::TYPE_STRING, 'required' => true),
            'fecha_caducidad' => array('type' => self::TYPE_STRING, 'required' => true),
            'active' => array('type' => self::TYPE_BOOL),

		),
	);
    public static function getIdCertife($shop)
    {
        return (int)Db::getInstance()->getValue('
                SELECT `id_certificadofe`
                FROM `'._DB_PREFIX_.'certificadofe`
                WHERE `id_shop` = '.(int)$shop.' 
                order by `id_certificadofe` desc');
    }

    public static function getCertificado()
    {
        return (int)Db::getInstance()->getValue('
                SELECT `id_certificadofe`
                FROM `'._DB_PREFIX_.'certificadofe`
                order by `id_certificadofe` desc');
    }

    public static function getByAllShop()
    {
        return Db::getInstance()->getRow('
                SELECT *
                FROM `'._DB_PREFIX_.'certificadofe`
                order by `id_certificadofe` desc');
    }

}
