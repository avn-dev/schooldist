<input 
	id="{$oField->getId()}"
	name="{$oField->getName()}"
	type="checkbox" 
	value="1"
	class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
	{if $oField->getValue() == 1}
		checked="checked"
	{/if}
	{if $oField->isEditable() == false } 
		disabled="disabled" 
		readonly="readonly"
	{/if} 
/>