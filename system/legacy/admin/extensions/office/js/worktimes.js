
/* ==================================================================================================== */

function loadWorktimesList()
{
	$('loader').show();

	var sParams = 'action=get_worktimes_list';

	sParams += '&year_from='	+ $('filter_year_from').value;
	sParams += '&month_from='	+ $('filter_month_from').value;
	sParams += '&year_till='	+ $('filter_year_till').value;
	sParams += '&month_till='	+ $('filter_month_till').value;

	var oAjax = new Ajax.Request(
		'/admin/extensions/office_employee_worktimes.html',
		{
			method		: 'post',
			parameters	: sParams,
			onComplete	: loadWorktimesListCallback
		}
	);
}

/* ==================================================================================================== */

function loadWorktimesListCallback(oResponse)
{
	var oData = oResponse.responseText.evalJSON();

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write info dates

	if(oData['ERROR'] && oData['ERROR'] == 'FILTER')
	{
		$('infoDates').innerHTML = '<b style="color:red;">Filterfehler!</b>';

		$('loader').hide();

		return;
	}
	else
	{
		$('infoDates').innerHTML = oData['dates']['from'] + ' - ' + oData['dates']['till'];
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Filter correcture

	$('filter_month_from').selectedIndex = parseInt(oData['dates']['month_from']) - 1;
	$('filter_month_till').selectedIndex = parseInt(oData['dates']['month_till']) - 1;

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	if($('tblTimes'))
	{
		
	}
	else
	{
		return false;
	}

	while($('tblTimes').hasChildNodes())
	{
		$('tblTimes').removeChild($('tblTimes').firstChild);
	}

	var tbody 		= $('tblTimes');
	var c 			= 0;
	var tr    		= document.createElement('tr');
    var objTr   	= tr.cloneNode(true);

    var td0, td1, td2, td3, td4, td5, td6, td7, td8;

	for(var i = 0; i < oData['data'].length; i++, c++)
	{
		var aData = oData['data'][i];

		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		var strId = 'tr_' + aData['id'];
		objTr.id = strId;

        //Event.observe(objTr, 'click', checkContractRow.bindAsEventListener(c, strId));
		Event.observe(objTr, 'dblclick', openWorktimesDialog.bindAsEventListener(c, aData['id']));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		td0 = document.createElement('td');
		objTr.appendChild(td0);
		td0.style.textAlign = 'left';
		td0.innerHTML = aData['name'];

		td1 = document.createElement('td');
		objTr.appendChild(td1);
		if(aData['absence'].substr(0, 1) == '-')
		{
			td1.style.color = 'red';
		}
		td1.innerHTML = aData['absence'];

		td2 = document.createElement('td');
		objTr.appendChild(td2);
		td2.innerHTML = aData['worked'];

		td3 = document.createElement('td');
		objTr.appendChild(td3);
		td3.innerHTML = aData['have_to'];

		td4 = document.createElement('td');
		objTr.appendChild(td4);
		td4.innerHTML = aData['to_work'];

		td5 = document.createElement('td');
		objTr.appendChild(td5);
		td5.innerHTML = aData['holi'];

		td6 = document.createElement('td');
		objTr.appendChild(td6);
		td6.innerHTML = aData['sick'];

		td7 = document.createElement('td');
		objTr.appendChild(td7);
		td7.innerHTML = aData['over'];

		td8 = document.createElement('td');
		objTr.appendChild(td8);
		td8.innerHTML = aData['over_paid'];

		td0 = td1 = td2 = td3 = td4 = td5 = td6 = td7 = td8 = null;
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	resize();

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	$('loader').hide();
}

/* ==================================================================================================== */

var oLightBox = null;

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

	intHeight -= 82;

	$('divTimes').style.height	= intHeight + 'px';
	/*$('divTimes').style.width	= document.body.clientWidth + 'px';*/
}

function openWorktimesDialog(c, iID)
{
	$('loader').show();

	var sParams = 'action=get_worktimes_graphic';

	sParams += '&employee_id=' + iID;

	if($('dialog_filter_year'))
	{
		sParams += '&year='		+ $('dialog_filter_year').value;
		sParams += '&month='	+ $('dialog_filter_month').value;
	}

	var oAjax = new Ajax.Request(
		'/admin/extensions/office_employee_worktimes.html',
		{
			method		: 'post',
			parameters	: sParams,
			onComplete	: openWorktimesDialogCallback
		}
	);
}

function openWorktimesDialogCallback(oResponse)
{
	var oData = oResponse.responseText.evalJSON();

	var sCode = '';

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create filter DDs

	var sMonth = '<select class="txt" id="dialog_filter_month" onchange="openWorktimesDialog(0, ' + oData['employee_id'] + ')">';
		for(var i = 0; i < $('filter_month_from').length; i++)
		{
			var sSelected = '';
			if(parseInt($('filter_month_from').options[i].value) == parseInt(oData['month']))
			{
				sSelected = 'selected="selected"';
			}
			sMonth += '<option value="' + $('filter_month_from').options[i].value + '" ' + sSelected + '>' + $('filter_month_from').options[i].innerHTML + '</option>';
		}
	sMonth += '</select> ';
	
	var sYear = '<select class="txt" id="dialog_filter_year" onchange="openWorktimesDialog(0, ' + oData['employee_id'] + ')">';
		for(var i = 0; i < $('filter_year_from').length; i++)
		{
			var sSelected = '';
			if(parseInt($('filter_year_from').options[i].value) == parseInt(oData['year']))
			{
				sSelected = 'selected="selected"';
			}
			sYear += '<option value="' + $('filter_year_from').options[i].value + '" ' + sSelected + '>' + $('filter_year_from').options[i].innerHTML + '</option>';
		}
	sYear += '</select>';

	sCode += '<div style="padding:5px; text-align:center;">';

		sCode += sMonth;
		sCode += sYear;

		sCode += '<img id="monthGraph" src="' + oData['file'] + '" />';

	sCode += '</div>';

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	if($('monthGraph'))
	{
		$('monthGraph').src = oData['file'];
	}
	else
	{
		oLightBox = new LITBox(sCode, {type:'alert', overlay:true, height:320, width:800, resizable:false, opacity:.9});
	}

	$('loader').hide();
}
