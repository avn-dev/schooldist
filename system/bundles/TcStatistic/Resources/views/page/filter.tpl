<div class="GUIDialogRow form-group" {if !$oFilter->isShown()}style="display: none"{/if}>
	<label class="GUIDialogRowLabelDiv control-label col-sm-3">{$oFilter->getTitle()}</label>
	<div class="GUIDialogRowInputDiv col-sm-9">
		{if $oFilter->getInputType() === 'select'}
			{$sValue = $oFilter->getDefaultValueOrOverwritten()}
				<select name="filter_{$oFilter->getKey()}" class="txt form-control input-sm" data-filter="select">
					{foreach $oFilter->getSelectOptions() as $sKey => $sLabel}
						<option value="{$sKey}" {if $sKey == $sValue}selected{/if}>{$sLabel}</option>
					{/foreach}
				</select>
		{elseif $oFilter->getInputType() === 'multiselect'}
			{$aValues = $oFilter->getDefaultValueOrOverwritten()}
				<select name="filter_{$oFilter->getKey()}[]" class="txt form-control" multiple data-filter="multiselect" style="height: 50px;">
					{foreach $oFilter->getSelectOptions() as $sKey => $sLabel}
						<option value="{$sKey}" {if in_array($sKey, $aValues)}selected{/if}>{$sLabel}</option>
					{/foreach}
				</select>
		{elseif $oFilter->getInputType() === 'checkbox'}
			<input type="checkbox" name="filter_{$oFilter->getKey()}" value="1" data-filter="checkbox" style="margin: 0">
		{else}
			Unknown filter input type: {$oFilter->getInputType()}
		{/if}
	</div>
	<div class="clearfix"></div>
</div>
