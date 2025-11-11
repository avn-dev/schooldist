/*
 * Util Klasse
 */
var ModuleGUI = Class.create(ATG2, {
	
    requestCallbackHook: function($super, aData) {
		
		$super(aData);
		
		if(aData.action == 'openDialog'){
			
			$$('.section_all_checkbox').each(function(oAllCheckbox){
				Event.observe(oAllCheckbox, 'click', function(){
					this.markAllSectionCheckboxes(oAllCheckbox);
				}.bind(this));
			}.bind(this));
			
			
			$$('.category_all_checkbox').each(function(oAllCheckbox){
				Event.observe(oAllCheckbox, 'click', function(){
					this.markAllCategoryCheckboxes(oAllCheckbox);
				}.bind(this));
			}.bind(this));
		}
		
	},
	
	markAllCategoryCheckboxes : function(oAllCheckbox){
		var sClassName = oAllCheckbox.value;

		$$('.'+sClassName).each(function(oCheckbox){
			oCheckbox.checked = oAllCheckbox.checked;
			this._fireEvent('click', oCheckbox);
		}.bind(this));
		
	},
	
	markAllSectionCheckboxes : function(oAllCheckbox){
		
		var sClassName = oAllCheckbox.value;

		$$('.'+sClassName).each(function(oCheckbox){
			oCheckbox.checked = oAllCheckbox.checked;
		});
		
	}
});