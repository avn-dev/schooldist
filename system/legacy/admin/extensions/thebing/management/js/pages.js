
function initStatisticPage() {

	$j('[data-filter="date"]').each(function(iKey, oCalendar) {
		oCalendar = $(oCalendar);
		$j(oCalendar).bootstrapDatePicker({
			weekStart: 1,
			todayHighlight: true,
			todayBtn: 'linked',
			calendarWeeks: true,
			format: sDateFormat,
			autoclose: true,
			assumeNearbyYear: true
		});
	}.bind(this));

	aGUI[1] = new StatisticGui(1);

	Event.observe(window, 'resize', setWindowSizes);
	setWindowSizes();

	// Feld ist bei Statistikenseiten nicht da
	var oStatisticSelect = $('statistic');
	if(oStatisticSelect) {
		oStatisticSelect.disabled = false;
		oStatisticSelect.observe('change', function() {
			if(aStatisticTypes[$('statistic').value] == 2) {
				// absolut
				$('date_filter').show();
				$('date_filter_separator').show();
				//$('filter_separator').show();
			} else {
				// relativ
				$('date_filter').hide();
				$('date_filter_separator').hide();
				//$('filter_separator').hide();
				loadTable();
			}
		}.bind(aStatisticTypes));
	}

}

/* ==================================================================================================== */

function setWindowSizes()
{
	// TODO: Das kann man auch mit float: left lösen (z.B. bei zusätzlichen Gebühren)
	$('blocksScroller').style.height	= (aGUI[1].getDocumentHeight() - 62) + 'px';
	//$('blocksScroller').style.width		= (aGUI[1].getDocumentWidth()) + 'px';
}

/* ==================================================================================================== */

function loadTable(bFirstCall, mStatisticID)
{
	var sParams = 'action=load_table';

	sParams += '&page_id=' + iGlobalPageID;
	sParams += '&from=' + $F('from');
	sParams += '&till=' + $F('till');
	sParams += '&class=' + sStaticReportClass;

	if($('statistic'))
	{
		sParams += '&statistic_id=' + $F('statistic');

		if($F('statistic') == 0)
		{
			return;
		}
	}
	else if(mStatisticID)
	{
		sParams += '&statistic_id=' + mStatisticID;
	}

	if(bFirstCall)
	{
		sParams += '&first_call=1';
	}
	else
	{
		$A($$('.GUIDialogContentPadding')).each(function(oDIV)
		{
			var iID = oDIV.id.replace(/blockFilter_/, '');

			sParams += '&filter[' + iID + ']=' + encodeURIComponent($('filterForm_' + iID).serialize());
		});
	}

	$('loading_indicator').show();

	new Ajax.Request('/admin/extensions/thebing/management/statistic.ajax.php', {
		method: 'post',
		parameters: sParams,
		onSuccess: loadTableCallback
	});
}

/* ==================================================================================================== */

function loadTableCallback(oReturn)
{
	var aData = oReturn.responseText.evalJSON();

//	if(aData[0]['first_call'] && aData[0]['show_dates_error'] == true)
//	{
//		$('loading_indicator').hide();
//
//		return;
//	}
//
//	$('from').value = aData[0]['global_filter']['from'];
//	$('till').value = aData[0]['global_filter']['till'];

	if(aData[0]['show_dates_error'] == true) {
		if($('date_filter').style.display != 'none') {
			$('loading_indicator').hide();

			alert(aData[0]['dates_error']);

			return;
		}
	} else {
//		if(aData[0]['global_filter']['from'] == '' || aData[0]['global_filter']['till'] == '') {
//			$('date_filter').hide();
//		}
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	if(aData[0]['block_table'] && aData[0]['block_table'] == 'school_error')
	{
		$('loading_indicator').hide();

		alert(aData[0]['school_error']);

		return;
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	var bAnyEnquiryStatistic = false;
	for(var i = 0; i < aData.length; i++)
	{
		var sHashID = aData[i]['data']['id'] + '_' + aData[i]['hash'];

		if(i == 0)
		{
			if(!$('dialog_wrapper_' + sHashID))
			{
				while($('blocksContainer').hasChildNodes())
				{
					$('blocksContainer').removeChild($('blocksContainer').firstChild);
				}
			}
		}

		if(!aGUI[aData[i]['hash']]) {
			aGUI[aData[i]['hash']] = new StatisticGui(aData[i]['hash']);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write code

		if(!$('dialog_wrapper_' + sHashID))
		{
			var sCode = '';

			sCode += '<div class="infoBox" id="dialog_wrapper_' + sHashID + '">';

			sCode += '<h1>' + aData[i]['block_title'] /*+ '</h1>'*/;

			sCode += '<div class="infoBoxAdditional pull-right"><i class="fa fa-colored fa-print" id="print_' + aData[i]['data']['id'] + '" onclick="printStatistic(\'' + aData[i]['data']['id'] + '\');"></i></div>';

			if(aData[i]['has_export']) {
				sCode += '<div class="infoBoxAdditional pull-right" style="margin-right: 5px;"><i class="fa fa-colored fa-file-excel-o" id="export_' + aData[i]['data']['id'] + '" onclick="exportStatistic(\'' + aData[i]['statistic_id'] + '\');"></i></div>';
			}

			sCode += '</h1>';

			var sStyle = '';
			if(aData[i]['user_has_filter_right'] == false) {
				sStyle = 'display:none';
			}

			sCode += '<h1 class="additionalH1 clearfix" style="' + sStyle + '" onclick="toggleFilterImg(\'' + aData[i]['statistic_id'] + '\');">';
			sCode += aData[i]['filter_title'];
			sCode += '<i id="filter_img_' + aData[i]['statistic_id'] + '" class="fa fa-angle-down pull-right"></i>';
			sCode += '</h1>';

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Filter
			sCode += '<div class="GUIDialogContentPadding" style="display:none;" id="blockFilter_' + aData[i]['statistic_id'] + '">';
				sCode += '<form id="filterForm_' + aData[i]['statistic_id'] + '" class="form-horizontal">';
					sCode += aData[i]['block_filter'];
				sCode += '</form>';
			sCode += '</div>';
			/* - - - - - - - - - - - - - - - - - - - - - - - - -
			 - - - - - */ // Content

			sCode += '<div class="infoBoxContentScroll infoBoxContent" id="blockTable_' + aData[i]['data']['id'] + '">';
			sCode += aData[i]['block_table'];
			sCode += '</div>';

			sCode += '</div>';

			if(aData[i + 1])
			{
				sCode += '<div class="topMarger"></div>';
			}

			$('blocksContainer').insert({ bottom: sCode });

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Call GUI methods for preparing of data

			aGUI[aData[i]['hash']].setDialogSaveFieldValues(aData[i]['data']);
			aGUI[aData[i]['hash']].initializeMultiselects(aData[i]['data']);
			aGUI[aData[i]['hash']].toogleStatisticFields('ID_' + aData[i]['statistic_id']);
		}
		else
		{
			$('dialog_wrapper_' + sHashID).down('.infoBoxContent').innerHTML = aData[i]['block_table'];
		}

		if(aData[i].statistic_period == 5) {
			bAnyEnquiryStatistic = true;
		}
	}

	var aAdditionalCalendars = ['service_from_start', 'service_from_end', 'course_from_start', 'course_from_end', 'created_from', 'created_until'];
	aAdditionalCalendars.forEach(function(sId) {
		$j('#' + sId).bootstrapDatePicker({
			weekStart: 1,
			todayHighlight: true,
			todayBtn: 'linked',
			calendarWeeks: true,
			format: sDateFormat,
			autoclose: true,
			assumeNearbyYear: true
		});
	});

	if(bAnyEnquiryStatistic) {
		$j('#dialog_wrapper_' + sHashID).find('.not_enquiry').hide();
	} else {
		$j('#dialog_wrapper_' + sHashID).find('.not_enquiry').show();
	}

	$('loading_indicator').hide();

}

/* ==================================================================================================== */

// function refreshBlock(sStatisticID) {
// 	loadTable(false, sStatisticID);
// }

/* ==================================================================================================== */

function toggleFilterImg(sStatisticID) {

	$('blockFilter_' + sStatisticID).toggle();

	if($('blockFilter_' + sStatisticID).style.display != 'none') {
		$j('#filter_img_' + sStatisticID).removeClass('fa-angle-down').addClass('fa-angle-up');
	} else {
		$j('#filter_img_' + sStatisticID).removeClass('fa-angle-up').addClass('fa-angle-down');
	}
}

/* ==================================================================================================== */
// TODO : Das Drucken nochmal überarbeiten
function printStatistic(sStatisticID)
{
	var oWin = window.open('_blank', 'printWindow', 'location=no,status=no,width=800,height=600,menubar=yes');

	var sHTML = '<html><head>';
	//sHTML += '<link type="text/css" rel="stylesheet" href="/admin/css/admin.css" media="" />';
	sHTML += '<link type="text/css" rel="stylesheet" href="/assets/ts-reporting/css/reporting_legacy.css" media="screen,print" />';

	sHTML += '<title></title>';
	sHTML += '</head><body style="overflow:visible;"><div class="infoBox"><div class="infoBoxContentScroll infoBoxContent">';

	oWin.document.writeln(sHTML);
	oWin.document.write($('blockTable_' + sStatisticID).innerHTML);
	oWin.document.writeln('</div></div></body></html>');

	setTimeout(function() {
		oWin.document.close();
		oWin.print();
	}, 500);

}

/* ==================================================================================================== */

/**
 * Schick einen Request als Post ab mit Hilfe eines Formulars
 * 
 * @param {String} sUrl
 * @param {String} sName
 * @param {Array} aKeys
 * @param {Array} aValues
 * @returns {Boolean}
 */
function openWindowWithPost(sUrl,sName,aKeys,aValues) {

	var html = "";
	html += "<form id='formid' method='post' action='" + sUrl + "'>";
	html += "<input type='hidden' name='class' value='" + sStaticReportClass + "'/>\n";
	if (aKeys && aValues && (aKeys.length == aValues.length)) {
		for (var i=0; i < aKeys.length; i++) {
			if(Object.isArray(aValues[i])) {
				aValues[i].each(function(mValue) {
					html += "<input type='hidden' name='" + aKeys[i] + "' value='" + mValue + "'/>\n";
				});
			} else {
				html += "<input type='hidden' name='" + aKeys[i] + "' value='" + aValues[i] + "'/>\n";
			}
			
		}
	}
	html += "</form>";

	$('hidden_form').update(html);

	document.getElementById("formid").submit();

	$('hidden_form').update('');

}

function exportStatistic(sStatisticID)
{

	var aKeys = [];
	var aValues = [];

	aKeys.push('action');
	aValues.push('export_excel');

	aKeys.push('from');
	aValues.push($F('from'));

	aKeys.push('till');
	aValues.push($F('till'));

	aKeys.push('statistic_id');
	aValues.push(sStatisticID);

	var aFormElements;
	var sElementName;
	$A($$('.GUIDialogContentPadding')).each(function(oDIV)
	{
		aFormElements = $('filterForm_' + sStatisticID).getElements();

		aFormElements.each(function(oFormElement){

			sElementName = oFormElement.name;
			if(sElementName.length > 0){
				sElementName = sElementName.replace(/save/, '[save]');

				var mValue = oFormElement.getValue();

				if(mValue !== null) {
					aKeys.push('filter[' + sStatisticID + ']' + sElementName);	
					aValues.push(mValue);
				}
				
			}
		});

	});

	openWindowWithPost('/admin/extensions/thebing/management/statistic.ajax.php', 'XXX', aKeys, aValues);
	setTimeout(function() {
		// setTimeout benötigt für Chrome
		$j('.page-loader').hide();
	}, 10);

}
