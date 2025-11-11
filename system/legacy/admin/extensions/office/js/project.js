
/**
 * 
 */
var iProjectID = 0;
var iProjectPositionID = 0;
var bProjectClosed = false;

var aActivities = new Array();
var aProjectPositions = new Array();

var aProjectTimes = new Array();
var aEmployeeSums = new Array();

var bHookFlag = false;

var aLoadedProjectsSelectOptions = [];

/* ====================================================================== */

function openProjectDialog(iDocumentID) {

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_project_data&project_id=' + iProjectID;

	if(iDocumentID) {
		strParameters += '&document_id=' + iDocumentID;
	}

	try {

		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openProjectDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function openProjectAnalysisDialog()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_project_analysis&project_id=' + iProjectID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openProjectAnalysisDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function copyProject()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=copy_project&project_id=' + iProjectID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: copyProjectCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function copyProjectCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	loadProjectsList();

	alert('Das Project wurde erfolgreich kopiert. Neuer Titel: ' + arrData['sNewTitle']);
}

/* ====================================================================== */

function getContactPersons(oCustomers)
{
	if(bProjectClosed)
	{
		return;
	}

	// Get ID of customer
	var iCustomerId = oCustomers.value;

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_project_contact_persons';
	strParameters += '&customer_id='	+ iCustomerId;
	strParameters += '&project_id='		+ iProjectID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: getContactPersonsCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function getContactPersonsCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	setContactPersons(arrData['aContacts']);
}

/* ====================================================================== */

function deleteProjectPosition(aPosition)
{
	if(bProjectClosed)
	{
		return;
	}

	iProjectPositionID = aPosition['id'];

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=remove_project_position';
	strParameters += '&project_id='		+ iProjectID;
	strParameters += '&position_id='	+ iProjectPositionID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: deleteProjectPositionCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function deleteProjectPositionCallback(objResponse)
{
	if(bProjectClosed)
	{
		return;
	}

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	aProjectPositions = arrData['aPositions'];
	createProjectPositionsList(arrData['aPositions']);
}

/* ====================================================================== */

function saveProject()
{
	if(bProjectClosed)
	{
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_project';
	strParameters += '&project_id='         + iProjectID;
	strParameters += '&editor_id='          + $('acc_1_editor').value;
	strParameters += '&category_id='        + $('acc_1_category').value;
	strParameters += '&customer_id='        + $('acc_1_customer').value;
	strParameters += '&budget='             + encodeURIComponent($('acc_1_budget').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&start_date='         + encodeURIComponent($('acc_1_time_from').value);
	strParameters += '&end_date='           + encodeURIComponent($('acc_1_time_till').value);
	strParameters += '&offer_id='           + $('acc_1_offer_id').value;
	strParameters += '&title='              + encodeURIComponent($('acc_1_title').value);
    strParameters += '&product_area_id='    + $('acc_1_productarea').value;
	strParameters += '&description='        + encodeURIComponent($('acc_pro_description').value);

	if($('acc_1_title').value.length < 1)
	{
		alert('Bitte geben Sie den Titel des Projektes ein!');
		return;
	}
	if($('acc_1_time_from').value.length < 10)
	{
		alert('Bitte geben Sie das Startdatum des Projektes ein! (Format: TT.MM.JJJJ)');
		return;
	}
	if($('acc_1_time_till').value.length < 10)
	{
		alert('Bitte geben Sie das Enddatum des Projektes ein! (Format: TT.MM.JJJJ)');
		return;
	}
	if($('acc_1_customer').value.length < 1)
	{
		alert('Bitte wählen Sie einen Kunden aus!');
		return;
	}

	strParameters = oWdHooks.executeHook('save_project_additional_fields', strParameters);

	// Save >>>
	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : saveProjectCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function openProjectCloseDialog()
{
	if(bProjectClosed)
	{
		return;
	}

	var objGUI = new GUI;
	var strCode = '';

	strCode += '<div style="padding-top:100px; color:green; text-align:center; display:none;" id="pro_close_confirm">';
		strCode += '<b>Das Projekt wurde erfolgreich abgeschlossen.</b>';
	strCode += '</div>';

	/* ================================================== */
	strCode += '<div id="pro_close_content">';
		strCode += objGUI.startFieldset('Projekt abschließen');
			strCode += 'Sie können nun das Projekt abschließen.<br /><br />';
			strCode += '<div style="color:red;">';
				strCode += '<b>Achtung! Nach dem Abschließen des Projekts können keine Projektdaten mehr verändert werden.</b><br /><br />';
			strCode += '</div>';
			strCode += 'Bitte Fazit eingeben, falls gewünscht:<br />';
			strCode +=  '<textarea style="width:100%; height:150px;" id="project_conclusion" class="txt"></textarea>';
			strCode = oWdHooks.executeHook('display_project_rate_field', strCode);
		strCode += objGUI.endFieldset();
		strCode += objGUI.printFormButton('Projekt abschließen', 'closeProject();', 'close_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	strCode += '</div>';

	strCode = oWdHooks.executeHook('close_project_other_close_content', strCode);

	// Display LitBox
	if(bHookFlag == false)
	{
		objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:500, width:700, resizable:false, opacity:.9});
		/* =============================================================================================================== */
	}
}

/* ====================================================================== */

function deleteProject()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_project';
	strParameters += '&project_id='	+ iProjectID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: loadProjectsList
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function closeProject()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=close_project';
	strParameters += '&project_id='	+ iProjectID;
	strParameters += '&conclusion='	+ encodeURIComponent($('project_conclusion').value);

	strParameters = oWdHooks.executeHook('save_project_rate', strParameters);

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: closeProjectCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function closeProjectCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	$('pro_close_content').hide();
	$('pro_close_confirm').show();

	loadProjectsList();
}

/* ====================================================================== */

function saveConclusion()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_project_conclusion';
	strParameters += '&project_id='	+ iProjectID;
	strParameters += '&conclusion='	+ encodeURIComponent($('project_conclusion').value);

	strParameters = oWdHooks.executeHook('save_project_rate', strParameters);

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: true
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function saveProjectCallback(objResponse)
{
	if(bProjectClosed)
	{
		return;
	}

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	iProjectID = arrData['id'];
	aProjectPositions = arrData['aPositions'];

	if(iProjectID <= 0)
	{
		$('employeesList').hide();
		$('employeesMessage').show();
	}
	else
	{
		$('employeesMessage').hide();
		$('employeesList').show();
	}

	$('saving_confirmation').show();
	$j('#saving_confirmation').delay(3000).hide();

	loadProjectsList();

	createProjectPositionsList(arrData['aPositions']);
}

/* ====================================================================== */

function openProjectAnalysisDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];
	var aEmployees = objData['employees'];
	var objGUI = new GUI;
	var strCode = '';

	// Global
	aProjectTimes = arrData['times'];
	aEmployeeSums = arrData['employee_groups'];

	/* ================================================== */ // Costs / budget
	strCode += objGUI.startFieldset('Budget');
		strCode += '<div style="float:left; width:150px;">';
			strCode += '<b>Gesamtbudget:</b><br />';
			strCode += '<b>Verbrauchtes Budget:</b><br />';
			strCode += '<b>Kosten:</b>';
		strCode += '</div>';
		strCode += '<div style="float:left; width:120px; text-align:right;">';
			strCode += '<b>' + parseFloat(arrData['budget']).number_format(2, ',', '.') + ' €</b><br />';
			strCode += '<b>' + parseFloat(arrData['consumed_budget']).number_format(2, ',', '.') + ' €</b><br />';
			strCode += '<b>' + parseFloat(arrData['costs']).number_format(2, ',', '.') + ' €</b>';
		strCode += '</div>';
	strCode += objGUI.endFieldset();

	/* ================================================== */ // Employee select
	strCode += objGUI.startFieldset('Auswertung nach Mitarbeiter');
		strCode += '<div style="float:left; width:80px;">';
			strCode += '<b>Mitarbeiter:</b>';
		strCode += '</div>';
		strCode += '<div style="float:left; width:210px;">';
			strCode += '<select id="employee" onchange="updateProjectAnalytics();">';
			strCode += '<option value="">Alle</option>';	
			aEmployees.each(function(oEmployee) {
				strCode += '<option value="'+oEmployee.id+'">'+oEmployee.name+'</option>';
			});
			strCode += '</select>';
		strCode += '</div>';
		strCode += '<div style="float:left; width:70px;">';
			strCode += '<label>Zeitraum</label>';
		strCode += '</div>';
		strCode += '<div style="float:left; width:80px;">';
			strCode += '<input id="employee_time_from" class="txt" style="width:75px;" value="" />';
		strCode += '</div>';
		strCode += '<div style="float:left; width:5px;">';
			strCode += ' - ';
		strCode += '</div>';
		strCode += '<div style="float:left; width:80px;">';
			strCode += '<input id="employee_time_until" class="txt" style="width:75px;" value="" />';
		strCode += '</div>';
		strCode += '<div style="float:left; width:30px;">';
			strCode += '<button class="btn" onclick="updateProjectAnalytics();" style="background-color: #dedede; color: #000; padding: 0 4px;">';
				strCode += 'Go';
			strCode += '</button>';
		strCode += '</div>';
	strCode += objGUI.endFieldset();

	/* ================================================== */ // Times
	
	var sColspan = '';
	sColspan += '<colgroup>';
	sColspan += '<col style="text-align:center; width:40%;"/>';
	sColspan += '<col style="text-align:center; width:20%;"/>';
	sColspan += '<col style="text-align:center; width:20%;"/>';
	sColspan += '<col style="text-align:center; width:20%;"/>';
	sColspan += '</colgroup>';
	
	strCode += '<div style="margin: 10px; height:400px; overflow:hidden;">';
	
	strCode += '<div style="height:49px; overflow:hidden;">';
		strCode += '<table id="tableProjectAnalyticsHeader" cellpadding="0" cellspacing="0" border="0" class="table" style="width:562px;table-layout:fixed;">';
			strCode += sColspan;
			strCode += '<thead>';
				strCode += '<tr>';
					strCode += '<th>&nbsp;</th>';
					strCode += '<th colspan="2" style="text-align:center;">Stunden</th>';
					strCode += '<th>&nbsp;</th>';
				strCode += '</tr>';
				strCode += '<tr>';
					strCode += '<th style="text-align:center; width:40%;">Tätigkeit</th>';
					strCode += '<th style="text-align:center; width:20%;">geplante</th>';
					strCode += '<th style="text-align:center; width:20%;">gebrauchte</th>';
					strCode += '<th style="text-align:center; width:20%;">Kosten</th>';
				strCode += '</tr>';
			strCode += '</thead>';
		strCode += '</table>';
	strCode += '</div>';

	strCode += '<div style="height:226px; overflow-y:scroll;">';
		strCode += '<table id="tableProjectAnalytics" cellpadding="0" cellspacing="0" border="0" class="table" style="width:562px;table-layout:fixed;">';
			strCode += sColspan;
			strCode += '<tbody id="tbl_project_analytics"></tbody>';
		strCode += '</table>';
	strCode += '</div>';

	strCode += '<div style="height:25px;">';
		strCode += '<table id="tableProjectAnalyticsFooter" cellpadding="0" cellspacing="0" border="0" class="table" style="width:562px;table-layout:fixed;">';
			strCode += sColspan;
			strCode += '<tfoot>';
				strCode += '<thead><tr>';
					strCode += '<th style="text-align:left; width:40%;">Summen</th>';
					strCode += '<th style="text-align:right; width:20%;" id="sum-hours-planned">...</th>';
					strCode += '<th style="text-align:right; width:20%;" id="sum-hours-used">...</th>';
					strCode += '<th style="text-align:right; width:20%;" id="sum-costs">...</th>';
				strCode += '</tr></thead>';
			strCode += '</tfoot>';
		strCode += '</table>';
	strCode += '</div>';
	
	strCode += '<div style="height:100px; overflow-y:scroll;">';
		strCode += '<table id="tableProjectAnalytics" cellpadding="0" cellspacing="0" border="0" class="table" style="width:562px;table-layout:fixed;">';
			strCode += sColspan;
			strCode += '<tbody id="tbl_project_employee_sums"></tbody>';
		strCode += '</table>';
	strCode += '</div>';

	strCode += '</div>';

	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:590, width:600, resizable:false, opacity:.9});
	/* =============================================================================================================== */

	updateProjectAnalytics('');

}

/* ====================================================================== */

var rSerachTimeOut = null;

function openProjectDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];
	var objGUI = new GUI;
	var strCode = '';

	iProjectID = arrData['id'];
	bProjectClosed = arrData['bProjectClosed'];
	aProjectPositions = arrData['aPositions'];
	aActivities = arrData['aActivities'];

	// Open main container
	strCode += '<div id="main_container" onclick="document.getElementById(\'saving_confirmation\').style.display = \'none\'">';

	// Saving Confirmation
	strCode += '<div id="saving_confirmation" style="display:none; color:#008000;">';
		strCode += '<div style="text-align:center; padding-top:10px;">';
			strCode += '<b>Das Projekt wurde erfolgreich gespeichert!</b>';
		strCode += '</div>';
	strCode += '</div>';

	// Open accordions container
	strCode += objGUI.startAccordionContainer('');

	// Document settings accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Projekteinstellungen');
		strCode += objGUI.startFieldset('Einstellungen');
			strCode += '<div style="float:left; width:250px;">';
				strCode += '<input type="hidden" id="acc_1_offer_id" value="'+arrData['iOfferID']+'" />';
				strCode += objGUI.printFormSelect('Projektleiter', 'acc_1_editor', arrData['aEditors'], arrData['selectedEditor'], 'style="width:130px;"');
				strCode += objGUI.printFormSelect('Kategorisierung','acc_1_category',arrData['aCategories'], arrData['selectedCategory'], 'style="width:130px;"');
				strCode += objGUI.printFormInput('Titel', 'acc_1_title', arrData['sTitle'], 'style="width:120px;"');
                strCode += objGUI.printFormSelect('Produktbereich', 'acc_1_productarea', arrData['aProductAreas'], arrData['selectedProductArea'], 'style="width:130px;"');
				strCode += objGUI.printFormInput('Budget / €', 'acc_1_budget', parseFloat(arrData['iBudget']).number_format(2, ',', '.'), 'style="width:120px;"');
			strCode += '</div>';
			strCode += '<div style="float:left; width:445px;">';
				strCode += objGUI.printFormSelect('Kunde', 'acc_1_customer', arrData['aCustomers'], arrData['selectedCustomer'], 'onchange="getContactPersons(this);" style="width:335px;"');
				strCode += '<div style="float: left;">Kontaktpersonen</div><div style="float: left; margin-left: 2px;" id="contact_persons"></div>';
			strCode += '</div>';
			strCode += '<div style="clear:both;"></div>';
			strCode += '<label style="margin-right:3px;">Zeitraum</label>';
			strCode += '<input id="acc_1_time_from" class="txt" style="width:75px;" value="'+arrData['sFrom']+'" /> - ';
			strCode += '<input id="acc_1_time_till" class="txt" style="width:75px;" value="'+arrData['sTill']+'" />';
		strCode += objGUI.endFieldset();
	strCode += objGUI.endAccordion();

	// Project description accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Projektbeschreibung');
		strCode += objGUI.startFieldset('Beschreibung');
			strCode += '<div style="text-align:center;margin-bottom:5px;">'
				strCode += objGUI.printFormTextarea('', 'acc_pro_description', arrData['sDescription'], '5', '5', 'style="width:690px;height:100px;"');
			strCode += '</div>'
		strCode += objGUI.endFieldset();
		strCode = oWdHooks.executeHook('get_project_additional_fields', strCode);
	strCode += objGUI.endAccordion();

	// Project positions accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Tätigkeiten');
		strCode += '<div id="positionsList">';
			strCode += '<div id="positionSettings" style="padding:3px; background-color:#f7f7f7; margin-top:5px; border: 1px solid #CCC;">';
				strCode += '<div style="position:relative; top:-3px;">';
					strCode += '<b>Anlegen:</b>';
					strCode += '<img onclick="addProjectPosition();" src="/admin/media/page_new.gif" alt="Anlegen" title="Anlegen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
					strCode += ':: <b>Bearbeitung:</b>';
					strCode += '<img onclick="manageProjectPosition(\'copy\');" src="/admin/media/page_copy.png" alt="Ableiten" title="Ableiten" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
					strCode += '<img onclick="manageProjectPosition(\'delete\');" src="/admin/media/cross.png" alt="Löschen" title="Löschen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
				strCode += '</div>';
			strCode += '</div>';
			strCode += '<div style="margin: 5px 0; height:200px; overflow-y:scroll; border: 1px solid #CCC;">';
				strCode += '<table id="tablePositions" cellpadding="0" cellspacing="0" border="0" class="table" style="width:100%">';
					strCode += '<thead>';
						strCode += '<tr>';
							strCode += '<th>Produkt</th>';
							strCode += '<th>Alias</th>';
							strCode += '<th>Verk. Menge</th>';
							strCode += '<th>Gepl. Menge</th>';
							strCode += '<th>Tätigkeit</th>';
						strCode += '</tr>';
					strCode += '</thead>';
					strCode += '<tbody id="tbl_positions"></tbody>';
				strCode += '</table>';
			strCode += '</div>';
		strCode += '</div>';
	strCode += objGUI.endAccordion();

	// Employees accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Mitarbeiter');
		strCode += '<div id="employeesMessage" style="text-align:center;">';
			strCode += '<br /><b>Um Mitarbeiter zu verwalten, speichern Sie bitte zuerst das Projekt.</b>';
		strCode += '</div>';
		strCode += '<div id="employeesList">';
			strCode += '<div style="padding:3px; background-color:#f7f7f7; margin-top:5px; border: 1px solid #CCC;">';
				strCode += '<div style="position:relative; top:-3px;">';
					strCode = oWdHooks.executeHook('set_additional_icons_list', strCode);
					strCode += '<div id="icons_list">';
						strCode += '<b>Hinzufügen:</b>';
						strCode += '<img onclick="loadProjectEmployeesList();" src="/admin/media/page_new.gif" alt="Hinzufügen" title="Hinzufügen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
						strCode = oWdHooks.executeHook('display_project_search_icon', strCode);
						strCode += ':: <b>Entfernen:</b>';
						strCode += '<img onclick="removeProjectEmployee();" src="/admin/media/cross.png" alt="Entfernen" title="Entfernen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
						strCode = oWdHooks.executeHook('display_employee_email_icon', strCode);
					strCode += '</div>';
					strCode += '<div id="groups_list" style="display:none; position:relative; top:4px;">';
						strCode += '<b>Gruppen: </b>';
						strCode += '<select class="txt" id="emp_groups" onchange="loadProjectEmployeesList();">';
						for(var i = 0; i < arrData['aEmployeeGroups'].length; i++)
						{
							strCode += '<option value="'+arrData['aEmployeeGroups'][i][0]+'">'+arrData['aEmployeeGroups'][i][1]+'</option>';
						}
						strCode += '</select> ';
						strCode += ' :: <b>Name: </b>';
						strCode += '<input class="txt" id="emp_name_search" onkeyup="rSerachTimeOut = setTimeout(\'loadProjectEmployeesList()\',500);" value="'+arrData['nameSearch']+'" />';
						strCode += ' :: <b>Hinzufügen:</b> <input type="checkbox" style="border:0; position:relative; top:2px;" onclick="selectAllBeforAdd(this);" /><img onclick="addProjectEmployees();" src="/admin/media/accept.png" alt="Markierte hinzufügen" title="Markierte hinzufügen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
					strCode += '</div>';
				strCode += '</div>';
			strCode += '</div>';
			strCode = oWdHooks.executeHook('set_additional_content', strCode);
			strCode += '<div id="pro_emp_list" style="margin: 5px 0; height:200px; overflow-y:scroll; border: 1px solid #CCC;">';
				strCode += '<table id="tableProjectEmployees" cellpadding="0" cellspacing="0" border="0" class="table" style="width:100%;">';
					strCode += '<thead>';
						strCode += '<tr>';
							strCode += '<th>Name</th>';
							strCode += '<th>Abteilung</th>';
							strCode += '<th>Position</th>';
						strCode += '</tr>';
					strCode += '</thead>';
					strCode += '<tbody id="tbl_project_employees"></tbody>';
				strCode += '</table>';
			strCode += '</div>';
			strCode += '<div id="all_emp_list" style="margin: 5px 0; padding:5px; height:200px; overflow-y:scroll; border: 1px solid #CCC; display:none;">';
				
			strCode += '</div>';
		strCode += '</div>';
	strCode += objGUI.endAccordion();

	// Conclusion accordion
	/* ================================================== */
	if(bProjectClosed)
	{
		strCode += objGUI.startAccordion('Fazit');
			strCode += objGUI.startFieldset('Fazit ändern / ergänzen');
				strCode +=  '<textarea style="width:100%; height:150px;" id="project_conclusion" class="txt">'+arrData['sConclusion']+'</textarea>';
				strCode = oWdHooks.executeHook('get_project_rate', strCode, arrData);
			strCode += objGUI.endFieldset();
		strCode += objGUI.endAccordion();
	}

	// Close accordions container
	strCode += objGUI.endAccordionContainer('');

	// Save button
	if(!bProjectClosed)
	{
		strCode += objGUI.printFormButton('Projekt speichern', 'saveProject();', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	}
	else
	{
		strCode += objGUI.printFormButton('Fazit speichern', 'saveConclusion();', 'save_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	}

	// Close main container
	strCode += '</div>';

	// Display LitBox
	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:600, width:750, resizable:false, opacity:.9});
	/* =============================================================================================================== */

	setContactPersons(arrData['aContacts']);

	activateProjectAccordions();

	if(iProjectID <= 0) {
		$('employeesList').hide();
		$('employeesMessage').show();
	} else {
		$('employeesMessage').hide();
		$('employeesList').show();
	}

	createProjectPositionsList(arrData['aPositions']);
	createEmployeesList(arrData['aEmployees']);

	oWdHooks.executeHook('fill_project_additional_fields', arrData['aAddons']);
}

/* ====================================================================== */

function selectAllBeforAdd(oCB)
{
	if(aNoProjectEmployees)
	{
		for(var i = 0; i < aNoProjectEmployees.length; i++)
		{
			if($('no_pro_emp_' + aNoProjectEmployees[i]['id']))
			{
				$('no_pro_emp_' + aNoProjectEmployees[i]['id']).checked = oCB.checked;
			}
		}
	}
}

/* ====================================================================== */

function loadProjectEmployeesList()
{
	rSerachTimeOut = null;

	if(bProjectClosed)
	{
		return;
	}

	$('pro_emp_list').hide();
	$('all_emp_list').show();
	$('groups_list').show();
	$('icons_list').hide();

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = '?task=get_project_employees_list';
	strParameters += '&group_id=' + $('emp_groups').value;
	strParameters += '&search=' + $('emp_name_search').value;
	strParameters += '&project_id=' + iProjectID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : loadProjectEmployeesListCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function addProjectEmployees()
{
	if(bProjectClosed)
	{
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = '?task=add_project_employees';
	strParameters += '&group_id=' + $('emp_groups').value;
	strParameters += '&project_id=' + iProjectID;

	var sEmployees = '';
	for(var i = 0; i < aNoProjectEmployees.length; i++)
	{
		if
		(
			$('no_pro_emp_'+aNoProjectEmployees[i]['id'])
				&&
			$('no_pro_emp_'+aNoProjectEmployees[i]['id']).checked == true
		)
		{
			sEmployees += aNoProjectEmployees[i]['id'] + '|';
		}
	}
	strParameters += '&employees=' + sEmployees;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : addProjectEmployeesCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function addProjectEmployeesCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var aEmployees = objData['data'];

	$('pro_emp_list').show();
	$('icons_list').show();
	$('all_emp_list').hide();
	$('groups_list').hide();

	createEmployeesList(aEmployees);
}

/* ====================================================================== */

function removeProjectEmployee()
{
	if(bProjectClosed)
	{
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = '?task=remove_project_employee';
	strParameters += '&employee_link_id=' + iProjectEmployeeID;
	strParameters += '&project_id=' + iProjectID;

	iProjectEmployeeID = 0;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : addProjectEmployeesCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

var aNoProjectEmployees = new Array();

function loadProjectEmployeesListCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var aEmployees = objData['data'];

	aNoProjectEmployees = aEmployees;

	var mainDIV 	= document.getElementById('all_emp_list');
	var div    		= document.createElement('div');
    var objDIV   	= div.cloneNode(true);
	var objGUI		= new GUI;

	while(mainDIV.hasChildNodes()) {
		mainDIV.removeChild(mainDIV.firstChild);
	}

	if(aEmployees.length == 0)
	{
		objDIV = div.cloneNode(false);
		mainDIV.appendChild(objDIV);
		objDIV.style.textAlign = 'center';
		objDIV.innerHTML = '<br /><b>Keine Mitarbeiter in dieser Gruppe verfügbar</b>';
	}

	for(var i = 0; i < aEmployees.length; i++)
	{
		objDIV = div.cloneNode(false);
		mainDIV.appendChild(objDIV);

		Event.observe(objDIV, 'mouseout', resetHighlightRow);
		Event.observe(objDIV, 'mousemove', setHighlightRow);

		objDIV.style.padding = '3px';
		objDIV.style.margin = '1px';
		objDIV.style.border = '1px solid #CCC';

		var sCode = '';

		sCode += '<div style="position:relative; top:-3px;">';
			sCode += '<input type="checkbox" id="no_pro_emp_' + aEmployees[i]['id'] + '" style="position:relative; top:3px;" /> ';
			sCode += aEmployees[i]['name'] + ' (' + aEmployees[i]['sektion'] + ' - ' + aEmployees[i]['position'] + ')';
		sCode += '</div>';

		objDIV.innerHTML = sCode;
	}
}

/* ====================================================================== */

function addProjectPosition()
{
	if(bProjectClosed)
	{
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = '?task=add_project_position';
	strParameters += '&project_id='		+ iProjectID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : addProjectPositionCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function addProjectPositionCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	aProjectPositions = arrData['aPositions'];

	createProjectPositionsList(arrData['aPositions']);
}

/* ====================================================================== */

function setContactPersons(aPersons)
{
	// Delete old showed contact persons
	$('contact_persons').innerHTML = '';

	// Set Variable
	var sContacts = '';

	// Read all contact persons and write DIVs and checkboxes with new contact persons
	for(var i = 0; i < aPersons.length; i++)
	{
		var sChecked = '';
		if(aPersons[i][2] && aPersons[i][2] == 1)
		{
			sChecked = 'checked="checked"';
		}
		sContacts += '<div id="' + aPersons[i][0] + '">';
			sContacts += '<input onclick="manageContacts(this);" type="checkbox" ' + sChecked + ' value="' + aPersons[i][0] + '" />' + aPersons[i][1];
		sContacts += '</div>';
	}

	// Put new contact persons into main div
	$('contact_persons').innerHTML = sContacts;
}

/* ====================================================================== */

function manageContacts(oCBox)
{
	if(bProjectClosed)
	{
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';

	if(oCBox.checked == true)
	{
		// Add contact person
		var strParameters = 'task=add_project_cc';
	}
	else
	{
		// Remove contact person
		var strParameters = 'task=remove_project_cc';
	}

	strParameters += '&project_id='		+ iProjectID;
	strParameters += '&cc_id='			+ oCBox.value;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : true
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function activateProjectAccordions()
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

function manageProjectPosition(sModus)
{
	if(bProjectClosed)
	{
		return;
	}

	if(sModus == 'copy')
	{
		for(var i = 0; i < aProjectPositions.length; i++)
		{
			if('pos_tr_' + aProjectPositions[i]['id'] == iProjectPositionID)
			{
				if(aProjectPositions[i]['doc_position_id'] > 0 && aProjectPositions[i]['title'] != '')
				{
					copyProjectPosition(iProjectPositionID);
				}
				else
				{
					alert('Die Ableitung von diesem Eintrag ist nicht möglich!');
					return;
				}
			}
		}
	}
	else if(sModus == 'delete')
	{
		for(var i = 0; i < aProjectPositions.length; i++)
		{
			if('pos_tr_' + aProjectPositions[i]['id'] == iProjectPositionID)
			{
				if(confirm('Möchten Sie wirklich die markierte Position löschen?') == false)
				{
					return;
				}
				deleteProjectPosition(aProjectPositions[i]);
				break;
			}
		}
		iProjectPositionID = 0;
	}
}

/* ====================================================================== */

function copyProjectPosition(iTmpID)
{
	if(bProjectClosed)
	{
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=copy_project_position';
	strParameters += '&project_id='		+ iProjectID;
	strParameters += '&position_id='	+ iTmpID.replace(/pos_tr_/, '');

	if($('pos_alias_' + iTmpID.replace(/pos_tr_/, '')).value == '')
	{
		alert('Bitte ein Alias eingeben!');
		return;
	}

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : addProjectPositionCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */
function str_pad(number, length) {
   
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
   
    return str;

}

function getFormatedTimes(iSeconds) {

	var iOverflowH		= iSeconds % 3600;
	
	var aTimes = new Object;
	
	aTimes['H']	= iSeconds - iOverflowH;
	aTimes['H']	= Math.round(aTimes['H'] / 3600);
	iOverflowM	= iOverflowH % 60;
	iOverflowH	= iOverflowH - iOverflowM;
	aTimes['M']	= iOverflowH / 60;
	aTimes['S']	= iOverflowM;

	aTimes['H'] = str_pad(aTimes['H'], 2);
	aTimes['M'] = str_pad(aTimes['M'], 2);
	aTimes['S'] = str_pad(aTimes['S'], 2);
	aTimes['T'] = aTimes['H']+':'+aTimes['M']+':'+aTimes['S'];
	aTimes['O'] = iSeconds;

	return aTimes;

}

function formattedDateToDbDate(sFormattedDate) {
	if (sFormattedDate.length > 0) {
		var matches = sFormattedDate.match(/^(0?[1-9]|[12][0-9]|3[01])[\/\-\.](0?[1-9]|1[012])[\/\-\.](\d{4})$/);
		if (matches[1] && matches[2] && matches[3]) {
			return [matches[3], matches[2], matches[1]].join('-');
		}
	}

	return null;
}

function compareDates(date1, date2) {
	date1 = parseInt(date1.replaceAll('-', ''));
	date2 = parseInt(date2.replaceAll('-', ''));
	console.log(date1, date2)
	if (date1 < date2) return -1;
	if (date1 > date2) return 1;
	return 0;
}

function updateProjectAnalytics() {

	var iEmployeeId = document.getElementById('employee').value;
	var sTimeFrom = formattedDateToDbDate(document.getElementById('employee_time_from').value);
	var sTimeUntil = formattedDateToDbDate(document.getElementById('employee_time_until').value);

	var aNewTasks = new Array();

	var aNewEmployeeSums = null;

	if(iEmployeeId == '' && !sTimeFrom && !sTimeUntil) {
		aNewTasks = aProjectTimes;
		if(aEmployeeSums.length > 0) {
			aNewEmployeeSums = aEmployeeSums.clone();
		}
	} else {	
		aProjectTimes.each(function(aTask) {
			if(aTask.times.length > 0) {

				var aNewTask = Object.clone(aTask);

				var aTimes = new Array();
				var iSeconds = 0;
				var fPrice = 0;
				aTask.times.each(function(aTime) {
					var add = true;

					if (iEmployeeId > 0 && aTime.employee_id != iEmployeeId) {
						add = false;
					}
					if (add === true && sTimeFrom && compareDates(sTimeFrom, aTime.end.substring(0, 10)) === 1) {
						add = false;
					}
					if (add === true && sTimeUntil && compareDates(sTimeUntil, aTime.start.substring(0, 10)) === -1) {
						add = false;
					}

					if(add) {
						aTimes[aTimes.length] = aTime;
						iSeconds += parseInt(aTime.time.O);
						fPrice += parseFloat(aTime.price);
					}
				});
				if(aTimes.length > 0) {
					aNewTask.times = aTimes;
					aNewTask.time = getFormatedTimes(iSeconds);
					aNewTask.price = fPrice;
					aNewTasks[aNewTasks.length] = aNewTask;
				}
			}

		});
		
	}

	fillProjectAnalytics(aNewTasks, aNewEmployeeSums);

}

function fillProjectAnalytics(aTimes, aNewEmployeeSums) {

	var tbody 		= document.getElementById('tbl_project_analytics');
	var tBodyEmployees	= document.getElementById('tbl_project_employee_sums');
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);
	var objGUI		= new GUI;

    var td0, td1, td2, td3;

	// Remove all entries
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}
	while(tBodyEmployees.hasChildNodes()) {
		tBodyEmployees.removeChild(tBodyEmployees.firstChild);
	}

	var fTotalPlanned = 0;
	var fTotalUsed = 0;
	var fTotalCosts = 0;

	for(var i = 0; i < aTimes.length; i++) {

		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);

		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		var sTitle = '';
		if(aTimes[i]['alias'] != '' && aTimes[i]['alias'] != null)
		{
			sTitle += aTimes[i]['alias'] + ' - ';
		}
		sTitle += aTimes[i]['title'];

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = '<b>' + sTitle + '&nbsp;</b>';

		var aTime = aTimes[i]['planed_amount'];

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = '<b>' + aTime['H'] + ':' + aTime['M'] + ':' + aTime['S'] + '</b>';
		td1.style.textAlign = 'right';

		aTime = aTimes[i]['time'];

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = '<b>' + aTime['H'] + ':' + aTime['M'] + ':' + aTime['S'] + '</b>';
		td2.style.textAlign = 'right';

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = '<b>' + parseFloat(aTimes[i]['price']).number_format(2, ',', '.') + ' €</b>';
		td3.style.textAlign = 'right';

		fTotalPlanned += parseInt(aTimes[i]['planed_amount']['O']);
		fTotalUsed += parseInt(aTimes[i]['time']['O']);
		fTotalCosts += parseFloat(aTimes[i]['price']);

		aEmpTimes = aTimes[i]['times'];

		for(var n = 0; n < aEmpTimes.length; n++) {

			objTr = tr.cloneNode(false);
			tbody.appendChild(objTr);

			Event.observe(objTr, 'mouseout', resetHighlightRow);
			Event.observe(objTr, 'mousemove', setHighlightRow);

			td0 = document.createElement("td");
			objTr.appendChild(td0);
			td0.innerHTML = '&nbsp;&nbsp;-&nbsp;' + aEmpTimes[n]['firstname'] + ' ' + aEmpTimes[n]['lastname'];

			var aTime = aEmpTimes[n]['planed_amount'];

			td1 = document.createElement("td");
			objTr.appendChild(td1);
			td1.innerHTML = '&nbsp;';

			aTime = aEmpTimes[n]['time'];

			td2 = document.createElement("td");
			objTr.appendChild(td2);
			td2.innerHTML = aTime['H'] + ':' + aTime['M'] + ':' + aTime['S'];
			td2.style.textAlign = 'right';

			td3 = document.createElement("td");
			objTr.appendChild(td3);
			td3.innerHTML = parseFloat(aEmpTimes[n]['price']).number_format(2, ',', '.') + ' €';
			td3.style.textAlign = 'right';
		}

		if(aTimes[i+1])	{

			objTr = tr.cloneNode(false);
			tbody.appendChild(objTr);
			td4 = document.createElement("td");
			objTr.appendChild(td4);
			td4.innerHTML = '&nbsp;';
			td4.colSpan = '4';
		}

	}

	$('sum-hours-planned').update(getFormatedTimes(fTotalPlanned)['T']);
	$('sum-hours-used').update(getFormatedTimes(fTotalUsed)['T']);
	$('sum-costs').update(parseFloat(fTotalCosts).number_format(2, ',', '.') + ' €');

	if(
		aNewEmployeeSums &&
		aNewEmployeeSums.length > 0
	) {
		
		aNewEmployeeSums.each(function(aNewEmployeeSum) {

			objTr = tr.cloneNode(false);
			tBodyEmployees.appendChild(objTr);
			
			Event.observe(objTr, 'mouseout', resetHighlightRow);
			Event.observe(objTr, 'mousemove', setHighlightRow);

			td0 = document.createElement("td");
			objTr.appendChild(td0);
			td0.innerHTML = aNewEmployeeSum.name;

			td1 = document.createElement("td");
			objTr.appendChild(td1);
			td1.innerHTML = '&nbsp;';

			var aTime = aNewEmployeeSum['time'];

			td2 = document.createElement("td");
			objTr.appendChild(td2);
			td2.innerHTML = aTime['H'] + ':' + aTime['M'] + ':' + aTime['S'];
			td2.style.textAlign = 'right';

			td3 = document.createElement("td");
			objTr.appendChild(td3);
			td3.innerHTML = parseFloat(aNewEmployeeSum['price']).number_format(2, ',', '.') + ' €';
			td3.style.textAlign = 'right';
			
		});
		
	}

}

/* ====================================================================== */

function createEmployeesList(aEmployees)
{
	var tbody 		= document.getElementById('tbl_project_employees');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);
	var objGUI		= new GUI;

    var td, td0, td1, td2;

	// Remove all positions
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	var bCheck = false;

	aEmployees = oWdHooks.executeHook('display_employee_email_checkbox', aEmployees);

	if(aEmployees.length > 0)
	{
		// Create new employees list
		for(var i = 0; i < aEmployees.length; i++)
		{
			if(aEmployees[i]['employees'].length > 0)
			{
				objTr = tr.cloneNode(false);
				tbody.appendChild(objTr);
				td = document.createElement("td");
				objTr.appendChild(td);
				td.innerHTML = '<b>' + aEmployees[i]['group'] + '</b>';
				td.colSpan = '4';
				td.style.textAlign = 'center';
				td.style.height = '40px';

				bCheck = true;

				for(var n = 0; n < aEmployees[i]['employees'].length; n++)
				{
					objTr = tr.cloneNode(false);
					tbody.appendChild(objTr);
					var strId = 'emp_tr_' + aEmployees[i]['employees'][n]['id'];
					objTr.id = strId;

					Event.observe(objTr, 'click', checkProjectEmployeeRow.bindAsEventListener(c, strId));
					Event.observe(objTr, 'mouseout', resetHighlightRow);
					Event.observe(objTr, 'mousemove', setHighlightRow);

					td0 = document.createElement("td");
					objTr.appendChild(td0);
					td0.innerHTML = aEmployees[i]['employees'][n]['name'];

					td1 = document.createElement("td");
					objTr.appendChild(td1);
					td1.innerHTML = aEmployees[i]['employees'][n]['sektion'] + '&nbsp;';

					td2 = document.createElement("td");
					objTr.appendChild(td2);
					td2.innerHTML = aEmployees[i]['employees'][n]['position'] + '&nbsp;';

					td0 = td1 = td2 = null;
				}
			}

			td = null;
		}
	}

	if(!bCheck)
	{
		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		td = document.createElement("td");
		objTr.appendChild(td);
		td.innerHTML = '<b>Zur Zeit sind keine Mitarbeiter zugewiesen.</b>';
		td.colSpan = '3';
		td.style.textAlign = 'center';
	}
}

/* ====================================================================== */

function createProjectPositionsList(aPositions)
{
	var tbody 		= document.getElementById('tbl_positions');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);
	var objGUI		= new GUI;

    var td0, td1, td2, td3, td4;

	// Remove all positions
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	if(aPositions.length > 0)
	{
		// Create new positions
		for(var i = 0; i < aPositions.length; i++, c++)
		{
			if(aPositions[i]['task'] && aPositions[i]['task'] == 'delete')
			{
				// do not display
			}
			else
			{
				objTr = tr.cloneNode(false);
				tbody.appendChild(objTr);
				var strId = 'pos_tr_' + aPositions[i]['id'];
				objTr.id = strId;
				
				Event.observe(objTr, 'click', checkProjectPositionRow.bindAsEventListener(c, strId));
				//Event.observe(objTr, 'dblclick', checkProjectPositionRow.bindAsEventListener(c, aPositions[i]));
				Event.observe(objTr, 'mouseout', resetHighlightRow);
				Event.observe(objTr, 'mousemove', setHighlightRow);
				
				td0 = document.createElement("td");
				objTr.appendChild(td0);
				td0.innerHTML = aPositions[i]['title'] + '&nbsp;';
				
				td1 = document.createElement("td");
				objTr.appendChild(td1);
				if(aPositions[i]['alias'] == '' && aPositions[i]['title'] == '' && aPositions[i]['doc_position_id'] != 0)
				{
					td1.innerHTML = '&nbsp;';
				}
				else
				{
					td1.innerHTML = '<input class="txt" id="pos_alias_' + aPositions[i]['id'] + '" value="' + aPositions[i]['alias'] + '" onblur="setPositionValues(this.id, \'alias\')" />';
				}
				
				td2 = document.createElement("td");
				objTr.appendChild(td2);
				if(aPositions[i]['alias'] == '' && aPositions[i]['title'] == '' && aPositions[i]['doc_position_id'] != 0)
				{
					td1.innerHTML = '&nbsp;';
				}
				else 
				{
					td2.innerHTML = parseFloat(aPositions[i]['amount']).number_format(2, ',', '.') + ' ' + aPositions[i]['unit'];
				}

				td3 = document.createElement("td");
				objTr.appendChild(td3);
				td3.innerHTML = '<input class="txt" id="pos_planed_' + aPositions[i]['id'] + '" value="' + parseFloat(aPositions[i]['planed_amount']).number_format(2, ',', '.') + '" onblur="setPositionValues(this.id, \'amount\')" style="width:50px;" /> Std.';

				td4 = document.createElement("td");
				objTr.appendChild(td4);

				strCode = '<select class="txt" id="pos_activity_' + aPositions[i]['id'] + '" onchange="setPositionValues(this.id, \'activity\')">';
				for(var n = 0; n < aActivities.length; n++)
				{
					strSelected = '';
					if(aPositions[i]['category_id'] == aActivities[n][0])
					{
						strSelected = 'selected="selected"';
					}
					strCode += '<option value="' + aActivities[n][0] + '" '+strSelected+'>' + aActivities[n][1] + '</option>';
				}
				strCode += '</select>';
				td4.innerHTML = strCode;

				td0 = td1 = td2 = td3 = td4 = null;
			}
		}
	}
	else
	{
		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.colSpan = '5';
		td0.innerHTML = '<div style="text-align:center;"><b>Die Liste ist zur Zeit leer.</b></div>';
	}
}

/* ==================================================================================================== */

var bInAction = false;
function setPositionValues(iTmpPosiitonID, sField)
{
	if(bProjectClosed)
	{
		return;
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	if(sField == 'alias')
	{
		strParameters = '?task=save_position_alias';
		strParameters += '&alias=' 			+ encodeURIComponent($(iTmpPosiitonID).value);
		strParameters += '&position_id=' 	+ iTmpPosiitonID.replace(/pos_alias_/, '');
	}
	if(sField == 'amount')
	{
		strParameters = '?task=save_position_amount';
		strParameters += '&amount='			+ $(iTmpPosiitonID).value.replace(/\./g, '').replace(/,/, '.');
		strParameters += '&position_id='	+ iTmpPosiitonID.replace(/pos_planed_/, '');
	}
	if(sField == 'activity')
	{
		strParameters = '?task=save_position_activity';
		strParameters += '&activity='		+ $(iTmpPosiitonID).value;
		strParameters += '&position_id='	+ iTmpPosiitonID.replace(/pos_activity_/, '');
	}
	strParameters += '&project_id='		+ iProjectID;

	bInAction = true;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : setPositionValuesCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function setPositionValuesCallback()
{
	bInAction = false;
}








/* ==================================================================================================== */
/* ==================================================================================================== */
/* ==================================================================================================== */

var loadProjectsListObserver;
var loadProjectsListEventElement;
var selectedProjectRow = 0;
var iProjectEmployeeID = 0;
var selectedProjectEmployeeRow = 0;
var selectedProjectPositionRow = 0;

var arrProjectIcons = ['toolbar_edit',
	'toolbar_delete',
	'toolbar_finish',
	'toolbar_analysis',
	'toolbar_copy'];
var arrProjectIconState = [];
var bCheckProjectToolbarInProgress = 0;

/* ====================================================================== */

function checkProjectEmployeeRow(c, strId)
{
	var objRow = $(strId);

	if(selectedProjectEmployeeRow && $(selectedProjectEmployeeRow))
	{
		$(selectedProjectEmployeeRow).className = '';
	}

	if(!objRow.hasClassName('selectedRow')) {
		objRow.addClassName('selectedRow');
	}

	selectedProjectEmployeeRow = strId;

	iProjectEmployeeID = selectedProjectEmployeeRow.replace(/emp_tr_/, '');
}

/* ====================================================================== */

function checkProjectPositionRow(c, strId)
{
	var objRow = $(strId);

	if(
		selectedProjectPositionRow && 
		$(selectedProjectPositionRow)
	) {
		$(selectedProjectPositionRow).className = '';
	}

	if(!objRow.hasClassName('selectedRow')) {
		objRow.addClassName('selectedRow');
	}

	iProjectPositionID = selectedProjectPositionRow = strId;
}

/* ====================================================================== */

function prepareLoadProjectsList(oEvent)
{
	if(oEvent)
	{
		loadProjectsListEventElement = oEvent;
	}

	if(loadProjectsListObserver)
	{
		clearTimeout(loadProjectsListObserver);
		loadProjectsListEventElement = null;
	}

	loadProjectsListObserver = setTimeout(loadProjectsList.bind(), 500);
}

/* ====================================================================== */

function loadProjectsList()
{
	if($('toolbar_loading'))
	{
		$('toolbar_loading').show();
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_projects';
	strParameters += '&search='       + encodeURIComponent($('project_search').value);
	strParameters += '&state='        + encodeURIComponent($('project_state').value);
	strParameters += '&from='         + encodeURIComponent($('project_from').value);
	strParameters += '&to='           + encodeURIComponent($('project_to').value);
    strParameters += '&product_area=' + encodeURIComponent($('product_area').value);

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : loadProjectsListCallback
							}
	); 
}

/* ====================================================================== */

function loadProjectsListCallback(objResponse)
{
	var objData 	= objResponse.responseText.evalJSON();
	var arrList 	= objData['data'];
	var rowCnt 		= arrList.length;
	var tbody 		= document.getElementById("tbl_projects");

	while(tbody.hasChildNodes())
	{
		tbody.removeChild(tbody.firstChild);
	}

    var floTotal 	= 0;
	var c 			= 0;
	var tr    		= document.createElement("tr");
    var objTr   	= tr.cloneNode(true);

	aLoadedProjectsSelectOptions = [];

    for(var i = 0; i < rowCnt; i++) {
        objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);
        var strId = "tr_"+arrList[i]['id'];
        objTr.id = strId;
        Event.observe(objTr, 'click', checkProjectRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', executeProjectAction.bindAsEventListener(c, 'edit'));
		Event.observe(objTr, 'mouseout', resetHighlightRow); 
		Event.observe(objTr, 'mousemove', setHighlightRow);
		addProjectsListCells(objTr, i, arrList[i]);
		c++;
		aLoadedProjectsSelectOptions.push([arrList[i]['id'], arrList[i]['title']]);
    }
    tbody = null;

	if(loadProjectsListEventElement)
	{
		loadProjectsListEventElement.focus();
		loadProjectsListEventElement.value = loadProjectsListEventElement.value;
	}

	selectedProjectRow = 0;

	checkProjectToolbar();

	$('toolbar_loading').hide();
}

/* ====================================================================== */

function addProjectsListCells(tr, cnt, arrList)
{
	
	var td0 = document.createElement('td');
	tr.appendChild(td0);
	if(arrList['closed_date'] > 0)
	{
		td0.innerHTML = '<img src="/admin/media/office_finished.png" alt="Geschlossen" title="Geschlossen" />';
	} 
	else 
	{
		td0.innerHTML = '<img src="/admin/media/bullet_green.png" alt="Laufend" title="Laufend" />';
	}

	var td1 = document.createElement('td');
	tr.appendChild(td1);
	td1.appendChild(document.createTextNode(arrList['title']));

	var td2 = document.createElement('td');
	tr.appendChild(td2);
	td2.appendChild(document.createTextNode(arrList['category']));

	var td3 = document.createElement('td');
	tr.appendChild(td3);
	td3.appendChild(document.createTextNode(arrList['company']));

	var td4 = document.createElement('td');
	tr.appendChild(td4);
	td4.appendChild(document.createTextNode(arrList['product_area_name']));

	var td5 = document.createElement('td');
	tr.appendChild(td5);
	td5.appendChild(document.createTextNode(arrList['editor']));

	var td6 = document.createElement('td');
	tr.appendChild(td6);
	td6.appendChild(document.createTextNode(arrList['start_date'] + ' - ' + arrList['end_date']));

	var td7 = document.createElement('td');
	tr.appendChild(td7);
	td7.appendChild(document.createTextNode(parseFloat(arrList['budget']).number_format(2, ',', '.') + ' €'));
	td7.style.textAlign = 'right';

	if(arrList['consumed_budget']) {
		var td8 = document.createElement('td');
		tr.appendChild(td8);
		td8.appendChild(document.createTextNode(parseFloat(arrList['consumed_budget']).number_format(2, ',', '.') + ' €'));
		td8.style.textAlign = 'right';

		// higlight row if project is bad
		if(parseFloat(arrList['budget']) > 0) {
			if(parseFloat(arrList['budget']) < parseFloat(arrList['consumed_budget'])) {
				td8.style.backgroundColor = '#ff7373';
			} else {
				td8.style.backgroundColor = '#67e667';
			}
		}
	}

	if(arrList['costs']) {
		var td9 = document.createElement('td');
		tr.appendChild(td9);
		td9.appendChild(document.createTextNode(parseFloat(arrList['costs']).number_format(2, ',', '.') + ' €'));
		td9.style.textAlign = 'right';

		// higlight row if project is bad
		if(parseFloat(arrList['budget']) > 0) {
			if(parseFloat(arrList['budget']) < parseFloat(arrList['costs'])) {
				td9.style.backgroundColor = '#ff7373';
			} else if(parseFloat(arrList['costs']) < (parseFloat(arrList['budget']) / 2) ) {
				td9.style.backgroundColor = '#67e667';
			} else if(parseFloat(arrList['costs']) < parseFloat(arrList['budget'])) {
				td9.style.backgroundColor = '#A9F3A9';
			}
		}
	}

	td0 = td1 = td2 = td3 = td4 = td5 = td6 = td7 = td8 = td9 = null;
}

/* ====================================================================== */

function checkProjectRow(e, strId) {

	var objRow = $(strId);

	if(
		selectedProjectRow && 
		$(selectedProjectRow)
	) {
		$(selectedProjectRow).className = '';
	}

	if(objRow.hasClassName('selectedRow')) {
		objRow.className = '';
		selectedProjectRow = null;
	} else {
		objRow.className = 'selectedRow';
		selectedProjectRow = strId;
	}

	checkProjectToolbar();
}

/* ====================================================================== */

function executeProjectAction(strId, strAction)
{
	if(bCheckProjectToolbarInProgress)
	{
		window.setTimeout("executeProjectAction('" + strId + "', '" + strAction + "')", 100);
		return false;
	}

	if(strAction != 'new' && strAction != 'analysis_export_times' && !arrProjectIconState['toolbar_' + strAction])
	{
		alert("Diese Aktion ist nicht zulässig!");
		return false;
	}

	var intProjectId;

	if(strAction != 'new' && selectedProjectRow)
	{
		intProjectId = selectedProjectRow.replace(/tr_/, '');
	}

	switch(strAction)
	{
		case 'new':
		{
			iProjectID = 0;
			openProjectDialog();
			break;
		}
		case 'edit':
		{
			iProjectID = intProjectId;
			openProjectDialog();
			break;
		}
		case 'copy':
		{
			iProjectID = intProjectId;
			copyProject();
			break;
		}
		case 'analysis':
		{
			iProjectID = intProjectId;
			openProjectAnalysisDialog();
			break;
		}
		case 'analysis_export_times':
		{
			doProjectAnalysisTimesExport();
			break;
		}
		case 'delete':
		{
			iProjectID = intProjectId;
			deleteProject();
			break;
		}
		case 'finish':
		{
			iProjectID = intProjectId;
			openProjectCloseDialog();
			break;
		}
	}
}

/* ====================================================================== */

function checkProjectsListHeight()
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

	var objTable = $('tableProjects-body');

	if(objTable)
	{
		objTable.style.height = intHeight+'px';	
	}
	else
	{
		document.write('<style>.scroll-table-body { height: '+intHeight+'px; }</style>');
	}
}

/* ====================================================================== */

function checkProjectToolbar()
{
	if(!bCheckProjectToolbarInProgress)
	{
		bCheckProjectToolbarInProgress = 1;

		if(selectedProjectRow)
		{
			var strRequestUrl = '/admin/extensions/office.ajax.php';
			var strParameters = 'task=check_project_toolbar&project_id=' + selectedProjectRow.replace(/tr_/, '');

			var objAjax = new Ajax.Request(
									strRequestUrl,
									{
										method : 'post',
										parameters : strParameters,
										onComplete : checkProjectToolbarCallback
									}
			);

		}
		else
		{
			arrProjectIcons.each(function(strIcon)
			{
				switchProjectToolbarIcon(strIcon, 0);
			});

			bCheckProjectToolbarInProgress = 0;
		}
	}
}

function checkProjectToolbarCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrList = objData['data'];

	arrProjectIcons.each(function(strIcon) {
		var bolShow = arrList[strIcon];
		switchProjectToolbarIcon(strIcon, bolShow);
	});

	bCheckProjectToolbarInProgress = 0;
}

function switchProjectToolbarIcon(strIcon, bolShow)
{
	var objIcon = $(strIcon);

	if(bolShow)
	{
		if(arrProjectIconState[strIcon] == undefined || arrProjectIconState[strIcon] == 0)
		{
			$j(objIcon).fadeIn();
		}
		arrProjectIconState[strIcon] = 1;
	}
	else
	{
		if(arrProjectIconState[strIcon] == undefined || arrProjectIconState[strIcon] == 1)
		{
			$j(objIcon).fadeTo(0.2);
		}
		arrProjectIconState[strIcon] = 0;
	}
}

/* ====================================================================== */

function doProjectAnalysisTimesExport() {

	var aSelectedProjects = [];
	for(var i = 0; i < aLoadedProjectsSelectOptions.length; i++) {
		aSelectedProjects.push(aLoadedProjectsSelectOptions[i][0]);
	}

	var oGUI = new GUI;
	var sHtml = '';

	sHtml += '<form id="ProjectAnalysisTimesExport" action="/admin/extensions/office_projects.html?task=analysis_export" target="_blank" method="POST">';
	sHtml += oGUI.startFieldset('Projektauswahl');
	sHtml += '<div style="text-align:center;margin-bottom:5px;">';
	sHtml += oGUI.printFormMultiSelect('Projekte', 'selected_projects[]', aLoadedProjectsSelectOptions, aSelectedProjects, 'style="width:400px;" multiple size="10"');
	sHtml += '</div>';
	sHtml += oGUI.endFieldset();
	sHtml += oGUI.printFormButton('XLS erstellen', 'document.forms["ProjectAnalysisTimesExport"].submit();', 'submit_button', 'style="opacity:1; filter:alpha(opacity=100);"');
	sHtml += '</form>';

	objDialogBox = new LITBox(sHtml, {type:'alert', overlay:true, height:350, width:700, resizable:false, opacity:.9});

}
