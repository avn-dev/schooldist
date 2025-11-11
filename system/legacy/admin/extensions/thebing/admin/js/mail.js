var Mail = Class.create(ATG2, {

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
			var oCheckbox = $('save['+this.hash+']['+aData.data.id+'][smtp]');
			if(oCheckbox){

				this.aDialogData = aData.data;
				this.oCheckbox = oCheckbox;
				this.toggleSmtpOptions(this);
				oCheckbox.observe('change', this.toggleSmtpOptions.bind(this));
				
				var oButtonSmtp = $('checkSmtp');
				if(oButtonSmtp){
					oButtonSmtp.observe('click', this.checkSmtpOptions.bind(this));
				}

			}
		}
	},

	toggleSmtpOptions: function() {

		var oContainerSmtp = $('container-smtp');

		if(this.oCheckbox.checked){
			oContainerSmtp.show();
		}else{
			oContainerSmtp.hide();
		}

	},

	checkSmtpOptions: function() {
		var sParam = '&'+$('dialog_form_'+this.aDialogData.id+'_'+this.hash).serialize();
		this.request('&task=checkSmtp'+sParam);
	}

});