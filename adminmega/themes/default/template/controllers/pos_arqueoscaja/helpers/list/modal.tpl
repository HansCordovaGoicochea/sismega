<style>
	#abrirCaja .contadorCaja {
		background: #fff;
		/*border-radius: 20px;*/
		text-align: center;
		font-size: 1.6em !important;
		height: 47px;
		border-top-right-radius: 55px;
		border-bottom-right-radius: 55px;
		padding: 10px 5px;
	}
	.vertical-alignment-helper {
		display:table;
		height: 100%;
		width: 100%;
		pointer-events:none;
	}
	.vertical-align-center {
		/* To center vertically */
		display: table-cell;
		vertical-align: middle;
		pointer-events:none;
	}
	.modal-content {
		/* Bootstrap sets the size of the modal in the modal-dialog class, we need to inherit it */
		width:inherit;
		max-width:inherit; /* For Bootstrap 4 - to avoid the modal window stretching full width */
		height:inherit;
		/* To center horizontally */
		margin: 0 auto;
		pointer-events:all;
	}

	/*stilos de cajas de billetes*/

	#contBilletes {
		max-width: 522px;
		margin: 10px auto;
	}

	.row .threecol {
		width: 23.4%;
	}

	.contBillete {
		padding: 0 !important;
		margin-top: 3px;
		margin-bottom: 3px;
	}

	.onecol, .twocol, .threecol, .fourcol, .fivecol, .sixcol, .sevencol, .eightcol, .ninecol, .tencol, .elevencol {
		margin-right: 1.8%;
		float: left;
		min-height: 1px;
	}

	.contBillete button.billete {
		margin: 0;
		border: 0 none;
		padding: 7px;
		border-top-right-radius: 0;
		border-top-left-radius: 5px;
		border-bottom-left-radius: 5px;
		border-bottom-right-radius: 0;
	}
	.row .sixcol {
		width: 49%;
	}

	.contBillete input.billeteomoneda, .contBillete input.billeteomoneda_cerrar {
		background-color: #fff;
		color: #000;
		padding: 7px;
		border: 0 none;
		width: 49% !important;
		border-top-left-radius: 0;
		border-bottom-left-radius: 0;
		border-top-right-radius: 5px;
		border-bottom-right-radius: 5px;
		border: 1px solid #e3e3e3;
	}

	.billeteomoneda {
		width: 43px !important;
		margin-right: 5px;
		float: left;
		text-align: center;
	}

	.last {
		margin-right: 0px !important;
	}

	.hasContent{
		background:#DFF0D8 !important
	}
</style>
<div class="modal fade" id="nuevo_arqueo{$table}">
	<div class="vertical-alignment-helper">
		<div class="modal-dialog vertical-align-center">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h2 class="text-center">Nuevo arqueo de caja</h2>
			</div>
			<div class="modal-body">
				<div id="abrirCaja" role="main" class="ui-content">
					<div class="row form-group hide">
						<div class="text-center">
							<label class="text-center">Seleccione la caja que va a abrir</label>
						</div>
						<select name="id_pos_caja" id="id_pos_caja" class="form-control">
							{foreach PosCaja::getCajas() as $caja}
								<option value="{$caja.id_pos_caja}">{$caja.nombre_caja}</option>
							{/foreach}
						</select>
					</div>
					<div class="text-center">
						<label class="text-center">Escriba el dinero en caja</label>
					</div>
					<div class="contDineroEnCaja row">
						<div class="form-group col-lg-offset-4 col-lg-4 col-xs-12">
							<div class="input-group input-group-lg">
								<span class="input-group-addon" id="helpId" style="border-top-left-radius: 55px; border-bottom-left-radius: 55px; font-size: 1.6em !important;">S/</span>
								<input type="number"
									   class="form-control text-center col-lg-2 contadorCaja" name="monto_apertura" id="monto_apertura" aria-describedby="helpId"
									   value="0.00" step="0.10">
							</div>
						</div>
					</div>
					<div class="row text-center">
						<label class="text-center">o seleccione uno a uno los billetes/monedas:</label>
					</div>
					<div id="contBilletes" class="row">
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">500</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[500]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">200</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[200]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">100</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[100]" value="">
						</div>
						<div class="contBillete threecol last">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">50</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[50]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">20</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[20]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">10</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[10]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">5</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[5]" value="">
						</div>
						<div class="contBillete threecol last">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">2</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[2]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">1</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[1]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">0.5</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[0.5]" value="">
						</div>
						<div class="contBillete threecol">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">0.2</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[0.2]" value="">
						</div>
						<div class="contBillete threecol last">
							<button class="billete sixcol ui-btn ui-shadow ui-corner-all">0.1</button>
							<input type="text" data-role="none" class="billeteomoneda  sixcol last" name="billeteOmoneda[0.1]" value="">
						</div>
					</div>
					<p>
						<textarea name="nota_apertura" id="nota_apertura" cols="20" rows="2" aria-describedby="descId"></textarea>
						<small id="descId" class="form-text text-muted">Alguna observación al abrir la caja</small>
					</p>

				</div>
			</div>
			<div class="modal-footer">
				<button id="btnabrirCaja" name="btnabrirCaja" type="submit" tabindex="4" class="btn btn-primary btn-lg btn-block ladda-button" data-style="slide-up" data-spinner-color="white" >
					<span class="ladda-label">
						{l s='Abrir Caja' d='Admin.Login.Feature'}
					</span>
				</button>
			</div>
		</div>
	</div>
	</div>
</div>
<script type="text/javascript">
	var existe_cajas = '{$exist_cajas}';
	//todo: ladda init
	var l = new Object();
	function clickAbricaja() {
		l = Ladda.create( document.querySelector( '#btnabrirCaja' ) );
	}
	$(document).ready(function(){

	});
	function abrirModalArqueo($id_modal) {
		// $('#importProgress').on('hidden.bs.modal', function () {
		// 	window.location.href = window.location.href.split('#')[0]; // reload same URL but do not POST again (so in GET without param)
		// })
		if (parseInt(existe_cajas) > 0){
			$($id_modal).modal({ backdrop: 'static', keyboard: false, closable: false});
			$($id_modal).modal('show');
		}else{
			$.growl.error({
				title: "Alerta!",
				message: "No hay ninguna caja disponible. Cierre o cree una caja",
				fixed: true,
				size: "large",
				duration: 8000
			});
		}
	}

	$('#btnabrirCaja').click(function () {
		if (parseInt(existe_cajas) > 0){
			$.ajax({
				type:"POST",
				url: "{$link->getAdminLink('AdminPosArqueoscaja')|addslashes}",
				async: true,
				dataType: "json",
				data : {
					ajax: "1",
					token: "{getAdminToken tab='AdminPosArqueoscaja'}",
					tab: "AdminPosArqueoscaja",
					action: "abrirCaja",
					id_pos_caja: $('#id_pos_caja').val(),
					monto_apertura: $('#monto_apertura').val(),
					nota_apertura: $('#nota_apertura').val(),
				},
				beforeSend: function(){
					// $('body').waitMe({
					// 	effect: 'timer',
					// 	text: 'Cargando...',
					// 	color: '#000',
					// 	maxSize: '',
					// 	textPos: 'vertical',
					// 	fontSize: '',
					// 	source: ''
					// });
					clickAbricaja();
					l.start();
				},
				success : function(res)
				{
					if (res.result){
						$.growl.notice({ title: "Exito!",message: "Arqueo creado correctamente!!!" });
						// l.stop();
						location.reload();
						{*window.location.replace("{$link->getAdminLink('AdminVender')|addslashes}");*}
					}else{
						$.growl.error({
							title: "Alerta!",
							message: ""+res.error+"",
							fixed: true,
							size: "large",
							duration: 8000
						});
					}

				},
				complete: function (res) {

				}
			});
		}else{
			$.growl.error({
				title: "Alerta!",
				message: "No hay ninguna caja disponible. Cierre o cree una caja",
				fixed: true,
				size: "large",
				duration: 8000
			});
		}

	})


	$(".billeteomoneda").on("keyup",function(event){
		var valor = 0;
		$(".billeteomoneda").each(function(){
			var valInput = $(this).val();
			if(valInput !== "")
				valor += valInput * $(this).prev('button').text();
		});
		$("#abrirCaja .contadorCaja").val(valor);
		efectoGuardado("#abrirCaja .contadorCaja");
	});

	function efectoGuardado(elemento){
		if($(elemento).is("input")){
			$(elemento).addClass("hasContent");
			setTimeout(function(){ $(elemento).removeClass("hasContent"); }, 300);
		}else if($(elemento).is("select")){
			var colorAntes = $(elemento).css('backgroundColor');
			$(elemento+'-button').animate({ backgroundColor: "#DFF0D8"}, 200 ).delay(300).animate({ backgroundColor: colorAntes}, 200 );
		}else{
			var colorAntes = $(elemento).css('backgroundColor');
			if(typeof colorAntes == "undefined")
				colorAntes = "#FFFFFF";
			else
				colorAntes = rgb2hex(colorAntes);
			$(elemento).animate({ backgroundColor: "#DFF0D8"}, 200 ).delay(300).animate({ backgroundColor: colorAntes}, 200 );
		}
	}

	function rgb2hex(rgb){
		rgb = rgb.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
		return (rgb && rgb.length === 4) ? "#" +
				("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
				("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
				("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : '';
	}
</script>
