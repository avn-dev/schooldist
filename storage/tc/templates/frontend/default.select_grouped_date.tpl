<select 
	id="{$oField->getId()}"
	name="{$oField->getName()}"
	class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
	{if $oField->isEditable() == false} 
		disabled="disabled" 
		readonly="readonly"
	{/if}
>

	{foreach from=$oField->getOptions() key=sKey item=aYearData}

		<optgroup label="{$aYearData.text}">
			{foreach from=$aYearData.options item=aMonthData}
				<option disabled="">{$aMonthData.text}</option>
				{foreach from=$aMonthData.options item=aValue}
					<option value="{$aValue.value}" {if $oField->getValue(false) == $aValue.value}selected="selected"{/if}>{$aValue.text}</option>
				{/foreach}
			{/foreach}
		</optgroup>

	{/foreach}

</select>