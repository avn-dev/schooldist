
var VatGui = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse
		$super(aData);

		if(
			aData.action == 'reloadDialogTab' ||
			aData.action == 'openDialog' ||
			aData.action == 'saveDialogCallback'
		) {
			
		}

	}

});