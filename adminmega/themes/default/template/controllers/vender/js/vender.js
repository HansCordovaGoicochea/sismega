$(function () {
    $('.page-head').remove();

    // if (perfil_empleado === 'Vendedor'){
    //     $('body').addClass('page-sidebar-closed');
    //     $('body').find('li').removeClass('ul-open');
    //     $('body').find('li').removeClass('open');
    // }

    if (perfil_empleado === 'Administrador' || perfil_empleado === 'SuperAdmin'){
        $('#cambiarCaja').click(function() {
            $('#elegirCaja-wrapper').css('display', 'block');
            $('#elegirCaja-wrapper .elegirCaja-section').css('display', 'block');
            $('.footer_ache').css('display', 'none');
        });
    }

    $("#minicart").on("click", function() {
        var rightPanel = $("#right-panel");
        var leftPanel = $("#left-panel");

        if (rightPanel.hasClass("visible")) {
            rightPanel.removeClass("visible");
            // leftPanel.css('visibility', '')
        } else {
            rightPanel.addClass("visible");
            // leftPanel.css('visibility', 'hidden')
        }

    });

    $('.alertmessage img').click(function () {
        $('.alertmessage').css('display', 'none')
    });


    $('#opcionesCaja').click(function () {
        $('#modaCierreCaja').modal({
            backdrop: 'static',
            keyboard: false,
            closable: false
        }).modal('show');

        $('#modaCierreCaja').on('shown.bs.modal', function() {
            var elem = $('#modaCierreCaja #monto_cierre');
            var val = elem.val();
            elem.focus().val('').val(val);
        })

    });


    $("#modaCierreCaja #monto_cierre").keyup(function (e) {
        var $this = $(this);
        var code = e.which;
        if (code === 13) e.preventDefault();
        if (code === 13) {
            var elem = $('#modaCierreCaja #monto_cierre_dolares');
            var val = elem.val();
            elem.focus().val('').val(val);
        }
    });

    $("#modaCierreCaja #monto_cierre_dolares").keyup(function (e) {
        var $this = $(this);
        var code = e.which;
        if (code === 13) e.preventDefault();
        if (code === 13) {
            var elem = $('#modaCierreCaja #nota_cierre');
            var val = elem.val();
            elem.focus().val('').val(val);
        }
    });
    $("#modaCierreCaja #nota_cierre").keyup(function (e) {
        var $this = $(this);
        var code = e.which;
        if (code === 13) e.preventDefault();
        if (code === 13) {
            $('#modaCierreCaja #cerrarCaja').trigger('click');
            // alert("dfdf");
        }
    });

});



// despues del vue para fncione bien
$('#tabProductosCliente a').click(function (e) {
    e.preventDefault();
    $(this).tab('show')
});

$('.datepicker').datepicker({
    prevText: '',
    nextText: '',
    dateFormat: 'yy-mm-dd',
});

function elegirCajaVender($id_caja) {

    $.ajax({
        type:"POST",
        url: url_ajax_vender,
        async: true,
        dataType: "json",
        data:{
            ajax: "1",
            token: token_vender,
            tab: "AdminVender",
            action : "elegirCajaVender",
            id_pos_arqueoscaja: $id_caja
        },
        beforeSend: function(){
            $('body').waitMe({
                effect: 'bounce',
                text: 'Cargando...',
                color: '#000',
                maxSize: '',
                textPos: 'vertical',
                fontSize: '',
                source: ''
            });
        },
        success: function (data) {
            $("#elegirCaja-wrapper").fadeOut("slow");
            $('body').waitMe('hide');
            $('.footer_ache').css('display', 'block');
        },
        error: function (error) {
            // console.log(error);
        },
        complete: function (data) {

        }
    });
}


function isNumberKey(evt){
    var charCode = (evt.which) ? evt.which : event.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
}
//

