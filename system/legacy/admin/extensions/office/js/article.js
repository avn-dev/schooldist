// v1
function loadArticlesList()
{
	$('toolbar_loading').show();

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_articles';

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method		: 'post',
								parameters	: strParameters,
								onComplete	: loadArticlesListCallback
							}
	);
}

/* ====================================================================== */

function loadArticlesListCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	createArticlesList(arrData['aArticles']);
}

/* ====================================================================== */

function createArticlesList(aArticles)
{
	var tbody 		= document.getElementById('tbl_articles');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1a, td1, td2, td3, td4, td5, td6, td7, td8;

	// Remove all articles
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Reset the selected articles row
	selectedRow = 0;

	// Create new articles list
	for(var i = 0; i < aArticles.length; i++, c++)
	{
		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		var strId = 'tr_' + aArticles[i]['id'];
		objTr.id = strId;

		Event.observe(objTr, 'click', checkRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', executeAction.bindAsEventListener(c, 'edit'));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = (aArticles[i]['number'].length != 0 ? aArticles[i]['number'] : '&nbsp;');

		td1a = document.createElement("td");
		objTr.appendChild(td1a);
		td1a.innerHTML = aArticles[i]['productgroup'];

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = aArticles[i]['product'];

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = aArticles[i]['unit'];

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = aArticles[i]['currency'];

		td4 = document.createElement("td");
		objTr.appendChild(td4);
		td4.innerHTML = parseFloat(aArticles[i]['cost']).number_format(2, ',', '.');
		td4.style.textAlign = 'right';

		td5 = document.createElement("td");
		objTr.appendChild(td5);
		td5.innerHTML = parseFloat(aArticles[i]['price']).number_format(2, ',', '.');
		td5.style.textAlign = 'right';

		td6 = document.createElement("td");
		objTr.appendChild(td6);
		td6.innerHTML = parseInt(aArticles[i]['amount']);
		td6.style.textAlign = 'right';

		td7 = document.createElement("td");
		objTr.appendChild(td7);
		td7.innerHTML = parseFloat(aArticles[i]['total']).number_format(2, ',', '.');
		td7.style.textAlign = 'right';

		td8 = document.createElement("td");
		objTr.appendChild(td8);
		td8.innerHTML = parseFloat(aArticles[i]['return']).number_format(2, ',', '.');
		td8.style.textAlign = 'right';

		td0 = td1a = td1 = td2 = td3 = td4 = td5 = td6 = td7 = td8 = null;
	}

	tbody = null;

	$('toolbar_loading').hide();
}

/* ====================================================================== */

function openDialog(iArticleID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_article_data&article_id=' + iArticleID;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method		: 'post',
								parameters	: strParameters,
								onComplete	: openDialogCallback
							}
	);
}

/* ====================================================================== */

function openDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var aArticle = objData['data']['aArticle'];

	var objGUI = new GUI;

	var strCode = '';

	// Open main container
	strCode += '<div id="main_container" onclick="document.getElementById(\'saving_confirmation\').style.display = \'none\'">';

	// Saving Confirmation
	strCode += '<div id="saving_confirmation" style="display:none; color:#008000;">';
		strCode += '<div style="text-align:center; padding-top:10px;">';
			strCode += '<b>Die Daten wurden erfolgreich gespeichert!</b>';
		strCode += '</div>';
	strCode += '</div>';

	strCode += objGUI.startFieldset('Artikeldaten');
		strCode += objGUI.printFormSelect('Artikelgruppe', 'a_productgroup', aArticle['aProductgroups'], aArticle['productgroup']);
		strCode += objGUI.printFormInput('Artikel', 'a_product', aArticle['product']);
		strCode += objGUI.printFormInput('Artikelnummer', 'a_number', aArticle['number']);
		strCode += objGUI.printFormSelect('Einheit', 'a_unit', aArticle['aUnits'], aArticle['unit']);
		strCode += objGUI.printFormSelect('Währung', 'a_currency', aArticle['aCurrencys'], aArticle['currency']);
		strCode += objGUI.printFormInput('Einkaufspreis', 'a_cost', parseFloat(aArticle['cost']).number_format(2, ',', '.'));
		strCode += objGUI.printFormInput('Preis', 'a_price', parseFloat(aArticle['price']).number_format(2, ',', '.'));
		strCode += objGUI.printFormInput('MwSt. %', 'a_vat', parseFloat(aArticle['vat']).number_format(2, ',', '.'));
		strCode += objGUI.printFormInput('Monate', 'a_month', parseInt(aArticle['month']));

		strCode += objGUI.printFormTextarea('Beschreibung', 'a_description', aArticle['description'], 3, 50, 'style="width:350px; height:130px;"');
	strCode += objGUI.endFieldset();

	// Save document button
	strCode += objGUI.printFormButton('Daten speichern', 'saveArticle('+aArticle['id']+');', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');

	// Close main container
	strCode += '</div>';

	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:470, width:520, resizable:false, opacity:.9});
	/* =============================================================================================================== */
}

/* ====================================================================== */

function saveArticle(iArticleID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_article&article_id=' + iArticleID;

	document.getElementById('main_container').style.cursor	= 'wait';
	document.getElementById('save_button').style.cursor		= 'wait';

	strParameters += '&product='		+ encodeURIComponent(document.getElementById('a_product').value);
	strParameters += '&number='			+ encodeURIComponent(document.getElementById('a_number').value);
	strParameters += '&unit='			+ encodeURIComponent(document.getElementById('a_unit').value);
	strParameters += '&productgroup='	+ encodeURIComponent(document.getElementById('a_productgroup').value);
	strParameters += '&currency='		+ encodeURIComponent(document.getElementById('a_currency').value);
	strParameters += '&price='			+ encodeURIComponent(document.getElementById('a_price').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&cost='			+ encodeURIComponent(document.getElementById('a_cost').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&vat='			+ encodeURIComponent(document.getElementById('a_vat').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&month='			+ encodeURIComponent(document.getElementById('a_month').value);
	strParameters += '&description='	+ encodeURIComponent(document.getElementById('a_description').value);

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method		: 'post',
								parameters	: strParameters,
								onComplete	: saveArticleCallback
							}
	);
}

/* ====================================================================== */

function saveArticleCallback() {

	document.getElementById('saving_confirmation').style.display	= 'inline';
	document.getElementById('main_container').style.cursor			= 'auto';
	document.getElementById('save_button').style.cursor				= 'auto';

	checkArticlesToolbar(0);
	loadArticlesList();

}

/* ====================================================================== */

function deleteArticle(iArticleID) {

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_article&article_id=' + iArticleID;

	if(confirm('Möchten Sie diesen Artikel wirklich löschen?'))
	{
		var objAjax = new Ajax.Request(
						strRequestUrl,
						{
							method: 'post',
							parameters: strParameters,
							onComplete: deleteArticleCallback
						}
		);
	}
}

/* ====================================================================== */

function deleteArticleCallback()
{
	checkArticlesToolbar(0);
	loadArticlesList();
}
