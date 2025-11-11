
var NumberrangeAllocation = Class.create(CoreGUI, {
	
	requestCallbackHook : function ($super, aData){

		$super(aData);
		
		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			) &&
			aData.data.additional == null &&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {
		
			// Beim editieren muss geprüft werden, ob das MS der Agenturen ein-/ausgeblendet werden soll
			if(aData.data.action == 'edit') {
				var aApplicationSelects = this.getApplicationSelects();

				aApplicationSelects.each(function(oSelect){
					this.prepareInboxValues(oSelect);
				}.bind(this));
			}

			// Events setzen
			this.prepareApplicationEvent();
		}
		
	},
	
	refreshJoinedObjectContainerEventsHook : function(sDialogid) {
		// Events neu setzen
		this.prepareApplicationEvent();
	},
	
	prepareApplicationEvent: function() {
		
		var aApplicationSelects = this.getApplicationSelects();
		
		aApplicationSelects.each(function(oSelect){
			
			// Event auf das Feld "Anwendungsfall" setzen:
			oSelect.stopObserving('change');			
			Event.observe(oSelect, 'change', function(){
				this.prepareInboxValues(oSelect);
			}.bind(this));
						
		}.bind(this));
		
	},
	
	prepareInboxValues: function(oApplicationSelect) {
		
		// MS der Inboxen holen
//		var sId = oApplicationSelect.id.replace('applications', 'inboxes');
//		var oInboxSelect = $(sId);
//
//		var aValue = $F(oApplicationSelect);
//		var bHide = false;
//
//		var oDialogRow = oInboxSelect.up('.GUIDialogRow');
//
//		// Wenn "Anfragen" als Anwendungsfall ausgewählt wurde, muss das MS der Agenturen
//		// ausgeblendet werden
//		if(
//            aValue.indexOf('enquiry') >= 0 ||
//            aValue.indexOf('manual_creditnote') >= 0
//        ) {
//			bHide = true;
//		}
//
//		// MS holen
//		var oSelect = oDialogRow.down('.jQm');
//
//		if(bHide == true) {
//			oDialogRow.hide();
//			// Auswahl zurücksetzen
//			$j(oSelect).multiselect('removeAllOptions');
//		} else {
//			oDialogRow.show();
//		}
		
	},
	
	getApplicationSelects: function() {
		var aApplicationSelects = $$('.numberrange_applications');
		return aApplicationSelects;		
	}
	
});

