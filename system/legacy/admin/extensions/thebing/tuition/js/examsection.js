/*
 * Util Klasse
 */
var Examsection = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse
		$super(aData);
		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			)
			&&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {
			this.prepareEvent(aData.data);
		}

	},

	prepareEvent : function (aDialogData) {


		var oSelect = $('save['+this.hash+']['+aDialogData.id+'][entity_type_id]');

		if(oSelect){

			// Tab für Drowdown optionen aus/einblenden
			this.toggleOptionTab(aDialogData, oSelect);
			Event.observe(oSelect, 'change', function() {
				this.toggleOptionTab(aDialogData, oSelect);
			}.bind(this));


		}
	},
	
	// Tab für Optionen aus/einblenden
	toggleOptionTab : function(aDialogData, oSelect){

		// Tab
		var oTabLi = '';
		if($('tabHeader_1_'+aDialogData.id+'_'+this.hash)){
			oTabLi  = $('tabHeader_1_'+aDialogData.id+'_'+this.hash);
		}

		if(
			oSelect.value == '5' &&
			oTabLi != ''
		){
			oTabLi.show();
		}else if(
			oTabLi != ''
		){
			oTabLi.hide();
		}

	},



});