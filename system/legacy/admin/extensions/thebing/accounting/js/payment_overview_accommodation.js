var AccommodationPaymentOverview = Class.create(CoreGUI, {

	aRememberProcessed: {},

	createTableBody: function($super, aTableData) {

		$super(aTableData);

		var aData = aTableData.body;

		// Weiterverarbeitung merken, damit beim Löschvorgang darauf geachtet werden kann.

		$(aData).each(function(oData) {
			oData.items.each(function(oItem) {
				if(oItem.db_column === 'processed') {
					if(oItem.text !== '') {
						this.aRememberProcessed[oData.id] = true;
					} else {
						this.aRememberProcessed[oData.id] = false;
					}
				}
			}.bind(this));
		}.bind(this));
	},

	executeDelete: function() {

		var bSuccess = false;
		var bUseExtendedConfirm = false;
		var bNoProcessedDataConfirm = false;


		$(this.selectedRowId).each(function(iId) {
			if(iId !== undefined) {
				if(
					this.aRememberProcessed[iId] &&
					this.aRememberProcessed[iId] === true
				) {
					bUseExtendedConfirm = true;
					return false;
				} else {
					bNoProcessedDataConfirm = true;
				}
			}
		}.bind(this));

		if(
			bUseExtendedConfirm &&
			!bNoProcessedDataConfirm
		) {
			bSuccess = confirm(this.getTranslation('processed_confirm'));
		} else if(
			bUseExtendedConfirm &&
			bNoProcessedDataConfirm
		) {
			bSuccess = confirm(this.getTranslation('extended_processed_confirm'));

		} else if(
			this.multipleSelection == 1 &&
			this.countSelection() > 1
		) {
			bSuccess = confirm(this.getTranslation('delete_all_question'));
		} else {
			bSuccess = confirm(this.getTranslation('delete_question'));
		}

		if(bSuccess) {
			this.request('&task=openDialog&action=delete');
		}

	}

});