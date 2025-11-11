
var CommunicationEmail = Class.create(AccessMatrixGui, {

	prepareAction : function($super, aElement, aData) {

		if(
			aElement &&
			aElement.task &&
			(
				aElement.task === 'check_imap' ||
				aElement.task === 'check_smtp'
			)
		) {
			this.checkConnection(aElement.task);
		} else if(
			aElement &&
			aElement.task &&
			aElement.task === 'load_settings'
		) {
			this.reloadDialogTab(aData.id, 0);		
		} else {
			$super(aElement, aData);
		}

	},

	requestCallbackHook: function($super, oData) {

		$super(oData);

		if(
			(
				oData.action === 'openDialog' ||
				oData.action === 'saveDialogCallback' ||
				oData.action == 'reloadDialogTab'
			) && (
				oData.data.action === 'new' ||
				oData.data.action === 'edit'
			)
		) {
			var oSettings = document.getElementById('mail_account_settings');
			var oSettingsImap = document.getElementById('container-imap');
			var bSettingsView = false;
			var bSettingsViewImap = false;
			
			if(oSettings) {
				bSettingsView = true;	
			}
			
			if(oSettingsImap) {
				bSettingsViewImap = true;	
			}
			
/*
			var oCheckboxSmtp = $('save['+this.hash+']['+oData.data.id+'][smtp][tc_ce]');
			if(oCheckboxSmtp){

				this.aDialogData = oData.data;
				this.oCheckboxSmtp = oCheckboxSmtp;
				this.toggleSmtpOptions(this);
				oCheckboxSmtp.observe('change', this.toggleSmtpOptions.bind(this));

			}
*/
			var oButtonSmtp = $('smtp_button');
			var oButtonImap = $('imap_button');
			var oButtonNext = $('next_button');
			var oButtonSave = $('save_button');

			if(bSettingsView) {
				
				if(bSettingsViewImap) {
					oButtonImap.show();
				} else {
					oButtonImap.hide();
				}
				oButtonSmtp.show();			
				oButtonSave.show();			
				oButtonNext.hide();
				
				var oEmail = $('save['+this.hash+']['+oData.data.id+'][email][tc_ce]');
				if(oEmail){
					oEmail.observe('keyup', (e) => {	
						$j(oEmail).closest('.GUIDialogContentDiv').find('input[name!="save[email][tc_ce]"]').val("");
						this.waitForInputEvent('reloadDialogTab', e, oData.data, 0);
					});
				}
				
				var oCheckboxImap = $('save['+this.hash+']['+oData.data.id+'][imap][tc_ce]');
				if(oCheckboxImap){
					this.aDialogData = oData.data;
					this.oCheckboxImap = oCheckboxImap;
					oCheckboxImap.observe('change', () => {					
						this.reloadDialogTab(oData.id, 0);
					});
				}
				
				$j('#dialog_' + oData.data.id + '_' + this.hash + ' .fix-settings button').click(function() {
					$j(this).closest('.fix-settings').hide();
					$j(this).closest('.mailserver').find('.manual-server-settings').show();
				});
				
			} else {
				oButtonSave.hide();
				oButtonSmtp.hide();			
				oButtonImap.hide();			
				oButtonNext.show();
			}
		}
	},

/*
	toggleSmtpOptions: function() {

		var oContainerSmtp = $('container-smtp');
		var oButtonSmtp = $('smtp_button');

		if(this.oCheckboxSmtp.checked){
			oContainerSmtp.show();
			oButtonSmtp.show();
		} else {
			oContainerSmtp.hide();
			oButtonSmtp.hide();
		}

	},

	toggleImapOptions: function() {

		var oContainerImap = $('container-imap');
		var oButtonImap = $('imap_button');

		if(this.oCheckboxImap.checked) {
			oContainerImap.show();
			oButtonImap.show();
		} else {
			oContainerImap.hide();
			oButtonImap.hide();
		}

		// Von "tc_ce.imap_append_sent_mail" abhängige Felder wieder ausblenden,
		// wenn sie durch "tc_ce.imap" eingeblendet wurden (#9371)
		 
		var oCheckboxImapAppend = $('save['+this.hash+']['+this.aDialogData.id+'][imap_append_sent_mail][tc_ce]');
		if(
			oCheckboxImapAppend &&
			!oCheckboxImapAppend.checked
		) {
			this._fireEvent('change', oCheckboxImapAppend);
		}

	},*/

	checkConnection: function(sCheckType) {
		var sParam = '&task=check&type='+sCheckType+'&'+$('dialog_form_'+this.aDialogData.id+'_'+this.hash).serialize();
		this.request(sParam);
	}

});
