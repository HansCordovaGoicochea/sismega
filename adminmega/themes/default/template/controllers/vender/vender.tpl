<link rel="stylesheet" href="{$tpl_folder}css/vender.css">
<link rel="stylesheet" href="{$tpl_folder}css/loader.css">
<link rel="stylesheet" href="{$tpl_folder}css/content-visibility.css">
<link rel="stylesheet" href="{$tpl_folder}css/miniarrow.css">
<link rel="stylesheet" href="{$tpl_folder}css/select2-ache.css">


<script>
    var deviceType = "{$deviceType}";
    const url_ajax_vender = "{$link->getAdminLink('AdminVender')|addslashes}";
    const token_vender = "{getAdminToken tab='AdminVender'}";
    const perfil_empleado = '{$perfil_empleado}'

    var colaboradores = new Array();
    {foreach $colaboradores as $key => $employee}
        colaboradores[{$key}] = { id: '{$employee.id_employee|intval}', text: '{$employee.firstname|@addcslashes:'\''} {$employee.lastname|@addcslashes:'\''}' };
    {/foreach}


    const url_ajax_reservas = "{$link->getAdminLink('AdminReservarCita')|addslashes}";
    const token_reservas = "{getAdminToken tab='AdminReservarCita'}";

</script>
<br>
<div id="app_vender">
    <!-- Preloader and it's background. -->
    <div id="loader-wrapper">
        <div id="loader"></div>
        <div class="loader-section section-left"></div>
        <div class="loader-section section-right"></div>
    </div>
    <div class="content-area">
        <div class="row-group">
            <div class="content-row">
                <div id="left-panel" class="pos-content payment_div col-sm-12 col-md-4 ">
                    <!-- Tab nav -->
                    <ul class="nav nav-tabs" id="tabProductosCliente" :class="order.id ? 'disabled-pointer-events-ache':''">

                        <li class="nav-item active">
                            <a href="#pagos" :class="cart.length == 0 ? 'disabled':''" >
                                <i class="fa fa-money fa-lg" aria-hidden="true"></i>
                                {l s='Pagos' d='Admin.Orderscustomers.Feature'}
                                /
                                <i class="fa fa-user fa-lg" aria-hidden="true"></i>
                                {l s='Cliente' d='Admin.Orderscustomers.Feature'}
                            </a>
                        </li>
                    </ul>
                    <!-- Tab content -->
                    <div class="tab-content">
                        <!-- Tab pagos -->
                        <div class="tab-pane active" id="pagos">
                           <div class="invoices-container">
                                {*duplicar este div si se necesita otro pago*}
                               <div>
                                   <div class="mb-4 collapse show">
                                        <div class="card">
                                            <div class="card-body">
                                                <div v-if="mostrar_adventencia" role="alert" class="alert alert-danger mb-4">
                                                    <div v-for="(val, index) in msg_errores" v-html="val.msg"></div>
                                                </div>
                                                <div v-if="msg_success.length" role="alert" class="alert alert-success mb-4">
                                                    <div v-for="(val, index) in msg_success" v-html="val.msg"></div>
                                                </div>
                                                <div class="alert alert-info" v-if="puntos_cliente >= 6">
                                                    <div>El cliente tiene mas de 6 puntos y puede reclamar un servicio gratis.</div>
                                                </div>
                                                <div>
                                                    <div class="row" v-if="!hasComprobante && total > 0 && perfil_empleado_vue != 'Colaborador'">
                                                        <div class="col-xs-6 col-lg-6 col-xl-6 text-center mb-3">
                                                            <a href="javascript:void(0)" class="card-link" @click="activarComprobante('Boleta')">
                                                                <i class="fa fa-file fa-lg"></i>&nbsp;&nbsp;Boleta
                                                            </a>
                                                        </div>
                                                        <div class="col-xs-6 col-md-6 col-lg-6 col-xl-6 text-center" @click="activarComprobante('Factura')">
                                                            <a href="javascript:void(0)" class="card-link">
                                                                <i class="fa fa-file fa-lg"></i>&nbsp;&nbsp;Factura
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div v-if="hasComprobante" class="row required mb-4">
                                                        <div class="col-xs-4 col-lg-4 col-xl-4 col-sm-4" v-if="!numero_comprobante">
                                                            <i class="fa fa-file fa-lg"></i>&nbsp;&nbsp;
                                                            <strong v-text="tipo_comprobante"></strong>
                                                        </div>
                                                        <div class="col-xs-4 col-lg-4 col-xl-4 col-sm-4" v-else>
                                                            <a class="card-link" href="javascript:void(0)">
                                                                <i class="fa fa-print fa-lg"></i>
                                                                <strong v-text="tipo_comprobante"></strong>
                                                                <span v-text="numero_comprobante"></span>
                                                            </a>
                                                        </div>
                                                        <div class="col-xs-8 col-lg-8 col-xl-8 col-sm-8 text-right" >
                                                            <span :inner-html.prop="total | moneda_ache"></span>
                                                            <a href="javascript:void(0)" class="ml-3" @click="activarComprobante('Eliminar')" v-if="!numero_comprobante">
                                                                <small >Eliminar</small>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <p class="card-text">
                                                        <strong>Cliente</strong>

                                                            <a href="javascript:void(0)"  v-if="id_customer != 1" class="card-link pull-right">
                                                                <i class="fa fa-bell-o"></i>&nbsp;Puntos <span v-text="puntos_cliente"></span>
                                                            </a>

                                                    <div>
                                                        <div>
                                                            <div class="v-autocomplete">
                                                                <div class="input-group mb-2" style="width: 95%">
                                                                    <select name="cb_tipo_documento" id="cb_tipo_documento" class="form-control" v-model.number="cb_tipo_documento" style="width: 35%"  @change="changeTipoDocumento($event)">
                                                                        {foreach Tipodocumentolegal::getAllTipDoc() as $doc}
                                                                            <option value="{$doc['id_tipodocumentolegal']}" data-codsunat="{$doc['cod_sunat']}">- {$doc['nombre']} -</option>
                                                                        {/foreach}
                                                                    </select>
                                                                    <input type="text" maxlength="8" id="clientes_search" ref="numero_doc" class="clientes_search form-control" v-model="numero_doc" :disabled="id_customer != 1" placeholder="Número de documento" onkeyup="$(this).val().length >= 8 && $(this).val().length <= 15 ? $('#buscar_sunat').removeAttr('disabled') : $('#buscar_sunat').attr('disabled', 'disabled');" onkeypress="isNumberKey(event)" @keyup.enter="triggerBuscarSunat">
                                                                    <div id="buscar_sunat" class="input-group-addon btn btn-warning pointer" v-if="id_customer == 1" @click="buscarCliente()" disabled ref="enterBuscarSunat">
                                                                        {*                                                                        <img src="{$img_dir}sunat.png" style="width: 14px; height: auto;" alt="" >&nbsp; Buscar Sunat*}
                                                                        <i class="fa fa-search"></i>
                                                                    </div>
                                                                    <div class="input-group-append" style="margin-left: 0px;" v-else>
                                                                        <button type="button" class="btn btn-sm btn-primary" style="border-top-right-radius: 3px; border-bottom-right-radius: 3px;" @click="borrarCliente()">
                                                                            <i class="fa fa-ban"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
{*                                                        <div v-if="id_customer != 1 || mostrar_form_cliente">*}
                                                        <div>
                                                            <div  class="form-group row required mt-3" >
                                                                <div  class="col-sm-4">
                                                                    <i class="fa fa-user"></i>&nbsp;<strong>Nombre Legal</strong>
                                                                </div>
                                                                <input type="text" class="col-sm-8 form-control" v-model="nombre_legal" :disabled="!mostrar_form_cliente" @keyup="verificarCliente">
                                                            </div>
                                                            <div class="form-group row hide">
                                                                <div class="col-sm-112">
                                                                    <i class="fa fa-credit-card"></i>&nbsp;
                                                                    <strong v-if="tipo_doc" v-text="tipo_doc">tipo documento</strong>
                                                                    <strong v-else>N° Documento</strong>
                                                                </div>
                                                                <input type="number" class="col-sm-8 form-control" v-model="numero_doc" disabled>
                                                            </div>
                                                            <div class="form-group row ">
                                                                <div class="col-sm-12">
                                                                    <i class="fa fa-map-marker"></i>&nbsp;<strong>Dirección</strong>
                                                                </div>
                                                                <input type="text" class="col-sm-8 form-control" v-model="direccion_cliente">
                                                            </div>
                                                            <div class="form-group row ">
                                                                <div class="col-sm-12">
                                                                    <i class="fa fa-phone"></i>&nbsp;<strong>Celular/Teléfono</strong>
                                                                </div>
                                                                <input type="text" class="col-sm-8 form-control" v-model="celular_cliente">
                                                            </div>
                                                            <div class="form-group row ">
                                                                <div class="col-sm-12">
                                                                    <i class="fa fa-calendar"></i>&nbsp;<strong>Fecha Nacimiento</strong>
                                                                </div>
                                                                <datepicker v-model="fecha_nacimiento"></datepicker>
                                                            </div>

                                                        </div>
                                                    </div>
                                                    </p>
                                                    <hr>
                                                    <div class="card-text" v-if="total > 0">
                                                        <div>
                                                            <div class="d-inline-block pull-left">
                                                                <strong>Pagos</strong>&nbsp;&nbsp;
                                                            </div>
                                                            <div class="my-3 sales-add-edit-payments" v-for="(pago, ind) in pagos" :key="'ind-' + ind">
                                                                <div class="input-group">
                                                                    <select  id="inputPaymentMethod" class="custom-select form-control" v-model.number="pago.id_metodo_pago" @change="changeMetodoPago($event, pago)">
                                                                        <option data-tipo="efectivo" value="1">Pago con Efectivo</option>
                                                                        <option data-tipo="visa" value="2">Pago con Visa</option>
                                                                        <option data-tipo="izipay" value="3">Pago con Izipay</option>
                                                                    </select>
                                                                    <div class="mx-datepicker hide">
                                                                        <datepicker v-model="pago.fecha"></datepicker>
                                                                    </div>
                                                                    <span class="input-group-text" style="border-radius: 0px; margin-right: -1px;">S/</span>
                                                                    <input type="number" id="inputCash" placeholder="0.00" class="form-control text-center" v-model.number="pago.monto">
                                                                    <div v-if="pagos.length > 1 && ind > 0" class="input-group-append">
                                                                        <button type="button" class="btn btn-sm btn-primary" @click="borrarPago(pago)">
                                                                            <i class="fa fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="d-inline-block pull-left">
{*                                                                 boton para agregar otro pago *}
                                                                <a href="javascript:void(0)" @click="addPayment()">
                                                                    <small><i class="icon-plus"></i></small> Pago
                                                                </a>
                                                            </div>
                                                            <div class="mt-3">
                                                                {* calcular la deuda con todos los pagos *}
                                                                <div class="pull-right" :inner-html.prop="deudaItem | moneda_ache">S/</div>
                                                                <div class="d-inline-block pull-right">
                                                                    <strong v-text="textDeudaVuelto">Deuda</strong>&nbsp;&nbsp;
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                   </div>
                               </div>
                                {*  fin div duplicado*}
                           </div>
                        </div>
                    </div>
                </div>
                <div id="right-panel" class="pos-content list_products_div col-sm-12 col-md-8 ">
                    <div class="row">
                        <div>
                            <div class="input-group" style="width: 100%;">
                                <selectdos
                                        style=" width: 50%;"
                                        url="ajax_products_list_ache.php"
                                        :name="'id_producto'"
                                        :selecteditems="[]"
                                        :text="product_name"
                                        :identifier="'id_product'"
                                        v-model="id_product"
                                >
                                </selectdos>

                                <select2-basic :options="colaboradores" :name="'id_colaborador'" :id="'id_colaborador'" v-model="id_colaborador" class="form-control" style="width: 45%;" :disabled="cart.length > 0">

                                </select2-basic>
                                <div class="input-group-append"  style="width: 5%; float: right">
                                    <button type="button" class="btn btn-sm btn-primary" style="line-height: 1.75!important;" @click="addItem()">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row content_carrito_table">
{*                        <h2 style="margin-top: 0!important;">Lista de Venta</h2>*}
                        <table class="table table-clean mt-2 mb-3 tabla_lista_venta">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center" width="10%">Cant.</th>
                                    <th scope="col" class="head-title"  width="30%">Producto</th>
                                    <th scope="col" class="head-title"  width="25%">Colaborador</th>
                                    <th scope="col" class="text-center" width="15%">P.U.</th>
                                    <th scope="col" class="text-center" width="15%">Total</th>
                                    <th scope="col" class="text-center" width="5%">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody  v-if="cart.length">
                            <tr class="cart-item" v-for="(item, id) in cart" :key="'id-' + id">
                                <td style="width: 10%">
                                    <div class="quantity">
                                        <input type="text" class="number_cantidad form-control" :id="'number_cantidad_'+id" ref="number_cantidad" v-model="item.quantity" @keyup="changeCantidad(item)" @input="filterInput" v-focus  @keyup.enter="setFocus()" onkeypress="return !(event.charCode != 46 && event.charCode > 31 && (event.charCode < 48 || event.charCode > 57));"/>
                                    </div>
                                </td>
                                <td style="width: 30%" v-text="item.title">
{*                                    <input v-bind:id="'id-' + id" type="text" v-model="item.title">*}
                                </td>
                                <td style="width: 25%" v-text="item.colaborador_name">
                                </td>
                                <td style="width: 15%" class="text-center">
                                    <input type="text" class="price form-control" v-model="item.price" @keyup="changePrecioUnitario(item)"/>
                                </td>
                                <td style="width: 15%">
                                    <input type="text" class="total form-control" v-model="item.importe_linea" @keyup="changeImporte(item)"/>
                                </td>
                                <td style="width: 5%"><button class="btn btn-danger" @click="borrarProducto(item)"><i class="fa fa-trash fa-lg"></i></button></td>
                            </tr>
                            </tbody>
                            <tfoot  v-if="cart.length">
                                <tr>
                                    <th colspan="4" style="width: 70%">
                                        <h5 class="total-title">Total</h5>
                                    </th>
                                    <th style="width: 30%">
                                        <h5 class="total-title" :inner-html.prop="total | moneda_ache">Total</h5>
                                    </th>
                                </tr>
                            </tfoot>

                            <tbody v-else style="display: inherit!important;">
                                <tr >
                                    <td class="list-empty" colspan="8">
                                        <div class="list-empty-msg">
                                            <i class="icon-warning-sign list-empty-icon"></i>
                                            Ningún producto encontrado
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="form-group row mb-5" v-if="enviadoSunat && numero_comprobante">
                            <label for="inputClientEmail" class="col-sm-3 col-form-label">
                                <strong>Enviar comprobante a</strong>
                            </label>
                            <div class="input-group col-sm-8">
                                <span class="input-group-addon"><i class="icon-envelope-o"></i></span>
                                <input type="email" id="inputClientEmail" v-model="email_cliente_envio" placeholder="ejemplo@email.com" class="form-control">
                                <span class="input-group-addon btn btn-warning" @click="enviarMailComprobanteCliente">Enviar</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div id="minicart">
                <div class="minicart-content">
                    <div class="body">
                        <div class="items"><i class="fa fa-arrow-right"></i></div>
                    </div>
                </div>
            </div>
            <div class="footer_ache_veder">
                <div class="row col-lg-12" >
                    <div class="col-md-4 mb-2 col-lg-4  col-xl-4 col-sm-12" v-if="!is_active_tab_pago">
                        <button type="button" class="btn w-100 btn-light btn-sm" onclick="location.reload()" :disabled="guardandoEnviar">
                            <i class="fa fa-spinner fa-spin fa-lg" v-if="guardandoEnviar"></i>
                            <i class="icon-cart-plus" v-else></i> Nueva Venta
                        </button>
                    </div>
                    <div class="col-md-4 mb-2 col-lg-4 col-xl-4 col-sm-12" v-if="!order.id && !hasComprobante">
                        <button type="button" class="btn w-100 btn-primary btn-sm" :disabled="guardandoEnviar || cart.length  == 0 || bloquear_error" @click="agregarVenta(2)" style="text-transform: none;" v-if="!order_bycliente.id">
                            <i class="fa fa-spinner fa-spin fa-lg" v-if="guardandoEnviar"></i>
                            <i class="fa fa-file" v-else></i>
                            Realizar venta
                        </button>
                        <button type="button" class="btn w-100 btn-primary btn-sm" :disabled="guardandoEnviar || cart.length  == 0 || bloquear_error" @click="addProductos(order_bycliente)" style="text-transform: none;" v-else>
                            <i class="fa fa-spinner fa-spin fa-lg" v-if="guardandoEnviar"></i>
                            <i class="icon-save" v-else></i>&nbsp; Actualizar Venta
                        </button>
                    </div>
                    <div v-else>
                        <div class="col-md-4 mb-2 col-lg-4 col-xl-4 col-sm-12" v-if="!order.id  && total > 0" >
                            <button type="button" class="btn w-100 btn-info btn-sm" :disabled="guardandoEnviar || cart.length  == 0  || bloquear_error" @click="agregarVenta(2)" style="text-transform: none;">
                                <i class="fa fa-spinner fa-spin fa-lg" v-if="guardandoEnviar"></i>
                                <img src="{$img_dir}sunat.png" style="width: 14px; height: auto;" alt="" v-else> Vender y Enviar
                            </button>
                        </div>
                    </div>

                    {if (bool)$existeCertificado}
                        <div v-if="!enviadoSunat">
                            <div class="col-md-4 mb-2 col-lg-4  col-xl-4 col-sm-12" v-if="order.id && hasComprobante">
                                <button type="button" class="btn w-100 btn-warning btn-sm" :disabled="guardandoEnviar || bloquear_error" @click="enviarComprobanteSunat()" id="enviarsunatclick" >
                                    <i class="fa fa-spinner fa-spin fa-lg" v-if="guardandoEnviar"></i>
                                    <img src="{$img_dir}sunat.png" style="width: 14px; height: auto;" alt="" v-else>&nbsp; Enviar Comprobante
                                </button>
                            </div>
                        </div>
                    {/if}
{*                    <div class="col-md-4 mb-2 col-lg-4 col-xl-4 col-sm-12 hide" v-if="!hasComprobante">*}
{*                        <button type="button" class="btn btn-sm btn-success" style="width: 100%;" :disabled="guardandoEnviar || cart.length  == 0" @click="agregarVenta(1)" v-if="!order.id">*}
{*                            <i class="fa fa-spinner fa-spin fa-lg" v-if="guardandoEnviar"></i>*}
{*                            <i class="icon-save" v-else></i>*}
{*                            Guardar Sin Pagar*}
{*                        </button>*}
{*                    </div>*}
                    <div class="col-md-4 mb-2 col-lg-4 col-xl-4 col-sm-12" v-if="total == 0 && cart.length && id_customer != 1 && puntos_cliente >= 6">
                        <button type="button" class="btn btn-sm btn-success" style="width: 100%;" :disabled="guardandoEnviar || cart.length  == 0" @click="agregarVenta(99)" v-if="!order.id">
                            <i class="fa fa-spinner fa-spin fa-lg" v-if="guardandoEnviar"></i>
                            <i class="icon-save" v-else></i>
                            Venta por puntos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .footer_ache_veder{
        padding: 10px 10px 0 225px;
        position: fixed;
        right: 0;
        bottom: 0;
        width: 100%;
        text-align: center;
        z-index: 503;
    }
    @media (max-width: 767px) {
        .footer_ache_veder{
            padding: 0px;
        }
    }
</style>
<div class="alertmessage" id="alertmessage">
    <div style="font-size: 1rem; color: white;">Imprimir</div>
{*    <img title="delete" src="http://icons.iconarchive.com/icons/dryicons/simplistica/16/delete-icon.png"/>*}
</div>

{include file="./cierre_caja.tpl"}
<script src="{$tpl_folder}js/vender-vue.js"></script>
<script src="{$tpl_folder}js/vender.js"></script>