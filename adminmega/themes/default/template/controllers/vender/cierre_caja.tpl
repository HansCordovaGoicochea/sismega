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
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Cierre de caja</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group col-lg-offset-4 col-lg-4 col-xs-12">
                        <div class="input-group input-group-lg">
                            <span class="input-group-addon" id="helpId">S/</span>
                            <input type="number"
                                   class="form-control text-center monto_cierre" name="monto_cierre" id="monto_cierre" aria-describedby="helpId"
                                   value="0.00" step="0.10">
                        </div>
                        <small id="helpId" class="form-text text-muted">Escriba el dinero en caja (SOLES)</small>
                    </div>
                    <div class="form-group col-lg-offset-4 col-lg-4 col-xs-12">
                        <div class="input-group input-group-lg">
                            <span class="input-group-addon" id="helpId">$</span>
                            <input type="number"
                                   class="form-control text-center monto_cierre" name="monto_cierre_dolares" id="monto_cierre_dolares" aria-describedby="helpIddol"
                                   value="0.00" step="0.10">
                        </div>
                        <small id="helpIddol" class="form-text text-muted">Escriba el dinero en caja (DOLARES)</small>
                    </div>
                    <p>
                        <textarea name="nota_cierre" id="nota_cierre" cols="20" rows="2" aria-describedby="descId"></textarea>
                        <small id="descId" class="form-text text-muted">Alguna observación al cerrar la caja</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-primary btn-lg" role="button" id="cerrarCaja">
                        <i class="fa fa-inbox fa-lg"></i>
                        {*                    <i class="fa fa-spinner fa-lg fa-spin"></i>*}
                        Cerrar Caja
                    </a>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    $(function () {

        $('#cerrarCaja').click(function () {
            $.ajax({
                type:"POST",
                url: "{$link->getAdminLink('AdminVender')|addslashes}",
                async: true,
                dataType: "json",
                data : {
                    ajax: "1",
                    token: "{getAdminToken tab='AdminVender'}",
                    tab: "AdminVender",
                    action: "cerrarCaja",
                    monto_cierre: $('#monto_cierre').val(),
                    monto_cierre_dolares: $('#monto_cierre_dolares').val(),
                    nota_cierre: $('#nota_cierre').val(),
                },
                beforeSend: function(){
                    $('body').waitMe({
                        effect: 'timer',
                        text: 'Cargando...',
                        color: '#000',
                        maxSize: '',
                        textPos: 'vertical',
                        fontSize: '',
                        source: ''
                    });
                },
                success : function(res)
                {
                    if (res.result){
                        $.growl.notice({ title: "Exito!",message: "redireccionando!!!" });
                        location.reload();
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