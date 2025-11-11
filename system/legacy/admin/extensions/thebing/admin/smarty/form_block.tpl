<table class="areas" id="sort_{$aContent.block_id}" {if !empty($aContent['fixed'])}data-fixed="{$aContent['fixed']}"{/if}>
	{if $aContent.block_key == 1}
		<th id="courses_block">
	{elseif $aContent.block_key == 2}
		<th id="accommodations_block">
	{elseif $aContent.block_key == 3}
		<th id="transfers_block">
	{elseif $aContent.block_key == 4}
		<th id="insurances_block">
	{elseif $aContent.block_key == 5}
		<th id="prices_block_{$aContent.page_id}">
	{elseif $aContent.block_key == 6}
		<th id="additional_costs_block">
	{else}
		<th>
	{/if}
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
	</th>
	{if $aContent.content || $aContent.dependency}
		<tr>
			<td>
                {$aContent.content}
				{$aContent.dependency}
			</td>
		</tr>
	{/if}
</table>