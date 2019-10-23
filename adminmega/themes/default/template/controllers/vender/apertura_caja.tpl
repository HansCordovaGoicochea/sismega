<style>
    .page_apertura input.monto_apertura{
        font-size: 2em!important;
    }
    /*overriding theme.css*/
    #content.bootstrap {
        padding: 10px 10px 0 225px;
        -webkit-transition-property: margin;
    }
    .mobile #content.bootstrap {
        padding: 50px 5px 0;
    }
</style>
<div class="panel page_apertura">
    <div class="jumbotron jumbotron-fluid">
        <div class="container text-center">
            <h1 class="display-3">Nuevo arqueo de caja</h1>
            <p class="lead">Aperture caja para vender</p>
            <hr class="my-2">
            <div class="form-group col-lg-offset-5 col-lg-2 col-xs-12">
                <div class="input-group input-group-lg">
                    <span class="input-group-addon" id="helpId">S/</span>
                    <input type="number"
                       class="form-control text-center col-lg-2 monto_apertura" name="monto_apertura" id="monto_apertura" aria-describedby="helpId"
                       value="0.00" step="0.10">
                </div>
                <small id="helpId" class="form-text text-muted">Escriba el dinero en caja (SOLES)</small>
            </div>
            <div class="form-group  col-lg-offset-5 col-lg-2 col-xs-12">
                <div class="input-group input-group-lg">
                    <span class="input-group-addon" id="helpId">$</span>
                    <input type="number"
                       class="form-control text-center col-lg-2 monto_apertura" name="monto_apertura_dolares" id="monto_apertura_dolares" aria-describedby="helpIddol"
                    value="0.00" step="0.10">
                </div>
                <small id="helpIddol" class="form-text text-muted">Escriba el dinero en caja (DOLARES)</small>
            </div>
            <p>
                <textarea name="nota_apertura" id="nota_apertura" cols="20" rows="2" aria-describedby="descId"></textarea>
                <small id="descId" class="form-text text-muted">Alguna observación al abrir la caja</small>
            </p>
            <p class="lead">
                <a class="btn btn-primary btn-lg" role="button" id="abrirCaja">
                    <i class="fa fa-inbox fa-lg"></i>
{*                    <i class="fa fa-spinner fa-lg fa-spin"></i>*}
                    Guardar
                </a>
            </p>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('.page-head').remove();
        $('#opcionesCaja').remove();

        $('#abrirCaja').click(function () {
            $.ajax({
                type:"POST",
                url: "{$link->getAdminLink('AdminVender')|addslashes}",
                async: true,
                dataType: "json",
                data : {
                    ajax: "1",
                    token: "{getAdminToken tab='AdminVender'}",
                    tab: "AdminVender",
                    action: "abrirCaja",
                    monto_apertura: $('#monto_apertura').val(),
                    monto_apertura_dolares: $('#monto_apertura_dolares').val(),
                    nota_apertura: $('#nota_apertura').val(),
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