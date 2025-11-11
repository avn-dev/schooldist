var MatchingGui = Class.create(UtilGui, {

	_bLockCutRequest: false,
	sGotoAllocation: null,
	bOverview: false,
	bAvailability: false,
	latestSearchString: null,

	prepareAction: function($super, aElement, aData) {

		// Filter übergeben für Export
		if(aElement.task === 'requestAsUrl') {
			aElement.request_data = this.getFilterparam(this.hash);
		}

		if(
			aElement.task == 'request' &&
			(
				aElement.action == 'overview' ||
				aElement.action == 'availability'
			)
		) {
			// Toggle overview
			if(this.bOverview == true) {
				
				this.bOverview = false;
				this.bAvailability = false;
				
				$('matching_body').update();
				this.initMatchingList();
				return;
			
			} else {
				this.bOverview = true;
				if(aElement.action == 'availability') {
					this.bAvailability = true;
				}
			}
		}

		$super(aElement, aData);

	},

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse

		$super(aData);

		var sTask = aData.task;
		
		if(
			aData.data &&
			aData.data.idAccommodation
		){
			// Dieses hier hat immer vorrang, falls man einen Kunden Zuweist, verschwindet er aus 
			// der oberen Liste, Damit man weiter mit dem Kunden arbeiten kann muss diese ID genommen werden
			this.idAccommodation = aData.data.idAccommodation;
		}
	
		if(aData.action == 'createTable'){
			// Matching höhe anpassen  für Legende
			this.initMatchingList();
		}

		if(
			aData.data && 
			aData.data.task && 
			aData.data.task == 'moveAllocation' &&
			aData.roomData
		){
			var oFamilySelect = $('family_id');
			var oRoomSelect = $('room_id');
			var oBedSelect = $('dialog_move_bed_number');

			var oRefreshBedData = function() {
				var aBeds = aData.bed_data[$F(oRoomSelect)];
				this.updateSelectOptions(oBedSelect, aBeds);
			}.bind(this);

			oRefreshBedData();

			Event.observe(oFamilySelect, 'change', function(e) {
				var aRooms = aData.roomData[$F(oFamilySelect)];
				this.updateSelectOptions(oRoomSelect, aRooms);
				oRefreshBedData();
			}.bind(this));

			Event.observe(oRoomSelect, 'change', function(e) {
				oRefreshBedData();
			}.bind(this));

			this._fireEvent('change', oFamilySelect);
			this._fireEvent('change', oRoomSelect);
		}

		// Druck Icon
		var oDiv = $('method__'+this.hash);
		
		if(oDiv){
			var oImg = oDiv.down('i');
			var oButton = oDiv.up('.guiBarElement');
			
			if(
				oButton &&
				this.bOverview == 1
			){

				oButton.removeClassName('guiBarInactive');
				oImg.removeClassName('guiIconInactive');
				oButton.addClassName('guiBarLink');
				Event.observe(oButton, 'click', function() {
				
					/**
					 * Wenn das hier mal Probleme macht, bitte in JS Methode openProgressReportPrint nachschauen
					 */
					/*var oDiv = $('matching_body');
					var oWin = window.open('', 'printWindow', 'location=no,status=no,width=1000,height=900');
				
					var sHTML = '<html><head>';
					sHTML += '<link type="text/css" rel="stylesheet" href="/admin/css/admin.css" media="" />';
					sHTML += '<link type="text/css" rel="stylesheet" href="/admin/extensions/gui2/gui2.css" media="" />';
					sHTML += '<link type="text/css" rel="stylesheet" href="/assets/ts/css/gui2.css" media="" />';
					sHTML += '<link type="text/css" rel="stylesheet" href="/admin/extensions/thebing/css/accommodation.css" media="" />';

					sHTML += '<title></title>';
					sHTML += '</head><body><div id="matching_body">';
					oWin.document.writeln(sHTML);
					oWin.document.write(oDiv.innerHTML);
					oWin.document.writeln('</div></body></html>');
					oWin.print();*/

					//var oDiv = $('matching_body');
					var oDivHeader = $('table_overview-head');
					var oDivBody = $('table_overview-body');

					var sHtmlHead = '<div id="table_overview-head">';
					sHtmlHead += oDivHeader.innerHTML;
					sHtmlHead += '</div>';

					var sHtmlBody = '<div id="table_overview-body">';
					sHtmlBody += oDivBody.innerHTML;
					sHtmlBody += '</div>';
					
					if(!this.iCounter){
						this.iCounter = 1;
					}else{
						this.iCounter++;
					}
					
					var sWindowName = 'printWindow_' + this.iCounter;

					var oWin = window.open('', sWindowName, 'location=no,status=no,width=1000,height=900,scrollbars=yes');

					var sHTML = '<html style="overflow: auto; height: auto; width: auto;"><head>';
					sHTML += '<script type="text/javascript" src="/admin/js/prototype/prototype.js"></script>';
					sHTML += '<link type="text/css" rel="stylesheet" href="/admin/css/admin.css" media="" />';
					sHTML += '<link type="text/css" rel="stylesheet" href="/admin/extensions/gui2/gui2.css" media="" />';
					sHTML += '<link type="text/css" rel="stylesheet" href="/assets/ts/css/gui2.css" media="" />';
					sHTML += '<link type="text/css" rel="stylesheet" href="/admin/extensions/thebing/css/accommodation.css" media="" />';

					sHTML += '<title></title>';
					sHTML += '</head><body style="overflow: auto; height: auto; width: auto;"><div id="matching_body">';

					oWin.document.open();
					oWin.document.writeln(sHTML);
					//oWin.document.write(oDiv.innerHTML);
					oWin.document.write(sHtmlHead);
					oWin.document.write(sHtmlBody);

					var sEndHtml = '';
					sEndHtml += '</div><script type="text/javascript">';
					sEndHtml += 'Event.observe(window, "load", function() { self.print(); });';
					sEndHtml += '</script></body></html>';

					oWin.document.writeln(sEndHtml);
					oWin.document.close();
					oWin.focus();
   
				}.bind(this));
			}else if(
				oButton &&
				this.bOverview == false
			){
				oButton.addClassName('guiBarInactive');
				oImg.addClassName('guiIconInactive');
				oButton.removeClassName('guiBarLink');
			}
			
		}
	
		if(
			aData.data &&
			aData.data.action == 'matching_cut'
		){
			// Zerschneidungsdialog	
				
			this.setCutObserver(aData.data);

		}else if(
			aData &&
			aData.action == 'showOverview'
		){
			// Overview Dialog
			this.updateIconCallbackHook(aData.data);
			
			this.closeDialog(aData.data.id);
			
		}else if(
			aData &&
			(
				aData.action == 'reloadOverview' || // Hier immer neu laden
				(
					aData.action == 'saveDialogCallback' &&
					this.bOverview == true
				)
			)
		){
			// Overview neu laden
			this.reloadOverview(aData.error);
		}else if(
			aData &&
			aData.action == 'reloadMatching'
		){
			this.reloadMatching(aData);
		}else if(
			aData &&
			aData.action == 'confirmMoveAllocation'
		){
			if(confirm(aData.data.move_question)){
				this.moveAllocation(aData.data.allocation_id, aData.data.room_id);
			}else{
				this.reloadMatching();
			}
			
		} else if(
			aData &&
			aData.action == 'reloadMatchingAndTable'
		){
			this.reloadMatching(aData);
			this.loadTable(false,this.hash);
		}
        
        if(aData && (
			aData.action === 'reloadMatching' ||
			aData.action === 'updateIcons')
		) {
			// Request für Schere (Zerschneiden) wieder freigeben
			this._bLockCutRequest = false;
		}

        // Zwischenbalken ausbalken den die Page Klasse erstellt
        $$('.Gui2ChildTableButtonContainer').each(function(oDiv){
            oDiv.remove();
		}.bind(this));
	},
	
	reloadMatching : function(aData){

		if(
			aData.error &&
			aData.error.length > 0	
		){
			this.displayErrors(aData.error);
		}else if(
			aData.success &&
			aData.success == 1	&&
			aData.success_message &&
			aData.success_title
		){
			this.displaySuccess(this.sCurrentDialogId, aData.success_message, aData.success_title);
		}

		this.updateIcons();
			
	},
	
	reloadOverview : function(aError){
		
		if(
			aError &&
			aError.length > 0
		) {
			this.displayErrors(aError);
		}
		
		var sParam = '';
		sParam += '&task=request';
		
		if(this.bAvailability) {
			sParam += '&action=availability';
		} else {
			sParam += '&action=overview';
		}

		sParam += this.getFilterparam(this.sHash);

		this.request(sParam);
		
	},
	
	additionalFilterHook : function(sParam){

		sParam += '&'+$j('#matching_bar_availability select, #matching_bar_availability input').serialize();

		sParam += '&idAccommodation=' + this.idAccommodation;
		sParam += '&bOverview=' + (this.bOverview?1:0);
		sParam += '&bAvailability=' + (this.bAvailability?1:0);
		
		return sParam;
	},
	
	setCutObserver : function(aData){
		
		// Zeile löschen
		$$('#dialog_'+aData.id+'_'+this.hash+' .delete_img').each(function(oImg){
			Event.stopObserving(oImg, 'click');
			Event.observe(oImg, 'click', function(e){
				oImg.up('.GUIDialogRow').remove(); 
			}.bind(this));
		}.bind(this));
		
		$$('#dialog_'+aData.id+'_'+this.hash+' .clone_img').each(function(oImg){
					Event.stopObserving(oImg, 'click');
			Event.observe(oImg, 'click', function(e){			
				this.cloneCuttingRow(aData, oImg);
			}.bind(this));
		}.bind(this));	

	},

	cloneCuttingRow : function(aData, oImg){
		
		var oCloneRow = oImg.up('.GUIDialogRow');
		var oNewRow = oCloneRow.clone(true);

		oImg.removeClassName('clone_img');
		oImg.addClassName('delete_img');
		Event.stopObserving(oImg, 'click');
		$j(oImg).children('i').removeClass('fa-plus-circle').addClass('fa-minus-circle');

		// MEGA Code!
		var iCount = 0;	
		do {
			iCount++;
		} while ($('save['+this.hash+']['+aData.id+'][cutting_'+iCount+']'));

		oCloneRow.insert({
			after: oNewRow 
		});

		//var oNewImg = oNewRow.down('clone_img');
		var oNewCalendar = oNewRow.down('input');
		
		oNewCalendar.id = 'save['+this.hash+']['+aData.id+'][cutting_'+iCount+']';
		oNewCalendar.name = 'save[cutting_'+iCount+']';
		oNewCalendar.value = '';
		// oNewCalendar.previous('div').update();
		
		//oNewCalendar.next('i').id = 'save['+this.hash+']['+aData.id+'][calendar][cutting_'+iCount+']';

		this.prepareCalendar(oNewCalendar/*, oNewCalendar.next('i')*/);

		this.executeCalendars();

		// neue Observer setzen
		this.setCutObserver(aData);
	},

	updateIconCallbackHook : function(aData) {

		this.sGotoAllocation = null;

		if(aData.matching_data) {
			this.allMatchingData			= aData.matching_data;

			// Anzeige wo wir uns gerade befinden
			this.matchingView				= this.allMatchingData.view;
			this.matchingInquiry			= this.allMatchingData.inquiry;
			this.matchingCustomer			= this.allMatchingData.customer;
			this.matchingData				= this.allMatchingData.data;
			//this.bOverview					= this.allMatchingData.bOverwiew;

			this.displayData				= aData.displayData;
						
			if(aData.action == 'availability') {
				this.bAvailability = true;
			} else if(aData.action == 'overview') {
				this.bAvailability = false;
			}

			this.iHalfDayWidth				= 10;
			this.aInquiryCache				= [];
			this.aSharingCache				= [];
//			this.aRoomJump					= [];
			
			this.aMonthData			= [];
			this.aMonthData[0]		= this.getTranslation('january');
			this.aMonthData[1]		= this.getTranslation('february');
			this.aMonthData[2]		= this.getTranslation('march');
			this.aMonthData[3]		= this.getTranslation('april');
			this.aMonthData[4]		= this.getTranslation('may');
			this.aMonthData[5]		= this.getTranslation('june');
			this.aMonthData[6]		= this.getTranslation('july');
			this.aMonthData[7]		= this.getTranslation('august');
			this.aMonthData[8]		= this.getTranslation('september');
			this.aMonthData[9]		= this.getTranslation('october');
			this.aMonthData[10]		= this.getTranslation('november');
			this.aMonthData[11]		= this.getTranslation('december');
			
			this.newHeadLine_top	= '';
			this.newHeadLine		= '';

			// Matching laden
			this.loadMatchingCallback();
		}

		this.initMatchingList();

		if(this.sGotoAllocation) {
			if($j('#'+this.sGotoAllocation).parent().parent().position().top > 0) {
				$j('#table_overview-body').scrollTop($j('#'+this.sGotoAllocation).parent().parent().position().top);
			}
		}
		
		return aData;
	},
	
	prepareFamilyInfo : function(aFamily, aInquiry){

		var aReturn = [];

		var sDescription = '';
		var sIcon = '';
		var iRating = 0;
		var iMax = 0;

		var aTranslations = this.getTranslation('matching_criteria');

		var cWriteString = function(sLabel, bState) {
			var sClass = bState ? 'text-success' : 'text-danger';
			sDescription += '<span class="'+sClass+'"><i class="fa fa-' + (bState ? 'check' : 'times') + '"></i> ';
			sDescription += aTranslations[sLabel]+'</span>&nbsp;';
			iMax += 3;
		}.bind(this);

		if( (aFamily['ext_42'] == 1 &&  aInquiry['matching_cats'] == 2) || ( aFamily['ext_42'] == 0 && aInquiry['matching_cats'] == 1)){
			cWriteString('A', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_cats'] != 0) {
			cWriteString('A', false);
			iRating = iRating+1;
		}

		if( (aFamily['ext_43'] == 1 &&  aInquiry['matching_dogs'] == 2) || ( aFamily['ext_43'] == 0 && aInquiry['matching_dogs'] == 1)){
			cWriteString('B', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_dogs'] != 0 ) {
			cWriteString('B', false);
			iRating = iRating+1;
		}

		if( (aFamily['ext_44'] == 1 &&  aInquiry['matching_pets'] == 2) || ( aFamily['ext_44'] == 0 && aInquiry['matching_pets'] == 1)){
			cWriteString('C', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_pets'] != 0 ) {
			cWriteString('C', false);
			iRating = iRating+1;
		}

		if((aFamily['ext_45'] == 1 && aInquiry['matching_smoker'] == 2) || (aFamily['ext_45'] == 0 && aInquiry['matching_smoker'] == 1)){
			cWriteString('D', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_smoker'] != 0 )  {
			cWriteString('D', false);
			iRating = iRating+1;
		}

		if(aFamily['ext_46'] == aInquiry['matching_distance_to_school'] && aFamily['ext_46'] != 0){
			cWriteString('E', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_distance_to_school'] != 0  && aFamily['ext_46'] != 0 ) {
			cWriteString('E', false);
			iRating = iRating+1;
		}

		if((aFamily['ext_47'] == 1 && aInquiry['matching_air_conditioner'] == 2) || (aFamily['ext_47'] == 0 && aInquiry['matching_air_conditioner'] == 1)){
			cWriteString('F', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_air_conditioner'] != 0 ) {
			cWriteString('F', false);
			iRating = iRating+1;
		}

		if((aFamily['ext_48'] == 1 &&  aInquiry['matching_bath'] == 2) || (aFamily['ext_48'] == 0 &&  aInquiry['matching_bath'] == 1)){
			cWriteString('G', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_bath'] != 0 ) {
			cWriteString('G', false);
			iRating = iRating+1;
		}

		if(aFamily['ext_49'] == aInquiry['matching_family_age']  && aFamily['ext_49'] > 0 ){
			cWriteString('H', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_family_age'] != 0  && aFamily['ext_49'] > 0) {
			cWriteString('H', false);
			iRating = iRating+1;
		}

		if(aFamily['ext_50'] == aInquiry['matching_residential_area'] && aFamily['ext_50'] != '' && aInquiry['matching_residential_area'] != '' ){
			cWriteString('I', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_residential_area'] != 0 ) {
			cWriteString('I', false);
			iRating = iRating+1;
		}

		if((aFamily['ext_51'] == 1 && aInquiry['matching_family_kids'] == 2) || (aFamily['ext_51'] == 0 && aInquiry['matching_family_kids'] == 1)){
			cWriteString('J', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_family_kids'] != 0 ) {
			cWriteString('J', false);
			iRating = iRating+1;
		}

		if((aFamily['ext_53'] == 1 && aInquiry['matching_internet'] == 2) || (aFamily['ext_53'] == 0 && aInquiry['matching_internet'] == 1)){
			cWriteString('K', true);
			iRating = iRating+3;
		} else if(aInquiry['matching_internet'] != 0) {
			cWriteString('K', false);
			iRating = iRating+1;
		}

		if(iRating === iMax) {
			sIcon = '<i class="fa fa-fw fa-check text-success" data-family-id="'+aFamily.id+'" data-toggle="tooltip"></i>';
		} else if(iRating / iMax > 0.8) {
			sIcon = '<i class="fa fa-fw fa-info text-info" data-family-id="'+aFamily.id+'" data-toggle="tooltip"></i>';
		} else {
			sIcon = '<i class="fa fa-fw fa-times text-danger" data-family-id="'+aFamily.id+'" data-toggle="tooltip"></i>';
		}

		this.aTooltips['assignment-tooltip-'+aFamily.id] = sDescription;

		aReturn['rating'] = iRating;
		aReturn['string'] = sIcon;

		return aReturn;
	},
	
	getMonthCells : function(){
		
			
		var iMonthCount = [];
		var sLastMonth = "";
	
		if(this.displayData && this.displayData.days){

			this.displayData.days.each(function(aDay){

				if(iMonthCount[aDay.year] == undefined){
					iMonthCount[aDay.year] = [];
				}

				if(iMonthCount[aDay.year][aDay.month] == undefined){
					iMonthCount[aDay.year][aDay.month] = 0;
				}

				iMonthCount[aDay.year][aDay.month] = iMonthCount[aDay.year][aDay.month]+1;
			});
			
			this.displayData.days.each(function(aDay){
				
				if(aDay.month != sLastMonth){
					var iCountMonthColspan = iMonthCount[aDay.year][aDay.month] * 2 ;
					var iWidth = (iCountMonthColspan * 10) - 7;
					if(!this.newHeadLine_top){
						this.newHeadLine_top = '';
					}
					this.newHeadLine_top += '<th style="text-align:center;" colspan="'+iCountMonthColspan+'"><div style="overflow:hidden; width:'+iWidth+'px">'+aDay.month+'</div></th>';
					
				}
				sLastMonth = aDay.month;
			}.bind(this));
		
			return true;
		}
			
	},
	
	
	confirmMoveAllocation : function(element, idRoom){

		var id = element.id;
		var aId = id.split('_');
		var iAllocation = aId[1];

		var iOverviewstatus = 0;
		if(this.bOverview == true) {
			iOverviewstatus = 1;
		}
			
		var bOtherMatching = 0;

		if(this.matchingView != 'matching_hostfamily'){
			bOtherMatching = 1;
		}
	
		// Bestätigung ob wirklich verschhoben werden darf
		var strParameters = '';
		strParameters += '&task=confirmMoveAllocation';
		strParameters += '&iNewRoom='+idRoom;			
		strParameters += '&idAllocation='+iAllocation;
		strParameters += '&bOverview='+iOverviewstatus;
		strParameters += '&other_matching='+bOtherMatching;
		
		this.request(strParameters);
	},
	
	moveAllocation : function(idAllocation, idRoom){
		
		var iInquiry = 0;
		var iFamily = 0;
		var iShareWithId = 0;
		var iAccommodationId = 0;
		
		this.saveAllocation(iInquiry, iFamily, idRoom, 0, idAllocation, iShareWithId, 1, iAccommodationId);
	},
	
	/*
	 * Liefert die akuell gewählte Inquiry Accommodation ID der GUI
	 */
	getInquiryAccommodationId :  function(){

		var aInquiryAccommodations = this.selectedRowId;
		var iInquiryAccommodation = 0;
		
		
		if(
			!aInquiryAccommodations &&
			this.idAccommodation > 0
		){
			// Die ge'cachte' benutzen
			return this.idAccommodation;
		}else{
			// es kann nur eine gewählt werden
			if(
				aInquiryAccommodations != null &&
				aInquiryAccommodations.length > 0
			){
				aInquiryAccommodations.each(function(sValue, iIndex){
					iInquiryAccommodation = sValue;	

				});
			} 
			
			return iInquiryAccommodation;
		}
		
		
		
	},
	
	saveAllocation : function(iInquiry, iFamily, idRoom, iBed, iAllocation, iShareWithId, bMove, iAccommodationId, iFrom, iTo){

		if(!idRoom){
			idRoom = 0;
		}

		if(!iBed) {
			iBed = 0;
		}
		
		if(!iAllocation){
			iAllocation = 0;
		}
		
		if(!iShareWithId){
			iShareWithId = 0;
		}

		var iOverviewstatus = 0;
		if(this.bOverview == true) {
			iOverviewstatus = 1;
		}
			
		var iInquiryAccommodation = this.getInquiryAccommodationId();



		if(
			iInquiryAccommodation > 0 ||
			this.bOverview == true	// in der Overview auch speichern
		){
			
			var strParameters = '';

			if(bMove == 1){
				strParameters += '&task=moveAllocation';
				strParameters += '&iNewRoom='+idRoom;		
			} else {
				strParameters += '&task=saveAllocation';
				strParameters += '&idInvoice='+iInquiry;
				strParameters += '&idFamily='+iFamily;
				strParameters += '&idRoom='+idRoom;
				strParameters += '&iBed='+iBed;
				strParameters += '&idShareWith='+iShareWithId;
			}
			
			strParameters += '&idAllocation='+iAllocation;		
			strParameters += '&idAccommodation='+iInquiryAccommodation;
			strParameters += '&bOverview=' + iOverviewstatus;
			strParameters += '&ignore_matching_requirements=' + ($('matching_requirement_checkbox').checked ? 1 : 0);
			//strParameters += '&matching_show_optional_beds=' + ($('matching_show_optional_beds').checked ? 1 : 0);

			if(iFrom && iTo){
				strParameters += '&iFrom='+iFrom+'&iTo='+iTo;
			}

			var bOtherMatching = 0;

			if(this.matchingView != 'matching_hostfamily'){
				bOtherMatching = 1;
			}

			strParameters += '&other_matching='+bOtherMatching;

			this.request(strParameters);
		}

	},
	
	saveAllocationConfirm: function(iInquiry, iFamily, idRoom, iBed, iAllocation, iShareWithId, bMove, iAccommodationId, iFrom, iTo){
		
		var bCheck = confirm(this.getTranslation('allocation_confirm_wrong_roomtype'));
		
		if(bCheck) {
			this.saveAllocation(iInquiry, iFamily, idRoom, iBed, iAllocation, iShareWithId, bMove, iAccommodationId, iFrom, iTo);
		}
		
	},
	
	saveReservation: function(iFamily, idRoom, iBed, iFrom, iTo){

		var sParam = '';
		sParam += '&task=request';
		sParam += '&action=ReservationDialog';
		sParam += '&accommodation_provider_id='+iFamily;
		sParam += '&room_id='+idRoom;
		sParam += '&bed='+iBed;
		sParam += '&from='+iFrom;
		sParam += '&to='+iTo;

		sParam += this.getFilterparam(this.sHash);

		sParam += '&view='+this.matchingView;	
				
		this.request(sParam);
	},
	
	deleteAllocation : function(iAllocation,iInvoice){
		
		if(confirm(this.getTranslation('delete_question'))){
	
			var iOverviewstatus = 0;
			if(this.bOverview == true) {
				iOverviewstatus = 1;
			}

			var strParameters = '';
			strParameters += '&task=deleteAllocation';
			strParameters += '&idAllocation='+iAllocation;
			strParameters += '&idInvoice='+iInvoice;
			strParameters += '&bOverview=' + iOverviewstatus;

			this.request(strParameters);

		}
	},
	
	/*
	 * Wochentagszellen
	 */
	getWeekdayCells : function(iFrom, iTo, iFromOld, iToOld, bHighlight){
	
		if(bHighlight == null) {
			bHighlight = true;
		}
		
		var iCount = 0;

		if(this.displayData && this.displayData.days){

			this.displayData.days.each(function(aDay){
				
				var sClass = '';

				if(
					bHighlight && 
					(iFromOld) <= aDay.timestamp && 
					(iToOld) >= aDay.timestamp
				) {
					sClass = " selected";
				}
				
				if(aDay.weekday == 1){
					this.newHeadLine += '<th colspan="2" class="thDay'+sClass+'">'+this.getTranslation('monday')+'<br/>'+aDay.day+'</th>';
				} else if(aDay.weekday == 2) {
					this.newHeadLine += '<th colspan="2" class="thDay'+sClass+'">'+this.getTranslation('thuesday')+'<br/>'+aDay.day+'</th>';
				} else if(aDay.weekday == 3) {
					this.newHeadLine += '<th colspan="2" class="thDay'+sClass+'">'+this.getTranslation('wednesday')+'<br/>'+aDay.day+'</th>';
				} else if(aDay.weekday == 4) {
					this.newHeadLine += '<th colspan="2" class="thDay'+sClass+'">'+this.getTranslation('thursday')+'<br/>'+aDay.day+'</th>';
				} else if(aDay.weekday == 5) {
					this.newHeadLine += '<th colspan="2" class="thDay'+sClass+'">'+this.getTranslation('friday')+'<br/>'+aDay.day+'</th>';
				} else if(aDay.weekday == 6) {
					this.newHeadLine += '<th colspan="2" class="thDay thWeekend'+sClass+'">'+this.getTranslation('saturday')+'<br/>'+aDay.day+'</th>';
				} else if(aDay.weekday == 0) {
					this.newHeadLine += '<th colspan="2" class="thDay thWeekend'+sClass+'">'+this.getTranslation('sunday')+'<br/>'+aDay.day+'</th>';
				}
		
				iCount++;
			}.bind(this));

}
		return iCount;
	},
	
	/*
	 * Assignement Div
	 */
	getAssignments : function(aInquiry){
		
		var aDivs = [];

		if(aInquiry === null) {
			return aDivs;
		}

		// Wenn es inaktive zuordnungen gibt
		if(
			aInquiry['inactive_allocations'] &&
			aInquiry['inactive_allocations'].length > 0
		) {

			aInquiry['inactive_allocations'].each(function(aAlloInactiv, iIndex){

				var aDiv			= {};
				aDiv['from']		= parseInt(aAlloInactiv['from']);
				aDiv['to']			= parseInt(aAlloInactiv['to']);
				aDiv['day_from']	= aAlloInactiv['day_from'];
				aDiv['day_until']	= aAlloInactiv['day_until'];

				aDivs.push(aDiv);

			});

		} else {

			var aDiv			= {};
			aDiv['from']		= parseInt(aInquiry['acc_time_from']);
			aDiv['to']			= parseInt(aInquiry['acc_time_to']);
			aDiv['day_from']	= aInquiry['acc_day_from'];
			aDiv['day_until']	= aInquiry['acc_day_until'];

			aDivs.push(aDiv);

		}

		return aDivs;
	},
	
	/*
	 * write special Div 
	 */
	writeSpecialDiv : function(aFromDay, aUntilDay, sType, sTooltip, sCategory){
		
		var oDiv = this.oDivDummy.cloneNode();
		
		if(
			this.displayData.day_from.timestamp > aUntilDay.timestamp ||
			this.displayData.day_until.timestamp < aFromDay.timestamp
		) {
			return oDiv;
		}

		var sStyle = '';

		sStyle += this.getDivPositionStyle(aFromDay, aUntilDay, false);

		var sDivId = '';
		
		var sTitleBlocking = '';

		// Tooltip für Div
		if(sTooltip != '') {
			this.matchingBlockCounter++;
			sDivId = sType + '_' + this.matchingBlockCounter;				
			this.aTooltips[sDivId + '_tooltip'] = sTooltip;
		}

		oDiv.id = sDivId;
		oDiv.className = sType;
		oDiv.setAttribute('style', sStyle);

		if(sCategory) {
			sTitleBlocking = sCategory;
		}else{
			sTitleBlocking = this.getTranslation('not_available');
		}
		
		oDiv.innerHTML = sTitleBlocking;

		return oDiv;

	},
	
	/*
	 * get Position Style
	 */
	getDivPositionStyle : function(aFromDay, aUntilDay, bFullDays){
		
		
		var iDaysOffset = 0;
		var sStyle = '';
		var iDays = 0;

		var aDisplayDays		= this.displayData.days;
		var aDisplayFirstDay	= this.displayData.day_from;
		var aDisplayLastDay		= this.displayData.day_until;
	
		// Zuordnung am Anfang der Ansicht positionieren falls es vorher anfängt
		if(aFromDay.timestamp < aDisplayFirstDay.timestamp) {
			aFromDay = aDisplayFirstDay;
			iDays += 0.5;
		} else if(!bFullDays) {
			iDaysOffset += 0.5;
        // Wenn ganze Tage ( blockierung ) dann kein Offset von einem Halben Tag ergänzen sondern dafür 1 Tag hinzufügen ( halber tag vone / halber tag hinten , kein Offset )
		} else if(bFullDays) {
			iDays += 1;
        }

		// Zuordnung am Ende der Ansicht abschneiden falls zu lang
		if(aUntilDay.timestamp > aDisplayLastDay.timestamp) {
			aUntilDay = aDisplayLastDay;
            iDays -= 0.5;
		}
		
		aDisplayDays.each(function(aDay){
			// Solange nicht der Von Tag
			// muss das offset erhöht werden
			if(
				aDay.timestamp < aFromDay.timestamp
			){
				iDaysOffset++;
			}
			
			if(
				aDay.timestamp >= aFromDay.timestamp &&
				aDay.timestamp < aUntilDay.timestamp
			){
				iDays++;
			} 
		});
 

		var iOffset = iDaysOffset * this.iHalfDayWidth * 2;

		sStyle += 'left: '+iOffset+'px;';

		var iItemWidth = iDays * 2 * this.iHalfDayWidth;
		// Padding und Border abziehen
		iItemWidth = iItemWidth - 1;
		sStyle += 'width: '+iItemWidth+'px;';

		return sStyle;
	},
	
	getCustomerName : function(aCustomer){
		var sCustomerName = aCustomer['lastname']+", "+aCustomer['firstname'].substr(0,1)+".";
		return sCustomerName;
	},

	/*
	 * write Allocation Div
	 */
	writeAllocationDiv : function(aAllo, aFamily, aRoom, aInquiry, bIsOverview){

		var oDiv = this.oDivDummy.cloneNode();
		oDiv.id = 'allocation_'+aAllo['id'];

		// Sicherheitsabfrage nötig da bei Totnes aAllo == false übergeben wird?! mf 14.07.10
		if( !aAllo['inquiry']){
			return oDiv;
		}

		var sStyle = '';
		var sClass = '';

		// Wenn es Kundendaten gibt
		if(aAllo['customer']) {

			// male
			if(aAllo['customer']['gender'] == 1) {
				sClass = "male";
			// female
			} else if(aAllo['customer']['gender'] == 2) {
				sClass = "female";
			} else if(aAllo['customer']['gender'] == 3) {
				sClass = "diverse";
			}

		}

		// Wenn es Buchungsdaten gibt
		if(aAllo['inquiry']){
			// Wenn das Zimmer mit jemand geteilt werden soll
			if(aAllo['share_ids']) {
				var aIds = aAllo['share_ids'].split(/,/);

				aIds.each(function(iId) {
					this.aInquiryCache[aRoom['id']].push(iId);
					this.aSharingCache[aRoom['id']][iId] = [aAllo['id'], aAllo['inquiry']['id']];
				}.bind(this));
			}
		}

		var oAdditional = null;
		// Hintergrundfarbe falls es die Aktuell ausgewählte Buchung ist oder die Übersicht angezeigt wird
		if(
			aInquiry &&
			aAllo['inquiry']['id'] == aInquiry['id'] && 
			bIsOverview == 0
		) {

			// Hintergrund nur ändern wenn nicht in Übersicht
			if(!bIsOverview) {
				sClass = "selected";
			}

			oAdditional = this.oIDummy.cloneNode();
			oAdditional.classList.add('ui-icon', 'ui-icon-scissors');
			oAdditional.title = this.getTranslation('cut_allocation');
			if(aAllo['allocation_from_other_school']) {
				oAdditional.setAttribute('style', 'cursor: auto; ');
			} else {
				oAdditional.setAttribute('style', 'cursor: pointer;');
				$(oAdditional).observe('click', function(event) {
					this.cutAllocation(aAllo['id'], aAllo['from']);
				}.bind(this));
			}

			this.sGotoAllocation = oDiv.id;

		}

		// Hintergrundfarbe falls die Zuweisung aus einer anderen Schule kommt oder es überschneidungen gibt					
		if(aAllo.reservation !== null) {
			sStyle = 'background-color: #FFB900;'; // siehe Ext_Thebing_Util::getColor('substitute_part')
		} else if(aAllo['allocation_from_other_school']) {
			sStyle = 'background-color: #E0E0EB;'; // siehe Ext_Thebing_Util::getColor('matching_other_school')
		} else if(aAllo['warning']) {
			sStyle = "background-color: red;";
		}

		sStyle += this.getDivPositionStyle(aAllo['day_from'], aAllo['day_until']);

		oDiv.className = 'allocation '+sClass;
		if (aAllo['allocation_tooltip']) {
			oDiv.title = aAllo['allocation_tooltip'];
		} else {
			oDiv.title = aAllo['allocation_label'];
		}
		oDiv.setAttribute('style', sStyle);

		if(aAllo.bullet_status) {
			var oSpan = this.oSpanDummy.cloneNode();
			oSpan.title = aAllo.bullet_status.title;
			oSpan.className = 'bullet_icon';
			oSpan.style.background = aAllo.bullet_status.background;
			oDiv.appendChild(oSpan);
		}

		var oDelete = this.oIDummy.cloneNode();
		oDelete.classList.add('ui-icon', 'ui-icon-trash');
		oDelete.title = this.getTranslation('delete_allocation');
		if(aAllo['allocation_from_other_school']) {
			oDelete.setAttribute('style', 'cursor: auto; ');
		} else {
			oDelete.setAttribute('style', 'cursor: pointer;');
			$(oDelete).observe('click', function(event) {
				this.deleteAllocation(aAllo['id'],aAllo['inquiry']['id']);
			}.bind(this));
		}
		oDiv.appendChild(oDelete);
		
		if(aAllo.inquiry_accommodation_id > 0) {
			var oMove = this.oIDummy.cloneNode();
			oMove.classList.add('ui-icon', 'ui-icon-arrowthick-2-n-s');
			oMove.title = this.getTranslation('move_allocation');
			if(aAllo['allocation_from_other_school']) {
				oMove.setAttribute('style', 'cursor: auto; ');
			} else {
				oMove.setAttribute('style', 'cursor: pointer;');
				$(oMove).observe('click', function(event) {
					this.moveAllocationDialog(aAllo);
				}.bind(this));
			}
			oDiv.appendChild(oMove);
		}
		
		if(oAdditional){
			oDiv.appendChild(oAdditional);
		}

		var oComment = this.oIDummy.cloneNode();
		oComment.classList.add('ui-icon', 'ui-icon-comment');
		oComment.title = this.getTranslation('comment_allocation');
		oComment.setAttribute('style', 'cursor: pointer;');
		$(oComment).observe('click', function(event) {
			this.commentAllocationDialog(aAllo);
		}.bind(this));
		oDiv.appendChild(oComment);
		
		var oDescription = this.oDivDummy.cloneNode();
		oDescription.innerHTML = aAllo['allocation_label'];
		oDiv.appendChild(oDescription);

		return oDiv;
	},

	commentAllocationDialog: function(aAllocation){
		
		var iFamilie = 1;
		if(this.matchingView != 'matching_hostfamily'){
			iFamilie = 0;
		}
	
		var strParameters = '';
		strParameters += '&task=openDialog';
		strParameters += '&action=comment_allocation';
		strParameters += '&iAllocation='+aAllocation['id'];	
		strParameters += '&iFamilie='+iFamilie;	
		
		var sIgnoreCategoryCheckboxId	= 'ignore_category_' + this.hash;
		var oIgnoreCategoryCheckbox	= $(sIgnoreCategoryCheckboxId);
		
		if(oIgnoreCategoryCheckbox && oIgnoreCategoryCheckbox.checked){
			strParameters += '&ignore_category=1';
		}
		
		var sIgnoreRoomtypeCheckboxId	= 'ignore_roomtype_' + this.hash;
		var oIgnoreRoomtypeCheckbox	= $(sIgnoreRoomtypeCheckboxId);
		
		if(oIgnoreRoomtypeCheckbox && oIgnoreRoomtypeCheckbox.checked){
			strParameters += '&ignore_roomtype=1';
		}
		
		this.request(strParameters);
	},
	
	moveAllocationDialog: function(aAllocation){
		
		var iFamilie = 1;
		if(this.matchingView != 'matching_hostfamily'){
			iFamilie = 0;
		}
	
		var strParameters = '';
		strParameters += '&task=openMoveAllocation';
		strParameters += '&iAllocation='+aAllocation['id'];	
		strParameters += '&iFamilie='+iFamilie;	
		
		var sIgnoreCategoryCheckboxId	= 'ignore_category_' + this.hash;
		var oIgnoreCategoryCheckbox	= $(sIgnoreCategoryCheckboxId);
		
		if(oIgnoreCategoryCheckbox && oIgnoreCategoryCheckbox.checked){
			strParameters += '&ignore_category=1';
		}
		
		var sIgnoreRoomtypeCheckboxId	= 'ignore_roomtype_' + this.hash;
		var oIgnoreRoomtypeCheckbox	= $(sIgnoreRoomtypeCheckboxId);
		
		if(oIgnoreRoomtypeCheckbox && oIgnoreRoomtypeCheckbox.checked){
			strParameters += '&ignore_roomtype=1';
		}
		
		this.request(strParameters);
	},
	
	/*
	 * Matching TD schreiben
	 */
	writeMatchingTD : function(aInquiry, aFamily, aRoom, sAssignmentString, bOverview){
	
		var oDiv = this.oDivDummy.cloneNode();

		if(aRoom['id'] == null){
			return oDiv;
		}

		var sHtml = '';
		var aBlocking = null;
		if(aRoom['blocking']){
			aBlocking = aRoom['blocking'];
		}

		var aDivs = this.getAssignments(aInquiry);

		// Write blocking div
		if(
			aBlocking &&
			aBlocking.length > 0
		) {			
			
			aBlocking.each(function(aBlockingItem) {

				var oChild = this.writeSpecialDiv(aBlockingItem['day_from'], aBlockingItem['day_until'], 'blocking', aBlockingItem['comment'], aBlockingItem['category']);
				oDiv.appendChild(oChild);

				// tangiert die blockierung den zeitraum des neuen eintrages?
//				aDivs.each(function(aDiv, iKey){
//					if(
//						aDiv['from'] < aBlockingItem['until_timestamp'] &&
//						aDiv['to'] > aBlockingItem['from_timestamp']
//					) {
//						//aDivs[iKey]['occupied'] = 1;
//					}
//				});

			}.bind(this));

		}

		// Write existing allocations
		if(aRoom['allocation'] != 0) {
			aRoom['allocation'].each(function(aAllo){

				var oChild = this.writeAllocationDiv(aAllo, aFamily, aRoom, aInquiry, bOverview);

				oDiv.appendChild(oChild);
				// tangiert die zuordnung den zeitraum des neuen eintrages?
				aDivs.each(function(aDiv, iKey){

					if(
						aDiv['from'] < aAllo['to'] &&
						aDiv['to'] > aAllo['from']
					) {
						aDivs[iKey]['occupied'] = 1;
						//aRoom['isAssignable'] = 0;
						//return;
					}
				});

			}.bind(this));
		}

		if(!bOverview) {
			// Write allocation div
			if(aRoom['isAssignable'] != 0) {
				var oChild = this.writeAssignmentDiv(aDivs, aInquiry, aFamily, aRoom, sAssignmentString);
				oDiv.appendChild(oChild);
			} else {
				var oChild = this.writeInfoDiv(aDivs, aInquiry, aFamily, aRoom, sAssignmentString);
				oDiv.appendChild(oChild);
			}
		}

		return oDiv;
	},
	
	/*
	 * write Assignement Div
	 */
	writeAssignmentDiv : function(aDivs, aInquiry, aFamily, aRoom, sAssignmentString){
		
		var sStyle = '';
		var oDivs = this.oDivDummy.clone();
		
		var aBlocking = null;
		if(aRoom['blocking']){
			aBlocking = aRoom['blocking'];
		}

		aDivs.each(function(aDiv) {

			if(
//				(
//					typeof(this.aRoomJump[aRoom['id']]) == 'undefined' ||
//					typeof(this.aRoomJump[aRoom['id']][aDiv['from']]) == 'undefined' ||
//					this.aRoomJump[aRoom['id']][aDiv['from']] == 0
//				) &&
				!aDiv['occupied']
			) {
	
				// Hier wird jetzt geguckt ob für den Zeitraum eine Blockierung vorliegt, falls ja
				// darf hier natürlich nicht erneut zugewiesen werden
				var bIsBlocked = false;
//				aBlocking.each(function(aBlockingItem) {
//					
//					if(
//						aDiv['to'] >= aBlockingItem['from_timestamp'] &&
//						aDiv['from'] <= aBlockingItem['until_timestamp']
//					){
//						// Ist blockiert -> Wird nix angezeigt
//						//bIsBlocked = true;
//					}
//			
//				}.bind(this));
		
				if(!bIsBlocked){
					
					sStyle = this.getDivPositionStyle(aDiv['day_from'], aDiv['day_until']);
					
					var oDiv = this.oDivDummy.clone();
					oDiv.setAttribute('style', sStyle);
					oDiv.className = 'assignment';

					var sAllocationTooltip = aFamily['ext_33']+' - '+aRoom['name'];

					if(this.bAvailability) {
						oDiv.innerHTML = sAssignmentString+'<a href="" title="'+sAllocationTooltip+'" onclick="var oGui = aGUI[\''+this.hash+'\']; oGui.saveReservation(\''+aFamily['id']+'\',\''+aRoom['id']+'\',\''+aRoom['bed_number']+'\',\''+aDiv['day_from']['db_date']+'\',\''+aDiv['day_until']['db_date']+'\'); return false;"> '+this.getTranslation('reserve')+' </a>';
					} else {

						var sMethod = 'saveAllocation';

						if(aRoom['wrongType'] == 1) {
							sMethod = 'saveAllocationConfirm';
						}

						oDiv.innerHTML = sAssignmentString+'<a href="" title="'+sAllocationTooltip+'" onclick="var oGui = aGUI[\''+this.hash+'\']; oGui.' + sMethod + '(\''+aInquiry['id']+'\',\''+aFamily['id']+'\',\''+aRoom['id']+'\',\''+aRoom['bed_number']+'\',false,0,false,0,\''+aDiv['day_from']['db_date']+'\',\''+aDiv['day_until']['db_date']+'\'); return false;"> '+this.getTranslation('match')+' </a>';
					}
					oDivs.appendChild(oDiv);
	
//					if(typeof(this.aRoomJump[aRoom['id']]) == 'undefined'){
//						this.aRoomJump[aRoom['id']] = [];
//					}
//					this.aRoomJump[aRoom['id']][aDiv['from']] = 1;
				}	
					

			}

		}.bind(this));

		return oDivs;
	},
	
	writeInfoDiv : function(aDivs, aInquiry, aFamily, aRoom, sAssignmentString){
		
		var sStyle = '';
		var oDivs = this.oDivDummy.clone();
		
		var aBlocking = null;
		if(aRoom['blocking']){
			aBlocking = aRoom['blocking'];
		}

		aDivs.each(function(aDiv) {

			if(!aDiv['occupied']) {

				sStyle = this.getDivPositionStyle(aDiv['day_from'], aDiv['day_until']);

				var oDiv = this.oDivDummy.clone();
				oDiv.setAttribute('style', sStyle);
				oDiv.className = 'info';
				oDiv.title = aRoom.not_assignable_reasons;

				oDiv.innerHTML = '<i class="fa fa-fw fa-times text-danger" data-family-id="'+aFamily.id+'" data-toggle="tooltip"></i><div>'+aRoom.not_assignable_reasons+'</div>';
				oDivs.appendChild(oDiv);

			}

		}.bind(this));

		return oDivs;
	},
	
	/*
	 * setMouseOverTooltip
	 */
	setMouseOverTooltip : function(oElement){
		
		Event.observe(oElement, 'mouseover', function(e){ 
			this.showFamilyInfos(e);
		}.bind(this));
		
		Event.observe(oElement, 'mouseout', function(e){ 
			this.hideFamilyInfos(e);
		}.bind(this));	
				
				
		
		
		//Event.observe(element, 'mouseover', function(element) {this.showFamilyInfos(element, this);});
		//Event.observe(element, 'mouseout', function(element) {this.hideFamilyInfos(element, this);});
	},
	
	showFamilyInfos : function(e){
		this.aTooltips['family_info'] = e.target.down('span').innerHTML;
		this.showTooltip('family_info', e);
	},
	
	hideFamilyInfos : function(e){
		this.hideTooltip('family_info', e);
	},
	
	getEndOfDay : function(iDay){
		
		var oDateTo = new Date(iDay * 1000);
		var iMSec = Date.UTC(oDateTo.getUTCFullYear(), oDateTo.getUTCMonth(), oDateTo.getUTCDate(), 23, 59, 59);
		iDay = iMSec / 1000;

		return iDay;
	},

	/**
	 * Sortiert anhand des Ratings
	 *
	 * @param {array} a
	 * @param {array} b
	 * @returns {number}
	 */
    sortByRating : function(a, b) {

		/* Sofern die Ratings den gleichen Wert haben,
		 * wird zusätzlich nach dem Namen sortiert. */
        if(a.rating == b.rating) {
	        return a.name == b.name ? 0 : a.name < b.name ? -1 : 1;
        } else if(a.rating > b.rating) {
            return -1;
        } else if(a.rating < b.rating) {
            return 1;
        }

    },

	/*
	 * Anzeige der Matchinginfos in der Unteren GUI
	 */
	loadMatchingCallback : function(){
		
		// Block-Counter zurücksetzen
		this.matchingBlockCounter = 0;
			
		var objData 			= this.allMatchingData;
		var objList 			= this.matchingData;
		var aCustomer 			= this.matchingCustomer;
		var aInquiry 			= this.matchingInquiry;
		var sType				= 'family';

		bOverview = false;
		if(this.matchingInquiry == null) {
			bOverview = true;
		}

		if(this.matchingView != 'matching_hostfamily') { 
			sType = 'other';
		}
		
		var iFrom		= 0;
		var iTo			= 0;
		var iFromOld	= 0;
		var iToOld		= 0;

		this.oDivDummy		= new Element('div');
		this.oTrDummy		= new Element('tr');
		this.oTdDummy		= new Element('td');
		this.oThDummy		= new Element('th');
		this.oTableDummy	= new Element('table');
		this.oSpanDummy		= new Element('span');
		this.oImgDummy		= new Element('img');
		this.oIDummy = new Element('i');
		this.oSpanDummy = new Element('span');

		var aDropRooms		= [];

		// Prepare Search
		var oSearchField = $('matching_search_field');

		var sSearch = $F(oSearchField).toLowerCase();
		var aSearch = this.findSearchStrings(sSearch);
		
		var oSearchCheckboxField = $('matching_requirement_checkbox');
		var oSearchOptionalBedsCheckbox = $('matching_show_optional_beds');

		if(bOverview) { 
			
			$('matching_body').update();
			
			// Alle Spalten deselectieren
			// Keine Unterkunft markieren da overview
			this.unselectAllRows();
			
			iFrom = parseInt(objData['iFrom']);
			iTo = parseInt(objData['iTo']);

			iTo = this.getEndOfDay(iTo);

			iFromOld = iFrom;
			iToOld = iTo;
		
		} else {

			iFrom           = parseInt(aInquiry['acc_time_from']);
			iTo             = parseInt(aInquiry['acc_time_to']);
            var iWeekDayFrom= aInquiry['acc_day_from']['weekday'];
            
			iTo = this.getEndOfDay(iTo);	

			iFromOld = iFrom;
			iToOld = iTo;

			// 1 wochen vorher
			iFrom = iFrom - (7 * 24 * 60 * 60);
			// 1 wochen nacher
			iTo = iTo + (7 * 24 * 60 * 60);	
			
		}

		$('matching_body').show();

		var sEmptyTable = '<div id="table_overview-head" style="overflow: hidden; position: relative; width: 0px;">';
		sEmptyTable += '<table id="table_overview-head-table" class="tblMatching borderTop">';
		sEmptyTable += '<thead>';
		sEmptyTable += '<tr id="overview_month"></tr>';
		sEmptyTable += '<tr id="matching_overview_start"></tr>';
		sEmptyTable += '</thead>';
		sEmptyTable += '</table>';
		sEmptyTable += '</div>';
		sEmptyTable += '<div id="table_overview-body" style="overflow: scroll; height: 0px; width: 0px;">';
		sEmptyTable += '<table id="table_overview-body-table" class="tblMatching">';
		sEmptyTable += '<tbody id="tbody_list">';
		sEmptyTable += '</tbody>';
		sEmptyTable += '</table>';
		sEmptyTable += '</div>';
			
		$('matching_body').update(sEmptyTable);

		var iFamilyCount = 0;
		var aTrs = [];
		var i = 0;
		var arrListNew = [];
		var aFamilyStrings = [];
		
		objList.each(function(aFamilyMaster) {
			
			var aFamily = Object.assign({}, aFamilyMaster);

			if (!$j(oSearchOptionalBedsCheckbox).is(':checked')) {
				// Optionale Räume herausfilter, es sei denn sie haben eine Zuweisung
				aFamily.rooms = aFamily.rooms.filter((oRoom) => parseInt(oRoom.optional) === 0 || oRoom.allocation.length > 0)

				if(aFamily.rooms.length === 0) {
					return;
				}
			}

			var aRoomsWithAllocation = [];
			if(
				aInquiry &&
				bOverview == 0
			) {
				aFamily.rooms.each(function(aRoom) {
					if(aRoom['allocation'] != 0) {
						aRoom['allocation'].each(function(aAllo){
							if(aAllo['inquiry']['id'] == aInquiry['id']) {
								aRoomsWithAllocation = aRoom['id'];
							}
						});
					}
				});
			}
			
			// Search
			if(
				oSearchCheckboxField.checked === false &&
				(
					aFamily['requirement_missing'] === "1" ||
					aFamily['requirement_expired'] === "1"
				)
			) {
				return;
			}
			
			if(aSearch.length > 0) {
				
				sFamilyKeywords = aFamily['ext_33']+' '+aFamily['ext_50']+' '+aFamily['ext_63']+' '+aFamily['ext_65']+' '+aFamily['ext_103']+' '+aFamily['ext_104'];

				bFound = this.checkSearchString(sFamilyKeywords, aSearch);

				// Wenn Familie kein Treffer, Räume prüfen
				if(!bFound) {

					var bFoundRoom = false;
					var aFamilyRoomsTmp = aFamily.rooms;
					aFamily.rooms = [];
					aFamilyRoomsTmp.each(function(aRoomData) {
						bFoundRoom = this.checkSearchString(aRoomData.name, aSearch);
						if(
							bFoundRoom ||
							aRoomsWithAllocation.includes(aRoomData.id)
						) {
							aFamily.rooms.push(aRoomData);
						}
					}.bind(this));

					// Wenn auch kein Raum Familie ausblenden
					if(aFamily.rooms.length === 0) {
						return;
					}

				}
				
			}

			iFamilyCount++;

			var iRating = 0;
			var new_family_string = '';

			if(
				sType == 'family' &&
				!bOverview
			) {

				var aReturn = this.prepareFamilyInfo(aFamily, aInquiry);

				iRating = aReturn['rating'];
				new_family_string = aReturn['string'];

			}

			var iKey = aTrs.length;//parseInt(aFamily['id']);
			aTrs[iKey] = [];
			aTrs[iKey]['data'] = aFamily;
			aTrs[iKey]['rating'] = iRating;		
			aTrs[iKey]['name'] = aFamily['ext_33'];

			aFamilyStrings[aFamily['id']] =  new_family_string;

			i++;

		}.bind(this));

		aTrs.sort(this.sortByRating);
	
		var iTemp = 0;

		aTrs.each(function(aTr){
			if(aTr){
				arrListNew[iTemp] = aTr['data'];
				iTemp++;
			}
		});

		if(iFamilyCount == 0){
	
			// Wenn keine Familie gefunden wurde, nochmal schauen ohne den Requirements-Check
			if(!$('matching_requirement_checkbox').checked) {
				$('matching_requirement_checkbox').checked = true;
				this.matchingSearch();
				return;
			}
	
			var oDiv = new Element('div');
			oDiv.style.padding = '20px';
	
			var oGui = aGUI[this.hash];
			var oNotificationDiv = oGui.getNotificationDiv('no_result', 'error', this.getTranslation('general_error'), this.getTranslation('no_result'));
			oDiv.appendChild(oNotificationDiv);
			$('matching_body').update(oDiv);
			oNotificationDiv.show();
			$j(oNotificationDiv).find('.GuiDescription').show();

		} else {

			// Belegungstabelle
			var sLastFamily = ""; 

			var sLastTr = 'matching_overview_start';
			var oTBody = $('tbody_list');

			this.newHeadLine = '<th class="thFamily">';
			if(sType === 'family') {
				this.newHeadLine += this.getTranslation('family');
			} else {
				this.newHeadLine += '&nbsp;';
			}
			this.newHeadLine += '</th>';
			this.newHeadLine += '<th class="thRoom">'+this.getTranslation('room')+'</th>';

			var iCount = 0;

			// Monatsanzeige
			this.newHeadLine_top = '<th class="thFamily"> &nbsp; </th><th class="thRoom"> &nbsp; </th>';

			this.getMonthCells();

			if($('overview_month')) {
				$('overview_month').update(this.newHeadLine_top);
			}

			if(bOverview) {
				iCount = this.getWeekdayCells(iFrom, iTo, iFromOld, iToOld, false);
			} else {
				iCount = this.getWeekdayCells(iFrom, iTo, iFromOld, iToOld, true);
			}

			var iTotalWidth = (280 + (iCount * 2 * this.iHalfDayWidth));

			$('matching_overview_start').update(this.newHeadLine);

			var sColgroup = '';
			sColgroup += '<col style="width: 140px;"></col>';
			sColgroup += '<col style="width: 140px;"></col>';
			for(var i = 0; i < iCount; i++) {
				sColgroup += '<col style="width: '+this.iHalfDayWidth+'px;"></col>';
				sColgroup += '<col style="width: '+this.iHalfDayWidth+'px;"></col>';
			}
			sColgroup = '<colgroup>'+sColgroup+'</colgroup>';

			$('table_overview-head-table').insert({top: sColgroup});	

			sColgroup = '';
			sColgroup += '<col style="width: 140px;"></col>';
			sColgroup += '<col style="width: 140px;"></col>';
			var sColWidth = iCount * 2 * this.iHalfDayWidth;
			sColgroup += '<col style="width: '+sColWidth+'px;"></col>';
			sColgroup = '<colgroup>'+sColgroup+'</colgroup>';

			$('table_overview-body-table').insert({top: sColgroup});	

			// count rows
			var iRows = 0;
			arrListNew.each(function(aFamily) {
				aFamily['rooms'].each(function(aRoom){
					iRows++;
				});
			});

			var bFirstRow = 1;

			var oDiv = this.oDivDummy.cloneNode();
				oDiv.id = 'divLeftContainer';
				
			var oLeftTable = this.oTableDummy.cloneNode();
				oLeftTable.className = 'tableLeftContainer';
				oLeftTable.style.tableLayout = 'fixed';
		
			var iFamily = 0;
			var iLastFamilyId = 0;
			var sRoomRowClass = 'odd';

			arrListNew.each(function(aFamily){

				var aFamilyAllo = [];
//				this.aRoomJump = [];
				var iRoom = 0;
				var iLastRoomId = 0;

				var newTrClass = ' family_result_' + aFamily['id']+' ';

				aFamily['rooms'].each(function(aRoom){

					if(
						typeof(this.aSharingCache[aRoom['id']]) == 'undefined' ||
						typeof(this.aInquiryCache[aRoom['id']]) == 'undefined'
					) {
						this.aSharingCache[aRoom['id']] = {};
						this.aInquiryCache[aRoom['id']] = [];
					}

					var sAssignmentString = aFamilyStrings[aFamily['id']];

					var sRowClass = "";

					aFamilyAllo[aFamily['ext_33']] = 0;

					var sNewTrId = "matching_result_"+aRoom['id']+"_"+aRoom['bed_number'];
					var sFamilyTrId = "matching_label_"+aRoom['id']+"_"+aRoom['bed_number'];
					
					sRowClass += newTrClass;

					if (iLastRoomId > 0 && aRoom['id'] !== iLastRoomId) {
						sRowClass += ' room_separator';

						if (sRoomRowClass === 'even') {
							sRoomRowClass = 'odd';
						} else {
							sRoomRowClass = 'even';
						}
					}

					sRowClass += ' '+sRoomRowClass;

					//
					// Familien Daten
					//
					var sFamilyAddonInfos = '';
					var oFamilieTR = this.oTrDummy.cloneNode();
					oFamilieTR.id = sFamilyTrId;
					var oFamilyTD = this.oTdDummy.cloneNode();
						oFamilyTD.className = 'tdFamily';

					oFamilyTD.setAttribute('data-provider-id', aFamily['id']);
					
					if(sLastFamily != aFamily['id']) {
						
						sFamilyAddonInfos += ' '+this.getTranslation('name')+': '+aFamily['ext_33']+'<br>';
						
						if(aFamily['ext_103'] || aFamily['ext_104']) {
							sFamilyAddonInfos += ' '+this.getTranslation('contact_person')+': '+aFamily['ext_104']+', '+aFamily['ext_103']+'<br>';
						}
						
						var oAddFamilyAddonInfo = function(sField, sTranslation) {
							if(aFamily[sField]) {
								sFamilyAddonInfos += ' '+this.getTranslation(sTranslation)+': '+aFamily[sField]+' <br>';
							}
						}.bind(this);
						
						oAddFamilyAddonInfo('ext_63', 'address');
						oAddFamilyAddonInfo('ext_64', 'zip');
						oAddFamilyAddonInfo('ext_65', 'city');
						oAddFamilyAddonInfo('ext_66', 'country');
						oAddFamilyAddonInfo('ext_67', 'phone');
						oAddFamilyAddonInfo('ext_76', 'phone2');
						oAddFamilyAddonInfo('ext_77', 'mobile');
						oAddFamilyAddonInfo('ext_78', 'skype');
						oAddFamilyAddonInfo('ext_34', 'comment');

						if(
							aFamily['requirement_missing'] === "1" ||
							aFamily['requirement_expired'] === "1"
						) {
							sFamilyAddonInfos += '<span style="color: red; font-weight: bold;">' + this.getTranslation('requirement_invalid') + '<span/>';
						}

						var oDiv = this.oDivDummy.cloneNode();
						oDiv.id = 'family_info_'+aFamily['id'];
						
						var oSpan = this.oSpanDummy.cloneNode();
						oSpan.className = 'tooltipped';
						oSpan.innerHTML = aFamily['ext_33'];

						if(
							aFamily['requirement_missing'] === "1" ||
							aFamily['requirement_expired'] === "1"
						) {

							oSpan.style.color = "red";
						}
						
						var oSpan2 = this.oSpanDummy.cloneNode();
						oSpan2.className = 'spanFamilyAddonTooltipContent';
						oSpan2.innerHTML = sFamilyAddonInfos;
						
						oSpan.appendChild(oSpan2);
						oDiv.appendChild(oSpan);
						oFamilyTD.appendChild(oDiv);

						if (sLastFamily !== "") {
							sRowClass += ' provider_separator';
						}

					} else if(sLastFamily == aFamily['id']){

						oFamilyTD.className = 'tdFamily';

					}
					
					if(
						aFamily['requirement_missing'] === "1" ||
						aFamily['requirement_expired'] === "1"
					) {
						sRowClass += ' requirement_invalid';
					} else {
						sRowClass += ' requirement_valid';
					}
					
					sLastFamily = aFamily['id'];
					var wrongStyle = "";
					if(parseInt(aRoom['wrongType']) == 1) {
						wrongStyle = "background-color:#FF9999;";
					} 

					// Spalte mit Familyn/Raumnamen		
					// Wenn es eine Belegung für den Raum gibt
					if(aRoom['allocation'] != 0){
						// Durchlaufe alle
						aRoom['allocation'].each(function(aAllo) {

							// Wenn es im zeitraum liegt setzte den Flag
							if(
								(aAllo['to'] <= iFromOld ) ||
								(aAllo['from'] >= iToOld)
							){

							} else {
								aFamilyAllo[aFamily['ext_33']] = 1;
							}
						});	
					}

					// Familien TR für linken bereich
					oFamilieTR.className = 'leftTableContainerRow family_left_'+aFamily['id']+' '+sRowClass;

					var oRoomTd = this.oTdDummy.cloneNode();
					oRoomTd.className = "tdRoom";
					oRoomTd.setAttribute('data-room-id', aRoom['id']);
					
					if(aRoom['cleaning_status'] && aRoom['cleaning_status'] === 'clean') {
						var oRoomSpan = this.oSpanDummy.cloneNode();
						oRoomSpan.className = "green";
						oRoomSpan.innerHTML = '<img src="/admin/ts/accommodation/resources/svg/cleaning.svg" title="">';
						oRoomTd.appendChild(oRoomSpan);
					}
					
					var oRoomDiv = this.oDivDummy.cloneNode();
					oRoomDiv.title = aRoom['name'];
					oRoomDiv.innerHTML = aRoom['name'];

					if(aRoom['bed_comment'] && aRoom['bed_comment'].length > 0) {
						var oInfoIcon = this.oIDummy.cloneNode();
						oInfoIcon.classList.add('fa', 'fa-info-circle');
						oInfoIcon.title = aRoom['bed_comment'];

						oRoomDiv.innerHTML += ' ';
						oRoomDiv.appendChild(oInfoIcon);
					}

					oRoomTd.appendChild(oRoomDiv);

					oFamilieTR.appendChild(oFamilyTD);
					oFamilieTR.appendChild(oRoomTd);
					oLeftTable.appendChild(oFamilieTR);
					
					// Rechtes TR
					var oNewTR			= this.oTrDummy.cloneNode();
					oNewTR.id			= sNewTrId;
					oNewTR.style		= wrongStyle;
					oNewTR.className	= 'matching_results rightTableContainerRow family_right_'+aFamily['id']+' '+sRowClass;

					if(bFirstRow) {
						var oFirstRow = this.oTdDummy.cloneNode();
							oFirstRow.className = 'tdLeftContainer';
							oFirstRow.id = 'matching_left_container';
							oFirstRow.colSpan = 2;
							oFirstRow.rowSpan = iRows;
						oNewTR.appendChild(oFirstRow);
						bFirstRow = 0;
					}

					var oSecondTd = this.oTdDummy.cloneNode();
					oSecondTd.className = 'noPadding';
						
					var oItemDiv = this.oDivDummy.cloneNode();
					oItemDiv.className = 'item_container';
						
					oSecondTd.appendChild(oItemDiv);
					oNewTR.appendChild(oSecondTd);
		
					// male Matching TDs
					var oChild = this.writeMatchingTD(aInquiry, aFamily, aRoom, sAssignmentString, bOverview);
					oItemDiv.appendChild(oChild);
					oTBody.appendChild(oNewTR);

					sLastTr = sNewTrId;			
				
					aDropRooms[aDropRooms.length] = aRoom['id'];

					// Zusammenreisende markieren
					// Jede in diesem Raum zugeordnete Buchung durchlaufen und checken, ob es einen Partner gibt.
					this.aInquiryCache[aRoom['id']].each(function(iInquiryId) {

						this.aInquiryCache[aRoom['id']].each(function(iInnerId) {
							var aCheck = this.aSharingCache[aRoom['id']][iInnerId];
							if(aCheck[1] == iInquiryId) {
								var sAllocationId = 'allocation_'+aCheck[0];
								if($(sAllocationId)) {
									$(sAllocationId).addClassName("shared");
								}
							}
						}.bind(this));

					}.bind(this));

					iRoom++;
					iLastRoomId = aRoom['id'];
					iLastFamilyId = aFamily['id'];

				}.bind(this));
				
				iFamily++;

			}.bind(this));
				
			oDiv.appendChild(oLeftTable);
			
			$('matching_left_container').update('');
			$('matching_left_container').appendChild(oDiv);
			
			$$('.tooltipped').each(function(oElement){
				this.setMouseOverTooltip(oElement);
			}.bind(this));

			// Hintergrund wg. Wochenende auf korrekte Position setzen
			// get first day
            if(!iWeekDayFrom){
                var oDay = new Date(iFrom*1000);
                var iWeekDay = oDay.getUTCDay();
            } else {
                var iWeekDay = iWeekDayFrom;
            }
			
            var iTemp = 2;
            
			var iPos = 280 + ((((iWeekDay - 6) * -1) + iTemp) * 2 * this.iHalfDayWidth);

			$$('#table_overview-body-table tr').each(function(element) {
				$(element).setStyle({
					//backgroundPosition: (280 + ((((iWeekDay - 6) * -1) + 2) * 2 * this.iHalfDayWidth))+'px 0'
					backgroundPosition: iPos+'px 0'
				});
			});

			// Set table width
			// TODO Das wird von checkMatchingListHeight überschrieben
			$('table_overview-head').style.width = iTotalWidth+'px';
			$('table_overview-head-table').style.width = iTotalWidth+'px';
			$('table_overview-body-table').style.width = iTotalWidth+'px';

			Event.observe($('table_overview-body'), 'scroll', this.scrollAddommodationTable.bindAsEventListener('table_overview'), false);
			
			var oTableOverviewBody = $('table_overview-body');
			var aNewAllocations = $$('.allocation');

			oTableOverviewBody.style.overflow = 'scroll';
			oTableOverviewBody.style.position = 'relative';

			// Observer für Blocked Divs
			this.setTooltipObserver('blocking');

			//
			// Dieser Bereich macht es langsam!!
			//
			// DROP und Drag
			//
			/*
			aDropRooms.each(function(iRoom){
				$j('.matching_results').droppable({ 
						drop: function( event, ui ) {
						//	event.target.removeClassName('matching_results_hover');
							
							if(
								(
									event && event.target && event.target.tagName != "IMG"
								) || 
								!event ||
								!event.target
							){

							   // Start/End TR dürfen nicht identisch sein
						
							   if(
									event &&
									event.target &&
									event.target.id && // hier wurde hingeschoben
									event.originalEvent &&
									event.originalEvent.originalEvent &&
									event.originalEvent.originalEvent.originalTarget
								){
									var oElementDrag = event.originalEvent.originalEvent.originalTarget; // Element das verschoben wurde

									this.confirmMoveAllocation(oElementDrag,iRoom);
								}

							}
							
						}.bind(this),
						over: function ( event, ui ) {
						//	event.target.addClassName('matching_results_hover');	
						},
						out: function ( event, ui ) {
						//	event.target.removeClassName('matching_results_hover');
						}
					});
			}.bind(this));

			aNewAllocations.each(function(oElement){

				$j( oElement ).draggable(
					{ 
						appendTo: $j(oTableOverviewBody),
						containment: $j(oTableOverviewBody),
						axis: 'y', 
						scroll: true,
						opacity: 0.35,
						scrollSensitivity: 40,
						scrollSpeed: 10,
						//iframeFix: true,
						snap: '.item_container',
						snapTolerance: 10, 
						snapMode: 'inner',
						zIndex: 1000,
						distance: 5,
						revert: 'invalid'
					}
				);
			
			}.bind(this)); 
*/
		}
			
	
	},
	
	// Setzt die Observer für Tooltips innerhalb der unteren Liste
	setTooltipObserver : function(sType){
	
		$$('#matching_body .'+sType).each(function(oBlockingDiv){
				
			var oBlockingDivId = oBlockingDiv.id;

			var sTooltipKey = oBlockingDivId + '_tooltip';

			Event.stopObserving(oBlockingDiv);

			Event.observe(oBlockingDiv, 'mousemove', function(e) {
				this.showTooltip(sTooltipKey, e);
			}.bind(this));

			Event.observe(oBlockingDiv, 'mouseout', function(e) {
				this.hideTooltip(sTooltipKey, e);
			}.bind(this));

		}.bind(this));

		// Bootstrap Tooltips
		$j('[data-toggle="tooltip"]').each(function(i, oElement) {

			var sTooltipKey;

			Event.stopObserving(oElement);

			Event.observe(oElement, 'mousemove', function(e) {
				sTooltipKey = 'assignment-tooltip-' + $j(e.currentTarget).data('familyId');
				this.showTooltip(sTooltipKey, e);
			}.bind(this));

			Event.observe(oElement, 'mouseout', function(e) {
				sTooltipKey = 'assignment-tooltip-' + $j(e.currentTarget).data('familyId');
				this.hideTooltip(sTooltipKey, e);
			}.bind(this));
			
		}.bind(this));

	},
	
	resizeGuiPage : function ($super, event, oElement) {
		$super(event, oElement);

		this.checkMatchingListHeight();
	},
	
	scrollAddommodationTable : function(sTable){
		$(this + '-head').style.left  = ($(this + '-body').scrollLeft * -1)+'px';
		
		$('divLeftContainer').style.left = ($(this + '-body').scrollLeft - 1)+'px';
	},
	
	// Hook beim Linken Frame ausblenden bzw. resizen
	resizeHook : function(){
		this.checkMatchingListHeight();
		//this.changeMatchingListWidth();
	}, 


	// Tooltip ausrichten
	// adjustTooltip : function(){
	//
	// 	var iWidth = this.getFrameWidth();
	//
	// 	var oDiv = $('legent_tooltip');
	//
	// 	if(
	// 		oDiv
	// 	){
	//
	// 		oDiv.setStyle({
	// 			marginLeft: 0 + 'px'
	// 		});
	//
	// 		$$('.matching_legend').each(function(oDivBar){
	//
	// 			var oBarHtml = oDivBar.down('.divToolbarHtml');
	// 			/*
	// 			oBarHtml.setStyle({
	// 				width: iWidth + 'px'
	// 			});
	// 			*/
	// 			var iWidthTemp = oBarHtml.getWidth();
	//
	// 			var iMargin = iWidth - iWidthTemp - 20;
	//
	// 			oDiv.setStyle({
	// 				marginLeft: iMargin + 'px'
	// 			});
	// 		}.bind(this));
	//
	// 		this.setTooltipMargin = 1;
	// 	}
	// },
	
	// changeMatchingListWidth : function(){
	//
	// 	var oMatchingBody = $('table_overview-body');
	//
	// 	var iWidth = this.getFrameWidth();
	//
	// 	if(oMatchingBody){
	// 		oMatchingBody.setStyle({
	// 				width: iWidth + 'px'
	// 			});
	// 	}
	//
	// 	// Tooltip ausrichten
	// 	// this.adjustTooltip();
	//
	// },
	
	initMatchingList : function(){

		// Matching-Such-Bar einblenden wenn es Einträge gibt
		var oSearchBar = $('matching_bar');
		var oSearchAvailabilityBar = $('matching_bar_availability');
		var oTBody = $('tbody_list');

		if(this.bOverview == true) {
			$('divBody_'+this.hash).hide();
		} else {
			$('divBody_'+this.hash).show();
		}

		if(
			this.bAvailability ||
			oSearchBar &&
			oTBody &&
			oTBody.innerHTML != ''
		){
			oSearchBar.show();
			
			if(this.bAvailability) {
				oSearchAvailabilityBar.show();
				
				// Zeitraum der Datumsfelder einschränken
//				$j('#availability_from').attr('data-period-from', $j('#search_time_from_1_'+this.hash).val());
//				$j('#availability_from').attr('data-period-until', $j('#search_time_until_1_'+this.hash).val());
//				$j('#availability_to').attr('data-period-from', $j('#search_time_from_1_'+this.hash).val());
//				$j('#availability_to').attr('data-period-until', $j('#search_time_until_1_'+this.hash).val());
				
				$j('#availability_from').bootstrapDatePicker('destroy');
				$j('#availability_to').bootstrapDatePicker('destroy');
				
				this.prepareCalendar($('availability_from'));
				this.prepareCalendar($('availability_to'));
				
				$j('#availability_button').unbind('click');
				$j('#availability_button').click(function(oEvent) {
					
					var sParam = '&'+$j('#matching_bar_availability select, #matching_bar_availability input').serialize();
					sParam += '&task=request';
					sParam += '&action=availability';
					sParam += '&check_availability=1';

					sParam += this.getFilterparam(this.sHash);

					this.request(sParam);

				}.bind(this));

				$j('#collapseCriteria').on('shown.bs.collapse', function () {
					$j('#collapseCriteria').height($j('#matching_body').height()+38);
				});

			} else {
				oSearchAvailabilityBar.hide();
			}
			
			
			
			// Observer auf Suchfeld
			var oSearchField = $('matching_search_field');
			if(oSearchField){
				oSearchField.stopObserving();

				Event.observe(oSearchField, 'keyup', function(e){
					this.prepareMatchingSearch();
				}.bind(this));

				var oCheckboxField = $('matching_requirement_checkbox');
				oCheckboxField.stopObserving();
				Event.observe(oCheckboxField, 'change', function(e){
					this.matchingSearch();
				}.bind(this));
				
				var oCheckboxOptionalBedsField = $('matching_show_optional_beds');
				oCheckboxOptionalBedsField.stopObserving();
				Event.observe(oCheckboxOptionalBedsField, 'change', function(e){
					this.matchingSearch();
				}.bind(this));
				
			}

		} else {
			oSearchBar.hide();
			oSearchAvailabilityBar.hide();
		}
	
		this.checkMatchingListHeight();

	},
	
	checkMatchingListHeight : function(){

		var intHeight	= this.getFrameHeight();
		
		var iTopHeight = $('divHeader_'+this.hash).getHeight();
		
		var iScrollBody = $('divBody_'+this.hash).getHeight();

		var iLegend = 0;
		
		$$('.matching_legend').each(function(oBar){
			iLegend += oBar.getHeight()
		}.bind(this));
		
		if(this.bOverview) {
			iScrollBody = 0;
		}

		var iSearchBarHeight = $('guiTableBars_matching_bottom').getHeight();
		
		intHeight = intHeight - iTopHeight - iScrollBody - iLegend - iSearchBarHeight - 48;

		var oTable = $('matching_body');
		
		if(oTable) {
			oTable.style.height = (intHeight) + 'px';
		}

		var iTopWidth = $('divHeader_'+this.hash).getWidth();
		
		var oTable = $('table_overview-body');
		if(oTable) {
			var oTableHeader = $('table_overview-head').getHeight();
			oTable.style.height = (intHeight-oTableHeader)+'px';
			oTable.style.width = iTopWidth+'px';
		}
		
		// Tooltip ausrichten
		// if(	this.setTooltipMargin != 1){
		// 	this.adjustTooltip();
		// }

	},
	
	cutAllocation : function(idAllocation, iFrom){
		
		var oDiv = $('allocation_'+idAllocation);
		var oOldDiv = oDiv;

		var sWidth = oOldDiv.style.width;
		var iWidth = parseInt(sWidth.replace(/px/, ''));
		var sLeft = oOldDiv.style.left;
		var iLeft = parseInt(sLeft.replace(/px/, ''));

		var iDays = (iWidth + 8) / (this.iHalfDayWidth * 2);

		var oContainer = oDiv.up();

		while (oContainer.firstChild) {
			oContainer.firstChild.remove()
		}

		//oDiv.remove();

		for(var i = 0;i < (iDays-1); i++) {

			iFrom += 86400;

			var sId = 'cut_allocation_'+idAllocation+'_daypart_'+i+'_day_'+iFrom;
				
			var leftDay = (iLeft + (i * this.iHalfDayWidth * 2));
				
			if(i == iDays-2) {
				leftDay -= 1;
			}
				
			oContainer.insert({
				bottom: new Element('div', {
					id: sId,
					'class': 'cut',
					'style': 'left:'+leftDay+'px'
				})
			});

			var oNewDiv = $(sId);


			if(
				i < (iDays-2)
			) {
		
				Event.observe(oNewDiv, 'mouseover', function(e){ 
					this.changeCutBorder(e.target);
				}.bind(this));	
				Event.observe(oNewDiv, 'mouseout', function(e){ 
					this.resetCutBorder(e.target);
				}.bind(this));	
				Event.observe(oNewDiv, 'click', function(e){ 
					this.startCutting(e.target);
				}.bind(this));	
			}

		}
	},
	
	changeCutBorder : function(oElement){ 
		oElement.style.borderRight = '2px solid red'; 
	},
	
	resetCutBorder : function(oElement){
		oElement.style.borderRight = ''; 
	},
	
	startCutting : function(oElement){
		
		var aParts = oElement.id.split('_');

		var idAllocation = aParts[2];
		var iDay = parseInt(aParts[4]) + 1;

		var strParameters = '&task=cutAllocation';
		strParameters += '&idAllocation='+idAllocation;
		strParameters += '&iDay='+iDay;

		// Request sperren bis zu einem Fehler
		if(!this._bLockCutRequest) {
			this._bLockCutRequest = true;
			this.request(strParameters);
		}

	},
	
	prepareMatchingSearch : function(){

		if(this.getMatchingSearch) {
			clearTimeout(this.getMatchingSearch);
		}

		this.getMatchingSearch = setTimeout(this.matchingSearch.bind(this), 500);

	},
	
	findSearchStrings: function(sSearch) {
		
		aMatchList = [];

		oRegex = RegExp("[^\\s\"']+|\"([^\"]*)\"|'([^']*)'", 'g');
		oMatches = sSearch.matchAll(oRegex);

		for (const aMatch of oMatches) {
			if(aMatch[2]) {
				aMatchList.push(aMatch[2]);
			} else if(aMatch[1]) {
				aMatchList.push(aMatch[1]);
			} else if(aMatch[0]) {
				aMatchList.push(aMatch[0]);
			}
		} 
		
		return aMatchList;
	},
	
	checkSearchString: function(sValue, aSearch) {
		
		var iCheck = false;
		
		// Alle Suchbegriffe
		if(
			aSearch.length == 0 ||
			(
				aSearch.length == 1 &&
				aSearch[0] == ''
			)
		) {
			iCheck = true;

		} else {
			aSearch.each(function(sSearchKey){

				if(iCheck === false) {

					if(
						iCheck === false &&
						sSearchKey !== '' &&
						sValue
					) {
						sValue = sValue.toLowerCase();

						var iSearchIndex = sValue.indexOf(sSearchKey);
						if(iSearchIndex >= 0){
							iCheck = true;
						}
					}

				}

			}.bind(this));

		}
		
		return iCheck;
	},
	
	matchingSearch : function() {

		this.loadMatchingCallback();
		this.checkMatchingListHeight();

	},
	
	executeFilterSearch: function($super, bLoadBars, sHash, sAdditionalParam) {

		if(this.bOverview == true) {
			this.reloadOverview();
		}

		$super(bLoadBars, sHash, sAdditionalParam);

	},

}); 
