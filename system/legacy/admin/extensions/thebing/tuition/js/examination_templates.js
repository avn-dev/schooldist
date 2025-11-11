
var ExaminationTemplateGui = Class.create(ATG2, {

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
			$j('#joinedobjectcontainer_terms_fix > div[id^=row]').each(function(iItem, oRow) {
				this.setFixTermsVisiblityEvents(oRow);
			}.bind(this));
		}

	},

	addJoinedObjectContainerHook: function(oRepeat, sBlockId) {
		this.setFixTermsVisiblityEvents(oRepeat);
	},

	setFixTermsVisiblityEvents: function(oRow) {

		if(oRow.id.indexOf('terms_fix') === -1) {
			return;
		}

		var oPeriodSelect = $j(oRow).find('[id*="[period]"]');
		oPeriodSelect.change(function(oRow) {

			var oPeriodSelect = $j(oRow).find('[id*="[period]"]');
			var oPeriodLengthInput = $j(oRow).find('[id*="[period_length]"]');
			var oUnitSelect = $j(oRow).find('[id*="[period_unit]"]');

			if($j(oPeriodSelect).val() === 'one_time') {
				oPeriodLengthInput.hide();
				oUnitSelect.hide();
			} else {
				oPeriodLengthInput.show();
				oUnitSelect.show();
			}

		}.bind(this, oRow));

		oPeriodSelect.change();

	}

});

