
var Examination = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {
		var oScore;

		$super(aData);
		
		if(
			aData.action == 'openDialog' ||
			aData.action == 'saveDialogCallback' ||
			aData.action == 'reloadDialogTab'
		) {
			var oSelect = $('save['+this.hash+']['+aData.data.id+'][examination_template_id][kex]');
			if(oSelect){
				this.sDialogId	= aData.data.id;
				this.oSelect	= oSelect;
				oSelect.observe('change', this.loadSections.bind(this));
			}

			var oAutoComplete = $('save['+this.hash+']['+aData.data.id+'][inquiry_id]');
			if(oAutoComplete){
				$j(oAutoComplete).on('change', () => this.reloadDialogTab(aData.data.id, 0));
			}

			oScore = $j('input[id*="[score]"]');

			// Event: Bestanden-Checkbox an-/abwählen
			oScore.on('keyup change', function() {
				var fScore = parseFloat(oScore.val());
				if(
					!isNaN(fScore) &&
					aData.data.school_examination_score_passed > 0
				) {
					// Bestanden-Checkbox automatisch aktivieren
					var bPassed = fScore >= aData.data.school_examination_score_passed;
					$j('input[id*="[passed]"]').prop('checked', bPassed);
				}
			});

			// Event: Durchschnittlichen Score berechnen
			$j('#calculate_score_btn').click(function() {
				this.calculateScore(aData.data);
			}.bind(this));

			// Event: Zeitraum aktualisieren
			var oUntil = $j('input[id*="[until]"]');
			oUntil.parent().next('.divToolbar').click(function() {
				this.refreshFromAndUntil(aData.data);
			}.bind(this));

		} else if(aData.action=='refreshFromAndUntilCallback') {
			this.refreshFromAndUntilCallback(aData.data);
		} else if(aData.action=='setCalculatedScore') {
			oScore = $j('input[id*="[score]"]');
			oScore.val(aData.data.score);
			oScore.change();
		}
	},

	loadSections: function() {
		var iValue = this.oSelect.value;
		if(iValue>0){
			this.reloadDialogTab(this.sDialogId, 0);
		}
	},

	/**
	 * Journey-Course-ID und Course-ID ermitteln
	 */
	getInputCourseIds: function(oData) {
		var iInquiryCourseId, iCourseId, iProgramServiceId;

		var sIdMain = 'save['+this.hash+']['+oData.id+']';
		var oInquiryCourseCourse = $(sIdMain+'[inquiry_course_course][kex]');
		var oInquiryCourse = $(sIdMain+'[inquiry_course_id][kex]');
		var oCourse = $(sIdMain+'[course_id][kex]');

		if(oInquiryCourseCourse) {
			var sValue = oInquiryCourseCourse.value;
			var aInquiryCourseCourse = sValue.split('_');
			iInquiryCourseId = aInquiryCourseCourse[0];
			iCourseId = aInquiryCourseCourse[1];
			iProgramServiceId = aInquiryCourseCourse[2];
		} else{
			if(oInquiryCourse) {
				iInquiryCourseId = oInquiryCourse.value;
			}
			if(oCourse) {
				iCourseId = oCourse.value;
			}
		}

		return [iInquiryCourseId, iCourseId, iProgramServiceId];

	},

	/**
	 * Request zum Kalkulieren des Scores
	 */
	calculateScore: function(oData) {

		var sParam = '&task=calculateScore';
		var aCourseIds = this.getInputCourseIds(oData);

		sParam += '&inquiry_course_id=' + aCourseIds[0];
		sParam += '&course_id=' + aCourseIds[1];
		sParam += '&program_service_id=' + aCourseIds[2];
		sParam += '&from=' + $j('input[id*="[from][kexv]"]').val();
		sParam += '&until=' + $j('input[id*="[until][kexv]"]').val();

		this.request(sParam);
	},

	/**
	 * Request zum Aktualisieren von from und until
	 */
	refreshFromAndUntil: function(oData) {

		var oExaminationDate = $j('input[id*="[examination_date]"]');
		if(oExaminationDate.val()) {
			var aCourseIds = this.getInputCourseIds(oData);
			var sParam = '&task=refreshFromAndUntil';
			sParam += '&id=' + oData.id;
			sParam += '&examination_date=' + oExaminationDate.val();
			sParam += '&journey_course_id=' + aCourseIds[0];

			this.request(sParam);
		}
	},

	/**
	 * Request-Callback vom Aktualisieren von from und until
	 */
	refreshFromAndUntilCallback: function(oData) {
		if(!oData.from) {
			return;
		}

		['from', 'until'].forEach(function(sType) {
			var oElement = $j('input[id*="[' + sType + ']"]');
			oElement.val(oData[sType]);
			oElement.prev('.GUIDialogRowWeekdayDiv').html(oData[sType + '_day']);
		});
	}

});
