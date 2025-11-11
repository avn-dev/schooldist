var InsuranceCustomerGui = Class.create(StudentlistGui, {
	
	updateIconCallbackHook: function(aData) {

		var sSrc = '/admin/extensions/thebing/images/accept.png';
		var sLabel = this.getTranslation('provider_confirm');
		if(aData.additional_data == 1){
			sSrc = '/admin/extensions/thebing/images/delete.png';
			sLabel = this.getTranslation('provider_de_confirm');
		} else if(aData.additional_data == 2) {
			sSrc = '/admin/extensions/thebing/images/accept.png';
			sLabel = this.getTranslation('provider_both_confirm');
		}

		var oIconDiv = $('provider_accepted__'+this.hash);

		if(oIconDiv){
			var oImg = oIconDiv.down();
			if(oImg){
				oImg.src = sSrc;
			}
			var oLabel = oIconDiv.next();

			if(oLabel){
				oLabel.update(sLabel); 
			}
		}
		
		
		return aData;
	}
	
});