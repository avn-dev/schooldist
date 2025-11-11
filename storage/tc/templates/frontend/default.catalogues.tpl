<div class="{$oField->getIdentifier()} thebingCataloguesServiceData">
	<ul class="thebingCatalogues">
		{foreach from=$oField->getOptions() item=sCatalogue key=iService}
			<li>
				<input
					name="{$oField->getName()}"
					type="hidden"
					value="0"
				/>
				<input
					type="checkbox"
					name="{$oField->getName()}"
					value="{$iService}"
					class="{$oField->getCssClass()} {$oField->getTemplate()->field_css_classes}"
					{if $oField->hasValue($iService) == 1}
						checked="checked"
					{/if}
					{if $oField->isEditable() == false }
						disabled="disabled"
						readonly="readonly"
					{/if}
				/>
				{$sCatalogue}
			</li>
		{/foreach}
	</ul>
</div>