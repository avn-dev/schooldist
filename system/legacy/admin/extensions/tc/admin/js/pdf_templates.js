
var Templates = Class.create(CoreGUI, {

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse
		$super(aData);

		if(
			aData.action == 'reloadDialogTab' ||
			aData.action == 'openDialog' ||
			aData.action == 'saveDialogCallback'
		) {
			if(
				aData.data.show_positions_table_select &&
				aData.data.show_positions_table_select == 1
			) {
				$('positions_table_settings').show();
			} else {
				$('positions_table_settings').hide();
			}
		}

	},

	showPreviewPdf: function(iTemplate, iObject, sLanguage){

		var sParam = '';
		sParam += '&task=showPreviewPdf';
		sParam += '&iTemplate='+iTemplate;
		sParam += '&iObject='+iObject;
		sParam += '&sLang='+sLanguage;
		
		this.request(sParam, '', '', true);	
	},
	
	showLayoutChangeWarning: function(aData, b) {

		if(aData.action !== 'new' ) {
			var oWarningBox = confirm(this.getTranslation('switch_warning'));
			if(oWarningBox === true) {
				this.reloadDialogTab(aData.id, b);
			} else {
				var oSelect = $('save[' + this.hash + '][' + aData.id + '][layout_id][tc_pt]');
				oSelect.value = aData.values[1].value;
			}
		}
	}

});