
/* ==================================================================================================== */ // Globals

aContactPersons	= new Array();
aLocations		= new Array();
aComments		= new Array();

/* ==================================================================================================== */ // Requests

var sTmpPhoneNumber = '';

function preLogPhoneClick(oLink)
{
	sTmpPhoneNumber = oLink.href;
}

function logPhoneClick()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=log_phone_click&number='+sTmpPhoneNumber;
//alert(selectedRow);

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters
							}
	);
}

/* ====================================================================== */

function openDialog(intCustomerId)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_customer&customer_id='+intCustomerId;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : openDialogCallback
							}
	);
}

/* ====================================================================== */

function openProtocolDialog(intCustomerId)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_protocols_data&customer_id='+intCustomerId;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : openProtocolDialogCallback
							}
	);
}

/* ====================================================================== */

function saveProtocolData(iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=add_protocol';

	document.getElementById('main_container').style.cursor	= 'wait';
	document.getElementById('save_button').style.cursor		= 'wait';

	strParameters += '&customer_id='	+ iCustomerID;

	strParameters += '&contact_id='		+ encodeURIComponent(document.getElementById('pro_contact_id').value);
	strParameters += '&editor_id='		+ encodeURIComponent(document.getElementById('pro_editor_id').value);
	strParameters += '&topic='			+ encodeURIComponent(document.getElementById('pro_topic').value);
	strParameters += '&subject='		+ encodeURIComponent(document.getElementById('pro_subject').value);

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : saveProtocolDataCallback
							}
	);
}

/* ====================================================================== */

function saveCustomerData(iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';

	document.getElementById('main_container').style.cursor	= 'wait';
	document.getElementById('save_button').style.cursor		= 'wait';

	var oFormData = new FormData($('customer_form'));
	oFormData.append('task', 'save_customer_data');
	oFormData.append('customer_id', iCustomerID);

	var oXhr = new XMLHttpRequest();
	oXhr.open('POST', strRequestUrl, true);
	oXhr.onload = saveCustomerDataCallback;
	oXhr.send(oFormData);
}

/* ====================================================================== */

function deleteCustomer(iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_customer&customer_id=' + iCustomerID;

	if(confirm('Möchten Sie wirklich diesen Kunden löschen?'))
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method 		: 'post',
									parameters	: strParameters,
									onComplete	: deleteCustomerCallback
								}
		);
	}
}

/* ====================================================================== */

function getContactPersons(iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_contact_persons&customer_id=' + iCustomerID;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method 		: 'post',
								parameters	: strParameters,
								onComplete	: getContactPersonsCallback
							}
	);
}

/* ====================================================================== */

function saveContactPerson(iContactPersonID, iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_contact_person';

	strParameters += '&contact_person_id='	+ iContactPersonID;
	strParameters += '&customer_id='		+ iCustomerID;

	if
	(
		$('cp_password').value != ''
			&&
		$('cp_password').value != $('cp_password_confirm').value
	)
	{
		alert('Das Feld "Passwort" stimmt mit "Passwortwiederholung" nicht überein.\n\nLassen Sie diese Felder leer, falls Sie das Passwort nicht ändern möchten.');
		return;
	}

	strParameters += '&sex='			+ encodeURIComponent(document.getElementById('cp_sex').value);
	strParameters += '&firstname='		+ encodeURIComponent(document.getElementById('cp_firstname').value);
	strParameters += '&lastname='		+ encodeURIComponent(document.getElementById('cp_lastname').value);
	strParameters += '&nickname='		+ encodeURIComponent(document.getElementById('cp_nickname').value);
	strParameters += '&password='		+ encodeURIComponent(document.getElementById('cp_password').value);
	strParameters += '&password_c='		+ encodeURIComponent(document.getElementById('cp_password_confirm').value);
	strParameters += '&email='			+ encodeURIComponent(document.getElementById('cp_email').value);
	strParameters += '&phone='			+ encodeURIComponent(document.getElementById('cp_phone').value);
	strParameters += '&mobile='			+ encodeURIComponent(document.getElementById('cp_mobile').value);
	strParameters += '&fax='			+ encodeURIComponent(document.getElementById('cp_fax').value);
	strParameters += '&description='	+ encodeURIComponent(document.getElementById('cp_description').value);
	
	if(document.getElementById('cp_invoice_contact').checked) {
		strParameters += '&invoice_contact='	+ encodeURIComponent(document.getElementById('cp_invoice_contact').value);
	}
	
	if(document.getElementById('cp_invoice_recipient').checked) {
		strParameters += '&invoice_recipient='	+ encodeURIComponent(document.getElementById('cp_invoice_recipient').value);
	}
	
	if(document.getElementById('cp_lastname').value.length == 0)
	{
		alert('Bitte geben Sie einen Nachnamen ein.');
		return;
	}
	else
	{
		document.getElementById('main_container').style.cursor	= 'wait';
		document.getElementById('save_button').style.cursor		= 'wait';
	}

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method 		: 'post',
								parameters	: strParameters,
								onComplete	: saveContactPersonCallback
							}
	);
}

/* ====================================================================== */

function deleteContactPerson(iContactPersonID, iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_contact_person';

	strParameters += '&contact_person_id='	+ iContactPersonID;
	strParameters += '&customer_id='		+ iCustomerID;

	if(confirm('Möchten Sie wirklich diesen Ansprechpartner löschen?'))
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method 		: 'post',
									parameters	: strParameters,
									onComplete	: deleteContactPersonCallback
								}
		);
	}
}

/* ====================================================================== */

function getCustomerOverview(iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_customer_overview&customer_id='+iCustomerID;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : getCustomerOverviewCallback
							}
	);
}

/* ====================================================================== */

function getProtocols(iCustomerID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_protocols&customer_id='+iCustomerID;

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : getProtocolsCallback
							}
	);
}

/* ==================================================================================================== */ // Callback functions

function openDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	var objGUI = new GUI;

	var strCode = '';

	// Open main container
	strCode += '<form id="customer_form" onsubmit="return false;"><div id="main_container" class="container-fluid">';

	strCode += objGUI.startRow();
	strCode += objGUI.startCol('');

	// Protect the customer number if the number is the ID
	sDisabled = '';
	if(arrData['bLockID'] == true)
	{
		sDisabled = 'readonly="readonly" style="background-color:#F7F7F7;"';
	}

	strCode += objGUI.startFieldset('Allgemeines', 'style="height: auto;"');
	strCode += objGUI.printFormInput('Kundennummer', 'number', arrData['number'], sDisabled);
	strCode += objGUI.printFormInput('Kürzel', 'matchcode', arrData['matchcode']);
	strCode += objGUI.printFormInput('Firma', 'company', arrData['company']);
	strCode += objGUI.printFormMultiSelect('Kundengruppe', 'group_id[]', arrData['groups'], arrData['aAdditionals']['group_id'], 'size="3" multiple="multiple" style="height: 44px;"');
	strCode += objGUI.printFormInput('Redmine PID', 'redmine_project_id', arrData['aAdditionals']['redmine_project_id']);
	strCode += objGUI.endFieldset();

	strCode += objGUI.startFieldset('Anschrift', 'style="height: auto;"');
	strCode += objGUI.printFormInput('Zusatz', 'addition', arrData['addition']);
	strCode += objGUI.printFormInput('Adresse', 'address', arrData['address']);
	strCode += objGUI.printFormInput('PLZ', 'zip', arrData['zip'], 'onkeyup="fillGEO(\'zip\', \'city\')"');
	strCode += objGUI.printFormInput('Ort', 'city', arrData['city'], 'onkeyup="fillGEO(\'city\', \'zip\')"');
	strCode += '<div style="position:relative; white-space:nowrap;">';
	strCode += '<div onclick="this.hide();" style="height:80px; overflow-y:scroll; padding:3px; display:none; background-color:#FFC; position:absolute; margin-left:153px; border: 1px solid #000; z-index:100000;" id="geo_list"></div>';
	strCode += '</div>';
	strCode += objGUI.printFormSelect('Land', 'country', arrData['countries'], arrData['country']);

	strCode += objGUI.endFieldset();

	// Bild-Upload - Kundenlogo
	strCode += objGUI.startFieldset('Kundenlogo', 'style="height: auto;"');
	strCode += objGUI.printFormUpload('Bilddatei', 'logo');
	strCode += objGUI.printFormHidden('delete_logo', false);
	strCode += '<div class="logo-container">';
	strCode += getLogoPreviewImage(arrData['logo']);
	strCode += '</div>';
	strCode += objGUI.endFieldset();

	strCode += objGUI.endCol();

	strCode += objGUI.startCol('');

	strCode += objGUI.startFieldset('Kontaktdaten', 'style="height: auto;"');
	strCode += objGUI.printFormInput('Telefon', 'phone', arrData['phone']);
	strCode += objGUI.printFormInput('Telefax', 'fax', arrData['fax']);
	strCode += objGUI.printFormInput('E-Mail', 'email', arrData['email']);
	strCode += objGUI.printFormSelect('Ansprechpartner', 'cms_contact', arrData['aCMS_User'], arrData['aAdditionals']['cms_contact']);
	strCode += objGUI.printFormSelect('Sprache', 'language', arrData['languages'], arrData['aAdditionals']['language']);
	strCode += objGUI.printFormCheckbox('Rechnungsversand per E-Mail', 'by_email', arrData['aAdditionals']['by_email']);
	strCode += objGUI.endFieldset();

	strCode += objGUI.startFieldset('Zahlungsbedingungen', 'style="height: auto;"');
	if(arrData['aCustomerPayments'] && arrData['aCustomerPayments']['invoices'] && arrData['aCustomerPayments']['invoices'].length > 0)
	{
		strCode += objGUI.printFormSelect('Rechnung', 'payment_invoice', arrData['aCustomerPayments']['invoices'], arrData['aCustomerPayments']['selectedPaymentInvoice']);
	}
	if(arrData['aCustomerPayments'] && arrData['aCustomerPayments']['misc'] && arrData['aCustomerPayments']['misc'].length > 0)
	{
		strCode += objGUI.printFormSelect('Sonstige', 'payment_misc', arrData['aCustomerPayments']['misc'], arrData['aCustomerPayments']['selectedPaymentMisc']);
	}
	strCode += objGUI.printFormInput('Debitoren K-Nr.', 'debitor_nr', arrData['aAdditionals']['debitor_nr']);
	strCode += objGUI.printFormInput('Kreditoren K-Nr.', 'creditor_nr', arrData['aAdditionals']['creditor_nr']);
	strCode += objGUI.printFormInput('USt.-ID-Nr.', 'vat_id_nr', arrData['aAdditionals']['vat_id_nr']);
	strCode += objGUI.endFieldset();

	strCode += objGUI.startFieldset('VAT Validation Response', 'style="height: auto;"');
	strCode += '<div style="height: 90px; overflow: auto;" id="validation_response"></div>';
	strCode += objGUI.endFieldset();

	// Save document button
	strCode += objGUI.printFormButton('Daten speichern', 'saveCustomerData('+arrData['id']+');return false;', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	
	strCode += objGUI.endCol();
	strCode += objGUI.endRow();

	// Close main container
	strCode += '</div></form>';

	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, width:1100, resizable:false, opacity:.9});
	/* =============================================================================================================== */

	if(arrData['aAdditionals']['vat_id_valid'] && arrData['aAdditionals']['vat_id_valid'] == 1) {
		$('vat_id_nr').style.color = 'green';
	} else {
		$('vat_id_nr').style.color = '';
	}

	addLogoPreviewDeleteEvent();
}

/* ====================================================================== */

function openProtocolDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

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

	strCode += objGUI.startFieldset('Ereignis protokollieren');
		strCode += objGUI.printFormSelect('Kontakt', 'pro_contact_id', arrData['aProContacts'], arrData['iProSelectedContacts'], 'style="width:200px;"');
		strCode += objGUI.printFormSelect('Bearbeiter', 'pro_editor_id', arrData['aProEditors'], arrData['iProSelectedEditors'], 'style="width:200px;"');
		strCode += objGUI.printFormSelect('Aktivität', 'pro_topic', arrData['aActivities'], '', 'style="width:200px;"');
		strCode += objGUI.printFormTextarea('Betreff', 'pro_subject', '', 3, 5, 'style="width:550px; height:250;"');
	strCode += objGUI.endFieldset();

	// Save document button
	strCode += objGUI.printFormButton('Daten speichern', 'saveProtocolData('+arrData['customer_id']+')', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');

	// Close main container
	strCode += '</div>';

	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:450, width:700, resizable:false, opacity:.9});
	/* =============================================================================================================== */
}

function saveProtocolDataCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	document.getElementById('saving_confirmation').style.display	= 'inline';
	document.getElementById('main_container').style.cursor			= 'auto';
	document.getElementById('save_button').style.cursor				= 'auto';

	// Create the protocols list
	getProtocols(arrData['customer_id']);
}

/* ====================================================================== */

function saveCustomerDataCallback()
{
	var objData = this.responseText.evalJSON();
	var arrData = objData['data'];

	if(arrData['check_vat'] && arrData['check_vat'] == 1) {
		$('vat_id_nr').style.color = 'green';
	} else {
		$('vat_id_nr').style.color = 'red';
	}

	sMessage = '';

	if(arrData['save_error']) {

		sMessage += '<span style="color:red;">';

		if(arrData['save_error'] == 'VATID_WRONG_FORMAT') {
			sMessage += 'Der Kunden konnte nicht gespeichert werden. Die Ust.-ID hat kein korrektes Format.';
		} else if(arrData['save_error']) {
			sMessage += arrData['save_error'];
		}

		sMessage += '</span>';

		var sResponse = '<span style="color:red;">';

		if(arrData['vat_id_errors']) {
			arrData['vat_id_errors'].each(function(sError) {
				sResponse += sError+'<br/>';	
			});
			
		}
		
		sResponse += '</span>';

		if(sResponse != '') {
			$('validation_response').update(sResponse);
		}

	} else {

		$('validation_response').update('');

		sMessage += '<span style="color:green;">';
		sMessage += 'Die Daten wurden erfolgreich gespeichert!';
		sMessage += '</span>';

		// Set new customer ID in the input field if required
		document.getElementById('number').value = arrData['customer_id'];

		// Protocol DIVs
		document.getElementById('protocolsList').style.display			= 'none';
		document.getElementById('non_customer_protocol').style.display	= 'inline';

		// Reload the list + display/hide DIVs
		deleteCustomerCallback();

		selectedRow = 0;

		updateLogoPreview(arrData['logo']);
		
		var sResponse = '<span style="color:green;">';

		if(arrData['vat_id_errors']) {
			arrData['vat_id_errors'].each(function(sError) {
				sResponse += sError+'<br/>';	
			});
			
		}
		
		sResponse += '</span>';

		if(sResponse != '') {
			$('validation_response').update(sResponse);
		}		
	}

	$('LB_title').update(sMessage);

	document.getElementById('main_container').style.cursor			= 'auto';
	document.getElementById('save_button').style.cursor				= 'auto';

}

/* ====================================================================== */

function deleteCustomerCallback()
{
	// Reload the list
	loadCustomerList();

	// Display / hide DIVs
	document.getElementById('nonCustomerSelected').style.display	= 'inline';
	document.getElementById('contactPersonsToolbar').style.display	= 'none';
	document.getElementById('contactPersonsList').style.display		= 'none';
	document.getElementById('nonContactPersons').style.display		= 'none';
	document.getElementById('non_customer_overview').style.display	= 'inline';
	document.getElementById('customer_overview').style.display		= 'none';
	
	document.getElementById('noLocation').style.display				= 'inline';
	document.getElementById('locationToolbar').style.display		= 'none';
	document.getElementById('locationList').style.display			= 'none';
	document.getElementById('editLocationToolbar').style.display	= 'none';
	
	document.getElementById('noComment').style.display				= 'inline';
	document.getElementById('commentToolbar').style.display		= 'none';
	document.getElementById('commentList').style.display			= 'none';
	document.getElementById('editCommentToolbar').style.display	= 'none';
		
	selectedRow = 0;
}

/* ====================================================================== */

function getContactPersonsCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	aContactPersons = arrData['aContactPersons'];

	// Set the global aProtocols array
	aProtocols		= arrData['aProtocols']

	document.getElementById('contactPersonsToolbar').style.display	= 'none';
	document.getElementById('nonCustomerSelected').style.display	= 'none';

	// Check the number of contact persons
	if(arrData['aContactPersons'].length == 0)
	{
		document.getElementById('nonContactPersons').style.display		= 'inline';
		document.getElementById('contactPersonsList').style.display		= 'none';
		document.getElementById('contactPersonsToolbar').style.display	= 'inline';
		document.getElementById('editCPToolbar').style.display			= 'none';
	}
	else
	{
		document.getElementById('nonContactPersons').style.display		= 'none';
		document.getElementById('contactPersonsList').style.display		= 'inline';
		document.getElementById('contactPersonsToolbar').style.display	= 'inline';
		document.getElementById('editCPToolbar').style.display			= 'inline';

		// Create the list of contact persons
		createContactPersonsList(arrData['aContactPersons']);
	}
}

/* ====================================================================== */

function saveContactPersonCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	if(arrData['save_error'] && arrData['save_error'] == 'NICKNAME_NOT_UNIQUE')
	{
		alert('Nickname konnte nicht gespeichert werden. Es existiert bereits ein Benutzer mit diesem Nicknamen.\n\nAndere Personendaten wurden erfolgreich gespeichert.');
	}

	document.getElementById("saving_confirmation").style.display = 'inline';
	document.getElementById('main_container').style.cursor	= 'auto';
	document.getElementById('save_button').style.cursor		= 'auto';

	// Create the list of contact persons with new data
	getContactPersons(arrData['customer_id']);

	// Create the protocols list
	getProtocols(arrData['customer_id']);
}

/* ====================================================================== */

function deleteContactPersonCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	getContactPersons(arrData['customer_id']);
	getProtocols(arrData['customer_id']);
}

/* ====================================================================== */

var oInterval;

function displayLoader()
{
	clearInterval(oInterval);
	bolReadyState = 1;

	logPhoneClick();
}

/* ====================================================================== */

function getCustomerOverviewCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	// Create address
	if(arrData['full_address']) {
		sAddress = arrData['full_address'];
	} else {
		sAddress = '';
		if(arrData['addition'] != '')
		{
			sAddress += arrData['addition'] + '<br />';
		}
		sAddress += arrData['address'] + '<br />';
		sAddress += arrData['zip'] + ' ' + arrData['city'] + '<br />';
		sAddress += arrData['country'];
	}

	document.getElementById('co_number').innerHTML = 'Kundennummer: ' + arrData['number'];
	document.getElementById('co_company').innerHTML = arrData['company'];
	document.getElementById('co_address').innerHTML = sAddress;
	if(arrData['phone_link'])
	{
		document.getElementById('co_phone').innerHTML = 'Telefon: <a href="' + arrData['phone_link'] + '" onclick="bolReadyState = 0; logPhoneClick(\'' + arrData['phone'] + '\'); oInterval = setInterval(\'displayLoader()\', 100);">' + arrData['phone'] + '</a>';
	}
	else
	{
		document.getElementById('co_phone').innerHTML = 'Telefon: ' + arrData['phone'];
	}
	document.getElementById('co_fax').innerHTML = 'Fax: ' + arrData['fax'];
	document.getElementById('co_email').innerHTML = 'E-Mail: <a href="mailto:' + arrData['email'] + '">' + arrData['email'] + '</a>';
	document.getElementById('co_pay').innerHTML = '<strong>Zahlungsbedingungen:</strong><br/>';
	for(var i = 0; i < arrData['aCustomerPayments']['invoices'].length; i++) {
		if(arrData['aCustomerPayments']['invoices'][i][0] == arrData['aCustomerPayments']['selectedPaymentInvoice']) {
			document.getElementById('co_pay').innerHTML += 'Rechnung: ' + arrData['aCustomerPayments']['invoices'][i][1];
			break;
		}
	}
	document.getElementById('co_pay').innerHTML += '<br/>';
	for(var i = 0; i < arrData['aCustomerPayments']['invoices'].length; i++)
	{
		if(arrData['aCustomerPayments']['misc'][i][0] == arrData['aCustomerPayments']['selectedPaymentMisc'])
		{
			document.getElementById('co_pay').innerHTML += 'Sonstige: ' + arrData['aCustomerPayments']['misc'][i][1];
			break;
		}
	}

	document.getElementById('customer_overview').style.display = 'inline';
	document.getElementById('non_customer_overview').style.display = 'none';
}

/* ====================================================================== */

function getProtocolsCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	createProtocolsList(arrData['aProtocols']);
}

/* ==================================================================================================== */ // Functions

function preOpenContactPersonDialog(sModus)
{
	if(sModus == 'edit')
	{
		if(selectedCPRow == 0)
		{
			alert('Bitte wählen Sie den zu bearbeitenden Ansprechspartner.');
			return;
		}
		for(var i = 0; i < aContactPersons.length; i++)
		{
			if(aContactPersons[i]['id'] == selectedCPRow.replace(/cp_tr_/, ''))
			{
				selectedCPRow = 0;
				createContactPersonsList(aContactPersons);
				openContactPersonDialog(i, aContactPersons[i]);
				return;
			}
		}
	}
	else if(sModus == 'delete')
	{
		if(selectedCPRow == 0)
		{
			alert('Bitte wählen Sie den zu löschenden Ansprechspartner.');
			return;
		}
		deleteContactPerson(selectedCPRow.replace(/cp_tr_/, ''), selectedRow.replace(/tr_/, ''));
		return;

	} else {

		// Prepare an empty array
		aContactPerson = Array();
		aContactPerson['id'] = 0;
		aContactPerson['sex'] = 0;
		aContactPerson['firstname'] = '';
		aContactPerson['lastname'] = '';
		aContactPerson['nickname'] = '';
		aContactPerson['password'] = '';
		aContactPerson['password_confirm'] = '';
		aContactPerson['email'] = '';
		aContactPerson['phone'] = '';
		aContactPerson['mobile'] = '';
		aContactPerson['fax'] = '';
		aContactPerson['description'] = '';
	}

	return aContactPerson;
}

/* ====================================================================== */

function openContactPersonDialog(oEvent, aContactPerson) {

	if(!aContactPerson['id']) {
		aContactPerson = preOpenContactPersonDialog();
	}

	// Define the contact person sex
	var aSex = new Array(new Array(1, 'Herr'), new Array(2, 'Frau'));

	var objGUI = new GUI;

	var strCode = '';

	// Open main container
	strCode += '<div id="main_container" class="container-fluid" onclick="document.getElementById(\'saving_confirmation\').style.display = \'none\'">';
 
	// Saving Confirmation
	strCode += '<div id="saving_confirmation" style="display:none; color:#008000;">';
		strCode += '<div style="text-align:center; padding-top:10px;">';
			strCode += '<b>Die Daten wurden erfolgreich gespeichert!</b>';
		strCode += '</div>';
	strCode += '</div>';

	strCode += objGUI.startFieldset('Personendaten');
		strCode += objGUI.printFormSelect('Anrede', 'cp_sex', aSex, aContactPerson['sex']);
		strCode += objGUI.printFormInput('Vorname', 'cp_firstname', aContactPerson['firstname']);
		strCode += objGUI.printFormInput('Nachname', 'cp_lastname', aContactPerson['lastname']);
		strCode += objGUI.printFormInput('Nickname', 'cp_nickname', aContactPerson['nickname']);
		strCode += objGUI.printFormPassword('Passwort', 'cp_password', '');
		strCode += objGUI.printFormPassword('Passwort wdh.', 'cp_password_confirm', '');
		strCode += objGUI.printFormInput('E-Mail', 'cp_email', aContactPerson['email']);
		strCode += objGUI.printFormInput('Telefon', 'cp_phone', aContactPerson['phone']);
		strCode += objGUI.printFormInput('Mobil', 'cp_mobile', aContactPerson['mobile']);
		strCode += objGUI.printFormInput('Telefax', 'cp_fax', aContactPerson['fax']);

		strCode += objGUI.printFormTextarea('Anmerkungen', 'cp_description', aContactPerson['description'], 3, 50, 'style="height:45px;"');
		// Invoice		
		strCode += objGUI.printFormCheckbox('Rechnungskontakt', 'cp_invoice_contact', aContactPerson['invoice_contact']);
		strCode += objGUI.printFormCheckbox('Rechnungsempfänger', 'cp_invoice_recipient', aContactPerson['invoice_recipient']);
		
	strCode += objGUI.endFieldset();

	// Save document button
	strCode += objGUI.printFormButton('Daten speichern', 'saveContactPerson('+aContactPerson['id']+', '+selectedRow.replace(/tr_/, '')+');', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');

	strCode = oWdHooks.executeHook('add_additional_buttons', strCode, aContactPerson['id']);

	// Close main container
	strCode += '</div>';



	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:400, width:500, resizable:false, opacity:.9});
	/* =============================================================================================================== */

}

/* ==================================================================================================== */ // Table creation functions

function createContactPersonsList(aConPers)
{
	var tbody 		= document.getElementById('tbl_contact_persons');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5, td6;

	// Remove all contact persons
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Reset the selected contact person row
	selectedCPRow = 0;

	// Create new contact persons list
    for(var i = 0; i < aConPers.length; i++, c++)
    {
    	objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);
        var strId = 'cp_tr_' + aConPers[i]['id'];
        objTr.id = strId;

        Event.observe(objTr, 'click', checkCPRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', openContactPersonDialog.bindAsEventListener(c, aConPers[i]));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = (aConPers[i]['firstname'].length != 0 ? aConPers[i]['firstname'] : '&nbsp;');

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = (aConPers[i]['lastname'].length != 0 ? aConPers[i]['lastname'] : '&nbsp;');

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = (aConPers[i]['email'].length != 0 ? '<a href="mailto:' + aConPers[i]['email'] + '">' + aConPers[i]['email'] + '</a>' : '&nbsp;');

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		if(aConPers[i]['phone_link'])
		{
			td3.innerHTML = (aConPers[i]['phone'].length != 0 ? '<a href="' + aConPers[i]['phone_link'] + '" onclick="bolReadyState = 0; logPhoneClick(\'' + aConPers[i]['phone_link'] + '\'); oInterval = setInterval(\'displayLoader()\', 100);">' + aConPers[i]['phone'] + '</a>' : '&nbsp;');
		}
		else
		{
			td3.innerHTML = (aConPers[i]['phone'].length != 0 ? aConPers[i]['phone'] : '&nbsp;');
		}

		td4 = document.createElement("td");
		objTr.appendChild(td4);
		if(aConPers[i]['mobile_link'])
		{
			td4.innerHTML = (aConPers[i]['mobile'].length != 0 ? '<a href="' + aConPers[i]['mobile_link'] + '" onclick="bolReadyState = 0; oInterval = setInterval(\'displayLoader()\', 100);">' + aConPers[i]['mobile'] + '</a>' : '&nbsp;');
		}
		else
		{
			td4.innerHTML = (aConPers[i]['mobile'].length != 0 ? aConPers[i]['mobile'] : '&nbsp;');
		}

		td5 = document.createElement("td");
		objTr.appendChild(td5);
		td5.innerHTML = (aConPers[i]['fax'].length != 0 ? aConPers[i]['fax'] : '&nbsp;');

		td6 = document.createElement("td");
		objTr.appendChild(td6);
		td6.innerHTML = (aConPers[i]['description'].length != 0 ? aConPers[i]['description'] : '&nbsp;');

		td0 = td1 = td2 = td3 = td4 = td5 = td6 = null;
	}

	tbody = null;
}

function createProtocolsList(aProtocols)
{
	var tbody 		= document.getElementById('tbl_protocols');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5;

	// Remove all protocols
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Create new protocols list
    for(var i = 0; i < aProtocols.length; i++, c++)
    {
    	objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);
        var strId = 'protocol_tr_' + aProtocols[i]['id'];
        objTr.id = strId;

		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = aProtocols[i]['date'];

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = aProtocols[i]['topic'];

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = (aProtocols[i]['subject'].length != 0 ? aProtocols[i]['subject'] : '&nbsp;');

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = aProtocols[i]['contact_id'];

		td4 = document.createElement("td");
		objTr.appendChild(td4);
		td4.innerHTML = aProtocols[i]['editor_id'];

		td0 = td1 = td2 = td3 = td4 = null;
	}

	// Protocol DIVs
	document.getElementById('protocolsList').style.display			= 'inline';
	document.getElementById('non_customer_protocol').style.display	= 'none';

	tbody = null;
}

/* =============================================================================================================== */

var iBillingsCustomerID = 0;

function openBillingDialog(iCustomerID) {
	
	$('toolbar_loading').show();
	
	var sParams = 'task=get_billings&customer_id=' + iCustomerID;

	iBillingsCustomerID = iCustomerID;

	var objAjax = new Ajax.Request(
		'/admin/extensions/office.ajax.php',
		{
			method : 'post',
			parameters : sParams,
			onComplete : openBillingDialogCallback
		}
	);
}

function openBillingDialogCallback(oResponse) {

	var oData	= oResponse.responseText.evalJSON();
	var aLines	= oData['data'];

	var sCode = '';

	var iTotalTimes = 0;
	var iTotalTotal = 0;

	var oGUI = new GUI;

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	sCode += '<form id="formBillings" style="padding:10px;">';
		sCode += '<table id="tableBillings" cellpadding="0" cellspacing="0" border="0" class="table" style="width:100%;">';
			sCode += '<thead>';
				sCode += '<tr>';
					sCode += '<th>&nbsp;</th>';
					sCode += '<th>Bezeichnung</th>';
					sCode += '<th>Abrechnung</th>';
					sCode += '<th>Schätzung</th>';
					sCode += '<th>Tats. Aufwand</th>';
					sCode += '<th>Aufwand incl. Faktor</th>';
					sCode += '<th style="width:100px;">Gesamt</th>';
				sCode += '</tr>';
			sCode += '</thead>';

			for(var i = 0; i < aLines.length; i++) {

				var aLine = aLines[i];				
				var sKey = aLine['type'] + '_' + aLine['ticket_id'];

				sCode += '<tr>';

					sCode += '<td><input type="checkbox" name="save['+sKey+'][active]" value="1"></td>';
					sCode += '<td>';
					sCode += 'Ticket #'+aLine['ticket_id']+' - '+aLine['title'];
					sCode += oGUI.printFormHidden('save['+sKey+'][type]', aLine['type']);
					sCode += oGUI.printFormHidden('save['+sKey+'][ticket_id]', aLine['ticket_id']);
					sCode += oGUI.printFormHidden('save['+sKey+'][title]', aLine['title']);
					sCode += oGUI.printFormHidden('save['+sKey+'][created]', aLine['created']);
					sCode += oGUI.printFormHidden('save['+sKey+'][closed]', aLine['closed']);
					sCode += oGUI.printFormHidden('save['+sKey+'][billing_flag]', aLine['billing_flag']);
					sCode += '</td>';

					if(aLine['billing_flag'] == 'b') {
						sCode += '<td>Aufwand</td>';
					} else {
						sCode += '<td>Schätzung</td>';
					}

					if(aLine['type'] == 'redmine') {
						var iBilling = aLine['billing'] / 3600;
						sCode += '<td style="text-align:right;">' + iBilling.number_format(2,',','.') + '</td>';
					} else {
						sCode += '<td style="text-align:right;">' + aLine['billing'] + '</td>';
					}

					var iAmount = aLine['time'] / 3600;

					iTotalTimes += parseFloat(iAmount.toFixed(2));

					sCode += '<td style="text-align:right;">' + iAmount.number_format(2,',','.') + '</td>';

					if(aLine['billing_flag'] != 'm') {
						var iAmount = aLine['factor_time'] / 3600;

						sCode += '<td>';
							sCode += '<input class="txt w100 billingTimes" id="h_' + aLine['ticket_id'] + '" name="save['+sKey+'][hours]" value="' + iAmount.number_format(2,',','.') + '" onblur="recalculateBillings();" /> h';
							sCode += '<input type="hidden" id="h_' + aLine['ticket_id'] + '_price" name="save['+sKey+'][price]" value="' + aLine['price'] + '" />';
						sCode += '</td>';
						sCode += '<td id="h_' + aLine['ticket_id'] + '_total" style="text-align:right;">' + aLine['total'].number_format(2,',','.') + '</td>';
					} else {
						var iAmount = aLine['factor_time'] * 1;

						sCode += '<td>';
							sCode += '<input class="txt w100 billingMoney" id="m_' + aLine['ticket_id'] + '" name="save['+sKey+'][price]" value="' + iAmount.number_format(2,',','.') + '" onblur="recalculateBillings();" /> €';
						sCode += '</td>';
						sCode += '<td id="m_' + aLine['ticket_id'] + '_total" style="text-align:right;">' + iAmount.number_format(2,',','.') + '</td>';
					}

				sCode += '<tr>';
			}

			sCode += '<tr>';
				sCode += '<th colspan="4">Gesamt:</th>';
				sCode += '<th style="text-align:right;" id="total_times">' + iTotalTimes.number_format(2,',','.') + ' h</th>';
				sCode += '<th>&nbsp;</th>';
				sCode += '<th style="text-align:right;" id="total_money">' + iTotalTotal.number_format(2,',','.') + ' €</th>';
			sCode += '</tr>';

		sCode += '</table>';

		sCode += '<div style="text-align:right;"><button onclick="generateBillingsAccount(); return false;" id="billingsButton" class="btn" style="opacity:1; filter:alpha(opacity=100);">Rechnung generieren</button></div>';
	sCode += '</form>';

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	oDialogBox = new LITBox(sCode, {type: 'alert', overlay: true, height: 600, width: 1000, resizable: false, opacity: .9});

	recalculateBillings();

	$('toolbar_loading').hide();

}

/* =============================================================================================================== */

function generateBillingsAccount() {
	
	$('toolbar_loading').show();
	
	var sParams = 'task=generate_billing_account';

	sParams += '&customer_id=' + iBillingsCustomerID;

	sParams += '&' + $('formBillings').serialize();

	var oAjax = new Ajax.Request(
			'/admin/extensions/office.ajax.php',
			{
				method:		'post',
				parameters:	sParams,
				onComplete: generateBillingsAccountCallback
			}
	);
}

function generateBillingsAccountCallback(oResponse) {

	var oData = oResponse.responseText.evalJSON();

	if(oData['data'] == true) {
		alert('Die Rechnung wurde erfolgreich erstellt.');
		oDialogBox.remove();
	} else {
		alert('Es ist ein Fehler aufgetreten!');
	}
	
	$('toolbar_loading').hide();
	
}

/* =============================================================================================================== */

function recalculateBillings()
{
	var iTotal = 0;

	var aTimes = $A($$('.billingTimes'));
	var aMoney = $A($$('.billingMoney'));

	aTimes.each(function(oInput)
	{
		var iHourPrice = parseFloat($(oInput.id + '_price').value);

		var iAmount = getFloat(oInput.value);

		var iLineTotal = iHourPrice * iAmount;

		$(oInput.id + '_total').innerHTML = iLineTotal.number_format(2,',','.') + ' €';

		iTotal += iLineTotal;
	});

	aMoney.each(function(oInput)
	{
		var iPrice = getFloat(oInput.value);

		$(oInput.id + '_total').innerHTML = iPrice.number_format(2,',','.') + ' €';

		iTotal += iPrice;
	});

	$('total_money').innerHTML = iTotal.number_format(2,',','.') + ' €';
}

function getFloat(mValue)
{
	mValue = mValue.replace(/\./g, '').replace(/,/, '.');

	mValue = parseFloat(mValue);

	if(mValue == 'NaN')
	{
		return 0;
	}

	return mValue;
}

function addLogoPreviewDeleteEvent() {
	var divPictureContainer = getLogoContainer();
	var deleteCustomerLogo = divPictureContainer.firstElementChild;

	if (deleteCustomerLogo !== null) {
		deleteCustomerLogo.addEventListener('click', function() {
			var hiddenInput = getHiddenInputDeleteLogo();
			hiddenInput.value = true;
			clearLogoPreview();
		}, false);
	}
}

function updateLogoPreview(sPath) {
	clearLogoPreview();

	var fileInput = document.getElementById('logo');
	fileInput.value = null;

	var hiddenInput = getHiddenInputDeleteLogo();
	hiddenInput.value = false;

	var divPictureContainer = getLogoContainer();
	var sImg = getLogoPreviewImage(sPath);
	var aImgs = sImg.split('<img');
	aImgs.shift();
	aImgs[0] = '<img' + aImgs[0];
	aImgs[1] = '<img' + aImgs[1];
	divPictureContainer.innerHTML += aImgs[0];
	divPictureContainer.innerHTML += aImgs[1];
	addLogoPreviewDeleteEvent();
}

function getLogoContainer() {
	return document.querySelector('.logo-container');
}

function getHiddenInputDeleteLogo() {
	var hiddenInput = document.getElementById('delete_logo');
	return hiddenInput;
}

function clearLogoPreview() {
	var divLogoPreview = getLogoContainer();
	while (divLogoPreview.firstChild) {
		divLogoPreview.removeChild(divLogoPreview.firstChild);
	}
}

function getLogoPreviewImage(sPath){
	var oGUI = new GUI;
	var sImg = oGUI.printFormPicture('logo', sPath);
	return sImg;
}

function editLocation(){
	console.debug($('.selectedRow'));
}

function preOpenLocationDialog(sModus)
{
	if(sModus === 'edit')
	{
		if(selectedLocationRow === 0)
		{
			alert('Bitte wählen Sie den zu bearbeitenden Standort aus.');
			return;
		}

		for(var i = 0; i < aLocations.length; i++)
		{
			if(aLocations[i]['id'] === selectedLocationRow.replace(/locations_/, ''))
			{
				selectedLocationRow = 0;
				createLocationsList(aLocations);
				openLocationDialog(aLocations[i]['id']);
				return;
			}
		}
	}
	else if(sModus === 'delete')
	{
		if(selectedLocationRow === 0)
		{
			alert('Bitte wählen Sie den zu löschenden Standort aus.');
			return;
		}
		deleteLocation(selectedLocationRow.replace(/locations_/, ''));
		return;
	}

	return aLocations;
}

/**
 * Öffnet den Standort-Dialog
 */
function openLocationDialog(iLocationId) {
	if(this['id'] === undefined){
		this['id'] = -1;
	}
		
	if(iLocationId !== undefined && !isNaN(iLocationId)) {
		this['id'] = parseInt(iLocationId);
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_location&location_id=' + this['id'] + '&customer_id=' + selectedRow.replace(/tr_/, '');

	var objAjax = new Ajax.Request(
			strRequestUrl, {
		method: 'post',
		parameters: strParameters,
		onComplete: openLocationDialogCallback
	});
}

function openLocationDialogCallback(oResponse) {

	var oData = oResponse.responseText.evalJSON();
	var aData = oData['data'];
	var aLocation = aData['aLocation'];
	var aCountries = aData['aCountries'];
	var aCustomerGroups = aData['aCustomerGroups'];

	// Diese If-Anweisung ist notwendig, damit beim Erstellen eines neuen Standortes
	// die Felder nicht mit "null" ausgefüllt sind
	if (aLocation['created'] === null) {
		aLocation = Array();
		aLocation['id'] = '';
		aLocation['visible'] = '';
		aLocation['anonymous'] = '';
		aLocation['name'] = '';
		aLocation['address'] = '';
		aLocation['addition'] = '';
		aLocation['zip'] = '';
		aLocation['city'] = '';
		aLocation['country'] = '';
		aLocation['customer_group_id'] = '';
	}

	var objGUI = new GUI;

	var strCode = '';
	// Open main container
	strCode += '<form id="location_form" onsubmit="return false;"><div id="main_container">';

	// Anschrift
	strCode += objGUI.startCol('');
	strCode += objGUI.startFieldset('Anschrift', 'style="height: auto;"');
	strCode += objGUI.printFormHidden('customer_id', selectedRow.replace(/tr_/, ''));
	strCode += objGUI.printFormHidden('location_id', aLocation['id']);
	strCode += objGUI.printFormInput('Name', 'name', aLocation['name']);
	strCode += objGUI.printFormInput('Adresse', 'address', aLocation['address']);
	strCode += objGUI.printFormInput('Zusatz', 'addition', aLocation['addition']);
	strCode += objGUI.printFormInput('PLZ', 'zip', aLocation['zip'], 'onkeyup="fillGEO(\'zip\', \'city\')"');
	strCode += objGUI.printFormInput('Ort', 'city', aLocation['city'], 'onkeyup="fillGEO(\'city\', \'zip\')"');
	strCode += '<div style="position:relative; white-space:nowrap;">';
	strCode += '<div onclick="this.hide();" style="height:80px; overflow-y:scroll; padding:3px; display:none; background-color:#FFC; position:absolute; margin-left:153px; border: 1px solid #000; z-index:100000;" id="geo_list"></div>';
	strCode += '</div>';
	strCode += objGUI.printFormSelect('Land', 'country', aCountries, aLocation['country'], 'style="width:200px;"');
	strCode += objGUI.endFieldset();
	strCode += objGUI.endCol();

	// Zusätzliches
	strCode += objGUI.startCol('');
	strCode += objGUI.startFieldset('Zusätzliches', 'style="height: auto;"');
	strCode += objGUI.printFormCheckbox('Auf der Karte sichtbar', 'visible', aLocation['visible'], 'width: 150px');
	strCode += objGUI.printFormCheckbox('Anonymous', 'anonymous', aLocation['anonymous'], 'width: 150px');
	strCode += objGUI.printFormSelect('Kundengruppe', 'customer_group_id', aCustomerGroups, aLocation['customer_group_id'], 'style="width:200px;"');
	strCode += objGUI.endFieldset();
	strCode += objGUI.endCol();

	// Bild-Upload - Kundenlogo
	strCode += objGUI.startCol('');
	strCode += objGUI.startFieldset('Standortlogo', 'style="height: auto;"');
	strCode += objGUI.printFormUpload('Bilddatei', 'logo');
	strCode += objGUI.printFormHidden('delete_logo', false);
	strCode += '<div class="logo-container">';
	strCode += getLogoPreviewImage(aLocation['logo']);
	strCode += '</div>';
	strCode += objGUI.endCol();

	// Save document button
	strCode += objGUI.printFormButton('Daten speichern', 'saveLocation();return false;', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	
	// Close main container
	strCode += '</div></form>';

	objDialogBox = new LITBox(strCode, {type: 'alert', overlay: true, height: 500, width: 700, resizable: false, opacity: .9});

	addLogoPreviewDeleteEvent();
}

function getLocations(iCustomerId) {
	var sRequestUrl = '/admin/extensions/office.ajax.php';
	var sParameters = 'task=get_locations&customer_id=' + iCustomerId;

	var oAjax = new Ajax.Request(
			sRequestUrl, {
		method: 'post',
		parameters: sParameters,
		onComplete: getLocationsCallback
	});
}

function getLocationsCallback(oResponse) {
	document.getElementById('locationToolbar').style.display = 'inline';

	var oData = oResponse.responseText.evalJSON();
	var aData = oData['data'];
	
	aLocations = aData['aLocations'];

	// Check the number of loactions
	if (aLocations.length === 0) {
		document.getElementById('noLocation').style.display = 'inline';
		document.getElementById('locationList').style.display = 'none';
		document.getElementById('editLocationToolbar').style.display = 'none';
	}
	else {
		document.getElementById('noLocation').style.display = 'none';
		document.getElementById('locationList').style.display = 'inline';
		document.getElementById('editLocationToolbar').style.display = 'inline';

		// Create the list of contact persons
		createLocationsList(aLocations);
	}
}

function saveLocation() {
	var strRequestUrl = '/admin/extensions/office.ajax.php';

	document.getElementById('main_container').style.cursor = 'wait';
	document.getElementById('save_button').style.cursor = 'wait';

	var oFormData = new FormData($('location_form'));
	oFormData.append('task', 'save_location');

	var oXhr = new XMLHttpRequest();
	oXhr.open('POST', strRequestUrl, true);
	oXhr.onload = saveLocationCallback;
	oXhr.send(oFormData);
}

function saveLocationCallback() {
	var oData = this.responseText.evalJSON();
	var aData = oData['data'];

	sMessage = '';

	if(aData['save_error']) {

		/**
		 * @todo Muss noch berücksichtigt werden.
		 */
		var aErrors = aData['save_error'];

		aErrors.forEach(function(id){
			var input = document.getElementById(id);

			// Eventlistener, damit die Farbe wieder wechselt .... (unsauber :O)
			input.addEventListener('keydown', function(event) {
				var label = this.previousElementSibling;
				label.style.color = 'black';
			}, false);

			var label = input.previousElementSibling;
			label.style.color = 'red';
		});

		sMessage += '<span style="color:red;">';
		sMessage += 'Fehler beim Speichervorgang. Bitte überprüfen Sie die Eingabe.';
		sMessage += '</span>';
	} else {
		sMessage += '<span style="color:green;">';
		sMessage += 'Die Daten wurden erfolgreich gespeichert!';
		sMessage += '</span>';
		
		// Reload the list + display/hide DIVs
		getLocations(aData['customer_id']);

		updateLogoPreview(aData['logo']);
	}

	$('LB_title').update(sMessage);

	document.getElementById('main_container').style.cursor = 'auto';
	document.getElementById('save_button').style.cursor = 'auto';

	document.getElementById('location_id').value = aData['location_id'];

}

function deleteLocation(iLocationId) {
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_location';
	strParameters += '&location_id=' + iLocationId;

	if (confirm('Möchten Sie diesen Standort unwiederruflich löschen?')) {
		var objAjax = new Ajax.Request(
				strRequestUrl, {
			method: 'post',
			parameters: strParameters,
			onComplete: deleteLocationCallback
		});
	}
}

function deleteLocationCallback(objResponse) {
	var oData = objResponse.responseText.evalJSON();
	var aData = oData['data'];
	var iCustomerId = parseInt(aData['customer_id']);

	// Liste neuladen
	getLocations(iCustomerId);

}

function createLocationsList(aLocations) {
	var tbody = document.getElementById('tbl_loactions');
	var c = 0;
	var tr = document.createElement('tr');
	var objTr = tr.cloneNode(true);

	var td0, td1, td2, td3, td4, td5, td6, td7, td8;

	// Remove all locations
	while (tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Reset the selected location row
	selectedLocationRow = 0;

	// Create new location list
	for (var i = 0; i < aLocations.length; i++, c++)
	{
		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		var strId = 'locations_' + aLocations[i]['id'];
		objTr.id = strId;

		Event.observe(objTr, 'click', checkLocationRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', openLocationDialog.bindAsEventListener(aLocations[i]));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		checkbox = document.createElement("input");
		checkbox.type = "checkbox";
		checkbox.name = "visible";
		checkbox.disabled = true;
		if (aLocations[i]['visible'] === '1') {
			checkbox.checked = true;
		} else {
			checkbox.checked = false;
		}
		td0.appendChild(checkbox);

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		checkbox = document.createElement("input");
		checkbox.type = "checkbox";
		checkbox.name = "anonymous";
		checkbox.disabled = true;
		if (aLocations[i]['anonymous'] === '1') {
			checkbox.checked = true;
		} else {
			checkbox.checked = false;
		}
		td1.appendChild(checkbox);

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = (aLocations[i]['name'].length != 0 ? aLocations[i]['name'] : '&nbsp;');

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = (aLocations[i]['address'].length != 0 ? aLocations[i]['address'] : '&nbsp;');

		td4 = document.createElement("td");
		objTr.appendChild(td4);
		td4.innerHTML = (aLocations[i]['addition'].length != 0 ? aLocations[i]['addition'] : '&nbsp;');

		td5 = document.createElement("td");
		objTr.appendChild(td5);
		td5.innerHTML = (aLocations[i]['zip'].length != 0 ? aLocations[i]['zip'] : '&nbsp;');

		td6 = document.createElement("td");
		objTr.appendChild(td6);
		td6.innerHTML = (aLocations[i]['city'].length != 0 ? aLocations[i]['city'] : '&nbsp;');

		td7 = document.createElement("td");
		objTr.appendChild(td7);
		td7.innerHTML = (aLocations[i]['country'].length != 0 ? aLocations[i]['country'] : '&nbsp;');

		td8 = document.createElement("td");
		objTr.appendChild(td8);
		td8.innerHTML = (aLocations[i]['customer_group_name'].length != 0 ? aLocations[i]['customer_group_name'] : '&nbsp;');

		td0 = td1 = td2 = td3 = td4 = td5 = td6 = td7 = td8 = null;
	}
	tbody = null;
}

function preOpenCommentDialog(sModus)
{
	if(sModus === 'edit')
	{
		if(selectedCommentRow === 0)
		{
			alert('Bitte wählen Sie den zu bearbeitenden Kommentar aus.');
			return;
		}

		for(var i = 0; i < aComments.length; i++)
		{
			if(aComments[i]['id'] === selectedCommentRow.replace(/comments_/, ''))
			{
				selectedCommentRow = 0;
				createCommentsList(aComments);
				openCommentDialog(aComments[i]['id']);
				return;
			}
		}
	}
	else if(sModus === 'delete')
	{
		if(selectedCommentRow === 0)
		{
			alert('Bitte wählen Sie den zu löschenden Kommentar aus.');
			return;
		}
		deleteComment(selectedCommentRow.replace(/comments_/, ''));
		return;
	}

	return aComments;
}

function openCommentDialog(iCommentId) {
	if(this['id'] === undefined){
		this['id'] = -1;
	}
		
	if(iCommentId !== undefined && !isNaN(iCommentId)) {
		this['id'] = parseInt(iCommentId);
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_comment&comment_id=' + this['id'] + '&customer_id=' + selectedRow.replace(/tr_/, '');

	var objAjax = new Ajax.Request(
			strRequestUrl, {
		method: 'post',
		parameters: strParameters,
		onComplete: openCommentDialogCallback
	});
}

function openCommentDialogCallback(oResponse) {

	var oData = oResponse.responseText.evalJSON();
	var aData = oData['data'];
	var aComment = aData['aComment'];
	var aCustomerGroups = aData['aCustomerGroups'];

	var objGUI = new GUI;

	var strCode = '';
	// Open main container
	strCode += '<form id="comment_form" onsubmit="return false;"><div id="main_container">';

	// Kommentar
	strCode += objGUI.startCol('');
	strCode += objGUI.startFieldset('Kommentar', 'style="height: auto; width: 440px"');
	strCode += objGUI.printFormHidden('customer_id', selectedRow.replace(/tr_/, ''));
	strCode += objGUI.printFormHidden('comment_id', aComment['id']);
	strCode += objGUI.printFormCheckbox('Sichtbar', 'visible', aComment['visible']);
	strCode += objGUI.printFormCheckbox('Zufallsbox', 'box', aComment['box']);
	strCode += objGUI.printFormInput('Position', 'position', aComment['position'], 'style="width:30px;"');
	strCode += objGUI.printFormSelect('Kundengruppe', 'customer_group_id', aCustomerGroups, aComment['customer_group_id'], 'style="width:200px;"');
	strCode += objGUI.printFormTextarea('Kommentar', 'text', aComment['text'], 15, 22);
	strCode += objGUI.endFieldset();
	strCode += objGUI.endCol();

	// Save document button
	strCode += objGUI.printFormButton('Daten speichern', 'saveComment();return false;', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	
	// Close main container
	strCode += '</div></form>';

	objDialogBox = new LITBox(strCode, {type: 'alert', overlay: true, height: 420, width: 470, resizable: false, opacity: .9});
}

function getComments(iCustomerId) {
	var sRequestUrl = '/admin/extensions/office.ajax.php';
	var sParameters = 'task=get_comments&customer_id=' + iCustomerId;

	var oAjax = new Ajax.Request(
			sRequestUrl, {
		method: 'post',
		parameters: sParameters,
		onComplete: getCommentsCallback
	});
}

function getCommentsCallback(oResponse) {
	document.getElementById('commentToolbar').style.display = 'inline';

	var oData = oResponse.responseText.evalJSON();
	var aData = oData['data'];
	
	aComments = aData['aComments'];

	// Check the number of comments
	if (aComments.length === 0) {
		document.getElementById('noComment').style.display = 'inline';
		document.getElementById('commentList').style.display = 'none';
		document.getElementById('editCommentToolbar').style.display = 'none';
	}
	else {
		document.getElementById('noComment').style.display = 'none';
		document.getElementById('commentList').style.display = 'inline';
		document.getElementById('editCommentToolbar').style.display = 'inline';

		// Create the list of contact persons
		createCommentsList(aComments);
	}
}

function saveComment() {
	var strRequestUrl = '/admin/extensions/office.ajax.php';

	document.getElementById('main_container').style.cursor = 'wait';
	document.getElementById('save_button').style.cursor = 'wait';

	var oFormData = new FormData($('comment_form'));
	oFormData.append('task', 'save_comment');

	var oXhr = new XMLHttpRequest();
	oXhr.open('POST', strRequestUrl, true);
	oXhr.onload = saveCommentCallback;
	oXhr.send(oFormData);
}

function saveCommentCallback() {
	var oData = this.responseText.evalJSON();
	var aData = oData['data'];

	sMessage = '';

	if(aData['save_error']) {

		/**
		 * @todo Muss noch berücksichtigt werden.
		 */
		var aErrors = aData['save_error'];

		aErrors.forEach(function(id){
			var input = document.getElementById(id);

			// Eventlistener, damit die Farbe wieder wechselt .... (unsauber :O)
			input.addEventListener('keydown', function(event) {
				var label = this.previousElementSibling;
				label.style.color = 'black';
			}, false);

			var label = input.previousElementSibling;
			label.style.color = 'red';
		});

		sMessage += '<span style="color:red;">';
		sMessage += 'Fehler beim Speichervorgang. Bitte überprüfen Sie die Eingabe.';
		sMessage += '</span>';
	} else {
		sMessage += '<span style="color:green;">';
		sMessage += 'Die Daten wurden erfolgreich gespeichert!';
		sMessage += '</span>';
		
		// Reload the list + display/hide DIVs
		getComments(aData['customer_id']);
	}

	$('LB_title').update(sMessage);

	document.getElementById('main_container').style.cursor = 'auto';
	document.getElementById('save_button').style.cursor = 'auto';

	document.getElementById('comment_id').value = aData['comment_id'];

}

function deleteComment(iCommentId) {
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_comment';
	strParameters += '&comment_id=' + iCommentId;

	if (confirm('Möchten Sie diesen Standort unwiederruflich löschen?')) {
		var objAjax = new Ajax.Request(
				strRequestUrl, {
			method: 'post',
			parameters: strParameters,
			onComplete: deleteCommentCallback
		});
	}
}

function deleteCommentCallback(objResponse) {
	var oData = objResponse.responseText.evalJSON();
	var aData = oData['data'];
	var iCustomerId = parseInt(aData['customer_id']);

	// Liste neuladen
	getComments(iCustomerId);

}

function createCommentsList(aComments) {
	var tbody = document.getElementById('tbl_comments');
	var c = 0;
	var tr = document.createElement('tr');
	var objTr = tr.cloneNode(true);

	var td0, td1, td2, td3, td4;

	// Remove all comments
	while (tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Reset the selected comment row
	selectedCommentRow = 0;

	// Create new comment list
	for (var i = 0; i < aComments.length; i++, c++)
	{
		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		var strId = 'comments_' + aComments[i]['id'];
		objTr.id = strId;

		Event.observe(objTr, 'click', checkCommentRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', openCommentDialog.bindAsEventListener(aComments[i]));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		// visible
		td0 = document.createElement("td");
		objTr.appendChild(td0);
		checkbox = document.createElement("input");
		checkbox.type = "checkbox";
		checkbox.name = "visible";
		checkbox.disabled = true;
		if (aComments[i]['visible'] === '1') {
			checkbox.checked = true;
		} else {
			checkbox.checked = false;
		}
		td0.appendChild(checkbox);

		// box
		td1 = document.createElement("td");
		objTr.appendChild(td1);
		checkbox = document.createElement("input");
		checkbox.type = "checkbox";
		checkbox.name = "box";
		checkbox.disabled = true;
		if (aComments[i]['box'] === '1') {
			checkbox.checked = true;
		} else {
			checkbox.checked = false;
		}
		td1.appendChild(checkbox);

		// Text
		td2 = document.createElement("td");
		objTr.appendChild(td2);
		var maxPreviewLength = 90;
		// Nur 80 Zeichen anzeigen
		if(aComments[i]['text'].length >= maxPreviewLength ){
			td2.innerHTML = aComments[i]['text'].substr(0, maxPreviewLength) + " [..]";
		} else {
			td2.innerHTML = (aComments[i]['text'].length != 0 ? aComments[i]['text'] : '&nbsp;');		
		}

		// Position
		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = (aComments[i]['position'].length != 0 ? aComments[i]['position'] : '&nbsp;');

		// Kundengruppe
		td4 = document.createElement("td");
		objTr.appendChild(td4);
		td4.innerHTML = (aComments[i]['customer_group_name'].length != 0 ? aComments[i]['customer_group_name'] : '&nbsp;');

		td0 = td1 = td2 = td3 = td4 = null;
	}
	tbody = null;
}