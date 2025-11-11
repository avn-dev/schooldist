
var ExtrafieldGui = Class.create(CoreGUI,{
	
	/**                                  
	 * Request Callback                  
	 */                                  
	requestCallback : function($super, objResponse, strParameters) {
		
		var oData = this._evalJson(objResponse);
		
		if(
			oData &&
			oData.action &&
			oData.data &&
			oData.data.id &&
			oData.data.action &&
			(
				oData.action == 'openDialog' || 
				oData.action == 'saveDialogCallback'
			) && (
				oData.data.action == 'new' || 
				oData.data.action == 'edit'
			)
		) {
			this.aObjectLangs = oData.data.object_langs;
		}
		
		$super(objResponse, strParameters);
		
		if(
			oData &&
			oData.action &&
			oData.action == 'update_select_options'
		) {

			$$('#dialog_'+oData.data.id+'_'+this.hash+' .jQm').each(function(oSelect){

				var sTypeSelect = oSelect.id.replace('objects', 'type');
				var oTypeSelect = $(sTypeSelect);

				this.prepareI18NFields(oTypeSelect, oData.data.id)

			}.bind(this)); 
		
		}

	},
	
	/**                                           
	 * Individuelle Events für die wiederholbaren Bereiche
	 */                                           
	refreshJoinedObjectContainerEventsHook : function(sDialogid) {
                   
		$$('#dialog_'+sDialogid+'_'+this.hash+' .content_type').each(function(oSelect){

			this.prepareI18NFields(oSelect, sDialogid)

			oSelect.stopObserving('change');

			Event.observe(oSelect, 'change', function(){
				this.prepareI18NFields(oSelect, sDialogid);
			}.bind(this));

		}.bind(this));    
	},   
	
	getOfficeLangs: function(aObjects){
		var aLangs = new Array();
	
		if(
			aObjects &&
			aObjects.length > 0 &&
			this.aObjectLangs && 
			this.aObjectLangs.length > 0
		){
			this.aObjectLangs.each(function(aData){
				aObjects.each(function(iObject){
					if(aData.object_id == iObject){
						aData.langs.each(function(sLang){
							aLangs[aLangs.length] = sLang
						});
					}
				});
			});
		}
		return aLangs;
	},
	
	prepareI18NFields: function(oSelect, sDialogid){

		var sType = $F(oSelect);
		var sId = oSelect.id;
		var sContainerId = sId.replace('save['+this.hash+']['+sDialogid+'][type][tc_fcc][', '');
			sContainerId = sContainerId.replace('][content]', '');
		var sOfficeSelect = sId.replace('type', 'objects');
		var aObjects = $F(sOfficeSelect);
		var aAllowedLangs = this.getOfficeLangs(aObjects);
		
		$$('#row_joinedobjectcontainer_content_'+sContainerId+' .i18nInput').each(function(oField){
			
			var	oParent			= oField.up('div');
			var sLangFieldId	= oField.id;
			var sLangFieldName	= oField.name;
			var sLangFieldValue = $F(oField);
			var oNewElement;


			var sLang			= sLangFieldId.replace('save['+this.hash+']['+sDialogid+'][content][tc_fcc_i18n][', '');
			var aTemp			= sLang.split(']');
				sLang			= aTemp[0];
			var bAllowed		= false;
			
			
			aAllowedLangs.each(function(sAllowLang){
				if(sAllowLang == sLang){
					bAllowed = true;
				}
			});
			
			if(!bAllowed){
				oParent.hide();
			} else {
				oParent.show();
			}
			
			// Wenn HTML Feld
			if($(sLangFieldId+'_parent')){
				// Alle Schließen
				this.closeAllEditors(sDialogid);
			}
	
			switch(sType){
				case 'textarea':
				case 'html':
					oNewElement = new Element('textarea');
					oNewElement.innerHTML = sLangFieldValue;
					if(sType == 'html'){
						oNewElement.addClassName('GuiDialogHtmlEditor advanced');
					} else {
						oNewElement.addClassName('form-control input-sm');
					}
					break;
				default:
					oNewElement = new Element('input');
					oNewElement.type = 'text';
					oNewElement.value = sLangFieldValue;
					oNewElement.addClassName('form-control input-sm');
					break;
			}
			
			if(oNewElement){
			
				var oFlag = oField.next('.i18nFlag');
	
				if(
					oFlag &&
					sType != 'html'
				){
					oFlag.style.top = '5px';
					oFlag.style.left = '-297px';
				} else if(oFlag){
					oFlag.style.top = '-130px';
					oFlag.style.left = '-20px';
				}
				
				oField.insert({after: oNewElement});
				oField.remove();

				oNewElement.addClassName('i18nInput txt');
				oNewElement.id = sLangFieldId;
				oNewElement.name = sLangFieldName;
			}
			
		}.bind(this));
		
		this.pepareHtmlEditors(sDialogid);     
		
		
		
	}
	
});