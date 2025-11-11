
var Courses = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {
		$super(aData);

		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			)
			&&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {
			this.togglePerWeekCourseUnits(aData.data);
			this.toggleLessonFields()
			this.toggleAvailabilityTab();
			this.toggleProgramTab();
			this.togglePricesPerPaymentCondition();

			// Bei monatlich Wochen-MS ausblenden, das wird aber auch durch per_unit ausgeblendet
			var oPriceCalculationSelect = this.getDialogSaveField('price_calculation');
			oPriceCalculationSelect.change(function() {
				if(!oPriceCalculationSelect.is(':visible')) {
					return;
				}
				if(
					oPriceCalculationSelect.val() === 'month' ||
					oPriceCalculationSelect.val() === 'fixed'
				) {
					this.listToggle('week_fields2', 'hide');
					this.resetMultiSelect(aData.data, 'weeks');
				} else {
					this.listToggle('week_fields2', 'show');
				}
			}.bind(this)).change();

			var oMinDuration = this.getDialogSaveField('minimum_duration', 'ktc');
			var oMaxDuration = this.getDialogSaveField('maximum_duration', 'ktc');
			var oFixDuration = this.getDialogSaveField('fix_duration', 'ktc');
			Courses.initCourseDurationFields(oMinDuration, oMaxDuration, oFixDuration);

		/*	var iShortNameId = '#'+jQuery('.GUIDialogRowInputDiv input[id*="name_short"]').attr('id');
			iShortNameId = iShortNameId.replace(/\[/g, '\\[');
			iShortNameId = iShortNameId.replace(/]/g, '\\]');
		*/

		}
	},

	// TODO Das sollte man eigentlich alles auf dependency_visibility/child_visibility umstellen können
	togglePerWeekCourseUnits : function (aDialogData) {

		var oPerUnitSelect = this.getDialogSaveField('per_unit', 'ktc');
		var oInputLessons = this.getDialogSaveField('lessons_list', 'ktc');
		var oSelectLessonsUnit = this.getDialogSaveField('lessons_unit', 'ktc');
		var oPriceCalculationSelect = this.getDialogSaveField('price_calculation');

		if(oPerUnitSelect.val() === '0') { // pro Woche/Monat
			this.listToggle('week_fields', 'show');
			this.listToggle('unit_fields', 'hide');
			this.resetMultiSelect(aDialogData, 'units');
			oPriceCalculationSelect.closest('.GUIDialogRow').show();
			$j('#dialog_wrapper_' + aDialogData.id + '_' + this.hash + ' .availability-container').show();
		} else if (oPerUnitSelect.val() === '1') { // pro Lektion
			this.listToggle('week_fields', 'hide');
			this.listToggle('unit_fields', 'show');
			this.resetMultiSelect(aDialogData, 'weeks');
			oPriceCalculationSelect.closest('.GUIDialogRow').hide();
			$j('#dialog_wrapper_' + aDialogData.id + '_' + this.hash + ' .availability-container').show();
		} else if (oPerUnitSelect.val() === '2') { // Prüfung / Probeunterricht
			this.listToggle('week_fields', 'hide');
			this.listToggle('unit_fields', 'hide');
			this.resetMultiSelect(aDialogData, 'weeks');
			this.resetMultiSelect(aDialogData, 'units');
			oPriceCalculationSelect.closest('.GUIDialogRow').hide();
			$j('#dialog_wrapper_' + aDialogData.id + '_' + this.hash + ' .availability-container').show();
		} else if (oPerUnitSelect.val() === '3') { // Kombination
			this.listToggle('week_fields', 'show');
			this.listToggle('unit_fields', 'hide');
			this.resetMultiSelect(aDialogData, 'units');
			oPriceCalculationSelect.closest('.GUIDialogRow').show();
			$j('#dialog_wrapper_' + aDialogData.id + '_' + this.hash + ' .availability-container').show();
		} else if (oPerUnitSelect.val() === '4') { // Anstellung
			this.listToggle('week_fields', 'hide');
			this.listToggle('unit_fields', 'hide');
			this.resetMultiSelect(aDialogData, 'units');
			oPriceCalculationSelect.closest('.GUIDialogRow').show();
			$j('#dialog_wrapper_' + aDialogData.id + '_' + this.hash + ' .availability-container').show();
		} else if (oPerUnitSelect.val() === '5') { // Programm
			this.listToggle('week_fields', 'hide');
			this.listToggle('unit_fields', 'hide');
			this.resetMultiSelect(aDialogData, 'units');
			oPriceCalculationSelect.closest('.GUIDialogRow').show();

			$j('#dialog_wrapper_' + aDialogData.id + '_' + this.hash + ' .availability-container').hide();
			this.toggleAvailabilityTab();
		}

		$j(oInputLessons).tagsinput('destroy');

		// Leertaste + ENTER
		if(oPerUnitSelect.val() === '1') {
			$j(oInputLessons).tagsinput({ confirmKeys: [13, 32] })
			$j(oSelectLessonsUnit).attr('disabled', false)
		} else {
			$j(oInputLessons).tagsinput({ confirmKeys: [13, 32], maxTags: 1 })
			$j(oSelectLessonsUnit).attr('disabled', true)
			$j(oSelectLessonsUnit).val('per_week')
		}

		oPriceCalculationSelect.change();

		this.switchEnabledDisabled(aDialogData);
	},

	switchEnabledDisabled : function (aDialogData) {

		var oPerUnitSelect = this.getDialogSaveField('per_unit', 'ktc');

		var sID				= 'save['+this.hash+']['+aDialogData.id+']';
		var oInputDurationLesson	= $(sID+'[lesson_duration][ktc]');
		var oDivDurationLesson		= oInputDurationLesson.up('.GUIDialogRow');

		var iCourseType = parseInt(oPerUnitSelect.val());

		if ([3, 4, 5].indexOf(iCourseType) !== -1) {
			if(oInputDurationLesson){
				oDivDurationLesson.hide();
			}
		} else {
			if(oInputDurationLesson){
				oDivDurationLesson.show();
			}
		}

		this.changeRequiredFields(sID, oPerUnitSelect);
	},

	changeRequiredFields : function(sID, oPerUnitSelect){

		var bIsCombination = (oPerUnitSelect.val() === '3');

		var oInputLessons	= $(sID+'[lessons_list][ktc]');
		var oInputLessonDuration	= $(sID+'[lesson_duration][ktc]');
		var oInputCombinedCourses	= $(sID+'[combined_courses]');

		if(oInputLessons && oInputLessonDuration && oInputCombinedCourses){
			var oDivLessons	= oInputLessons.up('.GUIDialogRow');
			var oDivLessonDuration	= oInputLessonDuration.up('.GUIDialogRow');
			var oDivCombinedCourses	= oInputCombinedCourses.up('.GUIDialogRow');

			var oDivLabelLessons = oDivLessons.down('.GUIDialogRowLabelDiv');
			if(oDivLabelLessons){
				oDivLabelLessons = oDivLabelLessons.down('div');
			}

			var oDivLabelLessonDuration = oDivLessonDuration.down('.GUIDialogRowLabelDiv');
			if(oDivLabelLessonDuration){
				oDivLabelLessonDuration = oDivLabelLessonDuration.down('div');
			}
			var oDivLabelCombinedCourses = oDivCombinedCourses.down('.GUIDialogRowLabelDiv');
			if(oDivLabelCombinedCourses){
				oDivLabelCombinedCourses = oDivLabelCombinedCourses.down('div');
			}

			// TODO Die GUI sollte required bei ausgeblendeten Feldern (schon lange) bereits ignorieren
			if(oDivLabelLessons && oDivLabelLessonDuration && oDivLabelCombinedCourses){
				if(bIsCombination){
					oInputLessons.removeClassName('required');
					oInputLessonDuration.removeClassName('required');

					if(!oDivLabelCombinedCourses.hasClassName('required')){
						oDivLabelCombinedCourses.addClassName('required');
					}
					if(!oInputCombinedCourses.hasClassName('required')){
						oInputCombinedCourses.addClassName('required');
					}

					oDivLabelLessons.removeClassName('required');
					oDivLabelLessonDuration.removeClassName('required');
					
				}else{
					oInputCombinedCourses.removeClassName('required');
					oDivLabelCombinedCourses.removeClassName('required');

					if(!oDivLabelLessonDuration.hasClassName('required')){
						oDivLabelLessonDuration.addClassName('required');
					}

					if(oPerUnitSelect.val() === '0'){
						if(!oInputLessons.hasClassName('required')){
							oInputLessons.addClassName('required');
						}
						if(!oDivLabelLessons.hasClassName('required')){
							oDivLabelLessons.addClassName('required');
						}
					}else{
						if(oInputLessons.hasClassName('required')){
							oInputLessons.removeClassName('required');
						}
						if(oDivLabelLessons.hasClassName('required')){
							oDivLabelLessons.removeClassName('required');
						}
					}

					if(!oInputLessonDuration.hasClassName('required')){
						oInputLessonDuration.addClassName('required');
					}

				}
			}
		}
	},

	/*confirmSetCombination : function (aDialogData) {

		var oCourseTypeField = $j('save['+this.hash+']['+aDialogData.id+'][per_unit][ktc]');
		var oCheckboxCombination =  $('save['+this.hash+']['+aDialogData.id+'][combination][ktc]');
		var bChoice;

		if(oCourseTypeField.val() == 3){
			bChoice = confirm(this.getTranslation('tuition_courses_combination_switch_on'));
			if(!bChoice){
				oCheckboxCombination.checked = false;
			}
		}else{
			bChoice = confirm(this.getTranslation('tuition_courses_combination_switch_off'));
			if(!bChoice){
				oCheckboxCombination.checked = "checked";
			}
		}
		this.switchEnabledDisabled(aDialogData);
	},*/

	listToggle : function (className, type) {
		var aFields = $$('.'+className);
		var oDiv;
		if(aFields && aFields.length > 0) {
			aFields.each(function(oField) {
				oDiv = oField.up('.GUIDialogRow');
				if(oDiv){
					if( 'show' == type ){
						oDiv.show(oField);
					}else{
						oDiv.hide();
					}
				}
			});
		}
	},

	resetMultiSelect : function (aDialogData, type) {
		var oSelect		= $('save['+this.hash+']['+aDialogData.id+']['+type+'][ktc]');
		var oDiv		= oSelect.up('.GUIDialogRow');
		var oLinkRemove = oDiv.down('a.remove-all');
		this._fireEvent('click', oLinkRemove);
	},

	toggleLessonFields: function () {
		var oPerUnitSelect = this.getDialogSaveField('per_unit', 'ktc');
		var oInputLessonsList = this.getDialogSaveField('lessons_list', 'ktc');
		var oInputLessonsFix = this.getDialogSaveField('lessons_fix', 'ktc');

		$j(oInputLessonsList).on('itemAdded itemRemoved', (e) => {
			if (oPerUnitSelect.val() == 1 && oInputLessonsList.val().length <= 1) {
				$j(oInputLessonsFix).closest('.GUIDialogRow').show()
			} else {
				$j(oInputLessonsFix).closest('.GUIDialogRow').hide()
			}
		}).trigger('itemAdded')

	},

	toggleAvailabilityTab: function() {
		var oAvailabilitySelect = this.getDialogSaveField('avaibility', 'ktc');
		var oPerUnitSelect = this.getDialogSaveField('per_unit', 'ktc');
		oAvailabilitySelect.change(function () {
			var oTab = $j('.GUIDialogTabHeader.availability');
			oTab.hide();
			if (
				oPerUnitSelect.val() !== '5' && (
					oAvailabilitySelect.val() === '1' ||
					oAvailabilitySelect.val() === '3' ||
					oAvailabilitySelect.val() === '4'
				)
			) {
				oTab.show();
			}
		}).change();
	},

	toggleProgramTab: function() {
		var oPerUnitSelect = this.getDialogSaveField('per_unit', 'ktc');
		oPerUnitSelect.change(function () {
			var oTab = $j('.GUIDialogTabHeader.program');
			oTab.hide();
			if (oPerUnitSelect.val() === '5') {
				oTab.show();
			}
		}).change();
	},

	togglePricesPerPaymentCondition: function() {
		var differentPricePerLanguageCheckbox = this.getDialogSaveField(
			'different_price_per_language',
			'ktc'
		);
		var showPricesPerPaymentConditionSelectCheckbox = this.getDialogSaveField('show_prices_per_payment_conditon_select');
		var showPricesPerPaymentConditionSelectRow = showPricesPerPaymentConditionSelectCheckbox.closest('.GUIDialogRow')
		var pricesPerPaymentConditionDiv = $j('[data-row-key*="prices_per_payment_condition"]');

		if (differentPricePerLanguageCheckbox.is(':checked')) {
			showPricesPerPaymentConditionSelectRow.hide();
			showPricesPerPaymentConditionSelectCheckbox.prop('checked', false);
			pricesPerPaymentConditionDiv.hide();
		}

		differentPricePerLanguageCheckbox.change(function () {
			if (differentPricePerLanguageCheckbox.is(':checked')) {
				// Ergänzung zu 'child_visibility' und 'dependency_visibility' in der data
				showPricesPerPaymentConditionSelectRow.hide();
				pricesPerPaymentConditionDiv.hide();
				showPricesPerPaymentConditionSelectCheckbox.prop('checked', false);
			} else {
				showPricesPerPaymentConditionSelectRow.show();
				showPricesPerPaymentConditionSelectCheckbox.show()
			}
		});

		var priceCalculationSelect = this.getDialogSaveField('price_calculation');

		priceCalculationSelect.change(function () {
			if (
				(
					priceCalculationSelect.val() === 'fixed' ||
					priceCalculationSelect.val() === 'month'
				) && !differentPricePerLanguageCheckbox.is(':checked')
			) {
				// Ergänzung zu 'child_visibility' und 'dependency_visibility' in der data
				showPricesPerPaymentConditionSelectCheckbox.show();
				showPricesPerPaymentConditionSelectRow.show();
			} else {
				showPricesPerPaymentConditionSelectCheckbox.hide();
				showPricesPerPaymentConditionSelectRow.hide();
				showPricesPerPaymentConditionSelectCheckbox.prop('checked', false);
			}
		});
	},

});

Courses.initCourseDurationFields = function(oMinField, oMaxField, oFixField) {

	var oMinAndMax = oMinField.add(oMaxField);
	var oAll = oMinAndMax.add(oFixField);

	oAll.on('change keyup', function() {
		if(
			oMinField.val().length > 0 ||
			oMaxField.val().length > 0
		) {
			oMinAndMax.prop('readonly', false).removeClass('readonly');
			oFixField.prop('readonly', true).addClass('readonly').val('');
		} else if(oFixField.val().length > 0) {
			oMinAndMax.prop('readonly', true).addClass('readonly').val('');
			oFixField.prop('readonly', false).removeClass('readonly');
		} else {
			oMinAndMax.prop('readonly', false).removeClass('readonly');
			oFixField.prop('readonly', false).removeClass('readonly');
		}
	});

	oAll.change();

};
