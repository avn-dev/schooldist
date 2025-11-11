var Availability = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {
		$super(aData);

		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			) && (
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {
			var oMinDuration = this.getDialogSaveField('minimum_duration');
			var oMaxDuration = this.getDialogSaveField('maximum_duration');
			var oFixDuration = this.getDialogSaveField('fix_duration');
			Courses.initCourseDurationFields(oMinDuration, oMaxDuration, oFixDuration);

			var oDependingOnLevel = this.getDialogSaveField('depending_on_level');
			var oDependingOnCourselanguage = this.getDialogSaveField('depending_on_courselanguage');

			var oLevelsSelect = this.getDialogSaveField('levels');
			oDependingOnLevel.change(function() {
				if(!oDependingOnLevel.prop('checked')) {
					oLevelsSelect.val([]);
					oLevelsSelect.multiselect('reloadOptions');
				}
			});

			var oCourselanguagesSelect = this.getDialogSaveField('courselanguages');
			oDependingOnCourselanguage.change(function() {
				if(!oDependingOnCourselanguage.prop('checked')) {
					oCourselanguagesSelect.val([]);
					oCourselanguagesSelect.multiselect('reloadOptions');
				}
			});

			// Feld existiert in der DB nicht, daher emulieren
			aData.data.values.forEach(function(oValue) {
				if(
					oValue.db_column === 'levels' &&
					oValue.value.length > 0
				) {
					oDependingOnLevel.prop('checked', true);
					oLevelsSelect.closest('.GUIDialogRow').show(); // change funktioniert nicht
				} else if(
					oValue.db_column === 'courselanguages' &&
					oValue.value.length > 0
				) {
					oDependingOnCourselanguage.prop('checked', true);
					oCourselanguagesSelect.closest('.GUIDialogRow').show(); // change funktioniert nicht
				}
			});
		}

	}

});


