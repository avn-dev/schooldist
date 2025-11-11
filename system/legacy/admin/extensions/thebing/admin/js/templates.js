
var Templates = Class.create(UtilGui, {

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

			var oAppReleaseCheckbox = $('dialog_row_app_release');

			if(oAppReleaseCheckbox) {
				if(
					aData.data.show_dialog_row_app_release &&
					aData.data.show_dialog_row_app_release == 1
				) {
					oAppReleaseCheckbox.show();
				} else {
					oAppReleaseCheckbox.hide();
				}
			}

			this.switchInboxSelect();
		}

	},

	showPreviewPdf: function(iTemplate, iSchool, sLanguage){

		var sParam = '';
		sParam += '&task=showPreviewPdf';
		sParam += '&iTemplate='+iTemplate;
		sParam += '&iSchool='+iSchool;
		sParam += '&sLang='+sLanguage;
		
		this.request(sParam, '', '', true);	
	},

	switchInboxSelect: function() {
		
		var aTypeSelects = $$('.document_type');
		
		if(aTypeSelects[0]) {
		
			var oTypeSelect = aTypeSelects[0];
			var sDocumentType = oTypeSelect.value;
			
			var sInboxSelectId	= oTypeSelect.id.replace('type', 'inboxes');
			var oInboxSelect	= $(sInboxSelectId);
			
			if(oInboxSelect) {				
				var oInboxRow		= oInboxSelect.up('.GUIDialogRow');
				
				if(oInboxRow) {	
					// Dokumente, bei denen keine Inboxen ausgewählt werden müssen
					var aIgnoredDocumentTypes = [
						'cheque',
						'document_transfer_payment',
						'document_accommodation_payment',
						'agency_overview',
						'manual_creditnotes',
						'document_teacher_contract_basic',
						'document_teacher_contract_additional',
						'document_accommodation_contract_basic',
						'document_accommodation_contract_additional',
						'document_teacher_payment'
					];

					var bDisable = false;

					aIgnoredDocumentTypes.each(function(sDisableType){
						if(sDocumentType === sDisableType) {							
							bDisable = true;
						}
					}.bind(this));	
					
					if(bDisable) {
						oInboxRow.hide();
						oInboxSelect.removeClassName('required');
					} else {
						oInboxRow.show();
						oInboxSelect.addClassName('required');
					}
					
				}
			}
			
		}
		
	}

});