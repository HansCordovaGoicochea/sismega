
{extends file="helpers/form/form.tpl"}
{block name="before"}
	<style>
		.ache_radio{
			display: inline-block!important;
		}
	</style>
{/block}
{*{block name="input_row"}*}
{*	{$smarty.block.parent}*}
{*{/block}*}
{block name="after"}
	<script>
		$(function () {

			// alert($( "input[type=radio][name='tiene_comprobante']:checked" ).val());
			if (parseInt($( "input[type=radio][name='tiene_comprobante']:checked" ).val()) === 1){
				showFieldsPagos();
			}else{
				hideFieldsPagos()
			}


			$('input[type=radio][name="tiene_comprobante"]').change(function () {

				if (parseInt(this.value) === 0){
					showFieldsPagos();
					hideFieldsPagos()
				}
				if (parseInt(this.value) === 1){
					showFieldsPagos();
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

		});

		function hideFieldsPagos() {
			$("#numero_doc_iden").parent().parent().hide();
			$("#nombre_empresa").parent().parent().hide();
			$( "#factura, #boleta, #recibo" ).parent().parent().parent().parent().hide();
			$("#numero_comprobante").parent().parent().hide();
		}
		function showFieldsPagos() {
			$("#numero_doc_iden").parent().parent().show();
			$("#nombre_empresa").parent().parent().show();
			$( "#factura, #boleta, #recibo" ).parent().parent().parent().parent().show();
			$("#numero_comprobante").parent().parent().show();
		}

	</script>
{/block}



