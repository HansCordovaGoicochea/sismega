<!doctype html>
<html lang="es" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="UTF-8">

    <title>Document</title>
</head>
<body>
<br>
<br>
<br>
{*<div style="border: 1px solid #ddd;">*}
<table style="width: 100%; font-family: Calibri; " cellpadding="1" cellspacing="1">
    <tr>
        <td>
            <table>
                <tr>
                    <td colspan="2">
                        <strong>
                            <img src="{$logo}" style="width:180px;height: 150px;" /><br>
                            {*<span >{$tienda->direccion|upper}</span><br>*}
                            <span >{$address_shop->address1|upper}</span><br>
                            <span>TELF: {$address_shop->phone}</span><br>
                        </strong>
                    </td>
                </tr>
            </table>
        </td>
        <td>
            <table>
                <tr>

                    <td style="text-align: center">
                        <h1>{$PS_SHOP_RAZON_SOCIAL}</h1>
                        {*<div style="border: 1px solid #ddd; ">*}
                        <h1 style="color: #428bca;;">
                            <span>RUC: {$PS_SHOP_RUC}</span>
                            <br>
                            <span>NOTA DE CREDITO <br> ELECTRONICA</span>
                            <br>
                            <span>{$comprobante->numero_comprobante}</span>
                        </h1>
                        {*</div>*}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table style="font-size: 9px;">
                <tr>
                    <td colspan="2"><strong>Señor(es): </strong>
                        {if $customer->id == 1}
                            <span></span>
                        {else}
                            <span>{$customer->firstname}</span>
                        {/if}
                    </td>

                    {assign var="tipo_documento_identidad" value=Tipodocumentolegal::getById($customer->id_document)}
                    <td colspan="2"><strong>{if $customer->id_document != 0}{$tipo_documento_identidad['nombre']|strtoupper}{else}DNI{/if}:</strong>
                        {if $customer->id == 1}
                            <span></span>
                        {else}
                            <span>{$customer->num_document}</span>
                        {/if}</td>

                </tr>
                <tr>
                    <td colspan="2"><strong>Fecha de Emisión:</strong> {$comprobante->fecha_envio_comprobante|date_format:"%d /%m /%Y"}</td>
                    {if $order->id_currency==1}
                        {assign var='moneda' value='SOLES'}
                    {else}
                        {assign var='moneda' value='DOLARES'}
                    {/if}
                    <td colspan="2"><strong>Moneda:</strong> {$moneda}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Fecha de Vencimiento:</strong>  {$comprobante->fecha_envio_comprobante|date_format:"%d /%m /%Y"}</td>
                    <td colspan="2"><strong>Dirección del Cliente:</strong>
                        {if $customer->id == 1}
                            <span></span>
                        {else}
                            <span>{$customer->direccion}</span>
                        {/if}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td COLSPAN="2">
            <table style="font-size: 9px;" width="100%">
                <tr style="background-color: #428bca; color: #fff;">
                    <th style="text-align: center;  border-top: 1px solid black;border-bottom: 1px solid black;" width="9%"><strong>CANT.</strong></th>
                    <th style="text-align: center;  border-top: 1px solid black;border-bottom: 1px solid black;" width="18%"><strong>UNIDAD DE MEDIDA</strong></th>
                    <th style="text-align: left;  border-top: 1px solid black;border-bottom: 1px solid black;" width="45%"><strong>DESC.</strong></th>
                    <th style="text-align: center;  border-top: 1px solid black;border-bottom: 1px solid black;" width="10%"><strong>P.UNIT.</strong></th>
                    <th style="text-align: center;  border-top: 1px solid black;border-bottom: 1px solid black;" width="8%"><strong></strong></th>
                    <th style="text-align: center; border-top: 1px solid black;border-bottom: 1px solid black;" width="10%"><strong>IMPORTE</strong></th>
                </tr>
                <!-- PRODUCTS -->
                {foreach $order_details as $order_detail}
                    <tr >
                        <td style="text-align: center;">{$order_detail.product_quantity|round:2}</td>
                        <td style="text-align: center;">UNIDAD(ES)</td>
                        <td style="text-align: left;">{$order_detail.product_name} {$order_detail.nota}</td>
                        <td style="text-align: center;">{displayPrice currency=$order->id_currency price=$order_detail.unit_price_tax_incl}</td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: center;">{displayPrice currency=$order->id_currency price=$order_detail.total_price_tax_incl}</td>
                        {*<td style="text-align: center;">{displayPrice currency=$order->id_currency price=$order_detail.original_product_price*$order_detail.product_quantity}</td>*}
                        {*<td>{displayPrice currency=$order->id_currency price=$order_detail.original_product_price}</td>*}
                    </tr>
                {/foreach}
            </table>
        </td>
    </tr>
    <tr>
        {if $order->id_currency==1}
            {assign var='moneda' value='SOLES'}
        {else}
            {assign var='moneda' value='DOLARES'}
        {/if}
        <td colspan="2">
            <table style="font-size: 9px;" width="100%">
                <tr>
                    <td colspan="6"></td>
                    <td>{if $order->total_discounts > 0}Total descuentos:{/if}</td>
                    <td style="text-align: center">{if $order->total_discounts > 0} {displayPrice currency=$order->id_currency price=$order->total_discounts} - {/if}</td>
                </tr>
                <tr>
                    <td colspan="6" style="border-top: 1px solid black;">SON: {Tools::displaynumeroaletras($footer.total_paid_tax_incl, $moneda|upper)}</td>
                    <td  style="border-top: 1px solid black;"><strong>Op. Gravadas:</strong></td>
                    <td style="text-align: center; border-top: 1px solid black;">{displayPrice currency=$order->id_currency price=$order->total_paid_tax_excl}</td>
                </tr>
                <tr>
                    <td colspan="6"></td>
                    <td><strong>Op. Inafectas:</strong></td>
                    <td style="text-align: center">{displayPrice currency=$order->id_currency price=0}</td>
                </tr>
                <tr>
                    <td colspan="6"></td>
                    <td><strong>Op. Exoneradas:</strong></td>
                    <td style="text-align: center">{displayPrice currency=$order->id_currency price=0}</td>
                </tr>
                <tr>
                    <td colspan="6"></td>
                    <td><strong>IGV:</strong></td>
                    <td style="text-align: center">{displayPrice currency=$order->id_currency price=$footer.total_taxes}</td>
                </tr>
                <tr>
                    <td colspan="6"></td>
                    <td style="border-top: 1px solid black;"><strong>Importe Total:</strong></td>
                    <td style="text-align: center; border-top: 1px solid black;">{displayPrice currency=$order->id_currency price=$footer.total_paid_tax_incl}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" border="1" cellpadding="1" cellspacing="0">
                <tbody>
                <tr style="background-color: #428bca; color: #fff;">
                    <td  style="font-size: 8px!important;">Doc. Referencia</td>
                    <td  style="font-size: 8px!important;">Tipo</td>
                    <td  style="font-size: 8px!important;">Serie</td>
                    <td  style="font-size: 8px!important;">Número</td>
                    <td  style="font-size: 8px!important;">F.Emisión</td>
                </tr>
                <tr>

                    <td  style="font-size: 8px!important;">Documento</td>
                    <td  style="font-size: 8px!important;">FACTURA</td>
                    {assign var="numeracionsplit" value='-'|explode:$comprobante_relacionado['numero_comprobante']}
                    <td  style="font-size: 8px!important;">{$numeracionsplit[0]}</td>
                    <td  style="font-size: 8px!important;">{$numeracionsplit[1]}</td>
                    <td  style="font-size: 8px!important;">{$comprobante_relacionado['fecha_envio_comprobante']|date_format:"%d/%m/%Y"}</td>
                </tr>
                <tr>
                    <td style="font-size: 8px!important;">SUSTENTO</td>
                    {if $comprobante->code_motivo_nota_credito == 1}
                        <td style="font-size: 8px!important;" COLSPAN="4">ANULACION DE LA OPERACION</td>
                    {elseif $comprobante->code_motivo_nota_credito == 1}
                        <td style="font-size: 8px!important;" COLSPAN="4">ANULACION POR ERROR EN EL RUCL</td>
                    {elseif $comprobante->code_motivo_nota_credito == 6}
                        <td style="font-size: 8px!important;" COLSPAN="4">DEVOLUCION TOTAL</td>
                    {/if}
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
    <tr>
        <td colspan="2" STYLE="text-align: center;">{$comprobante->tipo_documento_electronico|cat:' Electronica'|upper}</td>
    </tr>
    </tr>

</table>
{*</div>*}
</body>
</html>
