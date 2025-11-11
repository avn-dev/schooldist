var QuestionGUI = Class.create(CoreGUI, {
	
	reloadDependencyFields: function(sDialogId, iTabIndex) {

		var sFieldPrefix = 'save[' + this.hash + '][' + sDialogId + ']';
		
		var oDependencyObjects		= $(sFieldPrefix + '[dependency_objects]');
		var oDependencySubObjects	= $(sFieldPrefix + '[dependency_subobjects]');
		
		if(oDependencyObjects) {
			$j(oDependencyObjects).multiselect('removeAllOptions');
		}
		
		if(oDependencySubObjects) {
			$j(oDependencySubObjects).multiselect('removeAllOptions');
		}
				
		this.reloadDialogTab(sDialogId, iTabIndex);		
		
	}

});


