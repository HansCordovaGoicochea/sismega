<style>
    body{
        background-color: white!important;
        /*overflow: hidden!important;*/
    }
    #content.bootstrap {
        padding: 10px 10px 0 225px;
        -webkit-transition-property: margin;
    }
    .mobile #content.bootstrap {
        padding: 20px 5px 0;
    }

    #contenido {
        /*max-width: 100%;*/
        margin: 5px auto;
        padding: 0 10px;
    }
</style>

<div class="row">
    <h4 class="text-center">CALENDARIO DE CITAS</h4>
    <div class="row" id="contenido">
        <div class="row row-margin-bottom">
            <label for="id_colaborador" class="control-label col-lg-12">{l s='Colaboradores:' d='Admin.Orderscustomers.Feature'}</label>
            <div class="col-lg-3">
                <select name="id_colaborador" id="id_colaborador" class="chosen">
                    <option value="">{l s='-- Elija un Colaborador --' d='Admin.Actions'}</option>
                    {foreach $colaboradores as $employee}
                        <option value="{$employee.id_employee}"> {$employee.firstname} {$employee.lastname}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
    <!-- add calander in this div -->
    <div class="row">
        <div id="calendar"></div>
    </div>

    <!-- Modal  to Add Event -->
    <div id="createEventModal" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h4 class="modal-title">Add Event</h4>
                </div>
                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label" for="inputPatient">Event:</label>
                        <div class="field desc">
                            <input class="form-control" id="title" name="title" placeholder="Event" type="text" value="">
                        </div>
                    </div>

                    <input type="hidden" id="startTime"/>
                    <input type="hidden" id="endTime"/>



                    <div class="control-group">
                        <label class="control-label" for="when">When:</label>
                        <div class="controls controls-row" id="when" style="margin-top:5px;">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitButton">Save</button>
                </div>
            </div>

        </div>
    </div>
</div>
{*<script src="https://unpkg.com/popper.js/dist/umd/popper.min.js"></script>*}
{*<script src="https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js"></script>*}
<script>
    const url_ajax_cita = "{$link->getAdminLink('AdminReservarCita')|addslashes}";
    const token_cita = "{getAdminToken tab='AdminReservarCita'}";
    const initialLocaleCode = 'es-us';
    const calendarEl = document.getElementById('calendar');

    $(document).ready(function(){
        $('.page-head').remove();
    });

    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: [ 'interaction', 'dayGrid', 'timeGrid', 'list' ],
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            // defaultDate: '2019-08-01',
            locale: initialLocaleCode,
            buttonIcons: true, // show the prev/next text
            weekNumbers: false,
            navLinks: true, // can click day/week names to navigate views
            editable: true,
            eventLimit: true, // allow "more" link when too many events
            fixedWeekCount: false, // esto hace que sean solo las semanas del mes
            // defaultView: 'listMonth',
            // selectable: true,
            allDaySlot: false, // quita el slot de todo el dia

            eventSources: [
                getTasks //pass a reference to a function, so we have a dynamic, updateable event source
            ],
            // eventRender: function(info) {
            //     $(info.el).tooltip({
            //         title: info.event.extendedProps.description,
            //         placement: "top",
            //         trigger: "hover",
            //         container: "body"
            //     });
            // },

            eventClick:  function(event, jsEvent, view) {  // when some one click on any event
                endtime = $.fullCalendar.moment(event.end).format('h:mm');
                starttime = $.fullCalendar.moment(event.start).format('dddd, MMMM Do YYYY, h:mm');
                var mywhen = starttime + ' - ' + endtime;
                $('#modalTitle').html(event.title);
                $('#modalWhen').text(mywhen);
                $('#eventID').val(event.id);
                $('#calendarModal').modal();
            },
            dateClick: function(info) {  // click on empty time slot
                // endtime = $.fullCalendar.moment(end).format('h:mm');
                // starttime = $.fullCalendar.moment(start).format('dddd, MMMM Do YYYY, h:mm');
                // var mywhen = starttime + ' - ' + endtime;
                // start = moment(start).format();
                // end = moment(end).format();
                let start = moment(info.dateStr).format();
                $('#createEventModal #startTime').val(start);
                // $('#createEventModal #endTime').val(end);
                // $('#createEventModal #when').text(mywhen);
                $('#createEventModal').modal('toggle');
                // console.log( start);
            },
        });

        $("select[name='id_colaborador']").on('change', function() {
            calendar.refetchEvents(); //this will automatically cause the "getTasks" function to run, because it's associated with an event source in the calendar

        });


        calendar.render();

    });




    var getTasks = function(fetchInfo, successCallback, failureCallback) {
        $.ajax({
            type: 'post',
            url: url_ajax_cita,
            dataType: 'json',
            data: {
                ajax: "1",
                token: token_cita,
                tab: "AdminReservarCita",
                action : "getCitasByColaborador",
                id_colaborador: $("#id_colaborador :selected").val(),
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
            success: function (doc) {
                var events = [];
                if(!!doc.citas) {
                    $.each(doc.citas, function () {
                        events.push({
                            title: this.product_name,
                            start: this.fecha_inicio,
                            color: this.color,   // an option!
                            textColor: getContrastYIQ(this.color) // an option!
                        });
                    });
                }
                successCallback(events); //pass the event data to fullCalendar via the provided callback function

                $('body').waitMe('hide');
            }
        });
    }

</script>