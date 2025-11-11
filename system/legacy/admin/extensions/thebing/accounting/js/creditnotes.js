var CreditnotesGui = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {
		$super(aData);

		var sTask = aData.action;
		
		aData = aData.data;

		if(
			sTask == 'openDialog' ||
			sTask == 'update_select_options'
		){
			this.updateTemplateFields(aData);
		}else if(sTask == 'closeDialogAndReloadTable'){
				if(
					aData.cn_ids &&
					aData.cn_ids.length == 1
				){
					var aElement = new Object();
					aElement.task = 'openDialog';
					aElement.action = 'edit';
					aElement.request_data = '';
					this.prepareAction(aElement);
				}
		}
	},
	
	updateTemplateFields : function(aData){

		var oSelect = $('save['+this.hash+']['+aData.id+'][template_id][kamc]');
		
		// Templateabhängigen Felder
		this.aFields = new Array();

		var oDate		= $('save['+this.hash+']['+aData.id+'][date][kmv]');
		var oAddress	= $('save['+this.hash+']['+aData.id+'][txt_address][kmv]');
		var oSuject		= $('save['+this.hash+']['+aData.id+'][txt_subject][kmv]');
		var oIntro		= $('save['+this.hash+']['+aData.id+'][txt_intro][kmv]');
		var oOutro		= $('save['+this.hash+']['+aData.id+'][txt_outro][kmv]');
		
		if(oDate){
			this.aFields['date'] = oDate;
			this.hideRow('date');
		}
		if(oAddress){
			this.aFields['address'] = oAddress;
			this.hideRow('address');
		}
		if(oSuject){
			this.aFields['subject'] = oSuject;
			this.hideRow('subject');
		}
		if(oIntro){
			this.aFields['intro'] = oIntro;
			this.hideRow('intro');
		}
		if(oOutro){
			this.aFields['outro'] = oOutro;
			this.hideRow('outro');
		}
		
		// gewählte Template ID
		var iTemplateId = 0;
		if(oSelect){
			iTemplateId = oSelect.value
		}else if(aData.template_id){
			iTemplateId = aData.template_id;
		}
		
		
		if(iTemplateId > 0){

			if(
				aData.fields &&
				aData.fields[iTemplateId]
			){

				var aFieldData = aData.fields[iTemplateId];
				
				if(aFieldData['date'] == 1){
					this.showRow('date');
				}
				
				if(aFieldData['address'] == 1){
					this.showRow('address');
				}
				
				if(aFieldData['subject'] == 1){
					this.showRow('subject');
				}
				
				if(aFieldData['intro'] == 1){
					this.showRow('intro');
				}
				
				if(aFieldData['outro'] == 1){
					this.showRow('outro');
				}
				
			}
		}
	},
	
	hideRow : function(sType){
		if(this.aFields[sType]){
			this.aFields[sType].up('.GUIDialogRow').hide();
			this.aFields[sType].disable();
		}
	},
	
	showRow : function(sType){
		if(this.aFields[sType]){
			this.aFields[sType].up('.GUIDialogRow').show();
			this.aFields[sType].enable();
		}
	}
	
	
	
});
