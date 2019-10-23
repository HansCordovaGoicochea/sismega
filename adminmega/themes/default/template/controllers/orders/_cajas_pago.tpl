<style>
    #cajas_pago {
        width: auto!important;
        display: none;
        padding: 15px;
    }

    #cajas_pago sup {
        color: red;
        font-weight: bold;
        top: -.5em;
        position: relative;
        font-size: 75%;
        line-height: 0;
        vertical-align: baseline;
    }

    #cajas_pago label {
        font-size: 14px;
        display: block;
        padding-bottom: 5px;
        text-align: left;
        /*display: inline-block;*/
        max-width: 100%;
        margin-bottom: 5px;
        font-weight: 700;
    }


    .bonorder_send.button.button-small {
        padding: 7px 12px;
    }

    /*ss*/

    .yo_fieldset {
        min-width: 0;
        padding: 0;
        margin: 0;
        border: 0;

        display: block;
        margin-inline-end: 2px;
        padding-inline-end: 2.75em;
    }

    #id_caja {
        display: block;
        width: 100%;
        padding: 6px;
        font-size: 17px!important;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;
        -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
        box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
        -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
        -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
        transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;

        margin: 0;
    }

    .bonorder_send {
        display: inline-block;
        padding: 6px 12px;
        margin-bottom: 0;
        font-size: 15px!important;
        font-weight: 400;
        line-height: 1.42857143;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        -ms-touch-action: manipulation;
        touch-action: manipulation;
        cursor: pointer;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        border-radius: 4px;
        color: #333;
        background: #fff none;
        border: 1px solid #ccc;
        -webkit-appearance: button;
        cursor: pointer;
        align-items: flex-start;
    }

    .bonorder_send:hover {
        color: #333;
        background-color: #e6e6e6;
        border-color: #adadad;
    }

    .bonorder_form {
        display: block;
        margin-top: 0em;
    }

</style>

<section id="cajas_pago" style="width: 200px">
    <form method="post" class="bonorder_form" action="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}">
        <input type="hidden" name="ajax" value="1">
        <input type="hidden" name="token" value="{getAdminToken tab='AdminOrders'}">
        <input type="hidden" name="tab" value="AdminOrders">
        <input type="hidden" name="action" value="paymentOrderAche">
        <input type="hidden" name="id_order" id="id_order" value="">
        <fieldset class="yo_fieldset">
            <div class="clearfix">
                <div class="form-group bon_order_box" style="margin-bottom: 15px;">
                    <label for="txtTipoCambio">{l s='Caja para pago' mod='bonorder'}: <sup>*</sup></label>
                    <select name="id_caja" id="id_caja">
                        {assign var="cajas" value=PosArqueoscaja::cajasAbiertasJoinEmpleado()}
                        {foreach from=$cajas item=caja}
                            <option data-montoinicial="{$caja.monto_operaciones}" value="{$caja.id_pos_arqueoscaja}">Caja de {$caja.empleado}</option>
                        {/foreach}
                    </select>
                </div>
                <p class="bon_order_errors_phone alert alert-danger">
                    {l s='Seleccione Caja.' mod='bonorder'}
                </p>
            </div>
            <div class="submit">
                <input type="submit" class="btn btn-default button button-small bonorder_send" value="{l s='Pagar' mod='bonorder'}"/>
            </div>
        </fieldset>
    </form>
</section>