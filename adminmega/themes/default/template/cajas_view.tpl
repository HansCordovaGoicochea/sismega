{*<style>*}
{*	.bo_search_form #toolbar_caja_dolares, #toolbar_caja_soles {*}
{*		border-top-left-radius: 0;*}
{*		border-top-right-radius: 55px;*}
{*		border-bottom-right-radius: 55px;*}
{*		border-bottom-left-radius: 0;*}
{*		color: #363a41;*}
{*		background: #fff;*}
{*		border: 1px solid #bbcdd2;*}
{*		border-left: none;*}
{*		font-style: italic;*}
{*		-webkit-box-shadow: none;*}
{*		box-shadow: none;*}
{*		-webkit-transition: none;*}
{*		transition: none;*}
{*	}*}
{*	.bo_search_form .form-group {*}
{*		width: 145px!important;*}
{*	}*}
{*	@media (max-width: 767px) {*}
{*		.bootstrap .bo_search_form {*}
{*			display: inline-flex !important;*}
{*		}*}
{*		#header_logo{*}
{*			display: none!important;*}
{*		}*}
{*		.bo_search_form .form-group {*}
{*			width: 20vh!important;*}
{*		}*}
{*	}*}

{*	#toolbar_caja_soles, #toolbar_caja_dolares{*}
{*		font-size: 1.7em;*}
{*		padding: 0px 5px;*}
{*	}*}
{*</style>*}
{*{assign var="monto_caja_abierta" value=PosArqueoscaja::getCajaLast($shop->id)}*}
{*{d($monto_caja_abierta)}*}
<form id="header_search" class="component bo_search_form toolbar_cajas_form form-inline" style="display:block!important;">
{*	{if !empty($monto_caja_abierta) && $monto_caja_abierta['estado'] == 1}*}
{*	Caja Soles*}
{*	<div class="form-group div_soles">*}
{*		<span class="input-group">*}
{*			<span class="input-group-btn">*}
{*				<span class="btn btn-primary">*}
{*					<i id="search_type_icon" class="material-icons" style="top: -6px;">S/</i>*}
{*				</span>*}
{*			</span>*}
{*			<span id="toolbar_caja_soles" class="form-control">{$monto_caja_abierta['monto_operaciones']}</span>*}
{*		</span>*}
{*	</div>*}
{*	Caja Dolares*}
{*	<div class="form-group div_dolares">*}
{*		<span class="input-group">*}
{*			<span class="input-group-btn">*}
{*				<span class="btn btn-primary">*}
{*					<i id="search_type_icon" class="material-icons">attach_money</i>*}
{*				</span>*}
{*			</span>*}
{*			<span id="toolbar_caja_dolares" class="form-control">{$monto_caja_abierta['monto_operaciones_dolares']}</span>*}
{*		</span>*}
{*	</div>*}
{*	{/if}*}
</form>
