var AgencyPayments = Class.create(StudentlistGui, {

	requestCallbackHook: function($super, aData) {
		

//		var sTask = aData.action;
//
//		if(
//			(
//				sTask == 'saveDialogCallback'
//			)	&&
//			this.sParentGuiHash != ''
//		){
//			//Bei agenturzahlungen muss nach dem speichern eines Payments die Eltern Gui neugeladen werden
//			var oParentGui = this.getOtherGuiObject(this.sParentGuiHash);
//			var aTemp = oParentGui.aChildGuiHash;
//			oParentGui.aChildGuiHash = new Array();
//			oParentGui.loadTable();
//			oParentGui.aChildGuiHash = aTemp;
//		}

		if(
			(
				aData.data.action === 'edit' ||
				aData.data.action === 'new'
			) &&
			(
				aData.action === 'openDialog' ||
				aData.action === 'saveDialogCallback' ||
				aData.action === 'reloadDialogTab'
			)
		) {			
			this.setSchoolSelectEvents(aData);
		}
		
		$super(aData);
	},
			
	setSchoolSelectEvents: function(aData) {		
		var sFieldPrefix = 'save[' + this.hash + '][' + aData.data.id + ']';

		var oSchoolField = $(sFieldPrefix + '[school_id]');
		
		if(oSchoolField) {
			oSchoolField.stopObserving('change');
			Event.observe(oSchoolField, 'change', function(){
				this.reloadDialogTab(aData.dialog_id, 0);				
			}.bind(this));
		}	
	}
	
});


