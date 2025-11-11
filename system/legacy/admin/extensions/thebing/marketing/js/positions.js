
var Positions = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {

		// RequestCallback der Parent Klasse
		$super(aData);

		var sTask = aData.action;
		if(aData.data) {
			var sAction = aData.data.action;
			var aData = aData.data;
		}

		// Obeserver setzen für die DD
		$$('.position_placeholders').each(function(oSelect){
			oSelect.stopObserving('change');
			Event.observe(oSelect, 'change', function(e){
				this.updatePlaceholder(oSelect);
			}.bind(this));
		}.bind(this));
		
	},

	updatePlaceholder : function(oSelect){
		// Platzhalter
		var sPlaceholder = $F(oSelect);

		// PositionsTyp holen

		var oTr = oSelect.up('tr');

		var aMatch = oTr.id.match(/([a-z].*)_(.*)_([0-9].*)/);

		if(aMatch[3]){
			var sParam = '';
			sParam += '&task=updatePlaceholder&data_id='+aMatch[3];
			sParam += '&placeholder='+sPlaceholder;

			this.request(sParam);
		}

	}
    
});
