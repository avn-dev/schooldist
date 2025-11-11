<textarea 
	id="{$oField->getId()}"
	name="{$oField->getName()}"
	class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
	{if $oField->isEditable() == false } 
		readonly="readonly"
	{/if}
>{$oField->getValue(true)|trim|escape}</textarea>