var bFirstInit = 1;
	var iPlanificationHeight = 0;
	var iStudentsHeight = 0;
	var iCountOtherRooms = 0;
	var iPlanificationBodyHeight = 0;
	var arrIcons = [
		'toolbar_copy',
		'toolbar_replace_data',
		'toolbar_new',
		'toolbar_edit',
		'toolbar_delete',
		'toolbar_student_delete',
		'toolbar_student_communication',
		'toolbar_teacher_replace',
		'toolbar_export',
		'toolbar_exportWeek'];
	var arrIconState = [];
	var selectedRow = false;
	var bCheckToolbarInProgress = 0;
	var objLitBox;
	var selectedRow = 0;
	var selectedStudent = 0;
	var aInquiryCache = {}; // KEIN ARRAY, DA DAS TAUSENDE NULL-WERTE ERZEUGT
	var aStudentSort = [];
	var iCurrentWeek;
	var bTablePlanificationInitialized = 0;
	var bCalled = 0;
	
	var iAddedBlocks = 1;
	var oLang = [];

	var loadTableListObserver;
	var iColumnWidth = 120;
	var oSearchDebounce = null;
	var callbackData = null;

	function prepareFilterSearch() {

		if(loadTableListObserver){
			clearTimeout(loadTableListObserver);
		}

		loadTableListObserver = setTimeout("executeFilterSearch()", 500);

	}

	function executeFilterSearch() {

		loadStudents('unallocated');

	}

	function changeWeek(sAction, bNoReload) {

		var iIndex = $('week').selectedIndex;
		var bCheckCopy = true;
			
		if(sAction == 'last') {
		
			if(iIndex < 2) {
				iIndex = $('week').length - 1; 
			} else {
				iIndex--;
			}
			
		} else if(sAction == 'next') {
		
			if(iIndex > ($('week').length-1)) {
				iIndex = 0; 
			} else {
				iIndex++;
			}
			
		} else {

			iIndex = getCurrentWeekIndex();

		}

		$('week').selectedIndex = iIndex;

		if(!bNoReload) {
			if(bCheckCopy) {
				preparePlanification(1);
			} else {
				preparePlanification(0);
			}
				
		}
		
	}

	function getCurrentWeekIndex(){

		var iIndex = 0;

			for (i = 0; i < $('week').length; ++i) {
    		if ($('week').options[i].value == iDefaultWeek) {
      				iIndex = i;
      				break;
      			}
      		}

		return iIndex;
		}

	function deleteCourseToBlockDialog(iBlockId, oIcon) {
	
		var oDiv = oIcon.up();
		oDiv.remove();

	}

	function addCourseToBlockDialog(iBlockId) {

		var oDiv = $('block_courses_'+iBlockId);

		var oSelect = oDiv.previous(2);
		var oNewSelect = oSelect.cloneNode(true);
		
		var sAddButton = '<img src="/admin/extensions/thebing/images/bullet_delete.png" alt="" onclick="deleteCourseToBlockDialog('+parseInt(iBlockId)+', this);" />';
		
		var oContainer = document.createElement('div');
		
		oContainer.insert({bottom : oNewSelect});
		oContainer.insert({bottom : sAddButton});
		
		oDiv.insert({bottom : oContainer});

	}

	function selectMulti(sId) {
		var oSelect = $(sId);
		var aBoxes = $A(oSelect.options);

		aBoxes.each(function(oBox) {
			if(oBox.selected) {
				oBox.selected = false;
			} else {
				oBox.selected = true;
			}
		});
	}

	function openTeacherDialog(iBlockId) {

		var strRequestUrl = '/ts-tuition/scheduling/json/open-teacher-dialog';

		var iWeek = $('week').value;

		var strParameters = 'week='+encodeURIComponent(iWeek);
		strParameters += '&block_id='+encodeURIComponent(iBlockId);

		var objAjax = new Ajax.Request(
			strRequestUrl,
			{
				method : 'post',
				parameters : strParameters,
				onComplete : openTeacherDialogCallback
			}
		); 

	}	

	function openTeacherDialogCallback(oResponse)	{

		var oData = oResponse.responseText.evalJSON();

		var oGUI = new GUI;
	
		var sHTML = '';

		sHTML += '<div class="GUIDialogTabContentDiv GUIDialogContentPadding" style="height:300px;" id="substitute-teacher-dialog-content"><form method="post" id="formTeacherDialog" onsubmit="return false;" class="form-horizontal">';
		sHTML += '<input type="hidden" name="block_id" id="block_id" value="'+oData.block.id+'" />';

		oData.days.each(function(aDay) { 
			sHTML += '<div style="clear: both; margin-bottom: 10px;">';
			sHTML += '<h4>'+aDay[1]+'</h4>';

			sHTML += '<div id="day_'+aDay[0]+'">';
			
			if(oData.substitute_teachers[aDay[0]]) {
				$A(oData.substitute_teachers[aDay[0]]).each(function(aItem) {
					sHTML += '<div class="py-2">';
					sHTML += oGUI.printFormSelect(oData.lang.teacher, 'substitute['+aDay[0]+'][teacher][]', oData.teachers[aDay[0]], aItem.teacher_id);
					sHTML += oGUI.printFormSelect(oData.lang.from, 'substitute['+aDay[0]+'][from][]', oData.times, aItem.from);
					sHTML += oGUI.printFormSelect(oData.lang.to, 'substitute['+aDay[0]+'][to][]', oData.times, aItem.to);
					sHTML += '<div class="GUIDialogRow form-group form-group-sm">' +
						'<label class="GUIDialogRowLabelDiv col-sm-4 control-label">'+oData.lang.lessons+'</label>' +
						'<div class="GUIDialogRowInputDiv col-sm-8">' +
							'<input class="txt form-control" name="substitute['+aDay[0]+'][lessons][]" id="substitute['+aDay[0]+'][lessons][]" value="'+aItem.lessons+'"/>' +
						'</div>' +
					'</div><br/>';
					sHTML += '<a href="javascript:;" onclick="deleteSubstituteSet(this);" class="btn btn-gray">'+oData.lang['delete']+'</a>';
					sHTML += '</div>';
				});
			}

			sHTML += '</div>';

			sHTML += '<a href="javascript:;" onclick="addSubstituteSet('+aDay[0]+');" class="btn btn-default">'+oData.lang.add+'</a>';
			sHTML += '</div>';
		});

		var iEnd = oData.times.slice(-1)[0];

		sHTML += '<div id="day_template" class="p-2" style="display: none;">';
		sHTML += '<div class="py-2">';
		sHTML += '{TEACHER_SELECT}';
		sHTML += oGUI.printFormSelect(oData.lang.from, 'substitute[{DAY}][from][]', oData.times, 0);
		sHTML += oGUI.printFormSelect(oData.lang.to, 'substitute[{DAY}][to][]', oData.times, iEnd[0]);

		sHTML += oGUI.printFormInput(oData.lang.lessons, 'substitute[{DAY}][lessons][]', oData.block.lessons);
		sHTML += '<a href="javascript:;" onclick="deleteSubstituteSet(this);" class="btn btn-gray">'+oData.lang['delete']+'</a>';
		sHTML += '</div>';
		sHTML += '</div>';

		sHTML += '<button onclick="saveTeacherDialog();" class="btn btn-primary">'+oData.lang.save+'</button>';

		sHTML += '</form></div>';

		objLitBox = new Dialog({
			content: sHTML,
			openOnCreate: true,
			destroyOnClose: true,
			height: 380,
			width: 700,
			sTitle: oData.lang.title,
			buttons: false,
			closeIconEvent: true
		});

		callbackData = oData;

	}

	function deleteSubstituteSet(oLink) {
		
		var oDiv = oLink.up('div');
		oDiv.remove();

	}

	function addSubstituteSet(day) {

		var oGUI = new GUI;
		var HTML = $('day_template').innerHTML;
		HTML = HTML.replace('{TEACHER_SELECT}', oGUI.printFormSelect(callbackData.lang.teacher, 'substitute[{DAY}][teacher][]', callbackData.teachers[day], callbackData.block.teacher_id));
		HTML = HTML.replace(/{DAY}/g, day);
		HTML = '<div>'+HTML+'</div>';

		$('day_'+day).insert({bottom: HTML});

	}
	
	function saveTeacherDialog() {
		var sParameter = $('formTeacherDialog').serialize();
		sParameter += '';
		
		var strRequestUrl = '/ts-tuition/scheduling/json/save-teacher-dialog';
	
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : sParameter,
									onComplete : saveTeacherDialogCallback
								}
		); 
		
	}
	
	function saveTeacherDialogCallback(oResponse) {
		
		var oData = oResponse.responseText.evalJSON();
		
		$('block_id').value = oData.block_id;

		if(oData.success) {
			$('substitute-teacher-dialog-content').update('<span style="color: green;">'+oData.message+'</span>');
			window.setTimeout("objLitBox.close();", 2000);
		} else {
			$('substitute-teacher-dialog-content').update('<span style="color: red;">'+oData.message+'</span>');
		}

		preparePlanification();
	}

	function clearStudents(iBlockId, iRoomId, bCanFireRequest, iAllWeeks) {

		var bConfirm;

		if(!bCanFireRequest){
			bConfirm = confirm(getTranslation('confirm_clear_students'));
		}else{
			bConfirm = true;
		}

		if(typeof iRoomId == 'undefined') {
			console.error('Missing room id!');
		}

		if(bConfirm) {

			if(!bCanFireRequest){
				oGUI		= getStudentsGui('allocated');
				showQuestionWeeks(oGUI,'clear_students',iBlockId, iRoomId);
			}else{

				var iWeek = $('week').value;

				var strRequestUrl = '/ts-tuition/scheduling/json/clear-students';
				var strParameters = 'block_id='+encodeURIComponent(iBlockId)+'&room_id='+encodeURIComponent(iRoomId)+'&week='+iWeek+'&all_weeks='+iAllWeeks;

				var oAjax = new Ajax.Request(
					strRequestUrl,
					{
						method : 'post',
						parameters : strParameters,
						onComplete : clearStudentsCallback
					}
				);
			}

		}

	}

	function clearStudentsCallback(oResponse) {

		var oData = oResponse.responseText.evalJSON();

		if(oData.success == 1){
			loadStudents('allocated');
			loadStudents('unallocated');

			oData.block_ids.each(function(aBlock) {
				updateBlock(aBlock['block_id'], aBlock['room_id']);
			});
		} else {
			if (oData.error_message) {
				let html = '<div style="padding: 20px;">' + oData.error_message + '</div>';
				objLitBox = new Dialog({
					content: html,
					openOnCreate: true,
					destroyOnClose: true,
					height: 400,
					width: 700,
					sTitle: oData.title,
					buttons: false,
					closeIconEvent: true
				});
			}
		}

	}

	function prepareDailyComments(iItemId) {

		var oGui = aGUI[sGuiHash];
		oGui.instance_hash = sGuiInstanceHash;
		
		var iWeek = $('week').value;

		var strParameters = '&filter[week_filter]='+iWeek;

		var sAction;
		sAction = 'daily_comments';
		strParameters += '&block_id[]='+iItemId;

		aElement = {};
		aElement.task = 'openDialog';
		aElement.action = sAction;
		aElement.request_data = strParameters;
		oGui.prepareAction(aElement);

	}

	function prepareEditMultipleBlocks(iItemId) {

		var oGui = aGUI[sGuiHash];
		oGui.instance_hash = sGuiInstanceHash;
		
		var iWeek = $('week').value;

		var strParameters = '&filter[week_filter]='+iWeek;

		var sAction;
		if(undefined==iItemId){
			sAction = 'new';
		}else{
			sAction = 'edit';
			strParameters += '&block_id[]='+iItemId;
		}

		aElement = {};
		aElement.task = 'openDialog';
		aElement.action = sAction;
		aElement.request_data = strParameters;
		oGui.prepareAction(aElement);

	}

	var aCoursesInfo      = [];
	var aCourseCategories = [];

	function unselectBlockCourses ( oSelectElement ) {
	
		$A( oSelectElement.options ).each(function (oOption) {
			oOption.selected = false; 
		});
		
	}
		
	function updateFlexboxHeight(mOptions) {
		var iErrorHeight = $('divLitboxError').getHeight();
		var iContentHeight = $('LB_content').getHeight();
		$('divBlockSets').style.height = (iContentHeight - 25 - iErrorHeight)+'px';
	}
		
	function checkRow(e, strId) {
		var objRow = $(strId);
		
		if(
			selectedRow && 
			$(selectedRow)
		) {
			$(selectedRow).className = "";
		}
		
		if(objRow.className == "") {
			objRow.className = "selectedRow";
		} else {
			objRow.className = "";
		}
		selectedRow = strId;
	
		checkToolbar();
	
	}
	
	function executeAction(strId, strAction) {
	
		if(bCheckToolbarInProgress) {
			window.setTimeout("executeAction('"+strId+"', '"+strAction+"')", 100);
			return false;
		}

		if(
			!strId &&
			!arrIconState['toolbar_'+strAction]
		) {
			alert(getTranslation('action_not_allowed'));
			return false;
		}

		var iBlockId;
		var iRoomId = 0;

		if(strId > 0) {
			iBlockId = strId;
		} else if(selectedRow) {
			var aBlockId = selectedRow.replace(/room_content_/, '').split('_');
			iBlockId = aBlockId[0];
			iRoomId = aBlockId[1];
		}

		switch(strAction) {
			case "delete":
				if(confirm(getTranslation('confirm_delete_block'))) {
					oGUI = getStudentsGui('allocated');
					showQuestionWeeks(oGUI,'delete_block',iBlockId, iRoomId);
				}
				break;
			case "student_delete":
				var aSelectedIds = [];
				var sSelectedId;
				$A($$('#divStudentsAllocated .multiple_checkbox')).each(function(oCheckBox){
					if(oCheckBox.checked){
						sSelectedId = oCheckBox.id;
						sSelectedId = sSelectedId.replace(/checkbox_inquiry_allocated_/, '');
						aSelectedIds.push(sSelectedId);
					}
				});
				if(confirm(getTranslation('confirm_delete_student'))) {
					oGUI = getStudentsGui('allocated');
					oGUI.aSelectedInquiryIds	= aSelectedIds;
					showQuestionWeeks(oGUI,'delete', iBlockId, iRoomId);
				}
				break;
			case "student_communication":

				var aSelectedIds = [];
				$A($$('#divStudentsAllocated .multiple_checkbox')).each(function(oCheckBox){
					if(oCheckBox.checked){
						var sSelectedId = oCheckBox.id.replace(/checkbox_inquiry_allocated_/, '');
						aSelectedIds.push(sSelectedId);
					}
				});

				var oGUI = getStudentsGui('allocated');
				aElement = {};
				aElement.task			= 'request';
				aElement.action			= 'communication'; // muss "communication" sein
				aElement.additional		= 'tuition_allocation';
				aElement.request_data	= '';
				aElement.id	= aSelectedIds;
				oGUI.prepareAction(aElement);

				break;
			case "change_room_teacher":

				break;
			case "allocate_students":

				

				break;
			case "clear_students":
				clearStudents(iBlockId, iRoomId);
				break;
			case "teacher_replace":
				openTeacherDialog(iBlockId);
				break;
			case "edit":
				prepareEditMultipleBlocks(iBlockId);
				break;
			case "new":
				prepareEditMultipleBlocks();
				break;
			case "copy":
				copyWeek(false);
				break;
			case "export":
				//export_csv
				
				var sExportType;
				
				if(strId == 'unallocated'){
					sExportType = 'unallocated';
				}else{
					sExportType = 'allocated';
				}
				
				var oGUI = getStudentsGui(sExportType);
				aElement = {};
				aElement.task			= 'export_csv';
				aElement.action			= '';
				aElement.additional		= '';
				aElement.request_data	= '';
				oGUI.prepareAction(aElement);
				break;
			case 'exportWeek':
				var iWeek = $F('week');
				go('/ts-tuition/scheduling/json/export-week?week='+encodeURIComponent(iWeek)+'&day='+encodeURIComponent(iCurrentWeekday));
				setTimeout(function() {
					// setTimeout benötigt für Chrome
					$j('.page-loader').hide();
				}, 10);
				break;
			case "replace_data":
				copyWeek(true);
				break;
			case 'daily_comments':
				prepareDailyComments(iBlockId);
				break;
			default:
				alert(getTranslation('action_not_allowed'));
				break;
		}
	
	}
	
	function deleteBlock(iItemId, iAllWeeks) {
	
		var strRequestUrl = '/ts-tuition/scheduling/json/delete-block';
		var strParameters = 'block_id='+encodeURIComponent(iItemId)+'&all_weeks='+iAllWeeks;
		
		var objAjax = new Ajax.Request(
								strRequestUrl,
								{
									method : 'post',
									parameters : strParameters,
									onComplete : deleteBlockCallback
								}
		);

	}
	
	function deleteBlockCallback(oResponse) {

		var oData = oResponse.responseText.evalJSON();

		if(oData.success == 1) {

			preparePlanification();
			loadStudents('allocated');
			loadStudents('unallocated');
			if(oData.alert_message){

				oGUI = getStudentsGui('unallocated');
				oGUI.displaySuccess(null, oData.alert_message);

			}

		} else {

			oGUI = getStudentsGui('unallocated');
			oGUI.displayErrors([oGUI.getTranslation('general_error'), oData.error_message]);

		}
	}

	function deleteStudent(iBlockId, aSelectedIds, iAllWeeks,iRoomId) {

		var strRequestUrl = '/ts-tuition/scheduling/json/delete-student';
		var strParameters =
			'block_id='+encodeURIComponent(iBlockId)+
			'&room_id='+encodeURIComponent(iRoomId);

		for(iCounter=0;iCounter<aSelectedIds.length;iCounter++){
			strParameters += '&inquiry_ids[]='+aSelectedIds[iCounter];
		}
		strParameters += '&all_weeks='+iAllWeeks;
		
		var objAjax = new Ajax.Request(
			strRequestUrl,
			{
				method : 'post',
				parameters : strParameters,
				onComplete : deleteStudentCallback,
				onLoading: showPlanificationLoading
			}
		);

	}

	function deleteStudentCallback(oResponse) {

		hidePlanificationLoading();

		var oData = oResponse.responseText.evalJSON();
		
		loadStudents('allocated');
		loadStudents('unallocated');

		if(
			oData.success == 0 && 
			oData.message
		) {
			let html = '<div style="padding: 20px;">'+oData.message+'</div>';
			objLitBox = new Dialog({
				content: html,
				openOnCreate: true,
				destroyOnClose: true,
				height: 400,
				width: 700,
				sTitle: oData.title,
				buttons: false,
				closeIconEvent: true
			});

		} else {
			oData.block_ids.each(function(aBlock) {
				updateBlock(aBlock['block_id'], aBlock['room_id']);
			});
		}
		
	}

	function prepareDeleteStudent() {

	}

	function checkToolbar() {
	
		if(!bCheckToolbarInProgress) {

			bCheckToolbarInProgress = 1;
	
			var strParameters = '?';
	
			if(selectedRow) {
	
				var intItemId = selectedRow.replace(/room_content_/, '');
				strParameters += '&block_id='+encodeURIComponent(intItemId);
	
			}
			if(selectedStudent) {
	
				var intItemId = selectedStudent.replace(/inquiry_allocated_/, '');
				strParameters += '&inquiry_id='+encodeURIComponent(intItemId);
	
			}
			
			var strRequestUrl = '/ts-tuition/scheduling/json/check-toolbar';
			
			var objAjax = new Ajax.Request(
									strRequestUrl,
									{
										method : 'post',
										parameters : strParameters,
										onComplete : checkToolbarCallback
									}
			); 
	
		}
	
	}
	
	function checkToolbarCallback(objResponse) {
	
		var objData = objResponse.responseText.evalJSON();
	
		var arrList = objData['data'];
		
		updateToolbar(arrList);
	}

	function updateToolbar(arrList) {

		arrIcons.each(function(strIcon) {
			var bolShow = arrList[strIcon];
			switchToolbarIcon(strIcon, bolShow);
		});

		bCheckToolbarInProgress = 0;

	}

	function switchToolbarIcon(strIcon, bolShow) {
		
		var objIcon = $(strIcon);
		if(objIcon){
			if(bolShow) {
				if(
					arrIconState[strIcon] == undefined ||
					arrIconState[strIcon] == 0
				) {
					objIcon.style.opacity = 1;
				}
				arrIconState[strIcon] = 1;
			} else {
				if(
					arrIconState[strIcon] == undefined ||
					arrIconState[strIcon] == 1
				) {
					objIcon.style.opacity = 0.2;
				}
				arrIconState[strIcon] = 0;
			}
		}
	}

	function preparePlanification(bCheckEmptyWeek, callback) {
	
		if(!bCheckEmptyWeek) {
			bCheckEmptyWeek = 0;
		}

		var iWeek = $('week').value;
		
		if(iWeek) {

			$('week_loading').show();

			window.__FIDELO__.scheduling.setWeekAndWeekDay(iWeek, iCurrentWeekday);
			var filterValues = window.__FIDELO__.scheduling.filterValues;

			var strRequestUrl = '/ts-tuition/scheduling/json/load-week';

			var strParameters = 'week='+encodeURIComponent(iWeek)+'&day='+encodeURIComponent(iCurrentWeekday)+'&check_empty_week='+encodeURIComponent(bCheckEmptyWeek)+'&search='+$('search').value+'&table_initialized='+bTablePlanificationInitialized;
			var strFilters = $j.param(filterValues);

			if (strFilters.length > 0) {
				strParameters += '&'+strFilters;
			}

			var objAjax = new Ajax.Request(
				strRequestUrl,
				{
					method : 'post',
					parameters : strParameters,
					onComplete : (response) => {
						preparePlanificationCallback(response)

						if (callback) {
							callback(response)
						}
					}
				}
			);

			if(iCurrentWeek != iWeek && bCalled==1) {
				loadStudents('unallocated');
				var oDivBodyAllocated = $('guiScrollBody_'+sStudentsAllocatedHash);
				if(oDivBodyAllocated){
					oDivBodyAllocated.update('');
				}
			}

			iCurrentWeek = iWeek;
			bCalled = 1;

		}
		
	}
	
	function preparePlanificationCallback(oResponse) {
		
		var oTransfer = oResponse.responseText.evalJSON();
		
		if(oTransfer.block_count == 0 && oTransfer.message && oTransfer.can_copy_last_week == 1) {
			if(confirm(oTransfer.message)) {
				copyClasses();
				return false;
			}
		}

		if(oTransfer.column_width) {
			iColumnWidth = oTransfer.column_width;
		}

		if(oTransfer['html']) {

			var oContainer = $('divPlanification');
			var sHTML = oTransfer['html'];
			oContainer.update(sHTML);
			oTable = $('tablePlanification');
			TuitionScrollableTable.init(oTable);

		} else {

			var aContainer = $$('.room_container');

			aContainer.each(function(oContainer) {
				oContainer.update('');
			});

			if(oTransfer.container) {
				Object.keys(oTransfer.container).each(function(iClassTime){
					Object.keys(oTransfer.container[iClassTime]).each(function(iRoom){
						var oContainer = $('room_container_'+iClassTime+'_'+iRoom+'');
						if(oContainer) {
							oContainer.update(oTransfer.container[iClassTime][iRoom]);
						}
					});
				});
			}
		
		}

		if(oTransfer['html_other_rooms']) {
			var oContainer = $('divPlanificationOtherRooms');
			var sHTMLOther = oTransfer['html_other_rooms'];
			oContainer.update(sHTMLOther);
			var oTableOther = $('tablePlanificationother');
			TuitionScrollableTable.init(oTableOther);
		}

		var aContents = $A($$('.room_container .room_content'));
		aContents.reverse();

		aContents.each(function(oItem) {

			bindSelectBlockEvent(oItem);

			oItem.observe('mouseover', toggleBlock.bindAsEventListener(this, oItem, 1));
			oItem.observe('mouseout', toggleBlock.bindAsEventListener(this, oItem, 0));

			$j(oItem).draggable({ 
				axis: 'x',
				revert: true,
				appendTo: 'tablePlanification-body',
				scroll: true,
				containment:  'tablePlanification-body'
			});

			$j(oItem).droppable({
				accept: '.student',
				hoverClass: 'hover',
				drop: function(e, ui) {
					// Wenn sich Droppables überlappen (linker und rechter Bereich), lösen alle aus, trotz Viewport #14778
					if($j(oItem).visible(true)) { // Plugin: jquery.visible.js
						prepareMoveStudent(ui.draggable[0], oItem);
					}
				}
			});

		});

		$A($$('.room_container')).each(function(oItem) {

			$j(oItem).droppable({
				accept: '.room_content',
				hoverClass: 'hover',
				drop: function(oElement, ui) {

					var oContent = ui.draggable[0];
					var oOld = ui.draggable[0];

					if(oOld) {
						while(!oOld.hasClassName('room_container')) {
							oOld = oOld.up();
						}

						if(oItem.id != oOld.id) {
							moveBlock(oContent, oItem);
						}

					}
					
				}
			});

		});

		updateToolbar(oTransfer.data);

		$('week_loading').hide();

		resetDaySelectorsStyles();
		setDaySelectorsStyles ( oTransfer.aHolidays );

		bTablePlanificationInitialized = 1;

		// Anpassungen der Größe
		iCountOtherRooms = oTransfer['count_other_rooms'];
		checkListHeight();

	}

	function bindSelectBlockEvent(oItem){

		var oContent = oItem.down('div.room_content_padding');

		//oContent.style.height = (oItem.getHeight() - 25)+'px';
		oItem.stopObserving('click');
		oItem.observe('click', selectBlock.bindAsEventListener(this, oItem));

		var bIsCutOff, itemHeight, itemZIndex, contentHeight;
		$j(oItem)
			.on('mouseenter', () => {
				bIsCutOff = $j(oContent).outerHeight() > (oItem.getHeight() - 25)
				if (bIsCutOff) {
					// Originalwerte merken
					itemHeight = $j(oItem).outerHeight();
					itemZIndex = 100;
					contentHeight = $j(oItem).outerHeight();

					$j(oItem).css({height: 'auto', zIndex: 10})
					$j(oContent).css('height', 'auto')
				}
			})
			.on('mouseleave', () => {
				if (bIsCutOff) {
					// Originalwerte wieder setzen
					$j(oItem).css({height: itemHeight + 'px', zIndex: itemZIndex})
					$j(oContent).css('height', contentHeight + 'px')
				}
			});


		return oContent;
	}

	function resetDaySelectorsStyles () {
		$$('#divWeekDaySwitch .divButton').each(function (elem) {
			$(elem).removeClassName('schoolHoliday');
			$(elem).removeClassName('publicHoliday');
		});
	}
	
	function setDaySelectorsStyles ( aHolidays ) {

		if (
			!aHolidays ||
			$A(aHolidays).length < 1
		) {
			return;
		}

		$A([0,1,2,3,4,5,6]).each(function (iDay) {
			var sButton = 'weekday_'+(iDay+1);
			if (aHolidays[iDay] != '') { 
				if ($(sButton)) {
					$(sButton).addClassName(aHolidays[iDay]);
				}
			}
		});

	}
	
	var iCurrentWeekday;
	function changeWeekDay(iDay,bCheckEmptyWeek, callback) {

		var iWeekDayBefore = iCurrentWeekday;

		if(iCurrentWeekday) {
			$('weekday_'+iCurrentWeekday).removeClassName('primary-color-element');
		}
		
		var oButton = $('weekday_'+iDay);
		oButton.addClassName('primary-color-element');
		iCurrentWeekday = iDay;

		if(!bCheckEmptyWeek){
			bCheckEmptyWeek = false;
		}

		var containers = ['divPlanification', 'divPlanificationOtherRooms']

		containers.forEach((container) => $(container).classList.add('animation-hide'))

		preparePlanification(bCheckEmptyWeek, () => {
			setTimeout(() => {
				containers.forEach((container) => {
					$(container).classList.remove('animation-hide')
					$(container).classList.add('animation-show')
				})
			}, 300)

			callback()
		});

		if(iWeekDayBefore > 0) {
			loadStudents('unallocated');
			
			if(selectedRow) {			
				loadStudents('allocated', true);
			}
				
		}

	}

	function copyWeek(bReplace) {

		var oGui = aGUI[sGuiHash];
		oGui.instance_hash = sGuiInstanceHash;
		var iWeek = $('week').value;
		var strParameters = '&filter[week_filter]='+iWeek;
		var aElement = {};
		aElement.task = 'openDialog';
		aElement.action = 'copy';

		if(bReplace){
			aElement.additional = 'replace_data';
		}

		aElement.request_data = strParameters;
		oGui.prepareAction(aElement);
	}

	function copyClasses(){

		var iWeek = $('week').value;

		if(iWeek) {

			var strRequestUrl = '/ts-tuition/scheduling/json/load-week';
			var strParameters = 'copy_last_week=1&week='+encodeURIComponent(iWeek)+'&day='+encodeURIComponent(iCurrentWeekday);

			var objAjax = new Ajax.Request(
									strRequestUrl,
									{
										method : 'post',
										parameters : strParameters,
										onComplete : copyWeekCallback
									}
			);

			selectedRow = false;
			selectedStudent = false;

		}
	}

	function copyWeekCallback(oResponse) {
		preparePlanificationCallback(oResponse);

		var oTransfer = oResponse.responseText.evalJSON();

		var oGui = aGUI[sGuiHash];
		oGui.instance_hash = sGuiInstanceHash;

		if(oTransfer.errors) {
			var sAlert = oGui.getTranslation('tuition_copy_week_error');
			sAlert += '\n\n' + oTransfer.errors;
		} else {				
			var sAlert = oGui.getTranslation('tuition_copy_week_success');
		}
		
		alert(sAlert);
	}

	function prepareMoveStudent(oContent, oContainer, bCanFireRequest,iAllWeeks) {

		var strRequestUrl = '/ts-tuition/scheduling/json/prepare-move-student';
		var sContainerId  = oContainer.id;
		var strParameters = 'container_id='+encodeURIComponent(sContainerId);
		strParameters += '&day=' + iCurrentWeekday;

		var iStudents = 0;
		var bAllocatedCheck = -1;

		if(
			oContent &&
			oContent.hasClassName('student')
		) {

			bAllocatedCheck = oContent.id.search(/inquiry_allocated_/);

			if(bAllocatedCheck != -1 && 1!=bCanFireRequest) {
				alert(getTranslation('move_allocated_student_alert'));
			}

			var sContentId = oContent.id;

			strParameters += '&content_id='+encodeURIComponent(sContentId);

			iStudents++;

		}

		$j('#divStudentsUnallocated INPUT.multiple_checkbox:checked, #divStudentsAllocated INPUT.multiple_checkbox:checked').each(function (i, oItem) {
			strParameters += '&additional_id[]='+oItem.id;
			iStudents++;
		});

		if(iStudents == 0) {
			alert(getTranslation('error_no_students'));
			return;
		}

		if(1!=bCanFireRequest) {
			if(bAllocatedCheck==-1) {
				oGUI				= getStudentsGui('unallocated');
			}else{
				oGUI				= getStudentsGui('allocated');
			}

			var aContainerInfos		= sContainerId.split('_');

			oGUI.content			= oContent;
			oGUI.container			= oContainer;

			showQuestionWeeks(oGUI,'move',aContainerInfos[2],aContainerInfos[3]);
			
		} else {
			if(iAllWeeks==1){
				strParameters += '&all_weeks=1';
			}
			var objAjax = new Ajax.Request(
				strRequestUrl,
				{
					method : 'post',
					parameters : strParameters,
					onComplete : prepareMoveStudentCallback,
					onLoading: showPlanificationLoading
				}
			);
		}
	}

	// @TODO Neu schreiben, da das hier nicht mehr wartbar ist
	function prepareMoveStudentCallback(oResponse) {
		
		var oGUI	= new GUI;
		var oData = oResponse.responseText.evalJSON();
		var sType	= oData.type;
		var iAllWeeks = oData.all_weeks;
		
		if(oData.success == true) {

			moveStudent(oData.inquiries, oData.block_id, oData.room_id, oData.type,iAllWeeks);

		} else {

			var oButtons = {};

			hidePlanificationLoading();

			var sHTML = '<div class="GUIDialogTabContentDiv GUIDialogContentPadding" style="height:300px;" id="substitute-teacher-dialog-content">';

			if(!oData.has_double_allocations) {
				sHTML += '<form method="post" id="formMoveDialog" onsubmit="return false;">';
				sHTML += '<input type="hidden" name="block_id" id="block_id" value="'+oData.block_id+'" />';
				sHTML += '<input type="hidden" name="room_id" id="room_id" value="'+oData.room_id+'" />';
			}
			
			var bShowSave = false;

			if(oData.has_double_allocations) {

				sHTML += '<div class="alert alert-warning">'+oData.lang.tuition_move_student_failure_exists+'</div>';
				
				var sClass = '';
				
				$A(oData.blocked_inquiry_courses).each(function(aInquiryCourse) {
				
					if(
						aInquiryCourse &&
						aInquiryCourse.class !== sClass
					) {
						sHTML += '<br/><span class="sc_font">'+aInquiryCourse.class+'</span><br/><br/>';
					}
				
					aInquiry		= findInquiryCacheCustomer(aInquiryCourse);
					sHTML			+= aInquiry['name']+' ('+aInquiryCourse['week']+')';
				
				});
				
			}else if(
				oData.has_errors_allocated
			){

				sHTML += '<div class="alert alert-warning">'+oData.lang.tuition_move_student_failure_allocated+'</div>';

				var sCustomerName;
				var aInquiry;
				var iInquiryId;

				$A(oData.inquiries).each(function(oInquiryCourse, iKey) {

					if(
						oData.errors_allocated[oInquiryCourse.inquiry_course_id] &&
						oData.errors_allocated[oInquiryCourse.inquiry_course_id][oInquiryCourse.program_service_id]
					) {

						aInquiry		= findInquiryCacheCustomer(oInquiryCourse);
						sCustomerName	= aInquiry['name']+' ('+aInquiry['course_type']+')';

						oData.errors_allocated[oInquiryCourse.inquiry_course_id][oInquiryCourse.program_service_id].each(function(sError){
							sError = sError.replace('%s', sCustomerName);
							sHTML += sError+'<br/>';
						});

					}

				});

			}

			var sTemp = '';
			var oInquiryCourseErrors = {};

			$A(oData.inquiries).each(function(oInquiryCourse, iKey) {
				if(
					oInquiryCourse && (
						oInquiryCourse.check == 'no_course' ||
						oInquiryCourse.check == 'wrong_course_language' ||
						oInquiryCourse.check == 'wrong_course' ||
						oInquiryCourse.check == 'no_online_room' ||
						oInquiryCourse.check == 'wrong_room_multiple_allocation' ||
						oInquiryCourse.check == 'allocated_activity' ||
						oInquiryCourse.check == 'not_enough_lessons'
					)
				) {
					oData.inquiries[iKey] = null;
					var aInquiry = findInquiryCacheCustomer(oInquiryCourse);
					sTemp += aInquiry['name']+' ('+aInquiry['course_type']+')<br/>';
					oInquiryCourseErrors[oInquiryCourse.check] = true;
				}
				
			});

			if(sTemp != '') {
				// Keine Ahnung, wie man das hier noch trennen soll, da das vorher schon alles auf bloßem Vorhandensein von Werten basierte
				if('no_course' in oInquiryCourseErrors) {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_no_course+'</strong><br/>';
				}
				if('wrong_course_language' in oInquiryCourseErrors) {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_wrong_course_language+'</strong><br/>';
				}
				if('wrong_course' in oInquiryCourseErrors) {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_wrong_course+'</strong><br/>';
				}
				if('no_online_room' in oInquiryCourseErrors) {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_no_online_room+'</strong><br/>';
				}
				if('wrong_room_multiple_allocation' in oInquiryCourseErrors) {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_wrong_room_multiple_allocation+'</strong><br/>';
				}
				if('allocated_activity' in oInquiryCourseErrors) {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_allocated_activity+'</strong><br/>';
				}
				if('not_enough_lessons' in oInquiryCourseErrors) {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_not_enough_lessons+'</strong><br/>';
				}

				sHTML += sTemp;
			}

			$A(oData.inquiries).each(function(oInquiryCourse, iKey) {
				if(oInquiryCourse) {
					sHTML += '<input type="hidden" name="inquiries" id="inquiries" value="'+oInquiryCourse.inquiry_course_id+'_'+oInquiryCourse.program_service_id+'" />';
				}
			});

			sTemp = '';
			var sTmp2 = '';
			$A(oData.inquiries).each(function(oInquiryCourse, iKey) {

				var oGenerateCheckbox = function(sFieldName) {
					var sTmp = '';
					var aInquiry = findInquiryCacheCustomer(oInquiryCourse);
					sTmp += '<div class="checkbox">';
					sTmp += '<label>';
					sTmp += '<input type="checkbox" name="'+sFieldName+'" value="'+oInquiryCourse.inquiry_course_id+'_'+oInquiryCourse.program_service_id+'" />&nbsp;';
					sTmp += ''+aInquiry['name'] + ' (' + aInquiry['course_type'] + ')';
					sTmp += '</label>';
					sTmp += '</div>';
					return sTmp;
				};
  
				if(oInquiryCourse && oInquiryCourse.inquiry_course_id) {
					if(!oInquiryCourse.check_level) {
						sTemp += oGenerateCheckbox('apply_level');
					}

					if(oInquiryCourse.check_flexible_allocation) {
						sTmp2 += oGenerateCheckbox('apply_all_blocks_to_flexible_allocation');
					}
				}
			});

			if(!oData.has_errors_allocated) {
				if(sTemp != '') {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_failure_level+'</strong><br/>';
					sHTML += sTemp;
					bShowSave = true;
				}

				if(sTmp2 != '') {
					sHTML += '<br/><strong>'+oData.lang.tuition_move_student_flexible_allocation+'</strong><br/>';
					sHTML += sTmp2;
					bShowSave = true;
				}
			}

			if(bShowSave) {
				oButtons[oData.lang.go] = {};
				oButtons[oData.lang.go].function = function() {
					executeMoveStudent(sType, iAllWeeks);
				}.bind(sType, iAllWeeks);
			} else {
				oButtons[oData.lang.close] = {};
				oButtons[oData.lang.close].function = function() {
					objLitBox.close();
				};
			}
			
			if(!oData.has_double_allocations) {
				sHTML += '</form>';
			}

			sHTML += '</div>';

			objLitBox = new Dialog({
				content: sHTML,
				openOnCreate: true,
				destroyOnClose: true,
				height: 400,
				width: 700,
				sTitle: oData.lang.title,
				buttons: oButtons,
				closeIconEvent: true
			});

		}

	}

	function executeMoveStudent(sType, iAllWeeks) {

		var aFormData = $('formMoveDialog').serialize(true);
		var aInquiries = [];
		var sTemp;

		if(typeof aFormData.inquiries != 'object') {
			sTemp = aFormData.inquiries;
			aFormData.inquiries = new Array(sTemp);
		}
		if(typeof aFormData.apply_level != 'object') {
			sTemp = aFormData.apply_level;
			aFormData.apply_level = new Array(sTemp);
		}
		if(typeof aFormData.apply_all_blocks_to_flexible_allocation != 'object') {
			sTemp = aFormData.apply_all_blocks_to_flexible_allocation;
			aFormData.apply_all_blocks_to_flexible_allocation = new Array(sTemp);
		}

		$A(aFormData.inquiries).each(function(iItem, iKey) {

			aInquiries[iKey] = {};
			aInquiries[iKey]['inquiry_course_id'] = iItem;
			$A(aFormData.apply_level).each(function(iItemLevel) {
				if(iItemLevel == iItem) {
					aInquiries[iKey]['apply_level'] = 1;
				}
			});
			$A(aFormData.apply_all_blocks_to_flexible_allocation).each(function(iItemLevel) {
				if(iItemLevel == iItem) {
					aInquiries[iKey]['apply_all_blocks_to_flexible_allocation'] = 1;
				}
			});
		});

		moveStudent(aInquiries, aFormData.block_id, aFormData.room_id, sType, iAllWeeks);

		objLitBox.close();

	}

	function moveStudent(aInquiries, iBlockId, iRoomId, sType, iAllWeeks) {

		var strRequestUrl = '/ts-tuition/scheduling/json/move-student';
		var strParameters = 'block_id='+iBlockId+'&room_id='+iRoomId+'&type='+sType;

		$A(aInquiries).each(function (oItem) {
			strParameters += '&inquiries[]='+oItem.inquiry_course_id+'_'+oItem.program_service_id;
			if(oItem.apply_level) {
				strParameters += '&apply_level[]='+oItem.inquiry_course_id+'_'+oItem.program_service_id;
			}
			if(oItem.apply_all_blocks_to_flexible_allocation) {
				strParameters += '&apply_all_blocks_to_flexible_allocation[]='+oItem.inquiry_course_id+'_'+oItem.program_service_id;
			}
    	});

		if(selectedRow && sType=='allocated'){
			var aSelectedRowInfo	= selectedRow.split('_');
			var iReplaceBlockId		= aSelectedRowInfo[2];
			strParameters += '&replace_block='+iReplaceBlockId;
		}

		strParameters += '&all_weeks='+iAllWeeks;
		strParameters += '&day='+iCurrentWeekday;

		var objAjax = new Ajax.Request(
			strRequestUrl, {
				method : 'post',
				parameters : strParameters,
				onComplete : moveStudentCallback,
				onLoading: showPlanificationLoading
			}
		);

	}

	function moveStudentCallback(oResponse) {

		hidePlanificationLoading();

		var oData = oResponse.responseText.evalJSON();

		if(oData.success == 1) {

			oData.block_ids.each(function(aBlock) {
				updateBlock(aBlock['block_id'], aBlock['room_id']);
			});

			selectBlock(false, $('room_content_'+oData.block_id+'_'+oData.room_id));

			if(oData.old_block_ids) {
				oData.old_block_ids.each(function(aBlock) {
					updateBlock(aBlock['block_id'], aBlock['room_id']);
				});
			}

			loadStudents('allocated');
			loadStudents('unallocated');

			oStudentsGui = getStudentsGui('unallocated');
			oStudentsGui.bResetSelectedRows = true;
			
		} else {

			updateBlock(oData.block_id, oData.room_id);

			loadStudents('unallocated');
			if (oData.message) {
				let html = '<div style="padding: 20px;">'+oData.message+'</div>';
				objLitBox = new Dialog({
					content: html,
					openOnCreate: true,
					destroyOnClose: true,
					height: 400,
					width: 700,
					sTitle: oData.title,
					buttons: false,
					closeIconEvent: true
				});

			}

		}

	}
	
	function moveBlock(oContent, oContainer) {

		if(oContent.hasClassName('room_content')) {

			var oItem = oContent.remove();
			oContainer.insert(oItem);
	
			var strRequestUrl = '/ts-tuition/scheduling/json/move-block';
			var strParameters = 'content_id='+encodeURIComponent(oContent.id)+'&container_id='+encodeURIComponent(oContainer.id);
	
			var objAjax = new Ajax.Request(
									strRequestUrl,
									{
										method : 'post',
										parameters : strParameters,
										onComplete : moveBlockCallback
									}
			);

		}
	
	}

	function moveBlockCallback(oResponse) {

		var oData = oResponse.responseText.evalJSON();

		if(!oData.success) {
			alert(oData.message);
		}
		
		preparePlanification();
	}

	function updateBlock(iBlockId, iRoomId) {

		var oContainer = $('room_content_'+iBlockId+'_'+iRoomId);

		if(!oContainer) {
			// Bei iRoomId = 0 steht die 0 nicht in der ID
			oContainer = $('room_content_'+iBlockId+'_');
		}

		if(typeof iRoomId == 'undefined') {
			console.error('Missing room id for block update!');
		}

		if(oContainer) {
    		var strRequestUrl = '/ts-tuition/scheduling/json/update-block';
    		var strParameters =
				'block_id='+encodeURIComponent(iBlockId)+
				'&room_id='+encodeURIComponent(iRoomId)
			;

    		new Ajax.Updater(
				oContainer.id,
				strRequestUrl,
				{
					method : 'post',
					parameters : strParameters,
					onComplete : updateBlockCallback.bind({}, iBlockId, iRoomId)
				}
    		);
		}
	}	
	
	function updateBlockCallback(iBlockId, iRoomId) {
		bindSelectBlockEvent($('room_content_'+iBlockId+'_'+iRoomId));
	}

	function selectBlock(dummy, oElement, bFocus) {
		
		if($(selectedRow)) {
			$(selectedRow).removeClassName('selected');
		}
		
		oElement.addClassName('selected');
		
		selectedRow = oElement.id;

		if (bFocus) {
			focusBlock(oElement);
		}

		loadStudents('allocated');

		checkToolbar();

	}

	function focusBlock(element) {

		// TODO scrollIntoView() funktioniert nicht sauber

		var container = $j(element).closest('div.scroll-table-body')[0];

		if (!container) {
			console.error('Missing sortable container');
			return;
		}

		var scrolling = getScrollingOffset(element, container)

		//container.scrollTo({ ...scrolling, ...{ behavior: 'smooth'}})
		$j(container).animate({ scrollTop: scrolling.top, scrollLeft: scrolling.left }, 200)
		//container.scrollTop = scrolling.top;
		//container.scrollLeft = scrolling.left;

	}

	function getScrollingOffset(element, container) {

		var elementRect = element.getBoundingClientRect();
		var containerRect = container.getBoundingClientRect();

		var minHeight = Math.min(element.clientHeight, container.clientHeight);

		// -5 wegen dem box-shadow
		let top = elementRect.top - containerRect.top + container.scrollTop - 5;
		let left = elementRect.left - containerRect.left + container.scrollLeft - (element.clientWidth / 2)  - 5;

		top = top >= 0 ? top : 0;
		left = left >= 0 ? left : 0;

		let visibleX = [container.scrollLeft, (container.scrollLeft + container.clientWidth)];
		let visibleY = [container.scrollTop, (container.scrollTop + container.clientHeight)];

		let fullTop = top + minHeight
		let fullLeft = left + element.clientWidth

		if (fullTop >= visibleY[0] && fullTop <= visibleY[1]) {
			top = container.scrollTop;
		}

		if (fullLeft >= visibleX[0] && fullLeft <= visibleX[1]) {
			left = container.scrollLeft;
		}

		return { top: top, left: left }
	}

	function toggleBlock(dummy, oElement, bActive) {
		if(!bActive) {
			oElement.removeClassName('hover');
		} else {
			oElement.addClassName('hover');
		}
	}
	
	function loadStudents(sType, bEmptyList) {

		var oStudentsGui;

		if(sType=='allocated' && selectedRow) {

			var intBlockId = 0;
			var intRoomId = 0;

			if(!bEmptyList){
				var aItemId = selectedRow.replace(/room_content_/, '').split('_');
				intBlockId = aItemId[0];
				intRoomId = aItemId[1];
			}

			oStudentsGui = getStudentsGui('allocated');
			oStudentsGui.iBlockId = intBlockId;
			oStudentsGui.iRoomId = intRoomId;

		} else {

			oStudentsGui = getStudentsGui('unallocated');
		}

		oStudentsGui.loadTable(false);
	}

	function selectAllocatedStudent(e, sId) {

		var oRow = $(sId);

		if(
			selectedStudent && 
			$(selectedStudent)
		) {
			$(selectedStudent).up(1).removeClassName('selectedRow');
		}

		oRow.up(1).addClassName('selectedRow');
		selectedStudent = sId;

		checkToolbar();		

	}

	function loadStudentsCallback(oResponse) {

		var oData = oResponse.responseText.evalJSON();

		loadStudentsExecute(oData);

		$('loading_indicator').hide();

	}

	function loadStudentsExecute(aData, sType) {

		var sMultipleId;
		var aSplit;
		var iInquiryCourseId;
		var sHash;
		if(sType=='unallocated'){
			sHash = sStudentsUnallocatedHash;
		}else{
			sHash = sStudentsAllocatedHash;
		}
		var oTr;
		var oDraggables = $j();
		var oRowDraggableDivs;

		aData.each(function(aBodyList){

			oTr = $j('#row_'+sHash+'_'+aBodyList.id);

			if(oTr.length){
				oRowDraggableDivs = oTr.find('.student');

				// Select Row setzten
				oTr.click(function(e) {
					
					var oTr = $('row_'+sHash+'_'+aBodyList.id);
				
					var oStudentsDiv = oTr.up('.divStudents');
					
					if(
						oStudentsDiv && 
						oStudentsDiv.id == 'divStudentsAllocated'
					){
						//var oGUI		= getStudentsGui('allocated');
					} else {
						// nur hier da die andere gui scheinbar selectRow noch hat oder es anderst macht...
						var oGUI		= getStudentsGui('unallocated');
						oGUI.selectRow(e, oTr, false, true);
					}
					
				}.bind(this));
				
			}
			sMultipleId = aBodyList.multiple_checkbox_id;
			aSplit		= sMultipleId.split('_');
			iInquiryCourseId	= aSplit[3];

			aBodyList.items.each(function(oColumn){
				
				if(!aInquiryCache[iInquiryCourseId]){
					aInquiryCache[iInquiryCourseId] = {};
				}

				if(oColumn.db_column == 'lastname') {

					if(typeof oColumn.title == 'object') {
						if(oColumn.title.data && oColumn.title.data.name){
							aInquiryCache[iInquiryCourseId]['name'] = oColumn.title.data.name;
						}
						
					} else {
						aInquiryCache[iInquiryCourseId]['name'] = oColumn.title;
					}
				}

				if(oColumn.db_column=='course_type') {
					aInquiryCache[iInquiryCourseId]['course_type'] = oColumn.text;
				}

				if(
					oColumn.db_column == 'state' &&
					oColumn.text != 'V' &&
					oRowDraggableDivs.length
				) {
					oDraggables = oDraggables.add(oRowDraggableDivs);
				}

			});
		});

		oDraggables.each(function(iKey, oDivRow) {

			$j(oDivRow).draggable({
				appendTo: 'body',
				helper: 'clone',
				zIndex: 9999
			});
		});

		/*
		var objTable = $('guiScrollBody_'+sStudentsUnallocatedHash);
		if(objTable) {
			objTable.style.height = (iStudentsHeight-26-10)+'px';
		}

		var objTable = $('guiScrollBody_'+sStudentsAllocatedHash);
		if(objTable) {
			objTable.style.height = (iStudentsHeight-26-10)+'px';
		}*/
	
	}
	
	document.onmousemove = updateTooltip;
	var oTooltip = null;
	function updateTooltip(e) {
	  if (oTooltip != null) {
		x = e.clientX;
	    y = e.clientY;
	    oTooltip.style.left = (x + 20) + "px";
	    oTooltip.style.top  = (y - 80 ) + "px";
	  }
	}
	
	
	function showTooltip(sElementId){
		oTooltip = $(sElementId);
		oTooltip.style.display = "block";
		oTooltip.show();
	}
	
	function hideTooltip(){
		oTooltip.hide();
		oTooltip = null;
	}

	function toggleSide(bMode) {
		go('?&left_frame='+bMode);
	}

	function initCustomerPage() {
		changeWeekDay(iStartDay, 1);

		var oGui = getStudentsGui('unallocated')

		window.__FIDELO__.SchedulingVueUtil.createVueApp('FilterBar', $('filter-bar'), oGui, {
			floors: window.aFloorOptions ?? []
		})
	}

	function checkListHeight(iTopHeight) {

		var intHeight = 0;
		intHeight = window.innerHeight;
		if(!intHeight) {
			intHeight = document.body.clientHeight;
		}
		if(!intHeight) {
			intHeight = document.documentElement.clientHeight;
		}

		var oHeader = $('divHeader');
		var iHeaderHeight = 0;
		if(oHeader) {
			iHeaderHeight = oHeader.getHeight();
			intHeight = intHeight - iHeaderHeight;
		}

		var oWeekDaySwitch = $('divWeekDaySwitch');
		if(oWeekDaySwitch) {
			intHeight = intHeight - 29;
		}

		var oDivLegend = $('divFooter_planification');
		if(oDivLegend) {
			intHeight = intHeight - oDivLegend.getHeight();
		}

		var oDivDraggable = $('Gui2ChildTableDraggable_' + sStudentsUnallocatedHash);
		if(oDivDraggable){
			intHeight = intHeight - oDivDraggable.getHeight();
		}

		oWeekDaySwitch.style.height = '37px';

		if(iTopHeight) {
			iStudentsHeight = iTopHeight - iHeaderHeight;
			iPlanificationHeight = intHeight - iStudentsHeight;
		} else {
			iPlanificationHeight = intHeight * 2 / 3;
			iPlanificationHeight = Math.round(iPlanificationHeight);

			iStudentsHeight = intHeight - iPlanificationHeight;
		}

		iPlanificationHeight -= 40;

		var iAllWidth	= $('divLowerContainer').getWidth();

		if(
			iCountOtherRooms &&
			iCountOtherRooms > 0
		) {

			var iCalculatedWidth = 42 + 19 + (iCountOtherRooms * iColumnWidth);

			var iRightWidth	= iAllWidth / 3;

			if(iCalculatedWidth < iRightWidth) {
				iRightWidth = iCalculatedWidth;
			}

			var iLeftWidth	= iAllWidth - iRightWidth - 2;

		} else {
			var iLeftWidth	= iAllWidth;
			var iRightWidth	= 0;
		}

		$('divPlanification').style.width = iLeftWidth+'px';
		$('divPlanificationOtherRooms').style.width = iRightWidth+'px';
		
		if(iRightWidth > 0){
			$('divPlanificationOtherRooms').style.borderLeft = '2px solid #CCCCCC';
		}else{
			$('divPlanificationOtherRooms').style.border = 'none';
		}

		var objContainer = $('divPlanification');

		if(objContainer) {
			objContainer.style.height = iPlanificationHeight+'px';	
		}

		var objStudents = $('divStudents');

		if(objStudents) {
			objStudents.style.height = iStudentsHeight+'px';	
		}

		if($('tablePlanification-head')) {
			iPlanificationBodyHeight = iPlanificationHeight - $('tablePlanification-head').getHeight();

			if($('tablePlanificationother-body')) {
				$('tablePlanificationother-body').style.height = iPlanificationBodyHeight+'px';
				$('tablePlanificationother-body').style.width = iRightWidth+'px';
		}
			if($('tablePlanification-body')) {
				$('tablePlanification-body').style.height = iPlanificationBodyHeight+'px';
				$('tablePlanification-body').style.width = iLeftWidth+'px';
		}
		}

		if($('divStudentsUnallocated')) {
			var iUnallocatedDivWidth   = $('divStudentsUnallocated').getWidth();

			/*
			$('guiTableHead_'+sStudentsUnallocatedHash).setStyle({
				width : iUnallocatedDivWidth - 15
			});

			$('guiScrollBody_'+sStudentsUnallocatedHash).setStyle({
				width : iUnallocatedDivWidth - 15
			});*/
		}

		if($('divStudentsAllocated')) {
			var iAllocatedDivWidth = $('divStudentsAllocated').getWidth();
			if($('tableStudentsAllocated-head-table')) {
				$('tableStudentsAllocated-head-table').style.width = iAllocatedDivWidth - 15;
			}

			if($('tableStudentsAllocated')) {
				$('tableStudentsAllocated').style.width = iAllocatedDivWidth - 15;
			}
		}		

		var objTable = $('guiScrollBody_'+sStudentsUnallocatedHash);
		if(objTable) {
			objTable.style.height = (iStudentsHeight-26-10+3)+'px';
		}

		var objTable = $('guiScrollBody_'+sStudentsAllocatedHash);
		if(objTable) {
			objTable.style.height = (iStudentsHeight-26-10+3)+'px';
		}
		
		if(bFirstInit) {
			ScrollableTable.load();
			bFirstInit = 0;
		}

	}

	function getStudentsGui(sType){
		if(sType=='unallocated'){
			oGUI					= aGUI[sStudentsUnallocatedHash];
		}else{
			oGUI					= aGUI[sStudentsAllocatedHash];
		}

		return oGUI;
	}

	function showQuestionWeeks(oGUI, sAdditional, iBlockId, iRoomId){
		oGUI.block_id			= iBlockId;
		oGUI.room_id			= iRoomId;

		var aElement				= {};
		aElement.task			= 'openDialog';
		aElement.action			= 'move_student_question';
		aElement.additional		= sAdditional;
		aElement.request_data	= '&block_id='+iBlockId+'&room_id='+iRoomId;
		oGUI.prepareAction(aElement);
	}
	
	function getTranslation(sKey)
	{
		var oGui = getStudentsGui('unallocated');
		var sTranslation = oGui.getTranslation(sKey);
		
		return sTranslation;
	}

	function findInquiryCacheCustomer(oInquiryCourse) {

		if(
			oInquiryCourse.inquiry_course_id &&
			oInquiryCourse.inquiry_course_id in aInquiryCache
		) {
			return aInquiryCache[oInquiryCourse.inquiry_course_id];
		}

		// Kurse, die über die prepare_move_student reingefakt wurden, gibt es in aInquiryCache dementsprechend nicht
		if(
			oInquiryCourse.origin_journey_course_id &&
			oInquiryCourse.origin_journey_course_id in aInquiryCache
		) {
			return aInquiryCache[oInquiryCourse.origin_journey_course_id];
		}

		console.error('Couldn\'t find inquiry in inquiry cache!', oInquiryCourse);
		throw new Error('Couldn\'t find inquiry in inquiry cache!'); // Funktioniert in dem Kontext irgendwie nicht

	}

	function showPlanificationLoading() {
		$j('#loading_unallocated, #loading_allocated, #week_loading').show();
	}

	function hidePlanificationLoading() {
		$j('#loading_unallocated, #loading_allocated, #week_loading').hide();
	}

	Event.observe(window, 'load', initCustomerPage);
	Event.observe(window, 'resize', checkListHeight);

	document.body.style.overflow="hidden";
