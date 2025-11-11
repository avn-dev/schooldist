var Tuition = Class.create(ATG2, {
	
	initialize: function() {
		
		this.aBarToggleStatus = new Array();
		this.iBarCount = 0;
		this.iToolBarHeight = 36;
		this.aBars = new Array();
		this.translations = {};
		
	},
	
	resize: function() {
		// no action
	},
	
	toogleFilterBar: function() {

		var aToolBars = $$('.divToolbar');
		
		if(aToolBars.length > 0) {
			var oFilterBar = aToolBars[0];
			var oToggleBar = oFilterBar.down('.toggle-bar');

			if(oToggleBar) {
				var oToggleIcon = this.createBarToogleIcon();
				oToggleIcon.hide();
				// Toogle-Icon vor dem Cleaner einsetzen, da dort die zweite Filterreihe gesetzt wird
				oToggleBar.insert(oToggleIcon);
			}
			// this.aBars muss für this.resizeBars() befüllt sein
			this.aBars.push(oFilterBar); 
			// in der this.resizeBars() wird das Event auf das ToogleIcon gesetzt
			this.resizeBars();
		}
	},
	
	addTranslation: function(sTrans, sValue) {
		this.translations[sTrans] = sValue;
	}
	
});


