
{if $bo_theme == "new-theme"}
	<div id="notif" class="notification-center dropdown dropdown-clickable">
		<button class="btn notification js-notification dropdown-toggle" data-toggle="dropdown">
			<i class="material-icons">notifications_none</i>
			<span id="notifications-total" class="count hide">0</span>
		</button>
		<div class="dropdown-menu dropdown-menu-right js-notifs_dropdown">
			<div class="notifications">
				<ul class="nav nav-tabs" role="tablist">
					{$active = "active"}
					{if $show_new_orders}
						<li class="nav-item">
							<a
									class="nav-link {$active}"
									id="orders-tab"
									data-toggle="tab"
									data-type="order"
									href="#orders-notifications"
									role="tab"
							>
								{l s='Orders[1][/1]' html=true sprintf=['[1]' => '<span id="_nb_new_orders_">', '[/1]' => '</span>'] d='Admin.Navigation.Notification'}
							</a>
						</li>
						{$active = ""}
					{/if}
					{if $show_new_customers}
						<li class="nav-item">
							<a
									class="nav-link {$active}"
									id="customers-tab"
									data-toggle="tab"
									data-type="customer"
									href="#customers-notifications"
									role="tab"
							>
								{l s='Customers[1][/1]' html=true sprintf=['[1]' => '<span id="_nb_new_customers_">', '[/1]' => '</span>'] d='Admin.Navigation.Notification'}
							</a>
						</li>
						{$active = ""}
					{/if}
				</ul>

				<!-- Tab panes -->
				<div class="tab-content">
					{$active = "active"}
					{if $show_new_orders}
						<div class="tab-pane {$active} empty" id="orders-notifications" role="tabpanel">
							<p class="no-notification">
								{l s='No new order for now :(' d='Admin.Navigation.Notification'}<br>
								{$no_order_tip}
							</p>
							<div class="notification-elements"></div>
						</div>
						{$active = ""}
					{/if}
					{if $show_new_customers}
						<div class="tab-pane {$active} empty" id="customers-notifications" role="tabpanel">
							<p class="no-notification">
								{l s='No new customer for now :(' d='Admin.Navigation.Notification'}<br>
								{$no_customer_tip}
							</p>
							<div class="notification-elements"></div>
						</div>
						{$active = ""}
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<li id="notification" class="dropdown">
		<a href="javascript:void(0);" class="notification dropdown-toggle notifs">
			<i class="material-icons">notifications_none</i>
			<span id="total_notif_number_wrapper" class="notifs_badge hide">
			<span id="total_notif_value">{$total}</span>
		</span>
		</a>
		<div class="dropdown-menu dropdown-menu-right notifs_dropdown">
			<div class="notifications">
				<ul class="nav nav-tabs" role="tablist">

					<li class="nav-item active" style="width: 50%">
						<a class="nav-link" data-toggle="tab" data-type="cita" href="#citas-notifications" role="tab" id="orders-tab">{l s='Prox. Citas' d='Admin.Navigation.Header'}<span id="citas_notif_value"></span></a>
					</li>

					<li class="nav-item" style="width: 50%">
						<a class="nav-link" data-toggle="tab" data-type="cumples" href="#cumples-notifications" role="tab" id="cumples-tab">{l s='Prox. Cumpleaños' d='Admin.Navigation.Header'}<span id="customers_notif_value"></span></a>
					</li>

				</ul>
				<!-- Tab panes -->
				<div class="tab-content">

					<div class="tab-pane active empty" id="citas-notifications" role="tabpanel">
						<p class="no-notification">
							{l s='No hay próximas citas por ahora. :(' d='Admin.Navigation.Notification'}<br>
						</p>
						<div class="notification-elements"></div>
					</div>

					<div class="tab-pane empty" id="cumples-notifications" role="tabpanel">
						<p class="no-notification">
							{l s='No hay próximos cumpleaños por ahora. :(' d='Admin.Navigation.Notification'}<br>
						</p>
						<div class="notification-elements"></div>
					</div>

				</div>
			</div>
		</div>
	</li>
	<script>
		$("#citas-notifications").children('.notification-elements').empty();
		{if $total > 0}
		$("#citas-notifications").removeClass('empty');
		$("#citas_notif_value").text(' (' + {$total} + ')').attr('data-nb', {$total});
		{else}
		$("#citas-notifications").addClass('empty');
		$("#citas_notif_value").text('');
		{/if}


		{if $total > 0}
		$("#total_notif_number_wrapper").removeClass('hide');
		$('#total_notif_value').text({$total});
		{else}
		$("#total_notif_number_wrapper").addClass('hide');
		{/if}
	</script>
{/if}
