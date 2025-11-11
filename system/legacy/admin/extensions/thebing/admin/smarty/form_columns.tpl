<table class="areas" id="sort_{$aContent.block_id}">
	<th colspan="{$aContent.settings.number_of_cols}">
		<div class="floatLeft">
			{$aContent.title}
		</div>
		<div class="floatRight">
			<span class="form_tab_icons">
				<i class="block_move_img fa {$sMoveIconPath}" id="move_{$aContent.block_id}" alt="{$aTranslations.move}" title="{$aTranslations.move}"></i>&nbsp;
				<i class="block_edit_img fa {$sEditIconPath}" id="edit_{$aContent.block_id}" alt="{$aTranslations.edit}" title="{$aTranslations.edit}"></i>&nbsp;
				<i class="block_remove_img fa {$sRemoveIconPath}" id="remove_{$aContent.block_id}" alt="{$aTranslations.remove}" title="{$aTranslations.remove}"></i>
			</span>
		</div>
		<div class="divCleaner"></div>
	</th>
	<tr>
		{foreach from=$aContent.settings.numbers key=iKey item=iWidth}
			<td style="width:{$iWidth}%;" id="form_pages_content_block_{$aContent.block_id}_{$iKey}" class="container sortable">
				{foreach from=$aContent.content[$iKey] item=aContents}
					{foreach from=$aContents item=sContent}
						{$sContent}
					{/foreach}
				{/foreach}
				<div class="divCleaner"></div>
			</td>
		{/foreach}
	</tr>
</table>
