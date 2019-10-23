
<script>
	var dashboard_ajax_url = '{$link->getAdminLink('AdminDashboard')}';
	var adminstats_ajax_url = '{$link->getAdminLink('AdminStats')}';
	var no_results_translation = '{l s='No result' js=1}';
	var dashboard_use_push = '{$dashboard_use_push|intval}';
	var read_more = '{l s='Read more' js=1}';

</script>
<div id="dashboard">
	<div class="row">
		<div class="col-lg-12">
			{if $warning}
				<div class="alert alert-warning">{$warning}</div>
			{/if}
			<div id="calendar" class="panel">
				<form action="{$action|escape}" method="post" id="calendar_form" name="calendar_form" class="form-inline">
					<div class="btn-group">
						<button type="button" name="submitDateDay" class="btn btn-default submitDateDay{if isset($preselect_date_range) && $preselect_date_range == 'day'} active{/if}">
							{l s='Day' d='Admin.Global'}
						</button>
						<button type="button" name="submitDateMonth" class="btn btn-default submitDateMonth{if (!isset($preselect_date_range) || !$preselect_date_range) || (isset($preselect_date_range) && $preselect_date_range == 'month')} active{/if}">
							{l s='Month' d='Admin.Global'}
						</button>
						<button type="button" name="submitDateYear" class="btn btn-default submitDateYear{if isset($preselect_date_range) && $preselect_date_range == 'year'} active{/if}">
							{l s='Year' d='Admin.Global'}
						</button>
						<button type="button" name="submitDateDayPrev" class="btn btn-default submitDateDayPrev{if isset($preselect_date_range) && $preselect_date_range == 'prev-day'} active{/if}">
							{l s='Day' d='Admin.Global'}-1
						</button>
						<button type="button" name="submitDateMonthPrev" class="btn btn-default submitDateMonthPrev{if isset($preselect_date_range) && $preselect_date_range == 'prev-month'} active{/if}">
							{l s='Month' d='Admin.Global'}-1
						</button>
						<button type="button" name="submitDateYearPrev" class="btn btn-default submitDateYearPrev{if isset($preselect_date_range) && $preselect_date_range == 'prev-year'} active{/if}">
							{l s='Year' d='Admin.Global'}-1
						</button>

						{*<button type="submit" name="submitDateRealTime" class="btn btn-default submitDateRealTime {if $dashboard_use_push}active{/if}" value="{!$dashboard_use_push|intval}">*}
						{*{l s='Real Time'}*}
						{*</button>*}
					</div>
					<input type="hidden" name="datepickerFrom" id="datepickerFrom" value="{$date_from|escape}" class="form-control">
					<input type="hidden" name="datepickerTo" id="datepickerTo" value="{$date_to|escape}" class="form-control">
					<input type="hidden" name="preselectDateRange" id="preselectDateRange" value="{if isset($preselect_date_range)}{$preselect_date_range}{/if}" class="form-control">
					<div class="form-group pull-right">
						<button id="datepickerExpand" class="btn btn-default" type="button">
							<i class="icon-calendar-empty"></i>
							<span class="hidden-xs">
								{l s='From' d='Admin.Global'}
								<strong class="text-info" id="datepicker-from-info">{$date_from|escape}</strong>
								{l s='To' d='Admin.Global'}
								<strong class="text-info" id="datepicker-to-info">{$date_to|escape}</strong>
								<strong class="text-info" id="datepicker-diff-info"></strong>
							</span>
							<i class="icon-caret-down"></i>
						</button>
					</div>
					{$calendar}
				</form>
			</div>
		</div>
	</div>
	<div class="panel row">
		<div class="col-md-12 col-lg-12" id="hookDashboardZoneTwo">
			{foreach $reporte_colaboradores as $key => $item}
				<strong><a href="{$link->getAdminLink('AdminReporteServiciosColaborador')}&id_colaborador={$item.id_colaborador}&fi='{$date_from|escape} 00:00:00'&ff='{$date_to|escape} 23:59:59'">{$item.colaborador}</a></strong>
				<strong class="pull-right">{$item.cantidad}</strong>
				<div class="progress">
					<div class="progress-bar progress-bar-info" role="progressbar" aria-valuemin="0" aria-valuemax="999999999999999999" style="width: {$item.cantidad}px;">
						<span></span>
					</div>
				</div>
				{foreachelse}
				<table class="table">
					<tbody >
					<tr >
						<td class="list-empty">
							<div class="list-empty-msg">
								<i class="icon-warning-sign list-empty-icon"></i>
								No hay servicios atendidos en estas fechas
							</div>
						</td>
					</tr>
					</tbody>
				</table>
				</table>

			{/foreach}
		</div>
	</div>
</div>
