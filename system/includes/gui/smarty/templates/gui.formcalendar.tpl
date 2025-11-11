<input type="text"{if $sName != ''} name="{$sName|escape:"htmlall"}"{/if}{if $sID != ''} id="{$sID|escape:"htmlall"}"{/if}{if $sCss != ''} class="{$sCss|escape:"htmlall"}"{/if}{if $sStyle != ''} style="{$sStyle|escape:"htmlall"}"{/if}{if $sValue != ''} value="{$sValue|escape:"htmlall"}"{/if} {if $sOnDblClick != ''} ondblclick="{$sOnDblClick|escape:"htmlall"}"{/if}{if $sOnFocus != ''} onfocus="{$sOnFocus|escape:"htmlall"}"{/if}{if $sOnBlur != ''} onblur="{$sOnBlur|escape:"htmlall"}"{/if}{if $sOnMouseDown != ''} onmousedown="{$sOnMouseDown|escape:"htmlall"}"{/if}{if $sOnMouseUp != ''} onmouseup="{$sOnMouseUp|escape:"htmlall"}"{/if}{if $sOnMouseMove != ''} onmousemove="{$sOnMouseMove|escape:"htmlall"}"{/if}{if $sOnMouseOut != ''} onmouseout="{$sOnMouseOut|escape:"htmlall"}"{/if}{if $sOnMouseOver != ''} onmouseover="{$sOnMouseOver|escape:"htmlall"}"{/if}{if $sOnKeyDown != ''} onkeydown="{$sOnKeyDown|escape:"htmlall"}"{/if}{if $sOnKeyPress != ''} onkeypress="{$sOnKeyPress|escape:"htmlall"}"{/if}{if $sOnKeyUp != ''} onkeyup="{$sOnKeyUp|escape:"htmlall"}"{/if} {if $sOnChange != ''} onchange="{$sOnChange|escape:"htmlall"}"{/if}{if $sOnSelect != ''} onselect="{$sOnSelect|escape:"htmlall"}"{/if}{if $sOnClick != ''} onclick="{$sOnClick|escape:"htmlall"}"{/if} />
{*
<script type="text/javascript">

jQuery.extend(DateInput.DEFAULT_OPTS, {ldelim}   
	month_names: ["{$unixdatezero|date_format:"%B"}", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],   
	short_month_names: ["{$unixdatezero|date_format:"%b"}", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"],   
	short_day_names: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sab"] 
{rdelim});

$.extend(DateInput.DEFAULT_OPTS, {ldelim}   

	stringToDate: function(string) {ldelim}
		var matches;     
		if (matches = string.match(/^(\d{ldelim}2,2{rdelim})\.(\d{ldelim}2,2{rdelim})\.(\d{ldelim}4,4{rdelim})$/)) {ldelim}       
			return new Date(matches[3], matches[2] - 1, matches[1]);     
		{rdelim} else {ldelim}       
			return null;     
		{rdelim};   
	{rdelim},   
	
	dateToString: function(date) {ldelim}     
		var month = (date.getMonth() + 1).toString();     
		var dom = date.getDate().toString();     
		if (month.length == 1) 
			month = "0" + month;     
		if (dom.length == 1) 
			dom = "0" + dom;
		return dom+"."+month+"."+date.getFullYear();   
	{rdelim} 

{rdelim});

$($.date_input.initialize);

</script>
*}