	
var CancellationFee = Class.create(ATG2, {

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

			// wird beim Start aufgerufen
			$$('.type_change').each(function(oSelect){
				this.checkType(oSelect);
			}.bind(this));

			// wird beim verändern des Selects ausgeführt	
			$$('.type_change').each(function(oSelect){
				oSelect.stopObserving('change');
				Event.observe(oSelect, 'change', function(){
					this.switchType(oSelect);
				}.bind(this));
			}.bind(this));

		}

	},

	switchType: function(oSelect){

		var sInput = oSelect.id.replace('kind', 'lowest_amount');
		var oInput = $(sInput);

		this.checkType(oSelect);

		// Wenn das Select verändert wird, wird der Mindestbetrag auf 0 gesetzt,
		// weil sonst der alte Wert mit in der Datenbank gespeichet wird		
		oInput.value = '';

	},

	checkType: function(oSelect){

		var sInput = oSelect.id.replace('kind', 'lowest_amount');
		var oInput = $(sInput);

		// Wenn im Select Währung ausgewählt wurde, kann kein Mindestbetrag 
		// eingetragen werden
		if(oSelect.value == 2){
			oInput.up('.GUIDialogRow').hide();						
		}else{
			oInput.up('.GUIDialogRow').show();
		}

	},

	/**
	 * abgeleitete Funktion: wird bei refreshJoinedObjectContainerEvents 
	 * aufgerufen, damit der neue Container die Methode switchType ausführt
	 */

	refreshJoinedObjectContainerEventsHook: function(){
		
		$$('.type_change').each(function(oSelect){
			oSelect.stopObserving('change');
			Event.observe(oSelect, 'change', function(){
				this.switchType(oSelect);
			}.bind(this));
		}.bind(this));
			
	}

});