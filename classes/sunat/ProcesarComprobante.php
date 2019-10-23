<?php
/**
 * Created by PhpStorm.
 * User: sc2
 * Date: 14/11/2018
 * Time: 06:28 PM
 */

class ProcesarComprobante
{

    public static function procesar_factura($data_comprobante, $objComprobantes, $rutas){

        $order_detail = OrderDetail::getList($objComprobantes->id);

        $resp = Apisunat_2_1::crear_xml_factura_boleta($data_comprobante, json_decode(json_encode($order_detail)), $rutas["ruta_xml"]);

        if ($resp['result'] == "error"){
            $objComprobantes->cod_sunat =  99999;
            $objComprobantes->msj_sunat =  $resp["msj_error"];
            $objComprobantes->update();
            return die(json_encode($resp));
        }

        $resp_firma = FirmarDocumento::firmar_xml($data_comprobante, $rutas["ruta_xml"], $rutas["ruta_firma"], $rutas["pass_firma"], $rutas["nombre_archivo"]);

        if ($resp_firma['result'] == "error"){
            $objComprobantes->cod_sunat =  99999;
            $objComprobantes->msj_sunat =  $resp_firma["msj_error"];
            $objComprobantes->update();
            return die(json_encode($resp_firma));
        }else{
            $objComprobantes->hash_cpe =  $resp_firma["hash_cpe"];
            $objComprobantes->ruta_xml =  $rutas["ruta_xml"].".zip";
            $objComprobantes->update();
        }

        $resp_envio = self::enviar_documento($data_comprobante['EMISOR_RUC'], $data_comprobante['EMISOR_USUARIO_SOL'], $data_comprobante['EMISOR_PASS_SOL'],  $rutas["ruta_xml"], $rutas["ruta_cdr"], $rutas['nombre_archivo'], $rutas['ruta_ws']);

        if ($resp_envio['result'] == "error"){

            $resp_envio["msg"][] = $resp_envio["msj_sunat"];

            $objComprobantes->cod_sunat =  $resp_envio["cod_sunat"];
            $objComprobantes->msj_sunat =  $resp_envio["msj_sunat"];
            $objComprobantes->update();
            return die(json_encode($resp_envio));
        }

        $resp["ruta_xml"] = $rutas["ruta_xml"].".zip";
        $resp["ruta_cdr"] = $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
        $resp["result"] = "ok";
        $resp["hash_cpe"] = $resp_firma["hash_cpe"];
        $resp["hash_cdr"] = $resp_envio['hash_cdr'];
        $resp["cod_sunat"] = $resp_envio['cod_sunat'];
        $resp["msj_sunat"] = $resp_envio['msj_sunat'];
        $resp["msg"][] = $resp_envio["msj_sunat"];
        $resp["estado_envio_sunat"][] = $resp_envio["estado_envio_sunat"];

        return $resp;

    }

    public static function procesar_boleta($data_comprobante, $objComprobantes, $rutas){

        $order_detail = OrderDetail::getList($objComprobantes->id_order);

        $resp = Apisunat_2_1::crear_xml_factura_boleta($data_comprobante, json_decode(json_encode($order_detail)), $rutas["ruta_xml"]);

        if ($resp['result'] == "error"){
            return die(json_encode($resp));
        }

        $resp_firma = FirmarDocumento::firmar_xml($data_comprobante, $rutas["ruta_xml"], $rutas["ruta_firma"], $rutas["pass_firma"], $rutas["nombre_archivo"]);

        if ($resp_firma['result'] == "error"){
            $objComprobantes->cod_sunat =  99999;
            $objComprobantes->msj_sunat =  $resp_firma["msj_error"];
            $objComprobantes->update();
            return die(json_encode($resp_firma));
        }else{
            $objComprobantes->hash_cpe =  $resp_firma["hash_cpe"];
            $objComprobantes->ruta_xml =  $rutas["ruta_xml"].".zip";
            $objComprobantes->update();
        }

        $resp_envio = self::enviar_documento($data_comprobante['EMISOR_RUC'], $data_comprobante['EMISOR_USUARIO_SOL'], $data_comprobante['EMISOR_PASS_SOL'],  $rutas["ruta_xml"], $rutas["ruta_cdr"], $rutas['nombre_archivo'], $rutas['ruta_ws']);

        if ($resp_envio['result'] == "error"){

//            $resp_envio["ruta_ticket"] = $objComprobantes->ruta_ticket;
//            $resp_envio["ruta_pdf_a4"] = $objComprobantes->ruta_pdf_a4;
            $resp_envio["msg"][] = $resp_envio["msj_sunat"];

            $objComprobantes->cod_sunat =  $resp_envio["cod_sunat"];
            $objComprobantes->msj_sunat = $resp_envio["msj_sunat"];
            $objComprobantes->update();
            return die(json_encode($resp_envio));
        }

//        $resp["ruta_ticket"] = $objComprobantes->ruta_ticket;
//        $resp["ruta_pdf_a4"] = $objComprobantes->ruta_pdf_a4;

        $resp["ruta_xml"] = $rutas["ruta_xml"].".zip";
        $resp["ruta_cdr"] = $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
        $resp["result"] = "ok";
        $resp["hash_cpe"] = $resp_firma["hash_cpe"];
        $resp["hash_cdr"] = $resp_envio['hash_cdr'];
        $resp["cod_sunat"] = $resp_envio['cod_sunat'];
        $resp["msj_sunat"] = $resp_envio['msj_sunat'];
        $resp["msg"][] = $resp_envio["msj_sunat"];
        $resp["estado_envio_sunat"][] = $resp_envio["estado_envio_sunat"];

        return $resp;

    }

    public static function procesar_nota_de_credito($data_comprobante, $objComprobantes, $rutas) {

        $order_detail = OrderDetail::getList($objComprobantes->id_order);

        $resp = Apisunat_2_1::crear_xml_nota_credito($data_comprobante, json_decode(json_encode($order_detail)), $rutas["ruta_xml"]);

        $resp_firma = FirmarDocumento::firmar_xml($data_comprobante, $rutas["ruta_xml"], $rutas["ruta_firma"], $rutas["pass_firma"], $rutas["nombre_archivo"]);

        if ($resp_firma['respuesta'] == "error"){
            return die(json_encode($resp_firma));
        }

        $resp_envio = self::enviar_documento($data_comprobante['EMISOR_RUC'], $data_comprobante['EMISOR_USUARIO_SOL'], $data_comprobante['EMISOR_PASS_SOL'],  $rutas["ruta_xml"], $rutas["ruta_cdr"], $rutas['nombre_archivo'], $rutas['ruta_ws']);

        if ($resp_envio['respuesta'] == 'error') {
            $resp_envio["msg"][] = $resp_envio["msj_sunat"];

            $objComprobantes->cod_sunat =  $resp_envio["cod_sunat"];
            $objComprobantes->msj_sunat =  $resp_envio["msj_sunat"];
            $objComprobantes->update();
            return $resp_envio;
        }

        $resp["ruta_xml"] = $rutas["ruta_xml"].".zip";
        $resp["ruta_cdr"] = $rutas["ruta_cdr"].'R-'. $rutas['nombre_archivo'].".zip";
        $resp["result"] = "ok";
        $resp["hash_cpe"] = $resp_firma["hash_cpe"];
        $resp["hash_cdr"] = $resp_envio['hash_cdr'];
        $resp["cod_sunat"] = $resp_envio['cod_sunat'];
        $resp["msj_sunat"] = $resp_envio['msj_sunat'];
        $resp["msg"][] = $resp_envio["msj_sunat"];
        $resp["estado_envio_sunat"][] = $resp_envio["estado_envio_sunat"];

        return $resp;
    }

    public static function enviar_documento($ruc, $usuario_sol, $pass_sol, $ruta_archivo, $ruta_archivo_cdr, $archivo, $ruta_ws) {
        //=================ZIPEAR ================
        $zip = new ZipArchive();
        $filenameXMLCPE = $ruta_archivo . '.zip';

//        if ($zip->open($filenameXMLCPE, ZIPARCHIVE::CREATE) === true) {
//            $zip->addFile($ruta_archivo . '.zip', $archivo . '.zip'); //ORIGEN, DESTINO
//            $zip->close();
//        }

        //===================ENVIO FACTURACION=====================
        $soapUrl = str_replace('?wsdl', '', $ruta_ws);

//        $soapUrl = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService";
        // xml post structure
        $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
        xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" 
        xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        <soapenv:Header>
            <wsse:Security>
                <wsse:UsernameToken>
                    <wsse:Username>' . $usuario_sol . '</wsse:Username>
                    <wsse:Password>' . $pass_sol . '</wsse:Password>
                </wsse:UsernameToken>
            </wsse:Security>
        </soapenv:Header>
        <soapenv:Body>
            <ser:sendBill>
                <fileName>' . $archivo . '.zip</fileName>
                <contentFile>' . base64_encode(file_get_contents($ruta_archivo . '.zip')) . '</contentFile>
            </ser:sendBill>
        </soapenv:Body>
        </soapenv:Envelope>';
//        d(base64_encode(file_get_contents($ruta_archivo . '.zip')));
        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: ",
            "Content-length: " . strlen($xml_post_string),
        );

        $url = $soapUrl;
//        D($xml_post_string);
        // PHP cURL  for https connection with auth
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        d($httpcode);
        if ($httpcode == 200) {
            $doc = new DOMDocument();
            $doc->loadXML($response);

            file_put_contents("doc_response.txt",  $archivo ." ". date('Y-m-d H:i:s'). " -> ".$response.PHP_EOL , FILE_APPEND | LOCK_EX);

            //===================VERIFICAMOS SI HA ENVIADO CORRECTAMENTE EL COMPROBANTE=====================
            if (isset($doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue)) {
                $xmlCDR = $doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue;
                file_put_contents($ruta_archivo_cdr . 'R-' . $archivo . '.zip', base64_decode($xmlCDR));

                //extraemos archivo zip a xml
                $zip = new ZipArchive;
                if ($zip->open($ruta_archivo_cdr . 'R-' . $archivo . '.zip') === TRUE) {
                    $zip->extractTo($ruta_archivo_cdr, 'R-' . $archivo . '.xml');
                    $zip->close();
                }

                //eliminamos los archivos Zipeados
//                unlink($ruta_archivo . '.ZIP');
//                unlink($ruta_archivo_cdr . 'R-' . $archivo . '.ZIP');

                //=============hash CDR=================
                $doc_cdr = new DOMDocument();
                $doc_cdr->load($ruta_archivo_cdr . 'R-' . $archivo . '.xml');
                $resp['result'] = 'OK';
                $resp['cod_sunat'] = $doc_cdr->getElementsByTagName('ResponseCode')->item(0)->nodeValue;
                $resp['msj_sunat'] = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
                $resp['hash_cdr'] = $doc_cdr->getElementsByTagName('DigestValue')->item(0)->nodeValue;
                $resp["estado_envio_sunat"] = 1;
                //eliminamos los archivos extraidos
                unlink($ruta_archivo . '.xml');
                unlink($ruta_archivo_cdr . 'R-' . $archivo . '.xml');
            } else {
                $resp["estado_envio_sunat"] = 0;
                $resp['result'] = 'error';
                $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $doc->getElementsByTagName('faultcode')->item(0)->nodeValue), 10);
                $resp['cod_sunat'] = $resul_code_sunat;
                $resp['msj_sunat'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
                $resp["msj_error"] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
                $resp['hash_cdr'] = "";
            }
        } else {
            $resp["estado_envio_sunat"] = 0;
            //echo "no responde web";
            $resp['result'] = 'error';
            $resp['cod_sunat'] = "99999";
            $resp['msj_sunat'] = "Web Service de SUNAT - Fuera de Servicio: <a href='".$soapUrl."?wsdl' target='_blank'>".$soapUrl."</a>, Para validar la información llamar al: *4000 (Desde Claro, Entel y Movistar) - SUNAT";
            $resp['msj_error'] = "Web Service de SUNAT - Fuera de Servicio: <a href='".$soapUrl."?wsdl' target='_blank'>".$soapUrl."</a>, Para validar la información llamar al: *4000 (Desde Claro, Entel y Movistar) - SUNAT";
            $resp['hash_cdr'] = "";
        }

//        d($resp);
        return $resp;
    }

    public static function procesar_baja_sunat($data_comprobante, $objComprobantes, $rutas) {

        $order_detail = OrderDetail::getList($objComprobantes->id_order);

        $resp = Apisunat_2_1::crear_xml_baja_sunat($data_comprobante, json_decode(json_encode($order_detail)), $rutas["ruta_xml"]);

        $resp_firma = FirmarDocumento::firmar_xml($data_comprobante, $rutas["ruta_xml"], $rutas["ruta_firma"], $rutas["pass_firma"], $rutas["nombre_archivo"]);

        if ($resp_firma['result'] == "error"){
            $objComprobantes->cod_sunat =  99999;
            $objComprobantes->msj_sunat =  $resp_firma["msj_error"];
            $objComprobantes->update();
            return $resp_firma;
        }

        $resp_envio = self::enviar_documento_para_baja($data_comprobante['EMISOR_USUARIO_SOL'], $data_comprobante['EMISOR_PASS_SOL'],  $rutas["ruta_xml"],  $rutas['nombre_archivo'], $rutas['ruta_ws']);

        $objComprobantes->cod_sunat_otro =  $resp_envio["cod_sunat"];
        $objComprobantes->mensaje_cdr =  $resp_envio["msj_sunat"];
        if ($resp_envio['result'] == 'error') {
            $objComprobantes->update();
            return $resp_envio;
        }
        $resp["msg"][] = $resp_envio["msj_sunat"];
        $objComprobantes->identificador_comunicacion =  $resp_envio["cod_ticket"];
        $objComprobantes->ruta_xml_otro = $rutas["ruta_xml"].".zip";
        $resp['result'] = 'OK';
        $objComprobantes->update();
        return $resp;

    }

    public static function enviar_documento_para_baja( $usuario_sol, $pass_sol, $ruta_archivo, $archivo, $ruta_ws) {
        try {
            //=================ZIPEAR ================
            $zip = new ZipArchive();
            $filenameXMLCPE = $ruta_archivo . '.ZIP';

//            if ($zip->open($filenameXMLCPE, ZIPARCHIVE::CREATE) === true) {
//                $zip->addFile($ruta_archivo . '.XML', $archivo . '.XML'); //ORIGEN, DESTINO
//                $zip->close();
//            }

            //===================ENVIO FACTURACION=====================
            $soapUrl = str_replace('?wsdl', '', $ruta_ws);

            $soapUser = "";
            $soapPassword = "";
            // xml post structure
            $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" 
            xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . $usuario_sol . '</wsse:Username>
                        <wsse:Password>' . $pass_sol . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:sendSummary>
                    <fileName>' . $archivo . '.zip</fileName>
                    <contentFile>' . base64_encode(file_get_contents($ruta_archivo . '.zip')) . '</contentFile>
                </ser:sendSummary>
            </soapenv:Body>
            </soapenv:Envelope>';

            $headers = array(
                "Content-type: text/xml;charset=\"utf-8\"",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "SOAPAction: ",
                "Content-length: " . strlen($xml_post_string),
            ); //SOAPAction: your op URL

            $url = $soapUrl;

            // PHP cURL  for https connection with auth
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword); // username and password - declared at the top of the doc
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // converting
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            //convertimos de base 64 a archivo fisico
            $doc = new DOMDocument();
            $doc->loadXML($response);
            file_put_contents("doc_response_baja.txt",  $archivo ." ". date('Y-m-d H:i:s'). " -> ".$response.PHP_EOL , FILE_APPEND | LOCK_EX);

            //===================VERIFICAMOS SI HA ENVIADO CORRECTAMENTE EL COMPROBANTE=====================
            if (isset($doc->getElementsByTagName('ticket')->item(0)->nodeValue)) {
                $ticket = $doc->getElementsByTagName('ticket')->item(0)->nodeValue;

//                unlink($ruta_archivo . '.ZIP');
                $mensaje['respuesta'] = 'OK';
                $mensaje['cod_ticket'] = $ticket;
                $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $doc->getElementsByTagName('faultcode')->item(0)->nodeValue), 10);
                $mensaje['msj_sunat'] = $resul_code_sunat . ' - ' . $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
            } else {
                $mensaje['respuesta'] = 'error';
                $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $doc->getElementsByTagName('faultcode')->item(0)->nodeValue), 10);
                $mensaje['cod_sunat'] = $resul_code_sunat;
                $mensaje['msj_sunat'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
            }
        } catch (Exception $e) {
            $mensaje['respuesta'] = 'error';
            $mensaje['cod_sunat'] = "99999";
            $mensaje['msj_sunat'] = "SUNAT ESTA FUERA SERVICIO: " . $e->getMessage();
        }
        return $mensaje;
    }

    public static function enviar_resumen_boletas($usuario_sol, $pass_sol, $ruta_archivo, $archivo, $ruta_ws) {
        //=================ZIPEAR ================
        $zip = new ZipArchive();
        $filenameXMLCPE = $ruta_archivo . '.zip';

//        if ($zip->open($filenameXMLCPE, ZIPARCHIVE::CREATE) === true) {
//            $zip->addFile($ruta_archivo . '.XML', $archivo . '.XML'); //ORIGEN, DESTINO
//            $zip->close();
//        }

        //===================ENVIO FACTURACION=====================
        $soapUrl = str_replace('?wsdl', '', $ruta_ws);
        $soapUser = "";
        $soapPassword = "";
        // xml post structure
        $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
        xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" 
        xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        <soapenv:Header>
            <wsse:Security>
                <wsse:UsernameToken>
                    <wsse:Username>' . $usuario_sol . '</wsse:Username>
                    <wsse:Password>' . $pass_sol . '</wsse:Password>
                </wsse:UsernameToken>
            </wsse:Security>
        </soapenv:Header>
        <soapenv:Body>
            <ser:sendSummary>
                <fileName>' . $archivo . '.zip</fileName>
                <contentFile>' . base64_encode(file_get_contents($ruta_archivo . '.zip')) . '</contentFile>
            </ser:sendSummary>
        </soapenv:Body>
        </soapenv:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: ",
            "Content-length: " . strlen($xml_post_string),
        ); //SOAPAction: your op URL

        $url = $soapUrl;

        // PHP cURL  for https connection with auth
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword); // username and password - declared at the top of the doc
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {//======LA PAGINA SI RESPONDE
            //convertimos de base 64 a archivo fisico
            $doc = new DOMDocument();
            $doc->loadXML($response);
            file_put_contents("doc_response_resumen.txt",  $archivo ." ". date('Y-m-d H:i:s'). " -> ".$response.PHP_EOL , FILE_APPEND | LOCK_EX);

            //===================VERIFICAMOS SI HA ENVIADO CORRECTAMENTE EL COMPROBANTE=====================
            if (isset($doc->getElementsByTagName('ticket')->item(0)->nodeValue)) {
                $ticket = $doc->getElementsByTagName('ticket')->item(0)->nodeValue;

//                unlink($ruta_archivo . '.zip');
                $mensaje['respuesta'] = 'ok';
                $mensaje['cod_ticket'] = $ticket;
                $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $doc->getElementsByTagName('faultcode')->item(0)->nodeValue), 10);
                $mensaje['cod_sunat'] = $resul_code_sunat;
                $mensaje['msj_sunat'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
            } else {

                $mensaje['respuesta'] = 'error';
                $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $doc->getElementsByTagName('faultcode')->item(0)->nodeValue), 10);
                $mensaje['cod_sunat'] = $resul_code_sunat;
                $mensaje['msj_sunat'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
                $mensaje['hash_cdr'] = "";
            }
        } else {
            //echo "no responde web";
            $mensaje['respuesta'] = 'error';
            $mensaje['cod_sunat'] = "0000";
            $mensaje['msj_sunat'] = "SUNAT ESTA FUERA SERVICIO";
            $mensaje['hash_cdr'] = "";
        }
        return $mensaje;
    }

    public static function consultar_envio_ticket($usuario_sol, $pass_sol, $ticket, $archivo, $ruta_archivo_cdr, $ruta_ws) {

        $soapUrl = str_replace('?wsdl', '', $ruta_ws);

        $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        <soapenv:Header>
        <wsse:Security>
        <wsse:UsernameToken>
        <wsse:Username>' . $usuario_sol . '</wsse:Username>
        <wsse:Password>' . $pass_sol . '</wsse:Password>
        </wsse:UsernameToken>
        </wsse:Security>
        </soapenv:Header>
        <soapenv:Body>
        <ser:getStatus>
        <ticket>' . $ticket . '</ticket>
        </ser:getStatus>
        </soapenv:Body>
        </soapenv:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: ",
            "Content-length: " . strlen($xml_post_string),
        ); //SOAPAction: your op URL
        // PHP cURL  for https connection with auth
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword); // username and password - declared at the top of the doc
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpcode == 200) {//======LA PAGINA SI RESPONDE
            //echo $httpcode.'----'.$response;
            //convertimos de base 64 a archivo fisico
            $doc = new DOMDocument();
            $doc->loadXML($response);
//            d($response);
            file_put_contents("doc_response_ticket-consulta.txt",  $archivo ." ". date('Y-m-d H:i:s'). " -> ".$response.PHP_EOL , FILE_APPEND | LOCK_EX);
            //0 = Procesó correctamente
            //98 = En proceso
            //99 = Proceso con errores
            //===================VERIFICAMOS SI HA ENVIADO CORRECTAMENTE EL COMPROBANTE=====================
            if (isset($doc->getElementsByTagName('content')->item(0)->nodeValue)) {
                if (isset($doc->getElementsByTagName('statusCode')->item(0)->nodeValue) && (int)$doc->getElementsByTagName('statusCode')->item(0)->nodeValue != 127){
                    $xmlCDR = $doc->getElementsByTagName('content')->item(0)->nodeValue;
                    file_put_contents($ruta_archivo_cdr . 'R-' . $archivo . '.zip', base64_decode($xmlCDR));

                    //extraemos archivo zip a xml
                    $zip = new ZipArchive;
                    if ($zip->open($ruta_archivo_cdr . 'R-' . $archivo . '.zip') === TRUE) {
                        $zip->extractTo($ruta_archivo_cdr, 'R-' . $archivo . '.xml');
                        $zip->close();
                    }

                    //eliminamos los archivos Zipeados
                    //unlink($ruta_archivo . '.ZIP');
//                unlink($ruta_archivo_cdr . 'R-' . $archivo . '.zip');

                    //=============hash CDR=================
                    $doc_cdr = new DOMDocument();
                    $doc_cdr->load($ruta_archivo_cdr . 'R-' . $archivo . '.xml');
                    $mensaje['respuesta'] = 'ok';
                    $mensaje['cod_sunat'] = $doc_cdr->getElementsByTagName('ResponseCode')->item(0)->nodeValue;
                    $mensaje['msj_sunat'] = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
                    $mensaje['mensaje'] = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
                    $mensaje['ruta_cdr'] = $ruta_archivo_cdr . 'R-' . $archivo . '.zip';
                    $mensaje['hash_cdr'] = $doc_cdr->getElementsByTagName('DigestValue')->item(0)->nodeValue;

                    //eliminamos los archivos extraidos
                    unlink($archivo . '.xml');
                    unlink($ruta_archivo_cdr . 'R-' . $archivo . '.xml');
                }else{
                    $mensaje['respuesta'] = 'error';
                    $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $doc->getElementsByTagName('faultcode')->item(0)->nodeValue), 10);
                    $mensaje['cod_sunat'] = $resul_code_sunat;
                    $mensaje['mensaje'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
                    $mensaje['msj_sunat'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
                }
            } else {
                $mensaje['respuesta'] = 'error';
                $mensaje['cod_sunat'] = (int)$doc->getElementsByTagName('statusCode')->item(0)->nodeValue;
                $mensaje['mensaje'] = $doc->getElementsByTagName('content')->item(0)->nodeValue;
                $mensaje['msj_sunat'] = $doc->getElementsByTagName('content')->item(0)->nodeValue;
//                $mensaje['hash_cdr'] = "";
            }
        } else {
            //echo "no responde web";
            $mensaje['respuesta'] = 'error';
            $mensaje['cod_sunat'] = "9999";
            $mensaje['mensaje'] = "SUNAT ESTA FUERA SERVICIO: ";
            $mensaje['hash_cdr'] = "";
        }
        return $mensaje;
    }

    public static function consultar_envio_ticket_resumen($usuario_sol, $pass_sol, $ticket, $archivo, $ruta_archivo_cdr, $ruta_ws) {

        $soapUrl = str_replace('?wsdl', '', $ruta_ws);

        $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        <soapenv:Header>
        <wsse:Security>
        <wsse:UsernameToken>
        <wsse:Username>' . $usuario_sol . '</wsse:Username>
        <wsse:Password>' . $pass_sol . '</wsse:Password>
        </wsse:UsernameToken>
        </wsse:Security>
        </soapenv:Header>
        <soapenv:Body>
        <ser:getStatus>
        <ticket>' . $ticket . '</ticket>
        </ser:getStatus>
        </soapenv:Body>
        </soapenv:Envelope>';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: ",
            "Content-length: " . strlen($xml_post_string),
        ); //SOAPAction: your op URL
        // PHP cURL  for https connection with auth
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword); // username and password - declared at the top of the doc
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpcode == 200) {//======LA PAGINA SI RESPONDE
            //echo $httpcode.'----'.$response;
            //convertimos de base 64 a archivo fisico
            $doc = new DOMDocument();
            $doc->loadXML($response);

            file_put_contents("doc_response_ticket-consulta.txt",  $archivo ." ". date('Y-m-d H:i:s'). " -> ".$response.PHP_EOL , FILE_APPEND | LOCK_EX);

            //===================VERIFICAMOS SI HA ENVIADO CORRECTAMENTE EL COMPROBANTE=====================
            if (isset($doc->getElementsByTagName('content')->item(0)->nodeValue)) {
                if (isset($doc->getElementsByTagName('statusCode')->item(0)->nodeValue) && (int)$doc->getElementsByTagName('statusCode')->item(0)->nodeValue != 127){
                    $xmlCDR = $doc->getElementsByTagName('content')->item(0)->nodeValue;
                    file_put_contents($ruta_archivo_cdr . 'R-' . $archivo . '.zip', base64_decode($xmlCDR));

                    //extraemos archivo zip a xml
                    $zip = new ZipArchive;
                    if ($zip->open($ruta_archivo_cdr . 'R-' . $archivo . '.zip') === TRUE) {
                        $zip->extractTo($ruta_archivo_cdr, 'R-' . $archivo . '.xml');
                        $zip->close();
                    }

                    //eliminamos los archivos Zipeados
                    //unlink($ruta_archivo . '.ZIP');
//                unlink($ruta_archivo_cdr . 'R-' . $archivo . '.zip');

                    //=============hash CDR=================
                    $doc_cdr = new DOMDocument();
                    $doc_cdr->load($ruta_archivo_cdr . 'R-' . $archivo . '.xml');
                    $mensaje['respuesta'] = 'OK';
                    $mensaje['cod_sunat'] = $doc_cdr->getElementsByTagName('ResponseCode')->item(0)->nodeValue;
                    $mensaje['msj_sunat'] = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
                    $mensaje['mensaje'] = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
                    $mensaje['ruta_cdr'] = $ruta_archivo_cdr . 'R-' . $archivo . '.zip';
                    $mensaje['hash_cdr'] = $doc_cdr->getElementsByTagName('DigestValue')->item(0)->nodeValue;

                    //eliminamos los archivos extraidos
                    unlink($archivo . '.xml');
                    unlink($ruta_archivo_cdr . 'R-' . $archivo . '.xml');
                }else{
                    $mensaje['respuesta'] = 'error';
                    $mensaje['cod_sunat'] = (int)$doc->getElementsByTagName('statusCode')->item(0)->nodeValue;
                    $mensaje['mensaje'] = $doc->getElementsByTagName('content')->item(0)->nodeValue;
                    $mensaje['msj_sunat'] = $doc->getElementsByTagName('content')->item(0)->nodeValue;
                }
            } else {
                $mensaje['respuesta'] = 'error';
                $resul_code_sunat = intval(preg_replace('/[^0-9]+/', '', $doc->getElementsByTagName('faultcode')->item(0)->nodeValue), 10);
                $mensaje['cod_sunat'] = $resul_code_sunat;
                $mensaje['mensaje'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
                $mensaje['msj_sunat'] = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
//                $mensaje['hash_cdr'] = "";
            }
        } else {
            //echo "no responde web";
            $mensaje['respuesta'] = 'error';
            $mensaje['cod_sunat'] = "9999";
            $mensaje['mensaje'] = "SUNAT ESTA FUERA SERVICIO: ";
            $mensaje['hash_cdr'] = "";
        }
        return $mensaje;
    }
}