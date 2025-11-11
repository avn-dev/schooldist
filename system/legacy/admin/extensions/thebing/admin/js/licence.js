var Licence = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse

		$super(aData);
		if(
			aData.action == 'openDialog' &&
			aData.data.action == 'access'
		) {
			var aLinkMark	= $$('.markCheckbox');
			var oLinkMark;
			var oDivTabInner;
			var aInputs;
			var oElement;

			var sTitleSelectAll		= this.getTranslation('access_licence_select_all');
			var sTitleUnselectAll	= this.getTranslation('access_licence_unselect_all');

			for(i=0;i<aLinkMark.length;i++){
				oLinkMark = aLinkMark[i];
				oLinkMark.onclick = function(){

					oDivTabInner = this.up('.GUIDialogContentPadding');
					var mChecked;
					var sTitle;

					if(!this.hasClassName('marked')){
						this.addClassName('marked');
						mChecked	= 'checked';
						sTitle		= sTitleUnselectAll;
					}else{
						this.removeClassName('marked');
						mChecked	= false;
						sTitle		= sTitleSelectAll;
					}

					this.innerHTML = sTitle;

					if(oDivTabInner){
						aInputs = oDivTabInner.getElementsByTagName('input');
						for(i=0;i<aInputs.length;i++){
							oElement = aInputs[i];
							if(oElement.type=='checkbox'){
								oElement.checked = mChecked;
							}
						}
					}

				}
			}
		}
	}
});