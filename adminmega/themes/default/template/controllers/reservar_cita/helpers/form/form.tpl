
<style>
    .sunat-button {
        text-transform: uppercase!important;
        font-weight: 600!important;
        background-color: #0264af!important;
        border-color: #0264af!important;
        color: #fff!important;
    }

    .container {
        width: 100%;
        font-family: "Arial";
    }

    .progressbar {
        counter-reset: step;
    }
    .progressbar li {
        list-style: none;
        display: inline-block;
        width: 30.33%;
        position: relative;
        text-align: center;
        cursor: pointer;
    }
    .progressbar li:before {
        /*content: counter(step);
        counter-increment: step;*/
        content: "";
        width: 30px;
        height: 30px;
        line-height : 30px;
        border: 1px solid #E9E9E9;
        border-radius: 100%;
        display: block;
        text-align: center;
        margin: 0 auto 10px auto;
        color: white;
        background-color: #E9E9E9;

    }
    .progressbar li:after {
        content: "";
        position: absolute;
        width: 100%;
        height: 4px;
        background-color: #E9E9E9;
        top: 14px;
        left: -50%;
        z-index : -1;
    }
    .progressbar li:first-child:after {
        content: none;
    }
    .progressbar li.active {
        color: #1181F2;
    }
    .progressbar li.active:before {
        font-family: FontAwesome, serif;
        border-color: #1181F2;
        background-color: #1181F2;
        animation: pulse 2s infinite;
        content: '\f00c';

    }
    .progressbar li.active + li:after {
        background-color: #1181F2;
        background: linear-gradient(to right, #1181F2 50%, #E9E9E9 50%);
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(33,131,221, 0.4);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(33,131,221, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(33,131,221, 0);
        }
    }
</style>
{*<div class="container">*}
{*    <ul class="progressbar">*}
{*        <li class="active">Pendiente</li>*}
{*        <li>Atendido</li>*}
{*        <li>Facturado</li>*}
{*    </ul>*}
{*</div>*}


<div class="row" id="form_div_cita">
    <input type="hidden" id="id_reservar_cita" name="id_reservar_cita" value="{$cita->id}">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-table"></i>&nbsp;Cita
{*            {if $cita->id && $nombre_access != 'Colaborador' && $nombre_access != 'Recepcionista' && $existeCajasAbiertas && $cita->estado_actual == 0}*}
            {if $cita->id && $nombre_access != 'Recepcionista'}
                <a class="btn badge pull-right" style="{if $cita->id_order} display: none; {/if} background-color: #72c279; color: #fff" id="pasarVenta">
                    <i class="icon-money"></i>  Atender
                </a>
            {/if}
            {if $cita->id_order}
{*            {else}*}
                <a class="btn badge pull-right" style="{if !$cita->id && !$cita->id_order} display: none; {/if} background-color: #72c279; color: #fff" target="_blank" href="{strip}{if $smarty.server['HTTPS']=='on'}https://{else}http://{/if}{$smarty.server.HTTP_HOST}{$smarty.server.BASE}/index.php?controller=AdminPdf&token={getAdminToken tab='AdminPdf'}{/strip}&submitAction=generateInvoicePDF&id_ventarapida={$cita->id_order|intval}&documento=ticket" id="imprimir_ticket">
                    <i class="icon-print"></i>
                    {l s='Ticket' d='Admin.Orderscustomers.Feature'}
                </a>
            {/if}
        </div>
        <div  {if $cita->id_order}style="pointer-events: none"{/if} class="panel-body">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <div class="form-group col-lg-6 ">
                        <label for="id_colaborador" class="control-label">{l s='Colaborador:' d='Admin.Orderscustomers.Feature'}</label>
                        <select name="id_colaborador" id="id_colaborador" class="chosen">
                            <option value="">{l s='-- Elija un Colaborador --' d='Admin.Actions'}</option>
                            {foreach $colaboradores as $employee}
                                <option value="{$employee.id_employee}" {if $cita->id_colaborador == $employee.id_employee}selected{/if}> {$employee.firstname} {$employee.lastname}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="fecha_inicio" class="control-label required">Fecha y Hora:</label>
                        <div class="input-group" id="timepicker">
                            <input type="text" class="form-control datetimepicker" id="fecha_inicio" name="fecha_inicio" autocomplete="off" value="{$cita->fecha_inicio|date_format:"%d/%m/%Y %l:%M %p"}">
                            <span class="input-group-append input-group-addon">
                                <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <input type="hidden" id="product_id" name="product_id" value="{$cita->product_id}"/>
                            <label for="product_id" class="control-label required">Servicio:</label>

                                <input type="text" id="product_select2" name="product_select2" value="{$cita->product_name}" autocomplete="off" placeholder="Buscar servicio"/>
                                <input type="hidden" id="product_name" name="product_name" value="{$cita->product_name}" autocomplete="off" placeholder="Buscar servicio"/>

                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="product_id" class="control-label required">Precio:</label>
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="icon-money"></i>
                                </div>
                                <input type="text" id="precio" name="precio" value="{$cita->precio}" autocomplete="off" readonly/>
                            </div>

                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="product_id" class="control-label">Adelanto:</label>
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="icon-money"></i>
                                </div>
                                <input type="text" id="adelanto" name="adelanto" value="{$cita->adelanto}" autocomplete="off"/>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="product_id" class="control-label">Observación:</label>
                            <textarea name="observacion" id="observacion" rows="2">{$cita->observacion}</textarea>
                        </div>
                    </div>

                    <div class="form-group col-lg-4 hide">
                        <label for="tipo_doc">Estado Actual:</label>
                        <select name="estado_actual" id="estado_actual" class="chosen">
                            <option value="0" {if $cita->estado_actual == 0}selected{/if}>Pendiente</option>
                            <option value="1" {if $cita->estado_actual == 1}selected{/if}>Atendido</option>
                            <option value="2" {if $cita->estado_actual == 2}selected{/if}>Cancelado</option>
                            <option value="3" {if $cita->estado_actual == 3}selected{/if}>Facturado</option>
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6" id="div_datos_cliente">

                    <input type="hidden" class="input_ache" name="id_customer" id="id_customer" value="{$cita->id_customer}">
                    <div class="form-group col-lg-4">
                        <label for="tipo_doc" class="control-label required">Tipo Doc.:</label>
                        <select name="cb_tipo_documento" id="cb_tipo_documento" class="form-control">
                            {foreach $tipo_documentos as $doc}
                                <option value="{$doc['id_tipodocumentolegal']}" data-codsunat="{$doc['cod_sunat']}" {if $customer->id_document == $doc['id_tipodocumentolegal']}selected{/if}>- {$doc['nombre']} -</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group col-lg-4">
                        <label for="nro_doc" class="control-label required">N° Doc:</label>
                        <div class="row">
                            <div class="col-lg-9 col-xs-9">
                                <input type="text" class="form-control" id="txtNumeroDocumento" name="txtNumeroDocumento" placeholder="Número de documento" maxlength="8" onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57" value="{$customer->num_document}">
                            </div>
                            <div class="col-lg-2 col-xs-2">
                                <button type="button" class="btn btn-default sunat-button" onclick="traerDatosSunat()" id="buscar_sunat">
                                    &nbsp;<i class="icon-search" style="color: #fff"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="txtNombre" class="control-label required">Cliente:</label>
                        <input type="text" class="form-control" id="txtNombre" name="txtNombre" value="{$customer->firstname}">
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="txtDireccion" class="control-label">Dirección:</label>
                        <input type="text" class="form-control" id="txtDireccion" name="txtDireccion" value="{$customer->direccion}">
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="birthday" class="control-label">Fecha Nacimiento:</label>
                        <input type="text" class="form-control datepicker" id="birthday" name="birthday" value="{if $customer->birthday && $customer->birthday != '0000-00-00'}{$customer->birthday|date_format:"%d/%m/%Y"}{/if}">
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="celular" class="control-label">Celular:</label>
                        <input type="text" class="form-control" id="celular" name="celular" value="{$customer->telefono_celular}">
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button {if $cita->id_order}style=" display: none; "{/if} type="submit" value="1" id="cita_guardar_btn" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Guardar
            </button>
            <a class="btn btn-default" onclick="window.history.back();">
                <i class="process-icon-cancel"></i> Cancelar
            </a>
            <a class="btn btn-default" href="{$link->getAdminLink('AdminReservarCita')|addslashes}">
                <i class="process-icon-back"></i> Lista
            </a>
        </div>
    </div>
</div>
<div id="overlay"></div>
{if $cita->estado_actual == 2}
    <style>
        #overlay:after {
            content: "ANULADO";
            font-size: 15em;
            color: rgba(52, 166, 214, 0.17);
            z-index: 9999;
            transform: rotate(-20deg);

            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;

            -webkit-pointer-events: none;
            -moz-pointer-events: none;
            -ms-pointer-events: none;
            -o-pointer-events: none;
            pointer-events: none;

            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            -o-user-select: none;
            user-select: none;
        }
        #overlay {
            background-color: rgba(0, 0, 0, 0.01);
            z-index: 123;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
        }​

    </style>
{/if}
<script>
    const url_ajax_cita = "{$link->getAdminLink('AdminReservarCita')|addslashes}";

    $(function () {
        $('.select2-arrow').append('<i class="fa fa-angle-down"></i>');
    });

    $('#pasarVenta').click(function () {
        var x = confirm("¿Seguro de crear la venta?");
        if (x){
            if ($('#id_colaborador :selected').val() !== ""){
                $.ajax({
                    type:"POST",
                    url: "{$link->getAdminLink('AdminReservarCita')|escape:'html':'UTF-8'}",
                    async: true,
                    dataType: "json",
                    data:{
                        ajax: "1",
                        token: "{getAdminToken tab='AdminReservarCita'}",
                        action : "realizarVenta",
                        id_reservar_cita: '{$cita->id|intval}',
                        id_colaborador: $('#id_colaborador :selected').val(),
                    },
                    beforeSend: function(){
                        $('body').waitMe({
                            effect: 'bounce',
                            text: 'Guardando...',
                            color: '#000',
                            maxSize: '',
                            textPos: 'vertical',
                            fontSize: '',
                            source: ''
                        });
                    },
                    success: function (data) {
                        if (data.response === 'ok'){
                            {*window.location.href = "{$link->getAdminLink('AdminOrders')|escape:'UTF-8'}&id_order=" + data.order.id + "&vieworder";*}
                            window.location.href = "{$link->getAdminLink('AdminReservarCita')|escape:'UTF-8'}&updatereservar_cita&id_reservar_cita="+ data.objCita.id;
                            $('body').waitMe('hide');
                        }
                        if (data.response === 'failed'){
                            $('#error').text(data.msg);
                            $('#error').show();
                        }
                    },
                    error: function (error) {
                        console.log(error);
                    },
                    complete: function(data) {

                    },
                });
            }else{
                jAlert('Debe Seleccionar un colaborador')
            }
        }else{
            return false;
        }

    })

    $('#cita_guardar_btn').click(function () {
        if (
            // $('#id_colaborador :selected').val() !== "" &&
            $('#fecha_inicio').val() !== "" &&
            $('#product_id').val() !== "" &&
            $.trim($('#product_name').val()) !== "" &&
            $.trim($('#txtNumeroDocumento').val()) !== "" &&
            $.trim($('#txtNombre').val()) !== ""
        ){
            $.ajax({
                type: "POST",
                url: "{$link->getAdminLink('AdminReservarCita')|addslashes}",
                async: true,
                dataType: "json",
                data: {
                    ajax: "1",
                    token: "{getAdminToken tab='AdminReservarCita'}",
                    tab: "AdminReservarCita",
                    action: "guardarCita",
                    // data: $('#form_div_cita').serialize() + "&moredata=" + morevalue
                    data: $('#form_div_cita').find("select, textarea, input").serialize()
                },
                beforeSend: function () {
                    $('body').waitMe({
                        effect: 'timer',
                        text: 'Guardando...',
                        color: '#000',
                        maxSize: '',
                        textPos: 'vertical',
                        fontSize: '',
                        source: ''
                    });
                },
                success: function (res) {
                    if (res.respuesta === 'ok'){
                        $('#id_reservar_cita').val(res.cita.id);
                        $.growl.notice({ title: "", message:"Guardado Correctamente"});
                        window.location.href = "{$link->getAdminLink('AdminReservarCita')|addslashes}";
                        {*window.location.href = "{$link->getAdminLink('AdminReservarCita')|addslashes}&updatereservar_cita&id_reservar_cita="+res.cita.id;*}
                    }else{
                        $.growl.error({ title: "", message:"Error al guardar"});
                    }
                },
                complete: function (res) {
                    $('body').waitMe('hide');
                }
            });
        }else{
            jAlert("Faltan datos para crear la cita");
        }



    });

    function limitText(field, maxChar){
        $(field).attr('maxlength',maxChar);
    }

    $("#cb_tipo_documento").change(function (e) {
        var $this = $(this);
        e.preventDefault();
        //$this.button('loading');
        var value = parseInt($(this).find(':selected').data("codsunat"));
        $('#txtNumeroDocumento').val("");
        $('#txtNombre').val("");
        $('#txtDireccion').val("");
        // alert(value);
        if (value === 1) {
            limitText('#txtNumeroDocumento', 8);
        } else if(value === 4) {
            limitText('#txtNumeroDocumento', 12);
        }else if(value === 6) {
            limitText('#txtNumeroDocumento', 11);
        }
    });

    function traerDatosSunat() {
        // alert("buscar")
        $('.input_ache').remove();
        var data_cod_sunat = $("#cb_tipo_documento").find(':selected').data('codsunat');
        $.ajax({
            type: "POST",
            url: "{$link->getAdminLink('AdminCustomers')|addslashes}",
            async: true,
            dataType: "json",
            data: {
                ajax: "1",
                token: "{getAdminToken tab='AdminCustomers'}",
                tab: "AdminCustomers",
                action: "getDataDataBase",
                nruc: $.trim($("#txtNumeroDocumento").val()),
            },
            beforeSend: function () {
                $('body').waitMe({
                    effect: 'timer',
                    text: 'Consultando...',
                    color: '#000',
                    maxSize: '',
                    textPos: 'vertical',
                    fontSize: '',
                    source: ''
                });
            },
        })
            .done(function (data, textStatus, jqXHR) {

                if (data['success'] != "false" && data['success'] != false) {
                    // $("#json_code").text(JSON.stringify(data, null, '\t'));
                    if (typeof (data['result']) != 'undefined') {
                        if(!$(".input_ache").length) {
                            $('#div_datos_cliente').append('<input type="hidden" class="input_ache" name="id_customer" id="id_customer" value="'+data.result.id_customer+'">');
                        }
                        $('#txtNombre').val(data.result.firstname);
                        $('#txtDireccion').val(data.result.direccion);
                        $('#celular').val(data.result.telefono_celular);
                        if (data.result.birthday !== '0000-00-00')
                            $('#birthday').val(data.result.birthday.split('-').reverse().join('/'));

                    }

                    $('body').waitMe('hide');
                } else {

                    $('#txtNombre').val("");
                    $('#txtDireccion').val("");
                    $('#celular').val("");

                    $.ajax({
                        type: "POST",
                        url: "{$link->getAdminLink('AdminCustomers')|addslashes}",
                        async: true,
                        dataType: "json",
                        data: {
                            ajax: "1",
                            token: "{getAdminToken tab='AdminCustomers'}",
                            tab: "AdminCustomers",
                            action: "getDataSunat",
                            nruc: $.trim(($('#txtNumeroDocumento').val()))
                        },
                        beforeSend: function () {
                            $('body').waitMe({
                                effect: 'timer',
                                text: 'Consultando...',
                                color: '#000',
                                maxSize: '',
                                textPos: 'vertical',
                                fontSize: '',
                                source: ''
                            });
                        },
                    })
                        .done(function (data, textStatus, jqXHR) {
                            if (data['success'] != "false" && data['success'] != false) {
                                // $("#json_code").text(JSON.stringify(data, null, '\t'));
                                if (typeof (data['result']) != 'undefined') {
                                    if(!$(".input_ache").length) {
                                        $('#div_datos_cliente').append('<input type="hidden" class="input_ache" name="id_customer" id="id_customer" value="'+data.cliente.id+'">');
                                    }
                                    $('#txtNombre').val(data.result.RazonSocial);
                                    $('#txtDireccion').val(data.result.Direccion.replace(new RegExp('-', 'g'), ""));
                                }

                                $('body').waitMe('hide');
                            } else {
                                if (typeof (data['msg']) != 'undefined') {
                                    alert(data['msg']);
                                }
                                $('#txtNombre').val("");
                                $('#txtDireccion').val("");
                                $('#celular').val("");

                                $('body').waitMe('hide');
                            }
                        })
                        .fail(function (jqXHR, textStatus, errorThrown) {
                            alert("Solicitud fallida:" + textStatus);
                            $('body').waitMe('hide');
                        });
                    //$this.button('reset');
                }

            }).fail(function (jqXHR, textStatus, errorThrown) {
            alert("Solicitud fallida:" + textStatus);
            $('body').waitMe('hide');
        });
    }

    function productFormatResult(repo) {
        if (repo.loading) {
            return repo.text;
        }

        var $container = $(
            "<div class='select2-result-repository clearfix'>" +
            // "<div class='select2-result-repository__avatar'><img src='' /></div>" +
            "<div class='select2-result-repository__meta'>" +
            "<div class='select2-result-repository__title'></div>" +
            "<div class='select2-result-repository__description'></div>" +
            '<div class="select2-result-repository__statistics">' +
            '<div class="select2-result-repository__forks"><i class="fa fa-list-ol"></i> </div>' +
            '<div class="select2-result-repository__stargazers"><i class="fa fa-money"></i></div>' +
            '</div>' +
            "</div>" +
            "</div>"
        );

        $container.find(".select2-result-repository__title").text(repo.name);
        // $container.find(".select2-result-repository__description").text(repo.reference);

        if (parseInt(repo.is_virtual) === 1){
            // $container.find(".select2-result-repository__forks").remove();
            $container.find(".select2-result-repository__forks").html("SERVICIO");
        }else{
            $container.find(".select2-result-repository__forks").append("&nbsp;Stock "+ repo.quantity);

        }

        $container.find(".select2-result-repository__stargazers").append("&nbsp;Precio "+ repo.formatted_price);
        // $container.find(".select2-result-repository__watchers").append(repo.watchers_count + " Watchers");

        return $container;
    }
    function productFormatSelection(repo) {
        // console.log(repo);
        return repo.name || repo.text;
    }

    $('#product_select2').select2({
        placeholder: "Buscar Servicio",
        minimumInputLength: 3,
        width: '100%',
        dropdownCssClass: "bootstrap",
        initSelection: function (element, callback) {
            callback({ id: '{$cita->product_id}', text: '{$cita->product_name}' });
        },
        ajax: {
            url: "ajax_servicios_list.php",
            dataType: 'json',
            data: function (term) {
                return {
                    q: term
                };
            },
            results: function (data) {
                var returnIds = new Array();
                if (data) {
                    for (var i = data.length - 1; i >= 0; i--) {
                        returnIds.push(data[i]);
                    }
                    return {
                        results: returnIds
                    }
                } else {
                    return {
                        results: []
                    }
                }
            }
        },
        formatResult: productFormatResult,
        formatSelection: productFormatSelection,
    })
        .on("select2-selecting", function(e) {
            // selectedProduct = e.object
            console.log(e.object);
            if (e.object)
            {
                // Keep product variable
                current_product = e.object;
                $('#product_id').val(current_product.id_product);
                $('#product_name').val(current_product.name);
                $('#precio').val(current_product.price_tax_incl);
            }

        });



    // $("#product_name").autocomplete(url_ajax_cita,
    //     {
    //         minChars: 1,
    //         max: 10,
    //         // width: 100%,
    //         selectFirst: true,
    //         scroll: false,
    //         dataType: "json",
    //         highlightItem: true,
    //         formatItem: function(data, i, max, value, term) {
    //             return value;
    //             // return '<table><tr><td valign="top">' + value + '</td><td valign="top"> 123 </td></tr></table>';
    //         },
    //         parse: function(data) {
    //             var products = new Array();
    //             if (typeof(data.products) != 'undefined')
    //                 for (var i = 0; i < data.products.length; i++)
    //                     products[i] = { data: data.products[i], value: data.products[i].name };
    //             return products;
    //         },
    //         extraParams: {
    //             ajax: true,
    //             token: token,
    //             action: 'getProductByName',
    //             product_search: function() { return $('#product_name').val(); }
    //         }
    //     }
    // )
    //     .result(function(event, data, formatted) {
    //         if (!data)
    //         {
    //
    //         }
    //         else
    //         {
    //             // Keep product variable
    //             current_product = data;
    //             $('#product_id').val(data.id_product);
    //             $('#product_name').val(data.name);
    //         }
    //     });


    $('.datetimepicker').datetimepicker({
        prevText: '',
        nextText: '',

        // dateFormat: 'yy-mm-dd',
        dateFormat: 'dd/mm/yy',
        // Define a custom regional settings in order to use PrestaShop translation tools
        currentText: '{l s='Now' js=1}',
        closeText: '{l s='Done' js=1}',
        showSecond: false,
        ampm: true,
        amNames: ['AM', 'A'],
        pmNames: ['PM', 'P'],
        // timeFormat: 'HH:mm:ss',
        timeFormat: 'hh:mm tt', //24 horas
        timeSuffix: '',
        timeOnlyTitle: '{l s='Choose Time' js=1}',
        timeText: '{l s='Hora:' js=1}',
        hourText: '{l s='Hour' js=1}',
        minuteText: '{l s='Minute' js=1}',

    });

    $('.datepicker').datepicker({
        prevText: '',
        nextText: '',
        // dateFormat: 'yy-mm-dd',
        dateFormat: 'dd/mm/yy',
        changeYear: true,
        changeMonth: true,
        yearRange: "-100:+0", // last hundred years
    });

    $(".chosen").chosen({
        placeholder_text_multiple: "Seleccione Colaborador...",
        no_results_text: "Vaya, no se ha encontrado nada!",
        width: "100%"
    });

</script>