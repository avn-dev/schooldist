
var AccommodationWeekGui = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {
		$super(aData);

		var oSelect = $('save['+this.hash+']['+aData.data.id+'][cost_type][kacc]');

		if(oSelect) {
			this.toggleWeekSelect(oSelect, aData.data.id);
			Event.observe(oSelect, 'change', function() {
				this.toggleWeekSelect(oSelect, aData.data.id);
			}.bind(this));
		}

	},

	toggleWeekSelect : function(oSelect, sDialogId) {

		var oRow = $('save['+this.hash+']['+sDialogId+'][cost_weeks]').up('.GUIDialogRow');

		if(oRow) {
			if(
				$F(oSelect) === 'week' ||
				$F(oSelect) === 'periods'
			) {
				oRow.show();
			} else {
				oRow.hide();
			}
		}

	}

});
