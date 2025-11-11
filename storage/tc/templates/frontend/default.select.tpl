<select 
	id="{$oField->getId()}"
	name="{$oField->getName()}"
	class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
	{if $oField->isEditable() == false } 
		disabled="disabled" 
		readonly="readonly"
	{/if}
>

	{foreach from=$oField->getOptions() key=sKey item=sValue}
		<option 
			value="{$sKey}"
			{if
				!is_null($oField->getValue(false)) &&
				$oField->getValue(false) == $sKey
			}
				selected="selected"
			{/if}
		>
			{$sValue}
		</option>
	{/foreach}

</select>