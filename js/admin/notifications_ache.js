$(document).ready(function () {
    if (typeof admin_noti_ache_ajax_url !== 'undefined') {

        getNotificationAche();
    }
});

function getNotificationAche() {

    // notiInsertOnBackOfficeDOM('<div id="ache_notif" class="notifs"></div>');
    $.ajax({
        type: 'POST',
        headers: {"cache-control": "no-cache"},
        url: admin_noti_ache_ajax_url,
        async: true,
        cache: false,
        dataType: 'json',
        data: {
            action: 'getNotificationAche',
            ajax: true,
        },
        success: function (json) {

            if (json) {
                // console.log(json);
                // Set moment language
                // moment.lang(full_language_code);
                initHeaderNotification(json)
            }
        }
    });
}

function initHeaderNotification(json) {
    notiInsertOnBackOfficeDOM(json);
}


function notiInsertOnBackOfficeDOM(json) {
    var today = new Date();
    var date_ache = today.getDate()+'/'+(today.getMonth()+1)+'/'+today.getFullYear();

    var nbOrders = parseInt(json.order.total);
    var nbCitasMessages = parseInt(json.citas.total);
    var nbCumples = parseInt(json.cumples.total);

    // $('#ache_notif').remove();

    let class_hide = '';

    if (perfil_usuario === 'Recepcionista'){
        class_hide = 'hide';
        var notifications_total = nbCitasMessages + nbCumples;
    }else{
        var notifications_total = nbCitasMessages + nbCumples + nbOrders;
    }

    if (0 < $('ul.header-list').length) {
        // PrestaShop 1.7 - Default theme


        let html_base = `
        <li id="notification" class="dropdown">
		<a href="javascript:void(0);" class="notification dropdown-toggle notifs">
			<i class="material-icons">notifications_none</i>
			<span id="total_notif_number_wrapper" class="notifs_badge hide">
			<span id="total_notif_value">0</span>
		</span>
		</a>
		<div class="dropdown-menu dropdown-menu-right notifs_dropdown">
			<div class="notifications">
				<ul class="nav nav-tabs" role="tablist">

					  <li class="nav-item active" style="width: 33.33%!important;">
                            <a class="nav-link" data-toggle="tab" data-type="cita" href="#citas-notifications" role="tab" id="orders-tab">Prox. Citas<span id="citas_notif_value"></span></a>
                    </li>

					<li class="nav-item" style="width: 33.33%!important;">
						<a class="nav-link" data-toggle="tab" data-type="cumples" href="#cumples-notifications" role="tab" id="cumples-tab">Prox. Cumpl.<span id="cumples_notif_value"></span></a>
					</li>
					
					<li class="nav-item `+class_hide+`" style="width: 33.33%!important;">
						<a class="nav-link" data-toggle="tab" data-type="cobrar" href="#cobrar-notifications" role="tab" id="cobrar-tab">Por Cobrar<span id="cobrar_notif_value"></span></a>
					</li>

				</ul>
				<!-- Tab panes -->
				<div class="tab-content">

					<div class="tab-pane active empty" id="citas-notifications" role="tabpanel">
						<p class="no-notification" style="bottom: 20px;">
							No hay próximas citas por ahora. :(<br>
						</p>
						<div class="notification-elements" style="padding-bottom: 25px;"></div>
						<footer class="panel-footer" id="footer_citas" style="position: absolute; left: 0; bottom: 0; width: 100%; background-color: #d1dee2; color: white; text-align: center;height: 25px!important; display: flex; align-items: center; justify-content: center;">
						    <i class="fa fa-ticket" style="color: #25b9d7!important;"></i>&nbsp;
                            <a href="` + baseAdminDir + `index.php?tab=AdminReservarCita&token=` + token_admin_citas + `">Ver Reservas 
                            <i class="material-icons" style="color: #25b9d7!important;">chevron_right</i></a>
                        </footer>
					</div>

					<div class="tab-pane empty" id="cumples-notifications" role="tabpanel">
						<p class="no-notification" style="bottom: 20px;">
							No hay próximos cumpleaños por ahora. :(<br>
						</p>
						<div class="notification-elements" style="padding-bottom: 25px;"></div>
				
                        <footer class="panel-footer" id="footer_citas" style=" position: absolute; left: 0; bottom: 0; width: 100%; background-color: #d1dee2; color: white; text-align: center;height: 25px!important; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-birthday-cake" style="color: #25b9d7!important;"></i>&nbsp;
                            <a href="` + baseAdminDir + `index.php?tab=AdminCustomersCumples&birthday=`+date_ache+`&token=` + token_admin_customerscumples + `">Ver Cumpleaños 
                            <i class="material-icons" style="color: #25b9d7!important;">chevron_right</i></a>
                        </footer>
					</div>
					<div class="tab-pane empty" id="cobrar-notifications" role="tabpanel">
                                    <p class="no-notification" style="bottom: 20px;">
                                         No hay cuentas por cobrar. :)<br>
                                     </p>
                                    <div class="notification-elements" style="padding-bottom: 25px;"></div>
                                    <footer class="panel-footer" id="footer_cobrar" style=" position: absolute; left: 0; bottom: 0; width: 100%; background-color: #d1dee2; color: white; text-align: center;height: 25px!important; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa fa-money" style="color: #25b9d7!important;"></i>&nbsp;
                                        <a href="` + baseAdminDir + `index.php?tab=AdminCuentasCobrar&token=` + token_admin_cuentascobrar + `">Ver cuentas por cobrar 
                                        <i class="material-icons" style="color: #25b9d7!important;">chevron_right</i></a>
                                    </footer>
                    </div>
               </div>
			</div>
		</div>
	</li>
        `;
        $(html_base).appendTo('ul.header-list');

    }
    else if (0 < $('#header-employee-container').length) {
        // PrestaShop 1.7 - New theme

        let html_base = `
        <div id="notif" class="notification-center dropdown dropdown-clickable">
		<button class="btn notification js-notification dropdown-toggle" data-toggle="dropdown">
			<i class="material-icons">notifications_none</i>
			<span id="notifications-total" class="count hide">0</span>
		</button>
		<div class="dropdown-menu dropdown-menu-right js-notifs_dropdown">
			<div class="notifications">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item active" style="width: 33.33%!important;">
                            <a class="nav-link" data-toggle="tab" data-type="cita" href="#citas-notifications" role="tab" id="orders-tab">Prox. Citas<span id="citas_notif_value"></span></a>
                    </li>

					<li class="nav-item" style="width: 33.33%!important;">
						<a class="nav-link" data-toggle="tab" data-type="cumples" href="#cumples-notifications" role="tab" id="cumples-tab">Prox. Cumpl.<span id="cumples_notif_value"></span></a>
					</li>
					
					<li class="nav-item `+class_hide+`" style="width: 33.33%!important;">
						<a class="nav-link" data-toggle="tab" data-type="cobrar" href="#cobrar-notifications" role="tab" id="cobrar-tab">Por Cobrar<span id="cobrar_notif_value"></span></a>
					</li>

                </ul>

				<!-- Tab panes -->
				<div class="tab-content"  style="font-size: 12px!important;">
				
                    <div class="tab-pane active empty" id="citas-notifications" role="tabpanel">
						<p class="no-notification" style="bottom: 20px;">
							No hay próximas citas por ahora. :(
						</p>
						<div class="notification-elements" style="padding-bottom: 25px;"></div>
						<footer class="panel-footer" id="footer_citas" style=" position: absolute; left: 0; bottom: 0; width: 100%; background-color: #d1dee2; color: white; text-align: center;height: 25px!important; display: flex; align-items: center; justify-content: center;">
			                <i class="material-icons" style="color: #25b9d7!important;">insert_invitation</i>&nbsp;
                            <a href="` + baseAdminDir + `index.php?tab=AdminReservarCita&token=` + token_admin_citas + `">Ver Reservas 
                            <i class="material-icons" style="color: #25b9d7!important;">chevron_right</i></a>
                        </footer>
					</div>

					<div class="tab-pane empty" id="cumples-notifications" role="tabpanel">
						<p class="no-notification" style="bottom: 20px;">
							No hay próximos cumpleaños por ahora. :(
						</p>
						<div class="notification-elements" style="padding-bottom: 25px;"></div>
						<footer class="panel-footer" id="footer_cuples" style=" position: absolute; left: 0; bottom: 0; width: 100%; background-color: #d1dee2; color: white; text-align: center;height: 25px!important; display: flex; align-items: center; justify-content: center;">
						        <i class="material-icons" style="color: #25b9d7!important;">cake</i>&nbsp;
                            <a href="` + baseAdminDir + `index.php?tab=AdminCustomersCumples&birthday=`+date_ache+`&token=` + token_admin_customerscumples + `">Ver Cumpleaños <i class="material-icons" style="color: #25b9d7!important;">chevron_right</i></a>
                        </footer>
					</div>
					<div class="tab-pane empty" id="cobrar-notifications" role="tabpanel">
                        <p class="no-notification" style="bottom: 20px;">
                             No hay cuentas por cobrar. :)<br>
                         </p>
						<div class="notification-elements" style="padding-bottom: 25px;"></div>
                        <footer class="panel-footer" id="footer_cobrar" style=" position: absolute; left: 0; bottom: 0; width: 100%; background-color: #d1dee2; color: white; text-align: center;height: 25px!important; display: flex; align-items: center; justify-content: center;">
                                 <i class="material-icons" style="color: #25b9d7!important;">money</i>&nbsp;
                            <a href="` + baseAdminDir + `index.php?tab=AdminCuentasCobrar&token=` + token_admin_cuentascobrar + `">Ver cuentas por cobrar <i class="material-icons" style="color: #25b9d7!important;">chevron_right</i></a>
                        </footer>
					</div>
                    
				</div>
			</div>
		</div>
	</div>
        `;

        $('.notification-ache-component').remove();
        let html = '<div class="component pull-md-right notification-ache-component">' + html_base + '</div>';

        $(html).insertBefore('#header-employee-container');

    } else {
        console.error('Could not find proper place to add the gamification notification center. x_x');
    }

    // Add orders notifications to the list
    html = "";
    $.each(json.citas.results, function (property, value) {
        html += "<a class='notif' href='" + baseAdminDir + "index.php?tab=AdminReservarCita&token=" + token_admin_citas + "'>";
        // html += "<a class='notif' href='" + baseAdminDir + "index.php?tab=AdminReservarCita&token=" + token_admin_citas + "&updatereservar_cita&id_reservar_cita=" + parseInt(value.id_reservar_cita) + "'>";
        // html += "#" + parseInt(value.id_reservar_cita) + " - ";
        html += "<strong>" + value.customer_name + "</strong>";
        html += "<br><strong >" + value.colaborador + "</strong>";
        html += " - " + value.product_name;
        html += " - " + value.fecha + " " + value.hora;
        html += "</a>";
    });
    $("#citas-notifications").children('.notification-elements').empty();
    if (parseInt(json.citas.total) > 0) {
        $("#citas-notifications").removeClass('empty');
        $("#citas-notifications").children('.notification-elements').append(html);
        $("#citas_notif_value").text(' (' + nbCitasMessages + ')').attr('data-nb', nbCitasMessages);
    } else {
        $("#citas-notifications").addClass('empty');
        $("#citas_notif_value").text('');
    }

    // Add customers notifications to the list
    html = "";
    $.each(json.cumples.results, function (property, value) {
        html += "<a class='notif' href='" + baseAdminDir + "index.php?tab=AdminCustomersCumples&birthday="+date_ache+"&token=" + token_admin_customerscumples + "'>";
        // html += "<a class='notif' href='" + baseAdminDir + "index.php?tab=AdminCustomers&token=" + token_admin_customers + "&viewcustomer&id_customer=" + parseInt(value.id_customer) + "'>";
        html += "#" + value.id_customer + " - <strong>" + value.customer_name + "</strong>"
        html += " - " + value.fecha;
        html += "</a>";
    });
    $("#cumples-notifications").children('.notification-elements').empty();
    if (parseInt(json.cumples.total) > 0) {
        $("#cumples-notifications").removeClass('empty');
        $("#cumples-notifications").children('.notification-elements').append(html);
        $("#cumples_notif_value").text(' (' + nbCumples + ')').attr('data-nb', nbCumples);
    } else {
        $("#cumples-notifications").addClass('empty');
        $("#cumples_notif_value").text('');
    }

    // Add orders notifications to the list
    html = "";
    $.each(json.order.results, function(property, value) {
        html += "<a class='notif' href='"+baseAdminDir+"index.php?tab=AdminOrders&token=" + token_admin_orders + "&vieworder&id_order=" + parseInt(value.id_order) + "'>";
        html += "&nbsp;<strong>" + value.customer_name + "</strong>";
        html += "<strong class='pull-right float-right'>" + value.total_paid + "</strong>";
        html += "</a>";
    });
    $("#cobrar-notifications").children('.notification-elements').empty();
    if (parseInt(json.order.total) > 0)
    {
        $("#cobrar-notifications").removeClass('empty');
        $("#cobrar-notifications").children('.notification-elements').append(html);
        $("#cobrar_notif_value").text(' (' + nbOrders + ')').attr('data-nb', nbOrders);
    } else {
        $("#cobrar-notifications").addClass('empty');
        $("#cobrar_notif_value").text('');
    }


    if (notifications_total > 0) {
        $("#total_notif_number_wrapper").removeClass('hide');
        $('#total_notif_value').text(notifications_total);
        $("#notifications-total").removeClass('hide');
        $('#notifications-total').text(notifications_total);
    } else {
        $("#total_notif_number_wrapper").addClass('hide');
        $("#notifications-total").addClass('hide');
    }


    $('.notification.dropdown-toggle').on('click', function (event) {
        $(this).parent().toggleClass('open');
    });
    $('body').on('click', function (e) {

        if (!$('#notification.dropdown').is(e.target)
            && $('#notification.dropdown').has(e.target).length === 0
            && $('.open').has(e.target).length === 0
        ) {
            $('#notification.dropdown').removeClass('open');
        }
    });

}
