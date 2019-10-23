/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
$(document).ready(function () {
    var hints = $('.translatable span.hint');
    if (youEditFieldFor)
        hints.html(hints.html() + '<br /><span class="red">' + youEditFieldFor + '</span>');

    var html = "";
    var nb_notifs = 0;
    var wrapper_id = "";
    var type = new Array();

    $('.notification.dropdown-toggle').on('click', function (event) {
        $(this).parent().toggleClass('open');
    });

    $('body').on('click', function (e) {

        if (!$('#notification.dropdown').is(e.target)
            && $('#notification.dropdown').has(e.target).length === 0
            && $('.open').has(e.target).length === 0
        ) {
            if ($('#notification.dropdown').hasClass('open')) {
                getPush();
            }
            $('#notification.dropdown').removeClass('open');
        }
    });

    // call it once immediately, then use setTimeout
    getPush();

});


function getPush() {

    $.ajax({
        type: 'POST',
        headers: {"cache-control": "no-cache"},
        url: baseAdminDir + 'ajax.php?rand=' + new Date().getTime(),
        async: true,
        cache: false,
        dataType: 'json',
        data: {"getNotifications": "1"},
        success: function (json) {
            if (json) {
                // Set moment language
                moment.lang(full_language_code);


                var nbCitasMessages = parseInt(json.citas.total);
                var notifications_total = nbCitasMessages;

                // Add orders notifications to the list
                html = "";
                $.each(json.citas.results, function (property, value) {
                    html += "<a class='notif' href='" + baseAdminDir + "index.php?tab=AdminReservarCita&token=" + token_admin_citas + "&updatereservar_cita&id_reservar_cita=" + parseInt(value.id_reservar_cita) + "'>";
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


                if (notifications_total > 0) {
                    $("#total_notif_number_wrapper").removeClass('hide');
                    $('#total_notif_value').text(notifications_total);
                } else {
                    $("#total_notif_number_wrapper").addClass('hide');
                }
            }
            setTimeout("getPush()", 120000);
        }
    });
}
