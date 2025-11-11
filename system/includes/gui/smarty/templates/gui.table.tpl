<table{if $aConfig.width != ''} width="{$aConfig.width|escape:"htmlall"}"{/if}{if $aConfig.cellSpacing != ''} cellspacing="{$aConfig.cellSpacing|escape:"htmlall"}"{/if}{if $aConfig.cellPadding != ''} cellpadding="{$aConfig.cellPadding|escape:"htmlall"}"{/if}{if $aConfig.border != ''} border="{$aConfig.border|escape:"htmlall"}"{/if}{if $aConfig.css != ''} class="{$aConfig.css|escape:"htmlall"}"{/if}{if $aConfig.style != ''} style="{$aConfig.style|escape:"htmlall"}"{/if}>
	<colgroup>
		{foreach from=$aConfig.cols key=sColIndex item=aColData}
			<col{if $aColData.css != ''} class="{$aColData.css|escape:"htmlall"}"{/if}{if $aColData.style != ''} style="{$aColData.style|escape:"htmlall"}"{/if} />
		{/foreach}
	</colgroup>
	{if $aConfig.showth == true}
		<tr{if $aConfig.thname != ''} name="{$aConfig.thname|escape:"htmlall"}"{/if}{if $aConfig.thid != ''} id="{$aConfig.thid|escape:"htmlall"}"{/if}{if $aConfig.thcss != ''} class="{$aConfig.thcss|escape:"htmlall"}"{/if}{if $aConfig.thstyle != ''} style="{$aConfig.thstyle|escape:"htmlall"}"{/if}>
			{foreach from=$aConfig.cols key=sColIndex item=aColData}
				<th{if $aColData.thname != ''} name="{$aColData.thname|escape:"htmlall"}"{/if}{if $aColData.thid != ''} id="{$aColData.thid|escape:"htmlall"}"{/if}{if $aColData.thcss != ''} class="{$aColData.thcss|escape:"htmlall"}"{/if}{if $aColData.thstyle != ''} style="{$aColData.thstyle|escape:"htmlall"}"{/if}>{if $aColData.escapeText == true}{$aColData.text|escape:"htmlall"}{else}{$aColData.text}{/if}</th>
			{/foreach}
		</tr>
	{/if}
	{foreach from=$aRows key=sRowIndex item=aRowData}
		<tr{if $aRowData.highlight == true} onmousemove="this.style.backgroundColor='#D4DDF0';" onmouseout="this.style.backgroundColor='';"{/if}{if $aRowData.doubleClickAction != ''} ondblclick="{$aRowData.doubleClickAction|escape:"htmlall"}"{/if}{if $aRowData.name != ''} name="{$aRowData.name|escape:"htmlall"}"{/if}{if $aRowData.id != ''} id="{$aRowData.id|escape:"htmlall"}"{/if}{if $aRowData.css != ''} class="{$aRowData.css|escape:"htmlall"}"{/if}{if $aRowData.style != ''} style="{$aRowData.style|escape:"htmlall"}"{/if}>
			{foreach from=$aRowData.cols key=sColIndex item=aColData}
				{if $aColData.colspan > 0}
					<{if $aColData.forceTh == true}th{else}td{/if}{if $aColData.colspan > 1} colspan="{$aColData.colspan|escape:"htmlall"}"{/if}{if $aColData.name != ''} name="{$aColData.name|escape:"htmlall"}"{/if}{if $aColData.id != ''} id="{$aColData.id|escape:"htmlall"}"{/if}{if $aColData.css != ''} class="{$aColData.css|escape:"htmlall"}"{/if}{if $aColData.style != ''} style="{$aColData.style|escape:"htmlall"}"{/if}>{if $aColData.escapeText == true}{$aColData.text|escape:"htmlall"}{else}{$aColData.text}{/if}</{if $aColData.forceTh == true}th{else}td{/if}>
				{/if}
			{/foreach}
		</tr>
	{/foreach}
</table>