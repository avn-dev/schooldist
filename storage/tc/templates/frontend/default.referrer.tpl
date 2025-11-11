<div id="thebingReferrerData" class="{$oField->getIdentifier()}">
	<div class="{$oField->getCssClass('default')}">
		<label for="{$oField->getId()}">{$oField->getLabel()}{if $oField->isRequired()}*{/if}</label>
		<select 
			id="{$oField->getId()}"
			name="{$oField->getName()}"
			class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}" 
			{if $oField->isEditable() == false } 
				disabled="disabled" 
				readonly="readonly"
			{/if}
		>

			{foreach from=$oField->getOptions() key=sKey item=sValue}
				<option 
					value="{$sKey}"
					{if $oField->getValue(false) == $sKey}
						selected="selected"
					{/if}
				>
					{$sValue}
				</option>
			{/foreach}

		</select>
		{if $oField->hasError()}
			{foreach from=$oField->getErrors() item=sMessage}
				<span class="thebingErrorMessage">{$sMessage}</span>
			{/foreach}
		{/if}
	</div>

	{foreach from=$oField->getFields() item=oReferrerField}
		<div class="{$oReferrerField->getCssClass('default')}">
			<label for="{$oReferrerField->getId()}">{$oReferrerField->getLabel()}{if $oReferrerField->isRequired()}*{/if}</label>
			{$oReferrerField->getInput()}
		</div>
	{/foreach}
</div>