<table style="width: 100%" cellpadding="2">
	<tr>
		{foreach $columns as $key => $column}
			<th style="border-bottom: 1px solid black; {if isset($column_widths[$key])}width: {$column_widths[$key]}{/if}">{$column}</th>
		{/foreach}
	</tr>
	{foreach $documents as $document}
		<tr nobr="true">
            {foreach $columns as $key => $column}
				<td style="{if strpos($key, 'amount') !== false}text-align: right{/if}">{$document[$key]}</td>
			{/foreach}
		</tr>
	{/foreach}
	{foreach $totals as $total}
		<tr>
			<td colspan="{$columns|count - 1}" style="text-align: right; {if $total@first}border-top: 1px solid black{/if}">
				<strong>{$translations.total_amount}:&nbsp;</strong>
			</td>
			<td style="text-align: right;{if $total@first}border-top: 1px solid black{/if}">
                {$total}
			</td>
		</tr>
	{/foreach}
</table>
