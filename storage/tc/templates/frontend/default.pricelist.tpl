{if $aPriceList && $aPriceList|count > 0}
<table class="positions" >

	<tr>
		<th>{'Beschreibung'|L10N}</th>
		<th>{'Betrag'|L10N}</th>
	</tr>
	{foreach from=$aPriceList item=aPositionGroup}
		<tr>
			<td class="positiongroup"> {$aPositionGroup['description']} </td> 
			<td class="positiongroup"> &nbsp; </td> 
		</tr>
		{foreach from=$aPositionGroup['items'] item=aItem}
			<tr>
				<td class="description">{$aItem.description}</td>
				<td class="amount">{$aItem.amount}</td>
			</tr>
		{/foreach}
		
	{/foreach}
	<tr>
		<th class="sum_description">{'Summe'|L10N}</th>
		<th class="sum_amount">{$sSumAmount}</th>
	</tr>
	{if $aPrepay}
		<tr>
			<th class="sum_description">{'Anzahlung'|L10N}</th>
			<th class="sum_amount">{$aPrepay.amount}</th>
		</tr>
	{/if}
</table>
{/if}
