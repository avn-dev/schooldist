{assign 'sThStyle' 'border-bottom: 1px solid black;'}
<table style="width: 100%" cellpadding="2">
	<tr>
		<th style="{$sThStyle} width: 18%;">{$aTranslations.school}</th>
		<th style="{$sThStyle} width: 18%;">{$aTranslations.customer_name}</th>
		<th style="{$sThStyle} width: 12%">{$aTranslations.gender}</th>
		<th style="{$sThStyle} width: 16.6%;">{$aTranslations.courses}</th>
		<th style="{$sThStyle} width: 23.4%">{$aTranslations.course_dates}</th>
		<th style="{$sThStyle} width: 12%">{$aTranslations.course_weeks}</th>
	</tr>
	{foreach $aRows as $aRow}
		<tr nobr="true">
			<td style="">{$aRow.school_name}</td>
			<td style="">{$aRow.customer_name}</td>
			<td style="">{$aRow.gender}</td>
			<td style="">{$aRow.courses}</td>
			<td style="">{$aRow.course_dates}</td>
			<td style="text-align: right;">{$aRow.course_weeks}</td>
		</tr>
	{/foreach}
</table>
