
var PaymentConditionGui = Class.create(UtilGui, {

	refreshMultirowEvents: function($super, sDialogId) {
		$super(sDialogId);

		$j('.settings_type').each(function(iIndex, oTypeSelect) {

			oTypeSelect = $j(oTypeSelect);
			var oDueTypeSelect = oTypeSelect.closest('.GUIDialogJoinedObjectContainerRow').find('select[name*=due_type]');
			var oSettingsDepositContainer = oTypeSelect.closest('.GUIDialogRow').next('.settings_deposit_container');

			oTypeSelect.change(function() {

				// Einstellungen für Anzahlung (MultiRows)
				// dependency_visibility funktioniert nicht mit Multi-Rows
				oSettingsDepositContainer[oTypeSelect.val() === 'deposit' ? 'show' : 'hide']();

				// Due Type Options je nach Type
				var aEnabled = [];
				if(oTypeSelect.val() === 'installment') {
					aEnabled = ['begin', 'end', 'start_of_month'];
				} else {
					aEnabled = ['document_date', 'course_start_date'];
					if(oTypeSelect.val() === 'final') {
						aEnabled.push('course_start_date_month_end');
					}
				}

				oDueTypeSelect.children().prop('disabled', true);
				aEnabled.forEach(function(sValue) {
					oDueTypeSelect.children('[value=' + sValue + ']').prop('disabled', false);
				});

				// TODO oDueTypeSelect.val() === null
				if(oDueTypeSelect.children(':selected').prop('disabled')) {
					oDueTypeSelect.children(':enabled:first').prop('selected', true);
				}

				// Funktioniert nicht, weil refreshMultirowEvents() ein Dutzend mal ausgeführt wird
				//oDueTypeSelect.effect('highlight');

			}).change();
		});

		// Kominiertes Typ-Feld aus hidden-Fields auslesen oder setzen
		$j('.settings_deposit_container select[name*=type_combined]').each(function(iIndex, oElement) {
			oElement = $j(oElement);

			var oSetting = oElement.nextAll('input[name*=setting]').first();
			var oType = oElement.nextAll('input[name*=type]').first();
			var oTypeId = oElement.nextAll('input[name*=type_id]').first();

			oElement.val(oSetting.val() + '_' + oType.val() + '_' + oTypeId.val());

			oElement.off('change');
			oElement.change(function() {
				var aSplit = oElement.val().split('_');
				oSetting.val(aSplit[0]);
				oType.val(aSplit[1]);
				oTypeId.val(aSplit[2]);
			});
		});

	}

});
