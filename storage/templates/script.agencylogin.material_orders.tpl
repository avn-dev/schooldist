                <div class="inbox">
		<h2>#page:_LANG['Material orders']#</h2>
		
		{if $sTask == 'add' || $sTask == 'detail'}
		
			{if $oOrder->id > 0}
				<p>
					Order no.: {$oOrder->id}<br/>
					Order date:  {$oOrder->created|date_format:"%x"}
				</p>
			{/if}
			
			{if $sTask == 'add'}
			<form method="post" name="order_form" action="?">
			<input type="hidden" name="view" value="material_orders" />
			<input type="hidden" name="task" value="add" />
			<input type="hidden" id="act" name="act" value="save" />
			<input type="hidden" name="order_id" value="{$oOrder->id}" />
			<input type="hidden" id="delete_id" name="delete_id" value="" />
			{/if}

			<fieldset class="fieldsetDetail">
			
			{include file="script.form_fields.tpl"}
			
			</fieldset>
			
			{if $sTask == 'add'}
			<div class="divFormButton">
				<input type="submit" class="inputBtn" value="#page:_LANG['Submit order']#" />
			</div>
			</form>
			{/if}
			
			<div class="divFormButton">
				<input type="button" class="inputBtn" value="#page:_LANG['Back']#" onclick="go('?view=material_orders'); return false;" />
			</div>
		
		{else}

			<form method="post" name="orders_list" action="?">
				<input type="hidden" name="view" value="material_orders" />
				<input type="hidden" id="task" name="task" value="" />
	
				<fieldset>
	
					<div class="divFormElement">
						<label for="from">#page:_LANG['Period']#</label>
						<input type="text" class="w80" id="filter_from" name="filter_from" value="{$iFrom|date_format:"%x"}" />
						#page:_LANG['to']#
						<input type="text" class="w80" id="filter_until" name="filter_until" value="{$iUntil|date_format:"%x"}" />
					</div>
				
					<div class="divFormElement">
						<label for="filter">#page:_LANG['Filter']#</label>
						<select id="filter" name="filter">
							{html_options options=$aFilterOptions selected=$sFilter}
						</select>
					</div>
				
				</fieldset>
			
				<div class="divFormButton">
					<input type="submit" class="inputBtn" value="#page:_LANG['Update list']#" />
				</div>
		
				<div class="divPagination">
					<div class="divLeft">
						<a href="?view=material_orders&offset={$aPagination.offset_back}">#page:_LANG['back']#</a>
					</div>
					<div class="divCenter">
						#page:_LANG['Orders']# {$aPagination.from+1} #page:_LANG['to']# {$aPagination.to} #page:_LANG['of']# {$aPagination.total}
					</div>
					<div class="divRight">
						<a href="?view=material_orders&offset={$aPagination.offset_forward}">#page:_LANG['forward']#</a>
					</div>
				</div>
			
				<table class="tblInquiries">
					<tr>
						<th style="width:60px;">#page:_LANG['Order no.']#</th>
						<th style="width:120px;">#page:_LANG['Order date']#</th>
						<th style="width:160px;">#page:_LANG['School']#</th>
						<th style="width:auto;">#page:_LANG['Articles']#</th>
						<th style="width:200px;">#page:_LANG['Delivery address']#</th>
						<th style="width:120px;">#page:_LANG['Sent date']#</th>
					</tr>
					{foreach from=$aOrders item=aOrder}
						<tr ondblclick="openOrder('{$aOrder.id}');" style="cursor: pointer;">
							<td>{$aOrder.id}</td>
							<td>{$aOrder.created|date_format:"%x %X"}</td>
							<td>{$aOrder.school}</td>
							<td>{$aOrder.items}</td>
							<td>{$aOrder.address}</td>
							<td>{if $aOrder.sent_date}{$aOrder.sent_date|date_format:"%x %X"}{else}&nbsp;{/if}</td>
						</tr>
					{foreachelse}
						<tr>
							<td colspan="6">#page:_LANG['No orders found!']#</td>
						</tr>
					{/foreach}
				</table>
				
				<div class="divFormButton">
					<input type="button" class="inputBtn" value="#page:_LANG['Add order']#" onclick="go('?view=material_orders&task=add'); return false;" />
				</div>
				
			</form>
		
		{/if}
                </div>