<?php

class TipodocumentolegalCore extends ObjectModel {
    //put your code here


    public $nombre;
    public $descripcion;
    public $cod_sunat;

    public static $definition = array(
        'table' => 'tipodocumentolegal',
        'primary' => 'id_tipodocumentolegal',
        'fields' => array(
            'nombre' => array('type' => self::TYPE_STRING),
            'descripcion' => array('type' => self::TYPE_STRING),
            'cod_sunat' => array('type' => self::TYPE_STRING),
        ),
    );
    public static function getAllTipDoc()
	{
		$query = new DbQuery();
		$query->select('*');
		$query->from('tipodocumentolegal');
		$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (count($res))
            return $res;
        else
           return false;
                
	}
    public static function getByCodSunat($cod_sunat)
	{
		$query = new DbQuery();
		$query->select('*');
		$query->from('tipodocumentolegal');
		$query->where('cod_sunat = \''.$cod_sunat.'\'');
		$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if (count($res))
            return $res;
        else
           return false;

	}
    public static function getById($id_tipodocumentolegal)
	{
		$query = new DbQuery();
		$query->select('*');
		$query->from('tipodocumentolegal');
		$query->where('id_tipodocumentolegal = '.$id_tipodocumentolegal);
		$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if (count($res))
            return $res;
        else
           return false;

	}
}

?>
