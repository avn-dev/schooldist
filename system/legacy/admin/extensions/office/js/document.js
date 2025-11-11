 
// Globals

var objDialogBox;
var documentType		= 'letter';

var documentID			= 0;

var iPriceMultiplicator	= 1;

var iAccStartsCounter	= 0;

var aTextBlocks			= new Array();

var aPayments			= new Array();

var aPositions			= new Array();

var aReceivables		= new Array();

var aReminders			= new Array();
var aCustomers			= new Array();

var aEditors			= new Array();
var aContacts			= new Array();
var aSettlementList		= new Array();

var sErrorMessage		= 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal.';

// Customer specified payment conditions
var iCustPayInvoice		= '';
var iCustPayMisc		= '';

/* ==================================================================================================== */ // Check lists

function openCheckListDialog(iDocumentID)
{
	documentID = iDocumentID;

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=load_checklist&document_id='+iDocumentID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openCheckListDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function openCheckListDialogCallback(oResponse)
{
	var oData = oResponse.responseText.evalJSON();

	var aCheckList = oData['data']['aCheckList'];

	var aColors = new Array('#F5F5F5', '#FAFAFA');

	var sCode = '';

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	sCode += '<div style="margin:10px;">';

		for(var i = 0; i < aCheckList.length; i++)
		{
			sCode += '<div style="margin-bottom:5px; padding:10px; background-color:' + aColors[(i % 2)] + '">';
				sCode += '<div><b>' + aCheckList[i]['product'] + '</b></div>';

				sCode += '<div style="float:left;">';
					sCode += '<textarea class="txt" style="width:600px; height:50px;" id="new_checklist_' + aCheckList[i]['id'] + '"></textarea>';
				sCode += '</div>';
				sCode += '<div style="float:right;">';
					sCode += '<input type="button" class="btn" value="+" style="opacity:1; filter:alpha(opacity=100); margin:0; height:50px; width:30px;" onclick="saveCheckListPosition(' + aCheckList[i]['id'] + ', 0);" />';
				sCode += '</div>';

				sCode += '<div style="clear:both;"></div>';

				sCode += '<div style="" id="checks_container_' + aCheckList[i]['id'] + '">';

					for(var n = 0; n < aCheckList[i]['checks'].length; n++)
					{
						aEntry = aCheckList[i]['checks'][n];

						sCode += '<div id="checklist_' + aEntry['position_id'] + '_' + aEntry['id'] + '">';
							sCode += '<textarea class="txt" style="float:left; width:600px; height:50px;" id="checklist_entry_' + aEntry['position_id'] + '_' + aEntry['id'] + '">' + aEntry['text'] + '</textarea>';

							sCode += '<div style="float:right; margin-right:7px; margin-top:8px;">';
								sCode += '<img style="cursor:pointer; display:block;" onclick="saveCheckListPosition(' + aEntry['position_id'] + ', ' + aEntry['id'] + ');" src="/admin/media/accept.png" />';
								sCode += '<img style="cursor:pointer; display:block; margin-top:5px;" onclick="removeCheckListPosition(' + aEntry['position_id'] + ', ' + aEntry['id'] + ');" src="/admin/media/cross.png" />';
							sCode += '</div>';

							sCode += '<div style="clear:both;"></div>';
						sCode += '</div>';
					}

				sCode += '</div>';

			sCode += '</div>';
		}

		sCode += '<div style="text-align:right;">';
			sCode += '<input type="button" class="btn" value="PDF anzeigen" style="opacity:1; filter:alpha(opacity=100);" onclick="showCheckListPDF();" />';
		sCode += '</div>';

	sCode += '</div>';

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Display LitBox

	objDialogBox = new LITBox(sCode, {type:'alert', overlay:true, height:500, width:700, resizable:false, opacity:.9});
}

/* ==================================================================================================== */

function showCheckListPDF()
{
	window.open('/admin/extensions/office/office.php?action=show_checklist_pdf&document_id=' + documentID);
}

function saveCheckListPosition(iPositionID, iEntryID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';

	var strParameters = 'task=save_checklist';

	strParameters += '&position_id=' + iPositionID;
	strParameters += '&entry_id=' + iEntryID;

	if(iEntryID == 0)
	{
		strParameters += '&text=' + encodeURIComponent($('new_checklist_' + iPositionID).value);

		$('new_checklist_' + iPositionID).value = '';
	}
	else
	{
		strParameters += '&text=' + encodeURIComponent($('checklist_entry_' + iPositionID + '_' + iEntryID).value);
	}

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: saveCheckListPositionCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function saveCheckListPositionCallback(oResponse)
{
	var oData = oResponse.responseText.evalJSON();

	var aEntry = oData['data']['aEntry'];

	var sCode = '';

	if(!$('checklist_' + aEntry['position_id'] + '_' + aEntry['id']))
	{
		sCode += '<div id="checklist_' + aEntry['position_id'] + '_' + aEntry['id'] + '">';
			sCode += '<textarea class="txt" style="float:left; width:600px; height:50px;" id="checklist_entry_' + aEntry['position_id'] + '_' + aEntry['id'] + '">' + aEntry['text'] + '</textarea>';

			sCode += '<div style="float:right; margin-right:7px; margin-top:8px;">';
				sCode += '<img style="cursor:pointer; display:block;" onclick="saveCheckListPosition(' + aEntry['position_id'] + ', ' + aEntry['id'] + ');" src="/admin/media/accept.png" />';
				sCode += '<img style="cursor:pointer; display:block; margin-top:5px;" onclick="removeCheckListPosition(' + aEntry['position_id'] + ', ' + aEntry['id'] + ');" src="/admin/media/cross.png" />';
			sCode += '</div>';

			sCode += '<div style="clear:both;"></div>';
		sCode += '</div>';

		$('checks_container_' + aEntry['position_id']).insert({bottom: sCode});
	}
}

function removeCheckListPosition(iPositionID, iEntryID)
{
	if(!confirm('Möchten Sie diese Position wirklich entfernen?'))
	{
		return;
	}

	$('checklist_' + iPositionID + '_' + iEntryID).remove();

	var strRequestUrl = '/admin/extensions/office.ajax.php';

	var strParameters = 'task=remove_checklist';

	strParameters += '&entry_id=' + iEntryID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ==================================================================================================== */ // Requests

function openDocumentDialog(iDocumentID)
{
	documentID = iDocumentID;

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=prepare_document&document_id='+iDocumentID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openDocumentDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function openTicketDialog(iDocumentID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=prepare_ticket&document_id='+iDocumentID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openTicketDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function saveTicket()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_ticket';

	document.getElementById('main_container').style.cursor	= 'wait';
	document.getElementById('save_button').style.cursor		= 'wait';

	// Document settings >>>
	strParameters += '&customer_id='		+ parseInt(document.getElementById('tick_customer_id').value);
	strParameters += '&document_id='		+ parseInt(document.getElementById('tick_document_id').value);
	strParameters += '&due_date='			+ encodeURIComponent(document.getElementById('tick_due_date').value);
	strParameters += '&priority='			+ parseInt(document.getElementById('tick_priority').value);
	strParameters += '&state='				+ 'new';
	strParameters += '&assigned_user_id='	+ parseInt(document.getElementById('tick_assigned_user_id').value);
	strParameters += '&headline='			+ encodeURIComponent(document.getElementById('tick_headline').value);
	strParameters += '&description='		+ encodeURIComponent(document.getElementById('tick_description').value);

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : saveTicketCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function saveTicketCallback()
{
	document.getElementById('saving_confirmation').style.display	= 'inline';
	document.getElementById('main_container').style.cursor			= 'auto';
	document.getElementById('save_button').style.cursor				= 'auto';
}

/* ====================================================================== */

function getClientData(oObj)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=getClientData&document_client_id='+oObj.value;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : getClientDataCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function getCustomerData(oObj)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=getCustomerData&customer_id='+oObj.value;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : getCustomerDataCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function getArticleData(iArticleID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=getPositionData&article_id='+iArticleID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : getArticleDataCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function managePosition(sTask, sDataJSON) {

	var bError = false;

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=add_position';

	if(sTask == 'edit') {
		strParameters = 'task=edit_position';
		strParameters += '&position_id='	+ iEditPositionID;		
	} else if(sTask == 'delete') {
		var sPositionID = selectedPositionRow;
		if(sPositionID != 0) {
			sPositionID = sPositionID.substr(7);
		}
		strParameters = 'task=delete_position';
		strParameters += '&position_id='	+ sPositionID;
	} else if(sTask == 'pre_edit') {
		var sPositionID = selectedPositionRow;
		if(sPositionID != 0) {
			sPositionID = sPositionID.substr(7);
			for (var i = 0; i < aPositions.length; i++) {
				if(aPositions[i]['id'] == parseInt(sPositionID)) {
					editPosition(0, aPositions[i]);
					break;
				}
			}
			return;
		}
	} else if(sTask == 'sort') {
		strParameters = 'task=sort_positions';
		strParameters += '&sort_array='	+ sDataJSON;

		selectedPositionRow = 0;
	}

	strParameters += '&document_id='	+ documentID;
	strParameters += '&amount='			+ encodeURIComponent(document.getElementById('acc_3_amount').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&number='			+ encodeURIComponent(document.getElementById('acc_3_number').value);
	strParameters += '&product='		+ encodeURIComponent(document.getElementById('acc_3_product').value);
	strParameters += '&description='	+ encodeURIComponent(document.getElementById('acc_3_description').value);
	strParameters += '&price='			+ encodeURIComponent(document.getElementById('acc_3_price').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&discount_item='	+ encodeURIComponent(document.getElementById('acc_3_discountItem').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&unit='			+ encodeURIComponent(document.getElementById('acc_3_unit').value);
	strParameters += '&revenue_account='			+ encodeURIComponent(document.getElementById('acc_3_revenue_account').value);
	strParameters += '&settlement_list_item=' + encodeURIComponent(document.getElementById('acc_3_settlement_list_item').value);
	
	if(document.getElementById('acc_3_only_text').checked) {
		strParameters += '&only_text=1';
	} else {
		strParameters += '&only_text=0';
	}
	if(document.getElementById('acc_3_groupsum').checked) {
		strParameters += '&groupsum=1';
	} else {
		strParameters += '&groupsum=0';
	}
    strParameters += '&group_display=' + encodeURIComponent(document.getElementById('acc_3_group_display').value);
	strParameters += '&vat=' + encodeURIComponent(document.getElementById('acc_3_vat').value) * 100;

	if(parseFloat(document.getElementById('acc_3_discountItem').value.replace(/\./g, '').replace(/,/, '.')) > 100) {
		alert('Der Rabatt darf 100% nicht überschreiten. Bitte korrigieren Sie Ihre Angabe.');
		document.getElementById('acc_3_discountItem').value = '0,00';
		bError = true;
	}
	
	if(
		!sTask || 
		sTask == 'edit'
	) {
		if(
			!document.getElementById('acc_3_only_text').checked &&
			document.getElementById('acc_3_revenue_account').value == 0
		) {
			alert('Bitte wählen Sie ein Erlöskonto!');
			bError = true;
		}
	}

	if(sPositionID == 0 && sTask == 'delete') {
		alert('Bitte markieren Sie die zu löschende Position!');
		bError = true;
	} else if(sPositionID == 0 && sTask == 'pre_edit') {
		alert('Bitte markieren Sie die zu bearbeitende Position!');
		bError = true;
	} else if(
		sTask != 'delete' &&
		sTask != 'sort' &&
		sTask != 'pre_edit'
	) {

		if(!document.getElementById('acc_3_only_text').checked) {

			if(
				isNaN(parseFloat(document.getElementById('acc_3_amount').value)) ||
				document.getElementById('acc_3_amount').value == ''
			) {
				alert('Bitte geben Sie die Anzahl ein!');
				bError = true;
			} else if(
				document.getElementById('acc_3_product').value == ''
			) {
				alert('Bitte geben Sie das Produkt ein!');
				bError = true;
			} else if(
				isNaN(parseFloat(document.getElementById('acc_3_price').value)) ||
				document.getElementById('acc_3_price').value == ''
			) {
				alert('Bitte geben Sie den Preis ein!');
				bError = true;
			}
		}
	}

	if(
		sPositionID != 0
			&&
		sTask == 'delete'
			&&
		bError == false
	) {
		if(confirm('Möchten Sie wirklich die markierte Position löschen?') == false) {
			return;
		} else {
			selectedPositionRow = 0;
		}
	}

	if(bError == false) {
		try {
			var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : managePositionCallback
								}
			);
		} catch(e) {
			alert(sErrorMessage);
		}
	}
}

/* ====================================================================== */

function saveDocument() {

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=saveDocument';

	document.getElementById('main_container').style.cursor	= 'wait';
	document.getElementById('save_button').style.cursor		= 'wait';

	// Document settings >>>
	strParameters += '&document_id='		+ documentID;
	strParameters += '&document_client_id='	+ encodeURIComponent(document.getElementById('acc_1_client_id').value);
	strParameters += '&currency='			+ encodeURIComponent(document.getElementById('acc_1_currency').value);
	strParameters += '&type='				+ encodeURIComponent(document.getElementById('acc_1_type').value);
	strParameters += '&editor='				+ encodeURIComponent(document.getElementById('acc_1_editor').value);
	strParameters += '&date='				+ encodeURIComponent(document.getElementById('acc_1_date').value);
	strParameters += '&booking_date='		+ encodeURIComponent(document.getElementById('acc_1_booking_date').value);
	strParameters += '&subject='			+ encodeURIComponent(document.getElementById('acc_1_subject').value);
	strParameters += '&purchase_order_number=' + encodeURIComponent(document.getElementById('acc_1_purchase_order_number').value);
	strParameters += '&customer='			+ encodeURIComponent(document.getElementById('acc_1_customer').value);
	strParameters += '&contact_person='		+ encodeURIComponent(document.getElementById('acc_1_contactPerson').value);
	strParameters += '&address='			+ encodeURIComponent(document.getElementById('acc_1_address').value);
	strParameters += '&form_id='			+ encodeURIComponent(document.getElementById('acc_1_form_id').value);
	strParameters += '&product_area_id='	+ encodeURIComponent(document.getElementById('acc_1_productarea').value);

	// Document texts >>>
	try
	{
		var oEditorStart	= tinyMCE.get('editor_starttext');
		var oEditorEnd		= tinyMCE.get('editor_endtext');

		strParameters += '&starttext='		+ encodeURIComponent(oEditorStart.getContent());
		strParameters += '&endtext='		+ encodeURIComponent(oEditorEnd.getContent());
	}
	catch(e)
	{
		alert('Beim Laden der Dokumententexte ist ein Fehler aufgetreten.\nBitte versuchen Sie das Dokument noch mal zu speichern.');
	}

	var iPrice = document.getElementById('calculatedMinusDiscount').childNodes[0].nodeValue.replace(/\./g, '').replace(/,/, '.');
	var iPriceNet = document.getElementById('calculatedPlusVat').childNodes[0].nodeValue.replace(/\./g, '').replace(/,/, '.');

	strParameters += '&payment='			+ encodeURIComponent(document.getElementById('acc_5_payment').value);
	strParameters += '&discount='			+ encodeURIComponent(document.getElementById('acc_5_totalDiscount').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&cash_discount='		+ encodeURIComponent(document.getElementById('acc_5_cashDiscount').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&price='				+ encodeURIComponent(iPrice);
	strParameters += '&price_net='			+ encodeURIComponent(iPriceNet);

	// Document payment options (contracts) >>>
	strParameters += '&contract_last='		+ encodeURIComponent(document.getElementById('acc_5_contract_last').value);
	strParameters += '&contract_interval='	+ encodeURIComponent(document.getElementById('acc_5_contract_interval').value);
	strParameters += '&contract_scale='		+ encodeURIComponent(document.getElementById('acc_5_contract_scale').value);

	try
	{
		var objAjax = new Ajax.Request(
			strRequestUrl,
			{
				method : 'post',
				parameters : strParameters,
				onComplete : saveDocumentCallback
			}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function manageReminders(oCheckBox, iReminderID, iFee, iInterest)
{
	var sModus = '';

	// Set or unset a reminder
	if(
		oCheckBox.checked == true
			||
		oCheckBox.checked == false
	)
	{
		if(oCheckBox.checked == true)
		{
			sModus = 'set';
		}
		else
		{
			sModus = 'unset';
		}
	}
	// Edit a reminder
	else
	{
		if(document.getElementById('check_'+iReminderID).checked == true)
		{
			sModus = 'edit';
		}
		if(document.getElementById('check_'+iReminderID).checked == false)
		{
			sModus = '';
		}
	}

	if(sModus != '')
	{
		var strRequestUrl = '/admin/extensions/office.ajax.php';
		var strParameters = 'task=manageReminder';

		// Set properties
		strParameters += '&document_id='	+ escape(documentID);
		strParameters += '&reminder_id='	+ escape(iReminderID);
		strParameters += '&fee='			+ escape(iFee.replace(/\./g, '').replace(/,/, '.'));
		strParameters += '&interest='		+ escape(iInterest.replace(/\./g, '').replace(/,/, '.'));
		strParameters += '&modus='			+ escape(sModus);

		try
		{
			var objAjax = new Ajax.Request(
									strRequestUrl,
									{
										method : 'post',
										parameters : strParameters,
										onComplete : manageRemindersCallback
									}
			);
		}
		catch(e)
		{
			alert(sErrorMessage);
		}
	}
}

/* ====================================================================== */

function deleteDocument(iDocumentId) {

	if(confirm('Möchten Sie dieses Dokument wirklich löschen?')) {
		
		var sParameters = 'document_id='+iDocumentId;
	
		new Ajax.Request(
			'/wdmvc/office/document/delete',
			{
				method : 'post',
				parameters : sParameters,
				onComplete : loadDocumentList
			}
		);
		
	}

}

function openCopyDialog(iDocumentID) {

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_document_data&document_id=' + iDocumentID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openCopyDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function saveCopy(iDocumentID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_document_copy&document_id=' + iDocumentID;

	document.getElementById('main_container').style.cursor	= 'wait';
	document.getElementById('save_button').style.cursor		= 'wait';

	var sCheckbox = 0;
	if($('copy_checkbox').checked == true)
	{
		sCheckbox = 1;
	}

	strParameters += '&checkbox='			+ sCheckbox;
	strParameters += '&procent='			+ encodeURIComponent(document.getElementById('copy_procent').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&new_type='			+ $('copy_type').value;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: saveCopyCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ==================================================================================================== */ // Callback functions

function openDocumentDialogCallback(objResponse)
{

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];
	var objGUI = new GUI;
	var strCode = '';

	documentID = arrData['id'];
	iAccStartsCounter = 0;

	// Global properties
	documentType	= arrData['selectedType'];
	aTextBlocks		= arrData['aTextBlocks'];
	aPayments		= arrData['aPayments'];
	aPositions		= arrData['aPositions'];
	aReceivables	= arrData['aReceivables'];
	aReminders		= arrData['aReminders'];
	aCustomers		= arrData['aCustomers'];

	aContacts = arrData['aContacts'];
	aEditors = arrData['aEditors'];

	// Open main container
	strCode += '<div id="main_container" style="padding: 15px;" onclick="document.getElementById(\'saving_confirmation\').style.display = \'none\'">';

	// Saving Confirmation
	strCode += '<div id="saving_confirmation" style="display:none; color:#008000;">';
		strCode += '<div style="text-align:center; padding-top:10px;">';
			strCode += '<b>Das Dokument wurde erfolgreich gespeichert!</b>';
		strCode += '</div>';
	strCode += '</div>';

	strCode += '<div id="vat_hint" class="GUIDialogNotification GUIInfo" '+((arrData['selectedCustomerVatCheck'])?'style="display: none;"':'')+'>';
	strCode += '<div class="GUITitle">';
	strCode += '<img src="/admin/extensions/gui2/information.png">';
	strCode += '<span>Umsatzsteuer-Hinweis</span>';
	strCode += '</div>';
	strCode += '<div>Diese Rechnung / das Angebot muss ohne Umsatzsteuer gestellt werden!</div>';
	strCode += '</div>';

	strCode += objGUI.printFormHidden('acc_3_settlement_list_item', '0');

	// Open accordions container
	strCode += objGUI.startAccordionContainer('');

	// Document settings accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Dokumenteinstellungen');
		strCode += objGUI.startFieldset('Einstellungen');
			strCode += objGUI.startRow();
			strCode += objGUI.startCol('');
				strCode += objGUI.printFormSelect('Dokumentart', 'acc_1_type', arrData['aTypes'], arrData['selectedType'], 'onchange="documentType = this.value; displayAccordions();"');
				strCode += objGUI.printFormSelect('Bearbeiter', 'acc_1_editor', arrData['aEditors'], arrData['selectedEditor'], '');
				strCode += objGUI.printFormInput('Datum', 'acc_1_date', arrData['sDate'], 'onblur="setPaymentText(document.getElementById(\'acc_5_payment\').value);" ');
				strCode += objGUI.printFormInput('Betreff', 'acc_1_subject', arrData['sSubject']);
				strCode += objGUI.printFormSelect('Währung', 'acc_1_currency', arrData['aCurrencys'], arrData['selectedCurrency'], '');
				strCode += objGUI.printFormSelect('Produktbereich', 'acc_1_productarea', arrData['aProductAreas'], arrData['product_area_id'], '');
				strCode += objGUI.printFormSelect('Ausgabe', 'acc_1_form_id', arrData['aPDFForms'], arrData['form_id'], '');
				strCode += objGUI.printFormInput('Bestellnummer', 'acc_1_purchase_order_number', arrData['sPurchaseOrderNumber']);
			strCode += objGUI.endCol('');
			strCode += objGUI.startCol('');
				strCode += objGUI.printFormSelect('Mandant', 'acc_1_client_id', arrData['aClients'], arrData['selectedClient'], 'onchange="getClientData(this);" ');
				strCode += objGUI.printFormSelect('Kunde', 'acc_1_customer', arrData['aCustomers'], arrData['selectedCustomer'], 'onchange="getCustomerData(this);" ');
				strCode += objGUI.printFormSelect('Kontaktperson', 'acc_1_contactPerson', arrData['aContacts'], arrData['selectedContact'], '');
				strCode += objGUI.printFormTextarea('Anschrift', 'acc_1_address', arrData['sAddress'], 3, 50, 'style="height:76px;"');
			strCode += objGUI.endCol('');
			strCode += objGUI.endRow();
		strCode += objGUI.endFieldset();
		
		strCode += objGUI.startFieldset('Rechnungsdaten', 'id="invoice-data"');
				strCode += objGUI.printFormInput('Buchungsdatum', 'acc_1_booking_date', arrData['sBookingDate'], ' style="width:120px;"');
		strCode += objGUI.endFieldset();

		strCode += objGUI.startFieldset('Texte und Positionen aus anderem Dokument übernehmen', 'id="copy-data"');
			strCode += '<div style="float:left; width:350px;">';
				strCode += objGUI.printFormSelect('Dokumentart', 'acc_1_copy_type', arrData['aTypes'], arrData['selectedType']);
			strCode += '</div>';
			strCode += '<div style="float:left; width:245px;">';
				strCode += objGUI.printFormInput('ID', 'acc_1_copy_id', '', ' style="width:120px;"');
			strCode += '</div>';
			strCode += '<div style="float:left; width:245px;">';
				strCode += objGUI.printFormCheckbox('Positionen leeren?', 'acc_1_truncate_items', '', ' width:120px;');
			strCode += '</div>';
			strCode += '<div style="clear:both; width:200px;">';
				strCode += objGUI.printFormButton('Übernehmen', 'copyContent();', 'copy-btn', 'style="margin: 0;"', 'style="padding:0;text-align: left;"');
			strCode += '</div>';
		strCode += objGUI.endFieldset();

	strCode += objGUI.endAccordion();

	// Document start text accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Dokumenttext');
		strCode += objGUI.startFieldset('Textbaustein einfügen');
			strCode += objGUI.printFormSelect('', 'acc_2_textBlock', aTextBlocks, '', 'onchange="addTextBlock(this, \'starttext\')"');
		strCode += objGUI.endFieldset();
		strCode += '<div style="padding: 0 10px 10px 10px;">';
				strCode += '<textarea class="tinymce-office" id="editor_starttext"></textarea>';
		strCode += '</div>';
	strCode += objGUI.endAccordion();

	// Document positions accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Positionen / Artikel');
		strCode += '<div id="addPosition">';
			strCode += objGUI.startFieldset('Position / Artikel auswählen');
				strCode += objGUI.printFormSelect('', 'acc_3_article', arrData['aArticles'], '', 'onchange="getArticleData(this.value);"');
			strCode += objGUI.endFieldset();
		strCode += '</div>';
		strCode += '<div id="editPosition">';
			strCode += objGUI.startFieldset('Position / Artikel bearbeiten');
				strCode += '<label for="acc_3_only_text" style="margin-right:3px;">Nur Text</label>';
				strCode += '<input type="checkbox" id="acc_3_only_text" name="acc_3_only_text" onclick="togglePositionFields()" value="1" /><br />';
				strCode += '<div id="position_only_text" style="display: none;">';
					strCode += '<label for="acc_3_groupsum" style="margin-right:3px;">Gruppensummen</label>';
					strCode += '<input type="checkbox" id="acc_3_groupsum" name="acc_3_groupsum" onclick="togglePositionFields()" value="1" /><br />';
                    strCode += '<div id="position_groupsum" style="display: none;">';
                        strCode += '<label for="acc_3_group_display" style="margin-right:3px;">Summenanzeige</label>';
                        strCode += '<select id="acc_3_group_display" name="acc_3_group_display">';
                            strCode += '<option value="">Positionen mit Preis anzeigen (Summe an Ende des Dokuments)</option>';
                            strCode += '<option value="only_text_positions">Positionen ohne Preis anzeigen (Summe bei Gruppe)</option>';
                            strCode += '<option value="hide_positions">Positionen nicht anzeigen (Summe bei Gruppe)</option>';
                        strCode += '</select>';
                    strCode += '</div>';
				strCode += '</div>';
				strCode += '<div id="position_details">';
				strCode += '<div style="float:left; width:340px;">';
					strCode += objGUI.printFormInput('Anzahl', 'acc_3_amount', '');
					strCode += objGUI.printFormSelect('Einheit', 'acc_3_unit', arrData['aUnits'], '', '');
					strCode += objGUI.printFormInput('Produkt Nr.', 'acc_3_number', '');
				strCode += '</div>';
				strCode += '<div style="float:right; width:340px;">';
					strCode += objGUI.printFormInput('Preis', 'acc_3_price', '');
					strCode += objGUI.printFormInput('Rabatt (%)', 'acc_3_discountItem', '');
					strCode += objGUI.printFormSelect('Mehrwertsteuer ', 'acc_3_vat', arrData['aVats'], '', '');
					strCode += objGUI.printFormSelect('Erlöskonto ', 'acc_3_revenue_account', arrData['aRevenueAccounts'], '', '');
				strCode += '</div>';
				strCode += '</div>';
				strCode += '<div style="clear:both;"></div>';
				strCode += '<div>';
					strCode += objGUI.printFormInput('Produkt', 'acc_3_product', '', 'style="width:553px;"');
					strCode += objGUI.printFormTextarea('Beschreibung', 'acc_3_description', '', 3, 50, 'style="width:553px; height:100px;"');
				strCode += '</div>';
			strCode += objGUI.endFieldset();
			strCode += '<div id="backToListButton">';
				strCode += objGUI.printFormButton('Zurück zur Position- / Artikelliste', 'cleanPositionFields(1); hidePositionDIVs(\'offAdd_offEdit_onList\');', '', 'style="opacity:1; filter:alpha(opacity=100);"');
			strCode += '</div>';
			strCode += '<div id="positionsAddButton">';
				strCode += objGUI.printFormButton('Position / Artikel hinzufügen', 'managePosition();', '', 'style="opacity:1; filter:alpha(opacity=100);"');
			strCode += '</div>';
			strCode += '<div id="positionsEditSaveButton" style="display:none;">';
				strCode += objGUI.printFormButton('Änderungen speichern', 'managePosition(\'edit\');', '', 'style="opacity:1; filter:alpha(opacity=100);"');
			strCode += '</div>';
		strCode += '</div>';
		strCode += '<div id="positionsList">';
			strCode += '<div id="positionSettings" style="padding:3px; background-color:#f7f7f7; margin-top:5px; border: 1px solid #CCC;">';
				strCode += '<div style="position:relative; top:-3px;">';
					strCode += '<b>Anlegen:</b>';
					strCode += '<img onclick="hidePositionDIVs(\'onAdd_onEdit_offList\');" src="/admin/media/page_new.gif" alt="Anlegen" title="Anlegen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
					strCode += ':: <b>Bearbeitung:</b>';
					strCode += '<img onclick="managePosition(\'pre_edit\');" src="/admin/media/pencil.png" alt="Bearbeiten" title="Bearbeiten" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
					strCode += '<img onclick="managePosition(\'delete\');" src="/admin/media/cross.png" alt="Löschen" title="Löschen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
				strCode += '</div>';
			strCode += '</div>';
			strCode += '<table id="tablePositions" cellpadding="0" cellspacing="0" border="0" class="table" style="width: 100%; margin: 10px 0;">';
				strCode += '<thead>';
					strCode += '<tr>';
						strCode += '<th style="width: 80px;">Menge</th>';
						strCode += '<th style="width: 70px;">Artikel Nr.</th>';
						strCode += '<th style="width: auto;">Produkt</th>';
						strCode += '<th style="width: 80px;">Preis</th>';
						strCode += '<th style="width: 70px;">Rabatt</th>';
						strCode += '<th style="width: 80px;">Gesamt</th>';
						strCode += '<th style="width: 50px;">USt.</th>';
					strCode += '</tr>';
				strCode += '</thead>';
				strCode += '<tbody id="tbl_positions"></tbody>';
			strCode += '</table>';
		strCode += '</div>';

		strCode += '<div id="settlementlist_container">';
		strCode += '<h3>Abrechnungsliste</h3>';
			strCode += '<table cellpadding="0" cellspacing="0" border="0" class="table" style="width: 100%; margin: 10px 0;">';
				strCode += '<thead>';
					strCode += '<tr>';
						strCode += '<th style="width: 16px;">&nbsp;</th>';
						strCode += '<th style="width: auto;">Produkt</th>';
						strCode += '<th style="width: 120px;">Ansprechpartner</th>';
						strCode += '<th style="width: 120px;">Bearbeiter</th>';
					strCode += '</tr>';
				strCode += '</thead>';
				strCode += '<tbody id="settlementlist_items"></tbody>';
			strCode += '</table>';
		strCode += '</div>';
	strCode += objGUI.endAccordion();

	// Document receivables accordion
	/* ================================================== */

	strCode += objGUI.startAccordion('Fällige Rechnungen');
		strCode += '<div id="receivablesNoCustomer">';
			strCode += '<div style="margin:10px; text-align:center;">';
				strCode += 'Bitte wählen Sie einen Kunden aus.';
			strCode += '</div>';
		strCode += '</div>';
		strCode += '<div id="receivablesNoReceivables">';
			strCode += '<div style="margin:10px; text-align:center;">';
				strCode += 'Es sind keine fällige Rechnungen für diesen Kunden vorhanden.';
			strCode += '</div>';
		strCode += '</div>';
		strCode += '<div id="receivablesList">';
			strCode += '<table id="tableReceivables" cellpadding="0" cellspacing="0" border="0" width="730" class="table" style="margin: 10px 0;">';
				strCode += '<thead>';
					strCode += '<tr>';
						strCode += '<th style="white-space:nowrap;">Datum</th>';
						strCode += '<th style="white-space:nowrap;">Beleg Nr.</th>';
						strCode += '<th style="white-space:nowrap;">Fälligkeit</th>';
						strCode += '<th style="white-space:nowrap;">Betrag / €</th>';
						strCode += '<th style="white-space:nowrap;">Gebühren / €</th>';
						strCode += '<th style="white-space:nowrap;">Zinssatz / %</th>';
						strCode += '<th style="white-space:nowrap;">Zinsen / €</th>';
						strCode += '<th style="white-space:nowrap;">Gesamt / €</th>';
						strCode += '<th style="white-space:nowrap;">Aktiv</th>';
					strCode += '</tr>';
				strCode += '</thead>';
				strCode += '<tbody id="tbl_receivables"></tbody>';
			strCode += '</table>';
		strCode += '</div>';
	strCode += objGUI.endAccordion();

	// Document end text accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Schlusstext');
		strCode += objGUI.startFieldset('Textbaustein einfügen');
			strCode += objGUI.printFormSelect('', 'acc_4_textBlock', aTextBlocks, '', 'onchange="addTextBlock(this, \'endtext\')"');
		strCode += objGUI.endFieldset();
		strCode += '<div style="padding: 0 10px 10px 10px;">';
				strCode += '<textarea class="tinymce-office" id="editor_endtext"></textarea>';
		strCode += '</div>';
	strCode += objGUI.endAccordion();

	// Payment conditions accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Zahlungshinweis');
		strCode += objGUI.startFieldset('Zahlungszeitraum');
			strCode += objGUI.printFormSelect('', 'acc_5_payment', arrData['aPayments'], arrData['selectedPayment'], 'onchange="setPaymentText(this.value);"');
		strCode += objGUI.endFieldset();
		strCode += '<div id="contract_options">';
			strCode += objGUI.startFieldset('Zahlungsoptionen');
				strCode += objGUI.printFormInput('Letzte Fälligkeit', 'acc_5_contract_last', arrData['selectedContractLast']);
				strCode += objGUI.printFormInput('Interval', 'acc_5_contract_interval', arrData['selectedInterval']);
				strCode += objGUI.printFormSelect('Größe', 'acc_5_contract_scale', arrData['aContractScales'], arrData['selectedScale'], '');
			strCode += objGUI.endFieldset();
		strCode += '</div>';
		strCode += '<div id="payment_amount_div">';
			strCode += objGUI.startFieldset('Zahlungsbetrag');
				strCode += '<table id="tablePayments" cellpadding="0" cellspacing="0" border="0" width="100%" class="table sortable scroll" style="table-layout: fixed; margin-top:10px;">';
					strCode += '<thead>';
						strCode += '<tr>';
							strCode += '<th>Endpreis / €</th>';
							strCode += '<th>Skonto / %</th>';
							strCode += '<th>Rabatt / %</th>';
							strCode += '<th>abzgl. Rabatt / €</th>';
							strCode += '<th>zzgl. MwSt / €</th>';
						strCode += '</tr>';
					strCode += '</thead>';
					strCode += '<tbody>';
						strCode += '<tr>';
							strCode += '<td id="calculatedTotalPrice">0,00</td>';
							strCode += '<td>' + objGUI.printFormInput('', 'acc_5_cashDiscount', parseFloat(arrData['iCashDiscount']).number_format(4, ',', '.'), 'onblur="calculatePaymentAmount();" style="width:80px;"') + '</td>';
							strCode += '<td>' + objGUI.printFormInput('', 'acc_5_totalDiscount', parseFloat(arrData['iTotalDiscount']).number_format(4, ',', '.'), 'onblur="calculatePaymentAmount();" style="width:80px;"') + '</td>';
							strCode += '<td id="calculatedMinusDiscount">0,00</td>';
							strCode += '<td id="calculatedPlusVat">0,00</td>';
						strCode += '</tr>';
					strCode += '</tbody>';
				strCode += '</table>';
			strCode += objGUI.endFieldset();
		strCode += '</div>';
		strCode += objGUI.startFieldset('Zahlungsbedingung');
			strCode += '<div id="payment_condition_message">&nbsp;</div>';
		strCode += objGUI.endFieldset();
	strCode += objGUI.endAccordion();

	// Close accordions container
	strCode += objGUI.endAccordionContainer('');

	strCode += '<div class="divCleaner divButton">';
		// Preview document
		strCode += '<span id="privew_button" style="display:none;">';
			strCode += ' <button onclick="previewPDF()" name="preview_button" id="preview_button" class="btn" style="opacity:1; filter:alpha(opacity=100);">Vorschau</button> ';
		strCode += '</span>';

		// Save document button
		strCode += ' <button onclick="saveDocument()" name="save_button" id="save_button" class="btn" style="opacity:1; filter:alpha(opacity=100);">Dokument speichern</button>';
	strCode += '</div>';

	// Close main container
	strCode += '</div>';

	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:750, width:900, resizable:false, opacity:.9, func: 'closeDocument'});
	/* =============================================================================================================== */

	// Checken, ob es noch Instanzen gibt
	var oTinyStart = tinyMCE.get('editor_starttext');
	if(oTinyStart) {
		try {
			oTinyStart.destroy();
			oTinyStart.remove();
		} catch(e) {
		}
	}
	var oTinyEnd = tinyMCE.get('editor_endtext');
	if(oTinyEnd) {
		try {
			oTinyEnd.destroy();
			oTinyEnd.remove();
		} catch(e) {
		}
	}

	var oTinyMceConfig = {
		//selector: "textarea.tinymce-office",
		language: "de",
		mode: "none",
		plugins: [
			"template preview paste code fullscreen searchreplace wordcount visualblocks visualchars"
		],
		menubar: false,
		toolbar: "undo redo | template | searchreplace pastetext visualblocks visualchars | bold italic underline | preview code fullscreen",
		toolbar_items_size: 'small',
		theme_modern_toolbar_location: "top",
		theme_modern_toolbar_align: "left",
		theme_modern_statusbar_location: "none",
		theme_modern_resizing: true,
		theme_modern_path: false,
		readonly: false,
		forced_root_block: false,
		verify_html: false,
		relative_urls: false,
		remove_script_host: true,
		resize: "both",
		templates: "/wdmvc/office/document/templates",
		height: 200
	};

	$('editor_starttext').innerHTML = arrData['sStartText'];
	$('editor_endtext').innerHTML = arrData['sEndText'];
	tinyMCE.EditorManager.createEditor('editor_starttext', oTinyMceConfig).render();
	tinyMCE.EditorManager.createEditor('editor_endtext', oTinyMceConfig).render();

	// Feel, activate or deactivate the select-Tabs
	prepareSelectTabs(arrData);

	// Set payment message (text)
	setPaymentText(arrData['selectedPayment']);

	// Display or hide accordions
	displayAccordions();

	activateAccordions();

	if(documentType == 'account') {
		if(arrData.settlement_list_items) {
			$('settlementlist_container').show();
			showSettlementList(arrData.settlement_list_items);
		} else {
			$('settlementlist_container').hide();
			$('settlementlist_items').update('');
		}
	}

}

function openPayments(iDocumentId) {
	
	sParameters = 'document_id='+iDocumentId;
	
	new Ajax.Request(
		'/wdmvc/office/document/payments',
		{
			method : 'get',
			parameters : sParameters,
			onComplete : openPaymentsCallback
		}
	);
	
}

function openPaymentsCallback(oResponse) {

	var oData = oResponse.responseText.evalJSON();
	
	var oGUI = new GUI;
	
	sHtml = oGUI.startFieldset('Zahlungen');
	sHtml += '<table cellpadding="0" cellspacing="0" border="0" width="100%" class="table sortable scroll" style="table-layout: fixed; margin-top:10px;">';
	sHtml += '<tr><th style="width: 140px;">Zeitpunkt</th><th style="width: 200px;">Benutzer</th><th style="width: 100px;">Betrag</th><th style="width: auto;">Kommentar</th><th>Aktionen</th></tr>';

	oData.payments.payments.each(function(oPayment) {
		sHtml += '<tr><td>'+oPayment.created+'</td><td>'+oPayment.user+'</td><td style="text-align: right;">'+oPayment.amount+'</td><td>'+oPayment.text+'</td><td><a href="javascript:void(0);" class="delete-payment" data-id="'+oPayment.id+'">Löschen</a></td></tr>';
	});
	
	sHtml += '</table>';
	sHtml += oGUI.endFieldset();
	
	objLitBox = new LITBox(sHtml, {type:'alert', overlay:true, height:400, width:800, resizable:false, opacity:.9});

	$j('.delete-payment').click(function() {
		
		if(confirm('Möchten Sie diese Zahlung löschen?')) {
			$j.post('/admin/office/payment/'+$j(this).data('id')+'/delete', function( data ) {
				console.debug(data);
			});
		}
		
	});

}

function copyContent() {
	
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=check_copy_document&document_id='+documentID;
	strParameters += '&document_copy_type=' + encodeURIComponent($('acc_1_copy_type').value);
	strParameters += '&document_copy_id=' + encodeURIComponent($('acc_1_copy_id').value);
	strParameters += '&document_truncate_items=' + encodeURIComponent($('acc_1_truncate_items').value);

	try {
		var objAjax = new Ajax.Request(
			strRequestUrl,
			{
				method : 'post',
				parameters : strParameters,
				onComplete : copyContentCallback
			}
		);
	} catch(e) {
		alert(sErrorMessage);
	}

}

function copyContentCallback(objResponse)
{
	var oData = objResponse.responseText.evalJSON();
	
	if(oData.check == false) {
		alert('Es konnte kein eindeutiges Dokument mit diesen Daten gefunden werden.');
	} else {
		var bConfirm = confirm('Möchten Sie die Texte und Positionen dieses Dokuments wirklich überschreiben? Diesen Vorgang kann man nicht rückgängig machen!');	
		
		if(bConfirm == true) {
			
			var strRequestUrl = '/admin/extensions/office.ajax.php';
			var strParameters = 'task=copy_document&document_id='+documentID;
			strParameters += '&copy_document_id=' + encodeURIComponent(oData.copy_id);
			strParameters += '&document_truncate_items=' + encodeURIComponent(oData.document_truncate_items);

			try {
				var objAjax = new Ajax.Request(
					strRequestUrl,
					{
						method : 'post',
						parameters : strParameters,
						onComplete : executeCopyContentCallback
					}
				);
			} catch(e) {
				alert(sErrorMessage);
			}
			
		}
		
	}

}

function executeCopyContentCallback(objResponse) {
	var oData = objResponse.responseText.evalJSON();

	if(oData.success) {

		// Dokument neu laden
		objDialogBox.remove();
		openDocumentDialog(documentID);
		loadDocumentList();
		
	} else {
		alert('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut!');
	}

}

/* ====================================================================== */

function togglePositionFields() {
	
	var oCheckbox = $('acc_3_only_text');
	
	if(oCheckbox.checked) {
		$('position_details').hide();
		$('position_only_text').show();		
	} else {
		$('position_details').show();
		$('position_only_text').hide();
	}

    oCheckbox = $('acc_3_groupsum');

	if(oCheckbox.checked) {
		$('position_groupsum').show();		
	} else {
		$('position_groupsum').hide();
	}

}

function activateAccordions()
{
	// Start and activate accordions
	var horizontalAccordion = new accordion('divAccordionContainer', {
	    classNames : {
	        toggle : 'accordionTitle',
	        toggleActive : 'accordionTitleActive',
	        content : 'accordionContent'
	    }
	});
	horizontalAccordion.switchContainer($('divAccordionContainer').down(0));
}

/* ====================================================================== */

function previewPDF()
{
	var iFormID = document.getElementById('acc_1_form_id').value;
	window.open('/admin/extensions/office/office.php?template=true&document_id=' + documentID + '&form_id='+iFormID);
}

/* ====================================================================== */

function openTicketDialogCallback(objResponse)
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
			strCode += '<b>Das Ticket wurde erfolgreich angelegt!</b>';
		strCode += '</div>';
	strCode += '</div>';

	strCode += objGUI.startFieldset('Ticket erstellen');
		strCode += '<input type="hidden" id="tick_document_id" value="' + arrData['documentID'] + '" />';
		strCode += objGUI.printFormSelect('Kunde', 'tick_customer_id', arrData['aCustomers'], arrData['selectedCust'], 'style="width:200px;"');
		strCode += objGUI.printFormInput('Fälligkeitsdatum', 'tick_due_date', '', '');
		strCode += objGUI.printFormSelect('Priorität', 'tick_priority', arrData['aPriorities'], '', 'style="width:200px;"');
		strCode += objGUI.printFormSelect('Bearbeiter', 'tick_assigned_user_id', arrData['aUsers'], arrData['selectedUser'], 'style="width:200px;"');
		strCode += objGUI.printFormInput('Titel', 'tick_headline', '', '');
		strCode += objGUI.printFormTextarea('Beschreibung', 'tick_description', '', 3, 50, 'style="width:200px; height:50px;"');
	strCode += objGUI.endFieldset();

	// Save ticket button
	strCode += objGUI.printFormButton('Ticket speichern', 'saveTicket()', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');

	// Close main container
	strCode += '</div>';



	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:300, width:340, resizable:false, opacity:.9});
	/* =============================================================================================================== */
}

/* ====================================================================== */

function getClientDataCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	/* ========== */
	
	var oObj = document.getElementById('acc_1_contactPerson');

	// Remove all options from contact persons
	while(oObj.hasChildNodes())
	{
		oObj.removeChild(oObj.firstChild);
	}

	/* ========== */

	var oObj = document.getElementById('acc_1_customer');

	// Remove all options from customers
	while(oObj.hasChildNodes())
	{
		oObj.removeChild(oObj.firstChild);
	}

	/* ========== */

	// Create new option-Tags on customers
	for(var i = 0; i < arrData['aCustomers'].length; i++)
	{
		var newOption = document.createElement('option');

		// Set option value
		newOption.setAttribute('value', arrData['aCustomers'][i][0]);

		// Set selected value
		if(arrData['aCustomers'].length == 2 && i == 1)
		{
			newOption.setAttribute('selected', 'selected');
		}

		// Set option content
		newOption.innerHTML = arrData['aCustomers'][i][1];

		// Add option to select
		oObj.appendChild(newOption);
	}
}

/* ====================================================================== */

function showSettlementList(aItems) {

	var oContainer = $('settlementlist_items');
	
	oContainer.update();

	aItems.each(function(aItem) {

		aSettlementList[aItem.id] = aItem;

		var sHtml = '';

		var sContact = ''
		var sEditor = ''

		aContacts.each(function(aContact) {
			if(aContact[0] == aItem.customer_contact_id) {
				sContact = aContact[1];
			}
		});
		aEditors.each(function(aEditor) {
			if(aEditor[0] == aItem.editor_id) {
				sEditor = aEditor[1];
			}
		});
	
		sHtml += '<tr>';
		sHtml += '<td><img src="/admin/media/tick.png" id="settlementlist_item_'+aItem.id+'"></td>';
		sHtml += '<td>'+aItem.product+'</td>';
		sHtml += '<td>'+sContact+'</td>';
		sHtml += '<td>'+sEditor+'</td>';
		sHtml += '</tr>';

		oContainer.insert({
			bottom: sHtml
		});

		$('settlementlist_item_'+aItem.id).observe('click', function() {

			var iId = this.id.replace(/settlementlist_item_/, '');

			cleanPositionFields(1);

			$('acc_3_settlement_list_item').value = iId;
			$('acc_3_amount').value = parseFloat(aSettlementList[iId].amount).number_format(2, ',', '.');
			$('acc_3_number').value = '';
			$('acc_3_product').value = aSettlementList[iId].product;
			$('acc_3_description').value = aSettlementList[iId].description;
			$('acc_3_price').value = parseFloat(aSettlementList[iId].price).number_format(2, ',', '.');
			$('acc_3_discountItem').value = parseFloat(aSettlementList[iId].discount_item).number_format(2, ',', '.');

			// Set article unit
			var oObj = $('acc_3_unit');
			for(var i = 0; i < oObj.options.length; i++) {
				if(oObj.options[i].value == aSettlementList[iId].unit) {
					oObj.options[i].selected = 'selected';
					break;
				}
			}

			// Set article vat
			var oObj = $('acc_3_vat');
			for(var i = 0; i < oObj.options.length; i++) {
				if(parseFloat(oObj.options[i].value) == parseFloat(aSettlementList[iId].vat)) {
					oObj.options[i].selected = 'selected';
					break;
				}
			}

			managePosition('save');

			this.up().up().remove();

		});

	});
	
}

function getCustomerDataCallback(objResponse) {

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	aReceivables = arrData['aReceivables'];
	aContacts = arrData['aContacts'];

	if (documentType == 'reminder') {
		createReceivablesList();
	}

	if(
		!arrData.selectedCustomerVatCheck &&
		(
			documentType == 'account' ||
			documentType == 'offer'
		)
	) {
		$('vat_hint').show();
	} else {
		$('vat_hint').hide();
	}

	if(documentType == 'account') {
		if(arrData.settlement_list_items) {
			$('settlementlist_container').show();
			showSettlementList(arrData.settlement_list_items);
		} else {
			$('settlementlist_container').hide();
			$('settlementlist_items').update('');
		}
	}

	var oObj = document.getElementById('acc_1_contactPerson');

	// Remove all options from contact persons
	while(oObj.hasChildNodes()) {
		oObj.removeChild(oObj.firstChild);
	}

	// Create new option-Tags on contact person
	for(var i = 0; i < arrData['aContacts'].length; i++) {
		var newOption = document.createElement('option');

		// Set option value
		newOption.setAttribute('value', arrData['aContacts'][i][0]);

		// Set selected value
		if(
			arrData['selectedContact'] > 0 &&
			arrData['selectedContact'] == arrData['aContacts'][i][0]
		) {
			newOption.setAttribute('selected', 'selected');
		} else if(
			arrData['aContacts'].length == 2 && 
			i == 1
		) {
			newOption.setAttribute('selected', 'selected');
		}

		// Set option content
		newOption.innerHTML = arrData['aContacts'][i][1];

		// Add option to select
		oObj.appendChild(newOption);
	}

	// Update the global variables
	iCustPayInvoice	= arrData['selectedPaymentInvoice'];
	iCustPayMisc	= arrData['selectedPaymentMisc'];

	// Set customer specified payment conditions
	if(documentType == 'offer') {
		setNewPayments(iCustPayMisc);
	} else {
		setNewPayments(iCustPayInvoice);
	}

	// Update payment message (text)
	setPaymentText(document.getElementById('acc_5_payment').value);

	if (arrData['iCustomerID'] <= 0) {
		document.getElementById('acc_1_contactPerson').disabled = true;
	} else {
		document.getElementById('acc_1_contactPerson').disabled = false;
	}

	document.getElementById('acc_1_address').value = arrData['sAddress'];

}

/* ====================================================================== */

function getArticleDataCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	// Set positions list on default value
	var oObj = document.getElementById('acc_3_article');
	for(var i = 0; i < oObj.options.length; i++)
	{
		if(oObj.options[i].value == '')
		{
			oObj.options[i].selected = 'selected';
			break;
		}
	}

	// Set article properties
	document.getElementById('acc_3_amount').value		= 1;
	document.getElementById('acc_3_number').value		= arrData['aArticle']['number'];
	document.getElementById('acc_3_product').value		= arrData['aArticle']['product'];
	document.getElementById('acc_3_price').value		= parseFloat(arrData['aArticle']['price']).number_format(2, ',', '.');
	document.getElementById('acc_3_description').value	= arrData['aArticle']['description'];

	// Set Erlöskonto
	var oObj = document.getElementById('acc_3_revenue_account');
	for(var i = 0; i < oObj.options.length; i++) {
		if(oObj.options[i].value == arrData['aArticle']['revenue_account']) {
			oObj.options[i].selected = 'selected';
			break;
		}
	}

	// Set article unit
	var oObj = document.getElementById('acc_3_unit');
	for(var i = 0; i < oObj.options.length; i++) {
		if(oObj.options[i].value == arrData['aArticle']['unit']) {
			oObj.options[i].selected = 'selected';
			break;
		}
	}

	// Set article vat
	var oObj = document.getElementById('acc_3_vat');
	for(var i = 0; i < oObj.options.length; i++)
	{
		if(oObj.options[i].value == parseFloat(arrData['aArticle']['vat']) / 100)
		{
			oObj.options[i].selected = 'selected';
			break;
		}
	}
}

/* ====================================================================== */

function managePositionCallback(objResponse) {

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	aPositions = arrData['aPositions'];

	cleanPositionFields();

	// Display / hide DIVs with buttons
	document.getElementById('positionsEditSaveButton').style.display	= 'none';
	document.getElementById('positionsAddButton').style.display			= 'inline';

	// Enable 'back to positions list' button
	document.getElementById('backToListButton').style.display = 'inline';

	hidePositionDIVs('offAdd_offEdit_onList');

	if (aPositions.length == 0)
	{
		hidePositionDIVs('onAdd_onEdit_offList');

		// Disable 'back to positions list' button
		document.getElementById('backToListButton').style.display = 'none';
	}

	createPositionsList();

}

/* ====================================================================== */

function manageRemindersCallback(objResponse)
{

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	aReminders = arrData['aReminders'];

}

/* ====================================================================== */

function saveDocumentCallback(objResponse) {

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	document.getElementById('acc_1_type').disabled = 'disabled';

	document.getElementById('privew_button').style.display = 'inline';

	if(
		documentType == 'reminder' && 
		document.getElementById('acc_1_customer').value != ''
	) {
		document.getElementById('acc_1_customer').disabled = 'disabled';
	}

	document.getElementById('saving_confirmation').style.display	= 'inline';
	document.getElementById('main_container').style.cursor			= 'auto';
	document.getElementById('save_button').style.cursor				= 'auto';

	documentID		= arrData['id'];
	aPositions		= arrData['aPositions'];
	aReminders		= arrData['aReminders'];

	loadDocumentList();

	$('copy-data').show();

}

/* ====================================================================== */

function openCopyDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var aDocument = objData['data']['aDocument'];

	var objGUI = new GUI;
	var strCode = '';

	// Open main container
	strCode += '<div id="main_container" onclick="document.getElementById(\'saving_confirmation\').style.display = \'none\'">';

	// Saving Confirmation
	strCode += '<div id="saving_confirmation" style="display:none; color:#008000;">';
		strCode += '<div style="text-align:center; padding-top:10px;">';
			strCode += '<b>Neues Dokument wurde erfolgreich erstellt!</b>';
		strCode += '</div>';
	strCode += '</div>';

	switch(aDocument['type'])
	{
		case 'letter':
		{
			var aNewTypes = new Array(
				new Array('letter', 'neuer Brief'),
				new Array('fax', 'neues Fax')
			);
			break;
		}
		case 'fax':
		{
			var aNewTypes = new Array(
				new Array('fax', 'neues Fax'),
				new Array('letter', 'neuer Brief')
			);
			break;
		}
		case 'offer':
		{
			var aNewTypes = new Array(
				new Array('offer', 'neues Angebot'),
				new Array('confirmation', 'neue Auftragsbestätigung'),
				new Array('account', 'neue Rechnung')
			);
			break;
		}
		case 'account':
		{
			var aNewTypes = new Array(
				new Array('account', 'neue Rechnung'),
				new Array('offer', 'neues Angebot'),
				new Array('credit', 'neue Gutschrift'),
				new Array('cancellation_invoice', 'neue Stornorechnung'),
				new Array('contract', 'neuer Dauerauftrag')
			);
			break;
		}
		case 'contract':
		{
			var aNewTypes = new Array(
				new Array('account', 'neue Rechnung')
			);
			break;
		}
		case 'reminder':
		{
			var aNewTypes = new Array(
				new Array('reminder', '2. Mahnung')
			);
			break;
		}
		default:
		{
			var aNewTypes = new Array();
			break;
		}
	}
	
//	strCode += objGUI.startFieldset();
		
			strCode += objGUI.printFormSelect('Speichern als', 'copy_type', aNewTypes, '', 'style="width:200px; float:right;" onchange="checkCopyFields(\''+aDocument['type']+'\', \''+aDocument['state']+'\', this.value);"');
			strCode += '<br /><hr size="1" />';
			//strCode += objGUI.printFormCheckbox('Abschlagsrechnung erstellen?', 'copy_checkbox', false);
			strCode += '<div class="form-group form-group-sm clearfix">';
			strCode += '<label for="copy_checkbox" class="col-sm-4 control-label">Abschlagsrechnung erstellen?</label> ';
			strCode += '<div class="col-sm-8"><input type="checkbox" id="copy_checkbox" style="float:right;" onchange="checkCopyFields(\''+aDocument['type']+'\', \''+aDocument['state']+'\', document.getElementById(\'copy_type\').value);" /></div>';
			strCode += '</div>';
			strCode += objGUI.printFormInput('Höhe in %', 'copy_procent', '', 'style="float:right;"');
		
//	strCode += objGUI.endFieldset();

	// Save document button
	strCode += objGUI.printFormButton('Dokument speichern', 'saveCopy('+aDocument['id']+');', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');

	// Close main container
	strCode += '</div>';

	objDialogBox = new LITBox(strCode, {title: 'Weiterführungsoptionen', type:'alert', overlay:true, height:300, width:600, resizable:false, opacity:.9});
	/* =============================================================================================================== */

	checkCopyFields(aDocument['type'], aDocument['state'], 0);
}

/* ====================================================================== */

function saveCopyCallback()
{
	document.getElementById('saving_confirmation').style.display	= 'inline';
	document.getElementById('main_container').style.cursor			= 'auto';
	document.getElementById('save_button').style.cursor				= 'auto';

	loadDocumentList();
}

/* ==================================================================================================== */ // Functions

function checkCopyFields(sType, sState, sSelected)
{
	if(sType == 'offer' && sState == 'accepted' && sSelected == 'account')
	{
		$('copy_checkbox').disabled	= false;
		if($('copy_checkbox').checked == true)
		{
			$('copy_procent').disabled = false;
			$('copy_procent').focus();
		}
		else
		{
			$('copy_procent').disabled = 'disabled';
		}
	}
	else
	{
		$('copy_checkbox').disabled	= 'disabled';
		$('copy_procent').disabled	= 'disabled';
	}
}

/* ====================================================================== */

function closeDocument()
{

	var bConfirm = confirm("Möchten Sie das Fenster wirklich schließen?");

	if(!bConfirm) {
		return false;
	}

	// Reload document list
	loadDocumentList();

	return true;

}

/* ====================================================================== */

function displayAccordions() {

	// For selected payment condition
	var selectedPaymentID = 0;
	if(document.getElementById('acc_5_payment').value != '') {
		selectedPaymentID = document.getElementById('acc_5_payment').value;
	}

	switch(documentType)
	{
		case 'letter' :
		case 'fax':
		{
			document.getElementById('accordion_2').style.display = 'none';
			document.getElementById('accordion_3').style.display = 'none';
			document.getElementById('accordion_4').style.display = 'none';
			document.getElementById('accordion_5').style.display = 'none';

			break;
		}
		case 'offer':
		case 'confirmation':
		case 'account':
		case 'credit':
		case 'cancellation_invoice':
		case 'contract':
		{
			document.getElementById('accordion_2').style.display = 'inline';
			document.getElementById('accordion_3').style.display = 'none';
			document.getElementById('accordion_4').style.display = 'inline';
			document.getElementById('accordion_5').style.display = 'inline';

			setNewPayments(selectedPaymentID);
			createPositionsList();

			break;
		}
		case 'reminder':
		{
			document.getElementById('accordion_2').style.display = 'none';
			document.getElementById('accordion_3').style.display = 'inline';
			document.getElementById('accordion_4').style.display = 'inline';
			document.getElementById('accordion_5').style.display = 'inline';

			setNewPayments(selectedPaymentID);

			createReceivablesList();

			break;
		}
	}

	if(
		documentType == 'account' ||
		documentType == 'credit' ||
		documentType == 'cancellation_invoice'
	) {
		$('invoice-data').show();
	} else {
		$('invoice-data').hide();
	}

	if(documentID > 0) {
		$('copy-data').show();
	} else {
		$('copy-data').hide();
	}

	// If the document is new and the document type will be changed,
	// set the customer specified payment conditions
	if(documentID <= 0) {
		if(documentType == 'offer') {
			setNewPayments(iCustPayMisc);
		} else {
			setNewPayments(iCustPayInvoice);
		}
	}

	// Update payment message (text)
	setPaymentText(document.getElementById('acc_5_payment').value);

}

/* ====================================================================== */

function prepareSelectTabs(arrData)
{

	if(aPositions.length <= 0)
	{
		document.getElementById('backToListButton').style.display = 'none';
	}

	if(arrData['id'] <= 0)
	{
		document.getElementById('acc_1_contactPerson').disabled = 'disabled';
	}
	else
	{
		document.getElementById('privew_button').style.display = 'inline';

		if(document.getElementById('acc_1_customer').value == '')
		{
			document.getElementById('acc_1_contactPerson').disabled = 'disabled';
		}
		document.getElementById('acc_1_type').disabled = 'disabled';
	}

	if(aPositions.length > 0)
	{
		document.getElementById('editPosition').style.display = 'none';
		document.getElementById('addPosition').style.display = 'none';
	}
	else
	{
		document.getElementById('positionsList').style.display = 'none';
	}

	if(documentType == 'reminder')
	{
		// A customer is selected
		if(document.getElementById('acc_1_customer').value != '')
		{
			document.getElementById('acc_1_customer').disabled = 'disabled';
		}
	}

	// If document is a credit: price = price * -1;
	checkDocumentType();

}

/* ====================================================================== */

function addTextBlock(oBlock, sArea)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_block_text&block_text_id='+oBlock.value;
	strParameters += '&area='+sArea;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: addTextBlockCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function addTextBlockCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	var sText = arrData['sText'];
	var sArea = arrData['sArea'];

	var oEditor;

	if(sArea == 'starttext') {
		oEditor = tinyMCE.get('editor_starttext');
	} else if(sArea == 'endtext') {
		oEditor = tinyMCE.get('editor_endtext');
	}

	try {

		if(sText != null) {
			oEditor.insertContent(sText);
		}
	}
	catch(e) {}

	// Reset templates select-Tag
	for(var i = 0; i < oBlock.options.length; i++)
	{
		if(oBlock.options[i].value == '')
		{
			oBlock.options[i].selected = 'selected';
			break;
		}
	}
}

/* ====================================================================== */

function clearPayments()
{
	var aNewPayments = new Array();

	for(var i = 0; i < aPayments.length; i++)
	{
		if(!aPayments[i][2]['id'])
		{
			aNewPayments.push(aPayments[i]);
		}
		else
		{
			if(documentType == 'offer' && aPayments[i][2]['type_flag'] == 1)
			{
				aNewPayments.push(aPayments[i]);
			}
			else if(
				(
					documentType == 'account'
						||
					documentType == 'reminder'
						||
					documentType == 'contract'
				)
					&&
				aPayments[i][2]['type_flag'] == 2
			)
			{
				aNewPayments.push(aPayments[i]);
			}
			else if((documentType == 'credit' || documentType == 'cancellation_invoice') && aPayments[i][2]['type_flag'] == 3)
			{
				aNewPayments.push(aPayments[i]);
			}
			else if(aPayments[i][2]['type_flag'] == 0)
			{
				aNewPayments.push(aPayments[i]);
			}
		}
	}

	return aNewPayments;

}

/* ====================================================================== */

function setNewPayments(selectedPaymentID)
{

	var aNewPayments = clearPayments();

	// Get payments object
	var oObj = document.getElementById('acc_5_payment');

	// Remove all options from contact persons
	while(oObj.hasChildNodes())
	{
		oObj.removeChild(oObj.firstChild);
	}

	// Create new option-Tags on contact person
	for(var i = 0; i < aNewPayments.length; i++)
	{
		var newOption = document.createElement('option');

		// Set option value
		newOption.setAttribute('value', aNewPayments[i][0]);

		// Set selected payment if required
		if(selectedPaymentID > 0 && aNewPayments[i][0] == selectedPaymentID)
		{
			newOption.setAttribute('selected', 'selected');
		}

		// Set option content
		newOption.innerHTML = aNewPayments[i][1];

		// Add option to select
		oObj.appendChild(newOption);
	}

	if(documentType == 'contract')
	{
		document.getElementById('contract_options').style.display = 'inline';
	}
	else
	{
		document.getElementById('contract_options').style.display = 'none';
	}

	// If document is a credit: price = price * -1;
	checkDocumentType();

	if(aPositions.length > 0 && documentType != 'reminder')
	{
		createPositionsList();
	}

}

/* ====================================================================== */

function setPaymentText(iSelectedPayment)
{
	for(var i = 0; i < aPayments.length; i++)
	{
		if(aPayments[i][0] == iSelectedPayment)
		{
			// Sprache ermitteln
			var iCustomerId = $F('acc_1_customer');
			var sLanguage = 'de';
			aCustomers.each(function(aCustomer){
				if(aCustomer[0] == iCustomerId) {
					if(
						aCustomer[2] != null &
						aCustomer[2] != ''
					) {
						sLanguage = aCustomer[2];
					}
					throw $break;
				}
			});

			// Calculate the date
			var sDate		= document.getElementById('acc_1_date').value;
			var aDate		= sDate.split('.');
			if(parseInt(aDate[2], 10) < 2000 && aDate[2].length == 2)
			{
				aDate[2] = parseInt(aDate[2], 10) + 2000;
			}
			var iTimeStamp	= Date.UTC(aDate[2], aDate[1]-1, aDate[0], 0, 0, 0);
			var sNewDate	= '';

			// Calculate the additional days
			if(aPayments[i][2]['days'])
			{
				iTimeStamp += (aPayments[i][2]['days'] * 3600 * 24 * 1000);
			}

			var oDate = new Date(iTimeStamp);

			// Set day
			if(oDate.getDate() < 10)
			{
				sNewDate += '0';
			}
			sNewDate += oDate.getDate() + '.';

			// Set month
			iMonth = oDate.getMonth() + 1;
			if(iMonth < 10)
			{
				sNewDate += '0';
			}
			sNewDate += iMonth + '.';

			// Set year
			sNewDate += oDate.getFullYear();

			var sMessage = aPayments[i][2]['message'];
			if(
				aPayments[i][2]['message_'+sLanguage] &&
				aPayments[i][2]['message_'+sLanguage] != ''
			) {
				sMessage = aPayments[i][2]['message_'+sLanguage];
			}

			// Display message
			document.getElementById('payment_condition_message').childNodes[0].nodeValue = sMessage.replace(/<#date#>/g, sNewDate);

			break;

		}
	}
}

/* ====================================================================== */

function calculatePaymentAmount()
{
	var iTotalDiscount = parseFloat(document.getElementById('acc_5_totalDiscount').value.replace(/\./g, '').replace(/,/, '.'));
	var iCashDiscount = parseFloat(document.getElementById('acc_5_cashDiscount').value.replace(/\./g, '').replace(/,/, '.'));

	if(iTotalDiscount > 100)
	{
		alert('Der Rabatt darf 100% nicht überschreiten. Bitte korregieren Sie Ihre Angabe.');
		document.getElementById('acc_5_totalDiscount').value = '0,00';
	}

	if(iCashDiscount > 100)
	{
		alert('Der Skontosatz darf 100% nicht überschreiten. Bitte korregieren Sie Ihre Angabe.');
		document.getElementById('acc_5_cashDiscount').value = '0,00';
	}

	var iTotalPrice		= 0;
	var iMinusDiscount	= 0;
	var iPlusVat		= 0;

	var aVatSum = new Hash();
	for(var i = 0; i < aPositions.length; i++)
	{
		var sKey = aPositions[i]['vat'].toString();
		if(!aVatSum.get(sKey))
		{
			aVatSum.set(sKey, 0);
		}
		iItemPrice = parseFloat(aPositions[i]['totalprice']);
		aVatSum.set(sKey, (aVatSum.get(sKey) + iItemPrice));
	}

	aVatSum.each(function(pair)
	{
		iTotalPrice		+= parseFloat(pair.value);
		var fVat		= pair.value * (1 - parseFloat(iTotalDiscount) / 100);
		iMinusDiscount	+= parseFloat(fVat);
		iPlusVat		+= parseFloat(fVat) * (1 + parseFloat(pair.key) / 100);
	});

	document.getElementById('calculatedTotalPrice').childNodes[0].nodeValue		= parseFloat(iTotalPrice * iPriceMultiplicator).number_format(2, ',', '.');
	document.getElementById('calculatedMinusDiscount').childNodes[0].nodeValue	= parseFloat(iMinusDiscount * iPriceMultiplicator).number_format(2, ',', '.');
	document.getElementById('calculatedPlusVat').childNodes[0].nodeValue		= parseFloat(iPlusVat * iPriceMultiplicator).number_format(2, ',', '.');
}

/* ====================================================================== */

function calculateReceivable(iReminderID)
{
	var iPrice		= document.getElementById('reminder_price_' + iReminderID).childNodes[0].nodeValue.replace(/\./g, '').replace(/,/, '.');
	var iFee		= document.getElementById('fee_'+iReminderID).value.replace(/\./g, '').replace(/,/, '.');
	var iInterest	= document.getElementById('interest_'+iReminderID).value.replace(/\./g, '').replace(/,/, '.');

	// Calculate new prices
	var iTotalPrice	= parseFloat(iPrice) * (1 + parseFloat(iInterest) / 100) + parseFloat(iFee);
	var iZins		= parseFloat(iPrice) / 100 * parseFloat(iInterest);

	// Set zins price
	document.getElementById('reminder_zins_'+iReminderID).childNodes[0].nodeValue = iZins.number_format(2, ',', '.');

	// Set new total price
	document.getElementById('reminder_totalprice_'+iReminderID).childNodes[0].nodeValue = iTotalPrice.number_format(2, ',', '.');
}

/* ====================================================================== */

function hidePositionDIVs(sModus)
{

	if(sModus == 'onAdd_onEdit_offList')
	{
		document.getElementById('addPosition').style.display = 'inline';
		document.getElementById('editPosition').style.display = 'inline';
		document.getElementById('positionsList').style.display = 'none';
	}

	if(sModus == 'offAdd_offEdit_onList')
	{
		document.getElementById('addPosition').style.display = 'none';
		document.getElementById('editPosition').style.display = 'none';
		document.getElementById('positionsList').style.display = 'inline';
	}

}

/* ====================================================================== */

function checkDocumentType() {

	if(
		documentType == 'cancellation_invoice'
	) {
		iPriceMultiplicator = -1;
	} else {
		iPriceMultiplicator = 1;
	}

	if (documentType != 'reminder') {
		calculatePaymentAmount();
		createPositionsList();
	}

}

/* ====================================================================== */

var selectedPositionRow = 0;
function checkPositionRow(e, strId) {

	var objRow = $(strId);

	if(
		selectedPositionRow && 
		$(selectedPositionRow)
	) {
		$(selectedPositionRow).className = "";
	}

	if(objRow.hasClassName('selectedRow')) {
		objRow.className = '';
		selectedPositionRow = null;
	} else {
		objRow.className = 'selectedRow';
		selectedPositionRow = strId;
	}

}

/* ====================================================================== */

var iEditPositionID = 0;
function editPosition(c, aPosition)
{
	// Set the id of position which is to edit
	iEditPositionID = aPosition['id'];

	// Display / hide DIVs
	document.getElementById('positionsEditSaveButton').style.display	= 'inline';
	document.getElementById('positionsAddButton').style.display			= 'none';
	document.getElementById('editPosition').style.display				= 'inline';
	document.getElementById('positionsList').style.display				= 'none';

	$('acc_3_settlement_list_item').value = '0';

	// Fill the fields with position data
	if(aPosition['only_text'] == "1") {
		document.getElementById('acc_3_only_text').checked			= true;
	} else {
		document.getElementById('acc_3_only_text').checked			= false;
	}
	if(aPosition['groupsum'] == "1") {
		document.getElementById('acc_3_groupsum').checked			= true;
	} else {
		document.getElementById('acc_3_groupsum').checked			= false;
	}
    document.getElementById('acc_3_group_display').value        = aPosition['group_display'];
	document.getElementById('acc_3_amount').value				= parseFloat(aPosition['amount']).number_format(2, ',', '.');
	document.getElementById('acc_3_number').value				= aPosition['number'];
	document.getElementById('acc_3_product').value				= aPosition['product'];
	document.getElementById('acc_3_price').value				= parseFloat(aPosition['price']).number_format(2, ',', '.');
	document.getElementById('acc_3_discountItem').value			= parseFloat(aPosition['discount_item']).number_format(4, ',', '.');
	document.getElementById('acc_3_description').value			= aPosition['description'];

	for(var i = 0; i < document.getElementById('acc_3_revenue_account').options.length; i++) {
		if(document.getElementById('acc_3_revenue_account').options[i].value == aPosition['revenue_account']) {
			document.getElementById('acc_3_revenue_account').options[i].selected = 'selected';
			break;
		}
	}

	for(var i = 0; i < document.getElementById('acc_3_unit').options.length; i++) {
		if(document.getElementById('acc_3_unit').options[i].value == aPosition['unit']) {
			document.getElementById('acc_3_unit').options[i].selected = 'selected';
			break;
		}
	}

	for(i = 0; i < document.getElementById('acc_3_vat').options.length; i++)
	{
		if((parseFloat(document.getElementById('acc_3_vat').options[i].value)) == (parseFloat(aPosition['vat']) / 100))
		{
			document.getElementById('acc_3_vat').options[i].selected = 'selected';
			break;
		}
	}
	
	togglePositionFields();
	
}

/* ====================================================================== */

function cleanPositionFields(sModus)
{
	// Clear position fields
	document.getElementById('acc_3_amount').value				= '';
	document.getElementById('acc_3_number').value				= '';
	document.getElementById('acc_3_product').value				= '';
	document.getElementById('acc_3_price').value				= '';
	document.getElementById('acc_3_discountItem').value			= '';
	document.getElementById('acc_3_description').value			= '';
	document.getElementById('acc_3_unit').options[0].selected	= 'selected';
	document.getElementById('acc_3_vat').options[0].selected	= 'selected';
	document.getElementById('acc_3_revenue_account').options[0].selected	= 'selected';

	if(sModus == 1)
	{
		document.getElementById('positionsEditSaveButton').style.display	= 'none';
		document.getElementById('positionsAddButton').style.display			= 'inline';
	}
}

/* ==================================================================================================== */ // Table creation functions

function createPositionsList()
{

	var tbody 		= document.getElementById('tbl_positions');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5, td6;

	// Remove all positions
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Create new positions
    for(var i = 0; i < aPositions.length; i++, c++)
    {
        objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);
        var strId = 'pos_tr_' + aPositions[i]['id'];
        objTr.id = strId;

        Event.observe(objTr, 'click', checkPositionRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', editPosition.bindAsEventListener(c, aPositions[i]));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		if(aPositions[i]['only_text'] == 1) {
			
			td0 = document.createElement("td");
			objTr.appendChild(td0);
			td0.innerHTML = '&nbsp;';

			td1 = document.createElement("td");
			objTr.appendChild(td1);
			td1.innerHTML = '&nbsp;';

			td2 = document.createElement("td");
			td2.colSpan = 5;
			objTr.appendChild(td2);
			td2.innerHTML = aPositions[i]['product'];

		} else {

			td0 = document.createElement("td");
			objTr.appendChild(td0);
			td0.innerHTML = parseFloat(aPositions[i]['amount']).number_format(2, ',', '.') + ' ' + aPositions[i]['unit'];

			td1 = document.createElement("td");
			objTr.appendChild(td1);
			td1.innerHTML = (aPositions[i]['number'].length != 0 ? aPositions[i]['number'] : '&nbsp;');

			td2 = document.createElement("td");
			objTr.appendChild(td2);
			td2.innerHTML = aPositions[i]['product'];

			td3 = document.createElement("td");
			objTr.appendChild(td3);
			td3.innerHTML = parseFloat(aPositions[i]['price'] * iPriceMultiplicator).number_format(2, ',', '.') + ' €';
			td3.style.textAlign = 'right';

			td4 = document.createElement("td");
			objTr.appendChild(td4);
			td4.innerHTML = parseFloat(aPositions[i]['discount_item']).number_format(4, ',', '.') + ' %';
			td4.style.textAlign = 'right';

			td5 = document.createElement("td");
			objTr.appendChild(td5);
			td5.innerHTML = parseFloat(aPositions[i]['totalprice'] * iPriceMultiplicator).number_format(2, ',', '.') + ' €';
			td5.style.textAlign = 'right';

			td6 = document.createElement("td");
			objTr.appendChild(td6);
			td6.innerHTML = parseFloat(aPositions[i]['vat']).number_format(2, ',', '.') + ' %';
			td6.style.textAlign = 'right';

		}

		td0 = td1 = td2 = td3 = td4 = td5 = td6 = null;
    }

	document.getElementById('payment_amount_div').style.display = 'inline';

	calculatePaymentAmount();

    tbody = null;

	// Initialize the table and table rows for moving the articles positions
	if(aPositions.length > 1)
	{
		// Sorting of positions with drop & drag
		var oTable = document.getElementById('tablePositions');
		var oTableDnD = new TableDnD();
		oTableDnD.init(oTable);

		var aRowIDs = new Array();
		oTableDnD.onDrop = function(oTable, oDroppedRow)
		{
			oDroppedRow.className = '';
			aRowIDs = new Array();
			var aRows = this.table.tBodies[0].rows;

			if(bMovingFlag == true)
			{
				// Filter the position IDs
				for (i = 0; i < aRows.length; i++)
				{
					aRowIDs.push(aRows[i].id.substr(7));
					aRows[i].className = '';
				}

				// Only if the position was realy moved
				// do AJAX request for updating of article positions
				bMovingFlag = false;
				managePosition('sort', JSON.stringify(aRowIDs));
			}
		}
	}
}

/* ====================================================================== */

function createReceivablesList()
{

	if (documentType == 'reminder')
	{
		if(document.getElementById('acc_1_customer').value != '')
		{
			if(aReceivables.length > 0)
			{
				document.getElementById('receivablesNoReceivables').style.display	= 'none';
				document.getElementById('receivablesNoCustomer').style.display		= 'none';
				document.getElementById('receivablesList').style.display			= 'inline';
			}
			else
			{
				document.getElementById('receivablesNoReceivables').style.display	= 'inline';
				document.getElementById('receivablesNoCustomer').style.display		= 'none';
				document.getElementById('receivablesList').style.display			= 'none';
			}
		}
		else
		{
			document.getElementById('receivablesNoReceivables').style.display	= 'none';
			document.getElementById('receivablesNoCustomer').style.display		= 'inline';
			document.getElementById('receivablesList').style.display			= 'none';
		}
	}

	var tbody 		= document.getElementById('tbl_receivables');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5, td6, td7, td8;

	// Remove all receivables
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Create new receivables
    for(var i = 0; i < aReceivables.length; i++, c++)
    {
        objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);
        var strId = 'pos_tr_';
        objTr.id = strId;
		Event.observe(objTr, 'mouseout', resetHighlightRow); 
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = aReceivables[i]['date'];

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = aReceivables[i]['number'];

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = aReceivables[i]['due_date'];

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = aReceivables[i]['price'];
		td3.id = 'reminder_price_'+aReceivables[i]['id'];
		td3.style.textAlign = 'right';

		var iFee = iInterest = '0,00';
		if(aReminders[aReceivables[i]['id']])
		{
			iFee		= parseFloat(aReminders[aReceivables[i]['id']]['fee']).number_format(2, ',', '.');
			iInterest	= parseFloat(aReminders[aReceivables[i]['id']]['interest']).number_format(2, ',', '.');
		}

		var sFeeStr = 'fee_'+aReceivables[i]['id'];
		var sIntStr = 'interest_'+aReceivables[i]['id'];

		td4 = document.createElement("td");
		objTr.appendChild(td4);
		td4.innerHTML = '<input id="fee_'+aReceivables[i]['id']+'" value="'+iFee+'" style="width:60px;" onblur="calculateReceivable(\''+aReceivables[i]['id']+'\'); manageReminders(0, '+aReceivables[i]['id']+', document.getElementById(\''+sFeeStr+'\').value, document.getElementById(\''+sIntStr+'\').value);" />';

		td5 = document.createElement("td");
		objTr.appendChild(td5);
		td5.innerHTML = '<input id="interest_'+aReceivables[i]['id']+'" value="'+iInterest+'" style="width:60px;" onblur="calculateReceivable(\''+aReceivables[i]['id']+'\'); manageReminders(0, '+aReceivables[i]['id']+', document.getElementById(\''+sFeeStr+'\').value, document.getElementById(\''+sIntStr+'\').value);" />';

		td6 = document.createElement("td");
		objTr.appendChild(td6);
		td6.innerHTML = '0,00';
		td6.id = 'reminder_zins_'+aReceivables[i]['id'];
		td6.style.textAlign = 'right';

		td7 = document.createElement("td");
		objTr.appendChild(td7);
		td7.innerHTML = ' ' + aReceivables[i]['receivable'];
		td7.id = 'reminder_totalprice_'+aReceivables[i]['id'];
		td7.style.textAlign = 'right';

		td8 = document.createElement("td");
		objTr.appendChild(td8);
		if(aReminders[aReceivables[i]['id']])
		{
			td8.innerHTML = '<input id="check_'+aReceivables[i]['id']+'" type="checkbox" checked="checked" onchange="manageReminders(this, '+aReceivables[i]['id']+', document.getElementById(\''+sFeeStr+'\').value, document.getElementById(\''+sIntStr+'\').value);" />';
		}
		else
		{
			td8.innerHTML = '<input id="check_'+aReceivables[i]['id']+'" type="checkbox" onchange="manageReminders(this, '+aReceivables[i]['id']+', document.getElementById(\''+sFeeStr+'\').value, document.getElementById(\''+sIntStr+'\').value);" />';
		}

		td0 = td1 = td2 = td3 = td4 = td5 = td6 = td7 = td8 = null;

		calculateReceivable(aReceivables[i]['id']);
    }

	document.getElementById('payment_amount_div').style.display = 'none';

    tbody = null;

}