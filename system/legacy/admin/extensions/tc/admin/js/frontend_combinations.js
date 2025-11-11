
var FrontendCombinations = Class.create(CoreGUI,
{
	
	requestCallbackHook: function($super, aData)
	{
		$super(aData);

		if(
			aData.action === 'openDialog' ||
			aData.action === 'saveDialogCallback'
		) {
			this.toggleCurrencySelect(aData.data);
		}
	},

	toggleCurrencySelect : function(aData) {
		
		var oCheckbox = $('save['+this.hash+']['+aData.id+'][items_agencypricelist][ta_fc]');
		
		if(oCheckbox) {
			if(oCheckbox.checked == true) {
				$('items_currencies').show();
			} else {
				$('items_currencies').hide();
			}
		}
		
		return true;
		
	}
	
});