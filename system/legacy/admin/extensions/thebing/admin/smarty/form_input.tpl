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
			<div class="floatLeft">
				{$aContent.content.title}
			</div>
			<div class="floatRight">
				{if $aContent.block_key == -6}
					<input class="txt w100" disabled="disabled" />
				{elseif $aContent.block_key == -7}
					<select class="txt w100" disabled="disabled"></select>
				{elseif $aContent.block_key == -8}
					<input class="txt w80" disabled="disabled" />
					<img class="floatRight" src="{$sCalendarIconPath}" alt="" />
				{elseif $aContent.block_key == -9}
					<input type="checkbox" disabled="disabled" />
				{elseif $aContent.block_key == -10}
					<input class="txt" type="file" disabled="disabled" />
				{elseif $aContent.block_key == -11}
					<textarea class="txt w100" disabled="disabled"></textarea>
				{elseif $aContent.block_key == -12}
					<select class="txt w100" multiple size="5" disabled="disabled"></select>
				{elseif $aContent.block_key == -13}
					{if $aContent.select_as_radio}
						<input type="radio" disabled="disabled" /> &nbsp; <input type="radio" disabled="disabled" />
					{else}
						<select class="w100" disabled="disabled"></select>
					{/if}
				{/if}
			</div>
			<div class="divCleaner"></div>
            {$aContent.dependency}
		</td>
	</tr>
</table>