Vue.directive('tooltip', function(el, binding){
    $(el).tooltip({
        title: binding.value,
        placement: binding.arg,
        trigger: 'hover'
    })
});

Vue.directive('chosen', {
    bind: function (el, binding, vnode, oldVnode) {
        Vue.nextTick(function() {
            $(el).chosen({
                disable_search_threshold: 1,
                search_contains: true,
                width: '40%',
            }).change(function(e){
                vnode.data.on.change(e, $(el).val())
            });
        });
    },
    componentUpdated: function (el, binding, newVnode, oldVnode) {
        $(el).trigger("chosen:updated");
    }
});

Vue.component('select2-basic', {
    props: ['options', 'value'],
    template: ' <select><slot></slot></select>',
    mounted: function () {
        var vm = this
        $(this.$el)
        // init select2
            .select2({
                allowClear: true,
                placeholder :'Seleccione Colaborador',
                data: this.options,
            })
            .val(this.value)
            .trigger('change')
            // emit event on change.
            .on('change', function () {
                vm.$emit('input', this.value);

                var value = $(this).select2('data');
                if (value.length){
                    // nos devuelve un array
                    // console.log(value);
                    // ahora simplemente asignamos el valor a tu variable selected de VUE
                    Vue.set(app_vender, 'colaborador_name', value[0].text);
                }else{
                    Vue.set(app_vender, 'colaborador_name', "");
                }
            })

        // $(this.$el).on('select2:open', function (e) {
        //     $('body #content').append("<div class='overlay_ache'></div>");
        //     $('.select2-dropdown--below').addClass("overlay");
        // });
        // $(this.$el).on('select2:close', function (e) {
        //     $('body #content .overlay_ache').remove();
        // });

        // https://github.com/ColorlibHQ/AdminLTE/issues/802
        if (deviceType !== "computer"){
            // gaurav jain: quick fix for select2 not closing on mobile devices
            $(this.$el).on("select2:close", function () {
                setTimeout(function () {
                    $('.select2-container-active').removeClass('select2-container-active');
                    $(':focus').blur();
                }, 1);
            });

            // gaurav jain: quick fix for select2 not opening on mobile devices if with textbox
            $(this.$el).on('select2:open', function () {
                $('.select2-search__field').prop('focus', false);
            });
        }


    },
    watch: {
        value: function (value) {
            // update value
            $(this.$el)
                .val(value)
                .trigger('change')
        },
        options: function (options) {
            // update options
            $(this.$el).empty().select2({ data: options })
        }
    },
    destroyed: function () {
        $(this.$el).off().select2('destroy')
    }
});

Vue.component('selectdos', {
    template: ' <select :name="name" :id="identifier" class="form-control">\n' +
        '        <option v-for="selecteditem in selecteditems" :key="selecteditem.id" :value="selecteditem.id" v-text="selecteditem.text" >\n' +
        '        </option>\n' +
        '    </select>',
    props: ['selecteditems', 'identifier', 'text', 'name', 'url'],
    data() {
        return {
            ajaxOption: {
                url: this.url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var query = {
                        q: params.term
                    }
                    // Query parameters will be ?search=[term]&type=public
                    return query;
                },
                processResults: function (data) {
                    if (data.found){
                        return {
                            results: data.products
                            // results: $.map(data, function (obj) {
                            // 	return {
                            // 		id: obj.id_product,
                            // 		text: obj.name,
                            // 	};
                            // })
                        };
                    }else{
                        return {
                            results: $.map(data, function (obj) {
                            	return {
                            		id: "",
                            		text: "No se encontraron resultados",
                            	};
                            })
                        }
                    }


                },
                cache: false,

            },
        }
    },
    created(){

    },
    mounted(){
        let self = this;
        // $('#' + self.identifier).select2({
        $(this.$el).select2({
            // delay: 250,
            allowClear: true,
            placeholder: 'Busque producto',
            ajax: self.ajaxOption,
            minimumInputLength: 3,
            templateResult: self.templateResult,
            templateSelection: self.formatSelection,

            language: {
                errorLoading: function () {
                    return "La carga falló"
                }, inputTooLong: function (e) {
                    var t = e.input.length - e.maximum, n = "Por favor, elimine " + t + " car";
                    return t == 1 ? n += "ácter" : n += "acteres", n
                }, inputTooShort: function (e) {
                    var t = e.minimum - e.input.length, n = "Por favor, introduzca " + t + " car";
                    return t == 1 ? n += "ácter" : n += "acteres", n
                }, loadingMore: function () {
                    return "Cargando más resultados…"
                }, maximumSelected: function (e) {
                    var t = "Sólo puede seleccionar " + e.maximum + " elemento";
                    return e.maximum != 1 && (t += "s"), t
                }, noResults: function () {
                    return "No se encontraron resultados"
                }, searching: function () {
                    return "Buscando…"
                }
            },
            escapeMarkup: function (text) { return text; },
        })
            .val(this.value)
            .trigger('change')
            // emit event on change.
            .on('change', function (e) {
                self.$emit('input', this.value);
                self.$emit('change', e);

                var value = $(this).select2('data');
                if (value.length){
                    // nos devuelve un array
                    // console.log(value);
                    // ahora simplemente asignamos el valor a tu variable selected de VUE
                    Vue.set(app_vender, 'product_name', value[0].name);
                    Vue.set(app_vender, 'cantidad_real', value[0].quantity);
                    Vue.set(app_vender, 'precio_unitario', value[0].price_tax_incl);
                    Vue.set(app_vender, 'es_servicio', parseInt(value[0].is_virtual) === 1 );
                }else{
                    Vue.set(app_vender, 'product_name', "");
                    Vue.set(app_vender, 'cantidad_real', 0);
                    Vue.set(app_vender, 'precio_unitario', 0);
                    Vue.set(app_vender, 'es_servicio', false );
                }

            });
    },
    methods : {
        templateResult(repo) {
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
                        '<div class="select2-result-repository__forks"><i class="fa fa-list-ol"></i> </div>' +
                        '<div class="select2-result-repository__stargazers"><i class="fa fa-money"></i></div>' +
                    '</div>' +
                "</div>" +
                "</div>"
            );

            $container.find(".select2-result-repository__title").text(repo.name);
            // $container.find(".select2-result-repository__description").text(repo.reference);

            if (parseInt(repo.is_virtual) === 1){
                // $container.find(".select2-result-repository__forks").remove();
                $container.find(".select2-result-repository__forks").html("SERVICIO");
            }else{
                $container.find(".select2-result-repository__forks").append("&nbsp;Stock "+ repo.quantity);

            }

            $container.find(".select2-result-repository__stargazers").append("&nbsp;Precio "+ repo.formatted_price);
            // $container.find(".select2-result-repository__watchers").append(repo.watchers_count + " Watchers");

            return $container;
        },
        formatSelection(repo) {
            // console.log(repo);
            return repo.name || repo.text;
        },
    },
    destroyed: function () {
        let self = this;
        $('#' + self.identifier).select2('destroy');
    },
});


Vue.component('datepicker', {
    template: '<input name="date" type="text" autocomplete="off" placeholder="Seleccionar fecha" class="form-control">',
    mounted: function() {
        const self = this;
        $(this.$el).datepicker({
            autoclose: true,
            startView: 'years',
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+0", // last hundred years
            onSelect: function(dateText) {
                self.$emit('input', dateText);
            },
        });
    },
    destroyed: function () {
        $(this.$el).datepicker('destroy');
    },
});

Vue.component('my-currency-input', {
    props: ["value"],
    template: `<input type="text" class="form-control" v-model="displayValue" @blur="isInputActive = false" @focus="isInputActive = true"  @keyup="keyupInput"/>`,
    data: function() {
        return {
            isInputActive: false
        }
    },
    computed: {
        displayValue: {
            get: function() {
                if (this.isInputActive) {
                    // Cursor is inside the input field. unformat display value for user
                    return this.value.toString()
                } else {
                    // User is not modifying now. Format display value for user interface
                    return "S/ " + this.value.toFixed(3).replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1,")
                    // return "S/ " + this.value
                }
            },
            set: function(modifiedValue) {
                // Recalculate value after ignoring "$" and "," in user input
                let newValue = parseFloat(modifiedValue.replace(/[^\d\.]/g, ""))
                // Ensure that it is not NaN
                if (isNaN(newValue)) {
                    newValue = 0
                }
                // Note: we cannot set this.value as it is a "prop". It needs to be passed to parent component
                // $emit the event so that parent component gets it
                this.$emit('input', newValue);
            }
        },
    },
    methods: {
        keyupInput(){
            this.$emit('keyup', this.value);
        },
        updateInput() {

        }
    }
});

Vue.directive('focus', {
    inserted: function (el, binding, vnode) {
        Vue.nextTick(function() {
            el.focus();
            el.select();
            // highlight(-1);
            // vnode.context.indx_selected = -1;
        })
    }
});


var app_vender = new Vue({
    el: '#app_vender',
    data() {
        return {
            colaboradores: colaboradores,
            perfil_empleado_vue: perfil_empleado,
            show_forma_pago: false,
            guardandoEnviar: false,
            search: "",
            //producto
            id_product: 0,
            product_name: "",
            cantidad_real: 0,
            precio_unitario: 0,
            es_servicio: false,
            //colaborador
            id_colaborador: 0,
            colaborador_name: "",
            cart: [],
            pagos: [{
                id_metodo_pago: 1,
                tipo: 'efectivo',
                name_pay: "Pago en Efectivo",
                fecha: $.datepicker.formatDate('yy-mm-dd', new Date()),
                monto: 0,
            }],
            total: 0,
            active_codigo_barras: 0,

            //pagination
            pagination: [],
            total_prod: 0,
            page: 1,

            pagina_fin: 1,

            //botones
            is_press_pagar: false,
            is_active_tab_pago: false,

            //facturacion

            hasComprobante: false,
            tipo_comprobante: "",
            numero_comprobante: "",

            //textos y titulos
            textDeudaVuelto: "Deuda",

            //datos devueltos al guardar
            order: [],
            id_colaborador_general: 0,
            colaborador_name_general: "",

            //datos del cliente
            mostrar_form_cliente: false,
            puntos_cliente: 0,
            id_customer: 1,
            cb_tipo_documento: 1,
            fecha_nacimiento: "",
            celular_cliente: "",
            // cliente: "",
            nombre_legal: "",
            tipo_doc: "",
            cod_sunat: "",
            numero_doc: "",
            direccion_cliente: "No Definido",

            //errores
            bloquear_error: false,
            mostrar_adventencia: false,
            msg_errores: [],
            msg_success: [],
            enviadoSunat: false,
            email_cliente_envio: "",

            monto_deuda: 0,

            //////
            order_bycliente: []
        };
    },
    ready: function() {
        // $('[data-toggle="tooltip"]').tooltip();
    },
    created: function(){
        let self = this;


    },
    computed: {

        deudaItem: function(){
            let sum_pagos = 0;
            let deuda_vuelto;
            this.pagos.forEach(function(item) {
                sum_pagos += parseFloat(item.monto);
            });

            deuda_vuelto = this.total - sum_pagos;
            if (deuda_vuelto < 0){
                this.textDeudaVuelto = "Vuelto";

            }else{
                this.textDeudaVuelto = "Deuda";

            }
            this.monto_deuda = deuda_vuelto;
            // if (deuda_vuelto === 0) this.bloquear_error = false;

            return deuda_vuelto ? Math.abs(deuda_vuelto) : 0;
        },
        totalItem: function(){
            let sum = 0;
            this.cart.forEach(function(item) {
                sum += (parseFloat(item.price) * item.quantity);
            });

            return sum;
        },
    },
    methods: {
        enviarMailComprobanteCliente: function(){
            alert("aun no funciona");
        },
        changePrecioUnitario: function(item){
            item.importe_linea = ps_round((parseFloat(item.price) * parseFloat(item.quantity)), 2);
            this.refreshTotal();
        },
        changeImporte: function(item){
            item.price = ps_round((parseFloat(item.importe_linea) / parseFloat(item.quantity)), 3);
            this.refreshTotal();
        },
        filterKey(e){
            const key = e.key;

            // If is '.' key, stop it
            if (key === '.')
                return e.preventDefault();

            // OPTIONAL
            // If is 'e' key, stop it
            if (key === 'e')
                return e.preventDefault();
        },
        // This can also prevent copy + paste invalid character
        filterInput(e){
            e.target.value = e.target.value.replace(/[^0-9]+/g, '');
        },
        triggerBuscarSunat () {
            this.$refs.enterBuscarSunat.click()
        },
        verificarCliente(){
            // console.log(this.nombre_legal.length);
          this.bloquear_error = this.nombre_legal.length < 4;
          if (this.hasComprobante && this.tipo_comprobante === 'Factura' && this.numero_doc.length === 8){
              this.msg_errores = [];
              this.mostrarErrores();
              this.msg_errores.push({
                  msg: "Debe indicar un cliente con RUC"
              });
          }
        },
        borrarErrores(){
            this.mostrar_adventencia = false;
            this.bloquear_error = false;
            this.msg_errores = [];
        },
        mostrarErrores(){
            this.mostrar_adventencia = true;
            this.bloquear_error = true;
        },
        limitText(field, maxChar){
            $(field).attr('maxlength',maxChar);
        },
        changeTipoDocumento: function(e){

            let value = parseInt(e.target.options[e.target.options.selectedIndex].dataset.codsunat);
            if (value === 1) {
                this.limitText('#clientes_search', 8);
            } else if(value === 4) {
                this.limitText('#clientes_search', 15);
            }else if(value === 6) {
                this.limitText('#clientes_search', 11);
            }

        },
        changeMetodoPago: function(e, pago){
            if(e.target.options.selectedIndex > -1) {
                // console.log(e.target.options[e.target.options.selectedIndex].dataset.tipo)
                pago.tipo = e.target.options[e.target.options.selectedIndex].dataset.tipo;
                if (e.target.options[e.target.options.selectedIndex].dataset.tipo === "efectivo") {
                    pago.name_pay = "Pago en Efectivo";
                }
                else if (e.target.options[e.target.options.selectedIndex].dataset.tipo === "visa") {
                    pago.name_pay = "Pago con Visa";
                }else{
                    pago.name_pay = "Pago con Izipay";
                }
            }

        },
        activarComprobante: function(tipo){
            if (tipo === "Eliminar"){
                this.hasComprobante = false;
                this.tipo_comprobante = "";

               this.borrarErrores();
            }else{
                this.hasComprobante = true;
                this.tipo_comprobante = tipo;

                this.$refs.numero_doc.focus();

                if (this.id_customer === 1 && tipo === 'Factura'){
                    this.mostrarErrores();
                    this.msg_errores.push({
                        msg: "Una FACTURA debe tener un cliente",
                    });
                    this.msg_errores.push({
                        msg: "Debe indicar un cliente con RUC"
                    });
                }

                if (this.id_customer !== 1 && parseInt(this.cod_sunat) === 1 && tipo === 'Factura'){
                    this.mostrarErrores();
                    this.msg_errores.push({
                        msg: "Debe indicar un cliente con RUC"
                    })
                }

                if (this.hasComprobante && this.tipo_comprobante === 'Factura' && this.numero_doc.length === 8){
                    this.borrarErrores();
                    this.mostrarErrores();
                    this.msg_errores.push({
                        msg: "Debe indicar un cliente con RUC"
                    })
                }

            }

        },
        setFocus: function() {
            // Note, you need to add a ref="search" attribute to your input.
            this.$refs.search.select();
        },
        refreshTotal() {
            let self = this;
            let total_temporal = 0;
            for (let i = 0; i < this.cart.length; i++) {
                total_temporal += parseFloat(this.cart[i].importe_linea);
            }
            // alert(this.total);
            this.total = ps_round(total_temporal, 2);
            // this.pagos[0].monto = ps_round(this.total, 2);


        },
        addItem(){
            let self = this;
            if ((self.es_servicio && self.id_colaborador) || (!self.es_servicio && self.id_product)){
                // Increment total price
                this.total += parseFloat(self.precio_unitario);

                let inCart = false;
                // Update quantity if the item is already in the cart
                for(let i = 0; i < this.cart.length; i++){
                    if(this.cart[i].id === self.id_product){
                        inCart = true;
                        this.cart[i].quantity++;
                        $.growl.notice({ title: 'Prod. Agregado!', message: '', duration: 500, location: 'br' });
                        this.limpiarDatosAdd();
                        break;
                    }
                }

                // Add item if not already in the cart
                if(! inCart){
                    this.id_colaborador_general = self.id_colaborador;
                    this.colaborador_name_general = self.colaborador_name;
                    this.cart.push({
                        id: self.id_product,
                        title: self.product_name,
                        price: parseFloat(self.precio_unitario),
                        price_temporal: parseFloat(self.precio_unitario),
                        quantity: 1,
                        es_servicio: self.es_servicio ? 1 : 0,
                        cantidad_fisica: self.cantidad_real,
                        importe_linea: parseFloat(self.precio_unitario),
                        importe_linea_temporal: parseFloat(self.precio_unitario),
                        id_colaborador: self.id_colaborador,
                        colaborador_name: self.colaborador_name,
                    });
                    $.growl.notice({ title: 'Prod. Agregado!', message: '', duration: 1000, location: 'br' });
                    this.limpiarDatosAdd();
                    //actualizar monto de pago
                    // this.pagos[0].monto = ps_round(this.total, 2);

                    // }else{
                    //     $.growl.error({ title: 'Alerta!', message: 'No hay stock', location: 'br' });
                    // }

                    this.refreshTotal();


                }

                // this.search = "";
                // this.setFocus();
            }else{
                if (self.es_servicio && !self.id_colaborador) {
                    $.growl.error({title: 'Seleccione un colaborador!', message: '', duration: 1000, location: 'br'});
                }
            }

        },
        limpiarDatosAdd(){
            let self = this;
            self.id_product = 0;
            self.product_name = "";

            $('#id_product').empty().trigger('change');

            self.cantidad_real = 0;
            self.precio_unitario = 0;
            self.es_servicio = false;
            // self.id_colaborador = 0;
            // self.colaborador_name = "";
        },
        changeCantidad(item){
            this.total = 0;
            //truncar a 1 decimal
            item.quantity = item.quantity ? item.quantity.toString().match(/^-?\d+(?:\.\d{0,1})?/)[0] : item.quantity;

            item.importe_linea = ps_round((item.price * item.quantity), 2);

            this.refreshTotal();


        },
        borrarProducto (item) {

            this.total -= item.importe_linea;

            for( let i = 0; i < this.cart.length; i++){
                if(this.cart[i].id === item.id){
                    this.cart.splice(i, 1);
                    break;
                }
            }


            this.refreshTotal();

        },
        agregarVenta(tipo_venta){
            let self = this;
            self.borrarErrores();
            if (self.id_customer === 1 && self.tipo_comprobante === 'Factura'){
                self.mostrarErrores();
                self.msg_errores.push({
                    msg: "Una FACTURA debe tener un cliente",
                });
                self.msg_errores.push({
                    msg: "Debe indicar un cliente con RUC"
                });

                return false;
            }

            if (self.id_customer !== 1 && parseInt(self.cod_sunat) === 1 && self.tipo_comprobante === 'Factura'){
                self.mostrarErrores();
                self.msg_errores.push({
                    msg: "Debe indicar un cliente con RUC"
                });
                return false;
            }

            //tipo venta
            // 1 SIN PAGO
            // 2 PAGAR, ENVIAR A SUNAT E IMPRIMIR
            // 3 PAGAR, ENVIAR A SUNAT Y NUEVO

            // console.log(self.cart)
            // if (self.cart.length && self.nombre_legal && self.numero_doc){
            if (self.cart.length){
                $.ajax({
                            type:"POST",
                            url: url_ajax_vender,
                            async: true,
                            dataType: "json",
                            data:{
                                ajax: "1",
                                token: token_vender,
                                action : "realizarVenta",
                                productos: self.cart,
                                tipo_venta: tipo_venta,
                                hasComprobante: self.hasComprobante,
                                tipo_comprobante: self.tipo_comprobante,
                                id_customer: self.id_customer,
                                fecha_nacimiento: self.fecha_nacimiento,
                                cb_tipo_documento: self.cb_tipo_documento,
                                celular_cliente: self.celular_cliente,
                                nombre_legal: self.nombre_legal,
                                numero_doc: self.numero_doc,
                                direccion_cliente: self.direccion_cliente,
                                array_pagos: self.pagos,
                                puntos_cliente: self.puntos_cliente,
                                id_colaborador_general: self.id_colaborador_general,
                                colaborador_name_general: self.colaborador_name_general,
                            },
                            beforeSend: function(){
                                self.guardandoEnviar = true;
                                $('body').waitMe({
                                    effect: 'bounce',
                                    text: 'Guardando...',
                                    color: '#000',
                                    maxSize: '',
                                    textPos: 'vertical',
                                    fontSize: '',
                                    source: ''
                                });
                            },
                            success: function (data) {
                                self.guardandoEnviar = false;
                                if (data.result === 'error'){
                                    $.each(data.msg, function (index, value) {
                                        self.mostrar_adventencia = true;
                                        self.msg_errores.push({
                                            msg: value,
                                        })
                                    })
                                }
                                if (data.response === 'ok'){
                                    // if (data.reload === 'ok'){
                                    //     location.reload();
                                    // }
                                    self.order = data.order;
                                    let html_buttons = '';

                                    if (self.perfil_empleado_vue !== 'Colaborador'){
                                        html_buttons += '<a class="btn btn-primary" style="margin: 5px;" target="_blank" href="'+data.link_venta+'">Venta</a>';
                                    }


                                    html_buttons += '<input type="button" class="btn btn-warning" value="Ticket Venta - '+data.order.nro_ticket+'" style="margin: 5px;" onclick="windowPrintAche(\'PDFtoTicket\')">';
                                    let iframes = '<iframe id="PDFtoTicket" src="'+data.order.ruta_ticket_normal+'" style="display: none;"></iframe>';

                                    $('#alertmessage').after(iframes);
                                    $('.alertmessage').append(html_buttons);
                                    $('.alertmessage').css('display', 'grid');

                                    $("#toolbar_caja_soles").fadeOut("slow", function() {
                                        $(this).text(data.caja_actual.monto_operaciones).fadeIn("slow");
                                    });

                                    self.cart = [];
                                    self.is_active_tab_pago = false;
                                    $('#left-panel').css('pointer-events', 'none');
                                    $('.sales-add-edit-payments').css('pointer-events', 'none');
                                    $('.tabla_lista_venta').css('pointer-events', 'none');

                                    if (self.hasComprobante){
                                        $.growl.warning({
                                            title: '',
                                            message: 'Generando y Enviando XML del comprobante Por Favor espere Un Momento...!',
                                            fixed: true,
                                            size: "large",
                                            duration: 5000,
                                            location: 'tl'
                                        });
                                        self.enviarComprobanteSunat();
                                    }
                                }

                            },
                            error: function (error) {
                                console.log(error);
                            },
                            complete: function(data) {
                                // location.reload();
                                $('body').waitMe('hide');

                            },
                        });
            }else{
                $.growl.error({ title: 'No existen productos para vender!', message: '',});
            }
        },
        enviarComprobanteSunat(){
            let self = this;
            if (!self.order){
                $.growl.error({ title: 'Error al enviar!', message: '',});
            }else{
                $.ajax({
                    type:"POST",
                    url: url_ajax_vender,
                    async: true,
                    dataType: "json",
                    data:{
                        ajax: "1",
                        token: token_vender,
                        action : "enviarSunat",
                        id_order: self.order.id,
                        tipo_comprobante: self.tipo_comprobante,
                    },
                    beforeSend: function(){
                        self.guardandoEnviar = true;
                        $('body').waitMe({
                            effect: 'bounce',
                            text: 'Enviando...',
                            color: '#000',
                            maxSize: '',
                            textPos: 'vertical',
                            fontSize: '',
                            source: ''
                        });
                        // alert("DFDF");
                    },
                    success: function (data) {
                        if (data.result === 'error'){
                            self.msg_errores = [];
                            $.each(data.msg, function (index, value) {
                                self.mostrar_adventencia = true;
                                self.msg_errores.push({
                                    msg: value,
                                });
                            })
                        }
                        if (data.result === 'ok'){
                            self.msg_success = [];
                            $.each(data.msg, function (index, value) {
                                self.msg_success.push({
                                    msg: value,
                                });
                            })
                        }

                        if (data.comprobantes){
                            let iframes = '';
                            let html_buttons = '';
                            $.each(data.comprobantes, function (index, value) {
                                self.numero_comprobante = value.numero_comprobante;
                                if (this.ruta_ticket !== ""){
                                    iframes += `<iframe id="PDFtoTicketComp`+this.id_pos_ordercomprobantes+`" src="`+this.ruta_ticket+`" style="display: none;"></iframe>`;
                                    html_buttons += '<input type="button" class="btn btn-warning" value="Ticket '+self.tipo_comprobante+'" style="margin: 5px;" onclick="windowPrintAche(\'PDFtoTicketComp'+this.id_pos_ordercomprobantes+'\')">';
                                }

                                if (this.ruta_pdf_a4 !== "") {
                                    iframes += `<iframe id="PDFtoA4Comp` + this.id_pos_ordercomprobantes + `" src="` + this.ruta_pdf_a4 + `" style="display: none;"></iframe>`;
                                    html_buttons += '<input type="button" class="btn btn-warning" value="A4 '+self.tipo_comprobante+'" style="margin: 5px;" onclick="windowPrintAche(\'PDFtoA4Comp'+this.id_pos_ordercomprobantes+'\')">';
                                }

                                if (this.ruta_xml !== "") {
                                    html_buttons += '<a type="button" target="_blank" class="btn btn-warning" href="'+this.ruta_xml+'" style="margin: 5px;">Descargar XML</a>';
                                }
                                if (this.ruta_cdr !== "") {
                                    html_buttons += '<a type="button" target="_blank" class="btn btn-warning" href="'+this.ruta_cdr+'" style="margin: 5px;">Descargar CDR</a>';
                                }



                            });
                            $('#alertmessage').after(iframes);
                            $('.alertmessage').append(html_buttons);
                        }



                        if(parseInt(data.estado_envio_sunat) === 1 && parseInt(data.cod_sunat) === 0){
                            self.enviadoSunat = true;
                        }else{
                            self.enviadoSunat = false;
                        }
                    },
                    error: function (error) {
                        // console.log(error);
                    },
                    complete: function(data) {
                        $('body').waitMe('hide');
                        self.guardandoEnviar = false;
                    },
                });
            }
        },
        buscarCliente(){
            let that = this;
            //do whatever
            $('.error_ache').remove();
            $.ajax({
                type:"POST",
                url: url_ajax_vender,
                async: true,
                dataType: "json",
                data:{
                    ajax: "1",
                    token: token_vender,
                    tab: "AdminVender",
                    action : "SearchClientes",
                    cb_tipo_documento: that.cb_tipo_documento,
                    cliente_search: $.trim(that.numero_doc),
                },
                beforeSend: function(){
                    $('body').waitMe({
                        effect: 'bounce',
                        text: 'Buscando...',
                        color: '#000',
                        maxSize: '',
                        textPos: 'vertical',
                        fontSize: '',
                        source: ''
                    });
                },
                success: function (data) {
                    that.bloquear_error = false;
                    if (data.msg){
                        if (parseInt(that.cb_tipo_documento) !== 2) {
                            that.buscarEnSunat();
                        }else{
                            $('.v-autocomplete').after('<small style="color: red;" class="error_ache">Cliente no encontrado en SUNAT (Llene los campos)</small>');
                            that.mostrar_form_cliente = true;
                            that.id_customer = 0;
                            that.bloquear_error = true;
                            // that.numero_doc = that.cliente;
                            that.show_forma_pago = false;
                        }
                    }else{
                        that.fillCustomer(data.result);
                        if (data.order){
                            that.order_bycliente = data.order;
                            // that.id_colaborador = data.order.id_colaborador;
                        }
                        if (data.reservas.length){
                            let html = `
                                <!-- Modal -->
                                <div id="moda_reserva_cliente" class="modal fade" role="dialog">
                                  <div class="modal-dialog modal-lg">
                                    <!-- Modal content-->
                                    <div class="modal-content">
                                      <div class="modal-header">
                                        <h4 class="modal-title">Reservas Pendientes - Cliente: `+data.result.firstname+`</h4>
                                      </div>
                                      <div class="modal-body">
                                      <table class="table">
                                          <thead>
                                              <tr>
                                                  <td>Fecha</td>
                                                  <td>Hora</td>
                                                  <td>Colaborador</td>
                                                  <td>Servicio</td>
                                                  <td>Adelanto</td>
                                                  <td>&nbsp;</td>
                                              </tr>
                                          </thead>
                                          <tbody>
                                   `;
                            var date_moment = moment(); //Get the current date
                            $.each(data.reservas, function (indx, val) {
                                html += `
                                    <tr>
                                        <td>`+moment(val.fecha_inicio).format('DD/MM/YYYY')+`</td>
                                        <td>`+val.hora+`</td>
                                        <td>      
                                        <select class="form-control chosen " id="select_colaborador_`+val.id_reservar_cita+`">
                                            <option value="">- Seleccione Colaborador -</option>`;
                                    $.each(colaboradores, function (indx2, val2) {
                                        if (val.id_colaborador === val2.id){
                                            html += `
                                            <option value="`+val2.id+`" selected >`+val2.text+`</option>
                                        `;
                                        }else{
                                            html += `
                                            <option value="`+val2.id+`" >`+val2.text+`</option>
                                        `;
                                        }

                                    });
                                html += `</select>
                                           </td>
                                        <td>`+val.product_name+`</td>
                                        <td>S/`+val.adelanto+`</td>
                                        <td>
<!--                                         <button style="margin: 3px;" class="btn btn-danger pull-right" onclick="anularVentaReserva(`+val.id_reservar_cita+`, `+val.id_colaborador+`)"><i class="fa fa-ban"></i> Anular</button>-->
                                        <button style="margin: 3px;" class="btn btn-success pull-right" onclick="pasaVentaReserva(`+val.id_reservar_cita+`)"><i class="fa fa-check"></i> Atender</button>
                                       
                                        </td>
                                    </tr>
                                `;
                            });

                            html += `
                                    </tbody>
                                    </table>
                                      </div>
                                      <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                            `;
                            $('#moda_reserva_cliente').remove();
                            $('#app_vender').append(html);
                            $('#moda_reserva_cliente').modal({ backdrop: 'static' });
                            $('#moda_reserva_cliente').modal('show');
                            $('#moda_reserva_cliente select.chosen').each(function(k, item){
                                $(item).chosen({ search_contains: true, width: '100%', });
                            });
                        }
                    }
                },
                error: function (error) {
                    // console.log(error);
                },
                complete: function (data) {
                    $('body').waitMe('hide');
                }
            });
        },
        buscarEnSunat(){
            let that = this;
            //do whatever
            $.ajax({
                type:"POST",
                url: url_ajax_vender,
                async: true,
                dataType: "json",
                data:{
                    ajax: "1",
                    token: token_vender,
                    tab: "AdminVender",
                    action : "getDataSunat",
                    nruc: $.trim(that.numero_doc),
                },
                success: function (data) {
                    // console.log(data)
                    if (data.tipo_msg === 'encontrado'){
                        that.fillCustomer(data.cliente);
                    }else if(data.tipo_msg === 'nofound'){
                        $('.v-autocomplete').after('<small style="color: red;" class="error_ache">Cliente no encontrado en SUNAT (Llene los campos)</small>');
                        that.mostrar_form_cliente = true;
                        that.id_customer = 0;
                        that.bloquear_error = true;
                        // that.numero_doc = that.cliente;
                        that.show_forma_pago = false;
                    }else{
                        $('.v-autocomplete').after('<small style="color: red;" class="error_ache">Número de documento no válido</small>');
                        that.mostrar_form_cliente = false;
                        that.bloquear_error = true;
                        that.show_forma_pago = false;
                    }
                },
                error: function (error) {
                    // console.log(error);
                },
                complete: function (data) {
                    $('body').waitMe('hide');

                }
            });
        },
        fillCustomer(data){
            // console.log(data);
            let self = this;
            self.puntos_cliente = data.puntos_acumulados;
            self.id_customer = data.id_customer;
            self.numero_doc = data.num_document;
            // self.cliente = data.firstname +' - '+ data.num_document;
            self.nombre_legal = data.firstname;
            self.numero_doc = data.num_document;
            self.tipo_doc = data.tipo_documento;
            self.cod_sunat = data.cod_sunat;
            self.fecha_nacimiento = data.birthday && data.birthday !== '0000-00-00' ? data.birthday : '';
            self.celular_cliente = data.telefono_celular;
            self.show_forma_pago = parseInt(data.es_credito) === 1;

            if (data.direccion){
                self.direccion_cliente = data.direccion;
            }

            if (parseInt(self.cod_sunat) === 6 && self.tipo_comprobante === 'Factura'){
                self.borrarErrores();
            }

            if (self.id_customer !== 1 && parseInt(self.cod_sunat) === 1 && self.tipo_comprobante === 'Factura'){
                self.borrarErrores();
                self.mostrarErrores();
                self.msg_errores.push({
                    msg: "Debe indicar un cliente con RUC"
                })
            }

        },
        borrarCliente(){
            $('.error_ache').remove();
            let self = this;
            self.id_customer = 1;
            // self.cliente = "";
            self.nombre_legal = "";
            self.numero_doc = "";
            self.tipo_doc = "";
            self.direccion_cliente = "No Definido";
            self.mostrar_form_cliente = false;
            self.show_forma_pago = false;
            this.$refs.numero_doc.focus();


            self.borrarErrores();
            if (self.id_customer === 1 && self.tipo_comprobante === 'Factura'){
                self.mostrarErrores();
                self.msg_errores.push({
                    msg: "Una FACTURA debe tener un cliente",
                });
                self.msg_errores.push({
                    msg: "Debe indicar un cliente con RUC"
                });
            }

        },
        addPayment(){
            let self = this;
            self.pagos.push({
                id_metodo_pago: 0,
                tipo: 'efectivo',
                name_pay: "Pago en Efectivo",
                fecha: $.datepicker.formatDate('yy-mm-dd', new Date()),
                monto: 0,
            });
        },
        borrarPago (item) {

            for( let i = 0; i < this.pagos.length; i++){
                if(this.pagos[i].id_metodo_pago === item.id_metodo_pago){
                    this.pagos.splice(i, 1);
                    break;
                }
            }

        },
        addProductos(order) {
            var self = this;

            $.ajax({
                type:"POST",
                url: url_ajax_vender,
                async: true,
                dataType: "json",
                data:{
                    ajax: "1",
                    token: token_vender,
                    tab: "AdminVender",
                    action : "AddProductOnOrder",
                    order: order,
                    productos: self.cart,
                },
                beforeSend: function(){
                    self.guardandoEnviar = true;
                    $('body').waitMe({
                        effect: 'bounce',
                        text: 'Guardando...',
                        color: '#000',
                        maxSize: '',
                        textPos: 'vertical',
                        fontSize: '',
                        source: ''
                    });
                },
                success: function (data) {
                    self.guardandoEnviar = false;
                    if (data.success === 'ok'){
                        $.growl.notice({ title:data.result, message:'' });
                        self.order = data.order;
                        let html_buttons = '';
                        if (self.perfil_empleado_vue !== 'Colaborador'){
                            html_buttons += '<a class="btn btn-primary" style="margin: 5px;" target="_blank" href="'+data.link_venta+'">Venta</a>';
                        }


                        html_buttons += '<input type="button" class="btn btn-warning" value="Ticket Venta - '+data.order.nro_ticket+'" style="margin: 5px;" onclick="windowPrintAche(\'PDFtoTicket\')">';
                        let iframes = '<iframe id="PDFtoTicket" src="'+data.order.ruta_ticket_normal+'" style="display: none;"></iframe>';

                        $('#alertmessage').after(iframes);
                        $('.alertmessage').append(html_buttons);
                        $('.alertmessage').css('display', 'grid');

                        self.cart = [];
                        self.is_active_tab_pago = false;
                        $('#left-panel').css('pointer-events', 'none');
                        $('.sales-add-edit-payments').css('pointer-events', 'none');
                        $('.tabla_lista_venta').css('pointer-events', 'none');
                    }else{
                        $.growl.error({ title:data.result, message:'' })
                    }


                },
                error: function (error) {
                    // console.log(error);
                },
                complete: function (data) {
                    $('body').waitMe('hide');
                }
            });

        },
    },
    mounted() {
        let self = this;
        $('#app_vender').addClass('loaded');
    },
    updated(){

    },
    filters: {
        moneda_ache: function (price) {
            // return 'S/ ' + parseFloat(price).toFixed(4);
            return 'S/ ' + ps_round(parseFloat(price), 2);
        },
        num_entero: function (cant) {
            return parseInt(cant);
        }
    }
});


function windowPrintAche(id_selector){
    var ua = navigator.userAgent.toLowerCase();
    var iframe = document.getElementById(id_selector);
    var msie = ua.indexOf ("msie");
    if( navigator.userAgent.match(/Android/i)
        || navigator.userAgent.match(/webOS/i)
        || navigator.userAgent.match(/iPhone/i)
        || navigator.userAgent.match(/iPad/i)
        || navigator.userAgent.match(/iPod/i)
        || navigator.userAgent.match(/BlackBerry/i)
    ){
        var url = iframe.src;
        var tabOrWindow = window.open(url, '_blank');
        tabOrWindow.focus();
    }
    else{

        iframe.focus();
        if (msie > 0) {
            iframe.contentWindow.document.execCommand('print', false, null);
        } else {
            iframe.contentWindow.print();
        }
    }
}

function pasaVentaReserva(id) {

    let id_colaborador = $('#select_colaborador_'+id+' :selected').val();
    var x = confirm("¿Seguro de crear la reserva?");
    if (x){
        if (parseInt(id_colaborador) > 0){
            $.ajax({
                type:"POST",
                url: url_ajax_reservas,
                async: true,
                dataType: "json",
                data:{
                    ajax: "1",
                    token: token_reservas,
                    action : "realizarVenta",
                    id_reservar_cita: id,
                    id_colaborador: id_colaborador,
                },
                beforeSend: function(){
                    $('body').waitMe({
                        effect: 'bounce',
                        text: 'Guardando...',
                        color: '#000',
                        maxSize: '',
                        textPos: 'vertical',
                        fontSize: '',
                        source: ''
                    });
                },
                success: function (data) {
                    if (data.response === 'ok'){

                        // window.location.href = url_ajax_reservas+"&updatereservar_cita&id_reservar_cita="+ data.objCita.id;
                        // $('body').waitMe('hide');
                        // location.reload();

                        let html_buttons = '';

                        if (perfil_empleado !== 'Colaborador'){
                            html_buttons += '<a class="btn btn-primary" style="margin: 5px;" target="_blank" href="'+data.link_venta+'">Venta</a>';
                        }

                        html_buttons += '<input type="button" class="btn btn-warning" value="Ticket Venta - '+data.order.nro_ticket+'" style="margin: 5px;" onclick="windowPrintAche(\'PDFtoTicket\')">';
                        let iframes = '<iframe id="PDFtoTicket" src="'+data.order.ruta_ticket_normal+'" style="display: none;"></iframe>';
                        $('#alertmessage').after(iframes);
                        $('.alertmessage').append(html_buttons);
                        $('.alertmessage').css('display', 'grid');
                        $('body').waitMe('hide');
                        $('#moda_reserva_cliente').modal('hide');
                    }
                    if (data.response === 'failed'){
                        $('#error').text(data.msg);
                        $('#error').show();
                    }
                },
                error: function (error) {
                    console.log(error);
                },
                complete: function(data) {

                },
            });
        }else{
            $.growl.error({ title: "", message:"Tiene que seleccionar un colaborador"});
        }
    }else{
        return false;
    }
}

function anularVentaReserva(id, id_colaborador) {
    var x = confirm("¿Seguro de anular la reserva?");
    if (x){
        $.ajax({
                type:"POST",
                url: url_ajax_reservas,
                async: true,
                dataType: "json",
                data:{
                    ajax: "1",
                    token: token_reservas,
                    action : "anularCita",
                    id_reservar_cita: id,
                    id_colaborador: id_colaborador,
                },
                beforeSend: function(){
                    $('body').waitMe({
                        effect: 'bounce',
                        text: 'Anulando Cita...',
                        color: '#000',
                        maxSize: '',
                        textPos: 'vertical',
                        fontSize: '',
                        source: ''
                    });
                },
                success: function (data) {
                    if (data.response === 'ok'){
                        // window.location.href = url_ajax_reservas+"&updatereservar_cita&id_reservar_cita="+ data.objCita.id;
                        location.reload();
                    }
                },
                error: function (error) {
                    console.log(error);
                },
                complete: function(data) {

                },
            });
    }else{
        return false;
    }
}