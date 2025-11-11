var RatingGUI = Class.create(CoreGUI, {
	
	requestCallbackHook : function ($super, aData){

		if(!this.aTempFieldCache) {
			this.aTempFieldCache = new Array();
		}

		$super(aData);

		if(
			aData.action &&
			aData.action == 'reloadDialogTab'
		) {
			this.setRatingFieldData();
		}

	},
	
	loadRatingDataFields: function(sDialogId, iTabIndex) {		
		
		var oRating = $('save['+this.hash+']['+sDialogId+'][number_of_ratings][tc_mr]');
		
		if(oRating) {
			var aDescriptions	= $$('.ratingDescription');
            // Werte werden gecached da neue Werte noch nicht
            // gespeichert sein könnten
			this.saveCurrentRatingValues(aDescriptions);
			this.reloadDialogTab(sDialogId, iTabIndex);
		}
				
	},

	saveCurrentRatingValues: function(aFields) {

		aFields.each(function(oInput){
			this.aTempFieldCache[oInput.id] = $F(oInput);
		}.bind(this));

	},

	loadCurrentRatingValues: function(aFields) {

		aFields.each(function(oInput){
			if(this.aTempFieldCache[oInput.id]) {
				oInput.value = this.aTempFieldCache[oInput.id];
			}
		}.bind(this));

	},

	setRatingFieldData: function() {

		var aDescriptions	= $$('.ratingDescription');
		this.loadCurrentRatingValues(aDescriptions);

		this.aTempFieldCache = new Array();

	}
	
});
