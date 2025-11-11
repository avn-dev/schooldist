
var CompanyGui = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {

		$super(aData);

		if(
			aData.action === 'openDialog' ||
			aData.action === 'saveDialogCallback' ||
			aData.action === 'update_select_options'
		) {
			var oAllSelectFrom = $('save[' + this.hash + '][' + aData.data.id + '][from_all_accommodations]');
			var oAllSelectTo = $('save[' + this.hash + '][' + aData.data.id + '][to_all_accommodations]');
			if(oAllSelectFrom) {
				this.toggleAccommodationsFrom(oAllSelectFrom, aData.data.id);
				Event.observe(oAllSelectFrom, 'change', function() {
					this.toggleAccommodationsFrom(oAllSelectFrom, aData.data.id);
				}.bind(this));
			}
			if(oAllSelectTo) {
				this.toggleAccommodationsTo(oAllSelectTo, aData.data.id);
				Event.observe(oAllSelectTo, 'change', function() {
					this.toggleAccommodationsTo(oAllSelectTo, aData.data.id);
				}.bind(this));
			}
		}

	},

	toggleAccommodationsFrom: function(oAllSelect, sDialogId) {

		var oFromSelect = $('save[' + this.hash + '][' + sDialogId + '][from_accommodations]');
		if(!oFromSelect) {
			return;
		}
		var oDialogRow = oFromSelect.up('.GUIDialogRow');
		if(!oDialogRow) {
			return;
		}

		if(oAllSelect.value === '1') {
			oDialogRow.hide();
		} else {
			oDialogRow.show();
		}

	},

	toggleAccommodationsTo: function(oAllSelect, sDialogId) {

		var oToSelect = $('save[' + this.hash + '][' + sDialogId + '][to_accommodations]');
		if(!oToSelect) {
			return;
		}
		var oDialogRow = oToSelect.up('.GUIDialogRow');
		if(!oDialogRow) {
			return;
		}

		if(oAllSelect.value === '1') {
			oDialogRow.hide();
		} else {
			oDialogRow.show();
		}

	}

});
