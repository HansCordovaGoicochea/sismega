<script type="text/javascript">
    $(document).ready(function(){
        $('.datepicker').datepicker({
            prevText: '',
            nextText: '',
            dateFormat: 'dd/mm/yy',
        });
    });
    function pulsarcalendatio(objeto){
        $('#'+objeto).focus();
    }
</script>

<form enctype="multipart/form-data" action="{$link->getAdminLink('AdminReporteManifiesto')|escape:'html':'UTF-8'}" id='formTicketajax' method="post" class="form-inline">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Filtro del Reporte:</h4>
        </div>
        <div class="panel-body ">
            <div class="" >
                <div class='row ' >
                    <div class="form-group">
                        <label for="inputName" class="control-label">Fecha:</label>

                        <div class="input-group ">
                            <input name="fecha_inicio" data-hex="true" value="{Tools::getFormatFechaPresentar($fecha_inicio)}" class="datepicker" type="text" id = "fecha_inicio" />
                            <div class="input-group-addon" onclick="pulsarcalendatio('fecha_inicio');">
                                <i class="icon-calendar-empty" ></i>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputName" class="control-label">Tours:</label>

                        <div class="input-group">
                            <span class="input-group-addon" id="helpId">
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="nombre_producto" id="nombre_producto" value="{$nombre_producto}">
                            <input type="text" class="form-control" placeholder="Buscar producto" name="id_product" id="id_product" value="{$id_product}" >
                        </div>

                    </div>

                    <button type="submit" id="filtrar" class="btn btn-default" name="filtrar" value="Filtrar">Filtrar</button>
                    <button class="btn btn-warning" onclick="printTicket(); return false;"><i class="fa fa-file-pdf-o"></i>&nbsp;EXPORTAR PDF</button>

                </div>
            </div>
        </div>
    </div>
</form>
<div class="panel col-sx-12">

        <div id="datos_pdf">
            {if $nombre_producto != ""}
            <h3 class="text-center" style="text-align: center!important;">Lista de pax - Tours {$nombre_producto}</h3>
            <hr>
            <table class="table" width="100%">
                <thead>
                <tr>
                    <th class="text-center" style="text-align: left!important;">Cliente</th>
                    <th class="text-center" style="text-align: left!important;">Dirección</th>
                    <th class="text-center" style="text-align: center!important;">Celular</th>
                    <th class="text-center" style="text-align: center!important;">Cantidad</th>
                    <th class="text-center" style="text-align: center!important;">Importe</th>
                </tr>
                </thead>
                <tbody>
                    {assign var="cant" value= 0}
                    {assign var="sum" value= 0}
                    {foreach $order_detail as $item}
                        <tr>
                            <td class="text-center" style="text-align: left!important;">{$item.cliente} ({$item.num_document})</td>
                            <td class="text-center" style="text-align: left!important;">{$item.direccion}</td>
                            <td class="text-center" style="text-align: center!important;">{$item.telefono_celular}</td>
                            <td class="text-center" style="text-align: center!important;">{$item.product_quantity}</td>
                            <td class="text-center" style="text-align: center!important;">{displayPrice currency=1 price=$item.total_price_tax_incl|round:2}</td>
                        </tr>
                        {assign var="cant" value= $cant + $item.product_quantity}
                        {assign var="sum" value= $sum + $item.total_price_tax_incl}
                    {/foreach}
                </tbody>
                <tfood>
                    <tr>
                        <td colspan="3" class="text-right" style="text-align: right!important;">Total: </td>
                        <td class="text-center" style="text-align: center!important;">{$cant}</td>
                        <td class="text-center" style="text-align: center!important;">{displayPrice currency=1 price=$sum|round:2}</td>
                    </tr>
                </tfood>
            </table>
            {/if}
        </div>

</div>
<script>
    function printTicket(){

        printJS({
            printable: 'datos_pdf',
            type: 'html',
            scanStyles: false
        })
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
            // '<div class="select2-result-repository__forks"><i class="fa fa-list-ol"></i> </div>' +
            // '<div class="select2-result-repository__stargazers"><i class="fa fa-money"></i></div>' +
            '</div>' +
            "</div>" +
            "</div>"
        );

        $container.find(".select2-result-repository__title").text(repo.name);
        // $container.find(".select2-result-repository__description").text(repo.reference);
        // $container.find(".select2-result-repository__forks").append("&nbsp;Stock "+ repo.quantity);

        // $container.find(".select2-result-repository__stargazers").append("&nbsp;Precio "+ repo.formatted_price);
        // $container.find(".select2-result-repository__watchers").append(repo.watchers_count + " Watchers");

        return $container;
    }

    function productFormatSelection(repo) {
        // console.log(repo);
        return repo.name || repo.text;
    }


    $('#id_product').select2({
        placeholder: 'Buscar producto',
        minimumInputLength: 2,
        width: '400px',
        dropdownCssClass: "bootstrap",
        initSelection: function (element, callback) {
            callback({ id: '{$id_product}', text: '{$nombre_producto}' });
        },
        ajax: {
            url: "ajax_products_list.php",
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
            // console.log(e.object);
            if (e.object)
            {
                // Keep product variable
                current_product = e.object;
                $('#id_product').val(current_product.id_product);
                $('#nombre_producto').val(current_product.name);
            }

        });
</script>
