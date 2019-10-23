{**
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
 *}
{extends file="helpers/form/form.tpl"}
{block name="before"}
	<style>
		.ache_radio{
			display: inline-block!important;
		}
		.sunat-button {
			text-transform: uppercase!important;
			font-weight: 600!important;
			background-color: #0264af!important;
			border-color: #0264af!important;
			color: #fff!important;
		}
	</style>
{/block}
{*{block name="input_row"}*}
{*	{$smarty.block.parent}*}
{*{/block}*}
{block name="after"}
	<script>
		function limitText(field, maxChar){
			$(field).attr('maxlength',maxChar);
		}

		$(function () {
			limitText('#num_document', 8);
			// alert("dfdf");
			$( "#persona" ).prop( "checked", true );

			$('input[type=radio][name="TYPE_CUSTOMER_ACHE"]').change(function () {
				// alert(this.value);
				$("#id_document :selected").removeAttr('selected');
				$('.error_ache').remove();
				$('#num_document').val('');
				$('#firstname').val('');
				$('#num_document').parent().parent().parent().parent().show();
				if (this.value === 'empresa'){
					$("#id_document option[data-codsunat='6']").attr("selected","selected");
					$("#id_document").parent().parent().hide();
					limitText('#num_document', 11);
					$('#direccion').parent().parent().find('label').addClass('required');
					$('#firstname').parent().parent().find('label > span').text('Razón Social');
					$('#direccion').attr('required', 'required');
				}
				if (this.value === 'persona'){
					$("#id_document option[data-codsunat='1']").attr("selected","selected");
					$("#id_document").parent().parent().show();
					limitText('#num_document', 8);
					$('#direccion').parent().parent().find('label').removeClass('required');
					$('#firstname').parent().parent().find('label > span').text('Nombre Legal');
					$('#direccion').removeAttr('required');
				}
			});

			$('#id_document').change(function () {
				$('.error_ache').remove();
				let data = $(this).find(':selected').data('codsunat');
				// alert(data);
				$('#num_document').show();
				$('#num_document').val('');
				$('#firstname').parent().parent().find('label > span').text('Nombre Legal');
				$('#num_document').parent().parent().parent().parent().show();
				if (data === 6){
					$( "#empresa" ).prop( "checked", true );
					$( "#persona" ).prop( "checked", false );
					limitText('#num_document', 11);
					$('#direccion').parent().parent().find('label').addClass('required');
					$('#direccion').attr('required', 'required');
					$('#firstname').parent().parent().find('label > span').text('Razón Social');
				}
				else if (data === 4 || data === 1){
					$( "#empresa" ).prop( "checked", false );
					$( "#persona" ).prop( "checked", true );
					if (data === 1){
						limitText('#num_document', 8);
					}else{
						limitText('#num_document', 15);
					}
					$('#direccion').parent().parent().find('label').removeClass('required');
					$('#direccion').removeAttr('required');
				}
				else{
					$( "#empresa" ).prop( "checked", false );
					$( "#persona" ).prop( "checked", true );
					$('#direccion').parent().parent().find('label').removeClass('required');
					$('#direccion').removeAttr('required');
					$('#num_document').parent().parent().parent().parent().hide();
					$('#num_document').val('-');
				}
			});

			$('#customer_form').live('submit', function(e) {
				$('body').waitMe({
					effect: 'timer',
					text: 'Consultando...',
					color: '#000',
					maxSize: '',
					textPos: 'vertical',
					fontSize: '',
					source: ''
				});
				$('.error_ache').remove();
				let data = $("#id_document").find(':selected').data('codsunat');
				if (data === 6 && $.trim($('#direccion').val()) === ""){
					$('#direccion').after('<small style="color: red" class="error_ache">La dirección es obligatoria</small>');
					$('body').waitMe('hide');
					return false;
				}
				if ($.trim($('#num_document').val()) === ""){
					$('#num_document').after('<small style="color: red" class="error_ache">Número de documento es obligatorio</small>');
					$('body').waitMe('hide');
					return false;
				}
				if ($.trim($('#firstname').val()) === ""){
					$('#firstname').after('<small style="color: red" class="error_ache">Nombre Legal es obligatorio</small>');
					$('body').waitMe('hide');

					return false;
				}
			});

			$('#num_document').keyup(function () {
				let data = $("#id_document").find(':selected').data('codsunat');
				if (data === 6){
					$(this).val().length === 11 ? $('#buscar_sunat').removeAttr('disabled') : $('#buscar_sunat').attr('disabled', 'disabled');
				}
				else if (data === 1){
					$(this).val().length === 8 ? $('#buscar_sunat').removeAttr('disabled') : $('#buscar_sunat').attr('disabled', 'disabled');
				}
				else if (data === 4){
					$(this).val().length >= 8 || $(this).val().length <= 15 ? $('#buscar_sunat').removeAttr('disabled') : $('#buscar_sunat').attr('disabled', 'disabled');
				}

			})

			$("#num_document, #ruc_supplier").change(function (e) {
				var $this = $(this);
				e.preventDefault();

				var value = $("#num_document").val();
				var data = $("#id_document").find(':selected').data('codsunat');
				if (data === 6){
					limitText('#num_document', 11);
					if (value.length < 11){
						alert("El número de RUC debe tener 11 dígitos.");
						$('#customer_form_submit_btn').attr('disabled', true);
						return false;
					}
					else{
						$('#customer_form_submit_btn').attr('disabled', false);
					}
				}
				else if (data === 1){
					limitText('#num_document', 8);
					if (value.length < 8){
						alert("El número de documento debe tener 8 dígitos.");
						$('#customer_form_submit_btn').attr('disabled', true);
						return false;
					}
					else{
						$('#customer_form_submit_btn').attr('disabled', false);
					}
				}
				else if (data === 4){
					limitText('#num_document', 12);
					if (value.length >= 8 || value.length <= 12){
						alert("El número de documento debe tener entre 8 y 12 dígitos.");
						$('#customer_form_submit_btn').attr('disabled', true);
						return false;
					}
					else{
						$('#customer_form_submit_btn').attr('disabled', false);
					}
				}else{
					$('#customer_form_submit_btn').attr('disabled', false);
				}
			});
		});


		function traerDatosSunat() {
			// alert("buscar")
			$('.input_ache').remove();
			var data_cod_sunat = $("#id_document").find(':selected').data('codsunat');
			$.ajax({
				type: "POST",
				url: "{$link->getAdminLink('AdminCustomers')|addslashes}",
				async: true,
				dataType: "json",
				data: {
					ajax: "1",
					token: "{getAdminToken tab='AdminCustomers'}",
					tab: "AdminCustomers",
					action: "getDataDataBase",
					nruc: $.trim($("#num_document").val()),
				},
				beforeSend: function () {
					$('body').waitMe({
						effect: 'timer',
						text: 'Consultando...',
						color: '#000',
						maxSize: '',
						textPos: 'vertical',
						fontSize: '',
						source: ''
					});
				},
			})
					.done(function (data, textStatus, jqXHR) {

						if (data['success'] != "false" && data['success'] != false) {
							// $("#json_code").text(JSON.stringify(data, null, '\t'));
							if (typeof (data['result']) != 'undefined') {
								if(!$(".input_ache").length) {
									$('#customer_form').append('<input type="hidden" class="input_ache" name="id_customer" id="id_customer" value="'+data.result.id_customer+'">');
								}

								$('#firstname').val(data.result.firstname);
								$('#direccion').val(data.result.direccion);
								$('#telefono').val(data.result.telefono);
								$('#telefono_celular').val(data.result.telefono_celular);
								$('#email').val(data.result.email);

							}

							$('body').waitMe('hide');
						} else {

							$('#firstname').val("");
							$('#direccion').val("");
							$('#telefono').val("");
							$('#telefono_celular').val("");

							$.ajax({
								type: "POST",
								url: "{$link->getAdminLink('AdminCustomers')|addslashes}",
								async: true,
								dataType: "json",
								data: {
									ajax: "1",
									token: "{getAdminToken tab='AdminCustomers'}",
									tab: "AdminCustomers",
									action: "getDataSunat",
									nruc: $.trim(($('#num_document').val()))
								},
								beforeSend: function () {
									$('body').waitMe({
										effect: 'timer',
										text: 'Consultando...',
										color: '#000',
										maxSize: '',
										textPos: 'vertical',
										fontSize: '',
										source: ''
									});
								},
							})
									.done(function (data, textStatus, jqXHR) {
										if (data['success'] != "false" && data['success'] != false) {
											// $("#json_code").text(JSON.stringify(data, null, '\t'));
											if (typeof (data['result']) != 'undefined') {
												if(!$(".input_ache").length) {
													$('#customer_form').append('<input type="hidden" class="input_ache" name="id_customer" id="id_customer" value="'+data.cliente.id+'">');
												}
												$('#firstname').val(data.result.RazonSocial);
												// }else{
												// 	$('#firstname').val(data.result.NombreComercial);
												// }
												$('#direccion').val(data.result.Direccion.replace(new RegExp('-', 'g'), ""));
											}

											$('body').waitMe('hide');
										} else {
											if (typeof (data['msg']) != 'undefined') {
												alert(data['msg']);
											}
											$('#firstname').val("");
											$('#direccion').val("");
											$('#telefono').val("");
											$('#telefono_celular').val("");

											$('body').waitMe('hide');
										}
									})
									.fail(function (jqXHR, textStatus, errorThrown) {
										alert("Solicitud fallida:" + textStatus);
										$('body').waitMe('hide');
									});
							//$this.button('reset');
						}

					}).fail(function (jqXHR, textStatus, errorThrown) {
				alert("Solicitud fallida:" + textStatus);
				$('body').waitMe('hide');
			});
		}

	</script>
{/block}



