
<div class="infoBox floatLeft w200">
	<h1>
		<select class="txt" style="width:190px;" id="filter_group">
			{foreach from=$aColGroups item='sGroup' key='iID'}
				<option value="{$iID}">{$sGroup}</option>
			{/foreach}
		</select>
	</h1>
	<h1 class="additionalH1 additionalH1_extra clearfix">
		<div class="floatLeft labelText">
			<label for="filter_string">{$aTranslations.search}</label>
		</div>
		<div class="floatRight">
			<input class="txt w120" id="filter_string" />
		</div>
	</h1>
	<div class="infoBoxContent" id="cols_filter">
		{foreach from=$aGroupItems item='aColumns' key='iKey'}
			{foreach from=$aColumns item='aColumn' key='iColumnKey'}
				<div class="filter_col" id="filter_col_{$aColumn.id}">
					<i title="{$aTranslations.remove}" class="remover fa {$sIconPath}" style="display:none;"></i>
					<div class="divCleaner"></div>
					<span class="spanTitle">{$aColumn.title}</span>
					{if $aColumn.settings}
						<br />
						<select name="save[columns][settings][{$aColumn.id}]" style="width:112px; display:none;" id="filter_col_{$aColumn.id}_settings" class="txt">
							<option value="3" {if isset($aSavedCols.settings[$aColumn.id]) && $aSavedCols.settings[$aColumn.id] == 3}selected="selected"{/if}>max. 3</option>
							<option value="10" {if isset($aSavedCols.settings[$aColumn.id]) && $aSavedCols.settings[$aColumn.id] == 10}selected="selected"{/if}>max. 10</option>
							<option value="20" {if isset($aSavedCols.settings[$aColumn.id]) && $aSavedCols.settings[$aColumn.id] == 20}selected="selected"{/if}>max. 20</option>
							<option value="0" {if isset($aSavedCols.settings[$aColumn.id]) && $aSavedCols.settings[$aColumn.id] == 0}selected="selected"{/if}>{$aTranslations.all}</option>
						</select>
						{if $aColumn.max_by && false}
							<select name="save[columns][max_by][{$aColumn.id}]" style="width:112px; display:none;" id="filter_col_{$aColumn.id}_maxby" class="txt">
								{foreach from=$aTranslations.max_value item='sText' key='iKey'}
									<option value="{$iKey}" {if isset($aSavedCols.max_by[$aColumn.id]) && $aSavedCols.max_by[$aColumn.id] == $iKey}selected="selected"{/if}>{$sText}</option>
								{/foreach}
							</select>
						{/if}
					{/if}
					{if $aColumn.group_by}
						<input type="hidden" id="filter_col_{$aColumn.id}_group_by" />
					{/if}
				</div>
			{/foreach}
		{/foreach}
	</div>
</div>

<div id="divColTabLabels" class="floatLeft">
	<div class="GUIDialogRow">
		<div class="GUIDialogRowLabelDiv">
			<div>{$aTranslations.grouping}</div>
		</div>
		<div class="GUIDialogRowInputDiv">
			<div id="groups_container" class="column_block">
				<input type="hidden" id="groups_container_col" name="save[columns][groups][]" value="" />
			</div>
		</div>
		<div class="divCleaner"></div>
	</div>

	<div class="GUIDialogRow">
		<!--<div class="GUIDialogRowLabelDiv">
			<div>{$aTranslations.columns}</div>
		</div>-->
		<div class="GUIDialogRowInputDiv">
			<div id="scroll_container">
				<div id="columns_container">
                    {assign var='iColumnCount' value=$iColumnCount+1}
					{section name=boxes start=1 loop=$iColumnCount step=1}
						<div id="column_box_{$smarty.section.boxes.index}" class="column_block floatLeft" style="width:120px;">
							<input type="hidden" id="column_box_{$smarty.section.boxes.index}_col" name="save[columns][cols][]" value="" />
						</div>
					{/section}
				</div>
			</div>
		</div>
		<div class="divCleaner"></div>
	</div>
</div>

<div class="divCleaner"></div>