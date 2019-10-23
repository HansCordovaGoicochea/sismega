{extends file="helpers/list/list_footer.tpl"}
{block name="after"}
	{include file="./modal.tpl" }
	{include file='./cierre_caja.tpl'}
	<script>
		$('.cierre').click(function () {
			let id_pos_arqueoscaja = parseInt($(this).data('id_pos_arqueoscaja'));
			$('#id_pos_arqueoscaja').val(id_pos_arqueoscaja);

			$('#modaCierreCaja').modal({
				backdrop: 'static',
				keyboard: false,
				closable: false
			}).modal('show');

			$('#modaCierreCaja').on('shown.bs.modal', function() {
				var elem = $('#modaCierreCaja #monto_cierre');
				var val = elem.val();
				elem.focus().val('').val(val);

			});

			$("#modaCierreCaja #monto_cierre").val("0.00");
			$("#modaCierreCaja #nota_cierre").val("");



		});
	</script>
{/block}