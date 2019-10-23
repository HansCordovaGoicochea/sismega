<?php
/**
 * Created by PhpStorm.
 * User: sc2
 * Date: 14/11/2018
 * Time: 06:50 PM
 */

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
//d($baseDir);
//require $baseDir.'/vendor/xmlseclibs/xmlseclibs.php';
//require $baseDir.'/vendor/xmlseclibs/CustomHeaders.php';
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecEnc;

class FirmarDocumento
{
    public static function firmar_xml($data_comprobante, $ruta, $ruta_firma, $pass_firma, $nombre_archivo){

        $ruta_zip = $ruta;
        $ruta = $ruta.".xml";

        $ReferenceNodeName = 'ExtensionContent'; // el tag donde se colocara la firma
        $filename_cert = $ruta_firma; // el certificado
        $password = $pass_firma; // la clave del certificado
        $results = array();
        $worked = openssl_pkcs12_read(file_get_contents($filename_cert), $results, $password); // desempaquetar certificado

        if($worked) {
            $privateKey = $results['pkey']; // clave privada
            $publicKey = $results['cert']; // llave publica
            $domDocument = new DOMDocument();
            $domDocument->loadXML(file_get_contents($ruta)); //parsear xml


            $objSign = new XMLSecurityDSig(); // aqui puedo enviar el prefijo
//            d($objSign);
//                            d($order->numero_comprobante);
            $objSign->setCanonicalMethod(XMLSecurityDSig::C14N); // parsear el xml de forma canonical

            $objSign->addReference(
                $domDocument,
                XMLSecurityDSig::SHA1,
                array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
                $options = array('force_uri' => true)
            );
            $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
            // cargar la llave privada
            $objKey->loadKey($privateKey);
            // firmar el archivo xml
            $objSign->sign($objKey, $domDocument->getElementsByTagName($ReferenceNodeName)->item(0));
            // agregar la llave publica asociada
            $objSign->add509Cert($publicKey);

            $errors = $domDocument->getElementsByTagName($ReferenceNodeName);
            $exists = $errors->length > 0;

            if (!$exists ) {
                $respuesta["msj_error"] = "El XML esta vacio";
                $respuesta["respuesta"] = "error";
                return $respuesta;
            }

            $hash_cpe = $domDocument->getElementsByTagName($ReferenceNodeName)->item(0)->getElementsByTagName("Signature")->item(0)->getElementsByTagName("SignedInfo")->item(0)->getElementsByTagName("DigestValue")->item(0)->nodeValue;
            $content = $domDocument->saveXML(); // guardar el xml firmado
//            d($content);
            $fp = fopen($ruta,"wb");
            fwrite($fp,$content);
            fclose($fp);

            //zipperar el xml
            $zip = new ZipArchive();
            $filename_zip = $ruta_zip.'.zip';

            if($zip->open($filename_zip,ZIPARCHIVE::CREATE)===true) {
                $zip->addFromString($nombre_archivo.".xml", file_get_contents($ruta));
                $zip->close();
            }

            unlink($ruta); // eliminar xml tmp

            $respuesta["hash_cpe"] = $hash_cpe;
            $respuesta["respuesta"] = "correcto";
        }else{
            $respuesta["msj_error"] = 99999;
            $respuesta["msj_error"] = "No se firmo el documento";
            $respuesta["respuesta"] = "error";
        }
        return $respuesta;
    }
}