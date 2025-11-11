<div class="select_container">
	<select 
		id="{$oField->getId()}"
		name="{$oField->getName()}[day]"
		class="{$oField->getCssClass()} thebing_birthdate day {$oField->getTemplate()->field_css_classes}{if $oField->isRequired()} required{/if}" 
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
		class="{$oField->getCssClass()} thebing_birthdate month {$oField->getTemplate()->field_css_classes}{if $oField->isRequired()} required{/if}" 
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
		class="{$oField->getCssClass()} thebing_birthdate year {$oField->getTemplate()->field_css_classes}{if $oField->isRequired()} required{/if}" 
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
	
</div>

{if $oField->getTemplate()->description}
<img src="/media/anmeldeformular/icon_info.png" title="{$oField->getTemplate()->description}" class="icon_info"/>
{/if}

<img src="/media/anmeldeformular/icon_cross.png" title="Ungültige Eingabe" class="icon_invalid" style="display:none;"/>
<img src="/media/anmeldeformular/icon_tick.png" title="Gültige Eingabe" class="icon_valid" style="display:none;"/>

<img src="/media/anmeldeformular/ajax-loader.gif" title="Bitte warten" class="icon_loader" style="display:none;"/>