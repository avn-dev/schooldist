var RequestCombinationGui = Class.create(RequestConvertGui, {
	
	aData : new Array(),

	openDialog: function($super, oData) {

		// addJoinedObjectContainerHook findet lange vor requestCallbackHook statt, bringt darunter also nichts mehr
		if (oData.course_data) {
			this.course_data = oData.course_data;
		}

		$super(oData);

	},

	requestCallbackHook: function($super, oData) {

		$super(oData);

		if(
			(
				oData.action === 'openDialog' ||
				oData.action === 'saveDialogCallback'
			) && (
				oData.data.action === 'new' ||
				oData.data.action === 'edit'
			)
		) {

			// // Start/Endzeit der Unterkunftskategorien merken
			// if(oData.data.accommodation_times) {
			// 	this.accommodation_times = oData.data.accommodation_times;
			// }
			//
			// if(oData.data.course_data) {
			// 	this.course_data = oData.data.course_data;
			// }

			this.aData = oData.data;

			this.sFieldPrefix = 'save[' + this.hash + '][' + oData.data.id + ']';

			this.observeForRecalculateWeek();
			
			// if(
			// 	oData.data.action === 'new' &&
			// 	oData.action === 'openDialog'
			// ) {
			// 	this.updateCommentFields(oData.data);
			// }

		} else if(oData.action === 'recalculateWeekCallback') {
			this.recalculateWeekCallback(oData);
		}
	},

	// Observer für das Neuberechnen des Enddatums setzen:
	// Wenn man auf das Reload-Icon klickt und wenn man das Startdatum geändert hat
	observeForRecalculateWeek: function() {
		
		$$('.from_field').each(function(oInput){
			
			Event.stopObserving(oInput, 'change');
			Event.stopObserving(oInput, 'keyup');
			
			oInput.observe('keyup', function(oEvent) {
				this.waitForInputEvent('recalculateWeek', oEvent, oInput);
			}.bindAsEventListener(this));
			
			var oInputRow = oInput.up('.GUIDialogRow');
			
			if(oInputRow)
			{
				var oInputRowPrevious = oInputRow.previous('.GUIDialogRow');
				
				if(oInputRowPrevious)
				{
					var oRefresh = oInputRowPrevious.down('.recalculate_enddate');
					
					if(oRefresh)
					{
						Event.stopObserving(oRefresh, 'click');
						
						oRefresh.observe('click', this.recalculateWeek.bind(this, oInput));
					}
				}
			}
		}.bind(this));
	},

	// Woche neu berechnen - Event
	recalculateWeek: function(oElement) {
		
		var sElementId	= oElement.id;
		
		
		var sType		= sElementId.split('[').pop().replace(']', '');

		if(
			sType !== 'courses' &&
			sType !== 'accommodations' &&
			sType !== 'insurances'
		)
		{
			//Datumsberechnung nur bei den 3 Fällen möglich
			return;
		}
		
		//Von-Datum Feld
		var sFromDate	= $F(sElementId);

		//Wochen Feld ermitteln (from => weeks austauschen)
		var sWeeksId	= sElementId.replace('from', 'weeks');
		var oWeek		= $(sWeeksId);
		var iWeeks		= 0;

		
		if(
			oWeek &&
			oWeek.style.display !== 'none'
		)
		{
			iWeeks		= parseInt($F(oWeek));
			
			if(!iWeeks)
			{
				//Wenn 0 oder keine Zahl eingetippt wurde, dann nicht das Enddatum ausrechnen
				return;
			}
		}
		else
		{
			//Wenn Wochenfeld nicht vorhanden, dann nicht das Enddatum ausrechnen
			return;
		}
		
		//TypeId Feld ermitteln (from => 'xxx_id' austauschen)
		var sTypeId;
		if (sType === 'accommodations') {
			sTypeId		= sElementId.replace('from', 'accommodation_id');
		} else {
			sTypeId		= sElementId.replace('from', sType + '_id');
		}

		var oType		= $(sTypeId);
		var iTypeId		= 0;

		if(oType)
		{
			iTypeId		= parseInt($F(oType));
		}

		// Alle von Bis-felder nochmal mitschicken da sie z.B. für die Unterkunftszeiten berechnung
		// auf Basis der Kurszeiten benötigt werden
		var aCoursePeriodTimeFrom				= new Array();
		var aCoursePeriodTimeUntil				= new Array();
		
		var aAccommodations		= new Array();
		var aAccommodationPeriodTimeFrom		= new Array();
		var aAccommodationPeriodTimeUntil		= new Array();
		
		var aCourseFromFields					= this.getDialogFieldsByClass('courseFrom');
		var aCourseUntilFields					= this.getDialogFieldsByClass('courseUntil');
		
		var aAccommodationFields			= this.getDialogFieldsByClass('accommodationCategory');
		var aAccommodationFromFields			= this.getDialogFieldsByClass('accommodationFrom');
		var aAccommodationUntilFields			= this.getDialogFieldsByClass('accommodationUntil');

		aCourseFromFields.each(function(oField) {
			aCoursePeriodTimeFrom[aCoursePeriodTimeFrom.length] = $F(oField);
		}.bind(this));

		aCourseUntilFields.each(function(oField) {
			aCoursePeriodTimeUntil[aCoursePeriodTimeUntil.length] =  $F(oField);
		}.bind(this));

		aAccommodationFields.each(function(oField) {
			var oFromField = $(oField.id.replace('accommodation_id', 'from'));
			if ($F(oField) > 0 && $F(oFromField) === '') {
				aAccommodations[aAccommodations.length] = { field: oField.id, value: $F(oField) };
			}
		}.bind(this));

		aAccommodationFromFields.each(function(oField) {
			aAccommodationPeriodTimeFrom[aAccommodationPeriodTimeFrom.length] = $F(oField);
		}.bind(this));

		aAccommodationUntilFields.each(function(oField) {
			aAccommodationPeriodTimeUntil[aAccommodationPeriodTimeUntil.length] =  $F(oField);
		}.bind(this));
		
		var sCoursesFrom						= JSON.stringify(aCoursePeriodTimeFrom);
		var sCoursesUntil						= JSON.stringify(aCoursePeriodTimeUntil);
		
		var sAccommodations					= JSON.stringify(aAccommodations);
		var sAccommodationFrom					= JSON.stringify(aAccommodationPeriodTimeFrom);
		var sAccommodationUntil					= JSON.stringify(aAccommodationPeriodTimeUntil);
		
		var sParam = '&task=recalculateWeek';
		sParam += '&type=' + sType;
		sParam += '&type_id=' + iTypeId;
		sParam += '&weeks=' + iWeeks;
		sParam += '&from=' + sFromDate; // Datum des aktuellen Feldes
		sParam += '&courses_from=' + sCoursesFrom;
		sParam += '&courses_until=' + sCoursesUntil;
		sParam += '&accommodations=' + sAccommodations;
		sParam += '&accommodation_from=' + sAccommodationFrom;
		sParam += '&accommodation_until=' + sAccommodationUntil;
		sParam += '&element_id=' + sElementId;

		this.request(sParam);
	},

	// Woche neu berechnen - Callback
	recalculateWeekCallback: function(oData) {

		if(
			oData &&
			oData.data
		){
				
			var aData		= oData.data;
			var sUntilId	= aData.until_id;
			var oUntilInput = $j('#' + $j.escapeSelector(sUntilId));
			var sElementId	= aData.element_id;

			// Das gerade ausgefüllte Feld soll sich natürlich nicht automatisch wieder ändern!
			// Ansonsten felder autom. befüllen
			if(
				oUntilInput.length &&
				aData.until &&
				sUntilId !== sElementId
			) {
				this.updateCalendarValue(oUntilInput, aData.until);
			}

			// Nur bei Kurszeitberechnung darf die Unterkunftszeiten berechnet werden
			// TODO 'course' benutzt?
			if(aData.type === 'course' || aData.type === 'courses'){
				// Unterkunftsdaten ergänzen bei NEUER Anfrage
				this.showAccommodationDates(aData);
			}
			
			// Transferzeiten übernehmen
			if(
				aData.type === 'course' ||
				aData.type === 'accommodation'
			){
				// Unterkunftsdaten ergänzen bei NEUER Anfrage
				this.showTransferDates(aData);
			}

		}

	},
	
	// Befüllt die Unterkunftsdaten mit den autom. errechneten aus dem Kurs Tab
	showTransferDates: function(aData){
		
		var sDialogId = this.sCurrentDialogId;
		
		if(
			aData.transfer_data &&
			sDialogId === 'ID_0'
		){
			var aTransferData = aData.transfer_data;

			var aTransferDateArrival	= this.getDialogFieldsByClass('transferDateArrival');
			var aTransferDateDeparture	= this.getDialogFieldsByClass('transferDateDeparture');
			
			aTransferDateArrival.each(function(oField) {
				this.updateCalendarValue(oField, aTransferData.first);
			}.bind(this));
			
			aTransferDateDeparture.each(function(oField) {
				this.updateCalendarValue(oField, aTransferData.last);
			}.bind(this));
			
		}
		
	},
	
	// Befüllt die Unterkunftsdaten mit den autom. errechneten aus dem Kurs Tab
	showAccommodationDates: function(aData){
		
		var sDialogId = this.sCurrentDialogId;

		if(
			aData.accommodation_data &&
			sDialogId === 'ID_0'
		){
			aData.accommodation_data.forEach((aAccommodation) => {
				var oAccommodation = $(aAccommodation.field);
				if (oAccommodation) {
					var oFromField = $(aAccommodation.field.replace('accommodation_id', 'from'));
					var oUntilField = $(aAccommodation.field.replace('accommodation_id', 'until'));
					var oWeekField = $(aAccommodation.field.replace('accommodation_id', 'weeks'));

					if (oFromField) {
						oFromField.value = aAccommodation.dates.first;
					}

					if (oUntilField) {
						oUntilField.value = aAccommodation.dates.last;
					}

					if (oWeekField) {
						oWeekField.value = aAccommodation.dates.weeks_i;
					}
				}
			})
		}
	},
	
	// Liefert alle Dialogfelder die einer CSS Klasse haben
	getDialogFieldsByClass: function(sClass){
		
		var sDialogId	= this.sCurrentDialogId;
				
		var aFields = new Array();
		$$('#dialog_'+sDialogId+'_'+this.hash+' .'+sClass).each(function(oInput){
			aFields[aFields.length] = oInput;
		}.bind(this));
		
		return aFields;	
	},
	
	// Event, das beim Setzen eines Datums ausgeführt wird
	calendarCloseHandler: function($super, oInput, oDate, bForFilter) {
		$super(oInput, oDate, bForFilter);
		this.recalculateWeek(oInput);
	},

	// Erzeugt einen Präfix für den Aufbau der IDs von JoinedObject-Containern
	getFieldPrefix: function(sType, sField) {

		var sContainerPrefix = this.sFieldPrefix + '[' + sField + ']';

		var iSelectedId = 0;

		if(
			this.selectedRowId &&
			this.selectedRowId[0]
		) {
			iSelectedId = this.selectedRowId[0];
		}

		if(sType === 'course') {
			sContainerPrefix += '[ts_ijc][' + iSelectedId + '][course]';
		} else if(sType === 'accommodation') {
			sContainerPrefix += '[ts_ija][' + iSelectedId + '][accommodation]';
		} else if(sType === 'insurance') {
			sContainerPrefix += '[ts_iji][' + iSelectedId + '][insurance]';
		}

		return sContainerPrefix;
	},

	reloadAccommodationPeriod : function (aData, oEvent){
		var oAccommodationCategory = oEvent.target;

		var aCoursePeriodTimeFrom				= new Array();
		var aCoursePeriodTimeUntil				= new Array();

		var aCourseFromFields					= this.getDialogFieldsByClass('courseFrom');
		var aCourseUntilFields					= this.getDialogFieldsByClass('courseUntil');
		aCourseFromFields.each(function(oField) {
			aCoursePeriodTimeFrom[aCoursePeriodTimeFrom.length] = $F(oField);
		}.bind(this));

		aCourseUntilFields.each(function(oField) {
			aCoursePeriodTimeUntil[aCoursePeriodTimeUntil.length] =  $F(oField);
		}.bind(this));

		var sCoursesFrom = JSON.stringify(aCoursePeriodTimeFrom);
		var sCoursesUntil = JSON.stringify(aCoursePeriodTimeUntil);


		var sParam = '&task=getAccommodationPeriod';
		sParam += '&field=' + oAccommodationCategory.id;
		sParam += '&category_id=' + oAccommodationCategory.value;
		sParam += '&course_from=' + sCoursesFrom;
		sParam += '&course_until=' + sCoursesUntil;

		this.request(sParam);

	},

	getAccommodationPeriodCallback: function (oData) {

		console.log(oData)

	},

	// Prüft, ob die Zeit der Unterkunftskategorie übernommen werden kann
	checkTime: function(sTime) {
		
		if(sTime === false) {
			return '';
		}
		
		return sTime;
	},

	/**
	 * Individuelle Events für die wiederholbaren Bereiche
	 */
	refreshJoinedObjectContainerEventsHook: function(sDialogid) {
		this.observeForRecalculateWeek();
	},

	// Kommentarfelder updaten
	/*updateCommentFields: function(aData) {

		if(aData.enquiry_comments) {

			var sCourseComments = aData.enquiry_comments.course.join(', ');
			this.setComment('course_comment', sCourseComments);

			var sAccommodationComments = aData.enquiry_comments.accommodation.join(', ');
			this.setComment('accommodation_comment', sAccommodationComments);

			var sTransferComments = aData.enquiry_comments.transfer.join(', ');
			this.setComment('transfer_comment', sTransferComments);

			// Allgemeiner Kommentar
			this.setComment('general_comment', aData.enquiry_comments.comment);

		}

	},

	setComment: function(sClass, sComment) {
		
		var aFields = $$('.' + sClass);
		
		aFields.each(function(oField){
			oField.value = sComment;
		}.bind(this));
		
	},*/

	addJoinedObjectContainerHook: function(oRepeat, sBlockId) {

		aData = {
			id: this.sCurrentDialogId
		};

		if($j(oRepeat).hasClass('InquiryCourseContainer')) {

			aData.course_data = this.course_data;

			this.setCoursesObserver(aData);
		} else if($j(oRepeat).hasClass('InquiryAccommodationContainer')) {

			this.setAccommodationObserver(aData);
		}

	}

});
