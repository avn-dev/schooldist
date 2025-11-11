
{if $bPDF}
<style>
table {
	border: {$oReport->border_style|default:'1px solid #aaaaaa'};
}
table td {
	border: {$oReport->border_style|default:'1px solid #aaaaaa'};
}
table th {
	border: {$oReport->border_style|default:'1px solid #aaaaaa'};
}
</style>
{/if}

{foreach from=$aData item='aTable' key='iGroupID'}
	{if !$bPDF && !empty($aTable.title)}<h5>{$aTable.title}&nbsp;</h5>{/if}

	{foreach from=$aTable['data'] item='aSubTable' key='iSubGroupID'}
	
		{if !empty($aSubTable.title)}
			<p>
			{if $oReport->sub_group == 'days'}
				{$aSubTable.title|intl_date_format:$language:$timezone}
			{else}
				{$aSubTable.title}
			{/if}			
			</p>
		{/if}
		
	<table {if $bPDF}cellspacing="0" cellpadding="3" border="0"{else}class="stat_result"{/if}>
		
			<tr>
				{foreach from=$aWidths item='aWidth' key='iPosition'}
					{if $aWidth.days}
						{foreach from=$aWidth.days item='sDay' key='iDay'}
							<th style="background-color:#{if !$bPDF}{$aColors[$aWidth.column_id].color}{else}eeeeee{/if}; width:{if $bPDF}{$aWidth.pdf}mm{else}{$aWidth.html}%{/if};" {if $aWidth.setting > 1}colspan="{$aWidth.setting}"{/if}>
								{$sDay}
							</th>
						{/foreach}
                    {elseif $aWidth.travellers}
                        {foreach from=$aWidth.travellers[$iGroupID] item='travellerName' key='travellerIndex'}
							<th style="writing-mode: vertical-lr;transform:rotate(180deg);background-color:#{if !$bPDF}{$aColors[$aWidth.column_id].color}{else}eeeeee{/if}; width:{if $bPDF}{$aWidth.pdf/count($aWidth.travellers[$iGroupID])}mm{else}{$aWidth.html/count($aWidth.travellers[$iGroupID])}%{/if};" {if $aWidth.setting > 1}colspan="{$aWidth.setting}"{/if}>
								{$travellerName}
							</th>
                        {/foreach}
					{else}
						<th style="background-color:#{if !$bPDF}{$aColors[$aWidth.column_id].color}{else}eeeeee{/if}; width:{if $bPDF}{$aWidth.pdf}mm{else}{$aWidth.html}%{/if};">
							{if $aWidth.label}
								{$aWidth.label}
							{else}
								{$aColors[$aWidth.column_id].title}
							{/if}
						</th>
					{/if}
				{/foreach}
			</tr>
		

		{if $aSubTable.data}
		
		{foreach name="rows" from=$aSubTable.data item='aLine' key='iKey'}
			<tr {if $bPDF}style="background-color:#{cycle name='cyc' values='FFF,F8F8F8' reset=$smarty.foreach.rows.first}"{else}class="{cycle name='cyc' values='tr_bg_light,tr_bg_dark'}"{/if}>
				{foreach from=$aWidths item='aWidth' key='iPosition'}
					{if $aWidth.days}
						{foreach from=$aDays item='sDay' key='iDay'}
							{for $i=1 to $aWidth['setting']}
								{if !isset($aLine[$iPosition]) || $aLine[$iPosition][$iDay]}
									<td style="width:{if $bPDF}{$aWidth.pdf/$aWidth['setting']}mm{else}{$aWidth.html/$aWidth['setting']}%{/if};">&nbsp;</td>
								{else}
									<td style="background-color:#DDD; width:{if $bPDF}{$aWidth.pdf/$aWidth['setting']}mm{else}{$aWidth.html/$aWidth['setting']}%{/if};">&nbsp;</td>
								{/if}
							{/for}
						{/foreach}
                    {elseif $aWidth.travellers}
                        {foreach from=$travellers[$iGroupID] item='travellerName' key='travellerIndex'}
							<td class="alignLeft" style="width:{if $bPDF}{$aWidth.pdf/count($aWidth.travellers[$iGroupID])}mm{else}{$aWidth.html/count($aWidth.travellers[$iGroupID])}%{/if};"></td>
                        {/foreach}
					{else}
						<td class="alignLeft" style="width:{if $bPDF}{$aWidth.pdf}mm{else}{$aWidth.html}%{/if};">
							{foreach from=$aLine[$iPosition] item='sValue' key='iSubKey'}
								{if
									$aWidth['column_id'] == 33 ||
									$aWidth['column_id'] == 32 ||
									$aWidth['column_id'] == 31 ||
									$aWidth['column_id'] == 6
								}
									{$sValue}	
								{else}
									{$sValue|escape}
								{/if}
								
								{assign var='iNext' value=$iSubKey+1}
								{if $aLine[$iPosition][$iNext]}<br />{/if}
							{/foreach}
						</td>
					{/if}
				{/foreach}
			</tr>
		{/foreach}
		
		{/if}
	</table>
	{/foreach}
	
{/foreach}