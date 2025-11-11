<select 
	id="{$oField->getId()}"
	name="{$oField->getName()}[day]"
	class="thebing_birthdate {$oField->getCssClass()} {$oField->getTemplate()->field_css_classes} day" 
	{if $oField->isEditable() == false } 
		disabled="disabled" 
		readonly="readonly"
	{/if}
>

	{foreach from=$oField->getDayOptions() key=sKey item=sValue}
		<option 
			value="{$sKey}"
			{if $oField->getDay() == $sKey}
				selected="selected"
			{/if}
		>
			{$sValue}
		</option>
	{/foreach}

</select>
<select 
	id="{$oField->getId()}_month"
	name="{$oField->getName()}[month]"
	class="thebing_birthdate month {$oField->getCssClass()}" 
	{if $oField->isEditable() == false } 
		disabled="disabled" 
		readonly="readonly"
	{/if}
>

	{foreach from=$oField->getMonthOptions() key=sKey item=sValue}
		<option 
			value="{$sKey}"
			{if $oField->getMonth() == $sKey}
				selected="selected"
			{/if}
		>
			{$sValue}
		</option>
	{/foreach}

</select>
	
<select 
	id="{$oField->getId()}_year"
	name="{$oField->getName()}[year]"
	class="thebing_birthdate year {$oField->getCssClass()}" 
	{if $oField->isEditable() == false } 
		disabled="disabled" 
		readonly="readonly"
	{/if}
>

	{foreach from=$oField->getYearOptions() key=sKey item=sValue}
		<option 
			value="{$sKey}"
			{if $oField->getYear() == $sKey}
				selected="selected"
			{/if}
		>
			{$sValue}
		</option>
	{/foreach}
</select>