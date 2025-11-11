
/* ==================================================================================================== */

var sAbsenceAjaxUrl = '/admin/extensions/thebing/absence.ajax.php';
var iColumns = 0;
var aCategories;

var oStartCell = 0;
var aCurrentCells = [];

var oElementCache;

var oGui;

var iColspanCache = 0;
var aColspanCache = [];

var sParamCache = '';
var sAbsenceHash = '';

var bGlobalLocation = '';

function prepareLoadAbsencesList(){
	loadAbsencesList(sParamCache);
}

function loadAbsencesList(sIdParam) {

	if(!sIdParam){
		var sIdParam = '';
	} else {
		if(sIdParam.indexOf('&hash=') == -1) {
		sIdParam += '&hash='+sAbsenceHash;
		}
		sParamCache = sIdParam;
	}
	
	if($('loading_indicator')) {
		$('loading_indicator').show();
	}

	unselectCurrentCells();

	createMonthsHeader();

	var sParams = 'action=get_absences_list'+sIdParam;

	sParams += '&year='		+ $('filter_year').value;
	sParams += '&month='	+ $('filter_month').value;

	var oAjax = new Ajax.Request(
		sAbsenceAjaxUrl,
		{
			method		: 'post',
			parameters	: sParams,
			onComplete	: loadAbsencesListCallback
		}
	);
}

/* ==================================================================================================== */

function loadAbsencesListCallback(oResponse) {

	$j('#divHeader_'+sGuiHash).hide();

	var oData = oResponse.responseText.evalJSON();
	var aHead = oData['head'];
	var aData = oData['data'];

	aCategories = oData['categories'];

	// Clear cache
	aColspanCache = [];

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Clear table

	var aDays = $A($$('.monthDays'));

	aDays.each(function(oDay) {
		oDay.remove();
	});

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write the header

	iColumns = 0;

	for(var i = 0; i < 3; i++) {

		var sCode = '';

		$((i+1) + '_Month').colSpan = aHead[i]['days'].length;

		var aDays = aHead[i]['days'];

		$((i+1)+'_Month').style.width = (aDays.length * 17) + 'px';

		aDays.each(function(aDay) {

			var sColor = '#EEE';

			if(aDay['color'] != '') {
				sColor = aDay['color'];
			}
			
			sCode += '<th class="monthDays" style="background-color:' + sColor + ';">' + aDay['day'] + '</th>';

			iColumns++;

		});

		$('daysHeader').insert({bottom: sCode});
	}

	var oColgroup = $('tblScrollBody').down('colgroup');
	if(oColgroup) {
		oColgroup.remove();
	}

	while($('tblAbsences').hasChildNodes()) {
		$('tblAbsences').removeChild($('tblAbsences').firstChild);
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write the data

	var sCode = '';
	
	sCode += '<colgroup>';
	sCode += '<col id="teacher_column_body_col" style="width:328px;">';
	if(
		aData[0] &&
		aData[0]['months']
	) {
		var aMonths = aData[0]['months'];
		aMonths.each(function(aMonth) {
			var aDays = aMonth['days'];
			aDays.each(function(aDay) {
				sCode += '<col style="width: 17px;">';
			});
		});
	}
	sCode += '</colgroup>';

	$('tblAbsences').up().insert({top: sCode});

	var iRow = 0;
	aData.each(function(aItem) {

		iRow = aColspanCache.length;
		aColspanCache[iRow] = [];

		var sCode = '';

		sCode += '<tr>';
		sCode += '<th id="teacher_column_body">' + aItem['name'] + '</th>';

		var aMonths = aItem['months'];

		var iCol = 0;
		aMonths.each(function(aMonth)
		{

			var aDays = aMonth['days'];

			aDays.each(function(aDay)
			{

				var sClick = '';

				var sPointer = 'cursor:pointer;';

				var sColor = '';

				if(aDay['color'] != '')
				{
					sColor = aDay['color'];

					sClick = sPointer = '';
				}

				var sName = aDay['date'];
				sName += '_'+aItem.id;

				if(aDay['entry']) {
					aColspanCache[iRow][iCol] = 1;
				} else {
					aColspanCache[iRow][iCol] = 0;
				}

				if(iColspanCache > 1) {
					iColspanCache--;
				} else {

					if(aDay['entry']) {

						var iRemaining = iColumns - iCol;

						//Colspan
						var iColspan = 0;
						if(aDay['entry']['days'] > iRemaining){
							iColspan = iRemaining;
						}else{
							iColspan = aDay['entry']['days'];
						}

						// Write the cell
						sCode += '<td class="monthDays entry c' + aDay['entry']['category_id'] + '" id="'+sName+'" colspan="'+iColspan+'">';
						sCode += '<div class="ui-icon ui-icon-pencil" id="entry_' + aDay['entry']['id'] + '"></div>';
						if(aDay['entry']['days'] > 1) {
							sCode += '<div class="ui-icon ui-icon-trash" id="entry_' + aDay['entry']['id'] + '"></div>';
						}
						sCode += '</td>';
						iColspanCache = iColspan;
						aColspanCache[iRow][iCol] = 0;
					} else {
						sCode += '<td class="monthDays w16" style="background-color:' + sColor + ';" id="'+sName+'"></td>';
					}
					
				}

				iCol++;

			});

		});

		sCode += '</tr>';

		$('tblAbsences').insert({bottom: sCode});
	});

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	var aDays = $$('td.monthDays');

	oGui = aGUI[sGuiHash];
	if(oGui) {
		oGui.resize();
	}

	Event.stopObserving($('tblScrollBody'));

	Event.observe($('tblScrollBody'), 'mousedown', function(e) {
		oStartCell = Event.element(e);

		document.onselectstart = new Function("return false");
		document.body.style.MozUserSelect = "none";

	});

	Event.observe($('tblScrollBody'), 'mousemove', function(e) {
		
		if(
			oStartCell &&
			Event.isLeftClick(e)
		) {

			var oElement = Event.element(e);

			if(oElementCache != oElement) {
				var oPosition1 = getPosition(oStartCell);
				var oPosition2 = getPosition(oElement);
				highlightArea(oPosition1, oPosition2);
				oElementCache = oElement;
			}

		}

	});

	Event.observe($('tblScrollBody'), 'mouseup', function(e) {
		oStartCell = null;
		document.onselectstart = null;
		document.body.style.MozUserSelect = 'text';

		var oElement = Event.element(e);

		if(oElement.hasClassName('ui-icon')) {

			var iEntryId = oElement.id.replace(/entry_/, '');

			oGui.selectedRowId = new Array(iEntryId);

			if(oElement.hasClassName('ui-icon-trash')) {
				aElement = {};
				aElement.task = 'deleteRow';
				oGui.prepareAction(aElement);
			} else if(oElement.hasClassName('ui-icon-pencil')) {
				aElement = {};
				aElement.task = 'openDialog';
				aElement.action = 'edit';
				aElement.request_data = '';
				oGui.prepareAction(aElement);
			}

		} else {

			if(aCurrentCells.size() == 0) {
				aCurrentCells[aCurrentCells.length] = oElement;
			}

			openDialog();

		}

	});


	var oDiv = $('guiScrollBody');
	if(oDiv) {
		$j(oDiv).scroll(function(oEvent) {
			
			var iScrollLeft = $j(this).scrollLeft();
			
			$j('#guiTableHead_'+sGuiHash).css('position', 'relative');
			$j('#guiTableHead_'+sGuiHash).css('left', iScrollLeft*-1);
			
		});
	}



	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	if($('loading_indicator')) {
		$('loading_indicator').hide();
	}

}

function openDialog() {

	var sParam = '';

	aCurrentCells.each(function(oCell) {
		sParam += '&cells[]='+oCell.id;
	});

	aElement = {};
	aElement.task = 'openDialog';
	aElement.action = 'new';
	aElement.request_data = sParam;
	oGui.prepareAction(aElement);

}

function getPosition(oElement) {

	var aPreviousCols = oElement.previousSiblings();
	var oRow = oElement.up();
	var aPreviousRows = oRow.previousSiblings();

	var iCols = aPreviousCols.size();
	var iRows = aPreviousRows.size();

	var iDiff = 0;
	if(aColspanCache[iRows] && aColspanCache[iRows].length > 0) {
		aColspanCache[iRows].each(function(iValue, iKey){
			if(iValue) {
				iDiff++;
			}
			if(iKey >= (iCols + iDiff - 1)) {
				throw $break;
			}
		});
	}

	return {'cols': (iCols + iDiff), 'rows' : iRows};

}

function unselectCurrentCells() {

	aCurrentCells.each(function(oCell) {
		oCell.removeClassName('highlight');
	});
	aCurrentCells = [];

}

function highlightArea(oPosition1, oPosition2) {

	unselectCurrentCells();

	var iLeftCols, iLeftRows, iRightCols, iRightRows;
	iLeftCols = [oPosition1.cols, oPosition2.cols].min();
	iLeftRows = [oPosition1.rows, oPosition2.rows].min();
	iRightCols = [oPosition1.cols, oPosition2.cols].max();
	iRightRows = [oPosition1.rows, oPosition2.rows].max();

	if(iLeftCols == 0) {
		iLeftCols = 1;
	}
	if(iRightCols == 0) {
		iRightCols = 1;
	}

	var oCell;
	var oRow = $('tblAbsences').down();

	if(iLeftRows > 0) {
		oRow = oRow.next(iLeftRows-1);
	}

	var bValue = 0;
	for(var i=iLeftRows; i<=iRightRows; i++) {

		iTempLeftCols = iLeftCols;
		iTempRightCols = iRightCols;

		if(aColspanCache[i] && aColspanCache[i].length > 0) {
			for(var y=0; y<=iRightCols; y++) {
				bValue = aColspanCache[i][y];
				if(bValue) {
					if(y < iLeftCols) {
						iTempLeftCols--;
						iTempRightCols--;
					}
					if(y >= iLeftCols) {
						iTempRightCols--;
					}
				}
				if(y >= iRightCols) {
					break;
				}
			}
		}

		for(var j=iTempLeftCols; j<=iTempRightCols; j++) {
			oCell = oRow.down();
			if(oCell) {
			if(j > 0) {
				oCell = oCell.next(j-1);
			}
			if(!oCell.hasClassName('entry')) {
				oCell.addClassName('highlight');
				aCurrentCells[aCurrentCells.length] = oCell;
			}else{
				console.log(oCell);
			}
		}
		}
		oRow = oRow.next();
	}

}

/* ==================================================================================================== */

function createMonthsHeader() {

	if($('filter_month').value == 11) {
		$('1_Month').innerHTML = $('filter_month').options[10].innerHTML + ' ' + $('filter_year').value;

		$('2_Month').innerHTML = $('filter_month').options[11].innerHTML + ' ' + $('filter_year').value;

		$('3_Month').innerHTML = $('filter_month').options[0].innerHTML + ' ' + (parseInt($('filter_year').value) + 1);
	} else if($('filter_month').value == 12) {
		$('1_Month').innerHTML = $('filter_month').options[11].innerHTML + ' ' + $('filter_year').value;

		$('2_Month').innerHTML = $('filter_month').options[0].innerHTML + ' ' + (parseInt($('filter_year').value) + 1);

		$('3_Month').innerHTML = $('filter_month').options[1].innerHTML + ' ' + (parseInt($('filter_year').value) + 1);
	} else {
		$('1_Month').innerHTML = $('filter_month').options[$('filter_month').selectedIndex].innerHTML + ' ' + $('filter_year').value;

		$('2_Month').innerHTML = $('filter_month').options[$('filter_month').selectedIndex + 1].innerHTML + ' ' + $('filter_year').value;

		$('3_Month').innerHTML = $('filter_month').options[$('filter_month').selectedIndex + 2].innerHTML + ' ' + $('filter_year').value;
	}

}

function executeHook_absence_gui2_request_callback_hook(sHook, mInput, mData) {

	if(
		(
			mInput.action == 'saveDialogCallback' ||
			mInput.action == 'loadTable' ||
			mInput.action == 'closeDialogAndReloadTable'
		) &&
		mInput.absence == 1
	) {
		var sParam = '';
		if(mInput.item == 'accommodation'){
			sParam += '&parent_gui_id[]='+mInput.absence_parent+'&item=accommodation';
		}
		loadAbsencesList(sParam);
	}

	return mInput;

}

oWdHooks.addHook('gui2_request_callback_hook', 'absence');