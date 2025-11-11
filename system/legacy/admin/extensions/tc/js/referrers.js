
var ReferrersGui = Class.create(CoreGUI,
{
	
	prepareDependencyVisibilityHook: function(oElement, aValues, sElement, iIsIdElement){
		
		// prüft ob es sich um Eingabefeld oder um eine Div (o.ä) handelt		
		if(!iIsIdElement){
			var oChildElement = $('save['+this.hash+']['+this.sCurrentDialogId+']['+sElement+']');	
		} else {
			var oChildElement = $(sElement);
		}
		
		this.bFoundOfficeLanguages = true;
		
		if(
			oElement &&
			$F(oElement) &&
			oChildElement			
		){			
			//Dialog-Row in dem sich das Element befindet
			var oElementUp = oChildElement.up('.GUIDialogRow');
			//Wenn kein Eintrag ausgewählt wurde dann Dialog-Row ausblenden, 
			//ansonsten einblenden	
			if(oElementUp) {
				if($F(oElement).length === 0){						
					oElementUp.hide();			
				}else{
					oElementUp.show();
				}
			}
		}
		
	},
	
	// setzt den Value aus dem Select in das Feld "Label"	
	prepareJoinedObjectLabel: function(oSelect){

		var iID = oSelect.id.replace(/\[field\]/, '[label]');

		$(iID).value = oSelect.options[oSelect.selectedIndex].innerHTML;
				
	}
	
});