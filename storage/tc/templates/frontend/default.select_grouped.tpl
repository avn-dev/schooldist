<select 
	id="{$oField->getId()}"
	name="{$oField->getName()}"
	class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
	{if $oField->isEditable() == false } 
		disabled="disabled" 
		readonly="readonly"
	{/if}
>
	{assign var=aOptions value=$oField->getOptions(false, true)}
	
	{foreach from=$aOptions item=aOption}
		{if $aOption['options']}
			<optgroup {if $aOption['text']}label="{$aOption['text']}"{/if}>
				{foreach from=$aOption['options'] key=sKey item=sValue}
					<option 
						value="{$sKey}"
						{if $oField->getValue(false) == $sKey}
							selected="selected"
						{/if}
					>
						{$sValue}
					</option>
				{/foreach}
			</optgroup>
		{/if}
	{/foreach}

</select>