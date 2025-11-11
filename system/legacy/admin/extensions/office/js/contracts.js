
var iContractID = 0;

var bShowDueContracts = false;

var aDisplayedContracts = new Array();

var sOrder = '';

var sSearch = '';

/* ================================================== */

function openContractDialog()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_contract_data&contract_id=' + iContractID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openContractDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ================================================== */

function openContractDialogCallback(objResponse) {

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];
	var objGUI = new GUI;
	var strCode = '';

	strCode += '<style>.LB_content label {width: 180px;}</style>';

	// Open main container
	strCode += '<div id="main_container" onclick="document.getElementById(\'saving_confirmation\').style.display = \'none\'">';

	// Saving Confirmation
	strCode += '<div id="saving_confirmation" style="display:none; color:#008000;">';
		strCode += '<div style="text-align:center; padding-top:10px;">';
			strCode += '<b>Der Kontrakt wurde erfolgreich gespeichert!</b>';
		strCode += '</div>';
	strCode += '</div>';

	// Errors
	strCode += '<div id="saving_error" style="display:none; color:#FF0000;">';
		strCode += '<div style="text-align:center; padding-top:10px;">';
			strCode += '<b id="saving_error_message"></b>';
		strCode += '</div>';
	strCode += '</div>';

	strCode += objGUI.startFieldset('Kontraktdaten');
		strCode += objGUI.printFormSelect('Kunde', 'contract_customer', arrData['aCustomers'], arrData['selectedCustomer'], 'style="width:350px;" onchange="loadContractContacts(this.value);"');
		strCode += objGUI.printFormSelect('Kontaktperson', 'contract_contact', arrData['aContacts'], arrData['selectedContact'], 'style="width:350px;"');
		strCode += objGUI.printFormSelect('Bearbeiter', 'contract_editor', arrData['aEditors'], arrData['selectedEditor'], 'style="width:350px;"');
		strCode += objGUI.printFormSelect('Produkt', 'contract_product', arrData['aProducts'], arrData['selectedProduct'], 'style="width:350px;"');
		strCode += objGUI.printFormInput('Startdatum', 'contract_start', arrData['sStart'], 'style="width:100px;"');
		strCode += objGUI.printFormInput('Enddatum', 'contract_end', arrData['sEnd'], 'style="width:100px;"');
		strCode += objGUI.printFormInput('Anzahl', 'contract_amount', parseFloat(arrData['iAmount']).number_format(2, ',', '.'), 'style="width:100px;"');
		strCode += objGUI.printFormInput('Interval / Mon.', 'contract_interval', arrData['iInterval'], 'style="width:100px;"');
		strCode += objGUI.printFormInput('Rabatt / %', 'contract_discount', parseFloat(arrData['iDiscount']).number_format(6, ',', '.'), 'style="width:100px;"');
		strCode += objGUI.printFormInput('Abweichender Einzelpreis', 'contract_price', parseFloat(arrData['iPrice']).number_format(2, ',', '.'), 'style="width:100px;"');
		strCode += objGUI.printFormTextarea('Text', 'contract_text', arrData['sText'], 3, 50, 'style="width:350px; height:65px;"');
	strCode += objGUI.endFieldset();

	// Close main container
	strCode += '</div>';

	strCode += objGUI.printFormButton('Speichern', 'saveContract();', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');

	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:430, width:650, resizable:false, opacity:.9});
	/* =============================================================================================================== */
}

/* ================================================== */

function loadContractContacts(iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=load_contract_contacts';
	strParameters += '&customer_id=' + iCustomerID;
	strParameters += '&contract_id=' + iContractID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: loadContractContactsCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ================================================== */

function loadContractContactsCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	var oObj = document.getElementById('contract_contact');

	// Remove all options from contact persons
	while(oObj.hasChildNodes())
	{
		oObj.removeChild(oObj.firstChild);
	}

	// Create new option-Tags on contact person
	for(var i = 0; i < arrData['aContacts'].length; i++)
	{
		var newOption = document.createElement('option');

		// Set option value
		newOption.setAttribute('value', arrData['aContacts'][i][0]);

		// Set selected value
		if(arrData['aContacts'][i][0] == arrData['selectedContact'])
		{
			newOption.setAttribute('selected', 'selected');
		}

		// Set option content
		newOption.innerHTML = arrData['aContacts'][i][1];

		// Add option to select
		oObj.appendChild(newOption);
	}
}

/* ================================================== */

function createContractInvoices()
{
	if(!bShowDueContracts)
	{
		alert('Diese Aktion ist nicht zulässig!');
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=create_contract_invoices';

	for(var i = 0; i < aDisplayedContracts.length; i++)
	{
		if($('contract_due_cb_'+aDisplayedContracts[i]['id']).checked == true)
		{
			strParameters += '&contract_id[]=' + aDisplayedContracts[i]['id'];
		}
	}

	var oInput = $('contract_days_inadvance');

	if(oInput) {
		strParameters += '&contract_days_inadvance='+$F(oInput);
	}

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: function() { loadContractsList(); }
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ================================================== */

function changeContractStatsYear()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_contracts_stats';
	strParameters += '&selected_year=' + $('contract_stats_year').value;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: changeContractStatsYearCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function changeContractStatsCustomerYear()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_contracts_stats_customer';
	strParameters += '&selected_year=' + $('contract_stats_year').value;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: changeContractStatsYearCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}



/* ================================================== */

function changeContractStatsYearCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	loadContractStatsList(arrData);
}

/* ================================================== */

function openMatchingDialog() {

	var objGUI = new GUI;
	var strCode = '';

	// Open main container
	strCode += '<div style="padding: 10px;">';

	strCode += objGUI.printFormTextarea('Pro Zeile ein Eintrag', 'matching_items', '', 3, 50, 'style="width:350px; height:100px;"');

	// Close main container
	strCode += '</div>';
	
	strCode += objGUI.printFormButton('Abgleich starten', 'executeMatching();', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	
	strCode += '<div style="padding: 10px;" id="matching_results">';
	strCode += '</div>';

	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', title:'Abgleich', overlay:true, height:600, width:520, resizable:false, opacity:.9});
	/* =============================================================================================================== */

}

function executeMatching() {

	var strRequestUrl = '/admin/extensions/office/contract.ajax.php';
	var strParameters = 'task=matching';
	strParameters += '&matching_items='+$F('matching_items');

	try
	{
		var objAjax = new Ajax.Request(
				strRequestUrl,
				{
					method		: 'post',
					parameters	: strParameters,
					onComplete	: executeMatchingCallback
				}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function executeMatchingCallback(oResponse) {

	var oData = oResponse.responseText.evalJSON();

	// Display LitBox
	$('matching_results').update(oData.result);
	/* =============================================================================================================== */
	
}

/**
 *
 */

function openContractStatsCustomerDialog()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_contracts_stats_customer';

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openContractStatsCustomerDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ================================================== */

function openContractStatsCustomerDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];
	var objGUI = new GUI;
	var strCode = '';

	// Open main container
	strCode += '<div style="padding: 10px;">';

	strCode += objGUI.printFormSelect('Jahr', 'contract_stats_year', arrData['aYears'], arrData['selectedYear'], 'onchange="changeContractStatsCustomerYear();"');

	strCode += '<div id="container_contract_stats">';
	strCode += '</div>';

	// Close main container
	strCode += '</div>';

	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', title:'Kundenauswertung', overlay:true, height:600, width:500, resizable:true, opacity:.9});
	/* =============================================================================================================== */

	loadContractStatsList(arrData);
}

/**
 *
 */

function openContractStatsDialog()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_contracts_stats';

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openContractStatsDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ================================================== */

function openContractStatsDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];
	var objGUI = new GUI;
	var strCode = '';

	// Open main container
	strCode += '<div style="padding: 10px;">';

	strCode += objGUI.printFormSelect('Jahr', 'contract_stats_year', arrData['aYears'], arrData['selectedYear'], 'onchange="changeContractStatsYear();"');

	strCode += '<div id="container_contract_stats">';
	strCode += '</div>';

	// Close main container
	strCode += '</div>';

	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', title:'Monatsauswertung', overlay:true, height:430, width:950, resizable:true, opacity:.9});
	/* =============================================================================================================== */

	loadContractStatsList(arrData);
}

/* ================================================== */

function saveContract()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_contract';
	strParameters += '&contract_id='	+ iContractID;
	strParameters += '&customer_id='	+ $('contract_customer').value;
	strParameters += '&contact_id='		+ $('contract_contact').value;
	strParameters += '&editor_id='		+ $('contract_editor').value;
	strParameters += '&product_id='		+ $('contract_product').value;
	strParameters += '&start='			+ $('contract_start').value;
	strParameters += '&end='			+ $('contract_end').value;
	strParameters += '&interval='		+ $('contract_interval').value;
	strParameters += '&amount='			+ $('contract_amount').value.replace(/\./g, '').replace(/,/, '.');
	strParameters += '&discount='		+ $('contract_discount').value.replace(/\./g, '').replace(/,/, '.');
	strParameters += '&price='			+ $('contract_price').value.replace(/\./g, '').replace(/,/, '.');
	strParameters += '&text='			+ encodeURIComponent($('contract_text').value);

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: saveContractCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ================================================== */

function saveContractCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	if(arrData['error'])
	{
		switch(arrData['error'])
		{
			case 'start':
			{
				$('saving_error_message').innerHTML = 'Bitte das Startdatum im Format TT.MM.JJJJ angeben!';
				break;
			}
			case 'end':
			{
				$('saving_error_message').innerHTML = 'Bitte das Enddatum im Format TT.MM.JJJJ angeben!';
				break;
			}
			case 'interval':
			{
				$('saving_error_message').innerHTML = 'Bitte das Interval in Monaten angeben!';
				break;
			}
			case 'amount':
			{
				$('saving_error_message').innerHTML = 'Bitte gültige Anzahl angeben!';
				break;
			}
			case 'discount':
			{
				$('saving_error_message').innerHTML = 'Bitte gültigen Rabatt angeben!';
				break;
			}
		}

		$('saving_error').show();
		$j('#saving_error').delay(3000).hide();
		
	}
	else
	{
		iContractID = arrData['id'];

		$('saving_confirmation').show();
		$j('#saving_confirmation').delay(3000).hide();

		loadContractsList();
	}
}

/* ================================================== */

function deleteContract()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_contract';
	strParameters += '&contract_id=' + iContractID;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : function() { loadContractsList(); }
							}
	);
}

/* ================================================== */

function preLoadContractsList(oSearch)
{
	sSearch = oSearch.value;

	loadContractsList(sOrder);
}

/* ================================================== */

function checkDueCBs(iType)
{
	var bMain = $('tableContractsHeadMainCB').checked;

	if(iType == 1)
	{
		for(var i = 0; i < aDisplayedContracts.length; i++)
		{
			$('contract_due_cb_'+aDisplayedContracts[i]['id']).checked = bMain;
		}
	}
	else
	{
		var iChecked = 0;
		for(var i = 0; i < aDisplayedContracts.length; i++)
		{
			if($('contract_due_cb_'+aDisplayedContracts[i]['id']).checked)
			{
				iChecked++;
			}
		}
		if(bMain)
		{
			if(iChecked != aDisplayedContracts.length)
			{
				$('tableContractsHeadMainCB').checked = false;
			}
		}
		else
		{
			if(iChecked == aDisplayedContracts.length)
			{
				$('tableContractsHeadMainCB').checked = true;
			}
		}
	}
}

/* ================================================== */

function loadContractsList(sOrder)
{

	$('toolbar_loading').show();

	$('sort_start').removeClassName('active');
	$('sort_end').removeClassName('active');
	$('sort_company').removeClassName('active');
	$('sort_product').removeClassName('active');
	$('sort_editor').removeClassName('active');
	$('sort_amount').removeClassName('active');
	$('sort_interval').removeClassName('active');
	$('sort_price').removeClassName('active');
	$('sort_discount').removeClassName('active');

	if(!sOrder) {
		sOrder = '';
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_contracts';
		strParameters += '&order='+sOrder;
		strParameters += '&client_id='+$('client_id').value;
	strParameters += '&search='+sSearch;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : loadContractsListCallback
							}
	);
}

/* ================================================== */

function loadDueList()
{
	$('toolbar_loading').show();

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_actually_contracts';

	var oInput = $('contract_days_inadvance');

	if(oInput) {
		strParameters += '&contract_days_inadvance='+$F(oInput);
	}

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : loadContractsListCallback
							}
	);
}

/* ================================================== */

function loadContractStatsList(aData)
{
	var oContainer = document.getElementById("container_contract_stats");
	
	oContainer.update(aData.table);
	
	return;
	
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

	while(tbody.hasChildNodes())
	{
		tbody.removeChild(tbody.firstChild);
	}

	var td0, td1, td2, td3;

	var aList = aData['aStats'];

	for(var i = 0; i < aList.length; i++)
	{
		objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);

		td0 = document.createElement('td');
		objTr.appendChild(td0);
		td0.innerHTML = aList[i]['month'];

		td1 = document.createElement('td');
		objTr.appendChild(td1);
		td1.innerHTML = aList[i]['price'] + ' €';
		td1.style.textAlign = 'right';

		td2 = document.createElement('td');
		objTr.appendChild(td2);
		td2.innerHTML = aList[i]['cleared'] + ' €';
		td2.style.textAlign = 'right';

		td3 = document.createElement('td');
		objTr.appendChild(td3);
		td3.innerHTML = aList[i]['diff'] + ' €';
		td3.style.textAlign = 'right';

		td0 = td1 = td2 = td3 = null;
	}

	objTr = tr.cloneNode(false);
	tbody.appendChild(objTr);

	td0 = document.createElement('th');
	objTr.appendChild(td0);
	td0.innerHTML = "Gesamt";

	td1 = document.createElement('th');
	objTr.appendChild(td1);
	td1.innerHTML = aData['total_price'] + ' €';
	td1.style.textAlign = 'right';

	td2 = document.createElement('th');
	objTr.appendChild(td2);
	td2.innerHTML = aData['total_cleared'] + ' €';
	td2.style.textAlign = 'right';

	td3 = document.createElement('th');
	objTr.appendChild(td3);
	td3.innerHTML = aData['total_diff'] + ' €';
	td3.style.textAlign = 'right';

	td0 = td1 = td2 = td3 = null;

	tbody = null;
}

/* ================================================== */

function loadContractsListCallback(objResponse)
{
	var objData 	= objResponse.responseText.evalJSON();
	var arrList 	= objData['data'];
	sOrder 			= objData['order'];

	bShowDueContracts = arrList['bShowDueContracts'];

	$('tableContractsHeadCB').innerHTML = '&nbsp;';

	if(bShowDueContracts && arrList['aContracts'].length == 0)
	{
		$('toolbar_loading').hide();
		alert('Zur Zeit sind keine fällige Verträge vorhanden.');
		bShowDueContracts = false;
		return;
	}
	else if(bShowDueContracts && arrList['aContracts'].length > 0)
	{
		$('tableContractsHeadCB').innerHTML = '<input type="checkbox" id="tableContractsHeadMainCB" style="border:0; margin:0;" onclick="checkDueCBs(1);">';
	}

	aDisplayedContracts = arrList['aContracts'];

	checkInvoicesIcon();

	if(sOrder && sOrder != 'undefined') {
		$('sort_'+sOrder).addClassName('active');
	}

	var tbody 		= document.getElementById("tbl_contracts");
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

	while(tbody.hasChildNodes())
	{
		tbody.removeChild(tbody.firstChild);
	}

	var td0, td1, td2, td3, td4, td5, td6, td7, td8, td9, td10;

	var aContracts = arrList['aContracts'];

	if(aContracts.length > 0)
	{
		for(var i = 0; i < aContracts.length; i++)
		{
			objTr = tr.cloneNode(false);
			tbody.appendChild(objTr);
			var strId = 'tr_' + aContracts[i]['id'];
			objTr.id = strId;

			Event.observe(objTr, 'click', checkContractRow.bindAsEventListener(c, strId));
			Event.observe(objTr, 'dblclick', openContractDialog.bindAsEventListener(c, aContracts[i]));
			Event.observe(objTr, 'mouseout', resetHighlightRow);
			Event.observe(objTr, 'mousemove', setHighlightRow);

			td0 = document.createElement('td');
			objTr.appendChild(td0);
			if(!bShowDueContracts)
			{
				td0.innerHTML = '<img src="/admin/media/bullet_green.png" align="absmiddle" alt="Laufend" />';
				if(aContracts[i]['active'] == 0)
				{
					td0.innerHTML = '<img src="/admin/media/bullet_red.png" align="absmiddle" alt="Abgelaufen" />';
				}
			}
			else
			{
				td0.innerHTML = '<input type="checkbox" id="contract_due_cb_'+aContracts[i]['id']+'" style="border:0; margin:0;" onclick="checkDueCBs(0);" />';
			}
			td0.style.textAlign = 'center';

			td1 = document.createElement('td');
			objTr.appendChild(td1);
			td1.innerHTML = aContracts[i]['start'];

			td2 = document.createElement('td');
			objTr.appendChild(td2);
			td2.innerHTML = aContracts[i]['end'];

			td3 = document.createElement('td');
			objTr.appendChild(td3);
			td3.innerHTML = aContracts[i]['company'];

			td3 = document.createElement('td');
			objTr.appendChild(td3);
			td3.innerHTML = aContracts[i]['contact'];

			td4 = document.createElement('td');
			objTr.appendChild(td4);
			td4.innerHTML = aContracts[i]['product'];

			td5 = document.createElement('td');
			objTr.appendChild(td5);
			td5.innerHTML = aContracts[i]['firstname'] + ' ' + aContracts[i]['lastname'];

			td6 = document.createElement('td');
			objTr.appendChild(td6);
			td6.innerHTML = parseFloat(aContracts[i]['amount']).number_format(2, ',', '.');
			td6.style.textAlign = 'right';

			td7 = document.createElement('td');
			objTr.appendChild(td7);
			td7.innerHTML = parseFloat(aContracts[i]['interval']).number_format(2, ',', '.');
			td7.style.textAlign = 'right';

			td8 = document.createElement('td');
			objTr.appendChild(td8);
			td8.innerHTML = parseFloat(aContracts[i]['price']).number_format(2, ',', '.') + ' €';
			td8.style.textAlign = 'right';

			td9 = document.createElement('td');
			objTr.appendChild(td9);
			td9.innerHTML = parseFloat(aContracts[i]['discount']).number_format(2, ',', '.') + ' %';
			td9.style.textAlign = 'right';

			td10 = document.createElement('td');
			objTr.appendChild(td10);
			td10.innerHTML = parseFloat(aContracts[i]['total']).number_format(2, ',', '.') + ' €';
			td10.style.textAlign = 'right';

			td0 = td1 = td2 = td3 = td4 = td5 = td6 = td7 = td8 = td9 = td10 = null;
		}
	}
	else
	{
		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);

		td = document.createElement('td');
		objTr.appendChild(td);
		td.innerHTML = 'Zur Zeit sind keine Verträge vorhanden.';
		td.style.textAlign = 'center';
		td.colSpan = '10';
	}

	tbody = null;

	selectedContractRow = 0;

	checkContractToolbar();

	$('toolbar_loading').hide();
}

/* ==================================================================================================== */

var loadContractsListObserver;
var loadContractsListEventElement;
var selectedContractRow = 0;

var arrContractIcons = ['toolbar_edit',
	'toolbar_delete'];

var arrContractIconState = [];
var bCheckContractToolbarInProgress = 0;

/* ================================================== */

function executeContractAction(strId, strAction) {

	if(bCheckContractToolbarInProgress) {
		window.setTimeout("executeContractAction('" + strId + "', '" + strAction + "')", 100);
		return false;
	}

	if
	(
		strAction != 'new' &&
		strAction != 'display_stats' &&
		strAction != 'display_stats_customer' &&
		strAction != 'matching' &&
		strAction != 'show_aktually' &&
		strAction != 'create_invoices' &&
		!arrContractIconState['toolbar_' + strAction]
	)
	{
		alert("Diese Aktion ist nicht zulässig!");
		return false;
	}

	var intContractId;

	if(strAction != 'new' && selectedContractRow)
	{
		intContractId = selectedContractRow.replace(/tr_/, '');
	}

	switch(strAction)
	{
		case 'new':
		{
			iContractID = 0;
			openContractDialog();
			break;
		}
		case 'edit':
		{
			iContractID = intContractId;
			openContractDialog();
			break;
		}
		case 'delete':
		{
			iContractID = intContractId;
			if(confirm('Wirklich löschen?'))
			{
				deleteContract();
			}
			break;
		}
		case 'display_stats':
		{
			openContractStatsDialog();
			break;
		}
		case 'display_stats_customer':
		{
			openContractStatsCustomerDialog();
			break;
		}
		case 'matching':
		{
			openMatchingDialog();
			break;
		}
		case 'show_aktually':
		{
			loadDueList();
			break;
		}
		case 'create_invoices':
		{
			createContractInvoices();
			break;
		}
	}
}

/* ================================================== */

function checkInvoicesIcon()
{
	if(bShowDueContracts)
	{
		$j('#tmp_toolbar_create_invoices').fadeTo('fast', 1);
	}
	else
	{
		$j('#tmp_toolbar_create_invoices').fadeTo('fast', 0.2);
	}
}

/* ================================================== */

function checkContractRow(e, strId)
{
	var objRow = $(strId);

	if(selectedContractRow && $(selectedContractRow)) {
		$(selectedContractRow).className = '';
	}

	if(!objRow.hasClassName('selectedRow')) {
		objRow.className = 'selectedRow';
	} else {
		objRow.className = '';
	}

	selectedContractRow = strId;

	iContractID = selectedContractRow.replace(/tr_/, '');

	checkContractToolbar();
}

/* ================================================== */

function checkContractToolbar()
{
	if(!bCheckContractToolbarInProgress)
	{
		bCheckContractToolbarInProgress = 1;

		if(selectedContractRow)
		{
			var strRequestUrl = '/admin/extensions/office.ajax.php';
			var strParameters = 'task=check_contract_toolbar&contract_id=' + selectedContractRow.replace(/tr_/, '');

			var objAjax = new Ajax.Request(
									strRequestUrl,
									{
										method : 'post',
										parameters : strParameters,
										onComplete : checkContractToolbarCallback
									}
			);
		}
		else
		{
			arrContractIcons.each(function(strIcon){switchContractToolbarIcon(strIcon, 0);});

			bCheckContractToolbarInProgress = 0;
		}
	}
}

/* ================================================== */

function checkContractToolbarCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrList = objData['data'];

	arrContractIcons.each(function(strIcon) {
		var bolShow = arrList[strIcon];
		switchContractToolbarIcon(strIcon, bolShow);
	});

	bCheckContractToolbarInProgress = 0;
}

/* ================================================== */

function checkContractsListHeight()
{
	var intHeight = 0;

	intHeight = window.innerHeight;

	if(!intHeight)
	{
		intHeight = document.body.clientHeight;
	}
	if(!intHeight)
	{
		intHeight = document.documentElement.clientHeight;
	}

	intHeight = intHeight - 135;

	var objTable = $('tableContracts-body');

	if(objTable)
	{
		objTable.style.height = intHeight+'px';	
	}
	else
	{
		document.write('<style>.scroll-table-body { height: '+intHeight+'px; }</style>');
	}
}

/* ================================================== */

function switchContractToolbarIcon(strIcon, bolShow)
{
	var objIcon = $(strIcon);

	if(bolShow)
	{
		if(arrContractIconState[strIcon] == undefined || arrContractIconState[strIcon] == 0)
		{
			$j(objIcon).fadeTo('fast', 1);
		}
		arrContractIconState[strIcon] = 1;
	}
	else
	{
		if(arrContractIconState[strIcon] == undefined || arrContractIconState[strIcon] == 1)
		{
			$j(objIcon).fadeTo('fast', 0.2);
		}
		arrContractIconState[strIcon] = 0;
	}
}