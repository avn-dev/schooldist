
var MarketingTransferPackagesGui = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {
		$super(aData);

		if(
			aData.action && (
				aData.action === 'openDialog' ||
				aData.action === 'saveDialogCallback' ||
				aData.action === 'update_select_options'
			)
		) {
			var oCheckbox = $('save[' + this.hash + '][' + aData.data.id + '][individually_transfer]');

			if(oCheckbox) {
				this.toggleIndividuallyCheckbox(oCheckbox, aData.data.id);

				Event.observe(oCheckbox, 'change', function() {
					this.toggleIndividuallyCheckbox(oCheckbox, aData.data.id);
				}.bind(this));
			}

			var oCheckboxPrice = $('save[' + this.hash + '][' + aData.data.id + '][price_package]');

			if(oCheckboxPrice) {
				this.togglePriceCheckbox(oCheckboxPrice, aData.data.id);

				Event.observe(oCheckboxPrice, 'change', function() {
					this.togglePriceCheckbox(oCheckboxPrice, aData.data.id);
				}.bind(this));
			}

			var oCheckboxCost = $('save[' + this.hash + '][' + aData.data.id + '][cost_package]');

			if(oCheckboxCost) {
				this.toggleCostsCheckbox(oCheckboxCost, aData.data.id);

				Event.observe(oCheckboxCost, 'change', function() {
					this.toggleCostsCheckbox(oCheckboxCost, aData.data.id);
				}.bind(this));
			}
		}

	},

	togglePriceCheckbox : function(oCheckbox, sDialogId){
		var oRow = $('save['+this.hash+']['+sDialogId+'][join_saisons_prices]');
		if(oRow){
			oRow = oRow.up('.GUIDialogRow');
			if(oRow){
				if(oCheckbox.checked){
					oRow.show();
				} else {
					var oMuliButton = oRow.down('.remove-all');
					if(oMuliButton){
						this._fireEvent('click', oMuliButton);
					}
					oRow.hide();
				}
			}
		}

		var oRow1 = $('save['+this.hash+']['+sDialogId+'][amount_price][ktrp]');
		if(oRow1){
			oRow1 = oRow1.up('.GUIDialogRow');
			if(oRow1){
				if(oCheckbox.checked){
					oRow1.show();
				} else {
					oRow1.hide();
				}
			}
		}
		
		var oRow2 = $('save['+this.hash+']['+sDialogId+'][amount_price_two_way][ktrp]');
		if(oRow2){
			oRow2 = oRow2.up('.GUIDialogRow');
			if(oRow2){
				if(oCheckbox.checked){
					oRow2.show();
				} else {
					oRow2.hide();
				}
			}
		}
	},

	toggleCostsCheckbox : function(oCheckbox, sDialogId){
		var oRow = $('save['+this.hash+']['+sDialogId+'][join_saisons_costs]');
		if(oRow){
			oRow = oRow.up('.GUIDialogRow');
			if(oRow){
				if(oCheckbox.checked){
					oRow.show();
				} else {
					var oMuliButton = oRow.down('.remove-all');
					if(oMuliButton){
						this._fireEvent('click', oMuliButton);
					}
					oRow.hide();
				}
			}
		}

		var oRow1 = $('save['+this.hash+']['+sDialogId+'][amount_cost][ktrp]');
		if(oRow1){
			oRow1 = oRow1.up('.GUIDialogRow');
			if(oRow1){
				if(oCheckbox.checked){
					oRow1.show();
				} else {
					oRow1.hide();
				}
			}
		}
		
		var oRow2 = $('save['+this.hash+']['+sDialogId+'][join_providers_transfer]');
		if(oRow2){
			oRow2 = oRow2.up('.GUIDialogRow');
			if(oRow2){
				if(oCheckbox.checked){
					oRow2.show();
				} else {
					var oMuliButton = oRow.down('.remove-all');
					if(oMuliButton){
						this._fireEvent('click', oMuliButton);
					}
					oRow2.hide();
				}
			}
		}
		
		var oRow3 = $('save['+this.hash+']['+sDialogId+'][join_providers_accommodation]');
		if(oRow3){
			oRow3 = oRow3.up('.GUIDialogRow');
			if(oRow3){
				if(oCheckbox.checked){
					oRow3.show();
				} else {
					var oMuliButton = oRow.down('.remove-all');
					if(oMuliButton){
						this._fireEvent('click', oMuliButton);
					}
					oRow3.hide();
				}
			}
		}



	},

	toggleIndividuallyCheckbox : function(oCheckbox, sDialogId){

		var oRow1 = $('save['+this.hash+']['+sDialogId+'][join_from_accommodation_categories]');

		if(oRow1){
			oRow1 = oRow1.up('.GUIDialogRow');
		}

		var oRow2 = $('save['+this.hash+']['+sDialogId+'][join_from_accommodation_providers]');
		if(oRow2){
			oRow2 = oRow2.up('.GUIDialogRow');
		}

		var oRow3 = $('save['+this.hash+']['+sDialogId+'][join_to_accommodation_categories]');
		if(oRow3){
			oRow3 = oRow3.up('.GUIDialogRow');
		}
		var oRow4 = $('save['+this.hash+']['+sDialogId+'][join_to_accommodation_providers]');
		if(oRow4){
			oRow4 = oRow4.up('.GUIDialogRow');
		}

		var oRow5 = $('save['+this.hash+']['+sDialogId+'][amount_price_two_way]');
		if(oRow5){
			oRow5 = oRow5.up('.GUIDialogRow');
		}

		if(oRow1 && oRow2){
			if(!oCheckbox.checked){
				oRow1.show();
				oRow2.hide();
				var oMuliButton = oRow2.down('.remove-all');
				if(oMuliButton){
					this._fireEvent('click', oMuliButton);
				}
			} else {
				oRow2.show();
				oRow1.hide();
				var oMuliButton = oRow1.down('.remove-all');
				if(oMuliButton){
					this._fireEvent('click', oMuliButton);
				}
			}
		}

		if(oRow3 && oRow4){
			if(!oCheckbox.checked){
				oRow3.show();
				oRow4.hide();
				var oMuliButton = oRow4.down('.remove-all');
				if(oMuliButton){
					this._fireEvent('click', oMuliButton);
				}
			} else {
				oRow4.show();
				oRow3.hide();
				var oMuliButton = oRow3.down('.remove-all');
				if(oMuliButton){
					this._fireEvent('click', oMuliButton);
				}
			}
		}

		if(oRow5){
			if(oCheckbox.checked){
				oRow5.hide();
			} else {
				oRow5.show();
			}
		}

	}
});
