{assign var=aOptions value=$oField->getOptions()}

<div class="{$oField->getCssClass()} {$oField->getIdentifier()} thebingAdditionalServiceData" data-identifier="{$oField->getIdentifier()}" data-need-refresh="{if $aOptions|count == 0}0{else}1{/if}">

    <input 
        name="{$oField->getName()}"
        type="hidden" 
        value="0"
    />
    
	{foreach from=$aOptions item=aAdditionalService key=sServiceKey}

        {assign var=oParentObject value=$oField->getParentObject($sServiceKey)}
        {if $oParentObject}			
            <h3>{$oParentObject->getOutputformatName($oOffice->getProperty('id'))}</h3>
        {/if}
        
		<input 
			type="checkbox" 
			name="{$oField->getName()}" 
			value="{$sServiceKey}"
			class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
			{if $oField->hasValue($sServiceKey) == 1}
				checked="checked"
			{/if}
			{if $oField->isEditable() == false } 
				disabled="disabled" 
				readonly="readonly"
			{/if} 
		/> 
        {$aAdditionalService['name']}{if $aAdditionalService['price']} ({$aAdditionalService['price']['value']} {$aAdditionalService['price']['original_currency_iso']}){/if} <br/>
	{/foreach}
	
</div>