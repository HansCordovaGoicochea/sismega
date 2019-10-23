{block name="other_input"}
    <div class="oe_clear ui-tabs ui-widget ui-widget-content ui-corner-all">
            <div class="panel">
                <div class="row">
                    <form enctype="multipart/form-data" action="{$link->getAdminLink('AdminCertificadoFE')|escape:'html':'UTF-8'}" name="frm" id="frm" method="post">
                        <input type="text" id="txtid_certificadofe" name="txtid_certificadofe" class="form-control" value="{$objFactura->id}" style="display:none;">

                        <div class="row">
                            <div class="col-lg-6 col-xs-12 datos_1">

                                <div class="row" style="margin-bottom: 10px;">
                                    <div class="form-group">
                                        <label for="txtNombre" class="control-label col-lg-3 col-xs-12">Nombre:</label>
                                        <div class="col-lg-8 col-xs-12">
                                            <input type="text" id="txtNombre" name="txtNombre" class="form-control" value="{$objFactura->nombre}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" >
                                    <div class="form-group">
                                        <label class="control-label col-lg-3 col-xs-12 required">Adj. Certificado:</label>
                                        <input id="txtadjArchivo" name="txtadjArchivo[]" type="file" multiple class="btn btn-primary hide" value="{$objFactura->archivo}">
                                        <div class="dummyfile input-group col-lg-8 col-xs-12">
                                            <span class="input-group-addon"><i class="icon-file"></i></span>
                                            <input id="adjArchivo" name="adjArchivo" type="text" readonly value="{$objFactura->archivo}">
                                            <span class="input-group-btn">
											<button id="filename-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
											<i class="icon-folder-open"></i>Añadir Archivo
											</button>
										</span>
                                        </div>
                                        <div id="mensaje_pdf"></div>
                                    </div>
                                </div>
                                <script>
                                    var pdf = "";
                                    $("#txtadjPdf").on("change", function(){
//                                    alert('ddddddd');
                                        $("#mensaje_pdf").html('');
                                        var archivos = document.getElementById('txtadjPdf').files;
                                        var navegador = window.URL || window.webkitURL;
                                        var indexs=$('#txtadjPdf').attr("alt");
                                        //alert(indexs);
                                        for(x=0; x<archivos.length; x++)
                                        {
                                            var size = archivos[x].size;
                                            var type = archivos[x].type;
                                            var name = archivos[x].name;
                                            pdf = name;
//                                        alert(size);
                                            if (size > 5048*5048)
                                            {
                                                $.growl.error({ title: "Error",message: "El archivo "+name+" supera el máximo permitido 1MB" });
//                                            $("#mensaje_pdf").append("<p style='color: red'></p>");
                                            }
                                            else
                                            {
                                                $.growl.notice({ title: "PDF: "+name+" listo para subir.",message: "" });
//                                            $("#mensaje_pdf").append("<p style='color: green'><strong>Adjunto "+name+" listo para subir.</strong></p>");
                                            }

                                        }
//                                    $("#mensaje_pdf").addClass('alert alert-info');
                                        $("#adjPdf").val(pdf);
                                    });
                                </script>
                                <div class="row" style="margin-bottom: 10px;">
                                    <div class="form-group">
                                        <label for="txtClaveCertificado" class="control-label col-lg-3 col-xs-12 required">Clave del Certificado:</label>
                                        <div class="col-lg-2 col-xs-12">
                                            <input type="text" id="txtClaveCertificado" name="txtClaveCertificado" class="form-control" value="{$objFactura->clave_certificado}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <label for="inputName" class="control-label col-lg-3 col-xs-12 required">Fecha Inicio:</label>
                                    <div class="input-group">
                                        <div class="col-lg-2 col-xs-12">
                                            <div class="input-group fixed-width-md col-lg-2">
                                                <input name="fecha_caducidad" data-hex="true" value="{$objFactura->fecha_caducidad}" class="datepicker" type="text" id = "fecha_caducidad" />
                                                <div class="input-group-addon" onclick="pulsarcalendatio('fecha_caducidad');">
                                                    <i class="icon-calendar-empty" ></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-xs-12 datos_1">
                                <div class="" id="fieldset_0">
                                    <div class="form-wrapper">
                                        <div class="form-group" >
                                            <label class="control-label col-lg-3 required">
                                                Usuario Sunat
                                            </label>
                                            <div class="col-lg-9"  style="margin-bottom: 10px;">
                                                <input type="text" name="user_sunat" id="user_sunat" value="{$objFactura->user_sunat}" class="" size="80"  autocomplete="off">
                                                <p class="help-block"><small>RUC + Usuario. Ejemplo: 01234567890ELUSUARIO</small></p>
                                            </div>


                                        </div>
                                        <div class="form-group" >
                                            <label class="control-label col-lg-3 required">
                                                Password Sunat
                                            </label>
                                            <div class="col-lg-9"  style="margin-bottom: 10px;">
                                                <div class="input-group fixed-width-lg">
										<span class="input-group-addon">
											<i class="icon-key"></i>
										</span>
                                                    <input type="text" id="pass_sunat" name="pass_sunat" class="" value="{$objFactura->pass_sunat}" autocomplete="off" required="required">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group" >
                                            <label class="control-label col-lg-3 required">
                                                Web Service Sunat
                                            </label>
                                            <div class="col-lg-9" style="margin-bottom: 10px;">
                                                <input type="text" name="web_service_sunat" id="web_service_sunat" value="{$objFactura->web_service_sunat}" class="">

                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-lg-3" for="">{l s='Activo' d='Modules.Facetedsearch.Admin'}</label>
                                            <div class="col-lg-9">
                                                <span class="switch prestashop-switch fixed-width-md" style="margin-top: 0px; ">
                                                    <input type="radio" name="active" id="active_on" value="1" {if $objFactura->active}checked{else}{/if}>
                                                    <label for="active_on" class="radioCheck">Sí</label>
                                                     <input type="radio" name="active" id="active_off" value="0" {if !$objFactura->active}checked{/if}>
                                                    <label for="active_off" class="radioCheck">No</label>
                                                    <a class="slide-button btn"></a>
                                                </span>
                                            </div>
                                        </div>
                                    </div><!-- /.form-wrapper -->

                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
            <div class="panel-footer">

                <a href="{$link->getAdminLink('AdminCertificadoFE')|escape:'html':'UTF-8'}" class="btn btn-default" onclick="window.history.back();">
                    <i class="process-icon-cancel"></i> Cancelar
                </a>
                <a href="{$link->getAdminLink('AdminCertificadoFE')|escape:'html':'UTF-8'}" class="btn btn-default" onclick="window.location.replace(window.history.back()); location.reload();">
                    <i class="process-icon-back"></i> Visualizar Lista
                </a>

                <button type="button" id="btnProcesar" name="btnProcesar" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i>Guardar
                </button>

            </div>
        </div>
{/block}
{block name="after"}
    <script>
        function pulsarcalendatio(objeto){
            $('#'+objeto).focus();
        }
        $(function () {

                $('.datepicker').datepicker({
                    prevText: '',
                    nextText: '',
                    dateFormat: 'yy-mm-dd',
                });


            // las cajas de adjuntos

            $('#filename-selectbutton').click(function(e) {
                $('#txtadjArchivo').trigger('click');
            });

            $('#adjArchivo').click(function(e) {
                $('#txtadjArchivo').trigger('click');
            });

            $('#adjArchivo').on('dragenter', function(e) {
                e.stopPropagation();
                e.preventDefault();
            });

            $('#adjArchivo').on('dragover', function(e) {
                e.stopPropagation();
                e.preventDefault();
            });

            $('#adjArchivo').on('drop', function(e) {
                e.preventDefault();
                var files = e.originalEvent.dataTransfer.files;
                $('#txtadjArchivo')[0].files = files;
                $(this).val(files[0].name);
            });

            $('#txtadjArchivo').change(function(e) {
                if ($(this)[0].files !== undefined)
                {
                    var files = $(this)[0].files;
                    var name  = '';

                    $.each(files, function(index, value) {
                        name += value.name+', ';
                    });

                    $('#adjArchivo').val(name.slice(0, -2));
                }
                else // Internet Explorer 9 Compatibility
                {
                    var name = $(this).val().split(/[\\/]/);
                    $('#adjArchivo').val(name[name.length-1]);
                }
            });


        });


        $("#btnProcesar").on('click',function (e) {

            $('.admincertificadofe').waitMe({
                effect : 'bounce',
                text : 'Guardando...',
//    bg : rgba(255,255,255,0.7),
                color : '#000',
                maxSize : '',
                textPos : 'vertical',
                fontSize : '',
                source : ''
            });

            llenarFactura();

        });

        function llenarFactura() {
            $('#resultado').css('display','');
            var htmlmensaje = '';
            var adjArchivo = $('#adjArchivo').val();
//        alert($("#imagen").prop('files')[0]);
            var archivo = $("#txtadjArchivo")[0].files[0];
            var formData = new FormData();
            formData.append('ajax', "1");
            formData.append('token', "{getAdminToken tab='AdminCertificadoFE'}");
            formData.append('tab', "AdminCertificadoFE");
            formData.append('action', "addCertificado");
            formData.append('id_certificadofe', $('#txtid_certificadofe').val());
            formData.append('nombre', $('#txtNombre').val());
            formData.append('clave_certificado', $('#txtClaveCertificado').val());

            // console.log(adjArchivo);
            formData.append('archivo', archivo);
            formData.append('nombre_archivo', adjArchivo);
            formData.append('user_sunat', $('#user_sunat').val());
            formData.append('pass_sunat', $('#pass_sunat').val());
            formData.append('web_service_sunat', $('#web_service_sunat').val());
            formData.append('fecha_caducidad', $('#fecha_caducidad').val());
            formData.append('active', $('input[type=radio][name=active]:checked').val());

            $.ajax({
                type: "POST",
                url: "{$link->getAdminLink('AdminCertificadoFE')|addslashes}",
                async: true,
                dataType: "json",
                enctype: 'multipart/form-data',
                cache: false,
                contentType: false,
                processData: false,
                data: formData,
                success:  function (res) {
                    if (res.errors){
                        $.growl.error({ title: ""+res.incorrecto+"",message: "",duration:6200 });
                        $('.admincertificadofe').waitMe('hide');
                    }
                    else {
                        $("#txtid_certificadofe").val(res.id_factura);

                        $.growl.notice({ title: ""+res.correcto+"",message: "" });
                        window.location.replace("{$link->getAdminLink('AdminCertificadoFE')|addslashes}");
                        $('.admincertificadofe').waitMe('hide');
                    }
                }
            })
        }
    </script>
{/block}