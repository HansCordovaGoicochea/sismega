<?php
/**
 * Created by PhpStorm.
 * User: sc2
 * Date: 13/11/2018
 * Time: 08:04 PM
 */

class Apisunat_2_1
{

    public  static function crear_cabecera($emisor, $order, $objComprobantes, $tipo_comprobante, $receptor){

        if ($order->id_currency == 1){
            $currency = 'PEN';
            $moneda = 'SOLES';
        }
        else{
            $currency = 'USD';
            $moneda = 'DOLARES';
        }

        $notadebito_descripcion['01'] = 'INTERES POR MORA';
        $notadebito_descripcion['02'] = 'AUMENTO EN EL VALOR';
        $notadebito_descripcion['03'] = 'PENALIDADES';

        $notacredito_descripcion['01'] = 'ANULACION DE LA OPERACION';
        $notacredito_descripcion['02'] = 'ANULACION POR ERROR EN EL RUC';
        $notacredito_descripcion['03'] = 'CORRECCION POR ERROR EN LA DESCRIPCION';
        $notacredito_descripcion['04'] = 'DESCUENTO GLOBAL';
        $notacredito_descripcion['05'] = 'DESCUENTO POR ITEM';
        $notacredito_descripcion['06'] = 'DEVOLUCION TOTAL';
        $notacredito_descripcion['07'] = 'DEVOLUCION POR ITEM';
        $notacredito_descripcion['08'] = 'BONIFICACION';
        $notacredito_descripcion['09'] = 'DISMINUCION EN EL VALOR';

        $numero_comprobante = $objComprobantes->numero_comprobante;

        if (isset($tipo_comprobante)){
            if ($tipo_comprobante == "07"){ //NOTA DE CREDITO
                $codigo_motivo_modifica = '0'.(int)$objComprobantes->code_motivo_nota_credito;
                $descripcion_motivo_modifica = $notacredito_descripcion[$codigo_motivo_modifica];
//                $numero_comprobante = $objComprobantes->numeracion_nota_baja;
            }else if($tipo_comprobante == "08"){ //NOTA DE DEBITO
                $codigo_modifica = $objComprobantes->notadebito_motivo_id;
                $descripcion_motivo_modifica =$notadebito_descripcion[$objComprobantes->notadebito_motivo_id];
            }else if($tipo_comprobante == "Baja"){ //NOTA DE DEBITO
                $codigo_motivo_modifica = "";
                $descripcion_motivo_modifica = "";
                $numero_comprobante = $objComprobantes->numeracion_nota_baja;
                $serie_comprobante = $objComprobantes->numero_comprobante;
                $serie = explode("-", $serie_comprobante);
                $objComprobantes->serie = $serie[0];
                $objComprobantes->numero = $serie[1];
            }else{
                $codigo_motivo_modifica = "";
                $descripcion_motivo_modifica = "";
            }
        }


//        http://cpe.sunat.gob.pe/sites/default/files/inline-images/Guia%2BXML%2BFactura%2Bversion%202-1%2B1%2B0%20%282%29.pdf
        $cabecera = array(
            "TIPO_OPERACION" => "0101", //Pag. 28
            "TOTAL_GRAVADA" => Tools::truncate_number(OrderDetail::getTotalPorCodigo($order->id, '10'), 2),
            "TOTAL_INAFECTA" => Tools::truncate_number(OrderDetail::getTotalPorCodigo($order->id, '30'), 2),
            "TOTAL_EXONERADAS" => "0",
            "TOTAL_GRATUITAS" => "0",
            "TOTAL_PERCEPCIONES" => "0",
            "TOTAL_RETENCIONES" => "0",
            "TOTAL_DETRACCIONES" => "0",
            "TOTAL_BONIFICACIONES" => "0",
            "TOTAL_EXPORTACION" => "0",
            "TOTAL_DESCUENTO" => $order->total_discounts_tax_excl,
            "SUB_TOTAL" => $order->total_paid_tax_excl,
            "PORCENTAJE_IGV" => "18.00",
            "TOTAL_IGV" => (float)($order->total_paid_tax_incl - $order->total_paid_tax_excl),
            "TOTAL_ISC" => "0",
            "TOTAL_OTRO_IMP" => "0",
            "TOTAL" => $order->total_paid_tax_incl,
            "TOTAL_LETRAS" => Tools::displaynumeroaletras($order->total_paid_tax_incl, $moneda),
            //======================= GUIA REMISION =========
            "NRO_GUIA_REMISION" => "",
            "COD_GUIA_REMISION" => "",
            "NRO_OTR_COMPROBANTE" => "",
            "COD_OTR_COMPROBANTE" => "",
            //=================== NOTA DE CREDITO O DEBITO ===========
            'TIPO_COMPROBANTE_MODIFICA' => (isset($objComprobantes->tipo_comprobante_modificado)) ? $objComprobantes->tipo_comprobante_modificado : "",
            'NRO_DOCUMENTO_MODIFICA' => (isset($objComprobantes->num_comprobante_modificado)) ? $objComprobantes->num_comprobante_modificado : "",
            'COD_TIPO_MOTIVO' => $codigo_motivo_modifica,
            'DESCRIPCION_MOTIVO' => $descripcion_motivo_modifica,
            //=================== BAJA ===========
            'FECHA_REFERENCIA' => $objComprobantes->fecha_envio_comprobante,
            'FECHA_BAJA' => date('Y-m-d'),
            'SERIE' => $objComprobantes->serie,
            'NUMERO' => $objComprobantes->numero,
            'MOTIVO' => $objComprobantes->motivo_baja,
            //==========================================
            "NRO_COMPROBANTE" => $numero_comprobante,
            "FECHA_DOCUMENTO" => $objComprobantes->fecha_envio_comprobante,
            "FECHA_VTO" => $objComprobantes->fecha_envio_comprobante, //PAG. 31
            "COD_TIPO_DOCUMENTO" => $tipo_comprobante,
            "COD_MONEDA" => $currency,
            //==========================================
            "TIPO_DOCUMENTO_CLIENTE" => $receptor['TIPO_DOCUMENTO_CLIENTE'], //RUC 6 O DNI 1, CExt. 4
            "NRO_DOCUMENTO_CLIENTE" => $receptor['NRO_DOCUMENTO_CLIENTE'],
            "RAZON_SOCIAL_CLIENTE" => $receptor['RAZON_SOCIAL_CLIENTE'],
            "DIRECCION_CLIENTE" => $receptor['DIRECCION_CLIENTE'],
//            "CODIGO_UBIGEO_CLIENTE" =>
//            "DEPARTAMENTO_CLIENTE" =>
//            "PROVINCIA_CLIENTE" =>
//            "DISTRITO_CLIENTE" =>
//            "CIUDAD_CLIENTE" =>
//            "COD_PAIS_CLIENTE" =>
//
//            // ========================================
            "NUM_DOCUMENTO_EMPRESA" => $emisor['ruc'],
            "TIPO_DOCUMENTO_EMPRESA" =>  $emisor['tipo_doc'],
            "NOMBRE_COMERCIAL_EMPRESA" => $emisor['nom_comercial'],
            "CODIGO_UBIGEO_EMPRESA" => $emisor['codigo_ubigeo'],
            "DIRECCION_EMPRESA" => $emisor['direccion'],
            "DEPARTAMENTO_EMPRESA" => $emisor['direccion_departamento'],
            "PROVINCIA_EMPRESA" => $emisor['direccion_provincia'],
            "DISTRITO_EMPRESA" => $emisor['direccion_distrito'],
            "CODIGO_PAIS_EMPRESA" => $emisor['direccion_codigo_pais'],
            "RAZON_SOCIAL_EMPRESA" => $emisor['razon_social'],
//        //===================== informacion anticipo ======================
//            "FLG_ANTICIPO" =>
//        //==================REGULAR ANTICIPO ================
//            "FLG_REGULA_ANTICIPO" =>
//            "NRO_COMPROBANTE_REF_ANT" =>
//            "MONEDA_REG_ANTICIPO" =>
//            "NRO_DOCUMENTO_EMP_REG_ANT" =>
//        //============ CLAVES SOL ========================
            "EMISOR_RUC" => $emisor['ruc'],
            "EMISOR_USUARIO_SOL" => $emisor['usuario_sol'],
            "EMISOR_PASS_SOL" => $emisor['clave_sol'],
            "ES_PORCONSUMO" => $emisor['es_porconsumo'],
        );

//    d($cabecera);
        return $cabecera;

    }

    public static function crear_xml_factura_boleta($cabecera, $detalle, $ruta)
    {
        $ruta = $ruta.".xml";
        $doc = new DOMDocument();
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = true;
        $doc->encoding = 'utf-8';
//        <cbc:IssueTime>'.date_format(date_create($cabecera['FECHA_DOCUMENTO']),"H:i:s").'</cbc:IssueTime> <!-- Hora de emisión hh-mm-ss-->
        $xmlCPE = '<?xml version="1.0" encoding="UTF-8"?>
            <Invoice xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ccts="urn:un:unece:uncefact:documentation:2" xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
               <ext:UBLExtensions>
                  <ext:UBLExtension>
                     <ext:ExtensionContent /> <!-- firma del certificado -->
                  </ext:UBLExtension>
               </ext:UBLExtensions>
               <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
               <cbc:CustomizationID>2.0</cbc:CustomizationID>
               <cbc:ID>'.$cabecera['NRO_COMPROBANTE'].'</cbc:ID> <!-- F###-NNNNNNNN -->
               <cbc:IssueDate>'.Tools::getFormatFechaGuardar($cabecera['FECHA_DOCUMENTO']).'</cbc:IssueDate> <!-- Fecha de emisión yyyy-mm-dd -->
               <cbc:IssueTime>00:00:00</cbc:IssueTime> <!-- Hora de emisión hh-mm-ss-->
               <cbc:DueDate>'.Tools::getFormatFechaGuardar($cabecera['FECHA_DOCUMENTO']).'</cbc:DueDate> <!-- Fecha de vencimiento yyyy-mm-dd -->
               <cbc:InvoiceTypeCode 
               listID="'.$cabecera['TIPO_OPERACION'].'" 
               listAgencyName="PE:SUNAT" 
               listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">'.$cabecera['COD_TIPO_DOCUMENTO'].'</cbc:InvoiceTypeCode> <!-- Código de tipo de documento 01 si es Factura // 03 si es boleta an2 Cat. 01 -->
               <cbc:Note languageLocaleID="1000"><![CDATA['.$cabecera['TOTAL_LETRAS'].']]></cbc:Note> <!-- Leyenda : monto en letras an..100 Cat. 52 -->
               <cbc:DocumentCurrencyCode 
               listID="ISO 4217 Alpha" 
               listName="Currency"
               listAgencyName="United Nations Economic Commission for Europe">PEN</cbc:DocumentCurrencyCode> <!-- Cód. tipo moneda en la cual se emite la F.E. an3 Cat. 02 -->
               <cac:Signature>
                  <cbc:ID>SCSign</cbc:ID>
                  <cac:SignatoryParty>
                     <cac:PartyIdentification>
                        <cbc:ID>'.$cabecera['NUM_DOCUMENTO_EMPRESA'].'</cbc:ID> <!-- RUC DEL EMISOR -->
                     </cac:PartyIdentification>
                     <cac:PartyName>
                        <cbc:Name><![CDATA['.Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_EMPRESA']).']]></cbc:Name> <!-- nombre o razón social del emisor an..100 -->
                     </cac:PartyName>
                  </cac:SignatoryParty>
                  <cac:DigitalSignatureAttachment>
                     <cac:ExternalReference>
                        <cbc:URI>#SCSign</cbc:URI> <!--  -->
                     </cac:ExternalReference>
                  </cac:DigitalSignatureAttachment>
               </cac:Signature>
               <!--En esta sección se ingresaran todos los datos del emisor-->
               <cac:AccountingSupplierParty>
                  <cac:Party>
                     <cac:PartyIdentification>
                        <cbc:ID schemeID="'.$cabecera['TIPO_DOCUMENTO_EMPRESA'].'" 
                        schemeAgencyName="PE:SUNAT" 
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$cabecera['NUM_DOCUMENTO_EMPRESA'].'</cbc:ID> <!-- RUC DEL EMISOR -->
                     </cac:PartyIdentification>
                     <cac:PartyName>
                        <cbc:Name><![CDATA['.Tools::eliminar_tildes($cabecera['NOMBRE_COMERCIAL_EMPRESA']).']]></cbc:Name> <!-- Nombre Comercial DEL EMISOR -->
                     </cac:PartyName>
                     <cac:PartyLegalEntity>
                        <cbc:RegistrationName><![CDATA['.Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_EMPRESA']).']]></cbc:RegistrationName> <!-- Apellidos y nombres, denominación o razón social -->
                        <cac:RegistrationAddress>
                           <cbc:AddressTypeCode>0000</cbc:AddressTypeCode> <!-- Código de cuatro dígitos asignado por SUNAT, que identifica al establecimiento anexo. Dicho código se genera al momento la respectiva comunicación del establecimiento. Tratándose del domicilio fiscal y en el caso de no poder determinar el lugar de la venta, informar “0000” -->
                        </cac:RegistrationAddress>
                     </cac:PartyLegalEntity>
                  </cac:Party>
               </cac:AccountingSupplierParty>
              <!--En esta sección se ingresaran todos los datos del receptor-->
               <cac:AccountingCustomerParty>
                  <cac:Party>
                     <cac:PartyIdentification>
                        <cbc:ID schemeID="'.$cabecera['TIPO_DOCUMENTO_CLIENTE'].'" 
                        schemeAgencyName="PE:SUNAT"
                         schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$cabecera['NRO_DOCUMENTO_CLIENTE'].'</cbc:ID> <!-- Numero de documento schemeID tipo de documento de identidad -->
                     </cac:PartyIdentification>
                     <cac:PartyLegalEntity>
                        <cbc:RegistrationName><![CDATA['.Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_CLIENTE']).']]></cbc:RegistrationName> <!-- Nombre o denominación o razón social del cliente -->
                     </cac:PartyLegalEntity>
                  </cac:Party>
               </cac:AccountingCustomerParty>';

                if ($cabecera["TOTAL_DESCUENTO"] > 0) {
                    $xmlCPE .=
                        ' <!-- Información de descuentos Globales -->
                   <cac:AllowanceCharge>
                        <cbc:ChargeIndicator>false</cbc:ChargeIndicator>  <!-- Indicador del cargo / descuento global  "true"/"false" -->
                        <cbc:AllowanceChargeReasonCode listName="Cargo/descuento" listAgencyName="PE:SUNAT" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo53">00</cbc:AllowanceChargeReasonCode>  <!-- Código del motivo del cargo/descuento global - Se debe considerar el código 00 de acuerdo al catálogo N° 53.--> 
                        <cbc:MultiplierFactorNumeric>'.round($cabecera["TOTAL_DESCUENTO"] / $cabecera["TOTAL_GRAVADA"], 5).'</cbc:MultiplierFactorNumeric> <!-- el porcentaje que corresponde del descuento global
    aplicado. Se expresa en numeros decimales por ejemplo 5% será 0.05. -->
                        <cbc:Amount currencyID="PEN">'.round($cabecera["TOTAL_DESCUENTO"], 2).'</cbc:Amount> <!-- Monto del cargo/descuento global  -->
                        <cbc:BaseAmount currencyID="PEN">'.round( $cabecera["TOTAL_GRAVADA"], 2).'</cbc:BaseAmount> <!-- Monto de base de cargo/descuento global  -->
                    </cac:AllowanceCharge>
                    <!-- FIN Información de descuentos Globales -->';
                }

               $xmlCPE .= '<cac:TaxTotal>
                  <!--Sumatoria del Total IGV + Total ISC + Total Otros tributos-->
                  <cbc:TaxAmount currencyID="PEN">'.round($cabecera['TOTAL_IGV'],2).'</cbc:TaxAmount> <!-- Monto total de impuestos -->
                  <!--Total de IGV del comprobante | tambien va el (total de operaciones gravadas + ISC) / 1.%Descuento global-->
                  <cac:TaxSubtotal>
                     <cbc:TaxableAmount currencyID="PEN">'.round($cabecera['TOTAL_GRAVADA']  - $cabecera["TOTAL_DESCUENTO"],2).'</cbc:TaxableAmount> <!-- Monto las operaciones gravadas -->
                     <cbc:TaxAmount currencyID="PEN">'.round($cabecera['TOTAL_IGV'],2).'</cbc:TaxAmount> <!-- Monto total de impuestos -->
                     <cac:TaxCategory>
                        <cac:TaxScheme>
                           <cbc:ID>1000</cbc:ID> <!-- codigo de tributo Cat. 5 -->
                           <cbc:Name>IGV</cbc:Name> <!-- nombre del tributo Cat. 5 -->
                           <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode> <!-- codigo inter. del tributo Cat. 5 -->
                        </cac:TaxScheme>
                     </cac:TaxCategory>
                  </cac:TaxSubtotal>
               </cac:TaxTotal>
               <cac:LegalMonetaryTotal>
                  <cbc:LineExtensionAmount currencyID="PEN">'.round($cabecera['SUB_TOTAL'],2).'</cbc:LineExtensionAmount> <!-- Total Valor de Venta sin impuesto -->
                  <cbc:TaxInclusiveAmount currencyID="PEN">'.round($cabecera['TOTAL'],2).'</cbc:TaxInclusiveAmount> 
                  <cbc:AllowanceTotalAmount currencyID="PEN">'.round($cabecera["TOTAL_DESCUENTO"], 2).'</cbc:AllowanceTotalAmount>
                  <cbc:ChargeTotalAmount currencyID="PEN">0.00</cbc:ChargeTotalAmount>
                  <cbc:PayableAmount currencyID="PEN">'.round($cabecera['TOTAL'], 2).'</cbc:PayableAmount> <!-- Total Valor de Venta con impuesto -->
               </cac:LegalMonetaryTotal>
               ';
//                <!--Esta seccion permite visualizar el detalle del comprobante-->


            $count = 0;
            $respuesta["respuesta"] = "OK";
            foreach ($detalle as $key=>$item) {
                    $count++;
                    $tax_group_rule = TaxRule::getTaxRulesByGroupId(Context::getContext()->language->id, $item->id_tax_rules_group);

                $xmlCPE .= '<cac:InvoiceLine>
                  <cbc:ID>'.$count.'</cbc:ID>  <!-- Número de orden del Ítem -->
                  <cbc:InvoicedQuantity unitCode="NIU">'.(float)$item->product_quantity.'</cbc:InvoicedQuantity> <!-- unitCode: Unidad de medida por ítem Cat. 3 -->
                  <cbc:LineExtensionAmount currencyID="PEN">'.round(Tools::truncate_number($item->total_price_tax_excl, 2),2).'</cbc:LineExtensionAmount> <!-- valor cantidad * valor unitario sin impuesto -->
                  <cac:PricingReference>
                     <cac:AlternativeConditionPrice>
                        <cbc:PriceAmount currencyID="PEN">'.round($item->unit_price_tax_incl,2).'</cbc:PriceAmount> <!-- Precio de venta unitario con impuesto por item y código -->
                        <cbc:PriceTypeCode>01</cbc:PriceTypeCode> <!-- Codigo de precio Cat 16 -->
                     </cac:AlternativeConditionPrice>
                  </cac:PricingReference>
                  <cac:TaxTotal>
                     <cbc:TaxAmount currencyID="PEN">'.round(($item->total_price_tax_incl - $item->total_price_tax_excl),2).'</cbc:TaxAmount> <!-- impuesto por linea de item -->
                     <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="PEN">'.round(Tools::truncate_number($item->total_price_tax_excl, 2),2).'</cbc:TaxableAmount> <!-- valor cantidad * valor unitario -->
                        <cbc:TaxAmount currencyID="PEN">'.round(($item->total_price_tax_incl - $item->total_price_tax_excl),2).'</cbc:TaxAmount> <!--  -->';
                if (!empty($tax_group_rule) && (int)$tax_group_rule[0]['description'] == 30) {
                    $xmlCPE .= '  <cac:TaxCategory>
                                        <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">O</cbc:ID>
                                        <cbc:Percent>' . $cabecera["PORCENTAJE_IGV"] . '</cbc:Percent>
                                        <cbc:TaxExemptionReasonCode>30</cbc:TaxExemptionReasonCode>
                                        <cac:TaxScheme>
                                                <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">9998</cbc:ID>
                                                <cbc:Name>INA</cbc:Name>
                                                <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                                        </cac:TaxScheme>
                                    </cac:TaxCategory>';
                }
                elseif (!empty($tax_group_rule) && (int)$tax_group_rule[0]['description'] == 20) {
                    $xmlCPE .= '  <cac:TaxCategory>
                                       <cbc:Percent>'.$cabecera['PORCENTAJE_IGV'].'</cbc:Percent> <!-- Porcentaje de impuesto -->
                                       <cbc:TaxExemptionReasonCode>20</cbc:TaxExemptionReasonCode> <!--  Catálogo No. 07: 10 Gravado - Operación Onerosa // 20 Exonerado - Operación Onerosa-->
                                       <cac:TaxScheme>
                                          <cbc:ID>9997</cbc:ID> <!-- codigo de tributo Cat. 5 -->
                                          <cbc:Name>EXO</cbc:Name> <!-- nombre del tributo Cat. 5 -->
                                          <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode> <!-- codigo inter. del tributo Cat. 5 -->
                                       </cac:TaxScheme>
                                    </cac:TaxCategory>';
                }
                else {
                    $xmlCPE .= '<cac:TaxCategory>
                                   <cbc:Percent>'.$cabecera['PORCENTAJE_IGV'].'</cbc:Percent> <!-- Porcentaje de impuesto -->
                                   <cbc:TaxExemptionReasonCode>10</cbc:TaxExemptionReasonCode> <!--  -->
                                   <cac:TaxScheme>
                                      <cbc:ID>1000</cbc:ID> <!-- codigo de tributo Cat. 5 -->
                                      <cbc:Name>IGV</cbc:Name> <!-- nombre del tributo Cat. 5 -->
                                      <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode> <!-- codigo inter. del tributo Cat. 5 -->
                                   </cac:TaxScheme>
                                </cac:TaxCategory>';
                }

                $xmlCPE .= '
                     </cac:TaxSubtotal>
                  </cac:TaxTotal>
                  <cac:Item>
                     <cbc:Description><![CDATA['.Tools::eliminar_tildes($item->product_name).']]></cbc:Description> <!-- Nombre del producto -->
                     <cac:SellersItemIdentification>
                        <cbc:ID>'.$item->product_id.'</cbc:ID> <!-- codigo del producto -->
                     </cac:SellersItemIdentification>
                  </cac:Item>
                  <cac:Price>
                     <cbc:PriceAmount currencyID="PEN">'.round($item->unit_price_tax_excl, 2).'</cbc:PriceAmount> <!-- Valor unitario del ítem sin impuesto -->
                  </cac:Price>
               </cac:InvoiceLine>
               ';

            }


        $xmlCPE .='</Invoice>';



        $doc->loadXML($xmlCPE);
        $xml_doc =  $doc->saveXML();
        $fp = fopen($ruta,"wb");
        fwrite($fp, $xml_doc);
        fclose($fp);
//            d("sadas");
        $resp['respuesta'] = 'OK';
        $resp['url_xml'] = $ruta;

        return $resp;
    }

    public static function crear_xml_factura_boleta_exonerada($cabecera, $detalle, $ruta)
    {
        $ruta = $ruta.".xml";
        $doc = new DOMDocument();
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = true;
        $doc->encoding = 'utf-8';
//        <cbc:IssueTime>'.date_format(date_create($cabecera['FECHA_DOCUMENTO']),"H:i:s").'</cbc:IssueTime> <!-- Hora de emisión hh-mm-ss-->
        $xmlCPE = '<?xml version="1.0" encoding="UTF-8"?>
            <Invoice xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ccts="urn:un:unece:uncefact:documentation:2" xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
               <ext:UBLExtensions>
                  <ext:UBLExtension>
                     <ext:ExtensionContent /> <!-- firma del certificado -->
                  </ext:UBLExtension>
               </ext:UBLExtensions>
               <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
               <cbc:CustomizationID>2.0</cbc:CustomizationID>
               <cbc:ID>'.$cabecera['NRO_COMPROBANTE'].'</cbc:ID> <!-- F###-NNNNNNNN -->
               <cbc:IssueDate>'.Tools::getFormatFechaGuardar($cabecera['FECHA_DOCUMENTO']).'</cbc:IssueDate> <!-- Fecha de emisión yyyy-mm-dd -->
               <cbc:IssueTime>00:00:00</cbc:IssueTime> <!-- Hora de emisión hh-mm-ss-->
               <cbc:DueDate>'.Tools::getFormatFechaGuardar($cabecera['FECHA_DOCUMENTO']).'</cbc:DueDate> <!-- Fecha de vencimiento yyyy-mm-dd -->
               <cbc:InvoiceTypeCode 
               listID="'.$cabecera['TIPO_OPERACION'].'">'.$cabecera['COD_TIPO_DOCUMENTO'].'</cbc:InvoiceTypeCode> <!-- Código de tipo de documento 01 si es Factura // 03 si es boleta an2 Cat. 01 -->
               <cbc:Note languageLocaleID="1000"><![CDATA['.$cabecera['TOTAL_LETRAS'].']]></cbc:Note> <!-- Leyenda : monto en letras an..100 Cat. 52 -->
               <cbc:Note languageLocaleID="2001">BIENES TRANSFERIDOS EN LA AMAZONÍA REGIÓN SELVAPARA SER CONSUMIDOS EN LA MISMA</cbc:Note>
               <cbc:DocumentCurrencyCode>PEN</cbc:DocumentCurrencyCode> <!-- Cód. tipo moneda en la cual se emite la F.E. an3 Cat. 02 -->
               <cac:Signature>
                  <cbc:ID>SCSign</cbc:ID>
                  <cac:SignatoryParty>
                     <cac:PartyIdentification>
                        <cbc:ID>'.$cabecera['NUM_DOCUMENTO_EMPRESA'].'</cbc:ID> <!-- RUC DEL EMISOR -->
                     </cac:PartyIdentification>
                     <cac:PartyName>
                        <cbc:Name><![CDATA['.Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_EMPRESA']).']]></cbc:Name> <!-- nombre o razón social del emisor an..100 -->
                     </cac:PartyName>
                  </cac:SignatoryParty>
                  <cac:DigitalSignatureAttachment>
                     <cac:ExternalReference>
                        <cbc:URI>#SCSign</cbc:URI> <!--  -->
                     </cac:ExternalReference>
                  </cac:DigitalSignatureAttachment>
               </cac:Signature>
               <cac:AccountingSupplierParty>
                  <cac:Party>
                     <cac:PartyIdentification>
                        <cbc:ID schemeID="'.$cabecera['TIPO_DOCUMENTO_EMPRESA'].'" 
                        schemeAgencyName="PE:SUNAT" 
                        schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$cabecera['NUM_DOCUMENTO_EMPRESA'].'</cbc:ID> <!-- RUC DEL EMISOR -->
                     </cac:PartyIdentification>
                     <cac:PartyName>
                        <cbc:Name><![CDATA['.Tools::eliminar_tildes($cabecera['NOMBRE_COMERCIAL_EMPRESA']).']]></cbc:Name> <!-- Nombre Comercial DEL EMISOR -->
                     </cac:PartyName>
                     <cac:PartyLegalEntity>
                        <cbc:RegistrationName><![CDATA['.Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_EMPRESA']).']]></cbc:RegistrationName> <!-- Apellidos y nombres, denominación o razón social -->
                        <cac:RegistrationAddress>
                           <cbc:AddressTypeCode>0000</cbc:AddressTypeCode> <!-- Código de cuatro dígitos asignado por SUNAT, que identifica al establecimiento anexo. Dicho código se genera al momento la respectiva comunicación del establecimiento. Tratándose del domicilio fiscal y en el caso de no poder determinar el lugar de la venta, informar “0000” -->
                        </cac:RegistrationAddress>
                     </cac:PartyLegalEntity>
                  </cac:Party>
               </cac:AccountingSupplierParty>
               <cac:AccountingCustomerParty>
                  <cac:Party>
                     <cac:PartyIdentification>
                        <cbc:ID schemeID="'.$cabecera['TIPO_DOCUMENTO_CLIENTE'].'" 
                        schemeAgencyName="PE:SUNAT"
                         schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">'.$cabecera['NRO_DOCUMENTO_CLIENTE'].'</cbc:ID> <!-- Numero de documento schemeID tipo de documento de identidad -->
                     </cac:PartyIdentification>
                     <cac:PartyLegalEntity>
                        <cbc:RegistrationName><![CDATA['.Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_CLIENTE']).']]></cbc:RegistrationName> <!-- Nombre o denominación o razón social del cliente -->
                     </cac:PartyLegalEntity>
                  </cac:Party>
               </cac:AccountingCustomerParty>
               <cac:TaxTotal>
                  <cbc:TaxAmount currencyID="PEN">0.00</cbc:TaxAmount> <!-- Monto total de impuestos -->
                  <cac:TaxSubtotal>
                     <cbc:TaxableAmount currencyID="PEN">'.round($cabecera['TOTAL'],2).'</cbc:TaxableAmount> <!-- Monto las operaciones gravadas -->
                     <cbc:TaxAmount currencyID="PEN">0</cbc:TaxAmount> <!-- Monto total de impuestos -->
                     <cac:TaxCategory>
                        <cac:TaxScheme>
                           <cbc:ID>9997</cbc:ID> <!-- codigo de tributo Cat. 5 -->
                           <cbc:Name>EXO</cbc:Name> <!-- nombre del tributo Cat. 5 -->
                           <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode> <!-- codigo inter. del tributo Cat. 5 -->
                        </cac:TaxScheme>
                     </cac:TaxCategory>
                  </cac:TaxSubtotal>
               </cac:TaxTotal>
               <cac:LegalMonetaryTotal>
                  <cbc:LineExtensionAmount currencyID="PEN">'.round($cabecera['TOTAL'],2).'</cbc:LineExtensionAmount> <!-- Total Valor de Venta sin impuesto -->
                  <cbc:PayableAmount currencyID="PEN">'.round($cabecera['TOTAL'], 2).'</cbc:PayableAmount> <!-- Total Valor de Venta con impuesto -->
               </cac:LegalMonetaryTotal>
               ';

        if((int)$cabecera['ES_PORCONSUMO'] == 1){
            $xmlCPE .= '<cac:InvoiceLine>
                  <cbc:ID>1</cbc:ID>  <!-- Número de orden del Ítem -->
                  <cbc:InvoicedQuantity unitCode="NIU">1</cbc:InvoicedQuantity> <!-- unitCode: Unidad de medida por ítem Cat. 3 -->
                  <cbc:LineExtensionAmount currencyID="PEN">'.round($cabecera['TOTAL'],2).'</cbc:LineExtensionAmount> <!-- valor cantidad * valor unitario sin impuesto -->
                  <cac:PricingReference>
                     <cac:AlternativeConditionPrice>
                        <cbc:PriceAmount currencyID="PEN">'.round($cabecera['TOTAL'], 2).'</cbc:PriceAmount> <!-- Precio de venta unitario con impuesto por item y código -->
                        <cbc:PriceTypeCode>01</cbc:PriceTypeCode> <!-- Codigo de precio Cat 16 -->
                     </cac:AlternativeConditionPrice>
                  </cac:PricingReference>
                  <cac:TaxTotal>
                     <cbc:TaxAmount currencyID="PEN">0.00</cbc:TaxAmount> <!-- impuesto por linea de item -->
                     <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="PEN">'.round($cabecera['TOTAL'],2).'</cbc:TaxableAmount> <!-- valor cantidad * valor unitario -->
                        <cbc:TaxAmount currencyID="PEN">0</cbc:TaxAmount> <!--  -->
                        <cac:TaxCategory>
                           <cbc:Percent>'.$cabecera['PORCENTAJE_IGV'].'</cbc:Percent> <!-- Porcentaje de impuesto -->
                           <cbc:TaxExemptionReasonCode>20</cbc:TaxExemptionReasonCode> <!--  Catálogo No. 07: 10 Gravado - Operación Onerosa // 20 Exonerado - Operación Onerosa-->
                           <cac:TaxScheme>
                              <cbc:ID>9997</cbc:ID> <!-- codigo de tributo Cat. 5 -->
                              <cbc:Name>EXO</cbc:Name> <!-- nombre del tributo Cat. 5 -->
                              <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode> <!-- codigo inter. del tributo Cat. 5 -->
                           </cac:TaxScheme>
                        </cac:TaxCategory>
                     </cac:TaxSubtotal>
                  </cac:TaxTotal>
                  <cac:Item>
                     <cbc:Description><![CDATA[POR CONSUMO]]></cbc:Description> <!-- Nombre del producto -->
                     <cac:SellersItemIdentification>
                        <cbc:ID>9999</cbc:ID> <!-- codigo del producto -->
                     </cac:SellersItemIdentification>
                  </cac:Item>
                  <cac:Price>
                     <cbc:PriceAmount currencyID="PEN">'.round($cabecera['SUB_TOTAL'],2).'</cbc:PriceAmount> <!-- Valor unitario del ítem sin impuesto -->
                  </cac:Price>
               </cac:InvoiceLine>
               ';
        }else{
            $count = 0;
            foreach ($detalle as $key=>$item) {
                $count++;
                $xmlCPE .= '<cac:InvoiceLine>
                  <cbc:ID>'.$count.'</cbc:ID>  <!-- Número de orden del Ítem -->
                  <cbc:InvoicedQuantity unitCode="NIU">'.(float)$item->product_quantity.'</cbc:InvoicedQuantity> <!-- unitCode: Unidad de medida por ítem Cat. 3 -->
                  <cbc:LineExtensionAmount currencyID="PEN">'.round($item->unit_price_tax_incl,2).'</cbc:LineExtensionAmount> <!-- valor cantidad * valor unitario sin impuesto -->
                  <cac:PricingReference>
                     <cac:AlternativeConditionPrice>
                        <cbc:PriceAmount currencyID="PEN">'.round($item->unit_price_tax_incl,2).'</cbc:PriceAmount> <!-- Precio de venta unitario con impuesto por item y código -->
                        <cbc:PriceTypeCode>01</cbc:PriceTypeCode> <!-- Codigo de precio Cat 16 -->
                     </cac:AlternativeConditionPrice>
                  </cac:PricingReference>
                  <cac:TaxTotal>
                     <cbc:TaxAmount currencyID="PEN">0.00</cbc:TaxAmount> <!-- impuesto por linea de item -->
                     <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="PEN">'.round($item->unit_price_tax_incl,2).'</cbc:TaxableAmount> <!-- valor cantidad * valor unitario -->
                        <cbc:TaxAmount currencyID="PEN">0</cbc:TaxAmount> <!--  -->
                        <cac:TaxCategory>
                           <cbc:Percent>'.$cabecera['PORCENTAJE_IGV'].'</cbc:Percent> <!-- Porcentaje de impuesto -->
                           <cbc:TaxExemptionReasonCode>20</cbc:TaxExemptionReasonCode> <!--  Catálogo No. 07: 10 Gravado - Operación Onerosa // 20 Exonerado - Operación Onerosa-->
                           <cac:TaxScheme>
                              <cbc:ID>9997</cbc:ID> <!-- codigo de tributo Cat. 5 -->
                              <cbc:Name>EXO</cbc:Name> <!-- nombre del tributo Cat. 5 -->
                              <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode> <!-- codigo inter. del tributo Cat. 5 -->
                           </cac:TaxScheme>
                        </cac:TaxCategory>
                     </cac:TaxSubtotal>
                  </cac:TaxTotal>
                  <cac:Item>
                     <cbc:Description><![CDATA['.Tools::eliminar_tildes($item->product_name).']]></cbc:Description> <!-- Nombre del producto -->
                     <cac:SellersItemIdentification>
                        <cbc:ID>'.$item->product_id.'</cbc:ID> <!-- codigo del producto -->
                     </cac:SellersItemIdentification>
                  </cac:Item>
                  <cac:Price>
                     <cbc:PriceAmount currencyID="PEN">'.round($item->unit_price_tax_incl, 2).'</cbc:PriceAmount> <!-- Valor unitario del ítem sin impuesto -->
                  </cac:Price>
               </cac:InvoiceLine>
               ';
            }
        }


        $xmlCPE .='</Invoice>';

        if (!file_exists($ruta)) {
            $doc->loadXML($xmlCPE);
            $xml_doc =  $doc->saveXML();
            $fp = fopen($ruta,"wb");
            fwrite($fp, $xml_doc);
            fclose($fp);
        }
        return array("ruta_xml" => $ruta);
    }

    public static function crear_xml_nota_credito($cabecera, $detalle, $ruta) {


        $ruta = $ruta.".xml";
        $doc = new DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        //$doc->encoding = 'ISO-8859-1';
        $doc->encoding = 'utf-8';

        $xmlCPE = '<?xml version="1.0" encoding="UTF-8"?>
<CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ccts="urn:un:unece:uncefact:documentation:2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1" xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent>
            </ext:ExtensionContent>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>' . $cabecera["NRO_COMPROBANTE"] . '</cbc:ID>
    <cbc:IssueDate>'.Tools::getFormatFechaGuardar($cabecera['FECHA_DOCUMENTO']).'</cbc:IssueDate>
    <cbc:IssueTime>00:00:00</cbc:IssueTime>
    <cbc:DocumentCurrencyCode>' . $cabecera["COD_MONEDA"] . '</cbc:DocumentCurrencyCode>
    <cac:DiscrepancyResponse>
        <cbc:ReferenceID>' . $cabecera["NRO_DOCUMENTO_MODIFICA"] . '</cbc:ReferenceID>
        <cbc:ResponseCode>' . $cabecera["COD_TIPO_MOTIVO"] . '</cbc:ResponseCode>
        <cbc:Description><![CDATA[' . $cabecera["DESCRIPCION_MOTIVO"] . ']]></cbc:Description>
    </cac:DiscrepancyResponse>
    <cac:BillingReference>
        <cac:InvoiceDocumentReference>
            <cbc:ID>' . $cabecera["NRO_DOCUMENTO_MODIFICA"] . '</cbc:ID>
            <cbc:DocumentTypeCode>' . $cabecera["TIPO_COMPROBANTE_MODIFICA"] . '</cbc:DocumentTypeCode>
        </cac:InvoiceDocumentReference>
    </cac:BillingReference>
    <cac:Signature>
        <cbc:ID>IDSignST</cbc:ID>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[' . Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_EMPRESA']) . ']]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#SignatureSP</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="' . $cabecera['TIPO_DOCUMENTO_EMPRESA'] . '" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[' . Tools::eliminar_tildes($cabecera['NOMBRE_COMERCIAL_EMPRESA']) . ']]></cbc:Name>
            </cac:PartyName>
            <cac:PartyLegalEntity>
<cbc:RegistrationName><![CDATA[' . Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_EMPRESA']) . ']]></cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="' . $cabecera['TIPO_DOCUMENTO_CLIENTE'] . '" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $cabecera["NRO_DOCUMENTO_CLIENTE"] . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
<cbc:RegistrationName><![CDATA[' . Tools::eliminar_tildes($cabecera['RAZON_SOCIAL_CLIENTE']) . ']]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>
   
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="' . $cabecera["COD_MONEDA"] . '">' . round($cabecera['TOTAL_IGV'],2) . '</cbc:TaxAmount>
        <cac:TaxSubtotal>
        <cbc:TaxableAmount currencyID="' . $cabecera["COD_MONEDA"] . '">' . round($cabecera['SUB_TOTAL'],2) . '</cbc:TaxableAmount>
        <cbc:TaxAmount currencyID="' . $cabecera["COD_MONEDA"] . '">' . round($cabecera['TOTAL_IGV'],2) . '</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">1000</cbc:ID>
                    <cbc:Name>IGV</cbc:Name>
                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
    </cac:TaxTotal>
    <cac:LegalMonetaryTotal>
        <cbc:PayableAmount currencyID="' . $cabecera["COD_MONEDA"] . '">' . round($cabecera['TOTAL'], 2) . '</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>';



        $count = 0;
        foreach ($detalle as $key=>$item) {
            $count++;
            $xmlCPE = $xmlCPE . '<cac:CreditNoteLine>
        <cbc:ID>' . $count . '</cbc:ID>
<cbc:CreditedQuantity unitCode="NIU">'.(float)$item->product_quantity.'</cbc:CreditedQuantity>
<cbc:LineExtensionAmount currencyID="' . $cabecera["COD_MONEDA"] . '">'.round($item->total_price_tax_excl,2).'</cbc:LineExtensionAmount>
        <cac:PricingReference>
            <cac:AlternativeConditionPrice>
<cbc:PriceAmount currencyID="' . $cabecera["COD_MONEDA"] . '">'.round($item->unit_price_tax_incl,2).'</cbc:PriceAmount>
                <cbc:PriceTypeCode>01</cbc:PriceTypeCode> <!-- Precio unitario (incluye el IGV) -->
            </cac:AlternativeConditionPrice>
        </cac:PricingReference>
        <cac:TaxTotal>
<cbc:TaxAmount currencyID="' . $cabecera["COD_MONEDA"] . '">'.round(($item->total_price_tax_incl - $item->total_price_tax_excl),2).'</cbc:TaxAmount>
            <cac:TaxSubtotal>
<cbc:TaxableAmount currencyID="' . $cabecera["COD_MONEDA"] . '">'.round($item->total_price_tax_excl,2).'</cbc:TaxableAmount>
<cbc:TaxAmount currencyID="' . $cabecera["COD_MONEDA"] . '">'.round(($item->total_price_tax_incl - $item->total_price_tax_excl),2).'</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cbc:Percent>' . $cabecera["PORCENTAJE_IGV"] . '</cbc:Percent>
<cbc:TaxExemptionReasonCode>10</cbc:TaxExemptionReasonCode>
                    <cac:TaxScheme>
                        <cbc:ID>1000</cbc:ID>
                        <cbc:Name>IGV</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        <cac:Item>
<cbc:Description><![CDATA[' . Tools::eliminar_tildes($item->product_name) . ']]></cbc:Description>
            <cac:SellersItemIdentification>
                <cbc:ID>'.$item->product_id.'</cbc:ID>
            </cac:SellersItemIdentification>
        </cac:Item>
        <cac:Price>
<cbc:PriceAmount currencyID="' . $cabecera["COD_MONEDA"] . '">' . round($item->unit_price_tax_excl, 2) . '</cbc:PriceAmount>
        </cac:Price>
    </cac:CreditNoteLine>';
        }

        $xmlCPE = $xmlCPE . '</CreditNote>';

        $doc->loadXML($xmlCPE);
        $doc->save($ruta);

        $resp['respuesta'] = 'OK';
        $resp['url_xml'] = $ruta;
        return $resp;
    }

    public static function crear_xml_baja_sunat($cabecera, $detalle, $ruta) {

        $ruta = $ruta.".xml";
        $doc = new DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'ISO-8859-1';
        $xmlCPE = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?><VoidedDocuments xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:VoidedDocuments-1" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent>
            </ext:ExtensionContent>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
    <cbc:CustomizationID>1.0</cbc:CustomizationID>
    <cbc:ID>' . $cabecera["NRO_COMPROBANTE"] . '</cbc:ID>
    <cbc:ReferenceDate>' . Tools::getFormatFechaGuardar($cabecera['FECHA_DOCUMENTO']) . '</cbc:ReferenceDate>
    <cbc:IssueDate>' . $cabecera["FECHA_BAJA"] . '</cbc:IssueDate>
    <cac:Signature>
    <cbc:ID>IDSignKG</cbc:ID>
    <cac:SignatoryParty>
    <cac:PartyIdentification>
    <cbc:ID>' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:ID>
    </cac:PartyIdentification>
    <cac:PartyName>
    <cbc:Name>' . Tools::eliminar_tildes($cabecera["RAZON_SOCIAL_EMPRESA"]) . '</cbc:Name>
    </cac:PartyName>
    </cac:SignatoryParty>
    <cac:DigitalSignatureAttachment>
    <cac:ExternalReference>
    <cbc:URI>SIGN</cbc:URI>
    </cac:ExternalReference>
    </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:AccountingSupplierParty>
    <cbc:CustomerAssignedAccountID>' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:CustomerAssignedAccountID>
    <cbc:AdditionalAccountID>' . $cabecera["TIPO_DOCUMENTO_EMPRESA"] . '</cbc:AdditionalAccountID>
    <cac:Party>
    <cac:PartyLegalEntity>
    <cbc:RegistrationName><![CDATA[' . Tools::eliminar_tildes($cabecera["RAZON_SOCIAL_EMPRESA"]) . ']]></cbc:RegistrationName>
    </cac:PartyLegalEntity>
    </cac:Party>
    </cac:AccountingSupplierParty>
        <sac:VoidedDocumentsLine>
            <cbc:LineID>1</cbc:LineID>
            <cbc:DocumentTypeCode>01</cbc:DocumentTypeCode>
            <sac:DocumentSerialID>' . $cabecera["SERIE"] . '</sac:DocumentSerialID>
            <sac:DocumentNumberID>' . $cabecera["NUMERO"] . '</sac:DocumentNumberID>
            <sac:VoidReasonDescription><![CDATA[' . Tools::eliminar_tildes($cabecera["MOTIVO"]) . ']]></sac:VoidReasonDescription>
        </sac:VoidedDocumentsLine>
    </VoidedDocuments>';

        $doc->loadXML($xmlCPE);
        $xml_doc =  $doc->saveXML();
        $fp = fopen($ruta,"wb");
        fwrite($fp, $xml_doc);
        fclose($fp);
        $resp['respuesta'] = 'OK';
        $resp['url_xml'] = $ruta;

        return $resp;
    }

    public function crear_xml_resumen_documentos($cabecera, $detalle, $ruta) {
        $validacion = new validaciondedatos();
        $doc = new DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'ISO-8859-1';
        $xmlCPE = '<?xml version="1.0" encoding="iso-8859-1" standalone="no"?>
        <SummaryDocuments 
        xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:SummaryDocuments-1" 
        xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" 
        xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" 
        xmlns:ds="http://www.w3.org/2000/09/xmldsig#" 
        xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" 
        xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1"
        xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" 
        xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2">
        <ext:UBLExtensions>
            <ext:UBLExtension>
                            <ext:ExtensionContent>
                </ext:ExtensionContent>
            </ext:UBLExtension>
        </ext:UBLExtensions>
        <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
        <cbc:CustomizationID>1.1</cbc:CustomizationID>
        <!--  Identificador del resumen  -->
        <cbc:ID>' . $cabecera["CODIGO"] . '-' . $cabecera["SERIE"] . '-' . $cabecera["SECUENCIA"] . '</cbc:ID>
        <cbc:ReferenceDate>' . $cabecera["FECHA_REFERENCIA"] . '</cbc:ReferenceDate>
        <cbc:IssueDate>' . $cabecera["FECHA_DOCUMENTO"] . '</cbc:IssueDate>
        <cac:Signature>
            <cbc:ID>' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:ID>
            <cac:SignatoryParty>
                <cac:PartyIdentification>
                    <cbc:ID>' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:ID>
                </cac:PartyIdentification>
                <cac:PartyName>
                    <cbc:Name>' . $cabecera["RAZON_SOCIAL_EMPRESA"] . '</cbc:Name>
                </cac:PartyName>
            </cac:SignatoryParty>
            <cac:DigitalSignatureAttachment>
                <cac:ExternalReference>
                    <cbc:URI>' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:URI>
                </cac:ExternalReference>
            </cac:DigitalSignatureAttachment>
        </cac:Signature>
        <cac:AccountingSupplierParty>
            <!--  Numero de RUC  -->
            <cbc:CustomerAssignedAccountID>' . $cabecera["NUM_DOCUMENTO_EMPRESA"] . '</cbc:CustomerAssignedAccountID>
            <!--  Tipo de documento de identidad - Catalogo No. 06  -->
            <cbc:AdditionalAccountID>' . $cabecera["TIPO_DOCUMENTO_EMPRESA"] . '</cbc:AdditionalAccountID>
            <cac:Party>
                <cac:PartyLegalEntity>
                    <!--  Apellidos y nombres o denominacion o razon social  -->
                    <cbc:RegistrationName>' . $cabecera["RAZON_SOCIAL_EMPRESA"] . '</cbc:RegistrationName>
                </cac:PartyLegalEntity>
            </cac:Party>
        </cac:AccountingSupplierParty>';
        for ($i = 0; $i < count($detalle); $i++) {
            $xmlCPE = $xmlCPE . '<sac:SummaryDocumentsLine>
            <cbc:LineID>' . $detalle[$i]["ITEM"] . '</cbc:LineID>
            <!--  Tipo de documento - Catalogo No. 01  -->
            <cbc:DocumentTypeCode>' . $detalle[$i]["TIPO_COMPROBANTE"] . '</cbc:DocumentTypeCode>
            <!--  Serie y numero de comprobante  -->
            <cbc:ID>' . $detalle[$i]["NRO_COMPROBANTE"] . '</cbc:ID>
            <cac:AccountingCustomerParty>
                <!--  Numero de documento de identidad  -->
                <cbc:CustomerAssignedAccountID>' . $detalle[$i]["NRO_DOCUMENTO"] . '</cbc:CustomerAssignedAccountID>
                <!--  Tipo de documento de identidad - Catalogo No. 06  -->
                <cbc:AdditionalAccountID>' . $detalle[$i]["TIPO_DOCUMENTO"] . '</cbc:AdditionalAccountID>
            </cac:AccountingCustomerParty>';
            if ($detalle[$i]["TIPO_COMPROBANTE"] == "07" || $detalle[$i]["TIPO_COMPROBANTE"] == "08") {
                $xmlCPE = $xmlCPE . '<cac:BillingReference>
                <cac:InvoiceDocumentReference>
                    <cbc:ID>' . $detalle[$i]["NRO_COMPROBANTE_REF"] . '</cbc:ID>
                    <cbc:DocumentTypeCode>' . $detalle[$i]["TIPO_COMPROBANTE_REF"] . '</cbc:DocumentTypeCode>
                </cac:InvoiceDocumentReference>
            </cac:BillingReference>';
            }

            $xmlCPE = $xmlCPE . '
            <!--  (Codigo de operacion del item - catalogo No. 19)  -->
            <cac:Status>
                <cbc:ConditionCode>' . $detalle[$i]["STATUS"] . '</cbc:ConditionCode>
            </cac:Status>      
            <!-- Importe total de la venta, sesion en uso o del servicio prestado -->          
            <sac:TotalAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["TOTAL"] . '</sac:TotalAmount>
            <!--  Total valor de venta - operaciones gravadas  -->
            <sac:BillingPayment>
                <cbc:PaidAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["GRAVADA"] . '</cbc:PaidAmount>
                <cbc:InstructionID>01</cbc:InstructionID>
            </sac:BillingPayment>';

            if (intval($detalle[$i]["EXONERADO"]) > 0) {
                $xmlCPE = $xmlCPE . '<sac:BillingPayment>
                <cbc:PaidAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["EXONERADO"] . '</cbc:PaidAmount>
                <cbc:InstructionID>02</cbc:InstructionID>
            </sac:BillingPayment>';
            }

            if (intval($detalle[$i]["INAFECTO"]) > 0) {
                $xmlCPE = $xmlCPE . '<sac:BillingPayment>
                <cbc:PaidAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["INAFECTO"] . '</cbc:PaidAmount>
                <cbc:InstructionID>03</cbc:InstructionID>
            </sac:BillingPayment>';
            }

            if (intval($detalle[$i]["EXPORTACION"]) > 0) {
                $xmlCPE = $xmlCPE . '<sac:BillingPayment>
                <cbc:PaidAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["EXPORTACION"] . '</cbc:PaidAmount>
                <cbc:InstructionID>04</cbc:InstructionID>
            </sac:BillingPayment>';
            }

            if (intval($detalle[$i]["GRATUITAS"]) > 0) {
                $xmlCPE = $xmlCPE . '<sac:BillingPayment>
                <cbc:PaidAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["GRATUITAS"] . '</cbc:PaidAmount>
                <cbc:InstructionID>05</cbc:InstructionID>
            </sac:BillingPayment>';
            }

            if (intval($detalle[$i]["MONTO_CARGO_X_ASIG"]) > 0) {
                $xmlCPE = $xmlCPE . '<cac:AllowanceCharge>';
                if ($detalle[$i]["CARGO_X_ASIGNACION"] == 1) {
                    $xmlCPE = $xmlCPE . '<cbc:ChargeIndicator>true</cbc:ChargeIndicator>';
                } else {
                    $xmlCPE = $xmlCPE . '<cbc:ChargeIndicator>false</cbc:ChargeIndicator>';
                }
                $xmlCPE = $xmlCPE . '<cbc:Amount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["MONTO_CARGO_X_ASIG"] . '</cbc:Amount>
                        </cac:AllowanceCharge>';
            }
            if (intval($detalle[$i]["ISC"]) > 0) {
                $xmlCPE = $xmlCPE . '<cac:TaxTotal>
                <cbc:TaxAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["ISC"] . '</cbc:TaxAmount>
                <cac:TaxSubtotal>
                    <cbc:TaxAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["ISC"] . '</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:ID>2000</cbc:ID>
                            <cbc:Name>ISC</cbc:Name>
                            <cbc:TaxTypeCode>EXC</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>
            </cac:TaxTotal>';
            }
            $xmlCPE = $xmlCPE . '
            <!--  Total IGV  -->
            <cac:TaxTotal>
                <cbc:TaxAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["IGV"] . '</cbc:TaxAmount>
                <cac:TaxSubtotal>
                    <cbc:TaxAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["IGV"] . '</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:ID>1000</cbc:ID>
                            <cbc:Name>IGV</cbc:Name>
                            <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>
            </cac:TaxTotal>';

            if (intval($detalle[$i]["OTROS"]) > 0) {
                $xmlCPE = $xmlCPE . '<cac:TaxTotal>
                <cbc:TaxAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["OTROS"] . '</cbc:TaxAmount>
                <cac:TaxSubtotal>
                    <cbc:TaxAmount currencyID="' . $detalle[$i]["COD_MONEDA"] . '">' . $detalle[$i]["OTROS"] . '</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:ID>9999</cbc:ID>
                            <cbc:Name>OTROS</cbc:Name>
                            <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>
            </cac:TaxTotal>';
            }
            $xmlCPE = $xmlCPE . '</sac:SummaryDocumentsLine>';
        }
        $xmlCPE = $xmlCPE . '</SummaryDocuments>';


        $doc->loadXML($xmlCPE);
        $xml_doc =  $doc->saveXML();
        $fp = fopen($ruta,"wb");
        fwrite($fp, $xml_doc);
        fclose($fp);
        $resp['respuesta'] = 'OK';
        $resp['url_xml'] = $ruta;

        return $resp;
    }

}