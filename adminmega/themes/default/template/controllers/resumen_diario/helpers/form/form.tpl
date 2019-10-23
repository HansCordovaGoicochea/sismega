{block name="other_input"}
    <script>
        $(function () {
            //			date picker de cajas
            var currentDate = new Date();
            $("#fecha_generacion").datepicker({
                dateFormat: 'dd/mm/yy',
                changeYear: true
            });
//            $("#fecha_factura").datepicker("setDate", currentDate);

            $("#calendar").on('click',function () {
                $('#fecha_generacion').datepicker('show');
            });

            $("#fecha_comprobantes").datepicker({
                dateFormat: 'dd/mm/yy',
                changeYear: true
            });
//            $("#fecha_vencimiento").datepicker("setDate", sumarDias(currentDate, 7));

            $("#calendar_vencimiento").on('click',function () {
                $('#fecha_comprobantes').datepicker('show');
            });
        })

    </script>
	{if $nro_tiendas == 1}
        <div id="errores"></div>
<div class="oe_clear ui-tabs ui-widget ui-widget-content ui-corner-all">
	<div class="panel">
		<div class="row">
            <form enctype="multipart/form-data" action="{$link->getAdminLink('AdminResumenDiario')|escape:'html':'UTF-8'}" name="frm" id="frm" method="post">
                <input type="text" id="txt_id_resumen_diario" name="txt_id_resumen_diario" class="form-control" value="{$objResumenDiario->id}" style="display:none;">
			<!--datos de la factura-->
			<div class="row">
                <div class="col-lg-6 col-xs-12 datos_1">
					<div class="panel panel-default">
						{*<div class="panel-heading">*}
							{*<h4>Datos Del Cliente</h4>*}
						{*</div>*}
						<div class="panel-body">
							<div class="row" style="margin-bottom: 10px;">
								<div class="form-group">
                                    <label for="txt_identificador" class="control-label col-lg-2 col-xs-12"><span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="">Identificador:</span></label>
									<div class="col-xs-10">
										{*RC-{$smarty.now|date_format:'%Y%m%d'}-{"%05d"|sprintf:($correlativo_resumen[0]['correlativo']+1)}*}
										<span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="" id="span_identificador">{$objResumenDiario->identificador_resumen_diario}</span>
                                        {*{$correlativo_resumen[0]['correlativo']+1}*}
										<input class="form-control size_md" style="display: none;" type="number" id ="txt_identificador" min="0" name="txt_identificador" value="">
                                        {*{$correlativo_resumen[0]['id_numeracion_comanda']}*}
										<input class="form-control size_md" style="display: none;" type="number" id ="txt_id_numeracion" name="txt_id_numeracion" value="">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="form-group">
									<label for="fecha_generacion" class="control-label col-lg-3 col-xs-12"><span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="Fecha de generación del resumen diario">F. Generación:</span></label>
									<div class="input-group date col-lg-4 col-xs-12" id="calendar">
                                        {if $objResumenDiario->fecha_generacion_resumen_diario == ''}{$new_date_inicio = date('d/m/Y')}
                                        {elseif $objResumenDiario->fecha_generacion_resumen_diario != ''}{$new_date_inicio = getdate()}
                                            {$timesinicio = strtotime($objResumenDiario->fecha_generacion_resumen_diario)}
                                            {$new_date_inicio = date('d/m/Y', $timesinicio)}
                                        {/if}
										<input type="text" name="fecha_generacion" id="fecha_generacion" class="form-control input-md ui-datepicker" value="{$new_date_inicio}">
										<span class="input-group-addon" style="cursor: pointer;">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                        <i class="icon-calendar"></i>
                                       </span>
									</div>
								</div>
							</div>
							<div class="row" >
								<div class="form-group">
									<label for="fecha_comprobantes" class="control-label col-lg-3 col-xs-12"><span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="Fecha de emision de los comprobantes">F. Emisión Comprobantes:</span></label>
									<div class="input-group date col-lg-4 col-xs-12" id="calendar_vencimiento">
                                        {if $objResumenDiario->fecha_emision_comprobantes == ''}{$new_date_inicio = date('d/m/Y')}
                                        {elseif $objResumenDiario->fecha_emision_comprobantes != ''}{$new_date_inicio = getdate()}
                                            {$timesinicio = strtotime($objResumenDiario->fecha_emision_comprobantes)}
                                            {$new_date_inicio = date('d/m/Y', $timesinicio)}
                                        {/if}
										<input type="text" name="fecha_comprobantes" id="fecha_comprobantes" class="form-control input-md ui-datepicker" value="{$new_date_inicio}">
										<span class="input-group-addon" style="cursor: pointer;">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                        <i class="icon-calendar"></i>
                                       </span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-6 col-xs-12 datos_2">
					<div class="panel panel-default">
						<div class="panel-body">
							<div class="row" >
								<div class="form-group">
									<label for="txt_nota" class="control-label col-lg-2 col-xs-12">Descripción/Nota:</label>
									<textarea name="txt_nota" id="txt_nota" cols="8" rows="4">{$objResumenDiario->nota_resumen_diario}</textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
				<!--inicio del detalle-->
			<div class="row">
			    <div class="table-responsive">
				<table class="table table-bordered" id="detalle" width="100%">
					<thead>
					<tr>
						<th>
							<h4></h4>
						</th>
						<th width="10%">
							<h4>#</h4>
						</th>
						<th width="18%">
							<h4>Tipo Comprobante</h4>
						</th>
						<th>
							<h4>Cod. Comp.</h4>
						</th>
						<th width="15%">
							<h4>Comprobante</h4>
						</th>
{*						<th>*}
{*							<h4>SubTotal</h4>*}
{*						</th>*}
{*						<th>*}
{*							<h4>Impuesto</h4>*}
{*						</th>*}
{*						<th>*}
{*							<h4>Total</h4>*}
{*						</th>*}
						<th >
							<h4>Estado</h4>
						</th>
						<th width="15%">
							<h4>Motivo</h4>
						</th>
						<th>
							<h4></h4>
						</th>
					</tr>
					</thead>
					<tbody>
						<tr class="tr" id="trAgregar">
								<td colspan="3"><a id='button_agregar' onclick="addRow();" class="pointer" style="color: #00aff0;">Agregar nuevo Item</a></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
{*								<td></td>*}
{*								<td></td>*}
{*								<td></td>*}
{*								<td></td>*}
{*								<td></td>*}
								<td></td>
						</tr>
					</tbody>
				</table>
                </div>
			</div>
            </form>
		</div>
        <div id="mensaje_ticket"></div>
	</div>
	<div class="panel-footer">

		<a href="{$link->getAdminLink('AdminResumenDiario')|escape:'html':'UTF-8'}" class="btn btn-default" onclick="window.history.back();">
			<i class="process-icon-cancel"></i> Cancelar
		</a>
		<a href="{$link->getAdminLink('AdminResumenDiario')|escape:'html':'UTF-8'}" class="btn btn-default" onclick="window.location.replace(window.history.back()); location.reload();">
			<i class="process-icon-back"></i> Visualizar Lista
		</a>
        {if isset($objResumenDiario->id)}
            <button type="button" id="btnEnviarSunat" name="btnEnviarSunat" class="btn btn-default pull-right"  ONCLICK="consultarCDR();">
                <i class="process-icon-save"></i>Consultar CDR
            </button>
        {/if}
		<button type="button" id="btnProcesar" name="btnProcesar" class="btn btn-default pull-right" {if isset($objResumenDiario->id)} style="display: none;"{/if} onclick="guardarResumen();">
			<i class="process-icon-save"></i>Guardar
		</button>

	</div>
</div>
	{/if}
    {if $nro_tiendas != 1}
		<div class="alert alert-danger"><b>Seleccione una tienda!</b></div>
    {/if}
{/block}
{block name="after"}
	<script>
		$(function () {
            if ($('#txt_id_resumen_diario').val() === '') {
                crearPrimeraFila();
            }else{
                $('.adminresumendiario').waitMe({
                    effect : 'bounce',
                    text : 'Cargando...',
//    bg : rgba(255,255,255,0.7),
                    color : '#000',
                    maxSize : '',
                    textPos : 'vertical',
                    fontSize : '',
                    source : ''
                });

                // TraerDetalleFactura();
                traerDatosDetalleResumen();
            }
        });

        function addRow() {
            var i = $('#detalle tr.count_trs').length;
//        alert(i);
            if (i>0){
                i++;
                FilasSeccion(i,'');
            }

        }


        function checkRow(num) {
            var trs=$("#detalle tr.count_trs").length;
            if(trs>1)
            {
                $('#chk_'+num+'').prop( "checked", true ).trigger('change');
            }
            else{
                $('#cb_productos_'+num+'').val(0);
                $('#txtDescripcion_'+num+'').text('');
                $('#txtCantidad_'+num+'').val(1);
                $('#txtPrecioUnitario_'+num+'').val('0.00');
                $('#cb_impuestos_'+num+'').val(0);
                $('#txtImporte_'+num+'').val('0.00');
                $('#txtImpuesto_'+num+'').val('0.00');
                $('#sub_total').val('0.00');
                $('#monto_impuesto').val('0.00');
                $('#costo_total').val('0.00');
                $.growl.error({ message: "Usted no puede eliminar todas las filas!!" });
            }

        }

        // eliminar Fila de la tabla con campos
        function deleteRow(tableID,num) {
            try {
                var table = document.getElementById(tableID);
                var rowCount = table.rows.length;
                for(var i=0; i<rowCount; i++) {
                    var row = table.rows[i];
                    var chkbox = row.cells[0].childNodes[0];
                    if(chkbox !== null && chkbox.checked === true) {
                        table.deleteRow(i);
                        rowCount--;
                        i--;
                        $.growl.notice({ title: "Fila Eliminada!",message: "" });
                    }
                }

            }catch(e) {
                alert(e);
            }
        }

        function FilasSeccion(i, id_resumen_diario_detalle='',tipo_comprobante='',id_pos_ordercomprobantes=0, estado=3, motivo='', devolver_montos='',subtotal=0,igv=0,total=0) {
//        var i = 1;
//        var row;
// alert(id_producto);
            row =
				'<tr class="count_trs">' +
                '<td style="width:0px;">' +
                '<input type="checkbox" name="chk_'+i+'" id="chk_'+i+'" onchange="deleteRow(\'detalle\', '+i+');" style="display:none;"/>' +
                '</td>' +
                '<td name="line_nro_'+i+'" id="line_nro_'+i+'">'+i+'</td>' +
                '<td>' +
                '<input name="txt_id_resumen_diario_detalle_'+i+'" id="txt_id_resumen_diario_detalle_'+i+'" value="'+id_resumen_diario_detalle+'" style="display:none;"/>' +
                '<div class="col-xs-10">' +
                '<select name="cb_tipo_comprobante_'+i+'" id="cb_tipo_comprobante_'+i+'" class="form-control validarcb" onchange="traerComprobantes('+i+',this);">\n' +
                	'<option value="0">- Seleccione -</option>\n' +
                	'<option value="Boleta">- Boleta -</option>\n' +
                	// '<option value="NotaCredito">- Nota Credito -</option>\n' +
                '</select>\n' +
                '</div>' +
                '</td>' +
                '<td style="width:10px;">' +
                '<input type="text" name="txt_codigo_documento_'+i+'" id="txt_codigo_documento_'+i+'" maxlength="2" class="validar"/>' +
                '</td>' +
                '<td>' +
                '<div >' +
                '<select name="cb_comprobantes_'+i+'" id="cb_comprobantes_'+i+'" class="form-control chosen validarcb">\n' +
                	'<option value="0">- Seleccionar -</option>\n' +
                '</select>\n' +
                '</div>' +
                '</td>' +
                // '<td>' +
                // '<input type="number" name="txt_subtotal_'+i+'" id="txt_subtotal_'+i+'"  class="form-control subtotal validarnum" value="'+subtotal+'">'+
                // '</td>' +
                // '<td>' +
                // '<input type="number" name="txt_igv_'+i+'" id="txt_igv_'+i+'"  class="form-control igv validarnum" value="'+igv+'">'+
                // '</td>' +
                // '<td>' +
                // '<input type="number" name="txt_total_'+i+'" id="txt_total_'+i+'"  class="form-control total validarnum" value="'+total+'">'+
                // '</td>' +
                '<td>' +
                '<select name="cb_estado_comprobante_'+i+'" id="cb_estado_comprobante_'+i+'" class="form-control validarcb">\n' +
                // '<option value="0">- Seleccione -</option>\n' +
                // '<option value="1">- Adicionar -</option>\n' +
                // '<option value="2">- Modificar -</option>\n' +
                '<option value="3">- Anular -</option>\n' +
                '</select>\n' +
                '</td>' +
                '<td style="display: none">' +
                '<select name="cb_devolver_'+i+'" id="cb_devolver_'+i+'" class="form-control validarcb">\n' +
                '<option value="0">- Seleccione -</option>\n' +
                '<option value="devolver">- Devolver montos a Caja -</option>\n' +
                '<option value="nodevolver">- No Devolver montos a Caja -</option>\n' +
                '</select>\n' +
                '</td>' +
                '<td>' +
                '<input type="text" name="txt_motivo_'+i+'" id="txt_motivo_'+i+'"  class="form-control" value="'+motivo+'">'+
                '</td>' +
                '<td>' +
                '<button type="button" value="Borrar" id="btnBorrarSeccion_'+i+'" name="btnBorrarSeccion_'+i+'" class="btn btn-default pull-left" onclick="checkRow('+i+');">' +
                '<i class="icon-trash"></i>' +
                '</button>\n'+
                '</td>' +
                '</tr>';

            $("#trAgregar" ).closest( "tr" ).before(row);
//            $('#dataTable').append(row);

            $("#detalle tr").find('select.chosen').chosen();

            if (tipo_comprobante !== ''){
                $('#cb_tipo_comprobante_'+i+'').val(tipo_comprobante).trigger('change');
            }

            if (id_pos_ordercomprobantes > 0){
                setTimeout(function() {
                    $('#cb_comprobantes_'+i+'').val(id_pos_ordercomprobantes).trigger('change').trigger("chosen:updated");
                    $('.adminresumendiario').waitMe('hide');
                },1500);
            }

            $('#cb_estado_comprobante_'+i+'').val(estado).trigger('change');
            $('#cb_devolver_'+i+'').val(devolver_montos).trigger('change');


        }

        function crearPrimeraFila() {
            var i = 1;
            FilasSeccion(i,'');
        }

        function traerComprobantes(i, val) {
			if(val.value === 'Boleta'){
			    $('#txt_codigo_documento_'+i+'').val('03');
            }
            else if(val.value === 'NotaCredito') {
                $('#txt_codigo_documento_'+i+'').val('07');
            }
            else{
			    alert('¡Elija un comprobante!');
                $('#txt_codigo_documento_'+i+'').val('');
            }

            if (val.value !== '0'){
                $.ajax({
                    type: "POST",
                    url: "{$link->getAdminLink('AdminResumenDiario')|addslashes}",
                    async: true,
                    dataType: "json",
                    data : {
                        ajax: "1",
                        tab: "AdminResumenDiario",
                        action: "traerComprobantes",
                        tipo_comprobante: val.value,
                        fecha_comprobantes: $('#fecha_comprobantes').val(),
                    },
                    success:  function (res) {
// alert(res.Objcomprobantes.length);
                        if (res.Objcomprobantes.length !== 0) {
                            var opt = '';
                            opt = '<option value="0">- Seleccionar -</option>';
                            $.each(res.Objcomprobantes, function (index) {
                                id_pos_ordercomprobantes = this.id_pos_ordercomprobantes;
                                numero_comprobante = this.numero_comprobante;

								opt += '<option value="' + id_pos_ordercomprobantes + '">' + numero_comprobante + '</option>';
                            });

                            $('#cb_comprobantes_'+i+'').html(opt).trigger("chosen:updated");
                        }else{
                           	alert('No Hay Comprobantes para esta fecha!');
							val.value = 0;
							$('#txt_codigo_documento_'+i+'').val('');
                        }

                    }
                }).done(function(){

//                if (id_producto_comb > 0){
//                    $('#cb_combina_'+num+'').val(id_producto_comb).trigger('change');
//                }
                });

            }
        }

        function traerDatos(i, val) {
            $.ajax({
                type: "POST",
                url: "{$link->getAdminLink('AdminResumenDiario')|addslashes}",
                async: true,
                dataType: "json",
                data : {
                    ajax: "1",
                    tab: "AdminResumenDiario",
                    action: "traerDatosComprobante",
                    id_pos_ordercomprobantes: val.value,
                },
                success:  function (res) {
// alert(res.Objcomprobantes.length);

					var igv = parseFloat(res.Objcomprobante.total_paid_tax_incl) - parseFloat(res.Objcomprobante.total_paid_tax_excl);
					$('#txt_subtotal_'+i+'').val(parseFloat(res.Objcomprobante.total_paid_tax_excl).toFixed(2));
					$('#txt_igv_'+i+'').val(igv.toFixed(2));
					$('#txt_total_'+i+'').val(parseFloat(res.Objcomprobante.total_paid_tax_incl).toFixed(2));

                }
            }).done(function(){

//                if (id_producto_comb > 0){
//                    $('#cb_combina_'+num+'').val(id_producto_comb).trigger('change');
//                }
            });
        }

        function traerDatosDetalleResumen() {
            $.ajax({
                type: "POST",
                url: "{$link->getAdminLink('AdminResumenDiario')|addslashes}",
                async: true,
                dataType: "json",
                data : {
                    ajax: "1",
                    tab: "AdminResumenDiario",
                    action: "traerDatosDetalleResumen",
                    id_resumen_diario: $('#txt_id_resumen_diario').val()
                },
                success:  function (res) {
                    var i = $('#detalle').find('tr .count_trs').length;
                    if(res.found) {
                        $.each(res.objDetalle,function (index){
                            i++;
                            var row;
                            id_resumen_diario_detalle = this.id_resumen_diariodetalle;
                            tipo_documento = this.tipo_documento;
                            id_pos_ordercomprobantes = this.id_pos_ordercomprobantes;
                            status_comprobante = this.status_comprobante;
                            motivo = this.motivo;
                            devolver_montos = this.devolver_montos;
                            // id_producto_comb = this.id_producto_combinacion;


                            // alert(id_factura_compras_det);
                            FilasSeccion(i, id_resumen_diario_detalle, tipo_documento, id_pos_ordercomprobantes, status_comprobante, motivo,devolver_montos);

                        });
                    }
                }
            }).done(function(){

            });
        }

        function guardarResumen() {
            if ($.trim($('#fecha_comprobantes').val()) !== '' && $.trim($('#fecha_generacion').val()) !== ''){
                // alert('fdfd');
                // var trs=$("#detalle tr.count_trs").length;
				var campos_vacios = 0;
                $('#detalle tr.count_trs td input.validar').each(function () {
                    var input = $(this).val();
                    if (input === ''){
                        campos_vacios = campos_vacios + 1;
                    }
                });

                $('#detalle tr.count_trs td input.validarnum').each(function () {
                    var input = $(this).val();
                    if (input === '0'){
                        campos_vacios = campos_vacios + 1;
                    }
                });

                $('#detalle tr.count_trs td select.validarcb').each(function () {
                    var input = $(this).val();
                    if (input === '0'){
                        campos_vacios = campos_vacios + 1;
                    }
                });
				if (campos_vacios > 0){
				    jAlert('Algun campo en el detalle esta vacio');
                }else{

                    $('.adminresumendiario').waitMe({
                        effect : 'bounce',
                        text : 'Guardando...',
//    bg : rgba(255,255,255,0.7),
                        color : '#000',
                        maxSize : '',
                        textPos : 'vertical',
                        fontSize : '',
                        source : ''
                    });

                    var Objsecciones = {};
                    Objsecciones.valor=[];
                    var row_num = 0;
                    $("#detalle").find("tbody tr.count_trs").each(function (index) {
                        row_num++;
                        var txt_id_resumen_diario_detalle_=$('#txt_id_resumen_diario_detalle_'+row_num+'').val();
                        var cb_tipo_comprobante_=$('#cb_tipo_comprobante_'+row_num+'').val();
                        var txt_codigo_documento_=$('#txt_codigo_documento_'+row_num+'').val();
                        var id_cb_comprobantes_=$('#cb_comprobantes_'+row_num+'').val();
                        var valor_cb_comprobantes_=$('#cb_comprobantes_'+row_num+' :selected').text();
                        var txt_subtotal_=$('#txt_subtotal_'+row_num+'').val();
                        var txt_igv_=$('#txt_igv_'+row_num+'').val();
                        var txt_total_ = $('#txt_total_'+row_num+'').val();
                        var cb_estado_comprobante_ = $('#cb_estado_comprobante_'+row_num+'').val();
                        var txt_motivo_ = $('#txt_motivo_'+row_num+'').val();
                        var devolver_montos = $('#cb_devolver_'+row_num+'').val();


                        Objsecciones.valor.push({ "txt_id_resumen_diario_detalle":txt_id_resumen_diario_detalle_,"cb_tipo_comprobante":cb_tipo_comprobante_,"txt_codigo_documento":txt_codigo_documento_, 'id_cb_comprobantes': id_cb_comprobantes_, 'valor_cb_comprobantes': valor_cb_comprobantes_, 'txt_subtotal': txt_subtotal_,'txt_igv': txt_igv_, 'txt_total': txt_total_, 'cb_estado_comprobante': cb_estado_comprobante_, 'txt_motivo': txt_motivo_, 'devolver_montos': devolver_montos});

                    });


                    var jsonDetalle=JSON.stringify(Objsecciones);

                    $.ajax({
						type: "POST",
						url: "{$link->getAdminLink('AdminResumenDiario')|addslashes}",
						async: true,
						dataType: "json",
						data : {
                            ajax: "1",
                            tab: "AdminResumenDiario",
                            action: "guardarResumenDiario",
                            id_resumen_diario: $('#txt_id_resumen_diario').val(),
                            fecha_generacion: $('#fecha_generacion').val(),
                            fecha_emision_comprobantes: $('#fecha_comprobantes').val(),
                            nota: $('#txt_nota').val(),
                            detalleresumen: jsonDetalle,
                    },
                    success:  function (res) {
                        var html = '';
                        if (res.id_resumen_diario){
                            $("#txt_id_resumen_diario").val(res.id_resumen_diario);
                            $("#span_identificador").text(res.identificador);

                            var cont = 0;
                            $.each(res.mensaje_soap, function () {
                                cont = cont + 1;
                            });
                            if (cont > 0) {
                                html += '  <div class="bootstrap">\n' +
                                    '                                <div class="alert alert-warning">\n' +
                                    '                                    <button type="button" class="close" data-dismiss="alert">&times;</button>\n';

                                if (cont > 1) {
                                    html += '<h4>Hay ' + cont + ' errores</h4>\n';
                                }
                                html +=
                                    '                                    <ul class="list-unstyled">\n';
                                $.each(res.mensaje_soap, function (index, element) {
                                    html += '<li>' + element + '</li>\n';
                                });
                                html += '                                    </ul>\n' +
                                    '                                </div>\n' +
                                    '                            </div>';
                                $('#errores').html(html);
                            }
                            var i = 0;
                            if (res.detalle) {
                                $.each(res.detalle, function (index) {
                                    i++;
                                    id_detalle_resumen = this.clave_seccion;
                                    $("#txt_id_resumen_diario_detalle_" +i).val(id_detalle_resumen);
//                            alert($("#txt_id_seccion_"+(index+1)).val(id_seccion));

                                });
                            }
                            $.growl.notice({ title: ""+res.correcto+"",message: "" });
                            window.location.replace("{$link->getAdminLink('AdminResumenDiario')|addslashes}");
                        }

                    }
                    }).done(function(){
                        $('.adminresumendiario').waitMe('hide');
                    });

                }
            }
            else{
                alert('Llene los campos de Fechas')
            }

        }


        function consultarCDR() {
            $.ajax({
                type: "POST",
                url: "{$link->getAdminLink('AdminResumenDiario')|addslashes}",
                async: true,
                dataType: "json",
                data : {
                    ajax: "1",
                    tab: "AdminResumenDiario",
                    action: "consultarCDRTicket",
                    id_resumen_diario: $('#txt_id_resumen_diario').val(),
                },
                success:  function (res) {
                    if (res.respuesta == 'error'){
                        $.growl.error({ title: res.correcto, message: '' })
                    }

                    if (res.respuesta == 'ok'){
                        $.growl.notice({ title: res.correcto, message: '' })
                    }
                }
            }).done(function(){

//                if (id_producto_comb > 0){
//                    $('#cb_combina_'+num+'').val(id_producto_comb).trigger('change');
//                }
            });
        }

	</script>
{/block}