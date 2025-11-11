
/* ==================================================================================================== */

function loadAbsencesList()
{
	$('loader').show();

	createMonthsHeader();

	var sParams = 'action=get_absences_list';

	sParams += '&year='		+ $('filter_year').value;
	sParams += '&month='	+ $('filter_month').value;

	var oAjax = new Ajax.Request(
		'/admin/extensions/office_absence.html',
		{
			method		: 'post',
			parameters	: sParams,
			onComplete	: loadAbsencesListCallback
		}
	);
}

/* ==================================================================================================== */

function loadAbsencesListCallback(oResponse)
{
	var oData = oResponse.responseText.evalJSON();
	var aHead = oData['head'];
	var aData = oData['data'];

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Clear table

	var aDays = $A($$('.monthDays'));

	aDays.each(function(oDay)
	{
		oDay.remove();
	});

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write the header

	for(var i = 0; i < 3; i++)
	{
		var sCode = '';

		$((i+1) + '_Month').colSpan = aHead[i]['days'].length;

		var aDays = aHead[i]['days'];

		aDays.each(function(aDay)
		{
			var sColor = '#EEE';

			if(aDay['color'] != '')
			{
				sColor = aDay['color'];
			}

			sCode += '<th class="monthDays" style="padding: 4px 0; background-color:' + sColor + ';">' + aDay['day'] + '</th>';
		});

		$('daysHeader').insert({bottom: sCode});
	}

	while($('tblAbsences').hasChildNodes())
	{
		$('tblAbsences').removeChild($('tblAbsences').firstChild);
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write the data

	aData.each(function(aEmployee)
	{
		var sCode = '';

		sCode += '<tr>';
			sCode += '<th class="monthDays" style="font-size:10px; padding: 4px 2px; white-space:nowrap;" nowrap="nowrap">' + aEmployee['name'] + '</th>';

			for(var i = 0; i < 3; i++)
			{
				var aDays = aEmployee['data'][i]['days'];

				aDays.each(function(aDay)
				{
					var sDate = aDay['day'] + '.' + aEmployee['data'][i]['month'] + '.' + aEmployee['data'][i]['year'];

					var sClick = 'onclick="addEntryDialog(' + aEmployee['id'] + ', \'' + sDate + '\');"';

					var sPointer = 'cursor:pointer;';

					var sColor = '#FFF';

					if(aDay['color'] != '')
					{
						sColor = aDay['color'];

						sClick = sPointer = '';
					}

					if(aDay['entries'])
					{
						// Count total quote
						var iTotalQuote = 0;

						aDay['entries'].each(function(aEntry)
						{
							iTotalQuote += aEntry['quote'];
						});

						if(iTotalQuote >= 100)
						{
							sClick = sPointer = '';
						}

						// Write the cell
						sCode += '<td class="monthDays w10" style="' + sPointer + ' padding:0;" ' + sClick + '>';
							aDay['entries'].each(function(aEntry)
							{
								sCode += '<div style="float:left; height:20px; width:' + aEntry['quote'] + '%; background-color:' + aEntry['color'] + ';"></div>';
							});
						sCode += '</td>';
					}
					else
					{
						sCode += '<td class="monthDays w10" ' + sClick + ' style="' + sPointer + ' background-color:' + sColor + ';">&nbsp;</td>';
					}
				});
			}

		sCode += '</tr>';

		$('tblAbsences').insert({bottom: sCode});
	});

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	resize();

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	$('loader').hide();
}

/* ==================================================================================================== */

var oAbsenceDialog = null;

function resize()
{
	var intHeight = 0;
	intHeight = window.innerHeight;

	if(!intHeight) {
		intHeight = document.body.clientHeight;
	}

	if(!intHeight) {
		intHeight = document.documentElement.clientHeight;
	}

	intHeight -= 58;

	$('divDays').style.height	= intHeight + 'px';
	/*$('divDays').style.width	= document.body.clientWidth + 'px';*/
}

function addEntryDialog(iEmployeeID, sDate)
{
	var oGUI = new GUI;

	var aType = new Array(
		new Array('holiday', 'Urlaub'),
		new Array('sick', 'Krank'),
		new Array('overtime', 'Überstunden (Frei)'),
		new Array('overtime_paid', 'Überstunden (€)')
	);

	var sCode = '';

	sCode += '<input type="hidden" id="absence_employee_id" value="' + iEmployeeID + '" />';

	sCode += '<div style="padding:10px;">';
		sCode += oGUI.printFormSelect('Typ:', 'absence_entry_typ', aType, '', '');
		sCode += oGUI.printFormInput('Startdatum:', 'absence_entry_date', sDate, 'readonly="readonly" style="width:80px;"', '');
		sCode += oGUI.printFormInputNumbers('Anzahl:', 'absence_entry_days', '', '', 'Tage');
		sCode += oGUI.printFormInputNumbers('Anzahl:', 'absence_entry_hours', '', '', 'Stunden');
		sCode += oGUI.printFormTextarea('Notiz:', 'absence_entry_notice', '', '5', '5', 'style="width:300px; height:75px;"');

		sCode += '<div style="text-align:right; margin-right:7px;">';
			sCode += '<input class="btn" type="button" onclick="saveAbsenceEntry();" value="Speichern" style="opacity:1; filter:alpha(opacity=100);" />';
		sCode += '</div>';
	sCode += '</div>';

	oAbsenceDialog = new LITBox(sCode, {type:'alert', overlay:true, height:240, width:430, resizable:false, opacity:.9});
}

function exportSicknessAbsence() {

	var sParams = 'task=export_sickness_absence';

	sParams += '&year='		+ $('filter_year').value;
	sParams += '&month='	+ $('filter_month').value;

	window.open('/admin/extensions/office_absence.html?'+sParams);

	return false;

}

function saveAbsenceEntry()
{
	$('loader').show();

	var sParams = 'task=add_holiday';

	var aDate = $('absence_entry_date').value.split('.');

	sParams += '&employee_id='	+ $('absence_employee_id').value;
	sParams += '&from='			+ encodeURIComponent(aDate[2] + '-' + aDate[1] + '-' + aDate[0]);
	sParams += '&till_days='	+ encodeURIComponent($('absence_entry_days').value);
	sParams += '&till_hours='	+ encodeURIComponent($('absence_entry_hours').value);
	sParams += '&type='			+ encodeURIComponent($('absence_entry_typ').value);
	sParams += '&notice='		+ encodeURIComponent($('absence_entry_notice').value);

	var oAjax = new Ajax.Request(
		'/admin/extensions/office.ajax.php',
		{
			method:		'post',
			parameters:	sParams,
			onComplete:	saveAbsenceEntryCallback
		}
	);
}

function saveAbsenceEntryCallback()
{
	loadAbsencesList();

	oAbsenceDialog.remove();
}

/* ==================================================================================================== */

function createMonthsHeader()
{
	if($('filter_month').value == 1)
	{
		$('1_Month').innerHTML = $('filter_month').options[11].innerHTML + ' ' + (parseInt($('filter_year').value) - 1);

		$('2_Month').innerHTML = $('filter_month').options[0].innerHTML + ' ' + $('filter_year').value;

		$('3_Month').innerHTML = $('filter_month').options[1].innerHTML + ' ' + $('filter_year').value;
	}
	else if($('filter_month').value == 12)
	{
		$('1_Month').innerHTML = $('filter_month').options[10].innerHTML + ' ' + $('filter_year').value;

		$('2_Month').innerHTML = $('filter_month').options[11].innerHTML + ' ' + $('filter_year').value;

		$('3_Month').innerHTML = $('filter_month').options[0].innerHTML + ' ' + (parseInt($('filter_year').value) + 1);
	}
	else
	{
		$('1_Month').innerHTML = $('filter_month').options[$('filter_month').selectedIndex - 1].innerHTML + ' ' + $('filter_year').value;

		$('2_Month').innerHTML = $('filter_month').options[$('filter_month').selectedIndex].innerHTML + ' ' + $('filter_year').value;

		$('3_Month').innerHTML = $('filter_month').options[$('filter_month').selectedIndex + 1].innerHTML + ' ' + $('filter_year').value;
	}
}
