{foreach from=$oField->getOptions() key=sKey item=sValue}
	{if $sValue != ''}
		<input 
			id="{$oField->getId()}"
			name="{$oField->getName()}"
			type="radio" 
			value="{$sKey}"
			class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
			{if $oField->getValue(false) == $sKey}
				checked="checked"
			{/if}
			{if $oField->isEditable() == false } 
				disabled="disabled" 
				readonly="readonly"
			{/if} 
		/>
		{$sValue}
	{/if}
{/foreach}

