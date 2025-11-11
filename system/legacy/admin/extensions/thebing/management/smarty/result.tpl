
{literal}
<style type="text/css" media="print">
	
</style>
{/literal}

<div class="infoBoxContentScroll">
	{if $iListType == 1}
		<table class="stat_result">

			{if $aColumnWidths}
			<colgroup>
				{foreach from=$aColumnWidths item='iColumnWidth'}
					<col style="width: {$iColumnWidth}">
				{/foreach}
			</colgroup>
			{/if}

			{* ================================================== GROUPS *}

			{if $bWithGroups}
				<tr>
					{foreach from=$aLabels item='aPeriods' key='iPeriod'}
						{if $iPeriod == 1}
							<th rowspan="4">&nbsp;</th>
							{foreach from=$aPeriods.data item='aGroups' key='iGroupKey'}
									<th style="background-color:#{$aColors[$iGroupKey].color_dark};" colspan="{$aGroups.count}">{$aGroups.title}</th>
							{/foreach}
						{/if}
					{/foreach}
				</tr>

				<tr>
					{foreach from=$aLabels item='aPeriods' key='iPeriod'}
						{if $iPeriod == 1}
							{foreach from=$aPeriods.data item='aGroups' key='iGroupKey'}
								{foreach from=$aGroups.data item='aGroup' key='iGroupID' name='group'}
									<th {if !$smarty.foreach.group.last}class="border_big"{/if} {if $iGroupID != '-'}style="background-color:#{$aColors[$iGroupKey].color_light};"{/if} colspan="{$aGroup.count}">{$aGroup.title}</th>
								{/foreach}
							{/foreach}
						{/if}
					{/foreach}
				</tr>
			{/if}

			{* ==================================== COLUMNS PARENT / SUBGROUPING *}

			<tr>
				{foreach from=$aLabels item='aPeriods' key='iPeriod'}
					{if $iPeriod == 1}
						{if !$bWithGroups}
							<th rowspan="2">&nbsp;</th>
							{foreach from=$aPeriods.data item='aColumn' key='iColumnID'}
								{if $aColumn.data}
									<th style="background-color:#{$aColors[$iColumnID].color_dark};" colspan="{$aColumn.count}">{$aColumn.title}</th>
								{else}
									<th style="background-color:#{$aColors[$iColumnID].color_light};" rowspan="2">{$aColumn.title}</th>
								{/if}
							{/foreach}
						{else}
							{foreach from=$aPeriods.data item='aGroups' key='iGroupKey'}
								{foreach from=$aGroups.data item='aGroup' key='iGroupID' name='group'}
									{foreach from=$aGroup.data item='aColumn' key='iColumnID' name='sub_group'}
										{if $aColumn.data}
											<th class="{if !$smarty.foreach.group.last && $smarty.foreach.sub_group.last}border_big{/if}" {if $iGroupID != '-'}style="background-color:#{$aColors[$iColumnID].color_dark};"{/if} colspan="{$aColumn.count}">{$aColumn.title}</th>
										{else}
											<th class="{if !$smarty.foreach.group.last && $smarty.foreach.sub_group.last}border_big{/if}" {if $iGroupID != '-'}style="background-color:#{$aColors[$iColumnID].color_light};"{/if} rowspan="2">{$aColumn.title}</th>
										{/if}
									{/foreach}
								{/foreach}
							{/foreach}
						{/if}
					{/if}
				{/foreach}
			</tr>

			{* ================================================== COLUMNS CHILDS *}

			<tr>
				{foreach from=$aLabels item='aPeriods' key='iPeriod'}
					{if $iPeriod == 1}
						{if !$bWithGroups}
							{foreach from=$aPeriods.data item='aColumn' key='iColumnID'}
								{if $aColumn.data}
									{foreach from=$aColumn.data item='sColumn' key='iColumnKey'}
										<th style="background-color:#{$aColors[$iColumnID].color_light};">{$sColumn}</th>
									{/foreach}
								{/if}
							{/foreach}
						{else}
							{foreach from=$aPeriods.data item='aGroups' key='iGroupKey'}
								{foreach from=$aGroups.data item='aGroup' key='iGroupID' name='group'}
									{foreach from=$aGroup.data item='aColumn' key='iColumnID' name='sub_group'}
										{if $aColumn.data}
											{foreach from=$aColumn.data item='mColumn' key='iColumnKey' name='column'}
												<th class="{if !$smarty.foreach.group.last && $smarty.foreach.sub_group.last && $smarty.foreach.column.last}border_big{/if}" {if $iGroupID != '-'}style="background-color:#{$aColors[$iColumnID].color_light};"{/if}>
                                                    {if is_array($mColumn)}
                                                        {$mColumn.title}
                                                    {else}
                                                        {$mColumn}
                                                    {/if}
												</th>
											{/foreach}
										{/if}
									{/foreach}
								{/foreach}
							{/foreach}
						{/if}
					{/if}
				{/foreach}
			</tr>

			{* ================================================== DATA *}

			{foreach from=$aLabels item='aPeriods' key='iPeriod'}
				<tr class="{cycle values='tr_bg_light,tr_bg_dark'}">
					<th class="w150 noWrap">{$aPeriods.title}</th>
					{if !$bWithGroups}
						{assign var=sEmpty value=''}
						{foreach from=$aLabels.1.data item='aColumn' key='iColumnID'}
							{if $aColumn.data}
								{foreach from=$aColumn.data item='sColumn' key='iColumnKey'}
									{if $iPeriod === '-'}<th style="text-align:right;"><b>{else}<td>{/if}
										{$aData[$iPeriod][0][$sEmpty][null][$iColumnID][$iColumnKey]}
									{if $iPeriod === '-'}</b></th>{else}</td>{/if}
								{/foreach}
							{else}
								{if $iPeriod === '-'}<th style="text-align:right;"><b>{else}<td>{/if}
									{if !is_array($aData[$iPeriod][0][$sEmpty][$iColumnID]) && !is_array($aData[$iPeriod][0][$sEmpty][null][$iColumnID])}
										{$aData[$iPeriod][0][$sEmpty][null][$iColumnID]}
									{/if}
								{if $iPeriod === '-'}</b></th>{else}</td>{/if}
							{/if}
						{/foreach}
					{else}
						{foreach from=$aLabels.1.data item='aGroups' key='iGroupKey'}
							{foreach from=$aGroups.data item='aGroup' key='iGroupID' name='group'}
								{foreach from=$aGroup.data item='aColumn' key='iColumnID' name='sub_group'}
									{if $aColumn.data}
										{foreach from=$aColumn.data item='sColumn' key='iColumnKey' name='column'}
											{if $iPeriod == '-' || $iGroupID == '-'}
												<th style="text-align:right;" class="{if !$smarty.foreach.group.last && $smarty.foreach.sub_group.last && $smarty.foreach.column.last}border_big{/if}"><b>
											{else}
												<td class="{if !$smarty.foreach.group.last && $smarty.foreach.sub_group.last && $smarty.foreach.column.last}border_big{/if}">
											{/if}
                                            {if is_array($aData[$iPeriod][$iGroupKey][$iGroupID][null])}
                                                {$aData[$iPeriod][$iGroupKey][$iGroupID][null][$iColumnID][$iColumnKey]}
                                            {else}
                                                {$aData[$iPeriod][$iGroupKey][$iGroupID][$iColumnID][$iColumnKey]}
                                            {/if}
											{if $iPeriod == '-' || $iGroupID == '-'}</b></th>{else}</td>{/if}
										{/foreach}
									{else}
										{if $iPeriod == '-' || $iGroupID == '-'}
											<th style="text-align:right;" class="{if !$smarty.foreach.group.last && $smarty.foreach.sub_group.last && $smarty.foreach.column.last}border_big{/if}"><b>
										{else}
											<td class="{if !$smarty.foreach.group.last && $smarty.foreach.sub_group.last}border_big{/if}">
										{/if}
											{$aData[$iPeriod][$iGroupKey][$iGroupID][null][$iColumnID]}
										{if $iPeriod == '-' || $iGroupID == '-'}</b></th>{else}</td>{/if}
									{/if}
								{/foreach}
							{/foreach}
						{/foreach}
					{/if}
				</tr>
			{/foreach}
		</table>
	{/if}

	{* ==================================================================================================== DETAILS *}

	{if $iListType == 2}
		<table class="stat_result">
			<tr>
				{foreach from=$aLabels item='aPeriods' key='iPeriod'}
					{foreach from=$aPeriods.data item='aColumn' key='iColumnID'}
						<th style="background-color:#{$aColors[$iColumnID].color_light};">{$aColumn.title}</th>
					{/foreach}
				{/foreach}
			</tr>
			{foreach from=$aData item='aColumns' key='iLineID'}
				{if $iLineID == '-'}
					<tr>
				{else}
					<tr class="{cycle values='tr_bg_light,tr_bg_dark'}">
				{/if}
					{foreach from=$aColumns item='mValue' key='iLineColumnID'}
						{if $iLineID == '-'}
							<th style="text-align:right;">
								&nbsp;<b>{$mValue}</b>
							</th>
						{else}
							<td class="alignLeft">
								{$mValue}
							</td>
						{/if}
					{/foreach}
				</tr>
			{/foreach}
		</table>
	{/if}
</div>
