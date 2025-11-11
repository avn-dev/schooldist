<table class="areas" id="sort_{$aContent.block_id}">
	<th>
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
		<td>
			<h{$iH}>{$aContent.content}</h{$iH}>
			<div class="divCleaner"></div>
            {$aContent.dependency}
		</td>
	</tr>
</table>
