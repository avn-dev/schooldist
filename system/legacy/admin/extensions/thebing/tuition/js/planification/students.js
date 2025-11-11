var Students = Class.create(ATG2, {
	
	bResetSelectedRows : false,
	
	bCanChangeLevel : true,

	requestCallback: function($super, objResponse, strParameters) {

		var objData = this._evalJson(objResponse);

		if(objData && objData.action) {
			switch(objData.action) {
				case 'createTable':
					this.translations = objData.data.translations;
					this.createAllocatedPagination();
					this.createUnallocatedPagination();
					break;
			}
		}

		$super(objResponse, strParameters);

	},

	requestCallbackHook: function($super, aData) {

		if(
			aData.action == 'createTable'
		) {

			loadStudentsExecute(aData.data.body,this.sView);

			if(
				this.sView == 'unallocated' ||
				this.sView == 'allocated'
			) {
				this.bindLevelEvents();
			}

			this.updateStudentsPagination(aData.data);
			
			this.setOffsetObserver();
			
			if(this.bResetSelectedRows)
			{
				this.unselectAllRows(false);
				
				this.bResetSelectedRows = false;
			}
			
			var oLoading = $('loading_unallocated');
			
			if(oLoading)
			{
				this.bCanChangeLevel = true;
				
				oLoading.hide();
			}
			
		}else if(aData.action == 'changeStudentLevelCallback'){

			if(
				aData &&
				aData.data &&
				aData.data.selectedRows
			){
				this.selectedRowId = aData.data.selectedRows;
			}
			
			this.loadTable(false);

			// Jeweils andere Liste muss auch aktualisiert werden
			if(this.sView === 'unallocated') {
				loadStudents('allocated');
			} else if(this.sView == 'allocated') {
				loadStudents('unallocated');
			}

		}else if(aData.action == 'updateIcons'){
			this.updateIconsHandle();
		}else if(aData.action=='openDialog' && aData.data.action=='move_student_question'){
			var oDialogWrapper = $('dialog_wrapper_ID_0_'+this.hash);
			if(oDialogWrapper){
				var oDialogActions = oDialogWrapper.down('.dialog-actions');
				if(oDialogActions){
					var oButtonYes = oDialogActions.down('.dialog-button');
					if(oButtonYes){

						Event.observe(oButtonYes, 'click', function(e) {
							this.moveStudent(1,aData.data.additional);
						}.bind(this));

						var oButtonNo = oButtonYes.next('.dialog-button');
						if(oButtonNo){
							Event.observe(oButtonNo, 'click', function(e) {
								this.moveStudent(0,aData.data.additional);
							}.bind(this));
						}
					}
				}
			}
		}else if(aData.action=='executeAction'){
			this.moveStudent(aData.all_weeks,aData.data.additional);
		}

		var oPaginationContainer = $('pagination_container_'+this.hash);

		if(
			oPaginationContainer &&
			oPaginationContainer.innerHTML === '' &&
			this.translations &&
			Object.keys(this.translations).length > 0
		) {

			this.createAllocatedPagination();
			this.createUnallocatedPagination();
			
			if(
				aData && 
				aData.data && 
				aData.data.pagination
			) {
				this.updatePagination(aData.data.pagination);
			}
			
		}

	},

	createAllocatedPagination: function() {

		if(this.sView != 'allocated') {
			return;
		}

		var aPagination = {
			id: 'pagination',
			only_pagecount: 1,
			limit_selection: 1,
			limited_selection_options: null
		};

		var oPagination = this.createPagination(aPagination);
		$('pagination_container_'+this.hash).update(oPagination);
		
	},

	createUnallocatedPagination : function() {

		if(this.sView != 'unallocated') {
			return;
		}

		if(!$('pagination_1_'+this.hash+'_result_count')) {

			var aPagination = {
				element_type: "pagination",
				id: "pagination_1",
				html: "",
				only_pagecount: 0,
				limit_selection: 1,
				limited_selection_options: [
					{
						value: 10,
						text: "10"
					},{
						value:30,
						text:"30"
					},{
						value:50,
						text:"50"
					},{
						value:100,
						text:"100"
					},{
						value:200,
						text:"200"
					},{
						value:500,
						text:"500"
					},{
						value:1000,
						text:"1000"
					}
				],
				access:"",
				default_limit:30,
				select_options:[],
				visible:1
			}

			var oPaginationDiv = this.createPagination(aPagination);

			$('pagination_container_'+this.hash).update(oPaginationDiv);

		}

	},

	additionalFilterHook : function(sParam){

		var oSearch = $('search');
		if(oSearch){
			sParam += '&filter[search]='+$F(oSearch);
		}
		var oWeek	= $('week');
		if(oWeek){
			sParam += '&filter[week]='+$F(oWeek);
		}
		if(this.iBlockId){
			sParam += '&block_id='+this.iBlockId;
		}
		if(this.iRoomId){
			sParam += '&room_id='+this.iRoomId;
		}
		var oWeekDaySearch = $('divWeekDaySwitch');
		if(oWeekDaySearch){
			var oActiveWeekDay = oWeekDaySearch.down('.primary-color-element');
			if(oActiveWeekDay){
				var sId = oActiveWeekDay.id;
				var aDayInfo = sId.split('_');
				var iDay = aDayInfo[1];
				sParam += '&weekday='+iDay;
			}
		}
		var oFilterCourse = $('course_filter');
		if(oFilterCourse){
			sParam += '&filter[course]='+$F(oFilterCourse);
		}
		var oFilterGroup = $('group_filter');
		if(oFilterGroup) {
			sParam += '&filter[group]='+$F(oFilterGroup);
		}
		var oFilterCourseCategory = $('course_category_filter');
		if(oFilterCourseCategory){
			sParam += '&filter[course_category]='+$F(oFilterCourseCategory);
		}
		var oFilterState = $('week_state_filter');
		if(oFilterState){
			sParam += '&filter[state]='+$F(oFilterState);
		}
		var oFilterCourseState = $('week_course_state_filter');
		if(oFilterCourseState){
			sParam += '&filter[course_state]='+$F(oFilterCourseState);
		}
		var oFilterLevel = $('level_filter');
		if(oFilterLevel){
			sParam += '&filter[level]='+$F(oFilterLevel);
		}
		var oFilterCustomerState = $('customer_status_filter');
		if(oFilterCustomerState){
			sParam += '&filter[customer_status]='+$F(oFilterCustomerState);
		}
		var oFilterInbox = $('inbox_filter');
		if(oFilterInbox){
			sParam += '&filter[inbox]='+$F(oFilterInbox);
		}
		var oFilterDocumentType = $('document_type_filter');
		if(oFilterDocumentType) {
			sParam += '&filter[document_type_filter]='+$F(oFilterDocumentType);
		}
		var oFilterLevelGroup = $('levelgroup_filter');
		if(oFilterLevelGroup) {
			sParam += '&filter[levelgroup_filter]=' + $F(oFilterLevelGroup);
		}
		var oFilterCheckIn = $('checkin_filter');
		if(oFilterCheckIn) {
			sParam += '&filter[checkin_filter]=' + $F(oFilterCheckIn);
		}
		var oFilterAgency = $('agency_filter');
		if(oFilterAgency) {
			sParam += '&filter[agency_filter]=' + $F(oFilterAgency);
		}

		return sParam;
	},

	resize : function(){
		this.resizeTableHead();
		this.resizeTableBody();
		this.resizeTableColumns();
		checkListHeight();
	},

	updateIconsHandle : function(){
		var iChecked = 0;
		$A($$('#divStudentsAllocated .multiple_checkbox')).each(function(oCheckBox){
			if(oCheckBox.checked){
				iChecked++;
			}
		});
		
		var oDeleteStudentIconDiv = $('toolbar_student_delete');
		if (oDeleteStudentIconDiv) {
			if(0 < iChecked){
				oDeleteStudentIconDiv.style.opacity = '1';
			} else {
				oDeleteStudentIconDiv.style.opacity = '0.2';
			}
		}

		var oStudentCommunicationIconDiv = $('toolbar_student_communication');
		if (oStudentCommunicationIconDiv) {
			if (0 < iChecked){
				oStudentCommunicationIconDiv.style.opacity = '1';
			} else {
				oStudentCommunicationIconDiv.style.opacity = '0.2';
			}
		}
	},

	bindLevelEvents : function() {

		if(this.sView == 'unallocated') {
			$A($$('#divStudentsUnallocated select.levelSelect')).each(function(oSelect){
				Event.observe(oSelect, 'change', function(e) {
					this.changeStudentLevel(oSelect);
				}.bind(this));
			}.bind(this));
		}

		if(this.sView == 'allocated') {
			$A($$('#divStudentsAllocated select.levelSelect')).each(function(oSelect){
				Event.observe(oSelect, 'change', function(e) {
					this.changeStudentLevel(oSelect);
				}.bind(this));
			}.bind(this));
		}

	},

	changeStudentLevel : function(oSelect){
		
		if(!this.bCanChangeLevel)
		{
			var aError = new Array();
			aError[0] = this.getTranslation('error_dialog_title');
			aError[1] = this.getTranslation('pls_wait');
			
			this.displayErrors(aError);
			
			return;
		}
		
		var sParam = '&task=changeStudentLevel';
		sParam += '&level_id='+$F(oSelect);
		sParam += '&week='+$F('week');
		
		var oWeekDaySearch = $('divWeekDaySwitch');
		if(oWeekDaySearch){
			var oActiveWeekDay = oWeekDaySearch.down('.sortasc');
			if(oActiveWeekDay){
				var sId = oActiveWeekDay.id;
				var aDayInfo = sId.split('_');
				var iDay = aDayInfo[1];
				sParam += '&weekday='+iDay;
			}
		}

		var oParentTr	= oSelect.up('tr.guiBodyRow');
		var sTrId		= oParentTr.id;
		var iSelectedId	= sTrId.replace('row_'+this.hash+'_','');

		sParam += '&id='+iSelectedId;

		var oTr;
		var sTrIdTemp;
		var sSelectedIds = '';

		$A($$('#guiTableBody_'+this.hash+' .multiple_checkbox')).each(function(oCheckBox){
			if(oCheckBox.checked){	
				oTr = oCheckBox.up('.guiBodyRow');
				if(oTr){
					sTrIdTemp = oTr.id.replace('row_'+this.hash+'_', '');
					sSelectedIds += '&selected_ids[]='+sTrIdTemp;
				}
			}
		}.bind(this));

		sParam += sSelectedIds;
		
		this.unselectAllRows(false);
		
		var oLoading = $('loading_unallocated');
		
		if(oLoading)
		{
			this.bCanChangeLevel = false;
			
			oLoading.show();
		}

		this.request(sParam);
	},

	moveStudent : function(iAllWeeks,sCallbackType){

		if(sCallbackType=='move'){
			prepareMoveStudent(this.content,this.container,1,iAllWeeks);
		}else if(sCallbackType=='delete_block'){
			deleteBlock(this.block_id,iAllWeeks);
		}else if(sCallbackType=='clear_students'){
			clearStudents(this.block_id, this.room_id, true,iAllWeeks);
		} else{
			deleteStudent(this.block_id, this.aSelectedInquiryIds,iAllWeeks, this.room_id);
		}

		this.closeDialog('ID_0');
	},

	selectRow : function($super, e, oTr, bRequest, bCheckbox){
		//this.selectedRowClassName = 'guiBodyRow sc_linear_hover';
		this.selectedRowClassName = 'guiSelectedRow sc_bg_light guiBodyRow sc_linear_hover';
		$super(e, oTr, bRequest, bCheckbox);
	},
	
	updateStudentsPagination : function(aData){
		var sSpanTotalId	= 'pagination_' + this.hash + '_total';
		var oSpanTotal		= $(sSpanTotalId);
		
		if(oSpanTotal && aData.pagination){
			oSpanTotal.update(aData.pagination.total);
		}
	},
	
	setOffsetObserver : function(){
		var aCheckboxes = this.getMultipleCheckboxes();
		
		aCheckboxes.each(function(oCheckbox){
			Event.observe(oCheckbox, 'change', function(e) {
				this.updateOffset();
			}.bind(this));
		}.bind(this));
	},
	
	getMultipleCheckboxes : function(){
		var aCheckboxes = $$('#guiTableBody_'+this.hash+' .multiple_checkbox');
		
		return aCheckboxes;
	},
	
	updateOffset : function(){
		
		var aCheckboxes = this.getMultipleCheckboxes();
		var iOffset		= 0;
		
		aCheckboxes.each(function(oCheckbox){
			if(oCheckbox.checked){
				iOffset++;
			}
		});
		
		var oOffset = $('pagination_'+this.hash+'_offset');
		
		if(oOffset){
			oOffset.update(iOffset);
		}
	},
	
	selectAllRows : function ($super, oCheckbox){
		
		$super(oCheckbox);
		
		this.updateOffset();
	},
	
	unselectAllRows : function ($super, bUpdateIcons){
		
		$super(bUpdateIcons);
		
		var oOffset = $('pagination_'+this.hash+'_offset');
		
		if(oOffset){
			oOffset.update('0');
		}
	},

	resizeGuiPage: function ($super, event, element) {
		$super(event, element);
		checkListHeight(this.iPageResizeHeight - 50);
	}

});
