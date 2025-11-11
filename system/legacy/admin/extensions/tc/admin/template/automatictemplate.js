var AutomaticTemplateGui = Class.create(CoreGUI, {

	requestCallbackHook: function($super, oData) {
		$super(oData);

		if(
			oData &&
			oData.data &&
			oData.data.action && (
				oData.action == 'openDialog' ||
				oData.action == 'saveDialogCallback' ||
				oData.action == 'reloadDialogTab'
			) && (
				oData.data.action == 'new' ||
				oData.data.action == 'edit'
			)
		) {

			var oTypeSelect = this.getDialogSaveField('type');
			var oEventType = this.getDialogSaveField('event_type');
			oTypeSelect.change(function() {
				if(
					oData.data.types_with_condition &&
					Object.keys(oData.data.types_with_condition).indexOf(oTypeSelect.val()) === -1
				) {
					$j('.condition_row').hide();
				} else {
					$j('.condition_row').show();

					oEventType.find('option').prop('disabled', true);

					var aTypeEvents = oData.data.types_with_condition[oTypeSelect.val()];
					aTypeEvents.forEach(function(sValue) {
						oEventType.find('option[value=' + sValue + ']').prop('disabled', false);
					});

					// Wenn ausgewählte Option jetzt disabled ist, ist val() === null, daher erste Option setzen
					if(oEventType.val() === null) {
						oEventType.val(aTypeEvents[0]);
					}
				}
			}.bind(this)).change();

		}
	}

});
