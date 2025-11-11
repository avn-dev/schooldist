{assign var=aOptions value=$oField->getOptions()}

<div class="{$oField->getCssClass()} {$oField->getIdentifier()} thebingAdditionalServiceData" data-identifier="{$oField->getIdentifier()}" data-need-refresh="{if $aOptions|count == 0}0{else}1{/if}">
	
	{foreach from=$aOptions item=aAdditionalService key=sServiceKey}
	
		{assign var=iMinQuantity value=(int)$aAdditionalService['min_quantity']}
		{assign var=iMaxQuantity value=(int)$aAdditionalService['max_quantity']}
		
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
			<option value="{$sServiceKey}_0">0</option>
			
			{for $iQuantity=$iMinQuantity to $iMaxQuantity}
				{assign var=sServiceKeyQuantity value="`$sServiceKey`_`$iQuantity`"}
				<option 
					value="{$sServiceKeyQuantity}"
					{if $oField->hasValue($sServiceKeyQuantity) == 1}
						selected="selected"
					{/if}
				>
					{$iQuantity}
				</option>
			{/for}
		
		</select>

		{$aAdditionalService['name']} <br/>	
			
	{/foreach}
	
</div>