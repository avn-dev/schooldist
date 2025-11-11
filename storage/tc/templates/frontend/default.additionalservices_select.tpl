{assign var=aOptions value=$oField->getOptions()}

<div class="{$oField->getCssClass()} {$oField->getIdentifier()} thebingAdditionalServiceData" data-identifier="{$oField->getIdentifier()}" data-need-refresh="{if $aOptions|count == 0}0{else}1{/if}">

	<select 
		id="{$oField->getId()}"
		name="{$oField->getName()}"
		class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
		data-identifier="{$oField->getIdentifier()}"
		{if $oField->isEditable() == false } 
			disabled="disabled" 
			readonly="readonly"
		{/if}
	>
		
	{foreach from=$aOptions item=aAdditionalService key=sServiceKey}

		<option 
			value="{$sServiceKey}"
			{if $oField->hasValue($sServiceKey) == 1}
				selected="selected"
			{/if}
		>
			{$aAdditionalService['name']}{if $aAdditionalService['price']} ({$aAdditionalService['price']['value']} {$aAdditionalService['price']['original_currency_iso']}){/if}
		</option>
		
	{/foreach}

	</select>
	
</div>