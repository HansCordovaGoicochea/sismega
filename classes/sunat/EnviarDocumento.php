<?php
/**
 * Created by PhpStorm.
 * User: sc2
 * Date: 14/11/2018
 * Time: 06:51 PM
 */

class EnviarDocumento
{
    public static function enviar_comprobante($ruc, $user, $pass, $rutas){

        $respuesta = array();
        try{
            $headers = new CustomHeaders($user, $pass); // enviar el header de seguridad

            $client = new SoapClient($rutas['ruta_ws'], [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => TRUE ,
                'soap_version' => SOAP_1_1,
                'connection_timeout'=> 30 ] );
//            d($client);
            $client->__setSoapHeaders([$headers]);
//            $fcs = $client->__getFunctions(); // mostrar las funciones que tiene el web service
//            d($fcs);
            $params = array( 'fileName' => $rutas['nombre_archivo'].".zip", 'contentFile' => file_get_contents($rutas["ruta_xml"].".zip"));
            $status = $client->sendBill($params);
            $responseHeaders = $client->__getLastResponseHeaders();
            preg_match("/HTTP\/\d\.\d\s*\K[\d]+/", $responseHeaders,$matches);
//            d($matches[0]);
            if ($matches[0] == "200"){
                if ($status->applicationResponse){
                    // recibir la respuesta que te da SUNAT
                    $ifp = fopen( $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip", "wb" );
                    fwrite( $ifp, $status->applicationResponse );
                    fclose( $ifp );

                    //leer el ZIP de respuesta aun no esta
                    $zip = zip_open($rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip");
                    if($zip)
                    {
                        //la función zip_read sirve para leer el contenido de nuestro archivo ZIP
                        while ($zip_entry = zip_read($zip))
                        {
                            // la función zip_entry_name devuelve el nombre de cada uno de nuestros archivos.
                            if(zip_entry_open($zip, $zip_entry) && 'R-'.$rutas['nombre_archivo'].'.xml' == zip_entry_name($zip_entry))
                            {
                                //la función zip_entry_read lee el contenido del fichero
                                $contenido = zip_entry_read($zip_entry,8086);
                                $response = str_replace(['cbc:', 'ext:', 'cac:', 'ds:', 'sac:', 'ar:'], ['', '', '', '', ''], $contenido);
                                $res = simplexml_load_string($response);

                                $respuesta["cod_sunat"] = $res->DocumentResponse->Response->ResponseCode;
                                $respuesta["msj_sunat"] = $res->DocumentResponse->Response->Description;
                                $respuesta["hash_cdr"] = $res->UBLExtensions->UBLExtension->ExtensionContent->Signature->SignedInfo->Reference->DigestValue;

//                                $order->msj_sunat = $res->DocumentResponse->Response->Description;
//                                $this->confirmations[] = $this->trans($res->DocumentResponse->Response->Description, array(), 'Admin.Global');
                            }
                            zip_entry_close('R-' . $rutas['nombre_archivo'] . '.zip');
                        }
                    }
                    zip_close($zip);
                }
                else{
                    $respuesta['respuesta'] = "error";
                    $respuesta['cod_sunat'] = 9999;
                    $respuesta["msj_error"] = "No retorno CDR";
                    $respuesta["msj_sunat"] = "No retorno CDR";
                }
            }else{
                $respuesta['respuesta'] = "error";
                $respuesta["msj_error"] = "La webservice de SUNAT no esta respondiendo correctamente";
            }

        }catch (SoapFault $fault){
            $respuesta = [];
            $msg_error = str_replace("'", "", $fault->getMessage());
            $msg_error2 = str_replace("?", "", $msg_error);
            $msg_error3 = str_replace("/", "", $msg_error2);

//            d($msg_error2);
            $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $fault->faultcode), 10);

            $mensaje_error =  "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";
            $respuesta['respuesta'] = "error";
            $respuesta["msj_error"] = $mensaje_error;
            $respuesta["cod_sunat"] = $resul_code_sunat == 0?9999:$resul_code_sunat;
            $respuesta["msj_sunat"] = $msg_error3;
        }



        return $respuesta;

    }
}