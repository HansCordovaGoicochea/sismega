<?php
/**
* 
*/
class NumeracionDocumentoCore extends ObjectModel
{
	public $correlativo;
    public $nombre;
    public $serie;
    public $id_shop;

	public static $definition = array(
        'table' => 'numeracion_documentos',
        'primary' => 'id_numeracion_documentos',
        'fields' => array(
            'correlativo' =>  array('type' => self::TYPE_INT),
            'nombre' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'required' => false),
            'serie' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'required' => false),
            'id_shop' =>  array('type' => self::TYPE_INT),

        ),
    );

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
    }

    public static function getNumTipoDoc($nombre){
        $sql = new DbQuery();
        $sql->select('el.*');
        $sql->from('numeracion_documentos', 'el');
        $sql->where('el.nombre = \''.pSQL($nombre).'\'');
        $sql->where('el.id_shop = '.Context::getContext()->shop->id);

        return Db::getInstance()->getRow($sql);
    }

    public static function getlastBoletaFisica(){
        $sql = new DbQuery();
        $sql->select('el.*');
        $sql->from('numeracion_documentos', 'el');
        $sql->where('el.nombre = "Boleta_fisica"');
        $sql->where('el.id_shop = '.Context::getContext()->shop->id);

        return Db::getInstance()->getRow($sql);
    }

    public static function getlastFacturaFisica(){
        $sql = new DbQuery();
        $sql->select('el.*');
        $sql->from('numeracion_documentos', 'el');
        $sql->where('el.nombre = "Factura_fisica"');
        $sql->where('el.id_shop = '.Context::getContext()->shop->id);

        return Db::getInstance()->getRow($sql);
    }

}