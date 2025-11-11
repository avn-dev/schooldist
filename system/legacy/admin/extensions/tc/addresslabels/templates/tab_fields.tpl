<style type="text/css">
{literal}
	#fieldsList .fieldsItem {
		border:					1px solid #CCC;
		padding:				3px;
		padding-right:			5px;
		margin:					3px;
		line-height:			17px;
		background-color:		#F7F7F7;
	}

	#fieldsList .fieldsItem .fieldsItemMover {
		float:					left;
		width:					12px;
		height:					15px;
		background-image:		url("/admin/extensions/gui2/jquery/css/images/ui-icons_222222_256x240.png");
		background-position:	-130px -49px;
    	cursor:					move;
    	margin-right:			2px;
    	margin-top:				2px;
    	filter:					Alpha(opacity=50);
        opacity:				0.5;
        -moz-opacity:			0.5;
	}

	#fieldsList .fieldsItem .fieldsItemTitle {
		margin-top:				1px;
		float:					left;
	}

	#fieldsList .fieldsItem .fieldsItemOptions {
		float:					right;
	}

	#fieldsList .fieldsItem .fieldsItemOptions .fieldsItemDisplay {
		margin-top:				1px;
	}

	#fieldsList .fieldsItem .fieldsItemOptions input {
		position:				relative;
		top:					3px;
		margin:					0;
	}

	#fieldsList .fieldsItem .fieldsItemOptions select {
		margin-right:			15px;
	}

	#fieldsList .fieldsItem .cleaner {
		clear:					both;
	}
{/literal}
</style>

<div id="fieldsList">
	{foreach from=$aData item=aItem}
		<div class="fieldsItem">
			<div class="fieldsItemMover"></div>
			<div class="fieldsItemTitle">{$aFields[$aItem.field]}</div>
			<div class="fieldsItemOptions form-inline">
				{if $aItem.field != 'address' && $aItem.field != 'address_addon' && $aItem.field != 'address_additional' && $aItem.field != 'country_iso' && $aItem.field != 'zip'}
					<input type="hidden" name="fields[type][{$aItem.field}]" value="input" />
				{elseif $aItem.field == 'country_iso'}
					<input type="hidden" name="fields[type][{$aItem.field}]" value="select" />
				{/if}
				{if $aItem.field == 'address' || $aItem.field == 'address_addon' || $aItem.field == 'address_additional'}
					<select class="txt input-sm form-control" name="fields[type][{$aItem.field}]">
						<option value="input" {if $aItem.type == 'input'}selected="selected"{/if}>{$aTranslations.input}</option>
						<option value="textarea" {if $aItem.type == 'textarea'}selected="selected"{/if}>{$aTranslations.textarea}</option>
					</select>
				{elseif $aItem.field == 'zip'}
					<select class="txt input-sm form-control" name="fields[type][{$aItem.field}]">
						<option value="input" {if $aItem.type == 'input'}selected="selected"{/if}></option>
						<option value="input_po" {if $aItem.type == 'input_po'}selected="selected"{/if}>{$aTranslations.postbox}</option>
					</select>
				{/if}

				<span class="fieldsItemDisplay">
					{$aTranslations.display}
					<input type="hidden" name="fields[display][{$aItem.field}]" value="0" />
					<input name="fields[display][{$aItem.field}]" type="checkbox" value="1" {if $aItem.display}checked="checked"{/if} />
				</span>

				<input type="hidden" class="fieldsItemPosition" name="fields[position][{$aItem.field}]" />
			</div>
			<div class="cleaner"></div>
		</div>
	{/foreach}
</div>