{extends file="helpers/list/list_footer.tpl"}
{block name="after"}

    <style>
        .highlight td{
            background-color: #00d0ff!important;
        }

        @media (max-width: 992px) {
            #orderProducts td:nth-of-type(1):before {
                content: "Producto";
            }
            #orderProducts td:nth-of-type(2):before {
                content: "Precio Uni.";
            }
            #orderProducts td:nth-of-type(3):before {
                content: "Cant";
            }
            #orderProducts td:last-child:before {
                content: "Total"!important;
            }
            #orderProducts td:last-child {
                text-align: left!important;
                position: relative;
                padding-left: 35%!important;
                width: 100%!important;
                line-height: 2em!important;
                font-size: 1.15em!important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #orderPayments td:nth-of-type(1):before {
                content: "Fecha";
            }
            #orderPayments td:nth-of-type(2):before {
                content: "Metodo.";
            }
            #orderPayments td:last-child:before {
                content: "Monto"!important;
            }
            #orderPayments td:last-child {
                text-align: left!important;
                position: relative;
                padding-left: 35%!important;
                width: 100%!important;
                line-height: 2em!important;
                font-size: 1.15em!important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

    </style>
    <script>
        // Add event listener for opening and closing details
        $('#table-{$table} tbody').on('click', 'td.details-control', function () {
            var tr = $(this).closest('tr');
            var id_order = parseInt($(this).closest('tr').find('td:eq(0)').text());
            // console.log(tr.html(  ));
            if ( tr.hasClass('shown') ) {
                // This row is already open - close it
                tr.next('tr').remove();
                tr.removeClass('shown');
            }
            else {
                // Open this row
                if (id_order){

                    $.ajax({
                        type:"POST",
                        url: "{$link->getAdminLink('AdminAtenciones')}",
                        async: true,
                        dataType: "json",
                        data : {
                            ajax: "1",
                            token: "{Tools::getAdminTokenLite('AdminAtenciones')}",
                            tab: "AdminAtenciones",
                            action: "getDetailAndPayments",
                            id_order: id_order,
                        },
                        beforeSend: function(){
                            $('body').waitMe({
                                effect: 'timer',
                                text: 'Cargando...',
                                //    bg : rgba(255,255,255,0.7),
                                color: '#000',
                                maxSize: '',
                                textPos: 'vertical',
                                fontSize: '',
                                source: ''
                            });
                        },
                        success : function(res)
                        {
                            let tr_new =  format(res);
                            tr.after(tr_new);
                            tr.addClass('shown');
                        },
                        complete: function (res) {
                            $('body').waitMe('hide');
                        }
                    });
                }else{
                    jAlert('El ID de la orden no existe');
                }

            }
        } );

        var priceDisplayPrecision = 2;
        /* Formatting function for row details - modify as you need */
        function format ( d ) {
            //detalle
            let detalle = "";

            $.each(d.detalle, function () {
                detalle += ` <tr class="product-line-row">
                                <td>
                                    <a>
                                        <span class="productName">`+this.product_name+`</span><br>
                                    </a>
                                </td>
                                <td><span class="product_price_show">`+ formatCurrency(parseFloat(this.unit_price_tax_incl), 3, 'S/', 0) +`</span>
                                </td>
                                <td class="productQuantity text-center"><span class="product_quantity_show">`+parseInt(this.product_quantity)+`</span></td>
                                <td class="total_product">`+formatCurrency(parseFloat(this.total_price_tax_incl), 3, 'S/', 0)+`</td>
                            </tr>`

            });

            //pagos
            let pagos = "";

            $.each(d.pagos, function () {
                pagos += ` <tr class="product-line-row">
                                <td>`+this.date_add+`</td>
                                <td>`+this.payment_method+`</td>
                                <td>`+formatCurrency(parseFloat(this.amount), 3, 'S/', 0)+`</td>
                            </tr>`

            });
            // `d` is the original data object for the row
            return `<tr class="detail_order_preview">
                        <td colspan="100">
                            <div class="row col-lg-8">
                                <h4 style="text-align: left;">Productos <span class="badge">`+d.detalle.length+`</span></h4>
                                 <div class="well table-responsive">
                                    <table class="table" id="orderProducts">
                                        <thead>
                                        <tr>
                                            <th><span class="title_box ">Producto</span></th>
                                            <th><span class="title_box ">Precio Uni.</span><small class="text-muted">Impuestos incluidos</small></th>
                                            <th class="text-center"><span class="title_box ">Cant.</span></th>
                                            <th><span class="title_box ">Total</span><small class="text-muted">Impuestos incluidos</small></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            `+detalle+`
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row col-lg-4">
                                <h4 style="text-align: left;">Pagos <span class="badge">`+d.pagos.length+`</span></h4>
                                <div class="well table-responsive">
                                    <table class="table table-hover" id="orderPayments">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Metodo</th>
                                                <th>Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            `+pagos+`
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>`;
        }

    </script>

{/block}