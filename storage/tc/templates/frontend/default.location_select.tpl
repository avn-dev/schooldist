<select 
	id="{$oField->getId()}"
	name="{$oField->getName()}"
	class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
	{if $oField->isEditable() == false } 
		disabled="disabled" 
		readonly="readonly"
	{/if}
>

	{foreach from=$oField->getOptions() item=oCountry}
		
		<optgroup label="{$oCountry.text}">
			{foreach from=$oCountry.options key=sKey item=aValue}
				<option 
					value="{$aValue.value}"
					{if $oField->getValue(false) == $aValue.value}
						selected="selected"
					{/if}
				>
					{$aValue.text}
				</option>
			{/foreach}
		</optgroup>
		
	{/foreach}

</select>