
{if empty($aData)}
	<h3>{'Mit den gewählten Einstellungen konnten keine Daten gefunden werden'|L10N:$l10n_path}</h3>
{/if}

{foreach from=$aData item='aCategories' key='iCategoryID'}
	<h2>{$aCategoriesLabels[$iCategoryID]}</h2>

	{foreach from=$aCategories item='aRoomtypes' key='sDate'}
		<div style="float:left; margin-right:20px;">
			<h3>{$sDate}</h3>
			{if $aRoomtypes}
				<table class="table table-condensed stat_result">
					<tr>
						<th rowspan="2" class="w100"></th>
						<th colspan="2">{'Gesamt'|L10N:$l10n_path}</th>
						<th colspan="1" class="small">{'Gebucht'|L10N:$l10n_path}</th>
						<th colspan="1" class="small">{'Zugewiesen'|L10N:$l10n_path}</th>
						<th colspan="1" class="small">{'Reserviert'|L10N:$l10n_path}</th>
						<th colspan="2">{'Verfügbar'|L10N:$l10n_path}</th>
						<th colspan="2">{'Auslastung'|L10N:$l10n_path}</th>
					</tr>
					<tr>
						<th class="w75">{'Zimmer'|L10N:$l10n_path}</th>
						<th class="w75">{'Bett'|L10N:$l10n_path}</th>
						<th class="w75">{'Bett'|L10N:$l10n_path}</th>
						<th class="w75">{'Bett'|L10N:$l10n_path}</th>
						<th class="w75">{'Bett'|L10N:$l10n_path}</th>
						<th class="w75">{'Inkl. Res.'|L10N:$l10n_path}</th>
						<th class="w75">{'Exkl. Res.'|L10N:$l10n_path}</th>
						<th class="w75">{'Inkl. Res.'|L10N:$l10n_path}</th>
						<th class="w75">{'Exkl. Res.'|L10N:$l10n_path}</th>
					</tr>
					{foreach from=$aRoomtypes item='aValues' key='iRoomtypeID'}
						<tr data-roomtype-id="{$iRoomtypeID}">
							<th class="alignLeft">{$aRoomtypesLabels[$iRoomtypeID]}</th>
							<td>{$aValues.rooms}</td>
							<td>{$aValues.beds}</td>
							<td>{$aValues.booking_beds}</td>
							<td>{$aValues.allocation_beds}</td>
							<td>{$aValues.reservation_beds}</td>
							<td class="totals">{$aValues.beds - $aValues.allocation_beds - $aValues.booking_beds - $aValues.reservation_beds}</td>
							<td class="totals">{$aValues.beds - $aValues.allocation_beds - $aValues.booking_beds}</td>
							<td class="totals">{if $aValues.beds > 0}{$format->formatByValue((1-($aValues.beds - $aValues.booking_beds - $aValues.allocation_beds - $aValues.reservation_beds)/$aValues.beds)*100)} %{/if}</td>
							<td class="totals">{if $aValues.beds > 0}{$format->formatByValue((1-($aValues.beds - $aValues.booking_beds - $aValues.allocation_beds)/$aValues.beds)*100)} %{/if}</td>
						</tr>
					{/foreach}
						<tr>
							<th class="alignLeft">{'Gesamt'|L10N:$l10n_path}</th>
							<td class="totals">{$aTotals[$iCategoryID][$sDate].rooms}</td>
							<td class="totals">{$aTotals[$iCategoryID][$sDate].beds}</td>
							<td class="totals">{$aTotals[$iCategoryID][$sDate].booking_beds}</td>
							<td class="totals">{$aTotals[$iCategoryID][$sDate].allocation_beds}</td>
							<td class="totals">{$aTotals[$iCategoryID][$sDate].reservation_beds}</td>
							<td class="totals">{$aTotals[$iCategoryID][$sDate].available_incl}</td>
							<td class="totals">{$aTotals[$iCategoryID][$sDate].available_excl}</td>
							<td class="totals">{if $aTotals[$iCategoryID][$sDate].beds > 0}{$format->formatByValue((1-($aTotals[$iCategoryID][$sDate].beds - $aTotals[$iCategoryID][$sDate].booking_beds - $aTotals[$iCategoryID][$sDate].allocation_beds - $aTotals[$iCategoryID][$sDate].reservation_beds)/$aTotals[$iCategoryID][$sDate].beds)*100)} %{/if}</td>
							<td class="totals">{if $aTotals[$iCategoryID][$sDate].beds > 0}{$format->formatByValue((1-($aTotals[$iCategoryID][$sDate].beds - $aTotals[$iCategoryID][$sDate].booking_beds - $aTotals[$iCategoryID][$sDate].allocation_beds)/$aTotals[$iCategoryID][$sDate].beds)*100)} %{/if}</td>
						</tr>
				</table>
			{/if}
		</div>
	{/foreach}

	<div style="clear:both; margin-bottom:30px;"></div>
{/foreach}