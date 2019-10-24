<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>Document</title>
</head>
<body>

<table style="width: 7.5cm; font-size: 8px;  color: #000;" cellpadding="1" cellspacing="1">
    {*<table style="width: 11%; font-size: 8px; color: #000;" cellpadding="1" cellspacing="1">*}
    {*se agrego un maximo numero de caracteres al numero del ticket*}
    {assign var="caracteres" value=$order->id|@count}
    {assign var="numcaracteres" value=7-$caracteres}
    {*<thead>*}
    <tr>
        <th style="text-align: left; font-size: 12px;"><strong>Ticket N° {$order->nro_ticket}</strong></th>
    </tr>
    <tr>
        <th style="text-align: center">
            <strong>
                <img src="{$logo}" style="width:5cm;height: 3cm;" />
            </strong>
        </th>
    </tr>
    {*</thead>*}
    <tr style="text-align: center">
        <td colspan="4">
            <span >{$PS_SHOP_NAME|upper}</span><br>
            <span >{$address_shop->address1|upper}</span><br>
            <span>RUC: {$PS_SHOP_RUC}</span><br>
            <span>Fecha: {$order->date_upd|date_format:"%d/%m/%Y %H:%m:%S"}</span>
        </td>
    </tr>
    <tr style="text-align: left">
        <td colspan="4"><span>Nombre/R. Social: {if $customer->firstname}{$customer->firstname}{/if}</span><br><span>DNI/RUC: {if $customer->num_document}{$customer->num_document}{else}&nbsp;{/if}</span>{if $customer->direccion}<br><span>Direccion: {$customer->direccion}</span>{/if}</td>
    </tr>
    <tr >
        <td colspan="4" style="">
            <table width="100%">
                <tr>
                    <th style="text-align: center;" width="13%">Cant.</th>
                    <th style="text-align: left;" width="60%">Prod.</th>
                    <th style="text-align: center;" width="26%">Subtotal</th>
                </tr>
                <!-- PRODUCTS -->
                {foreach $order_details as $order_detail}
                    <tr>
                        <td style="text-align: center;">{$order_detail.product_quantity|round:2}</td>
                        <td style="text-align: left;">{$order_detail.product_name}</td>
                        <td style="text-align: center;">{displayPrice currency=$order->id_currency price=$order_detail.total_price_tax_incl|round:2}</td>
                    </tr>
                {/foreach}
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="">
            <table width="100%">
                <tr>
                    <td style="text-align: right;" colspan="3">DESCUENTO:</td>
                    <td style="text-align: center;">{if $order->total_discounts > 0} -{displayPrice currency=$order->id_currency price=$order->total_discounts}{else}S/0.00{/if}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right">SubTotal:</td>
                    <td style="text-align: center" >{displayPrice currency=$order->id_currency price=$footer.products_before_discounts_tax_excl}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right" >IGV:</td>
                    <td style="text-align: center">{displayPrice currency=$order->id_currency price=$footer.total_taxes}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right">Total:</td>
                    <td style="text-align: center">{displayPrice currency=$order->id_currency price=$footer.total_paid_tax_incl}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        {assign var="ultimopago" value=0}
        {assign var="vuelto" value=0}
        {assign var="acumuladopago" value = 0}
        {foreach from=$order->getOrderPaymentCollection() item=payment}
            {assign var="acumuladopago" value=$acumuladopago + $payment->amount}
            {assign var="ultimopago" value=$payment->amount}
            {assign var="vuelto" value=$payment->vuelto}
        {/foreach}

        {assign var="ultimopago" value=$ultimopago}
        <td colspan="4" style="border-top: 1px dashed black;">
            &nbsp;<br>Últ.Pagó: {displayPrice price=($ultimopago + $vuelto) currency=$order->id_currency}
            &nbsp;&nbsp;Deuda: {displayPrice currency=$order->id_currency price=round($footer.total_paid_tax_incl - ($acumuladopago + $vuelto),2)}
            &nbsp;Pagado: {displayPrice currency=$order->id_currency price=round(($acumuladopago + $vuelto),2)}
        </td>

    </tr>
    <tr><td colspan="4">Colaborador(es): </td></tr>
    {foreach OrderDetail::getDeailtColaboradores($order->id) as $order_detail}
        <tr >
            <td colspan="4" style="">{$order_detail.colaborador_name}</td>
        </tr>
    {/foreach}

    <tr>
        <td style="text-align: center" colspan="4">
            <span><strong>¡GRACIAS POR SU PREFERENCIA!</strong></span><br>
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>

</body>
</html>
