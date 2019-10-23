<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">

</head>
<body>

<table style="font-size: 8px; color: #000;" cellpadding="1" cellspacing="1">
    <tr>
        <th style="text-align: center"><h2>Reporte Caja</h2></th>
    </tr>
    <tr>
        <div class="pull-left">
            <span class="badge {if !(bool)$aperturas_caja->estado}badge-danger{/if}">{$operacion_caja->fecha_apertura|date_format:"%d/%m/%Y %I:%M %p"} - {$operacion_caja->fecha_cierre|date_format:"%d/%m/%Y %I:%M %p"} - Monto de Apertura Caja: {displayPrice currency=1 price=$operacion_caja->monto_apertura|round:2} - {$empleado_apertura->firstname}, {$empleado_apertura->lastname}</span>
        </div>
    </tr>
    <tr>
        <td>
            <table width="100%" cellpadding="1"  border="1">
                <tr style="background-color: #428bca; color: #fff; font-size: 10px;">
                    <th style="text-align: left;" width="15%">Fecha</th>
                    <th style="text-align: left; " width="30%">Producto</th>
                    <th style="text-align: center; " width="10%">Cant</th>
                    <th style="text-align: center;  " width="15%">Importe</th>
                    <th style="text-align: center; " width="15%">Pagos</th>
                    <th style="text-align: center; " width="15%">Deuda</th>
                </tr>
                <tbody>
                <!-- PRODUCTS -->
                {assign var='suma_efectivo' value = 0}
                {if count($efectivo)}
                    <tr class="info" style="background-color: #08ca98; color: #fff; font-size: 9px;">
                        <td style="text-align: center;" colspan="6">
                            <strong>
                                EFECTIVO
                            </strong>
                        </td>
                    </tr>
                    {foreach from=$efectivo item=datos_fila}
                        {if isset($datos_fila.id_order) && (int)$datos_fila.id_order > 0}
                            <tr class="success">
                                <td style="text-align: left;" colspan="6">
                                    <strong>
                                        Venta {$datos_fila.nro_comprobante} - {$datos_fila.cliente}
                                    </strong>
                                </td>
                            </tr>

                            {assign var='total' value=0}
                            {assign var='nro_operaciones' value=0}
                            {foreach from=Order::getDetailsOrdersDateFromDateTO((int)$datos_fila.id_order) item='detail'}
                                {assign var='suma_efectivo' value = $suma_efectivo + $detail.total_price_tax_incl}
                                {assign var='total' value=$total+$detail.total_price_tax_incl}
                                {assign var='nro_operaciones' value=$nro_operaciones+1}
                                {if $detail.product_quantity > 0}
                                    <tr >
                                        <td style="text-align: left;">{$detail.fecha|date_format:"%d/%m/%Y %I:%M %p"}</td>
                                        <td style="text-align: left;">{$detail.product_name}</td>
                                        <td style="text-align: center;">{$detail.product_quantity|round:2}</td>
                                        <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>
                                        <td style="text-align: center;">- -</td>
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.unit_price_tax_incl|round:2}</td>*}
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>*}
                                    </tr>
                                {/if}

                            {/foreach}

                            <tr class="warning">
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;">Totales</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$total|round:2}</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$datos_fila.pagos|round:2}</td>
                                <td style="text-align: center;">S/ 0.00</td>
                                {*                                    <td style="text-align: right;"></td>*}
                            </tr>
                        {/if}
                        {foreachelse}
                        <tr>
                            <td class="list-empty" colspan="6">
                                <div class="list-empty-msg">
                                    <i class="icon-warning-sign list-empty-icon"></i>
                                    Ningún registro encontrado
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                {/if}

                {assign var='suma_visa' value = 0}
                {if count($visa)}
                    <tr class="info" style="background-color: #08ca98; color: #fff; font-size: 9px;">
                        <td style="text-align: center; " colspan="6">
                            <strong>
                                VISA
                            </strong>
                        </td>
                    </tr>
                    {foreach from=$visa item=datos_fila}
                        {if isset($datos_fila.id_order) && (int)$datos_fila.id_order > 0}
                            <tr class="success">
                                <td style="text-align: left; " colspan="6">
                                    <strong>
                                        Venta {$datos_fila.nro_comprobante} - {$datos_fila.cliente}
                                    </strong>
                                </td>
                            </tr>

                            {assign var='total' value=0}
                            {assign var='nro_operaciones' value=0}
                            {foreach from=Order::getDetailsOrdersDateFromDateTO((int)$datos_fila.id_order) item='detail'}
                                {assign var='suma_visa' value = $suma_visa + $detail.total_price_tax_incl}
                                {assign var='total' value=$total+$detail.total_price_tax_incl}
                                {assign var='nro_operaciones' value=$nro_operaciones+1}
                                {if $detail.product_quantity > 0}
                                    <tr >
                                        <td style="text-align: left;">{$detail.fecha|date_format:"%d/%m/%Y %I:%M %p"}</td>
                                        <td style="text-align: left;">{$detail.product_name}</td>
                                        <td style="text-align: center;">{$detail.product_quantity|round:2}</td>
                                        <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>
                                        <td style="text-align: center;">- -</td>
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.unit_price_tax_incl|round:2}</td>*}
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>*}
                                    </tr>
                                {/if}

                            {/foreach}

                            <tr class="warning">
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;">Totales</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$total|round:2}</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$datos_fila.pagos|round:2}</td>
                                <td style="text-align: center;">S/ 0.00</td>
                                {*                                    <td style="text-align: right;"></td>*}
                            </tr>
                        {/if}
                        {foreachelse}
                        <tr>
                            <td class="list-empty" colspan="6">
                                <div class="list-empty-msg">
                                    <i class="icon-warning-sign list-empty-icon"></i>
                                    Ningún registro encontrado
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                {/if}

                {assign var='suma_izipay' value = 0}
                {if count($izipay)}
                    <tr class="info" style="background-color: #08ca98; color: #fff; font-size: 9px;">
                        <td style="text-align: center;" colspan="6">
                            <strong>
                                IZIPAY
                            </strong>
                        </td>
                    </tr>
                    {foreach from=$izipay item=datos_fila}
                        {if isset($datos_fila.id_order) && (int)$datos_fila.id_order > 0}
                            <tr class="success">
                                <td style="text-align: left;" colspan="6">
                                    <strong>
                                        Venta {$datos_fila.nro_comprobante} - {$datos_fila.cliente}
                                    </strong>
                                </td>
                            </tr>

                            {assign var='total' value=0}
                            {assign var='nro_operaciones' value=0}
                            {foreach from=Order::getDetailsOrdersDateFromDateTO((int)$datos_fila.id_order) item='detail'}
                                {assign var='suma_izipay' value = $suma_izipay + $detail.total_price_tax_incl}
                                {assign var='total' value=$total+$detail.total_price_tax_incl}
                                {assign var='nro_operaciones' value=$nro_operaciones+1}
                                {if $detail.product_quantity > 0}
                                    <tr >
                                        <td style="text-align: left;">{$detail.fecha|date_format:"%d/%m/%Y %I:%M %p"}</td>
                                        <td style="text-align: left;">{$detail.product_name}</td>
                                        <td style="text-align: center;">{$detail.product_quantity|round:2}</td>
                                        <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>
                                        <td style="text-align: center;">- -</td>
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.unit_price_tax_incl|round:2}</td>*}
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>*}
                                    </tr>
                                {/if}

                            {/foreach}

                            <tr class="warning">
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;">Totales</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$total|round:2}</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$datos_fila.pagos|round:2}</td>
                                <td style="text-align: center;">S/ 0.00</td>
                                {*                                    <td style="text-align: right;"></td>*}
                            </tr>
                        {/if}
                        {foreachelse}
                        <tr>
                            <td class="list-empty" colspan="6">
                                <div class="list-empty-msg">
                                    <i class="icon-warning-sign list-empty-icon"></i>
                                    Ningún registro encontrado
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                {/if}

                {assign var='suma_porcobrar' value = 0}
                {if count($porcobrar)}
                    <tr class="info" style="background-color: #08ca98; color: #fff; font-size: 9px;">
                        <td style="text-align: center;" colspan="6">
                            <strong>
                                CUENTAS POR COBRAR
                            </strong>
                        </td>
                    </tr>
                    {foreach from=$porcobrar item=datos_fila}
                        {if isset($datos_fila.id_order) && (int)$datos_fila.id_order > 0}
                            <tr class="success">
                                <td style="text-align: left;" colspan="6">
                                    <strong>
                                        Venta {$datos_fila.nro_comprobante} - {$datos_fila.cliente}
                                    </strong>
                                </td>
                            </tr>

                            {assign var='total' value=0}
                            {assign var='nro_operaciones' value=0}
                            {foreach from=Order::getDetailsOrdersDateFromDateTO((int)$datos_fila.id_order) item='detail'}
                                {assign var='suma_porcobrar' value = $suma_porcobrar + $detail.pagos}
                                {assign var='total' value=$total+$detail.total_price_tax_incl}
                                {assign var='nro_operaciones' value=$nro_operaciones+1}
                                {if $detail.product_quantity > 0}
                                    <tr >
                                        <td style="text-align: left;">{$detail.fecha|date_format:"%d/%m/%Y %I:%M %p"}</td>
                                        <td style="text-align: left;">{$detail.product_name}</td>
                                        <td style="text-align: center;">{$detail.product_quantity|round:2}</td>
                                        <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>
                                        <td style="text-align: center;">- -</td>
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.unit_price_tax_incl|round:2}</td>*}
                                        {*                                            <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$detail.total_price_tax_incl|round:2}</td>*}
                                    </tr>
                                {/if}

                            {/foreach}

                            <tr class="warning">
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;"></td>
                                <td style="text-align: right;">Totales</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$total|round:2}</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$datos_fila.pagos|round:2}</td>
                                <td style="text-align: center;">{displayPrice currency=$datos_fila.id_currency price=$total - $datos_fila.pagos|round:2}</td>
                                {*                                    <td style="text-align: right;"></td>*}
                            </tr>
                        {/if}
                        {foreachelse}
                        <tr>
                            <td class="list-empty" colspan="6">
                                <div class="list-empty-msg">
                                    <i class="icon-warning-sign list-empty-icon"></i>
                                    Ningún registro encontrado
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                {/if}

                {assign var='suma_egresos' value = 0}
                {if count($egresos)}
                    <tr class="info" style="background-color: #08ca98; color: #fff; font-size: 9px;">
                        <td style="text-align: center;" colspan="6">
                            <strong>
                                EGRESOS
                            </strong>
                        </td>
                    </tr>
                    {assign var='total' value=0}
                    {assign var='nro_operaciones' value=0}
                    {foreach from=$egresos item='detail'}
                        {assign var='suma_egresos' value = $suma_egresos + $detail.monto}
                        {assign var='total' value=$total+$detail.monto}
                        {assign var='nro_operaciones' value=$nro_operaciones+1}
                        <tr >
                            <td style="text-align: left;">{$detail.fecha|date_format:"%d/%m/%Y %I:%M %p"}</td>
                            <td style="text-align: left;">{$detail.descripcion}</td>
                            <td style="text-align: center;">- -</td>
                            <td style="text-align: center;">-{displayPrice currency=1 price=$detail.monto|round:2}</td>
                            <td style="text-align: center;">- -</td>
                        </tr>
                    {/foreach}

                    <tr class="warning">
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;">Totales</td>
                        <td style="text-align: center;">-{displayPrice currency=$datos_fila.id_currency price=$total|round:2}</td>
                        <td style="text-align: center;"> - - </td>
                        <td style="text-align: center;"> - - </td>
                        {*                                    <td style="text-align: right;"></td>*}
                    </tr>

                {/if}

                {assign var='suma_adelantos' value = 0}
                {if count($adelantos)}
                    <tr class="info" style="background-color: #08ca98; color: #fff; font-size: 9px;">
                        <td style="text-align: center;" colspan="6">
                            <strong>
                                ADELANTOS
                            </strong>
                        </td>
                    </tr>
                    {assign var='total' value=0}
                    {assign var='nro_operaciones' value=0}
                    {foreach from=$adelantos item='detail'}
                        {assign var='suma_adelantos' value = $suma_adelantos + $detail.adelanto}
                        {assign var='total' value=$total+$detail.adelanto}
                        {assign var='nro_operaciones' value=$nro_operaciones+1}
                        <tr >
                            <td style="text-align: left;">{$detail.fecha_inicio|date_format:"%d/%m/%Y %I:%M %p"}</td>
                            <td style="text-align: left;">{$detail.product_name}</td>
                            <td style="text-align: center;">- -</td>
                            <td style="text-align: center;">-{displayPrice currency=1 price=$detail.adelanto|round:2}</td>
                            <td style="text-align: center;">- -</td>
                        </tr>
                    {/foreach}

                    <tr class="warning">
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;">Totales</td>
                        <td style="text-align: center;">-{displayPrice currency=$datos_fila.id_currency price=$total|round:2}</td>
                        <td style="text-align: center;"> - - </td>
                        <td style="text-align: center;"> - - </td>
                        {*                                    <td style="text-align: right;"></td>*}
                    </tr>

                {/if}

                <tr class="warning">
                    <td style="text-align: right;" colspan="6">Total Efectivo: {displayPrice currency=1 price=$suma_efectivo|round:2}</td>
                </tr>
                <tr class="warning">
                    <td style="text-align: right;" colspan="6">Total Visa: {displayPrice currency=1 price=$suma_visa|round:2}</td>
                </tr>
                <tr class="warning">
                    <td style="text-align: right;" colspan="6">Total Izipay: {displayPrice currency=1 price=$suma_izipay|round:2}</td>
                </tr>
                <tr class="warning">
                    <td style="text-align: right;" colspan="6">Total Por Cobrar: {displayPrice currency=1 price=$suma_porcobrar|round:2}</td>
                </tr>
                <tr class="warning">
                    <td style="text-align: right;"  colspan="6">Total Egresos: -{displayPrice currency=1 price=$suma_egresos|round:2}</td>
                </tr>
                <tr class="warning">
                    <td style="text-align: right;"  colspan="6">Total Adelantos: {displayPrice currency=1 price=$suma_adelantos|round:2}</td>
                </tr>
                <tr class="warning">
                    <td style="text-align: right; font-size: 1.75em;"  colspan="6">Saldo en Caja: {displayPrice currency=1 price=($suma_adelantos + $suma_efectivo + $operacion_caja->monto_apertura) - $suma_egresos|round:2}</td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>

</table>
</body>
</html>
