
var UserGUI = Class.create(CoreGUI, {
	
	openDialog: function($super, aData) {

		$super(aData);

		this.initPasswordInputs(aData);

	},
	
	reloadDialogTabCallback: function($super, oData) {
		
		$super(oData);

		this.initPasswordInputs(oData);

	},

	initPasswordInputs: function(oData) {
		
		oPasswordStrengthTranslations = {
			very_week: this.translations['very_week'],
			week: this.translations['week'],
			sufficient: this.translations['sufficient'],
			good: this.translations['good'],
			very_good: this.translations['very_good'],
			password_strength: this.translations['password_strength']
		};

		initPasswordInputs('save['+this.hash+']['+this.sCurrentDialogId+'][password][su]');

	}

});
	