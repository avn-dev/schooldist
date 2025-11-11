<input 
	id="{$oField->getId()}"
	name="{$oField->getName()}"
	type="text" 
	class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
	value="{$oField->getValue(true)|trim|escape}" 
	{if $oField->isEditable() == false } 
		readonly="readonly"
	{/if} 
	style="display: none;"
/>
