
var Attendance = Class.create(StudentlistGui, {

	requestCallbackHook: function($super, aData) {

		$super(aData);

		if(aData.action == 'createTable') {

			// Events für die Wochennavigation setzen
			if(aData.data.loadBars == 1) {
				var oLastWeekIcon = $('lastWeek__'+this.hash);
				if(oLastWeekIcon){
					oLastWeekIcon.stopObserving('click');
					oLastWeekIcon.observe('click', this.changeWeekFilter.bind(this,'last'));
				}
				var oNextWeekIcon = $('nextWeek__'+this.hash);
				if(oNextWeekIcon){
					oNextWeekIcon.stopObserving('click');
					oNextWeekIcon.observe('click', this.changeWeekFilter.bind(this,'next'));
				}
				var oCurrentWeekIcon = $('currentWeek__'+this.hash);
				if(oCurrentWeekIcon){
					oCurrentWeekIcon.stopObserving('click');
					oCurrentWeekIcon.observe('click', this.changeWeekFilter.bind(this,'current'));
				}
			}

			// Checkbox für komplette Abwesenheit
			var aAttendanceDayInputs = $j('.attendance_input');
			aAttendanceDayInputs.each(function() {
				var oDiv = $j(this);
				oDiv.find('input[name*=checkbox]').click(function() {
					oDiv.find('.time_input').prop('disabled', this.checked);
				});
			});
			
			var absenceExcusedInputs = $j('.absence-reason-handle');
			absenceExcusedInputs.on('change paste keyup', function() {

				var absenceReasonSelect = $j(this).parents('td').find('.absence-reason-select');
				
				if(absenceReasonSelect.length == 0) {
					return;
				}
				
				if(
					(
						$j(this).is(':checkbox') &&
						!$j(this).is(':checked')
					) ||
					!$j(this).val() ||
					$j(this).val() === '0'
				) {
					absenceReasonSelect.hide();
				} else {
					absenceReasonSelect.show();
				}

			});

			//Selektiere Klasse (gibt es hiefür eine Methode)
			var sOption = $('classes_filter_'+this.hash).value;

			// Klassen Filter 
			if(aData.classes_filter) {
				this.updateSelectOptions($('classes_filter_'+this.hash), aData.classes_filter, false);
			}

			$('classes_filter_'+this.hash).value = sOption;


		} else if(aData.action == 'releaseCallback'){
            this.loadTable(false);
            
            this.displaySuccess('ID_0', aData.message);
        }

	},

	prepareAction : function($super, aElement, aData) {

		if(aElement.task == 'save_inputs') {

			var sParameter = this.getFilterparam();

			sParameter += '&task=save_inputs&';

			sParameter += $j('#guiTableBody_' + this.hash + ' input, #guiTableBody_' + this.hash + ' select').serialize();

			this.request(sParameter);

		} else {
			$super(aElement, aData);
		}

	},

	changeWeekFilter : function(sAction){
		
		var oWeekFilter		= $('week_filter_'+this.hash);
		var iSelectedIndex	= oWeekFilter.selectedIndex;

		if(oWeekFilter){
			if(sAction=='next'){
				iSelectedIndex += 1;
			}else if(sAction=='last'){
				iSelectedIndex -= 1;
			}else{
				var oNow = new Date();

				var iYear = oNow.getFullYear();

				var oDate = new Date(iYear, oNow.getMonth(), oNow.getDate());
				var iDay = oDate.getDay();
				var iTime = oDate.getTime();

				while(iDay != 1) {
					iTime = iTime - (1000 * 60 * 60 * 24);
					oDate = new Date(iTime);
					iDay = oDate.getDay();
				}

				iTime = iTime / 1000;

				var sMonth = (oDate.getMonth()+1);
				if(sMonth < 10) {
					sMonth = '0'+sMonth;
				}
				var sDay = oDate.getDate();
				if(sDay < 10) {
					sDay = '0'+sDay;
				}

				var sDate = (oDate.getYear()+1900)+'-'+sMonth+'-'+sDay;

				for (i = 0; i < oWeekFilter.length; ++i) {
					if (oWeekFilter.options[i].value == sDate) {
						iSelectedIndex = i;
						break;
					}
				}
			}

			oWeekFilter.selectedIndex = iSelectedIndex;
			this.loadTable(false,this.hash);
		}
	}

});