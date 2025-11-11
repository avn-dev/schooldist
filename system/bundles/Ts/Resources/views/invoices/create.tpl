{assign var=fTotal value=0}
<div class="create-invoice-container">

	{foreach $companyItems as $companyId=>$aItems}
		
		<h4>{$companies[$companyId]}</h4>
		
		<div class="text-xs grid grid-cols-1 sm:grid-cols-2 gap-4 p-2">
			<div class="col-span-1">
				<div class="flex flex-row items-center gap-2">
					<label for="invoice[{$companyId}][template_id]" class=" font-semibold">{'Vorlage'|L10N}</label>
					<div class="col-sm-10">
						<select name="invoice[{$companyId}][template_id]" class="p-0.5 rounded items-center relative border-none ring-1 bg-white text-gray-500 ring-gray-100/75 hover:text-gray-600 hover:ring-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:ring-gray-500 dark:hover:bg-gray-500 dark:hover:text-gray-100">
							{html_options options=$templates}
						</select>
					</div>
				</div>
			</div>
		</div>
		
		<table class="table table-hover">
			<thead>
				<tr>
					<th style="width:45px;">{'Aktiv'|L10N}</th>
					<th style="width:auto;">{'Beschreibung'|L10N}</th>
					<th style="width:120px;">{'Betrag'|L10N}</th>
					{if $view == 'net'}
					<th style="width:120px;">{'Provision'|L10N}</th>
					<th style="width:120px;">{'Betrag'|L10N}</th>
					{/if}
					<th style="width:80px;">{'USt.'|L10N}</th>
				</tr>
			</thead>
			<tbody>
			{foreach $aItems as $aItem}
				<tr>
					<td class="text-center"><input type="checkbox" name="invoice[{$companyId}][items][{$aItem['item_key']}][active]" value="1" checked></td>
					<td>{$aItem.description}</td>
					{if $view == 'net'}
					<td class="text-right"><input type="text" class="form-control input-sm text-right amount-field amount" data-company-id="{$companyId}" data-field="amount" name="invoice[{$companyId}][items][{$aItem['item_key']}][amount]" value="{\Ext_Thebing_Format::Number($aItem.amount)}"></td>
					<td class="text-right"><input type="text" class="form-control input-sm text-right amount-field amount_provision" data-company-id="{$companyId}" data-field="amount_provision" name="invoice[{$companyId}][items][{$aItem['item_key']}][amount_provision]" value="{\Ext_Thebing_Format::Number($aItem.amount_provision)}"></td>
					<td class="text-right"><input type="text" class="form-control input-sm text-right invisible-field amount-field amount_net" data-company-id="{$companyId}" data-field="amount_net" name="invoice[{$companyId}][items][{$aItem['item_key']}][amount_net]" value="{\Ext_Thebing_Format::Number($aItem.amount_net)}"></td>
					{else}
					<td class="text-right"><input type="text" class="form-control input-sm text-right amount-field amount" data-company-id="{$companyId}" data-field="amount" name="invoice[{$companyId}][items][{$aItem['item_key']}][amount]" value="{\Ext_Thebing_Format::Number($aItem.amount)}"></td>
					{/if}
					<td>{Ext_TS_Vat::getInstance($aItem.tax_category)->short}</td>
				</tr>
			{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<th></th>
					<th>{'Gesamt'|L10N}</th>
					{if $view == 'net'}
					<th class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount-total-{$companyId}" disabled></th>
					<th class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount_provision-total-{$companyId}" disabled></th>
					<th class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount_net-total-{$companyId}" disabled></th>
					{else}
					<th class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount-total-{$companyId}" disabled></th>
					{/if}
					<th>&nbsp;</th>
				</tr>
			</tfoot>
		</table>
		{assign var=fTotal value=$fTotal + $fTotalCompany}
		
	{foreachelse}
		
		<div class="alert alert-info">
			{'Keine unberechneten Rechnungspositionen vorhanden!'|L10N}
		</div>
		
	{/foreach}
	
	{if count($companyItems)>1}
	<table class="table">
		<tr>
			<th style="width:45px;"></th>
			<th>{'Gesamt alle Rechnungen'|L10N}</th>
			{if $view == 'net'}
			<th style="width:120px;" class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount-total" disabled></th>
			<th style="width:120px;" class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount_provision-total" disabled></th>
			<th style="width:120px;" class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount_net-total" disabled></th>
			{else}
			<th style="width:120px;" class="text-right"><input type="text" class="form-control input-sm text-right invisible-field" id="amount-total" disabled></th>
			{/if}
			<td style="width:80px;"></td>
		</tr>
	</table>
	{/if}
	
</div>