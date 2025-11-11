<input type="text" id="{$sCode}"{if $sName != ''} name="{$sCode}"{/if}{if $sCss != ''} class="{$sCss|escape:"htmlall"}"{/if}{if $sStyle != ''} style="{$sStyle|escape:"htmlall"}"{/if}{if $sDisplay != ''} value="{$sDisplay|escape:"htmlall"}"{/if}{if $sOnClick != ''} onclick="{$sOnClick|escape:"htmlall"}"{/if} />
<input type="hidden" id="{$sCode}_hidden" name="{$sName|escape:"htmlall"}"{if $sValue != ''} value="{$sValue|escape:"htmlall"}"{else} value=""{/if}/>

<script type="text/javascript">

function {$sCode}_selectItem( item ) {ldelim}
	if(item.extra) {ldelim}
		var objField = document.getElementById("{$sCode}_hidden");
		objField.value = item.extra[0];
	{rdelim}
{rdelim}

function {$sCode}_formatItem( row ) {ldelim}
	return {$strItemTemplate};
{rdelim}

$(document).ready(function() {ldelim}
	$("#{$sCode}").autocomplete("/admin/extensions/gui/gui.ajax.php", {ldelim} minChars:1, matchSubset:1, matchContains:1, cacheLength:10, onItemSelect:{$sCode}_selectItem, formatItem:{$sCode}_formatItem, selectOnly:1, extraParams:{ldelim}key:'{$sCode}'{rdelim} {rdelim});
{rdelim});

</script>
