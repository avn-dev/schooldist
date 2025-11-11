

var oGui = new Object();
var bExport = false;
var aFields = [
	'report_id',
	'search',
	'course_id',
	'course_category_id',
	'week',
	'state_booking',
	'state_course',
	'weekday',
	'tuition_template',
	'teacher_id',
	'inbox_id',
	'class_color',
	'course_start_from',
	'course_start_until',
	'courselanguage_id'
];

function initOverviewPage() {

	oGui = new ReportGui(1);

	Event.observe(window, 'resize', setWindowSizes);

	setWindowSizes();

	$j.each(aFields, function(iKey, sField) {
		$j('#' + sField).change(loadTable);
	});

	$j.each(aTranslations, function(sKey, sTranslation) {
		oGui.translations[sKey] = sTranslation;
	});

	createToggleIcon();

}

/* ==================================================================================================== */

function setWindowSizes()
{
	var oDivFooter = $('divFooter');
	var iFooterHeight = 0;
	if(oDivFooter){
		iFooterHeight = oDivFooter.getHeight();
	}

	var iHeaderHeight = $j('.divHeader').height();

	$('blocksScroller').style.height	= (oGui.getDocumentHeight() - 15 - iHeaderHeight - iFooterHeight) + 'px';
	//$('blocksScroller').style.width		= (oGui.getDocumentWidth()) + 'px';
}

function createToggleIcon() {

	var aToolBars = $$('.divToolbar');
	var oDivCleaner = aToolBars[0].down('.flex-none');
	var oToggleIcon = oGui.createBarToogleIcon();

	oDivCleaner.insert(oToggleIcon);

	oGui.aBars.push(aToolBars[0]);
	oGui.resizeBars();
}

/* ==================================================================================================== */

var bVisibleLeftFrame = true;

function toggleLeftFrame()
{
	var oGUI = oGui;

	$('toggleMenu_1').stopObserving('click');

	if(bVisibleLeftFrame)
	{
		bVisibleLeftFrame = false;

		oGUI.hideLeftFrame();

		Event.observe($('toggleMenu_1'), 'click', function()
		{
			toggleLeftFrame();
	    }.bind(oGUI));
	}
	else
	{
		bVisibleLeftFrame = true;

		oGUI.showLeftFrame();

		Event.observe($('toggleMenu_1'), 'click', function()
		{
			toggleLeftFrame();
	    }.bind(oGUI));
	}
}

/* ==================================================================================================== */

function loadTable()
{
	if(parseInt($('week').value) <= 0 || parseInt($('report_id').value) <= 0)
	{
		bExport = false;

		return false;
	}

	var sParams = 'action=load_table';
	sParams += getParams();

	$('loading_indicator').show();

	new Ajax.Request('/admin/ts-tuition/own-overview/load-table',
		{
			method:		'post',
			parameters:	sParams,
			onSuccess:	loadTableCallback
		}
	);
}

/* ==================================================================================================== */

function loadTableCallback(oReturn)
{
	var aData = oReturn.responseText.evalJSON();

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	while($('blocksContainer').hasChildNodes())
	{
		$('blocksContainer').removeChild($('blocksContainer').firstChild);
	}

	if(aData['code'] != false)
	{
		bExport = true;

		$('blocksContainer').innerHTML = aData['code'];
	}
	else
	{
		bExport = false;

		$('blocksContainer').innerHTML = aData['message'];
	}

	$('loading_indicator').hide();
}

/* ==================================================================================================== */

function getExport(sType){

	var iReportVal = $F('report_id');
	if(parseInt(iReportVal) == 0) {
		alert(aTranslations.export_error);
		return;
	}

	var sParams = 'action=get_export';
	sParams += '&export_type=' + sType;
	sParams += getParams();

	window.open('/admin/ts-tuition/own-overview/export?' + sParams);
}

/* ==================================================================================================== */

function changeFilterWeek(sType)
{
	switch(sType)
	{
		case 'last':
		{
			var iIndex = $('week').selectedIndex;

			if(iIndex > 0)
			{
				$('week').selectedIndex = iIndex - 1;
			}

			break;
		}
		case 'current':
		{
			for(var i = 0; i < $('week').length; i++)
			{
				if($('week').options[i].value == iCurrentWeek)
				{
					$('week').selectedIndex = i;

					break;
				}
			}

			break;
		}
		case 'next':
		{
			var iIndex = $('week').selectedIndex;

			if(iIndex < $('week').length - 1)
			{
				$('week').selectedIndex = iIndex + 1;
			}

			break;
		}
	}

	loadTable();
}

function getParams() {
	var sParams = '';

	$j.each(aFields, function(iKey, sField) {
		sParams += '&' + sField + '=' + $j('#' + sField).val();
	});

	return sParams;
}