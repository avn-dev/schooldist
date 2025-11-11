var BackendTranslationGUI = Class.create(ATG2, {
	
	requestCallbackHook: function($super, oData) {

		$super(oData);

		if(
			oData.action == 'openDialog' &&
			oData.data.action == 'edit'
		) {
			this.requestTranslationVerifications(oData);
		} else if(
			oData.action == 'requestTranslationVerificationsCallback'
		) {
			this.requestTranslationVerificationsCallback(oData);
		} else if(
			oData.action == 'requestVerifyTranslationCallback'
		) {
			this.switchTranslationVerifyCallback(oData);
		}

	},
	
	requestTranslationVerifications: function(oData) {
		
		$j('.translation-verify').hide();
		$j('.translation-verify').off('click');
		
		var sParam = '&task=request&action=TranslationVerifications';
		
		this.request(sParam);
		
	},
	
	requestTranslationVerificationsCallback: function(oData) {
		
		if(oData.data.external.length == 0) {
			return;
		}
		
		for(var i = 0; i < oData.data.external.length; ++i) {
			
			var oExternalTranslation = oData.data.external[i];
			
			var oVerifyIcon = $j('.translation-verify[data-language="'+oExternalTranslation.language+'"]');
			$j(oVerifyIcon).attr('data-id', oExternalTranslation.language_data_id);
			$j(oVerifyIcon).attr('data-key', oExternalTranslation.language_data_id+'-'+oExternalTranslation.language);
			
			if(parseInt(oExternalTranslation.verified) === 1) {
				$j(oVerifyIcon).addClass('system-color');
			}
			
			$j(oVerifyIcon).show();
			
			var _this = this;
			
			$j(oVerifyIcon).click(function() {
				_this.switchTranslationVerify($j(this).attr('data-id'), $j(this).attr('data-language'));
			});
			
		}
		
	},
	
	switchTranslationVerify: function(iId, sLanguage) {
		
		var oVerifyIcon = $j('.fa[data-key="'+iId+'-'+sLanguage+'"]');
		
		if($j(oVerifyIcon).hasClass('system-color')) {
			$j(oVerifyIcon).removeClass('system-color');
		} else {
			$j(oVerifyIcon).addClass('system-color');
		}
		
		var sParam = '&task=request&action=VerifyTranslation';
		sParam += '&translation_id='+iId;
		sParam += '&language='+sLanguage;
		
		this.request(sParam);
		
	},
	
	switchTranslationVerifyCallback: function(oData) {
		
//		var oVerifyIcon = $j('.fa[data-key="'+oData.data.id+'-'+oData.data.language+'"]');
//		
//		if(parseInt(oData.data.verified) === 0) {
//			$j(oVerifyIcon).removeClass('system-color');
//		} else {
//			$j(oVerifyIcon).addClass('system-color');
//		}
		
	}
	
});