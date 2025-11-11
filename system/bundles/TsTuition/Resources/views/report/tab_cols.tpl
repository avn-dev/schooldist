<div id="divColTabInfobox" class="infoBox floatLeft w200 form-inline">
	<div class="filter-group-container additionalH1 clearfix">
		<select class="form-control input-sm border-gray-300 border" style="width: 95%; margin: 5px;" id="filter_group">
			<option value="0"></option>
			{foreach from=$aColumns item='aGroup' key='iID'}
				<option value="{$iID}">{$aGroup.group}</option>
			{/foreach}
		</select>
	</div>
	<div class="filter-input-container additionalH1 additionalH1_extra clearfix" style="margin: 0 5px;">
		<label for="filter_string">{$aTranslations.search}</label>
		<input type="text" class="form-control input-sm pull-right border-gray-300 border" id="filter_string" style="width:140px;" />
	</div>
	<div class="infoBoxContent" id="cols_filter">
		
		{foreach from=$aColumns item='aGroup' key='iKey'}
			{foreach from=$aGroup.fields item='field' key='iFieldID'}
				<div class="filter_col small_margin" id="filter_col_{$iFieldID}" data-column-id="{$iFieldID}" style="background-color: #{$aGroup.color}">
					<i class="remover fa {$sIconPath}" title="{$aTranslations.remove}" style="display:none;"></i>
					<div class="spanTitle p-2 text-xs">{$field->label}</div>
					<input type="hidden" name="save[columns][COUNTER][column_id]" value="{$iFieldID}" class="column-id border-gray-300 border" disabled="true">
                    <div class="config-div column-config" style="display: none;">
						
						<input type="text" class="input-sm form-control column-label" name="save[columns][COUNTER][label]" placeholder="{'Individuelles Label'|L10N}" disabled="true">
						
						{if $field->hasSettings()}
							<select class="input-sm form-control column-setting" name="save[columns][COUNTER][setting]" disabled="true">
							{html_options options=$field->getSettings()}
							</select>
						{else}
							<select class="input-sm form-control dummy" disabled="true">
							</select>
						{/if}

						<div class="input-group input-group-sm" style="float: left;">
							<div class="input-group-addon">{$aTranslations.width}</div>
							<input type="text" class="form-control text-right column-width" value="40" name="save[columns][COUNTER][width]" disabled="true">
							<div class="input-group-addon">mm</div>
							{if array_key_exists($iFieldID, $aPossibleOrderColumns)}
							<div class="input-group-addon">
                                <input type="radio" name="save[order_by_column]" style="width: 10px; height: 10px" value="{$iFieldID}" title="{'Sortierung nach Spalten'|L10N:'Thebing » Tuition » Own overview'}" {if $iOrderByColumn == $iFieldID}checked="checked"{/if} disabled="true">
							</div>
							{/if}
						</div>

                    </div>

					{if $field->flex}
						<input type="hidden" name="save[columns][COUNTER][flex]" value="1" class="column-flex" disabled="true">
					{else}
						<input type="hidden" name="save[columns][COUNTER][flex]" value="0" class="column-flex" disabled="true">
					{/if}
				</div>

			{/foreach}
		{/foreach}
	</div>
</div>

<div id="divColTabLabels" class="floatLeft">
	<div class="GUIDialogRow">
		<!--<div class="GUIDialogRowLabelDiv">
			<div>{$aTranslations.columns}</div>
		</div>-->
		<div class="GUIDialogRowInputDiv">
			<div id="scroll_container" class="scroll_container_report">
				<div id="scroll_container_content"></div>
			</div>
		</div>
		<div class="divCleaner"></div>
	</div>
</div>

<div class="divCleaner"></div>