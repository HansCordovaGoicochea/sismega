
{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
  <script type="text/javascript">
    var admin_order_tab_link = "{$link->getAdminLink('AdminOrders')|addslashes}";
    var id_order = {$order->id};
    var id_lang = {$current_id_lang};
    var id_currency = {$order->id_currency};
    var id_customer = {$order->id_customer|intval};
    {assign var=PS_TAX_ADDRESS_TYPE value=Configuration::get('PS_TAX_ADDRESS_TYPE')}
    var id_address = {$order->$PS_TAX_ADDRESS_TYPE};
    var currency_sign = "{$currency->sign}";
    var currency_format = "{$currency->format}";
    var currency_blank = "{$currency->blank}";
    var priceDisplayPrecision = {$smarty.const._PS_PRICE_DISPLAY_PRECISION_|intval};
    var use_taxes = {if $order->getTaxCalculationMethod() == $smarty.const.PS_TAX_INC}true{else}false{/if};
    var stock_management = {$stock_management|intval};
    var txt_add_product_stock_issue = "{l s='Are you sure you want to add this quantity?' d='Admin.Orderscustomers.Notification' js=1}";
    var txt_add_product_new_invoice = "{l s='Are you sure you want to create a new invoice?' d='Admin.Orderscustomers.Notification' js=1}";
    var txt_add_product_no_product = "{l s='Error: No product has been selected' d='Admin.Orderscustomers.Notification' js=1}";
    var txt_add_product_no_product_quantity = "{l s='Error: Quantity of products must be set' d='Admin.Orderscustomers.Notification' js=1}";
    var txt_add_product_no_product_price = "{l s='Error: Product price must be set' d='Admin.Orderscustomers.Notification' js=1}";
    var txt_confirm = "{l s='Are you sure?' d='Admin.Notifications.Warning' js=1}";
    var statesShipped = new Array();
    var has_voucher = {if count($discounts)}1{else}0{/if};
    {foreach from=$states item=state}
    {if (isset($currentState->shipped) && !$currentState->shipped && $state['shipped'])}
    statesShipped.push({$state['id_order_state']});
    {/if}
    {/foreach}
    var order_discount_price = {if ($order->getTaxCalculationMethod() == $smarty.const.PS_TAX_EXC)}
            {$order->total_discounts_tax_excl}
            {else}
            {$order->total_discounts_tax_incl}
            {/if};

    var errorRefund = "{l s='Error. You cannot refund a negative amount.' d='Admin.Orderscustomers.Notification'}";
  </script>
  <div id="mensaje_sunat"></div>
  <div id="resultado"></div>
  {if $objComprobantes->msj_sunat}
    {if $objComprobantes->cod_sunat == 0}
      <div class="alert alert-success col-xs-12" id="mensaje_cdr_leido" style="font-weight: bold">{$objComprobantes->msj_sunat}</div>
    {else}
      <div class="alert alert-danger col-xs-12" id="mensaje_cdr_leido" style="font-weight: bold">{$objComprobantes->msj_sunat}</div>
    {/if}
  {/if}

  {assign var="hook_invoice" value={hook h="displayInvoice" id_order=$order->id}}
  {if ($hook_invoice)}
    <div>{$hook_invoice}</div>
  {/if}

  {assign var="order_documents" value=$order->getDocuments()}
  {assign var="order_shipping" value=$order->getShipping()}
  {assign var="order_return" value=$order->getReturn()}

  <!-- Customer informations -->
  <div class="col-lg-12">
    <div class="row panel" >
      {if $customer->id}
        <div class="panel-heading">
          <i class="icon-user"></i>
          {*{l s='Customer' d='Admin.Global'}*}
          <span class="badge">
               {if (int)$customer->id != 1}
                 <a href="?tab=AdminCustomers&amp;id_customer={$customer->id}&amp;viewcustomer&amp;token={getAdminToken tab='AdminCustomers'}" target="_blank" id="datos_cliente">
                   {$customer->firstname} - {$customer->num_document}
                  </a>
               {else}
                 <a href="#" target="_blank" id="datos_cliente">
                    - SIN CLIENTE -
                  </a>
               {/if}
            </span>
          <span class="badge">N° Pedido: {$order->nro_ticket}</span>
          <div class="panel-heading-action">
            <div class="btn-group">
              {if $objComprobantes->tipo_documento_electronico == 'NotaCredito' }<span class="badge">Nota de Credito</span>{elseif $objComprobantes->nota_baja == 'ComunicacionBaja'}<span class="badge">Comunicación de Baja</span>{/if}
              <i class="icon-credit-card hidden-xs"></i>
              <span class="hidden-xs">{l s='Venta' d='Admin.Global'}</span>
              <span class="badge hidden-xs">{$objComprobantes->fecha_envio_comprobante|date_format:"%d/%m/%Y"}</span>
              <span class="badge hidden-xs" id="numero_comprobante_return">{if isset($objComprobantes->numero_comprobante) && $objComprobantes->numero_comprobante}{l s="#" d='Admin.Orderscustomers.Feature'}{$objComprobantes->numero_comprobante}{/if}</span>

              <a class="btn btn-default{if !$previousOrder} disabled{/if}" href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$previousOrder|intval}">
                <i class="icon-backward"></i>
              </a>
              <a class="btn btn-default{if !$nextOrder} disabled{/if}" href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$nextOrder|intval}">
                <i class="icon-forward"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="row col-xs-6">
            <!-- Documentos Print -->
            <div class="col-xs-12">
              <!-- Orders Actions -->
              <div class="well hidden-print">
                <a class="btn btn-default" target="_blank" href="{$link->getAdminLink('AdminPdf')|escape:'html':'UTF-8'}&submitAction=generateInvoicePDF&id_ventarapida={$order->id|intval}&documento=ticket" id="imprimir_ticket">
                  <i class="icon-print"></i>
                  {l s='Ticket' d='Admin.Orderscustomers.Feature'}
                </a>

                  {if !empty($certificado) && (bool)$certificado['active'] && $order->current_state == 2}
                    <button type="button" class="btn {if $objComprobantes->tipo_documento_electronico == "Boleta"}btn-primary{elseif $objComprobantes->tipo_documento_electronico == ""}btn-primary{else}btn-default{/if} {if $order->total_paid_tax_incl == 0}hide{/if}" id="abrirCliente" data-value="Boleta" {if $objComprobantes->id} disabled{/if}>
                      <i class="icon-file"></i>
                      {l s='Boleta Elect.' d='Admin.Orderscustomers.Feature'}
                    </button>
                    <button type="button" class="btn {if $objComprobantes->tipo_documento_electronico == "Factura"}btn-primary{else}btn-default{/if} {if $order->total_paid_tax_incl == 0}hide{/if}" id="abrirEmpresa" data-value="Factura" {if $objComprobantes->id} disabled{/if}>
                      <i class="icon-file"></i>
                      {l s='Factura Elect.' d='Admin.Orderscustomers.Feature'}
                    </button>
                  {/if}

                {if Configuration::get('PS_INVOICE') && count($invoices_collection) && $order->invoice_number}
                {if $objComprobantes->ruta_cdr}
                  <a id="enviar_correo" class="btn btn-default" name="enviar_correo" data-toggle="modal" data-target="#modalEmails" data-backdrop="static" data-keyboard="false">
                    <i class="icon-envelope"></i>
                    {l s='Enviar Correo' d='Admin.Orderscustomers.Feature'}
                  </a>
                  <!-- Modal emails-->
                  <div id="modalEmails" class="modal fade" role="dialog">
                    <div class="modal-dialog">

                      <!-- Modal content-->
                      <div class="modal-content" id="modal-formyo">
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal">&times;</button>
                          <h4 class="modal-title">Enviar Email {if $objComprobantes->nota_baja == 'NotaCredito' }Nota de Credito{elseif $objComprobantes->nota_baja == 'ComunicacionBaja'}Comunicación de Baja{/if}</h4>
                        </div>
                        <div class="modal-body">
                          {*<div id="correos_clienteyo">*}
                          <label>E-mail:</label>
                          {if $customer->id == 1}
                            <input type="text" id="correo" name="correo" >
                          {elseif $customer->email|strstr:"sincorreo"}
                            <input type="text" id="correo" name="correo" value="">
                          {else}
                            <input type="text" id="correo" name="correo" value="{$customer->email}">
                          {/if}
                          <br>
                          <div>
                            {assign var=pdf value="/"|explode:$objComprobantes->ruta_ticket}
                            {assign var=pdf_a4 value="/"|explode:$objComprobantes->ruta_pdf_a4}
                            {assign var=pdf_a4nota value="/"|explode:$objComprobantes->ruta_pdf_a4nota}
                            {if $objComprobantes->ruta_ticket} <span class="badge" > <a STYLE="color: #fff" target="_blank" href="{$objComprobantes->ruta_ticket}"><strong>PDF COMPROBANTE Ticket</strong></a> </span>{/if}
                            {if $objComprobantes->ruta_pdf_a4} <span class="badge" > <a STYLE="color: #fff" target="_blank" href="{$objComprobantes->ruta_pdf_a4}"><strong>PDF COMPROBANTE A4</strong></a> </span>{/if}

                            {if $objComprobantes->nota_baja == 'NotaCredito' }
                              <span class="badge" >  <a STYLE="color: #fff" target="_blank" href="{$objComprobantes->ruta_pdf_a4nota}"><strong>PDF COMPROBANTE A4 NOTA DE CREDITO</strong></a> </span>
                            {elseif $objComprobantes->nota_baja == 'ComunicacionBaja'}
                            {else}
                              {if $objComprobantes->ruta_xml} <span class="badge" > <a STYLE="color: #fff" href="{$objComprobantes->ruta_xml}"><strong>XML COMPROBANTE</strong></a> </span>{/if}
                              {if $objComprobantes->ruta_cdr} <span class="badge" > <a STYLE="color: #fff" href="{$objComprobantes->ruta_cdr}"><strong>CDR COMPROBANTE</strong></a> </span>{/if}
                            {/if}

                          </div>
                        </div>
                        <div class="modal-footer">
                          <a  value="Enviar a E-mail" id="btnEnviarProforma2" name="btnEnviarProforma2" class="btn btn-default pull-left" onclick="sendMailToCustomer()">
                            <i class="icon-envelope"></i> Enviar E-mail
                          </a>
                          <a  class="btn btn-default"  data-dismiss="modal">Cancelar</a>
                        </div>
                      </div>

                    </div>
                  </div>
                  <script>
                    function sendMailToCustomer() {
                      $('#modal-formyo').waitMe({
                        effect : 'bounce',
                        text : 'Enviando...',
//    bg : rgba(255,255,255,0.7),
                        color : '#000',
                        maxSize : '',
                        textPos : 'vertical',
                        fontSize : '',
                        source : ''
                      });
                      var divIdHtml = $("#myDiv").html();
                      var htmlmensaje='';
                      $.ajax({
                        type:"POST",
                        url: "{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}",
                        async: true,
                        dataType: "json",
                        data : {
                          ajax: "1",
                          token: "{getAdminToken tab='AdminOrders'}",
                          tab: "AdminOrders",
                          action: "sendMailValidateOrderDocs",
                          id_order: '{$order->id}',
                          id_pos_ordercomprobantes: '{$objComprobantes->id}',
                          correos: $('#correo').val(),
                        },
                        success : function(res)
                        {
                          if (res.errors)
                            htmlmensaje += '<div class="alert alert-danger">'+res.result+'</div>';
                          else
                            htmlmensaje += '<div class="alert alert-success">'+res.result+'</div>';
                          $('#resultado').html(htmlmensaje);
                          $('#modalEmails').modal('hide');
                          $('#modal-formyo').waitMe("hide");
                          $('#correo').val('');

                        },
                        error: function (jqXHR, exception) {
                          var msg = '';
                          if (jqXHR.status === 0) {
                            msg = 'Not connect.\n Verify Network.';
                          } else if (jqXHR.status == 404) {
                            msg = 'Requested page not found. [404]';
                          } else if (jqXHR.status == 500) {
                            msg = 'Internal Server Error [500].';
                          } else if (exception === 'parsererror') {
                            msg = 'Requested JSON parse failed.';
                          } else if (exception === 'timeout') {
                            msg = 'Time out error.';
                          } else if (exception === 'abort') {
                            msg = 'Ajax request aborted.';
                          } else {
                            msg = 'Uncaught Error.\n' + jqXHR.responseText;
                          }
                          $('#resultado').html(msg);
                          $('#modal-formyo').waitMe("hide");
                        },
                      });

                      //alert('El correo fue enviado correctamente');
                    }
                  </script>
                {/if}
                  <div class="row " style="margin-top: 15px;" id="tickets_pdfs">

                    {if $objComprobantes->ruta_ticket} <span class="badge" > <a STYLE="color: #fff" target="_blank" href="{$objComprobantes->ruta_ticket}"><strong>PDF COMPROBANTE Ticket</strong></a> </span>
                      <br><br>{/if}
                    {if $objComprobantes->ruta_pdf_a4} <span class="badge" > <a STYLE="color: #fff" target="_blank" href="{$objComprobantes->ruta_pdf_a4}"><strong>PDF COMPROBANTE A4</strong></a> </span>
                      <br><br>{/if}


                    {if $objComprobantes->nota_baja == 'NotaCredito' || $objComprobantes->nota_baja == 'NotaCredito_fisica'}
                      <span class="badge" >  <a STYLE="color: #fff" target="_blank" href="{$objComprobantes->ruta_pdf_a4nota}"><strong>PDF COMPROBANTE A4 NOTA DE CREDITO</strong></a> </span><br><br>
                    {elseif $objComprobantes->nota_baja == 'ComunicacionBaja'}
                    {else}
                      {if $objComprobantes->ruta_xml} <span class="badge" > <a STYLE="color: #fff" href="{$objComprobantes->ruta_xml}"><strong>XML COMPROBANTE</strong></a> </span><br><br>{/if}
                      {if $objComprobantes->ruta_cdr} <span class="badge" > <a STYLE="color: #fff" href="{$objComprobantes->ruta_cdr}"><strong>CDR COMPROBANTE</strong></a> </span>{/if}
                    {/if}
                  </div>
                {/if}

                {hook h='displayBackOfficeOrderActions' id_order=$order->id|intval}
              </div>
              <!-- Tab content -->
              <div class="tab-content panel hide">
                {$HOOK_CONTENT_ORDER}
                <!-- Tab status -->
                <div class="tab-pane active" id="status">
                  <h4 class="visible-print">{l s='Status' d='Admin.Global'} <span class="badge">({$history|@count})</span></h4>
                  <!-- History of status -->
                  <div class="table-responsive">
                    <table class="table history-status row-margin-bottom">
                      <tbody>
                      {foreach from=$history item=row key=key}
                        {if ($key == 0)}
                          <tr>
                            <td style="background-color:{$row['color']}"><img src="../img/os/{$row['id_order_state']|intval}.gif" width="16" height="16" alt="{$row['ostate_name']|stripslashes}" /></td>
                            <td style="background-color:{$row['color']};color:{$row['text-color']}">{$row['ostate_name']|stripslashes}</td>
                            <td style="background-color:{$row['color']};color:{$row['text-color']}">{if $row['employee_lastname']}{$row['employee_firstname']|stripslashes} {$row['employee_lastname']|stripslashes}{/if}</td>
                            <td style="background-color:{$row['color']};color:{$row['text-color']}">{dateFormat date=$row['date_add'] full=true}</td>
                            <td style="background-color:{$row['color']};color:{$row['text-color']}" class="text-right">
                              {if $row['send_email']|intval}
                                <a class="btn btn-default" href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$order->id|intval}&amp;sendStateEmail={$row['id_order_state']|intval}&amp;id_order_history={$row['id_order_history']|intval}" title="{l s='Resend this email to the customer' d='Admin.Orderscustomers.Help'}">
                                  <i class="icon-mail-reply"></i>
                                  {l s='Resend email' d='Admin.Orderscustomers.Feature'}
                                </a>
                              {/if}
                            </td>
                          </tr>
                        {else}
                          <tr>
                            <td><img src="../img/os/{$row['id_order_state']|intval}.gif" width="16" height="16" /></td>
                            <td>{$row['ostate_name']|stripslashes}</td>
                            <td>{if $row['employee_lastname']}{$row['employee_firstname']|stripslashes} {$row['employee_lastname']|stripslashes}{else}&nbsp;{/if}</td>
                            <td>{dateFormat date=$row['date_add'] full=true}</td>
                            <td class="text-right">
                              {if $row['send_email']|intval}
                                <a class="btn btn-default" href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$order->id|intval}&amp;sendStateEmail={$row['id_order_state']|intval}&amp;id_order_history={$row['id_order_history']|intval}" title="{l s='Resend this email to the customer' d='Admin.Orderscustomers.Help'}">
                                  <i class="icon-mail-reply"></i>
                                  {l s='Resend email' d='Admin.Orderscustomers.Feature'}
                                </a>
                              {/if}
                            </td>
                          </tr>
                        {/if}
                      {/foreach}
                      </tbody>
                    </table>
                  </div>
                  <!-- Change status form -->
                  <form action="{$currentIndex|escape:'html':'UTF-8'}&amp;vieworder&amp;token={$smarty.get.token}" method="post" class="form-horizontal well hidden-print">
                    <div class="row">
                      <div class="col-lg-9">
                        <select id="id_order_state" class="chosen form-control" name="id_order_state">
                          {foreach from=$states item=state}
                            <option value="{$state['id_order_state']|intval}"{if isset($currentState) && $state['id_order_state'] == $currentState->id} selected="selected" disabled="disabled"{/if}>{$state['name']|escape}</option>
                          {/foreach}
                        </select>
                        <input type="hidden" name="id_order" value="{$order->id}" />
                      </div>
                      <div class="col-lg-3">
                        <button type="submit" name="submitState" id="submit_state" class="btn btn-primary">
                          {l s='Update status' d='Admin.Orderscustomers.Feature'}
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            {*          {if !$objComprobantes->ruta_pdf_a4}*}
            <!-- CLIENTE -->
            {*            <div class="col-xs-12" {if $order->valid == 0}style="display: none;" {/if} {if $objComprobantes->numero_comprobante != "" && $objComprobantes->cod_sunat == 0}style="display: none;" {/if}>*}
            <div class="col-xs-12 {if $order->total_paid_tax_incl == 0}hide{/if} " {if $objComprobantes->numero_comprobante != "" && $objComprobantes->cod_sunat == 0}style="display: none;" {/if}>

              <div {if $objComprobantes->id} style="display: none" {/if} class="col-lg-6 col-xs-12">
                <div class="form-group">
                  <label for="cb_tipo_documento" class="control-label required"><span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="Tipo de documento">Tipo Doc.:</span></label>
                  <div class="">
                    <select name="cb_tipo_documento" id="cb_tipo_documento" class="form-control">
                      {foreach Tipodocumentolegal::getAllTipDoc() as $doc}
                        <option value="{$doc['id_tipodocumentolegal']}" data-codsunat="{$doc['cod_sunat']}" {if $doc['id_tipodocumentolegal'] == $customer->id_document}selected{/if}>- {$doc['nombre']} -</option>
                      {/foreach}
                    </select>
                  </div>
                </div>
              </div>
              <div {if $objComprobantes->id} style="display: none"{/if} class="col-lg-6 col-xs-12" >
                <div class="form-group">
                  <label for="txtNumeroDocumento" class="control-label required"><span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="Número de documento">Número Doc.:</span></label>
                  <div class="">
                    <input type="text" class="form-control" id="txtNumeroDocumento" name="txtNumeroDocumento" value="{$customer->num_document}">
                  </div>
                </div>
              </div>
              <div {if $objComprobantes->id} style="display: none"{/if}  id="div_datos_cliente">
                <input type="hidden" class="input_ache" name="id_customer" id="id_customer" value="{$customer->id}">
                <br>
                <br>
                <br>
                <br>
                <div class="form-group">
                  <label for="txtNombre" class="control-label required col-lg-2 col-xs-12"><span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="Nombres del cliente">Cliente:</span></label>
                  <div class="col-lg-10 col-xs-12">
                    <input type="text" class="form-control" id="txtNombre" name="txtNombre" value="{$customer->firstname}">
                  </div>
                </div>
                <br>
                <br>
                <div class="form-group">
                  <label for="txtDireccion" class="control-label required col-lg-2 col-xs-12"><span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="Dirección del cliente">Direccion:</span></label>
                  <div class="col-lg-10 col-xs-12">
                    <input type="text" class="form-control" id="txtDireccion" name="txtDireccion" value="{$customer->direccion}">
                  </div>
                </div>
              </div>
              {if !$objComprobantes->id}
              <br>
              <br>
              {/if}

              {if !empty($certificado) && (bool)$certificado['active'] && $order->current_state == 2}
                  <button type="button" class="btn btn-default pull-right" name="submitCreateXMLFactura" id="b_compro" data-value="Boleta" {if $objComprobantes->tipo_documento_electronico == "Factura"}style="display: none;" {/if}>
                    <img src="{$img_dir}sunat.png" style="display: block; width: 30px; height: 30px;margin: 0 auto; font-size: 28px; background: transparent; background-size: 26px; background-position: 50%;" alt="" >
                    {l s='Enviar Boleta' d='Admin.Orderscustomers.Feature'}
                  </button>
                  <button type="button" class="btn btn-default  pull-right"  name="submitCreateXMLFactura" id="f_compro" data-value="Factura" {if $objComprobantes->tipo_documento_electronico == "Boleta"}style="display: none;"{/if} {if !isset($objComprobantes)}style="display: none;" {/if}>
                    <img src="{$img_dir}sunat.png" style="display: block; width: 30px; height: 30px;margin: 0 auto; font-size: 28px; background: transparent; background-size: 26px; background-position: 50%;" alt="" >
                    {l s='Enviar Factura' d='Admin.Orderscustomers.Feature'}
                  </button>

              {/if}
              {if !$objComprobantes->id}
                <button type="button" class="btn btn-default pull-right" name="submitCreateXMLFactura" id="guardarCliente" style="margin-right: 5px;">
                  <i class="process-icon-save"></i>
                  {l s='Cambiar Cliente' d='Admin.Orderscustomers.Feature'}
                </button>
              {/if}
            </div>
            {*          {/if}*}
          </div>

            <!-- Payments block -->
            <div style="padding: 10px!important;" class="col-xs-6 well">
              <!-- Payments block -->
              <div id="formAddPaymentPanel">
                <h4 >
                  <i class="icon-money"></i>
                  {l s="Pagos" d='Admin.Global'} <span class="badge">{$order->getOrderPayments()|@count}</span>
                </h4>
                {if count($order->getOrderPayments()) > 0}
                  <p class="alert alert-danger"{if round($orders_total_paid_tax_incl, 2) == round($total_paid, 2) || (isset($currentState) && $currentState->id == 6)} style="display: none;"{/if}>
                    {l s='Warning' d='Admin.Global'}
                    <strong>{displayPrice price=$total_paid currency=$currency->id}</strong>
                    {l s='paid instead of' d='Admin.Orderscustomers.Notification'}
                    <strong class="total_paid">{displayPrice price=$orders_total_paid_tax_incl currency=$currency->id}</strong>
                    {foreach $order->getBrother() as $brother_order}
                      {if $brother_order@first}
                        {if count($order->getBrother()) == 1}
                          <br />{l s='This warning also concerns order ' d='Admin.Orderscustomers.Notification'}
                        {else}
                          <br />{l s='This warning also concerns the next orders:' d='Admin.Orderscustomers.Notification'}
                        {/if}
                      {/if}
                      <a href="{$current_index}&amp;vieworder&amp;id_order={$brother_order->id}&amp;token={$smarty.get.token|escape:'html':'UTF-8'}">
                        #{'%06d'|sprintf:$brother_order->id}
                      </a>
                    {/foreach}
                  </p>
                {/if}
                <style>

                  /* Force table to not be like tables anymore */
                  table.cor table, table.cor thead, table.cor tbody, table.cor th, table.cor td, table.cor tr {
                    display: block;
                  }

                  /* Hide table headers (but not display: none;, for accessibility) */
                  table.cor thead tr {
                    position: absolute;
                    top: -9999px;
                    left: -9999px;
                  }

                  table.cor tr {
                    margin: 0 0 1rem 0;
                  }

                  table.cor tr:nth-child(odd) {
                    /*background: #ccc;*/

                  }

                  table.cor td {

                    /*border-right: solid 1px #a0d0eb !important;*/
                    /*border-left: solid 1px #a0d0eb !important;*/

                    /* Behave  like a "row" */
                    border: none;
                    border-bottom: 1px solid #eee;
                    position: relative;
                    padding-left: 100px;
                    font-size:1.3em;
                  }

                  table.cor td:before {
                    /* Now like a table header */
                    position: absolute;
                    /* Top/left values mimic padding */
                    top: 0;
                    left: 6px;
                    width: 45%;
                    padding-right: 10px;
                    white-space: nowrap;
                  }

                  table.cor  td:nth-of-type(1):before { content: "Fecha"; }
                  table.cor  td:nth-of-type(2):before { content: "Met. Pago"; }
                  table.cor  td:nth-of-type(3):before { content: ""; }
                  table.cor  td:nth-of-type(4):before { content: "Monto"; }
                  table.cor  td:nth-of-type(5):before { content: "Vuelto"; }
                  table.cor  td:nth-of-type(6):before { content: ""; }


                  @media screen and (max-width: 1200px) {

                    table.cor {
                      width:100%;
                    }

                    table.cor thead {
                      display: none;
                    }

                    table.cor tr:nth-of-type(2n) {
                      background-color: inherit;
                    }

                    table.cor tbody td {
                      display: block;
                      text-align:left;
                      margin-bottom: 5px;
                    }

                    table.cor tbody td:before {
                      content: attr(data-th);
                      display: block;
                      text-align:left;
                    }

                  }

                </style>
                <form id="formAddPayment" class="form-horizontal"  method="post" action="{$current_index}&amp;vieworder&amp;id_order={$order->id}&amp;token={$smarty.get.token|escape:'html':'UTF-8'}">
                  <input type="hidden" name="tipo_documento_electronico_fisico" id="tipo_documento_electronico_fisico" value="{$objComprobantes->tipo_documento_electronico}">
                  {assign var="valor_pagar" value=round($orders_total_paid_tax_incl,2) - round($total_paid,2)}
                  <div class="table-responsive" style="overflow-x: auto;">
                    <table class="cor">
                      <tbody>
                      <tr class="current-edit hidden-print">
                        <td>
                          <div class="input-group">
                            <input type="text" name="payment_date" class="datepicker " value="{date('Y-m-d')}" />
                            <div class="input-group-addon">
                              <i class="icon-calendar-o"></i>
                            </div>
                          </div>
                        </td>
                        <td>
                          <input type="hidden" name="tipo_pago" id="tipo_pago" value="1">
                          <select name="payment_method" class="payment_method" id="payment_method" onchange="mostrarTipoPago(this.value)">
                            <option data-tipo_pago="1" value="Pago en Efectivo" selected>Pago en Efectivo</option>
                            <option data-tipo_pago="2" value="Pago con Visa">Pago con Visa</option>
                            <option data-tipo_pago="3" value="Pago con Izipay">Pago con Izipay</option>
                          </select>
                        </td>
                        <td style="display: none;">
                          <input type="text" name="payment_transaction_id" value="" class="form-control fixed-width-sm"/>
                        </td>
                        <td>
                          <select name="payment_currency" class="payment_currency form-control fixed-width-xs pull-left hide">

                            {foreach from=$currencies item=current_currency}
                              <option value="{$current_currency['id_currency']}"{if $current_currency['id_currency'] == $currency->id} selected="selected"{/if}>{$current_currency['sign']}</option>
                            {/foreach}
                          </select>
                          <div class="input-group">
                            {foreach from=$currencies item=current_currency2}
                              {if $current_currency2['id_currency'] == $currency->id}
                                <span class="input-group-addon" style="font-size: 16px;">{$current_currency2['sign']}</span>
                              {/if}
                            {/foreach}
                            <input type="number" name="payment_amount" step="0.001" class="form-control fixed-width-md" value="{round($orders_total_paid_tax_incl,3) - round($total_paid,3)}" id="txtPagandoYO" style="font-size: 1.2em; padding: 0px!important;"/>
                          </div>
                        </td>
                        <td> - - </td>
                        <td class="actions" style="text-align: right!important; height: 2.7em!important;" >

                          {if (bool)$existeCajasAbiertas}
                            {if $perfil_empleado != 'Vendedor'}
                              <button class="btn btn-primary btn-lg pull-left" value="1" type="submit" name="submitAddPayment" id="btnPagosYO" {if (float)$valor_pagar == 0}disabled{/if}>
                                {l s='Pagar' d='Admin.Actions'}
                              </button>

                            {/if}
                          {/if}
                          {if count($invoices_collection) > 0}
                            <select name="payment_invoice" id="payment_invoice" style="display: none;">
                              {foreach from=$invoices_collection item=invoice}
                                <option value="{$invoice->id}" selected="selected">{$invoice->getInvoiceNumberFormatted($current_id_lang, $order->id_shop)}</option>
                              {/foreach}
                            </select>
                          {/if}
                        </td>
                      </tr>

                      {foreach from=$order->getOrderPaymentCollection() item=payment}
                        <tr>
                          <td>{dateFormat date=$payment->date_add full=true}</td>
                          <td>{$payment->payment_method|escape:'html':'UTF-8'}</td>
                          <td style="display: none;">{$payment->transaction_id|escape:'html':'UTF-8'}</td>
                          <td>{displayPrice price=$payment->amount currency=$payment->id_currency}</td>
                          <td>{displayPrice price=$payment->vuelto currency=$payment->id_currency}</td>
                          {*                        <td></td>*}
                          {assign var="empleado_pago" value=Employee::getEmployeeById($payment->id_employee_pago)}
                          <td>Pagado por - {$empleado_pago.name_employee}</td>
                        </tr>
                        {foreachelse}
                      {/foreach}

                      </tbody>
                    </table>
                  </div>
                </form>
                <script>
                  $(document).ready(function () {
                    var total_inclYO = "{round($orders_total_paid_tax_incl,2)}" ?{round($orders_total_paid_tax_incl,2)}: 0;
                    var total_paidYO = "{round($total_paid,2)}" ?{round($total_paid,2)}: 0;
                    var lo_que_queda = total_inclYO - total_paidYO;
                    var existe_reembolso = "{count($product['refund_history'])}";

                  });

                  function disableButton() {
                    $("#btnPagosYO").prop('disabled', true);
                  }

                  function mostrarTipoPago(val) {
                    if (val === 'Pago en Efectivo') {
                      $('#tipo_pago').val(1);
                    } else if (val === 'Pago con Visa') {
                      $('#tipo_pago').val(2);
                    } else if (val === 'Pago con Izipay') {
                      $('#tipo_pago').val(3);
                    }
                  }
                </script>
                {if (!$order->valid && sizeof($currencies) > 1)}
                  <form class="form-horizontal well hide" method="post" action="{$currentIndex|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$order->id}&amp;token={$smarty.get.token|escape:'html':'UTF-8'}">
                    <div class="row">
                      <label class="control-label col-lg-3">{l s='Change currency' d='Admin.Orderscustomers.Feature'}</label>
                      <div class="col-lg-6">
                        <select name="new_currency">
                          {foreach from=$currencies item=currency_change}
                            {if $currency_change['id_currency'] != $order->id_currency}
                              <option value="{$currency_change['id_currency']}">{$currency_change['name']} - {$currency_change['sign']}</option>
                            {/if}
                          {/foreach}
                        </select>
                        <p class="help-block">{l s='Do not forget to update your exchange rate before making this change.' d='Admin.Orderscustomers.Help'}</p>
                      </div>
                      <div class="col-lg-3">
                        <button type="submit" class="btn btn-default" name="submitChangeCurrency"><i class="icon-refresh"></i> {l s='Change' d='Admin.Orderscustomers.Feature'}</button>
                      </div>
                    </div>
                  </form>
                {/if}
              </div>
              {hook h="displayAdminOrderRight" id_order=$order->id}
            </div>


        </div>
      {/if}
    </div>
    {hook h="displayAdminOrderRight" id_order=$order->id}
  </div>
  {hook h="displayAdminOrder" id_order=$order->id}

  <div class="row" id="start_products">
    <!-- Products block -->
    <div class="col-lg-12">
      <form class="container-command-top-spacing" action="{$current_index}&amp;vieworder&amp;token={$smarty.get.token|escape:'html':'UTF-8'}&amp;id_order={$order->id|intval}" method="post" onsubmit="return orderDeleteProduct('{l s='This product cannot be returned.' d='Admin.Orderscustomers.Notification'}', '{l s='Quantity to cancel is greater than quantity available.' d='Admin.Orderscustomers.Notification'}');">
        <input type="hidden" name="id_order" value="{$order->id}" />
        <div style="display: none">
          <input type="hidden" value="{$order->getWarehouseList()|implode}" id="warehouse_list" />
        </div>

        <div class="panel">
          <div class="panel-heading">
            <i class="icon-shopping-cart"></i>
            {l s='Products' d='Admin.Global'} <span class="badge">{$products|@count}</span>
          </div>
          <div id="refundForm">
            <!--
            <a href="#" class="standard_refund"><img src="../img/admin/add.gif" alt="{l s='Process a standard refund'}" /> {l s='Process a standard refund'}</a>
            <a href="#" class="partial_refund"><img src="../img/admin/add.gif" alt="{l s='Process a partial refund'}" /> {l s='Process a partial refund'}</a>
          -->
          </div>

          {capture "TaxMethod"}
            {if ($order->getTaxCalculationMethod() == $smarty.const.PS_TAX_EXC)}
              {l s='Tax excluded' d='Admin.Global'}
            {else}
              {l s='Tax included' d='Admin.Global'}
            {/if}
          {/capture}
          {if ($order->getTaxCalculationMethod() == $smarty.const.PS_TAX_EXC)}
            <input type="hidden" name="TaxMethod" value="0">
          {else}
            <input type="hidden" name="TaxMethod" value="1">
          {/if}

          <style>
            .open_payment_information{
              display: none!important;
            }
            div#formAddPaymentPanel td.actions{
              text-align: left!important;
            }
          </style>

          <div class="table-responsive" >
            <table class="table" id="orderProducts">
              <thead>
              <tr>
                <th><span class="title_box ">{l s='Product' d='Admin.Global'}</span></th>
                <th><span class="title_box ">Colaborador</span></th>

                <th>
                  <span class="title_box ">{l s='Precio Uni.' d='Admin.Advparameters.Feature'}</span>
                  <small class="text-muted">{$smarty.capture.TaxMethod}</small>
                </th>
                <th class="text-center"><span class="title_box ">{l s='Qty' d='Admin.Orderscustomers.Feature'}</span></th>
                {*{if $display_warehouse}<th><span class="title_box ">{l s='Warehouse'}</span></th>{/if}*}
                {*{if ($order->hasBeenPaid())}<th class="text-center"><span class="title_box ">{l s='Refunded'}</span></th>{/if}*}

                {*{if ($order->hasBeenDelivered() || $order->hasProductReturned() > 0)}*}
                {*<th class="text-center"><span class="title_box ">{l s='Returned' d='Admin.Orderscustomers.Feature'}</span></th>*}
                {*{/if}*}
                {*{if $stock_management}<th class="text-center"><span class="title_box ">{l s='Cant. Disp.' d='Admin.Orderscustomers.Feature'}</span></th>{/if}*}
                <th>
                  <span class="title_box ">{l s='Total' d='Admin.Global'}</span>
                  <small class="text-muted">{$smarty.capture.TaxMethod}</small>
                </th>
                <th style="display: none;" class="add_product_fields"></th>
                <th style="display: none;" class="edit_product_fields"></th>
                <th style="display: none;" class="standard_refund_fields">
                  <i class="icon-minus-sign"></i>
                  {if ($order->hasBeenDelivered() || $order->hasBeenShipped())}
                    {l s='Return' d='Admin.Orderscustomers.Feature'}
                  {elseif ($order->hasBeenPaid())}
                    {l s='Refund' d='Admin.Orderscustomers.Feature'}
                  {else}
                    {l s='Cancel' d='Admin.Actions'}
                  {/if}
                </th>
                <th style="display:none" class="partial_refund_fields">
                  <span class="title_box ">{l s='Partial refund' d='Admin.Orderscustomers.Feature'}</span>
                </th>
                {if !$order->hasBeenDelivered()}
                  <th></th>
                {/if}
              </tr>
              </thead>
              <tbody>
              {foreach from=$products item=product key=k}
                {* Include customized datas partial *}
                {include file='controllers/orders/_customized_data.tpl'}
                {* Include product line partial *}
                {include file='controllers/orders/_product_line.tpl'}
              {/foreach}
              {if $can_edit}
                {include file='controllers/orders/_new_product.tpl'}
              {/if}
              </tbody>
            </table>
          </div>

          {if $can_edit}
            <div class="row-margin-bottom row-margin-top order_action">
              {if !$order->hasBeenDelivered()}

                <button type="button" id="add_product" class="btn btn-default" {if isset($objComprobantes->numero_comprobante) && $objComprobantes->numero_comprobante}disabled{/if}>
                  <i class="icon-plus-sign"></i>
                  {l s='Add a product' d='Admin.Orderscustomers.Feature'}
                </button>
              {/if}
              {if count($discounts) == 0}
              <button id="add_voucher" class="btn btn-default" type="button"  {if isset($objComprobantes->numero_comprobante) && $objComprobantes->numero_comprobante}disabled{/if}>
                <i class="icon-ticket"></i>
                {l s='Add a new discount' d='Admin.Orderscustomers.Feature'}
              </button>
              {/if}
            </div>
          {/if}
          <div class="clear">&nbsp;</div>
          <div class="row">
            <div class="col-xs-5">

            </div>
            <div class="col-xs-7">
              <div class="panel panel-vouchers" style="{if !sizeof($discounts)}display:none;{/if}">
                {if (sizeof($discounts) || $can_edit)}
                  <div class="table-responsive">
                    <table class="table">
                      <thead>
                      <tr>
                        <th>
                          <span class="title_box ">
                            {l s='Discount name' d='Admin.Orderscustomers.Feature'}
                          </span>
                        </th>
                        <th>
                          <span class="title_box ">
                            {l s='Value' d='Admin.Orderscustomers.Feature'}
                          </span>
                        </th>
                        {if $can_edit}
                          <th></th>
                        {/if}
                      </tr>
                      </thead>
                      <tbody>
                      {foreach from=$discounts item=discount}
                        <tr>
                          <td>{$discount['name']}</td>
                          <td>
                            {if $discount['value'] != 0.00}
                              -
                            {/if}
                            {displayPrice price=$discount['value'] currency=$currency->id}
                          </td>
                          {if $can_edit}
                            <td>
                              {if $order->current_state == 1}
                              <a href="{$current_index}&amp;submitDeleteVoucher&amp;id_order_cart_rule={$discount['id_order_cart_rule']}&amp;id_order={$order->id}&amp;token={$smarty.get.token|escape:'html':'UTF-8'}">
                                <i class="icon-minus-sign"></i>
                                {l s=' Eliminar descuento' d='Admin.Orderscustomers.Feature'}
                              </a>
                              {/if}
                            </td>
                          {/if}
                        </tr>
                      {/foreach}
                      </tbody>
                    </table>
                  </div>
                  <div class="current-edit" id="voucher_form" style="display:none;">
                    {include file='controllers/orders/_discount_form.tpl'}
                  </div>
                {/if}
              </div>
              <div class="panel panel-total">
                <div class="table-responsive">
                  <table class="table">
                    {* Assign order price *}
                    {if ($order->getTaxCalculationMethod() == $smarty.const.PS_TAX_EXC)}
                      {assign var=order_product_price value=($order->total_products)}
                      {assign var=order_discount_price value=$order->total_discounts_tax_excl}
                      {assign var=order_wrapping_price value=$order->total_wrapping_tax_excl}
                      {assign var=order_shipping_price value=$order->total_shipping_tax_excl}
                      {assign var=shipping_refundable value=$shipping_refundable_tax_excl}
                    {else}
                      {assign var=order_product_price value=$order->total_products_wt}
                      {assign var=order_discount_price value=$order->total_discounts_tax_incl}
                      {assign var=order_wrapping_price value=$order->total_wrapping_tax_incl}
                      {assign var=order_shipping_price value=$order->total_shipping_tax_incl}
                      {assign var=shipping_refundable value=$shipping_refundable_tax_incl}
                    {/if}
                    <tr id="total_products">
                      <td>
                        <div class="text-right "> {l s='SubTotal:' d='Admin.Orderscustomers.Feature'}</div>
                      </td>
                      <td class="amount text-right nowrap">
                        {displayPrice price=$order->total_paid_tax_excl currency=$currency->id}
                        {*{displayPrice price=$order_product_price currency=$currency->id}*}
                      </td>
                      <td class="partial_refund_fields current-edit" style="display:none;"></td>
                    </tr>
                    <tr id="total_discounts" {if $order->total_discounts_tax_incl == 0}style="display: none;"{/if}>
                      <td class="text-right">{l s='Discounts' d='Admin.Orderscustomers.Feature'}</td>
                      <td class="amount text-right nowrap">
                        -{displayPrice price=$order_discount_price currency=$currency->id}
                      </td>
                      <td class="partial_refund_fields current-edit" style="display:none;"></td>
                    </tr>
                    <tr id="total_wrapping" {if $order->total_wrapping_tax_incl == 0}style="display: none;"{/if}>
                      <td class="text-right">{l s='Wrapping' d='Admin.Orderscustomers.Feature'}</td>
                      <td class="amount text-right nowrap">
                        {displayPrice price=$order_wrapping_price currency=$currency->id}
                      </td>
                      <td class="partial_refund_fields current-edit" style="display:none;"></td>
                    </tr>
                    <tr id="total_shipping" style="display: none;">
                      <td class="text-right">{l s='Shipping' d='Admin.Catalog.Feature'}</td>
                      <td class="amount text-right nowrap" >
                        {displayPrice price=$order_shipping_price currency=$currency->id}
                      </td>
                      <td class="partial_refund_fields current-edit" style="display:none;">
                        <div class="input-group">
                          <div class="input-group-addon">
                            {$currency->sign}
                          </div>
                          <input type="text" name="partialRefundShippingCost" value="0" />
                        </div>
                        <p class="help-block"><i class="icon-warning-sign"></i> {l
                          s='(Max %s %s)'
                          sprintf=[Tools::displayPrice(Tools::ps_round($shipping_refundable, 2), $currency->id) , $smarty.capture.TaxMethod]
                          d='Admin.Orderscustomers.Feature'
                          }
                        </p>
                      </td>
                    </tr>

                    {*{d($order->getTaxCalculationMethod())}*}
                    {*{if ($order->getTaxCalculationMethod() == $smarty.const.PS_TAX_EXC)}*}
                    <tr id="total_taxes">
                      <td class="text-right">
                        <a class="pointer" data-original-title="" title="" onclick="sumarMontos();">
                          <span style="font-weight: bold;">(actualizar)</span>
                        </a>
                        {l s='Taxes' d='Admin.Global'}</td>
                      <td class="amount text-right nowrap" >{displayPrice price=($order->total_paid_tax_incl-$order->total_paid_tax_excl) currency=$currency->id}</td>
                      <td class="partial_refund_fields current-edit" style="display:none;"></td>
                    </tr>
                    {*{/if}*}
                    {assign var=order_total_price value=$order->total_paid_tax_incl}
                    <tr id="total_order">
                      <td class="text-right"><strong>{l s='Total' d='Admin.Global'}</strong></td>
                      <td class="amount text-right nowrap">
                        <strong>{displayPrice price=$order_total_price currency=$currency->id}</strong>
                      </td>
                      <td class="partial_refund_fields current-edit" style="display:none;"></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div style="display: none;" class="standard_refund_fields form-horizontal panel">
            <div class="form-group">
              {if ($order->hasBeenDelivered() && Configuration::get('PS_ORDER_RETURN'))}
                <p class="checkbox">
                  <label for="reinjectQuantities">
                    <input type="checkbox" id="reinjectQuantities" name="reinjectQuantities" />
                    {l s='Re-stock products' d='Admin.Orderscustomers.Feature'}
                  </label>
                </p>
              {/if}
              {if ((!$order->hasBeenDelivered() && $order->hasBeenPaid()) || ($order->hasBeenDelivered() && Configuration::get('PS_ORDER_RETURN')))}
                <p class="checkbox">
                  <label for="generateCreditSlip">
                    <input type="checkbox" id="generateCreditSlip" name="generateCreditSlip" onclick="toggleShippingCost()" />
                    {l s='Generate a credit slip' d='Admin.Orderscustomers.Feature'}
                  </label>
                </p>
                <p class="checkbox">
                  <label for="generateDiscount">
                    <input type="checkbox" id="generateDiscount" name="generateDiscount" onclick="toggleShippingCost()" />
                    {l s='Generate a voucher' d='Admin.Orderscustomers.Feature'}
                  </label>
                </p>
                <p class="checkbox" id="spanShippingBack" style="display:none;">
                  <label for="shippingBack">
                    <input type="checkbox" id="shippingBack" name="shippingBack" />
                    {l s='Repay shipping costs' d='Admin.Orderscustomers.Feature'}
                  </label>
                </p>
                {if $order->total_discounts_tax_excl > 0 || $order->total_discounts_tax_incl > 0}
                  <br/><p>{l s='This order has been partially paid by voucher. Choose the amount you want to refund:' d='Admin.Orderscustomers.Feature'}</p>
                  <p class="radio">
                    <label id="lab_refund_total_1" for="refund_total_1">
                      <input type="radio" value="0" name="refund_total_voucher_off" id="refund_total_1" checked="checked" />
                      {l s='Include amount of initial voucher: ' d='Admin.Orderscustomers.Feature'}
                    </label>
                  </p>
                  <p class="radio">
                    <label id="lab_refund_total_2" for="refund_total_2">
                      <input type="radio" value="1" name="refund_total_voucher_off" id="refund_total_2"/>
                      {l s='Exclude amount of initial voucher: ' d='Admin.Orderscustomers.Feature'}
                    </label>
                  </p>
                  <div class="nowrap radio-inline">
                    <label id="lab_refund_total_3" class="pull-left" for="refund_total_3">
                      {l s='Amount of your choice: ' d='Admin.Orderscustomers.Feature'}
                      <input type="radio" value="2" name="refund_total_voucher_off" id="refund_total_3"/>
                    </label>
                    <div class="input-group col-lg-1 pull-left">
                      <div class="input-group-addon">
                        {$currency->sign}
                      </div>
                      <input type="text" class="input fixed-width-md" name="refund_total_voucher_choose" value="0"/>
                    </div>
                  </div>
                {/if}
              {/if}
            </div>
            {if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Configuration::get('PS_ORDER_RETURN')))}
              <div class="row">
                {*                <input type="submit" name="cancelProduct" value="{if $order->hasBeenDelivered()}{l s='Return products'}{elseif $order->hasBeenPaid()}{l s='Refund products'}{else}{l s='Cancel products'}{/if}" class="btn btn-default" />*}
              </div>
            {/if}
          </div>
          <div style="display:none;" class="partial_refund_fields">
            <p class="checkbox">
              <label for="reinjectQuantitiesRefund">
                <input type="checkbox" id="reinjectQuantitiesRefund" name="reinjectQuantities" />
                {l s='Re-stock products' d='Admin.Orderscustomers.Feature'}
              </label>
            </p>
            <p class="checkbox">
              <label for="generateDiscountRefund">
                <input type="checkbox" id="generateDiscountRefund" name="generateDiscountRefund" onclick="toggleShippingCost()" />
                {l s='Generate a voucher' d='Admin.Orderscustomers.Feature'}
              </label>
            </p>
            {if $order->total_discounts_tax_excl > 0 || $order->total_discounts_tax_incl > 0}
              <p>{l s='This order has been partially paid by voucher. Choose the amount you want to refund: ' d='Admin.Orderscustomers.Feature'}</p>
              <p class="radio">
                <label id="lab_refund_1" for="refund_1">
                  <input type="radio" value="0" name="refund_voucher_off" id="refund_1" checked="checked" />
                  {l s='Product(s) price: ' d='Admin.Orderscustomers.Feature'}
                </label>
              </p>
              <p class="radio">
                <label id="lab_refund_2" for="refund_2">
                  <input type="radio" value="1" name="refund_voucher_off" id="refund_2"/>
                  {l s='Product(s) price, excluding amount of initial voucher: ' d='Admin.Orderscustomers.Feature'}
                </label>
              </p>
              <div class="nowrap radio-inline">
                <label id="lab_refund_3" class="pull-left" for="refund_3">
                  {l s='Amount of your choice: ' d='Admin.Orderscustomers.Feature'}
                  <input type="radio" value="2" name="refund_voucher_off" id="refund_3"/>
                </label>
                <div class="input-group col-lg-1 pull-left">
                  <div class="input-group-addon">
                    {$currency->sign}
                  </div>
                  <input type="text" class="input fixed-width-md" name="refund_voucher_choose" value="0"/>
                </div>
              </div>
            {/if}
            <br/>
            {*            <button type="submit" name="partialRefund" class="btn btn-default">*}
            {*              <i class="icon-check"></i> {l s='Partial refund' d='Admin.Orderscustomers.Feature'}*}
            {*            </button>*}
          </div>
        </div>
      </form>
    </div>

  </div>
  <div id="overlay"></div>
  {if $order->current_state == 6 || $order->current_state == 14 || $order->current_state == 15 || $order->current_state == 16}
    <style>
      #overlay:after {
        {if $order->current_state == 6 || $order->current_state == 14 || $order->current_state == 15}
        content: "ANULADO";
        {elseif $order->current_state == 16}
        content: "RECHAZADO";
        {/if}
        font-size: 15em;
        color: rgba(52, 166, 214, 0.17);
        z-index: 9999;
        transform: rotate(-20deg);

        display: flex;
        align-items: center;
        justify-content: center;
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;

        -webkit-pointer-events: none;
        -moz-pointer-events: none;
        -ms-pointer-events: none;
        -o-pointer-events: none;
        pointer-events: none;

        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        -o-user-select: none;
        user-select: none;
      }
      #overlay {
        background-color: rgba(0, 0, 0, 0.01);
        z-index: 123;
        /*position: absolute;*/
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
      }​

    </style>
  {/if}

  <script type="text/javascript">
    var tipo_documento_electronico = 'Boleta';

    // var geocoder =    new google.maps.Geocoder();
    // var delivery_map, invoice_map;
    {if $objComprobantes->cod_sunat >= 2000 && $objComprobantes->cod_sunat <= 3999 && $order->current_state != 16}
    $(document).ready(function(){
      $('#id_order_state').val(16);
      $('#submit_state').trigger('click');
    });
    {/if}



    var help_class_name = "";
    $(document).ready(function() {


      {*window.addEventListener("keyup", function(e){ if(e.keyCode == 27) window.location.href ="{$link->getAdminLink('AdminOrders')}" }, false);*}


      $('#txtNumeroDocumento').select();
      {if (int)$order->invoice_number > 0}
      $("#start_products form :input[type=button]").attr("disabled", "disabled");
      {/if}

      {if $objComprobantes->numero_comprobante != "" && $objComprobantes->cod_sunat == 0}
      var elem = $('#main');
      $(':input[type=button], select, :input[type=radio]', elem).css( {
        "pointer-events": "none",
        "border-color": "#999",
        "color": "#999",
        "background-color": "#f2f2f2",
        "opacity": ".65",
      } ).trigger("chosen:updated");
      {/if}


      $(".textarea-autosize").autosize();

      $('.datepicker').datepicker({
        prevText: '',
        nextText: '',
        dateFormat: 'yy-mm-dd',
        // Define a custom regional settings in order to use PrestaShop translation tools
        currentText: '{l s='Now' js=1}',
        closeText: '{l s='Done' js=1}',
        ampm: false,
        amNames: ['AM', 'A'],
        pmNames: ['PM', 'P'],
        timeFormat: 'hh:mm:ss tt',
        timeSuffix: '',
        timeOnlyTitle: '{l s='Choose Time' js=1}',
        timeText: '{l s='Time' js=1}',
        hourText: '{l s='Hour' js=1}',
        minuteText: '{l s='Minute' js=1}'
      });


      $('#div_datos_cliente input').not(":input[name=txtDireccion], :input[name=es_credito], :input[name=nro_guia_remision]").attr('disabled', true);

      {if !empty($certificado) && (bool)$certificado['active']}
      {else}
      if (parseInt($('#cb_tipo_documento :selected').data('codsunat')) === 6){
        $('#imprimir_factura').show();
      }
      {/if}

    });

    function limitText(field, maxChar){
      $(field).attr('maxlength',maxChar);
    }

    var num_doc = '{$customer->num_document}';

    $("#cb_tipo_documento").change(function (e) {
      var $this = $(this);
      e.preventDefault();
      //$this.button('loading');
      var value = parseInt($(this).find(':selected').data("codsunat"));
      $('#txtNumeroDocumento').val("");
      $('#txtNombre').val("");
      $('#txtDireccion').val("");
      // alert(value);
      if (value === 1) {
        limitText('#txtNumeroDocumento', 8);
        $('#imprimir_factura').hide();
      } else if(value === 4) {
        limitText('#txtNumeroDocumento', 12);
        $('#imprimir_factura').hide();
      }else if(value === 6) {
        limitText('#txtNumeroDocumento', 11);
        $('#imprimir_factura').show();
      }
    });


    var td_ache = '{$objComprobantes->tipo_documento_electronico}';
    $('#abrirCliente, #abrirEmpresa').on('click', function(evt, params) {
      var self = this;
      if (td_ache === 'Factura'){
        location.reload();
      }
      if ($(this).data("value") === "Boleta") {
        tipo_documento_electronico = "Boleta";
        limitText('#txtNumeroDocumento', 8);
        $('#div_datos_cliente input').attr('disabled', true);
        // $("#cb_tipo_documento option[data-codsunat='1']").attr("selected", "selected").attr('disabled', false);
        $("#cb_tipo_documento").attr('disabled', false);
        $('#div_datos_cliente').show();
        $('#abrirCliente').removeClass("btn-default").addClass("btn-primary");
        $('#abrirEmpresa').removeClass("btn-primary").addClass("btn-default");
        $('#f_compro').hide();
        $('#b_compro').show();

        if (parseInt($.trim($('#txtNumeroDocumento').val().length)) === 8){
          $("#cb_tipo_documento option[data-codsunat='1']").attr("selected", "selected").attr('disabled', false);
        }

      }
      if ($(this).data("value") === "Factura") {
        tipo_documento_electronico = "Factura";
        limitText('#txtNumeroDocumento', 11);
        $("#cb_tipo_documento option[data-codsunat='6']").attr("selected","selected");
        $("#cb_tipo_documento").attr('disabled', true);
        $('#abrirCliente').removeClass("btn-primary").addClass("btn-default");
        $('#abrirEmpresa').removeClass("btn-default").addClass("btn-primary");
        $('#f_compro').show();
        $('#b_compro').hide();

        if ($.trim($('#txtNombre').val()) === "")
          $('#f_compro').attr('disabled', true);

        if (parseInt($.trim($('#txtNumeroDocumento').val().length)) !== 11){
          $('#txtNumeroDocumento').val("").select();
          $('#txtNombre').val("");
          $('#txtDireccion').val("");
        }
      }

      td_ache = tipo_documento_electronico;

    });

    var count_click = 0;
    $('#b_compro, #f_compro').on('click', function(evt, params) {
      // console.log($.trim($('#txtNombre').val()))

      if ($.trim($('#txtNombre').val()) === "" || $.trim($('#txtNumeroDocumento').val()) === ""){
        alert("Faltan llenar algunos datos del cliente");
        return false;
      }

      count_click++;

      var self = this;
      // $('#b_compro').prop('disabled', true);
      // alert(tipo_documento_electronico);
      // return false;
      $('.adminorders').waitMe({
        effect: 'bounce',
        text: 'Creando y Enviando Comprobante a Sunat...',
        // bg : rgba(255,255,255,0.7),
        color: '#000',
        maxSize: '',
        textPos: 'vertical',
        fontSize: '',
        source: ''
      });

      // alert($(this).data("value"));
      $.ajax({
        type:"POST",
        url: "{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}",
        async: true,
        dataType: "json",
        data : {
          ajax: "1",
          token: "{getAdminToken tab='AdminOrders'}",
          tab: "AdminOrders",
          action: "realizarXMLComprobante",
          id_order: '{$order->id}',
          tipo_comprobante: $(this).data("value"), // campo boleta_factura
          //datos para comprobante electronico
          tipo_documento_electronico: tipo_documento_electronico,
          tipo_documento: $("#cb_tipo_documento :selected").val(),
          nro_documento: $("#txtNumeroDocumento").val(),

          id_customer: $("#id_customer").val(),
          nombre: $("#txtNombre").val(),
          direccion: $("#txtDireccion").val(),

          count_click: count_click,
        },
        success : function(res)
        {
          $('#mensaje_rapex').remove();
          if (res.respuesta){
            if (res.respuesta === "error"){
              $('#mensaje_sunat').html('<div class="alert alert-danger col-xs-12" id="mensaje_rapex" style="font-weight: bold">'+res.msj_error+'</div>');
              $('#tickets_pdfs').html('<span class="badge" > <a STYLE="color: #fff" target="_blank" href="'+res.ruta_ticket+'"><strong>PDF COMPROBANTE Ticket</strong></a> </span><span class="badge" > <a STYLE="color: #fff" target="_blank" href="'+res.ruta_pdf_a4+'"><strong>PDF COMPROBANTE A4</strong></a> </span>');
            }else{
              $('#mensaje_sunat').html('<div class="alert alert-success col-xs-12" id="mensaje_rapex" style="font-weight: bold">'+res.msj_sunat+'</div>');
              $('#tickets_pdfs').html('<span class="badge" > <a STYLE="color: #fff" target="_blank" href="'+res.ruta_ticket+'"><strong>PDF COMPROBANTE Ticket</strong></a> </span><span class="badge" > <a STYLE="color: #fff" target="_blank" href="'+res.ruta_pdf_a4+'"><strong>PDF COMPROBANTE A4</strong></a> </span><span class="badge" > <a STYLE="color: #fff" href="'+res.ruta_xml+'"><strong>XML COMPROBANTE</strong></a> </span><span class="badge" > <a STYLE="color: #fff" href="'+res.ruta_cdr+'"><strong>CDR COMPROBANTE</strong></a> </span>');
              $("#b_compro, #f_compro").attr('disabled', true);
            }
            $("#numero_comprobante_return").text(res.numero_comprobante);
            location.reload();
            // window.open(res.ruta_ticket, '_blank' );
            // location.reload();
          }
        },
        complete:function (res) {
          $('.adminorders').waitMe('hide');
          // console.log(res.responseJSON);
          if (res.responseJSON.result){
            if (res.responseJSON.result !== "error"){
              let text = res.responseText.split('<!');
              var returnedData = JSON.parse(text[0]);
              console.log(returnedData);
              // window.open(returnedData.ruta_ticket, '_blank' );
              // location.reload();
            }else{
              $('#mensaje_sunat').html('<div class="alert alert-danger col-xs-12" id="mensaje_rapex" style="font-weight: bold">'+res.responseJSON.msg[0]+'</div>');
            }
          }
        }
      });
    });

    $('#guardarCliente').on('click', function(evt, params) {
      // console.log($.trim($('#txtNombre').val()))

      if ($.trim($('#txtNombre').val()) === "" || $.trim($('#txtNumeroDocumento').val()) === ""){
        alert("Faltan llenar algunos datos del cliente");
        return false;
      }

      count_click++;

      var self = this;
      // $('#b_compro').prop('disabled', true);
      // alert(tipo_documento_electronico);
      // return false;
      $('.adminorders').waitMe({
        effect: 'bounce',
        text: 'Guardando cliente...',
        // bg : rgba(255,255,255,0.7),
        color: '#000',
        maxSize: '',
        textPos: 'vertical',
        fontSize: '',
        source: ''
      });

      // alert($(this).data("value"));
      $.ajax({
        type:"POST",
        url: "{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}",
        async: true,
        dataType: "json",
        data : {
          ajax: "1",
          token: "{getAdminToken tab='AdminOrders'}",
          tab: "AdminOrders",
          action: "guardarClienteOrder",
          id_order: '{$order->id}',
          tipo_documento: $("#cb_tipo_documento :selected").val(),
          nro_documento: $("#txtNumeroDocumento").val(),
          id_customer: $("#id_customer").val(),
          nombre: $("#txtNombre").val(),
          direccion: $("#txtDireccion").val(),
          count_click: count_click,
        },
        success : function(res)
        {
          $('#mensaje_rapex').remove();
          if (res.respuesta){
            if (res.respuesta === "error"){
              $('#mensaje_sunat').html('<div class="alert alert-danger col-xs-12" id="mensaje_rapex" style="font-weight: bold">'+res.msg+'</div>');
            }else{
              $('#mensaje_sunat').html('<div class="alert alert-success col-xs-12" id="mensaje_rapex" style="font-weight: bold">Cambio de cliente exitoso</div>');
              $('#datos_cliente').text(res.cliente);
            }
          }
        },
        complete:function (res) {
          $('.adminorders').waitMe('hide');
        }
      });
    });

    function sumarMontos() {
      $('.adminorders').waitMe({
        effect: 'bounce',
        text: 'Guardando...',
        //    bg : rgba(255,255,255,0.7),
        color: '#000',
        maxSize: '',
        textPos: 'vertical',
        fontSize: '',
        source: ''
      });
      $.ajax({
        type:"POST",
        url: "{$link->getAdminLink('AdminOrders')|addslashes}",
        async: true,
        dataType: "json",
        data : {
          ajax: "1",
          token: "{getAdminToken tab='AdminOrders'}",
          tab: "AdminOrders",
          action: "sumarMontos",
          id_order: '{$order->id}',
        },
        success : function(res)
        {
          if (res.result) {
            updateAmounts(res.order);
          }
          else{
            jAlert(res.error);
          }
        },
        complete: function(data) {
          // location.reload();
          $('.adminorders').waitMe('hide');
        },
      });
    }

    $("#txtDireccion").keyup(function (e) {
      var $this = $(this);
      var code = e.which;
      if (code === 13) e.preventDefault();
      if (code === 13) {
        $('#guardarCliente').trigger('click');
        var elem = $('#txtPagandoYO');
        var val = elem.val();
        elem.focus().val('').val(val);
      }
    });

    $("#nro_guia_remision").keyup(function (e) {
      var $this = $(this);
      var code = e.which;
      if (code === 13) e.preventDefault();
      if (code === 13) {
        var elem = $('#txtPagandoYO');
        var val = elem.val();
        elem.focus().val('').val(val);
      }
    });

    $("#txtNumeroDocumento").keyup(function (e) {
      var $this = $(this);
      var code = e.which;
      if(code===13) e.preventDefault();
      if(code===13){
        //$this.button('loading');
        var value = $(this).val();
        let id_tipo_documento = parseInt($('#cb_tipo_documento :selected').data("codsunat"));

        if (id_tipo_documento === 1){
          num_doc = value;
          limitText('#txtNumeroDocumento', 8);
          if (value.length < 8){
            if (parseInt(value) === 0){
              $(this).val("00000000");
            }else{
              alert("El número de documento debe tener 8 dígitos.");
              return false;
            }
          }
        }else if(id_tipo_documento === 4){
          num_doc = value;
          limitText('#txtNumeroDocumento', 12);
          if (value.length < 12){
            alert("El número de documento debe tener 12 dígitos.");
            return false;
          }
        }else if(id_tipo_documento === 6){
          num_doc = value;
          limitText('#txtNumeroDocumento', 11);
          if (value.length < 11) {
            alert("El número de RUC debe tener 11 dígitos.");
            return false;
          }
        }

        // $('.input_ache').remove();
        $.ajax({
          type: "POST",
          // url: "index.php?controller=AdminCustomers&token=" + token_admin_customers,
          url: "{$link->getAdminLink('AdminCustomers')|escape:'html':'UTF-8'}",
          async: true,
          dataType: "json",
          data: {
            ajax: "1",
            token: "{getAdminToken tab='AdminCustomers'}",
            tab: "AdminCustomers",
            action: "getDataDataBase",
            nruc: $.trim($(this).val()),
            id_order: '{$order->id}',
          },
          beforeSend: function () {
            $('.adminorders').waitMe({
              effect: 'timer',
              text: 'Buscando Cliente...',
              // bg : rgba(255,255,255,0.7),
              color: '#000',
              maxSize: '',
              textPos: 'vertical',
              fontSize: '',
              source: ''
            });
          },
        }).done(function (data, textStatus, jqXHR) {

          if (data['success'] != "false" && data['success'] != false) {
            // $("#json_code").text(JSON.stringify(data, null, '\t'));
            if (typeof(data['result']) != 'undefined') {
              $("#tbody").html("");

              $('#id_customer').val(data.result.id_customer);
              $('#txtNombre').val(data.result.firstname);
              $('#txtDireccion').val(data.result.direccion);
              $('#b_compro').attr('disabled', false);
              $('#txtDireccion').attr('disabled', false);

              $('#datos_cliente').removeAttr('href');
              var url = '{$link->getAdminLink('AdminCustomers')|escape:'html':'UTF-8'}&amp;id_customer='+data.result.id_customer+'&amp;viewcustomer';
              $('#datos_cliente').attr('href', url.replace('&amp;', '&'));
              $('#datos_cliente').text(data.result.firstname+" - "+$('#txtNumeroDocumento').val());
              // alert(url);

              if (parseInt(data.result.es_credito) === 1){
                $('#div_es_credito').show();
              }else{
                $('#div_es_credito').hide();
              }


            }
            //$this.button('reset');
            $("#error").hide();
            $(".result").show();
            $('.adminorders').waitMe('hide');

            var elem = $('#nro_guia_remision');
            var val = elem.val();
            elem.focus().val('').val(val);

          }
          else {
            // if (typeof(data['msg']) != 'undefined') {
            //     alert(data['msg']);
            // }


            $('#id_customer').val("");
            $('#txtNombre').val("");
            $('#txtDireccion').val("");
            $('#b_compro, #f_compro').attr('disabled', true);

            $.ajax({
              type: "POST",
              url: "{$link->getAdminLink('AdminCustomers')|escape:'html':'UTF-8'}",
              async: true,
              dataType: "json",
              data: {
                ajax: "1",
                token: "{getAdminToken tab='AdminCustomers'}",
                tab: "AdminCustomers",
                action: "getDataSunat",
                nruc: $.trim($this.val()),
                order_id: '{$order->id}'
              },
            })
                    .done(function (data, textStatus, jqXHR) {
                      if (data['success'] != "false" && data['success'] != false) {
                        // $("#json_code").text(JSON.stringify(data, null, '\t'));
                        if (typeof(data['result']) != 'undefined') {
                          $("#tbody").html("");

                          $('#id_customer').val(data.cliente.id);
                          $('#txtNombre').val(data.result.RazonSocial);
                          $('#b_compro').attr('disabled', false);
                          $('#txtDireccion').attr('disabled', false);
                          var url = '{$link->getAdminLink('AdminCustomers')|escape:'html':'UTF-8'}&amp;id_customer='+data.cliente.id+'&amp;viewcustomer';
                          $('#datos_cliente').attr('href', url.replace('&amp;', '&'));
                          $('#datos_cliente').text(data.result.RazonSocial+" - "+$('#txtNumeroDocumento').val());

                        }
                        //$this.button('reset');
                        $("#error").hide();
                        $(".result").show();
                        $('.adminorders').waitMe('hide');

                        var elem = $('#txtPagandoYO');
                        var val = elem.val();
                        elem.focus().val('').val(val);
                      }
                      else {
                        if (typeof(data['msg']) != 'undefined') {
                          alert(data['msg']);
                        }

                        $('#id_customer').val("");
                        $('#txtNombre').val("");
                        $('#b_compro, #f_compro').attr('disabled', false);
                        $('#div_datos_cliente input').attr('disabled', false);
                        $('.adminorders').waitMe('hide');
                      }

                      $('#b_compro, #f_compro').attr('disabled', false);
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                      alert("Solicitud fallida:" + textStatus);
                      $this.button('reset');
                      $('.adminorders').waitMe('hide');
                    });
            //$this.button('reset');
          }

        }).fail(function (jqXHR, textStatus, errorThrown) {
          alert("Solicitud fallida:" + textStatus);
          $this.button('reset');
          $('.adminorders').waitMe('hide');
        });
      }
    });

    $(document).ready(function () {
      $("#formAddPayment").submit(function (e) {
        $('.adminorders').waitMe({
          effect: 'bounce',
          text: 'Cargando...',
          color: '#000',
          maxSize: '',
          textPos: 'vertical',
          fontSize: '',
          source: ''
        });

        //disable the submit button
        setTimeout(function () { disableButton(); }, 0);

        return true;
      });
    });

    $('select.chosen').each(function(k, item){
      $(item).chosen({ search_contains: true, width: '100%', });
    });

  </script>
{/block}
