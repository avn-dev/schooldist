
var AgencyGui = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse
		$super(aData);

		var sTask = aData.action;
		var sAction;
		if(aData.data) {
			var sAction = aData.data.action;
		}
		var aData = aData.data;

		
	}


    
});
