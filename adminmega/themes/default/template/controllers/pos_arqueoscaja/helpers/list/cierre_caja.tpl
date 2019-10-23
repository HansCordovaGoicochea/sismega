<style>
    .page_cierre input.monto_cierre{
        font-size: 2em!important;
    }
    .vertical-alignment-helper {
        display:table;
        height: 100%;
        width: 100%;
        pointer-events:none;
    }
    .vertical-align-center {
        /* To center vertically */
        display: table-cell;
        vertical-align: middle;
        pointer-events:none;
    }
    .modal-content {
        /* Bootstrap sets the size of the modal in the modal-dialog class, we need to inherit it */
        width:inherit;
        max-width:inherit; /* For Bootstrap 4 - to avoid the modal window stretching full width */
        height:inherit;
        /* To center horizontally */
        margin: 0 auto;
        pointer-events:all;
    }
</style>
<!-- Modal -->
<div class="modal fade" id="modaCierreCaja" role="dialog">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center">
            <input type="hidden" id="id_pos_arqueoscaja" name="id_pos_arqueoscaja" value="">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header text-center" style="padding: 7px!important;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <span class="modal-title">CIERRE DE CAJA</span>
                </div>
                <div class="modal-body" id="cerrarCaja">
                    <div class="form-group col-lg-offset-4 col-lg-4 col-xs-12">
                        <div class="input-group input-group-lg">
                            <span class="input-group-addon" id="helpId">S/</span>
                            <input type="number"
                                   class="form-control text-center monto_cierre" name="monto_cierre" id="monto_cierre" aria-describedby="helpId"
                                   value="0.00" step="0.10">
                        </div>
                        <small id="helpId" class="form-text text-muted">Escriba el dinero en caja</small>
                    </div>
                    <div class="row text-center">
                        <label class="text-center">o seleccione uno a uno los billetes/monedas:</label>
                    </div>
                    <div id="contBilletes" class="row">
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">500</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[500]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">200</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[200]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">100</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[100]" value="">
                        </div>
                        <div class="contBillete threecol last">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">50</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[50]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">20</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[20]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">10</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[10]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">5</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[5]" value="">
                        </div>
                        <div class="contBillete threecol last">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">2</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[2]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">1</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[1]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">0.5</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[0.5]" value="">
                        </div>
                        <div class="contBillete threecol">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">0.2</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[0.2]" value="">
                        </div>
                        <div class="contBillete threecol last">
                            <button class="billete sixcol ui-btn ui-shadow ui-corner-all">0.1</button>
                            <input type="text" data-role="none" class="billeteomoneda_cerrar  sixcol last" name="billeteomoneda_cerrar[0.1]" value="">
                        </div>
                    </div>
                    <p>
                        <textarea name="nota_cierre" id="nota_cierre" cols="20" rows="2" aria-describedby="descId"></textarea>
                        <small id="descId" class="form-text text-muted">Alguna observación al cerrar la caja</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-primary btn-lg btn-block ladda-button" tabindex="4" role="button" id="btncerrarCaja" data-style="slide-up" data-spinner-color="white">
                     <span class="ladda-label">
						{l s='Cerrar Caja' d='Admin.Login.Feature'}
					</span>
                    </a>
{*                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>*}
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    $(".billeteomoneda_cerrar").on("keyup",function(event){
        var valor = 0;
        $(".billeteomoneda_cerrar").each(function(){
            var valInput = $(this).val();
            if(valInput !== "")
                valor += valInput * $(this).prev('button').text();
        });
        $("#cerrarCaja .monto_cierre").val(ps_round(valor, 2));
        efectoGuardado("#cerrarCaja .monto_cierre");
    });

    function clickCerrarcaja() {
        l = Ladda.create( document.querySelector( '#btncerrarCaja' ) );
    }
    $(function () {

        $('#btncerrarCaja').click(function () {
            $.ajax({
                type:"POST",
                url: "{$link->getAdminLink('AdminPosArqueoscaja')|addslashes}",
                async: true,
                dataType: "json",
                data : {
                    ajax: "1",
                    token: "{getAdminToken tab='AdminPosArqueoscaja'}",
                    tab: "AdminPosArqueoscaja",
                    action: "cerrarCaja",
                    id_pos_arqueoscaja: $('#id_pos_arqueoscaja').val(),
                    monto_cierre: $('#monto_cierre').val(),
                    nota_cierre: $('#nota_cierre').val(),
                },
                beforeSend: function(){
                    clickCerrarcaja();
                    l.start();
                },
                success : function(res)
                {
                    if (res.result){
                        $.growl.notice({ title: "Exito!",message: "redireccionando!!!" });
                        // location.reload();
                        window.location.replace("{$link->getAdminLink('AdminPosArqueoscaja')|addslashes}");
                    }else{
                        $.growl.error({
                            title: "Alerta!",
                            message: ""+res.error+"",
                            fixed: true,
                            size: "large",
                            duration: 8000
                        });
                    }

                },
                complete: function (res) {

                }
            });
        })
    })
</script>