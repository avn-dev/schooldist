
function fillGEO(sInputID, sOutputID) {

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=fill_geo';

	if(sInputID == 'zip') {
		strParameters += '&by_field=zip';
	} else {
		strParameters += '&by_field=city';
	}

	strParameters += '&in_field=' + sOutputID;
	strParameters += '&by_value=' + $(sInputID).value;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method 		: 'post',
								parameters	: strParameters,
								onComplete	: fillGEOCallback
							}
	);
}

function fillGEOCallback(objResponse)
{
	var oData = objResponse.responseText.evalJSON();
	var sCode = '<div style="text-align:center; margin:5px; cursor:pointer;" onclick="$(\'geo_list\').hide();"><b>Vorschläge ausblenden</b></div>';

	while($('geo_list').hasChildNodes())
	{
		$('geo_list').removeChild($('geo_list').firstChild);
	}

	var i = 0;

	oData['data']['list'].each(function(aRow)
	{
		sCode += '<div style="cursor:pointer;" onclick="$(\'zip\').value = $(\'geo_zip_' + i + '\').innerHTML; $(\'city\').value = $(\'geo_city_' + i + '\').innerHTML;">';
			sCode += '<span id="geo_zip_' + i + '">' + aRow['zip'] + '</span> ';
			sCode += '<span id="geo_city_' + i + '">' + aRow['city'] + '</span>';
		sCode += '</div>';

		i++;
	});

	if(oData['data']['list'].length > 0)
	{
		$('geo_list').innerHTML = sCode;

		$('geo_list').show();
	}
	else
	{
		$('geo_list').hide();
	}
}



function showArticleDetails(intArticleId) {

	

}

function showArticleDetailsCallback(objResponse) {

	

}

Number.prototype.number_format = function(decimals, dec_point, thousands_sep) {
	var number = this;
	var exponent = "";
	var numberstr = number.toString ();
	var eindex = numberstr.indexOf ("e");
	if (eindex > -1) {
		exponent = numberstr.substring (eindex);
		number = parseFloat (numberstr.substring (0, eindex));
	}
  
	if (decimals != null) {
		var temp = Math.pow (10, decimals);
		number = Math.round (number * temp) / temp;
	}
	var sign = number < 0 ? "-" : "";
	var integer = (number > 0 ? Math.floor (number) : Math.abs (Math.ceil (number))).toString ();
	var fractional = number.toString ().substring (integer.length + sign.length);
	dec_point = dec_point != null ? dec_point : ".";
	fractional = decimals != null && decimals > 0 || fractional.length > 1 ? (dec_point + fractional.substring (1)) : "";
	if (decimals != null && decimals > 0) {
		for (i = fractional.length - 1, z = decimals; i < z; ++i) {
			fractional += "0";
		}
	}
  
	thousands_sep = (thousands_sep != dec_point || fractional.length == 0) ? thousands_sep : null;
	if (thousands_sep != null && thousands_sep != "") {
		for (i = integer.length - 3; i > 0; i -= 3) {
      		integer = integer.substring (0 , i) + thousands_sep + integer.substring (i);
      	}
	}

	return sign + integer + fractional + exponent;

}

