<p{if $sName != ''} name="{$sName|escape:"htmlall"}"{/if}{if $sID != ''} id="{$sID|escape:"htmlall"}"{/if}{if $sCss != ''} class="{$sCss|escape:"htmlall"}"{/if}{if $sStyle != ''} style="{$sStyle|escape:"htmlall"}"{/if}>
	{$sInnerHTML}
</p>