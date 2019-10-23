<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>
<table style="width: 7.4cm; font-size: 8px; color: #000;" cellpadding="1" cellspacing="1">
    <tr>
        <th style="text-align: center">
            <strong>
                <img src="{$logo}" style="width:5cm;height: 3cm;" />
            </strong>
        </th>
    </tr>
    <tr style="text-align: center">
        <td >
            <span>{$PS_SHOP_RAZON_SOCIAL|upper}</span><br>
            <span>RUC: {$PS_SHOP_RUC}</span><br>
            <span>{$comprobante->tipo_documento_electronico|cat:' de Venta Electronica'|upper} {$comprobante->numero_comprobante}</span>
        </td>
    </tr>

    <tr>
        <td style="border-top:1px dashed #000;"><strong>Fecha Emisión:</strong> {$comprobante->fecha_envio_comprobante|date_format:"%d /%m /%Y"}</td>
    </tr>
    <tr>
        <td><strong>Señor(es): </strong>

            {if $customer->id == 1}
                <span></span>
            {else}
                <span>{$customer->firstname}</span>
            {/if}

        </td>
    </tr>
    <tr>

        {assign var="tipo_documento_identidad" value=Tipodocumentolegal::getById($customer->id_document)}
        <td><strong>{if $customer->id_document != 0}{$tipo_documento_identidad['nombre']|strtoupper}{else}DNI{/if}:</strong>
            {if $customer->id == 1}
                <span></span>
            {else}
                <span>{$customer->num_document}</span>
            {/if}
        </td>

    </tr>
    <tr>
        <td><strong>Dirección:</strong>

            {if $customer->id == 1}
                <span></span>
            {else}
                <span>{$customer->direccion}</span>
            {/if}
        </td>
    </tr>
</table>
<table style="width: 7.4cm; font-size: 8px; color: #000;" cellpadding="1" cellspacing="1">
    <thead>
    <tr>
        <th style="text-align: center;  border-top: 1px dashed black;border-bottom: 1px dashed black;" width="15%">CANT.</th>
        <th style="text-align: left;  border-top: 1px dashed black;border-bottom: 1px dashed black;" width="50%">DESC.</th>
        <th style="text-align: center;  border-top: 1px dashed black;border-bottom: 1px dashed black;" width="15%">P.U.</th>
        <th style="text-align: center; border-top: 1px dashed black;border-bottom: 1px dashed black;" width="20%">IMPOR.</th>
    </tr>
    </thead>
    <!-- PRODUCTS -->
    {foreach $order_details as $order_detail}
        <tr >
            <td style="text-align: center; width: 15%;">{$order_detail.product_quantity|round:2}</td>
            <td style="text-align: left; width: 50%;">{$order_detail.product_name}</td>
            <td style="text-align: center; width: 15%;">{$order_detail.unit_price_tax_incl|round:2}</td>
            <td style="text-align: center; width: 20%;">{$order_detail.total_price_tax_incl|round:2}</td>
            {*<td>{displayPrice currency=$order->id_currency price=$order_detail.original_product_price}</td>*}
        </tr>
    {/foreach}
    <tr>
        <td style="text-align: right; border-top: 1px dashed black;" colspan="2">DESCUENTO:</td>
        <td style="text-align: right; border-top: 1px dashed black;" colspan="2">{if $order->total_discounts > 0} -{displayPrice currency=$order->id_currency price=$order->total_discounts}{else}S/0.00{/if}</td>
    </tr>
    <tr>
        <td style="text-align: right;" colspan="2">OP. GRAVADAS:</td>
        <td style="text-align: right;" colspan="2">{displayPrice currency=$order->id_currency price=$order->total_paid_tax_excl}</td>
    </tr>
    <tr>
        <td style="text-align: right;"  colspan="2">OP. INAFECTAS:</td>
        <td style="text-align: right;"  colspan="2">{displayPrice currency=$order->id_currency price=0}</td>
    </tr>
    <tr>
        <td style="text-align: right; "  colspan="2">IGV:</td>
        <td style="text-align: right;"  colspan="2">{displayPrice currency=$order->id_currency price=$footer.total_taxes}</td>
    </tr>
    <tr>
        <td style="text-align: right;  border-bottom: 1px dashed black;"  colspan="2">TOTAL:</td>
        <td style="text-align: right;  border-bottom: 1px dashed black;"  colspan="2">{displayPrice currency=$order->id_currency price=$footer.total_paid_tax_incl}</td>
    </tr>

    {if $order->id_currency==1}
        {assign var='moneda' value='SOLES'}
    {else}
        {assign var='moneda' value='DOLARES'}
    {/if}

    <tr>
        <td style="text-align: left; border-bottom: 1px dashed black; font-size: 9px;" colspan="6">SON: {Tools::displaynumeroaletras($footer.total_paid_tax_incl, $moneda|upper)}</td>
    </tr>
    <tr>
        <td colspan="6" STYLE="text-align: center;">{$comprobante->tipo_documento_electronico|cat:' de Venta Electronica'|upper}</td>
    </tr>
</table>
</body>
</html>
