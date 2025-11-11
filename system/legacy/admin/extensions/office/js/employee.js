// Globals
var iEmployeeID = 0;
var loadEmployeesListObserver;
var loadEmployeesListEventElement;
var selectedEmployeeRow = 0;
var selectedHolidayRow = 0;
var selectedEmployeePositionRow = 0;
var bCheckEmployeeToolbarInProgress = 0;
var arrEmployeeIcons = ['toolbar_edit',
	'toolbar_delete', 'toolbar_holiday', 'toolbar_timeclock', 'toolbar_factors'];
var arrEmployeeIconState = [];
var usercookie = '';
var passcookie = '';
var aContracts = new Array();
var aHoliday = new Array();
var iContractsLength = '';
var selectedGroups = new Array();

// Major version of Flash required
var requiredMajorVersion = 9;
// Minor version of Flash required
var requiredMinorVersion = 0;
// Minor version of Flash required
var requiredRevision = 0;

/* ====================================================================== */


function deleteImage(iEmployeeID, filename)
{
	var strRequestUrl 	= 	'/admin/extensions/office.ajax.php';
	var strParameters 	= 	'task=delete_employee_file'
		strParameters	+=	'&employee_id='	+ iEmployeeID;
		strParameters 	+= 	'&filename='	+ filename;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: refreshEmployeeFileListCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function refreshEmployeeFileList(iEmployeeID)
{
	var strRequestUrl 	= 	'/admin/extensions/office.ajax.php';
	var strParameters 	= 	'task=refresh_file_list'
		strParameters	+=	'&employee_id='	+ iEmployeeID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: refreshEmployeeFileListCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function prepareLoadEmployeesList(oEvent)
{
	if(oEvent)
	{
		loadEmployeesListEventElement = oEvent;
	}

	if(loadEmployeesListObserver)
	{
		clearTimeout(loadEmployeesListObserver);
	}

	loadEmployeesListObserver = setTimeout(loadEmployeesList.bind(), 500);
}

/* ====================================================================== */

function checkEmployeesListHeight()
{
	var intHeight = window.innerHeight;

	if(!intHeight)
	{
		intHeight = document.body.clientHeight;
	}
	if(!intHeight)
	{
		intHeight = document.documentElement.clientHeight;
	}
	intHeight = intHeight - 135;

	var objTable = $('tableEmployees-body');

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

function loadEmployeesList()
{
	if($('toolbar_loading'))
	{
		$('toolbar_loading').style.display = 'block';
	}

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_employees';
	strParameters += '&search='	+ encodeURIComponent($('employee_search').value);
	strParameters += '&sign='	+ encodeURIComponent($('employee_sign').value);
	strParameters += '&limit='	+ encodeURIComponent($('employee_limit').value);
	strParameters += '&group='	+ encodeURIComponent($('employee_group').value);

	var objAjax = new Ajax.Request(
							strRequestUrl,
							{
								method : 'post',
								parameters : strParameters,
								onComplete : loadEmployeesListCallback
							}
	);
}

/* ====================================================================== */

function loadEmployeesListCallback(objResponse)
{
	var objData 	= objResponse.responseText.evalJSON();
	var arrList 	= objData['data'];
	var rowCnt 		= arrList.length;
	var tbody 		= document.getElementById("tbl_employees");

	while(tbody.hasChildNodes())
	{
		tbody.removeChild(tbody.firstChild);
	}

    var floTotal 	= 0;
	var c 			= 0;
	var tr    		= document.createElement("tr");
    var objTr   	= tr.cloneNode(true);

    for(var i = 0; i < rowCnt; i++)
	{
        objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);
        var strId = "tr_"+arrList[i]['id'];
        objTr.id = strId;
        Event.observe(objTr, 'click', checkEmployeeRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', executeEmployeeAction.bindAsEventListener(c, 'edit'));
		Event.observe(objTr, 'mouseout', resetHighlightRow); 
		Event.observe(objTr, 'mousemove', setHighlightRow);
		addEmployeesListCells(objTr, i, arrList[i]);
		c++;
    }
    tbody = null;

	if(loadEmployeesListEventElement)
	{
		loadEmployeesListEventElement.focus();
		loadEmployeesListEventElement.value = loadEmployeesListEventElement.value;
	}

	selectedEmployeeRow = 0;

	checkEmployeeToolbar();

	$('toolbar_loading').hide();
}

/* ====================================================================== */

function addEmployeesListCells(tr, cnt, arrList)
{
	var Sex = '';
	if(arrList['sex'] == 0){Sex = 'Herr';}
	if(arrList['sex'] == 1){Sex = 'Frau';}

	var td0 = document.createElement('td');
	tr.appendChild(td0);
	td0.appendChild(document.createTextNode(arrList['id']));

	var td1 = document.createElement('td');
	tr.appendChild(td1);
	td1.appendChild(document.createTextNode(Sex));

	var td2 = document.createElement('td');
	tr.appendChild(td2);
	td2.appendChild(document.createTextNode(arrList['name']+' '));

	var td3 = document.createElement('td');
	tr.appendChild(td3);
	td3.appendChild(document.createTextNode(arrList['date_o_b'] + ' ' + arrList['age']));

	var td4 = document.createElement('td');
	tr.appendChild(td4);
	td4.appendChild(document.createTextNode(arrList['phone']+' '));

	var td5 = document.createElement('td');
	tr.appendChild(td5);
	td5.appendChild(document.createTextNode(arrList['mobile']+' '));

	var td6 = document.createElement('td');
	tr.appendChild(td6);
	td6.appendChild(document.createTextNode(arrList['email']+' '));

	var td7 = document.createElement('td');
	tr.appendChild(td7);
	td7.appendChild(document.createTextNode(arrList['sektion']+' '));

	td0 = td1 = td2 = td3 = td4 = td5 = td6 = td7 = null;
}

/* ====================================================================== */

function checkEmployeeRow(e, strId) {

	var objRow = $(strId);

	iEmployeeID = strId.replace(/tr_/, '');

	if(selectedEmployeeRow && $(selectedEmployeeRow)) {
		$(selectedEmployeeRow).className = '';
	}

	if(!objRow.hasClassName('selectedRow')) {
		objRow.className = 'selectedRow';
	} else {
		objRow.className = '';
	}

	selectedEmployeeRow = strId;

	checkEmployeeToolbar();
}

/* ====================================================================== */

function executeEmployeeAction(strId, strAction)
{
	if(bCheckEmployeeToolbarInProgress)
	{
		window.setTimeout("executeEmployeeAction('" + strId + "', '" + strAction + "')", 100);
		return false;
	}

	if(strAction != 'new' && !arrEmployeeIconState['toolbar_' + strAction])
	{
		alert("Diese Aktion ist nicht zulässig!");
		return false;
	}

	var intEmployeeId;

	if(strAction != 'new' && selectedEmployeeRow)
	{
		intEmployeeId = selectedEmployeeRow.replace(/tr_/, '');
	}

	switch(strAction)
	{
		case 'new':
		{
			iEmployeeID = 0;
			openEmployeeDialog();
			break;
		}
		case 'edit':
		{
			iEmployeeID = intEmployeeId;
			openEmployeeDialog();
			break;
		}
		case 'holiday':
		{
			iEmployeeID = intEmployeeId;
			openEmployeeHoliday();
			break;
		}
		case 'timeclock':
		{
			iEmployeeID = intEmployeeId;
			openEmployeeTimeclock();
			break;
		}
		case 'factors':
		{
			iEmployeeID = intEmployeeId;
			openEmployeeFactors();
			break;
		}
		case 'delete':
		{
			iEmployeeID = intEmployeeId;
			deleteEmployee();
			break;
		}
		case 'rating':
		{
			intEmployeeId = oWdHooks.executeHook('open_employee_rating_dialog', intEmployeeId);
			break;
		}
	}
}

/* ====================================================================== */

function openEmployeeFactors()
{
	$('toolbar_loading').show();

	var sParams = 'task=get_factors';

	sParams += '&employee_id=' + iEmployeeID;

	var objAjax = new Ajax.Request(
		'/admin/extensions/office.ajax.php',
		{
			method:		'post',
			parameters:	sParams,
			onComplete:	openEmployeeFactorsCallback
		}
	);
}

function openEmployeeFactorsCallback(oResponse)
{
	var aData	= oResponse.responseText.evalJSON();
	var oGUI	= new GUI;

	var sCode = '';

	sCode += '<div id="factorsContainer">';
		sCode += oGUI.startAccordionContainer('');
			aData.each(function(aEntry)
			{
				sCode += oGUI.startAccordion('Vertrag: ' + aEntry[0]['date']);
					sCode += '<div style="height:200px; overflow-y:scroll; margin: 5px 0; border: 1px solid #CCC;">';
						sCode += '<table cellpadding="0" cellspacing="0" border="0" class="table" style="width:100%;">';
							sCode += '<thead>';
								sCode += '<tr>';
									sCode += '<th>Tätigkeit</th>';
									sCode += '<th style="width:70px;">Faktor</th>';
								sCode += '</tr>';
							sCode += '</thead>';
							sCode += '<tbody>';
								aEntry.each(function(aLine)
								{
									sCode += '<tr>';
										sCode += '<td>' + aLine['title'] + '</td>';
										sCode += '<td><input style="margin:0;" class="txt w50 factorsInput" id="' + aLine['contract_id'] + '_' + aLine['category_id'] + '" value="' + aLine['factor'] + '" /> %</td>';
									sCode += '</tr>';
								});
							sCode += '</tbody>';
						sCode += '</table>';
					sCode += '</div>';
				sCode += oGUI.endAccordion();
			});
		sCode += oGUI.endAccordionContainer('');
	sCode += '</div>';

	if($('factorsContainer'))
	{
		$('factorsContainer').replace(sCode);
	}
	else
	{
		sCode += '<div style="text-align:right; margin-right:10px;">';
			sCode += '<input class="btn" type="button" onclick="saveFactors();" value="Speichern" style="opacity:1; filter:alpha(opacity=100);" />';
		sCode += '</div>';

		oFactorsDialog = new LITBox(sCode, {type: 'alert', overlay: true, height: 400, width: 750, resizable: false, opacity: .9});
	}

	var oAccordion = new accordion('divAccordionContainer',
	{
		classNames:
		{
			toggle:			'accordionTitle',
			toggleActive:	'accordionTitleActive',
			content:		'accordionContent'
		}
	});

	oAccordion.switchContainer($('divAccordionContainer').down(0));

	$('toolbar_loading').hide();
}

function saveFactors()
{
	$('toolbar_loading').show();

	var sParams = 'task=save_factors';

	var aInputs = $A($$('.factorsInput'));

	sParams += '&employee_id=' + iEmployeeID;

	aInputs.each(function(oInput)
	{
		sParams += '&factors[' + oInput.id + ']=' + oInput.value;
	});

	var objAjax = new Ajax.Request(
		'/admin/extensions/office.ajax.php',
		{
			method:		'post',
			parameters:	sParams,
			onComplete:	saveFactorsCallback
		}
	);
}

function saveFactorsCallback()
{
	openEmployeeFactors();
}

/* ====================================================================== */

function checkEmployeeToolbar()
{
	arrEmployeeIcons = oWdHooks.executeHook('display_employee_rate_icon', arrEmployeeIcons, 'toolbar_rating');

	if(!bCheckEmployeeToolbarInProgress)
	{
		bCheckEmployeeToolbarInProgress = 1;

		if(selectedEmployeeRow)
		{
			var strRequestUrl = '/admin/extensions/office.ajax.php';
			var strParameters = 'task=check_employee_toolbar&employee_id=' + iEmployeeID;

			var objAjax = new Ajax.Request(
									strRequestUrl,
									{
										method : 'post',
										parameters : strParameters,
										onComplete : checkEmployeeToolbarCallback
									}
			);

		}
		else
		{
			arrEmployeeIcons.each(function(strIcon)
			{
				switchEmployeeToolbarIcon(strIcon, 0);
			});

			bCheckEmployeeToolbarInProgress = 0;
		}
	}
}

function checkEmployeeToolbarCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrList = objData['data'];

	arrEmployeeIcons.each(function(strIcon) {
		var bolShow = arrList[strIcon];
		switchEmployeeToolbarIcon(strIcon, bolShow);
	});

	bCheckEmployeeToolbarInProgress = 0;
}

function switchEmployeeToolbarIcon(strIcon, bolShow)
{
	var objIcon = $(strIcon);

	if(bolShow)
	{
		if(arrEmployeeIconState[strIcon] == undefined || arrEmployeeIconState[strIcon] == 0)
		{
			$j(objIcon).fadeTo('fast', 1);
		}
		arrEmployeeIconState[strIcon] = 1;
	}
	else
	{
		if(arrEmployeeIconState[strIcon] == undefined || arrEmployeeIconState[strIcon] == 1)
		{
			$j(objIcon).fadeTo('fast', 0.2);;
		}
		arrEmployeeIconState[strIcon] = 0;
	}
}

/* ====================================================================== */

function openEmployeeTimeclock()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_employee_timeclock';
	strParameters += '&employee_id=' + iEmployeeID;

	if($('acc_1_month') && $('acc_1_year'))
	{
		strParameters += '&month=' + $('acc_1_month').value;
		strParameters += '&year=' + $('acc_1_year').value;
	}

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openEmployeeTimeclockCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function deleteEmployee() {

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=delete_employee&employee_id=' + iEmployeeID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: loadEmployeesList
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function openEmployeeDialog(iDocumentID)
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_employee_data&employee_id=' + iEmployeeID;

	if(iDocumentID)
	{
		strParameters += '&document_id=' + iDocumentID;
	}

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openEmployeeDialogCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function refreshEmployeeHoliday(iYear)
{
	
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=refresh_holiday_data&employee_id=' + iEmployeeID;
		strParameters += '&year='	+iYear;
	
	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: refreshEmployeeHolidayCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
	
}


/* ====================================================================== */

function refreshEmployeeHolidayCallback(objResponse)
{

	var objData = objResponse.responseText.evalJSON();

	aHoliday = objData['holiday'];
	
	

	if( (aHoliday[0]['togetherholidays'] == null && aHoliday[0]['togethersickdays'] == null) || aHoliday[0]['no_entrys'] == 1)
	{
		var tbody = $('tbl_holidays');
		// Remove all contracts
		while(tbody.hasChildNodes()) {
			tbody.removeChild(tbody.firstChild);
		}

		$('acc_1_successholidays').value			= '';
		$('acc_1_holidaysopen').value				= parseFloat($F('acc_1_allholidays')).number_format(2,',','.');
		$('acc_1_allsickdays').value				= '';
	} else {
		
		createHolidaysList()
		// refresh holiday data
		$('acc_1_allholidays').value				= aHoliday[0]['holiday'];
		$('acc_1_successholidays').value			= aHoliday[0]['togetherholidays'];
		$('acc_1_holidaysopen').value				= parseFloat(aHoliday[0]['holiday']-aHoliday[0]['togetherholidays']).number_format(2,',','.');
		$('acc_1_allsickdays').value				= aHoliday[0]['togethersickdays'];
	}

	$('acc_1_allovertimes').value		= aHoliday[0]['allovertimes'];
	$('acc_1_successovertimes').value	= aHoliday[0]['togetherovertimes'];
	$('acc_1_overtimesopen').value		= aHoliday[0]['overtimesopen'];

	cleanHolidayFields(1);
}

/* ====================================================================== */

function openEmployeeHoliday()
{

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=get_holiday_data&employee_id=' + iEmployeeID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: openEmployeeHolidayCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}

}

/* ====================================================================== */

function openEmployeeTimeclockCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	var strCode = '';
	var objGUI = new GUI;

	strCode += objGUI.startFieldset('Filter', 'class="form-inline"');
		strCode += 'Monat: <select class="txt form-control input-sm" id="acc_1_month" onchange="openEmployeeTimeclock();">';
			for(var i = 0; i < arrData['aMonths'].length; i++)
			{
				strSelected = '';
				if(arrData['iSelMonth'] == arrData['aMonths'][i][0])
				{
					strSelected = 'selected="selected"';
				}
				strCode += '<option value="'+arrData['aMonths'][i][0]+'" '+strSelected+'>'+arrData['aMonths'][i][1]+'</option>';
			}
		strCode += '</select> ';
		strCode += ' Jahr: <select class="txt form-control input-sm" id="acc_1_year" onchange="openEmployeeTimeclock();">';
			for(var i = 0; i < arrData['aYears'].length; i++)
			{
				strSelected = '';
				if(arrData['iSelYear'] == arrData['aYears'][i][0])
				{
					strSelected = 'selected="selected"';
				}
				strCode += '<option value="'+arrData['aYears'][i][0]+'" '+strSelected+'>'+arrData['aYears'][i][1]+'</option>';
			}
		strCode += '</select>';
		strCode += '<button class="btn btn-primary btn-sm" onclick="openEmployeeTimeclock();" />anzeigen</button>';
	strCode += objGUI.endFieldset();



	strCode += '<div id="main_container">';
		strCode += objGUI.startAccordionContainer('');
			strCode += objGUI.startAccordion('Zeiterfassung');
				strCode += objGUI.startFieldset('Übersicht');
					strCode += '<div id="acc_1_overview"></div>';
				strCode += objGUI.endFieldset();
			strCode += objGUI.endAccordion();
			strCode += objGUI.startAccordion('Arbeitsstunden');
				strCode += '<div style="margin: 5px 0; border: 1px solid #CCC;">';
				strCode += '<table id="tableWorkTimes" cellpadding="0" cellspacing="0" border="0" class="table" style="width:100%;">';
					strCode += '<thead>';
						strCode += '<tr>';
							strCode += '<th>Tag</th>';
							strCode += '<th>Arbeitstunden</th>';
							strCode += '<th>Pause</th>';
							strCode += '<th>Gesamt</th>';
						strCode += '</tr>';
					strCode += '</thead>';
					strCode += '<tbody id="tbl_work_times"></tbody>';
				strCode += '</table></div>';
			strCode += objGUI.endAccordion();
			strCode += objGUI.startAccordion('Logindetails');
				strCode += '<div style="margin: 5px 0; border: 1px solid #CCC;">';
				strCode += '<table id="tableWorkDetails" cellpadding="0" cellspacing="0" border="0" class="table" style="width:100%;">';
					strCode += '<thead>';
						strCode += '<tr>';
							strCode += '<th>Nr.</th>';
							strCode += '<th>Kunde</th>';
							strCode += '<th>Projekt</th>';
							strCode += '<th>Tätigkeit</th>';
							strCode += '<th>Von</th>';
							strCode += '<th>Bis</th>';
							strCode += '<th>Stunden</th>';
						strCode += '</tr>';
					strCode += '</thead>';
					strCode += '<tbody id="tbl_work_details"></tbody>';
				strCode += '</table></div>';
			strCode += objGUI.endAccordion();
		strCode += objGUI.endAccordionContainer('');
	strCode += '</div>';

	if(arrData['bOpen'] == true)
	{
		// Display LitBox
		objDialogBox = new LITBox(strCode, {type: 'alert', overlay: true, height: 900, width: 900, resizable: false, opacity: .9});
		/* =============================================================================================================== */

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

	// Fill the all containers with data
	fillTimeclockOverview(arrData['aTimes']);
	fillTimeclockWorkTimes(arrData['aWorks']);
	fillTimeclockWorkDetails(arrData['aDetails']);

}

/* ====================================================================== */

function fillTimeclockWorkDetails(aDetails)
{
	var tbody 		= document.getElementById('tbl_work_details');
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5, td6;

	// Remove all entries
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Create new list
    for(var i = 0; i < aDetails.length; i++)
    {
	    objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);

		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = i+1;

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = aDetails[i]['company'];

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = aDetails[i]['project'];

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = aDetails[i]['title'];
		
		td4 = document.createElement("td");
		td5 = document.createElement("td");

		if(aDetails[i]['different_days'] == 1) {
			td4.style.backgroundColor = '#ff7373';
			td5.style.backgroundColor = '#ff7373';
		}
		
		objTr.appendChild(td4);
		td4.innerHTML = aDetails[i]['start'];

		objTr.appendChild(td5);
		td5.innerHTML = aDetails[i]['end'];

		td6 = document.createElement("td");
		objTr.appendChild(td6);
		td6.innerHTML = aDetails[i]['time'];

		td0 = td1 = td2 = td3 = td4 = td5 = td6 = null;
    }

    tbody = null;
}

/* ====================================================================== */

function fillTimeclockWorkTimes(aWorks)
{
	var tbody 		= document.getElementById('tbl_work_times');
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3;

	// Remove all entries
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Create new list
    for(var i = 0; i < aWorks.length; i++)
    {
	    objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);

		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = aWorks[i]['date'];

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = aWorks[i]['work'];

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = aWorks[i]['break'];

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = aWorks[i]['total'];

		td0 = td1 = td2 = td3 = null;
    }

    tbody = null;
}

/* ====================================================================== */

function fillTimeclockOverview(aTimes)
{
	var strCode = '';

	strCode += '<div style="width:120px; float:left;">';
		strCode += 'Arbeitsstunden:<br />';
		strCode += 'Urlaubsstunden:<br />';
		strCode += 'Krankheitsstunden:<br />';
	strCode += '</div>';
	strCode += '<div style="width:80px; text-align:right; float:left;">';
		strCode += aTimes['iH_Work'] + ':' + aTimes['iM_Work'] + ':' + aTimes['iS_Work'] + '<br />';
		strCode += aTimes['iH_Holly'] + ':' + aTimes['iM_Holly'] + ':' + aTimes['iS_Holly'] + '<br />';
		strCode += aTimes['iH_Sick'] + ':' + aTimes['iM_Sick'] + ':' + aTimes['iS_Sick'] + '<br />';
	strCode += '</div><div style="width:250px; float:left;">&nbsp;</div>';
	strCode += '<div style="width:120px; float:left;">';
		strCode += '<b>Gesamtstunden:</b><br />';
		strCode += '<b>Soll-Stunden:</b><br />';
		if(parseInt(aTimes['iH_Minus'], 10) < 0)
		{
			strCode += '<b>Minus-Stunden:</b>';
		}
		else
		{
			strCode += '<b>Überstunden:</b>';
		}
	strCode += '</div>';
	strCode += '<div style="width:80px; text-align:right; float:left;">';
		strCode += '<b>' + aTimes['iH_Total'] + ':' + aTimes['iM_Total'] + ':' + aTimes['iS_Total'] + '</b><br />';
		strCode += '<b>' + aTimes['iH_Todo'] + ':' + aTimes['iM_Todo'] + ':' + aTimes['iS_Todo'] + '</b><br />';
		strCode += '<b>' + aTimes['iH_Minus'] + ':' + aTimes['iM_Minus'] + ':' + aTimes['iS_Minus'] + '</b>';
	strCode += '</div><div style="clear:left;"></div>';

	$('acc_1_overview').innerHTML = strCode;
}

/* ====================================================================== */

function openEmployeeHolidayCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	aHoliday = objData['holiday'];

	if(aHoliday[0]['no_contract'] && aHoliday[0]['no_contract'] == 1)
	{
		alert('Es ist kein Gültiger Vertrag vorhanden.');
		exit;
	}

	var strCode = '';
	var objGUI = new GUI;

	// Create Type Array
	var aType1 	= new Array('holiday', 'Urlaub');
	var aType2 	= new Array('sick', 'Krank');
	var aType3 	= new Array('overtime', 'Überstunden (Frei)');
	var aType4 	= new Array('overtime_paid', 'Überstunden (€)');
	var aType = new Array(aType1, aType2, aType3, aType4);

	strCode += '<div id="main_container">';

		strCode += objGUI.startAccordionContainer('');

			strCode += objGUI.startAccordion('Abwesenheit');

				strCode += '<div id="holiday_overview">';
					strCode += '<div style="float:left; width:340px;">';
						strCode += objGUI.startFieldset('Allgemein');

							strCode += '<div style="margin-bottom: 3px;"><div style="width:100px;float:left;">Mitarbeiter:</div><div style="float:left;">'+aHoliday[0]['lastname']+' '+aHoliday[0]['firstname']+'</div></div>';
							strCode += '<br />'
							strCode += objGUI.printFormSelect('Jahr', 'acc_1_year', aHoliday[0]['years'], aHoliday[0]['selected_year'], 'onchange="refreshEmployeeHoliday(this.value)"');

						strCode += objGUI.endFieldset();
					strCode += '</div>';

					strCode += '<div style="float:left; width:340px;">';
						strCode += objGUI.startFieldset('Übersicht');
							strCode += objGUI.printFormInputNumbers('Urlaub (muss)', 'acc_1_allholidays', aHoliday[0]['holiday'], 'readonly="readonly"', 'Tage');
							strCode += objGUI.printFormInputNumbers('Urlaub (hat)', 'acc_1_successholidays', aHoliday[0]['togetherholidays'], 'readonly="readonly"', 'Tage');
							strCode += objGUI.printFormInputNumbers('Urlaub (kann)', 'acc_1_holidaysopen', (parseFloat(aHoliday[0]['holiday'])-parseFloat(aHoliday[0]['togetherholidays'])).number_format(2,',','.'), 'readonly="readonly"', 'Tage');
							strCode += objGUI.printFormInputNumbers('Krankheit', 'acc_1_allsickdays', aHoliday[0]['togethersickdays'], 'readonly="readonly"', 'Tage');

							strCode += objGUI.printFormInputNumbers('Überstd. (muss)', 'acc_1_allovertimes', aHoliday[0]['allovertimes'], 'readonly="readonly"', 'Std.');
							strCode += objGUI.printFormInputNumbers('Überstd. (hat)', 'acc_1_successovertimes', aHoliday[0]['togetherovertimes'], 'readonly="readonly"', 'Std.');
							strCode += objGUI.printFormInputNumbers('Überstd. (kann)', 'acc_1_overtimesopen', aHoliday[0]['overtimesopen'], 'readonly="readonly"', 'Std.');

						strCode += objGUI.endFieldset();	
					strCode += '</div>';
					strCode += '<div style="clear:both;"></div>';
				strCode += '</div>'
			
				
					// add or view holi-/sickdays
					strCode += '<div id="editHoliday">'; //addContract
			
							strCode += objGUI.startFieldset('Abwesenheit');
									strCode += objGUI.printFormSelect('Typ', 'edit_1_typ', aType, '', '');
										strCode += '<div style="margin-bottom:3px;">';
											strCode += '<div style="width:103px;float:left;">Startdatum:</div>';
											strCode += '<div style="float:left;">';
												strCode += '<input type="text" id="edit_1_from_dd" name="edit_1_from_dd" value="" class="txt w50" />.';
												strCode += '<input type="text" id="edit_1_from_mm" name="edit_1_from_mm" value="" class="txt w50" />.';											
												strCode += '<input type="text" id="edit_1_from_yyyy" name="edit_1_from_yyyy" readonly="readonly" value="'+aHoliday[0]['selected_year']+'" class="txt w50" />  (TT.MM.JJJJ)';
											strCode += '</div>';
									strCode += '</div>';
									strCode += '<div style="clear:both"></div>';
									strCode += objGUI.printFormInputNumbers('Anzahl', 'edit_1_days', '', '', 'Tage');
									strCode += objGUI.printFormInputNumbers('Anzahl', 'edit_1_hours', '', '', 'Stunden');
									strCode += objGUI.printFormTextarea('Notiz', 'edit_1_notice', '', '5', '5', 'style="width:345px;height:150px;"');
							strCode += objGUI.endFieldset();

							strCode += '<div>';
								// button back
								strCode += '<div id="backToListButton" style="float:right;">';
									strCode += objGUI.printFormButton('zurück zur Liste', 'cleanHolidayFields(1); hideHolidayDIVs(\'offAdd_offEdit_onList\');', '', 'style="opacity:1; filter:alpha(opacity=100);"');
								strCode += '</div>';
								// button save new
								strCode += '<div id="holidayAddButton" style="float:right;">';
									strCode += objGUI.printFormButton('Eintrag hinzufügen', 'manageHoliday();', '', 'style="opacity:1; filter:alpha(opacity=100);"');
								strCode += '</div>';
								// button save changes
								strCode += '<div id="holidayEditSaveButton" style="display:none;float:right;">';
									strCode += objGUI.printFormButton('Änderungen speichern', 'manageHoliday(\'edit\');', '', 'style="opacity:1; filter:alpha(opacity=100);"');
								strCode += '</div>';
							strCode += '</div>';

					strCode += '</div>';
								
					// view all holidays/sickdays
					strCode += '<div id="holidaysList">';
						strCode += '<div id="contractSettings" style="padding:3px; background-color:#f7f7f7; margin-top:5px; border: 1px solid #CCC;">';
							strCode += '<div style="position:relative; top:-3px;">';
								strCode += '<b>Anlegen:</b>';
								strCode += '<img onclick="hideHolidayDIVs(\'onAdd_onEdit_offList\');" src="/admin/media/page_new.gif" alt="Anlegen" title="Anlegen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
								strCode += ':: <b>Bearbeitung:</b>';
								strCode += '<img onclick="manageHoliday(\'pre_edit\');" src="/admin/media/pencil.png" alt="Bearbeiten" title="Bearbeiten" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
								strCode += '<img onclick="manageHoliday(\'delete\');" src="/admin/media/cross.png" alt="Löschen" title="Löschen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
							strCode += '</div>';
						strCode += '</div>';
						strCode += '<table id="tableContracts" cellpadding="0" cellspacing="0" border="0" width="730" class="table" style="margin: 10px 0;">';
							strCode += '<thead>';
								strCode += '<tr>';
									strCode += '<th>Anfang</th>';
									strCode += '<th>Ende</th>';
									strCode += '<th>Insgesamt (Tage)</th>';
									strCode += '<th>Typ</th>';
								strCode += '</tr>';
							strCode += '</thead>';
							strCode += '<tbody id="tbl_holidays"></tbody>';
						strCode += '</table>';
					strCode += '</div>';
		
	
			strCode += objGUI.endAccordion();
	
		strCode += objGUI.endAccordionContainer('');

	strCode += '</div>';

	// Display LitBox
	/* ================================================== */
	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:600, width:750, resizable:false, opacity:.9});
	/* =============================================================================================================== */

	var iAccStartsCounter = 0;

    // Activate accordions only one time
	if(iAccStartsCounter == 0)
	{
		activateHolidayAccordion();
		iAccStartsCounter = 1;
	}

	//refreshEmployeeHoliday(aHoliday[0]['selected_year']);

}

/* ====================================================================== */

function createHolidaysList()
{

	var tbody 		= document.getElementById('tbl_holidays');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5;



	// Remove all contracts
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Create new contracts
    for(var i = 0; i < aHoliday.length; i++, c++)
    {
		if(aHoliday[i]['from'][0])
		{
		    objTr = tr.cloneNode(false);
	        tbody.appendChild(objTr);
	        var strId = 'hol_tr_' + aHoliday[i]['id'];
	        objTr.id = strId;

	        Event.observe(objTr, 'click', checkHolidayRow.bindAsEventListener(c, strId));
			Event.observe(objTr, 'dblclick', editHoliday.bindAsEventListener(c, aHoliday[i]));
			Event.observe(objTr, 'mouseout', resetHighlightRow);
			Event.observe(objTr, 'mousemove', setHighlightRow);

			td0 = document.createElement("td");
			objTr.appendChild(td0);
			if(aHoliday[i]['from'][0])
			{
				td0.innerHTML = aHoliday[i]['from'][0]+'.'+aHoliday[i]['from'][1]+'.'+aHoliday[i]['from'][2];
			} else {
				td0.innerHTML = '';
			}

			td1 = document.createElement("td");
			objTr.appendChild(td1);
			if(aHoliday[i]['till'][0])
			{
				td1.innerHTML = aHoliday[i]['till'][0]+'.'+aHoliday[i]['till'][1]+'.'+aHoliday[i]['till'][2];
			} else {
				td1.innerHTML = '';
			}

			td2 = document.createElement("td");
			objTr.appendChild(td2);
			td2.innerHTML = aHoliday[i]['quote'];

			var type = '';
			if(aHoliday[i]['type'] == 'sick')			{type = 'Krank'}
			if(aHoliday[i]['type'] == 'holiday')		{type = 'Urlaub'}
			if(aHoliday[i]['type'] == 'overtime')		{type = 'Überstunden (Frei)'}
			if(aHoliday[i]['type'] == 'overtime_paid')	{type = 'Überstunden (€)'}

			td3 = document.createElement("td");
			objTr.appendChild(td3);
			td3.innerHTML = type;

			td0 = td1 = td2 = td3 = null;
		}
    }

    tbody = null;
}

/* ====================================================================== */

var selectedHolidayRow = 0;
function checkHolidayRow(e, strId)
{

	var objRow = $(strId);

	if(
		selectedHolidayRow && 
		$(selectedHolidayRow)
	) {
		$(selectedHolidayRow).className = "";
	}

	if(objRow.className == "") {
		objRow.className = "selectedRow";
	} else {
		objRow.className = "";
	}
	selectedHolidayRow = strId;

}

/* ====================================================================== */


function cleanHolidayFields(sModus)
{

	// Clear position fields
	$('edit_1_typ').options[0].selected			= 'selected';
	$('edit_1_from_dd').value					= '';
	$('edit_1_from_mm').value					= '';
	$('edit_1_from_yyyy').value					= $F('acc_1_year');
	$('edit_1_days').value						= '';
	$('edit_1_hours').value						= '';
	$('edit_1_notice').value					= '';

	if(sModus == 1)
	{
		$('holidayEditSaveButton').style.display	= 'none';
		$('holidayAddButton').style.display		= 'inline';
	}
}

/* ====================================================================== */


function manageHoliday(sTask, sDataJSON)
{

	var bError = false;

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=add_holiday';

	if(sTask == 'edit')
	{
		strParameters = 'task=edit_holiday';
		strParameters += '&id='	+ iEditHolidayID;
	}
	else if(sTask == 'delete')
	{
		var sHolidayID = selectedHolidayRow;
		if(sHolidayID != 0)
		{
			sHolidayID = sHolidayID.substr(7);
		}
		strParameters = 'task=delete_holiday';
		strParameters += '&id='	+ sHolidayID;
	}
	else if(sTask == 'pre_edit')
	{
		var sHolidayID = selectedHolidayRow;
		if(sHolidayID != 0)
		{
			sHolidayID = sHolidayID.substr(7);
			for (var i = 0; i < aHoliday.length; i++)
			{
				if(aHoliday[i]['id'] == parseInt(sHolidayID))
				{
					editHoliday(0, aHoliday[i]);
					break;
				}
			}
			return;
		}
	}

	
	strParameters += '&employee_id='				+ iEmployeeID;
	strParameters += '&from='						+ encodeURIComponent($F('acc_1_year')+'-'+$('edit_1_from_mm').value+'-'+$('edit_1_from_dd').value);
	strParameters += '&till_days='					+ encodeURIComponent($('edit_1_days').value);
	strParameters += '&till_hours='					+ encodeURIComponent($('edit_1_hours').value);
	strParameters += '&type='						+ encodeURIComponent($('edit_1_typ').value);
	strParameters += '&notice='						+ encodeURIComponent($('edit_1_notice').value);


	if(sHolidayID == 0 && sTask == 'delete')
	{
		alert('Bitte markieren Sie den zu löschenden Eintrag!');
		bError = true;
	}
	else if(sHolidayID == 0 && sTask == 'pre_edit')
	{
		alert('Bitte markieren Sie den zu bearbeitenden Eintrag!');
		bError = true;
	}
	else if(
		sTask != 'delete'
			&&
		sTask != 'sort'
			&&
		sTask != 'pre_edit'
	)
	{
		if(	$('edit_1_from_dd').value == ''
						||
			$('edit_1_from_mm').value == ''
						||
			$('edit_1_from_yyyy').value == '')
		{
			alert('Bitte geben Sie einen gültigen Startzeitpunkt an.');
			bError = true;
		}
	}

	if(
		iEmployeeID != 0
			&&
		sTask == 'delete'
			&&
		bError == false
	)
	{
		if(confirm('Möchten Sie wirklich den markierten Eintrag löschen?') == false)
		{
			return;
		}
		else
		{
			selectedHolidayRow = 0;
		}
	}

	if(bError == false)
	{
		try
		{
			var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : manageHolidayCallback
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

function manageHolidayCallback(objResponse)
{

	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['holiday'];

	aHoliday = arrData;

	if( 
		(
			aHoliday[0]['togetherholidays'] == null && 
			aHoliday[0]['togethersickdays'] == null
		) || 
		aHoliday[0]['no_entrys'] == 1
	) {
		var tbody = $('tbl_holidays');
		// Remove all contracts
		while(tbody.hasChildNodes()) {
			tbody.removeChild(tbody.firstChild);
		}

		$('acc_1_successholidays').value			= '';
		$('acc_1_holidaysopen').value				= parseFloat($F('acc_1_allholidays')).number_format(2,',','.');
		$('acc_1_allsickdays').value				= '';
	
	} else {
		
		createHolidaysList()
		// refresh holiday data
		$('acc_1_allholidays').value				= aHoliday[0]['holiday'];
		$('acc_1_successholidays').value			= aHoliday[0]['togetherholidays'];
		$('acc_1_holidaysopen').value				= (parseFloat(aHoliday[0]['holiday'])-parseFloat(aHoliday[0]['togetherholidays'])).number_format(2,',','.');
		$('acc_1_allsickdays').value				= aHoliday[0]['togethersickdays'];
	}

	$('acc_1_allovertimes').value				= aHoliday[0]['allovertimes'];
	$('acc_1_successovertimes').value			= aHoliday[0]['togetherovertimes'];
	$('acc_1_overtimesopen').value				= aHoliday[0]['overtimesopen'];

	cleanHolidayFields(1);

	// Display / hide DIVs with buttons
	$('holidayEditSaveButton').style.display		= 'none';
	$('holidayAddButton').style.display			= 'inline';

	// Enable 'back to Contracts list' button
	$('backToListButton').style.display = 'inline';

	hideHolidayDIVs('offAdd_offEdit_onList');
}

/* ====================================================================== */

function sendAccessData()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=send_access_data&employee_id=' + iEmployeeID;

	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method		: 'post',
									parameters	: strParameters,
									onComplete	: sendAccessDataCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

function sendAccessDataCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	alert('Neue Zugangsdaten wurden erfolgreich an ' + arrData['email'] + ' gesendet!');
}

/* ====================================================================== */

function hideHolidayDIVs(sModus)
{

	if(sModus == 'onAdd_onEdit_offList')
	{
//		$('addHoliday').style.display = 'inline';
		$('editHoliday').style.display = 'inline';
		$('holidaysList').style.display = 'none';
		$('holiday_overview').style.display = 'none';
	}

	if(sModus == 'offAdd_offEdit_onList')
	{
//		$('addHoliday').style.display = 'none';
		$('editHoliday').style.display = 'none';
		$('holidaysList').style.display = 'inline';
		$('holiday_overview').style.display = 'inline';
	}

}

/* ====================================================================== */


var iEditHolidayID = 0;
function editHoliday(c, aHoliday)
{
	
	$('holiday_overview').style.display = 'none';
	// Set the id of contract which is to edit
	iEditHolidayID = aHoliday['id'];

	// Display / hide DIVs
	$('holidayEditSaveButton').style.display	= 'inline';
	$('editHoliday').style.display				= 'inline';
	$('holidaysList').style.display				= 'none';
	$('holidayAddButton').style.display			= 'none';
	

	// Fill the fields with contract data
	if(aHoliday['from'][0])
	{
		$('edit_1_from_dd').value					= aHoliday['from'][0];
		$('edit_1_from_mm').value					= aHoliday['from'][1];
		$('edit_1_from_yyyy').value					= $F('acc_1_year');
	} else {
		$('edit_1_from_dd').value					= '';
		$('edit_1_from_mm').value					= '';
		$('edit_1_from_yyyy').value					= '';
	}

	$('edit_1_days').value						= aHoliday['difference'];
	$('edit_1_hours').value						= aHoliday['hours'];
	$('edit_1_notice').value					= aHoliday['notice'];

	for(var i = 0; i < $('edit_1_typ').options.length; i++)
	{
		if($('edit_1_typ').options[i].value == aHoliday['type'])
		{
			$('edit_1_typ').options[i].selected = 'selected';
			
			break;
		}
	}
}

/* ====================================================================== */

function activateHolidayAccordion()
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

	// first time hide div "addContract"
	$('editHoliday').style.display = 'none';

	// load contract data
	createHolidaysList();

}

/* ====================================================================== */


function openEmployeeDialogCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrFiles = objData['files'];
	var arrData = objData['data'];
	var objGUI = new GUI;
	var strCode = '';

	if(objData['selectedGroups']) {
		selectedGroups = objData['selectedGroups'];
	}

	var arrGroups = objData['groups'];

	aContracts = objData['contracts'];

	usercookie = objData['cookie']['user'];
	passcookie = objData['cookie']['pass'];

	// will need for preselect
	if(aContracts) {
		var iContractsLength = aContracts.length;
	} else {
		var iContractsLength = 0;
	}

	iEmployeeID = arrData['id'];

	// Open main container
	strCode += '<div id="main_container" style="padding: 15px;" onclick="document.getElementById(\'saving_confirmation\').style.display = \'none\'">';

	// Saving Confirmation
	strCode += '<div id="saving_confirmation" style="display:none; color:#008000;">';
		strCode += '<div style="text-align:center; padding-top:10px;" id="saving_confirmation_text">';
			strCode += '<b>Der Mitarbeiter wurde erfolgreich gespeichert!</b>';
		strCode += '</div>';
	strCode += '</div>';

	// Open accordions container
	strCode += objGUI.startAccordionContainer('');

	// Create SEX Array
	var Mr 	= new Array(0,'Herr');
	var Mrs	= new Array(1, 'Frau');
	var aSexArray = new Array(Mr, Mrs);

	// Create Tax_Class Array
	var aTaxclass1 	= new Array(0, 'I');
	var aTaxclass2 	= new Array(1, 'II');
	var aTaxclass3 = new Array(2, 'III');
	var aTaxclass4 	= new Array(3, 'IV');
	var aTaxclass5 	= new Array(4, 'V');
	var aTaxclass6 	= new Array(5, 'VI');
	var aTaxClassArray = new Array(aTaxclass1, aTaxclass2, aTaxclass3, aTaxclass4, aTaxclass5, aTaxclass6);

	// Stunden pro
	var aHoursPerWeek = new Array('week', 'Woche');
	var aHoursPerMonth = new Array('month', 'Monat');
	var aHourTypes = new Array(aHoursPerWeek, aHoursPerMonth);

	var arrReportingGroups = arrGroups.clone();
	arrReportingGroups.unshift(new Array(0, ''));

	// Employeer Accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Details');

		strCode += objGUI.startRow();
		strCode += objGUI.startCol('');
			strCode += objGUI.startFieldset('Allgemein');
//				strCode += '<div style="float:left; width:320px;">';
					strCode += objGUI.printFormInput('Nummer', 'acc_1_id', arrData['id'], 'disabled="disabled"');
					strCode += objGUI.printFormSelect('Anrede', 'acc_1_sex', aSexArray, arrData['sex'], '');
					strCode += objGUI.printFormInput('Vorname', 'acc_1_firstname', arrData['firstname']);
					strCode += objGUI.printFormInput('Nachname', 'acc_1_lastname', arrData['lastname']);
					strCode += objGUI.printFormInput('Geburtstag', 'acc_1_date_o_b', arrData['date_o_b']);
					strCode += objGUI.printFormInput('Staatsangeh.', 'acc_1_nationality', arrData['nationality']);
					strCode += objGUI.printFormMultiSelect('Gruppen', 'acc_1_groups', arrGroups, selectedGroups, 'size="3" multiple="multiple" style="width: 200px;"');
					strCode += objGUI.printFormSelect('Auswertungsgr.', 'acc_1_reporting_group', arrReportingGroups, arrData['reporting_group'], 'style="width: 200px;"');
					strCode += objGUI.printFormInput('Abteilung', 'acc_1_sektion', arrData['sektion']);
					strCode += objGUI.printFormInput('Position', 'acc_1_position', arrData['position']);
//				strCode += '</div>';
			strCode += objGUI.endFieldset();
		strCode += objGUI.endCol();
		strCode += objGUI.startCol('');
			strCode += objGUI.startFieldset('Kontakt');
//				strCode += '<div style="float:left; width:320px;">';
					strCode += objGUI.printFormInput('Telefon', 'acc_1_phone', arrData['phone']);
					strCode += objGUI.printFormInput('Fax', 'acc_1_fax', arrData['fax']);
					strCode += objGUI.printFormInput('E-Mail', 'acc_1_email', arrData['email']);
					strCode += objGUI.printFormInput('Nickname', 'acc_1_nickname', arrData['nickname']);
					strCode += objGUI.printFormInput('Web', 'acc_1_web', arrData['web']);
					strCode += objGUI.printFormInput('Mobil', 'acc_1_mobile', arrData['mobile']);
					strCode += objGUI.printFormInput('Strasse', 'acc_1_street', arrData['street']);
					strCode += objGUI.printFormInput('PLZ', 'acc_1_zip', arrData['zip']);
					strCode += objGUI.printFormInput('Stadt', 'acc_1_city', arrData['city']);
					strCode += objGUI.printFormInput('Land', 'acc_1_country', arrData['country']);
//				strCode += '</div>';
			strCode += objGUI.endFieldset();
		strCode += objGUI.endCol();
		strCode += objGUI.endRow();

	strCode += objGUI.endAccordion();

	// bank data accordion
	/* ================================================== */
/*
	strCode += objGUI.startAccordion('Bankverbindung');
		strCode += objGUI.startFieldset('Bankverbindung');
			strCode += '<div>';
				strCode += objGUI.printFormInput('Kreditinstitut', 'acc_5_bank_name', arrData['bank_name']);
				strCode += objGUI.printFormInput('Kontoinhaber', 'acc_5_bank_holder', arrData['bank_holder']);
				strCode += objGUI.printFormInput('Kontonummer', 'acc_5_bank_number', arrData['bank_number']);
				strCode += objGUI.printFormInput('BLZ', 'acc_5_bank_code', arrData['bank_code']);
			strCode += '</div>';
		strCode += objGUI.endFieldset();
	strCode += objGUI.endAccordion();
*/
	// contract accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Vertragsparameter');

		// add or view contract
		strCode += '<div id="editContract" class="row">'; //addContract
			strCode += objGUI.startCol('');
	
				strCode += objGUI.startFieldset('Zeitraum');
					//strCode += '<div style="float:left; width:320px;">';
						strCode += objGUI.printFormInput('Gültig von', 'acc_2_from', '');
						strCode += objGUI.printFormInput('Gültig bis', 'acc_2_until', '');
					//strCode += '</div>';
				strCode += objGUI.endFieldset();

				strCode += objGUI.startFieldset('Private Abrechnungsdaten');
//					strCode += '<div style="float:left; width:320px;">';
					
					if(iContractsLength != 0)
					{
						strCode += objGUI.printFormInput('Sozialvers.Nr.', 'acc_2_social_security_number', aContracts[iContractsLength-1]['social_security_number']);
						strCode += objGUI.printFormInput('Religion', 'acc_2_religion', aContracts[iContractsLength-1]['religion']);
						strCode += objGUI.printFormSelect('Steuerklasse', 'acc_2_tax_class', aTaxClassArray, aContracts[iContractsLength-1]['tax_class'], '');
						strCode += objGUI.printFormInput('Krankenkasse', 'acc_2_health_insurance', aContracts[iContractsLength-1]['health_insurance']);
						strCode += objGUI.printFormInput('Steuernummer', 'acc_2_tax_number', aContracts[iContractsLength-1]['tax_number']);
						strCode += objGUI.printFormInput('Faktor', 'acc_2_factor', aContracts[iContractsLength-1]['factor']);
					}
					else
					{
						strCode += objGUI.printFormInput('Sozialvers.Nr.', 'acc_2_social_security_number', '');
						strCode += objGUI.printFormInput('Religion', 'acc_2_religion', '');
						strCode += objGUI.printFormSelect('Steuerklasse', 'acc_2_tax_class', aTaxClassArray, '', '');
						strCode += objGUI.printFormInput('Krankenkasse', 'acc_2_health_insurance', '');
						strCode += objGUI.printFormInput('Steuernummer', 'acc_2_tax_number', '');
						strCode += objGUI.printFormInput('Faktor', 'acc_2_factor', '');
					}

//					strCode += '</div>';
				strCode += objGUI.endFieldset();
				
			strCode += objGUI.endCol();

			strCode += objGUI.startCol('');
				
				strCode += objGUI.startFieldset('Firmen Abrechnungsdaten');
//					strCode += '<div style="float:left; width:320px;">';
						
						if(iContractsLength != 0)
						{
							strCode += objGUI.printFormInput('Bruttogehalt', 'acc_2_gross_salary', parseFloat(aContracts[iContractsLength-1]['gross_salary']).number_format(2,',','.'));
							strCode += objGUI.printFormInput('Gehalt/h', 'acc_2_salary', parseFloat(aContracts[iContractsLength-1]['salary']).number_format(2,',','.'));
							strCode += objGUI.printFormSelect('Stunden pro', 'acc_2_hours_type', aHourTypes, aContracts[iContractsLength-1]['hours_type'], '');
							strCode += objGUI.printFormInput('Stunden', 'acc_2_hours_value', parseFloat(aContracts[iContractsLength-1]['hours_value']).number_format(2,',','.'));
							strCode += objGUI.printFormInput('Arbeitstage/Wo.', 'acc_2_days_per_week', parseFloat(aContracts[iContractsLength-1]['days_per_week']).number_format(2,',','.'));
							strCode += objGUI.printFormInput('Urlaub/Jahr', 'acc_2_holiday', aContracts[iContractsLength-1]['holiday']);
						}
						else
						{
							strCode += objGUI.printFormInput('Bruttogehalt', 'acc_2_gross_salary', '');
							strCode += objGUI.printFormInput('Gehalt/h', 'acc_2_salary', '');
							strCode += objGUI.printFormSelect('Stunden pro', 'acc_2_hours_type', aHourTypes, '', '');
							strCode += objGUI.printFormInput('Stunden', 'acc_2_hours_value', '');
							strCode += objGUI.printFormInput('Arbeitstage/Woche', 'acc_2_days_per_week', '');
							strCode += objGUI.printFormInput('Urlaub/Jahr', 'acc_2_holiday', '');
						}

//					strCode += '</div>';
				strCode += objGUI.endFieldset();

			strCode += objGUI.endCol();

//			strCode += '<div style="clear:both;">';
//			strCode += '</div>';

			strCode += '<div class="col-md-12">';

				// button back
				strCode += '<div id="backToListButton" style="float:right;">';
					strCode += objGUI.printFormButton('zurück zur Liste', 'cleanContractFields(1); hideContractDIVs(\'offAdd_offEdit_onList\');', '', 'style="opacity:1; filter:alpha(opacity=100);"');
				strCode += '</div>';
				
				// button save new
				strCode += '<div id="contractAddButton" style="float:right;">';
					strCode += objGUI.printFormButton('Vertragsparameter hinzufügen', 'manageContract();', '', 'style="opacity:1; filter:alpha(opacity=100);"');
				strCode += '</div>';
				
				// button save changes
				strCode += '<div id="contractEditSaveButton" style="display:none;float:right;">';
					strCode += objGUI.printFormButton('Änderungen speichern', 'manageContract(\'edit\');', '', 'style="opacity:1; filter:alpha(opacity=100);"');
				strCode += '</div>';

			strCode += '</div>';

		strCode += '</div>';

		// view all contracts
		strCode += '<div id="contractsList">';
			strCode += '<div id="contractSettings" style="padding:3px; background-color:#f7f7f7; margin-top:5px; border: 1px solid #CCC;">';
				strCode += '<div style="position:relative; top:-3px;">';
					strCode += '<b>Anlegen:</b>';
					strCode += '<img onclick="hideContractDIVs(\'onAdd_onEdit_offList\');" src="/admin/media/page_new.gif" alt="Anlegen" title="Anlegen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
					strCode += ':: <b>Bearbeitung:</b>';
					strCode += '<img onclick="manageContract(\'pre_edit\');" src="/admin/media/pencil.png" alt="Bearbeiten" title="Bearbeiten" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
					strCode += '<img onclick="manageContract(\'delete\');" src="/admin/media/cross.png" alt="Löschen" title="Löschen" style="position:relative; top:4px; cursor:pointer; margin: 0 4px;" />';
				strCode += '</div>';
			strCode += '</div>';
			strCode += '<table id="tableContracts" cellpadding="0" cellspacing="0" border="0" width="730" class="table" style="margin: 10px 0;">';
				strCode += '<thead>';
					strCode += '<tr>';
						strCode += '<th>von</th>';
						strCode += '<th>bis</th>';
						strCode += '<th>Gehalt(Brutto)</th>';
						strCode += '<th>Gehalt(Kosten)</th>';
						strCode += '<th>Stunden</th>';
						strCode += '<th>Urlaub</th>';
						strCode += '<th>Faktor</th>';
					strCode += '</tr>';
				strCode += '</thead>';
				strCode += '<tbody id="tbl_contracts"></tbody>';
			strCode += '</table>';
		strCode += '</div>';

	strCode += objGUI.endAccordion();

	// Notice Accordion
	/* ================================================== */
	strCode += objGUI.startAccordion('Notizen');
		strCode += '<div style="text-align:center;margin-bottom:5px;">'
			strCode += objGUI.printFormTextarea('', 'acc_notice', arrData['notice'], '5', '5', 'style="width:690px;height:150px;"');
		strCode += '</div>'
	strCode += objGUI.endAccordion();

	if(iEmployeeID > 0)
	{
		// File accordion
		/* ================================================== */
		strCode += objGUI.startAccordion('Uploads / Infos');
			strCode += objGUI.startFieldset('Dateien');
				strCode += '<div style="float:left; width:330px;">';
					strCode += '<div id="uploader" style="float:left;">';
					strCode += '</div>';
				strCode += '</div>';
				strCode += '<div id="files" style="float:left;margin-left:5px;height:260px;overflow-y:auto;overflow-x:hidden;">';
				strCode += insertFilesTable(arrFiles);
				strCode += '</div>';
			strCode += objGUI.endFieldset();
		strCode += objGUI.endAccordion();
	}

	// Close accordions container
	/* ================================================== */
	strCode += objGUI.endAccordionContainer('');

	strCode += '<div class="divCleaner divButton">';
		// Send access data button
		strCode += '<span id="access_data_button" style="display:none;">';
			strCode += ' <button onclick="sendAccessData();" class="btn" style="opacity:1; filter:alpha(opacity=100);">Zugangsdaten versenden</button> ';
		strCode += '</span>';

		// Save button
		strCode += ' <button onclick="saveEmployee();" id="save_button" class="btn" style="opacity:1; filter:alpha(opacity=100);">Mitarbeiter speichern</button>';
	strCode += '</div>';

	// Close main container
	/* ================================================== */
	strCode += '</div>';

	// Display LitBox
	/* ================================================== */
	objDialogBox = new LITBox(strCode, {type:'alert', overlay:true, height:600, width:750, resizable:false, opacity:.9});
	/* =============================================================================================================== */

	var iAccStartsCounter = 0;

    // Activate accordions only one time
	if(iAccStartsCounter == 0)
	{
		activateAccordions();
		iAccStartsCounter = 1;
	}

	if(iEmployeeID > 0)
	{
		$('access_data_button').style.display = 'inline';
	}
}

/* ====================================================================== */




function activateAccordions()
{
//	// Start and activate accordions
//	var horizontalAccordion = new accordion('divAccordionContainer', {
//	    classNames : {
//	        toggle : 'accordionTitle',
//	        toggleActive : 'accordionTitleActive',
//	        content : 'accordionContent'
//	    }
//	});
//	horizontalAccordion.switchContainer($('divAccordionContainer').down(0));

	// first time hide div "addContract"
	$('editContract').style.display = 'none';

	// load contract data
	createContractsList();

	// start flash with employeeid param
	//get_flash_uploader();

}

/* ====================================================================== */

function saveEmployee()
{
	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=save_employee';

	// Get selected groups
	var sGroups = '';
	for(i=0; i < $('acc_1_groups').length; i++)
	{
		if($('acc_1_groups').options[i].selected == true)
		{
			sGroups += '|'+$('acc_1_groups').options[i].value;
		}
	}
	if(sGroups == '')
	{
		alert('Bitte wählen Sie die Gruppe(n)!');
		return;
	}
	sGroups += '|';

	// Accordion 1 =======================================/
	strParameters += '&employee_id='	+ iEmployeeID;
	strParameters += '&sex='			+ ($('acc_1_sex').value);
	strParameters += '&firstname='		+ encodeURIComponent($('acc_1_firstname').value);
	strParameters += '&lastname='		+ encodeURIComponent($('acc_1_lastname').value);
	strParameters += '&date_o_b='		+ encodeURIComponent($('acc_1_date_o_b').value);
	strParameters += '&nationality='	+ encodeURIComponent($('acc_1_nationality').value);
	strParameters += '&sektion='		+ encodeURIComponent($('acc_1_sektion').value);
	strParameters += '&position='		+ encodeURIComponent($('acc_1_position').value);
	strParameters += '&reporting_group='+ encodeURIComponent($('acc_1_reporting_group').value);
/*
	strParameters += '&bank_name='		+ encodeURIComponent($('acc_5_bank_name').value);
	strParameters += '&bank_holder='	+ encodeURIComponent($('acc_5_bank_holder').value);
	strParameters += '&bank_number='	+ encodeURIComponent($('acc_5_bank_number').value);
	strParameters += '&bank_code='		+ encodeURIComponent($('acc_5_bank_code').value);
*/
	strParameters += '&phone='			+ encodeURIComponent($('acc_1_phone').value);
	strParameters += '&fax='			+ encodeURIComponent($('acc_1_fax').value);
	strParameters += '&email='			+ encodeURIComponent($('acc_1_email').value);
	strParameters += '&nickname='		+ encodeURIComponent($('acc_1_nickname').value);
	strParameters += '&web='			+ encodeURIComponent($('acc_1_web').value);
	strParameters += '&mobile='			+ encodeURIComponent($('acc_1_mobile').value);
	strParameters += '&street='			+ encodeURIComponent($('acc_1_street').value);
	strParameters += '&zip='			+ encodeURIComponent($('acc_1_zip').value);
	strParameters += '&city='			+ encodeURIComponent($('acc_1_city').value);
	strParameters += '&country='		+ encodeURIComponent($('acc_1_country').value);
	strParameters += '&notice='			+ encodeURIComponent($('acc_notice').value);
	strParameters += '&groups='			+ encodeURIComponent(sGroups);

	if($('acc_1_firstname').value.length < 1)
	{
		alert('Bitte geben Sie einen Vornamen ein!');
		return;
	}
	if($('acc_1_lastname').value.length < 1)
	{
		alert('Bitte geben Sie einen Nachnamen ein!');
		return;
	}

	// Save >>>
	try
	{
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : saveEmployeeCallback
								}
		);
	}
	catch(e)
	{
		alert(sErrorMessage);
	}
}

/* ====================================================================== */

function saveEmployeeCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['data'];

	if(arrData['email'] && arrData['email'] == 'DOUBLE')
	{
		alert("Fehler! Das Speichern wurde abgebrochen, da die E-Mail-Adresse bereits im System registriert ist.\n\nBitte geben Sie eine neue E-Mail-Adresse ein.");
		return;
	}
	else if(arrData['email'] && arrData['email'] == 'NOT_VALID')
	{
		alert("Fehler! Das Speichern wurde abgebrochen. Die E-Mail-Adresse ist ungültig.\n\nBitte geben Sie eine gültige E-Mail-Adresse ein.");
		return;
	}
	else if(arrData['nickname'] && arrData['nickname'] == 'DOUBLE')
	{
		alert("Fehler! Das Speichern wurde abgebrochen, da der Nickname bereits im System registriert ist.\n\nBitte geben Sie einen neuen Nicknamen ein.");
		return;
	}
	else if(arrData['nickname'] && arrData['nickname'] == 'EMPTY')
	{
		alert("Fehler! Das Speichern wurde abgebrochen, da der Nickname leer ist.\n\nBitte geben Sie einen neuen Nicknamen ein.");
		return;
	}
	else
	{
		$('saving_confirmation').style.display = '';
		$('acc_1_id').value = iEmployeeID;
		$('access_data_button').style.display = 'inline';
	}

	loadEmployeesList();

	iEmployeeID = arrData['id'];
}

/* ====================================================================== */

function get_flash_uploader(){
// Version check for the Flash Player that has the ability to start Player Product Install (6.0r65)
var hasProductInstall = DetectFlashVer(6, 0, 65);
// Version check based upon the values defined in globals
var hasRequestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);

// Check to see if a player with Flash Product Install is available and the version does not meet the requirements for playback
if ( hasProductInstall && !hasRequestedVersion ) {
	// MMdoctitle is the stored document.title value used by the installation process to close the window that started the process
	// This is necessary in order to close browser windows that are still utilizing the older version of the player after installation has completed
	// DO NOT MODIFY THE FOLLOWING FOUR LINES
	// Location visited after installation is complete if installation is required
	var MMPlayerType = (isIE == true) ? "ActiveX" : "PlugIn";
	var MMredirectURL = window.location;
    document.title = document.title.slice(0, 47) + " - Flash Player Installation";
    var MMdoctitle = document.title;

	AC_FL_RunContent(
		"src", "playerProductInstall",
		"FlashVars", "MMredirectURL="+MMredirectURL+'&MMplayerType='+MMPlayerType+'&MMdoctitle='+MMdoctitle+"",
		"width", "328",
		"height", "240",
		"align", "middle",
		"id", "FlexFileUpload",
		"quality", "high",
		"bgcolor", "#FFFFFF",
		"name", "FlexFileUpload",
		"allowScriptAccess","sameDomain",
		"type", "application/x-shockwave-flash",
		"pluginspage", "http://www.adobe.com/go/getflashplayer"
	);
} else if (hasRequestedVersion) {
	// if we've detected an acceptable version
	// embed the Flash Content SWF when all tests are passed
	
	AC_FL_RunContent(
			"src", "office/uploader/uploader",
			"width", "328",
			"height", "240",
			"align", "middle",
			"id", "FlexFileUpload",
			"quality", "high",
			"bgcolor", "#FFFFFF",
			"name", "FlexFileUpload",
			"flashvars",'iEmployeeID='+iEmployeeID+'&sUserCookie='+usercookie+'&sPassCookie='+passcookie+'',
			"allowScriptAccess","sameDomain",
			"type", "application/x-shockwave-flash",
			"pluginspage", "http://www.adobe.com/go/getflashplayer"
	);
  } else {  // flash is too old or we can't detect the plugin

	var alternateContent = 'Alternate HTML content should be placed here. '
  	+ 'This content requires the Adobe Flash Player. '
   	+ '<a href=http://www.adobe.com/go/getflash/>Get Flash</a>';
    document.write(alternateContent);  // insert non-flash content
  }

}

/* ====================================================================== */

function refreshEmployeeFileListCallback(objResponse)
{
	var objData 	= objResponse.responseText.evalJSON();
	var aNewFileData 	= objData['files'];

	$('files').innerHTML = insertFilesTable(aNewFileData);

}

/* ====================================================================== */

function insertFilesTable(arrFiles)
{
	var strCode = '';
	strCode += '<table cellpadding="4" cellspacing="0" class="table" style="width:350px;">';
			
	strCode += '<tr>';
	strCode += '<th style="width:310px;">';
	strCode += 'Dateiname';
	strCode += '</th>';
	strCode += '<th style="width:50px;">';
	strCode += 'Aktion';
	strCode += '</th>';
	strCode += '</tr>';
		
	// foreach array mit daten.
	arrFiles.each(function(filename){
	
		strCode += '<tr>';		
		// Link with Filename
		strCode += '<td>';
			strCode += '<a href="/storage/office/employees/'+iEmployeeID+'/'+filename+'" target="_blank">'+filename+'</a>';
		strCode += '</td>';
		// Action (Delete)
		strCode += '<td style="text-align:center;">';
			strCode += '<img src="/admin/media/delete.png" alt="Löschen" title="Löschen" class="img" onclick="deleteImage(\''+iEmployeeID+'\', \''+filename+'\')"';
		strCode += '</td>';
		strCode += '</tr>';
			
	});
		
	strCode += '</table>';
	
	return strCode;
}

/* ====================================================================== */

function hideContractDIVs(sModus)
{

	if(sModus == 'onAdd_onEdit_offList')
	{
//		document.getElementById('addContract').style.display = 'inline';
		document.getElementById('editContract').style.display = 'block';
		document.getElementById('contractsList').style.display = 'none';
	}

	if(sModus == 'offAdd_offEdit_onList')
	{
//		document.getElementById('addContract').style.display = 'none';
		document.getElementById('editContract').style.display = 'none';
		document.getElementById('contractsList').style.display = 'block';
	}

}

/* ====================================================================== */

function cleanContractFields(sModus)
{
	// Clear position fields
	document.getElementById('acc_2_from').value						= '';
	document.getElementById('acc_2_until').value					= '';

	if(iContractsLength != 0)
	{

		document.getElementById('acc_2_social_security_number').value	= aContracts[iContractsLength-1]['social_security_number'];
		document.getElementById('acc_2_religion').value					= aContracts[iContractsLength-1]['religion'];
		document.getElementById('acc_2_health_insurance').value			= aContracts[iContractsLength-1]['health_insurance'];
		document.getElementById('acc_2_gross_salary').value				= parseFloat(aContracts[iContractsLength-1]['gross_salary']).number_format(2,',','.');
		document.getElementById('acc_2_salary').value					= parseFloat(aContracts[iContractsLength-1]['salary']).number_format(2,',','.');
		document.getElementById('acc_2_hours_value').value				= parseFloat(aContracts[iContractsLength-1]['hours_value']).number_format(2,',','.');
		document.getElementById('acc_2_days_per_week').value			= parseFloat(aContracts[iContractsLength-1]['days_per_week']).number_format(2,',','.');
		document.getElementById('acc_2_holiday').value					= aContracts[iContractsLength-1]['holiday'];
		document.getElementById('acc_2_tax_number').value				= aContracts[iContractsLength-1]['tax_number'];
		document.getElementById('acc_2_factor').value					= aContracts[iContractsLength-1]['factor'];
	
	
		for(var i = 0; i < document.getElementById('acc_2_tax_class').options.length; i++)
		{
			if(document.getElementById('acc_2_tax_class').options[i].value == aContracts[iContractsLength-1]['tax_class'])
			{
				document.getElementById('acc_2_tax_class').options[i].selected = 'selected';
				
				break;
			}
		}
	
	
		for(var i = 0; i < document.getElementById('acc_2_hours_type').options.length; i++)
		{
			if(document.getElementById('acc_2_hours_type').options[i].value == aContracts[iContractsLength-1]['hours_type'])
			{
				document.getElementById('acc_2_hours_type').options[i].selected = 'selected';
				
				break;
			}
		}
	
	}
	
	else
	
	{
		document.getElementById('acc_2_social_security_number').value	= '';
		document.getElementById('acc_2_religion').value					= '';
		document.getElementById('acc_2_health_insurance').value			= '';
		document.getElementById('acc_2_tax_class').options[0].selected	= 'selected';
		document.getElementById('acc_2_tax_number').value				= '';
		document.getElementById('acc_2_factor').value					= '';
		document.getElementById('acc_2_gross_salary').value				= '';
		document.getElementById('acc_2_salary').value					= '';
		document.getElementById('acc_2_hours_type').options[0].selected	= 'selected';
		document.getElementById('acc_2_hours_value').value				= '';
		document.getElementById('acc_2_days_per_week').value			= '';
		document.getElementById('acc_2_holiday').value					= '';
	}
	
	
	

	
	

	

	if(sModus == 1)
	{
		document.getElementById('contractEditSaveButton').style.display	= 'none';
		document.getElementById('contractAddButton').style.display		= 'inline';
	}
}

/* ====================================================================== */

function manageContract(sTask, sDataJSON)
{

	var bError = false;

	var strRequestUrl = '/admin/extensions/office.ajax.php';
	var strParameters = 'task=add_contract';

	if(sTask == 'edit')
	{
		strParameters = 'task=edit_contract';
		strParameters += '&id='	+ iEditContractID;
	}
	else if(sTask == 'delete')
	{
		var sContractID = selectedContractRow;
		if(sContractID != 0)
		{
			sContractID = sContractID.substr(7);
		}
		strParameters = 'task=delete_contract';
		strParameters += '&id='	+ sContractID;
	}
	else if(sTask == 'pre_edit')
	{
		var sContractID = selectedContractRow;
		if(sContractID != 0)
		{
			sContractID = sContractID.substr(7);
			for (var i = 0; i < aContracts.length; i++)
			{
				if(aContracts[i]['id'] == parseInt(sContractID))
				{
					editContract(0, aContracts[i]);
					break;
				}
			}
			return;
		}
	}
	else if(sTask == 'sort')
	{
		strParameters = 'task=sort_contracts';
		strParameters += '&sort_array='	+ sDataJSON;

		selectedContractRow = 0;
	}

	strParameters += '&employee_id='				+ iEmployeeID;
	strParameters += '&from='						+ encodeURIComponent(document.getElementById('acc_2_from').value);
	strParameters += '&until='						+ encodeURIComponent(document.getElementById('acc_2_until').value);
	strParameters += '&social_security_number='		+ encodeURIComponent(document.getElementById('acc_2_social_security_number').value);
	strParameters += '&religion='					+ encodeURIComponent(document.getElementById('acc_2_religion').value);
	strParameters += '&tax_class='					+ encodeURIComponent(document.getElementById('acc_2_tax_class').value);
	strParameters += '&tax_number='					+ encodeURIComponent(document.getElementById('acc_2_tax_number').value);
	strParameters += '&factor='						+ encodeURIComponent(document.getElementById('acc_2_factor').value);
	strParameters += '&health_insurance='			+ encodeURIComponent(document.getElementById('acc_2_health_insurance').value);
	strParameters += '&gross_salary='				+ encodeURIComponent(document.getElementById('acc_2_gross_salary').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&salary='						+ encodeURIComponent(document.getElementById('acc_2_salary').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&hours_type='					+ encodeURIComponent(document.getElementById('acc_2_hours_type').value);
	strParameters += '&hours_value='				+ encodeURIComponent(document.getElementById('acc_2_hours_value').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&days_per_week='				+ encodeURIComponent(document.getElementById('acc_2_days_per_week').value.replace(/\./g, '').replace(/,/, '.'));
	strParameters += '&holiday='					+ encodeURIComponent(document.getElementById('acc_2_holiday').value);

	if(sContractID == 0 && sTask == 'delete')
	{
		alert('Bitte markieren Sie den zu löschenden Vertragsparameter!');
		bError = true;
	}
	else if(sContractID == 0 && sTask == 'pre_edit')
	{
		alert('Bitte markieren Sie den zu bearbeitenden Vertragsparameter!');
		bError = true;
	}
	else if(
		sTask != 'delete'
			&&
		sTask != 'sort'
			&&
		sTask != 'pre_edit'
	)
	{
		if(document.getElementById('acc_2_from').value == '')
		{
			alert('Bitte geben Sie einen gültigen Startzeitpunkt an.');
			bError = true;
		}
	}

	if(
		sContractID != 0
			&&
		sTask == 'delete'
			&&
		bError == false
	)
	{
		if(confirm('Möchten Sie wirklich den markierten Vertragsparameter löschen?') == false)
		{
			return;
		}
		else
		{
			selectedContractRow = 0;
		}
	}

	if(bError == false)
	{
		try
		{
			var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : manageContractCallback
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

function manageContractCallback(objResponse)
{
	var objData = objResponse.responseText.evalJSON();
	var arrData = objData['contracts'];

	if(objData['ERROR'])
	{
		switch(objData['ERROR'])
		{
			case 'from_date': alert('Fehler! Bitte überprüfen Sie das Startdatum.'); return;
			case 'hours_value':
			case 'days_per_week': alert('Fehler! Bitte geben Sie Stunden sowie Arbeitstage/Wo. an.'); return;
			case 'overlapping': alert('Fehler! Manche Verträge überschneiden sich in Zeiträumen.'); return;
			default: alert('Fehler! Der Vertrag konnte nicht gespeichert werden!'); return;
		}
	}

	aContracts = arrData;

	cleanContractFields();

	// Display / hide DIVs with buttons
	document.getElementById('contractEditSaveButton').style.display		= 'none';
	document.getElementById('contractAddButton').style.display			= 'inline';

	// Enable 'back to Contracts list' button
	document.getElementById('backToListButton').style.display = 'inline';

	hideContractDIVs('offAdd_offEdit_onList');

//	if (aContracts.length == 0)
//	{
//		hideContractDIVs('onAdd_onEdit_offList');
//
//		// Disable 'back to positions list' button
//		document.getElementById('backToListButton').style.display = 'none';
//	}

	createContractsList();

}

/* ====================================================================== */

function createContractsList()
{

	var tbody 		= document.getElementById('tbl_contracts');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5, td6;

	// Remove all contracts
	while(tbody.hasChildNodes()) {
		tbody.removeChild(tbody.firstChild);
	}

	// Create new contracts
    for(var i = 0; i < aContracts.length; i++, c++)
    {
        objTr = tr.cloneNode(false);
        tbody.appendChild(objTr);
        var strId = 'con_tr_' + aContracts[i]['id'];
        objTr.id = strId;

		if(aContracts[i]['validity'] == 1)
		{
			objTr.style.backgroundColor = '#67E667';
		}

        Event.observe(objTr, 'click', checkContractRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', editContract.bindAsEventListener(c, aContracts[i]));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement("td");
		objTr.appendChild(td0);
		td0.innerHTML = aContracts[i]['from'];

		td1 = document.createElement("td");
		objTr.appendChild(td1);
		td1.innerHTML = aContracts[i]['until'];

		td2 = document.createElement("td");
		objTr.appendChild(td2);
		td2.innerHTML = parseFloat(aContracts[i]['gross_salary']).number_format(2,',','.');
		td2.style.textAlign = 'right';

		td3 = document.createElement("td");
		objTr.appendChild(td3);
		td3.innerHTML = parseFloat(aContracts[i]['salary']).number_format(2,',','.');
		td3.style.textAlign = 'right';

		td4 = document.createElement("td");
		objTr.appendChild(td4);
		td4.innerHTML = parseFloat(aContracts[i]['hours_value']).number_format(2,',','.');
		if(aContracts[i]['hours_type'] === 'week') {
			td4.innerHTML += ' / Woche';
		} else if(aContracts[i]['hours_type'] === 'month') {
			td4.innerHTML += ' / Monat';
		} else {
			td4.innerHTML += ' / ' + aContracts[i]['hours_type'];
		}
		td4.style.textAlign = 'right';

		td5 = document.createElement("td");
		objTr.appendChild(td5);
		td5.innerHTML = aContracts[i]['holiday'];
		td5.style.textAlign = 'right';

		td6 = document.createElement("td");
		objTr.appendChild(td6);
		td6.innerHTML = aContracts[i]['factor'] + ' %';
		td6.style.textAlign = 'right';

		td0 = td1 = td2 = td3 = td4 = td5 =td6 = null;
    }

//	document.getElementById('payment_amount_div').style.display = 'inline';

    tbody = null;

	// Initialize the table and table rows for moving the articles positions
	if(aContracts.length > 1)
	{
		// Sorting of positions with drop & drag
//		var oTable = document.getElementById('tableContracts');
//		var oTableDnD = new TableDnD();
//		oTableDnD.init(oTable);

//		var aRowIDs = new Array();
//		oTableDnD.onDrop = function(oTable, oDroppedRow)
//		{
//			oDroppedRow.className = '';
//			aRowIDs = new Array();
//			var aRows = this.table.tBodies[0].rows;

//			if(bMovingFlag == true)
//			{
				// Filter the position IDs
//				for (i = 0; i < aRows.length; i++)
//				{
//					aRowIDs.push(aRows[i].id.substr(7));
//					aRows[i].className = '';
//				}

				// Only if the position was realy moved
				// do AJAX request for updating of article positions
//				bMovingFlag = false;
//				managePosition('sort', aRowIDs.toJSON());
//			}
//		}
	}
}

/* ====================================================================== */

var selectedContractRow = 0;
function checkContractRow(e, strId)
{

	var objRow = $(strId);

	if(
		selectedContractRow && 
		$(selectedContractRow)
	) {
		$(selectedContractRow).className = "";
	}

	if(objRow.className == "") {
		objRow.className = "selectedRow";
	} else {
		objRow.className = "";
	}
	selectedContractRow = strId;

}

/* ====================================================================== */

var iEditContractID = 0;
function editContract(c, aContract)
{

	// Set the id of contract which is to edit
	iEditContractID = aContract['id'];

	// Display / hide DIVs
	document.getElementById('contractEditSaveButton').style.display	= 'inline';
//	document.getElementById('contractAddButton').style.display			= 'none';
	document.getElementById('editContract').style.display				= 'block';
	document.getElementById('contractsList').style.display				= 'none';
	document.getElementById('contractAddButton').style.display			= 'none';
	

	// Fill the fields with contract data
	document.getElementById('acc_2_from').value							= aContract['from'];
	document.getElementById('acc_2_until').value						= aContract['until'];
	document.getElementById('acc_2_social_security_number').value		= aContract['social_security_number'];
	document.getElementById('acc_2_religion').value						= aContract['religion'];
	document.getElementById('acc_2_health_insurance').value				= aContract['health_insurance'];
	document.getElementById('acc_2_gross_salary').value					= parseFloat(aContract['gross_salary']).number_format(2,',','.');
	document.getElementById('acc_2_salary').value						= parseFloat(aContract['salary']).number_format(2,',','.');
	document.getElementById('acc_2_hours_value').value					= parseFloat(aContract['hours_value']).number_format(2,',','.');
	document.getElementById('acc_2_days_per_week').value				= parseFloat(aContract['days_per_week']).number_format(2,',','.');
	document.getElementById('acc_2_holiday').value						= aContract['holiday'];
	document.getElementById('acc_2_tax_number').value					= aContract['tax_number'];
	document.getElementById('acc_2_factor').value						= aContract['factor'];

	for(var i = 0; i < document.getElementById('acc_2_tax_class').options.length; i++)
	{
		if(document.getElementById('acc_2_tax_class').options[i].value == aContract['tax_class'])
		{
			document.getElementById('acc_2_tax_class').options[i].selected = 'selected';
			
			break;
		}
	}

	for(var i = 0; i < document.getElementById('acc_2_hours_type').options.length; i++)
	{
		if(document.getElementById('acc_2_hours_type').options[i].value == aContract['hours_type'])
		{
			document.getElementById('acc_2_hours_type').options[i].selected = 'selected';
			
			break;
		}
	}
}

/* ====================================================================== */

