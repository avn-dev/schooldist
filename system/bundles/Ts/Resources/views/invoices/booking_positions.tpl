<h3>{'Buchungspositionen'|L10N}</h3>

	{foreach $companyItems as $companyId=>$aItems}
		
		<h4>{$companies[$companyId]}</h4>
		
		<table class="table">
			<tr>
				<th>{'Beschreibung'|L10N}</th>
				<th style="width:120px;">{'Betrag'|L10N}</th>
				<th style="width:80px;">{'USt.'|L10N}</th>
			</tr>
		
		{assign var=fTotal value=0}
		{foreach $aItems as $aItem}
			<tr>
				<td>{$aItem.description}</td>
				<td class="text-right">{\Ext_Thebing_Format::Number($aItem.amount, $oInquiry->currency_id)}</td>
				<td>{Ext_TS_Vat::getInstance($aItem.tax_category)->short}</td>
			</tr>
			{assign var=fTotal value=$fTotal + $aItem.amount}
		{/foreach}
				<tr>
				<th>{'Gesamt'|L10N}</th>
				<th class="text-right">{\Ext_Thebing_Format::Number($fTotal, $oInquiry->currency_id)}</th>
				<th>&nbsp;</th>
			</tr>
		</table>
		
	{/foreach}
	
