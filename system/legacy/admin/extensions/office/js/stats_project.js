
/* ==================================================================================================== */

function loadProjectStatsList()
{
	$('loader').show();

	var sParams = 'task=get_project_stats_list';

	sParams += '&project_id='	+ $('filter_project').value;
	sParams += '&from='			+ $('filter_from').value;
	sParams += '&till='			+ $('filter_till').value;

	var oAjax = new Ajax.Request(
		'/admin/extensions/office.ajax.php',
		{
			method		: 'post',
			parameters	: sParams,
			onComplete	: loadProjectStatsListCallback
		}
	);
}

/* ==================================================================================================== */

function loadProjectStatsListCallback(oResponse)
{
	var oData = oResponse.responseText.evalJSON();

	var aProjects = oData['projects'];

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

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Fill projects DropDown

	var iSelectedProject = $('filter_project').value;

	while($('filter_project').length > 1)
	{
		$('filter_project').options[1] = null;
	}

	if($('filter_project').value != '' && oData['data'].length == 0)
	{
		$('filter_project').selectedIndex = 0;

		loadProjectStatsList();

		return;
	}

	for(var i = 0; i < aProjects.length; i++)
	{
		var newOption = document.createElement('option');

		// Set option value
		newOption.setAttribute('value', aProjects[i]['id']);

		// Set selected value
		if(aProjects[i]['id'] == iSelectedProject)
		{
			newOption.setAttribute('selected', 'selected');
		}

		// Set option content
		newOption.innerHTML = aProjects[i]['title'];

		// Add option to select
		$('filter_project').appendChild(newOption);
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

    var td0, td1, td2, td3, td4, td5, td6, td7, td8;

	for(var i = 0; i < oData['data'].length; i++, c++)
	{
		var aData = oData['data'][i];

		objTr = tr.cloneNode(false);
		tbody.appendChild(objTr);
		var strId = 'tr_' + i;
		objTr.id = strId;

        //Event.observe(objTr, 'click', checkContractRow.bindAsEventListener(c, strId));
		//Event.observe(objTr, 'dblclick', openWorktimesDialog.bindAsEventListener(c, aData['id']));
		Event.observe(objTr, 'mouseout', resetHighlightRow);
		Event.observe(objTr, 'mousemove', setHighlightRow);

		if(oData['data'][i + 1])
		{
			var sElement = 'td';
		}
		else
		{
			var sElement = 'th';
		}

		td0 = document.createElement(sElement);
		objTr.appendChild(td0);
		td0.style.textAlign = 'left';
		td0.innerHTML = aData['name'];

		td1 = document.createElement(sElement);
		objTr.appendChild(td1);
		td1.style.textAlign = 'right';
		td1.innerHTML = aData['total'];

		td2 = document.createElement(sElement);
		objTr.appendChild(td2);
		td2.style.textAlign = 'right';
		td2.innerHTML = aData['factored'];

		td3 = document.createElement(sElement);
		objTr.appendChild(td3);
		td3.style.textAlign = 'right';
		td3.innerHTML = aData['bugs'];

		td4 = document.createElement(sElement);
		objTr.appendChild(td4);
		td4.style.textAlign = 'right';
		td4.innerHTML = aData['bugs_total'];

		td5 = document.createElement(sElement);
		objTr.appendChild(td5);
		td5.style.textAlign = 'right';
		td5.innerHTML = aData['bugs_factored'];

		td6 = document.createElement(sElement);
		objTr.appendChild(td6);
		td6.style.textAlign = 'right';
		td6.innerHTML = aData['exts'];

		td7 = document.createElement(sElement);
		objTr.appendChild(td7);
		td7.style.textAlign = 'right';
		td7.innerHTML = aData['exts_total'];

		td8 = document.createElement(sElement);
		objTr.appendChild(td8);
		td8.style.textAlign = 'right';
		td8.innerHTML = aData['exts_factored'];

		td0 = td1 = td2 = td3 = td4 = td5 = td6 = td7 = td8 = null;
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	var iHeight = document.body.clientHeight;

	iHeight -= 58;

	$('divTimes').style.height	= iHeight + 'px';
	$('divTimes').style.width	= document.body.clientWidth + 'px';

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	$('loader').hide();
}

/* ==================================================================================================== */
