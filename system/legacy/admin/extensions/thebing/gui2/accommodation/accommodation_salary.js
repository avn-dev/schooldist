
// TODO #10620: Wird nicht mehr verwendet und ginge auch besser heutzutage (Festgehalt für Unterkunftsanbieter)
var AccommodationSalaryGui = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {

		if(
			(
				aData.action=='openDialog' ||
				aData.action=='saveDialogCallback'
			) &&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		){
			// INNER GUI KOSTENKATEGORIEN
			var sCategorySelectId = 'save['+this.hash+']['+aData.data.id+'][costcategory_id]';
			var oCategorySelect = $(sCategorySelectId);

			if(oCategorySelect){
				Event.observe(oCategorySelect, 'change', function(e) {
					this.switchSalaryFields(oCategorySelect);
				}.bind(this));

				this.switchSalaryFields(oCategorySelect);
			}
		}
		
	},

	switchSalaryFields: function(oCategorySelect) {

		var oSalaryContainer = $('salary_container_'+this.hash);
		var oSalaryContainer2 = $('salary_container2_'+this.hash);

		var oLabel = oSalaryContainer.down('.GUIDialogRowLabelDiv div');
		var oInput = oSalaryContainer.down('.GUIDialogRowInputDiv input');

		if($F(oCategorySelect) == -1) {
			if(oSalaryContainer){
				oSalaryContainer.show();
			}
			if(oSalaryContainer2){
				oSalaryContainer2.hide();
			}
			if(oInput){
				oInput.addClassName('required');
			}
			if(oLabel){
				oLabel.addClassName('required');
			}
		} else {
			if(oSalaryContainer){
				oSalaryContainer.hide();
			}
			if(oSalaryContainer2){
				oSalaryContainer2.show();
			}
			if(oInput){
				oInput.removeClassName('required');
			}
			if(oLabel){
				oLabel.removeClassName('required');
			}
		}

	}

});


