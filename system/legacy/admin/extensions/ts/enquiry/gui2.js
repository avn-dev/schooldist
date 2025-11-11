
var Enquiry = Class.create(RequestConvertGui, {
	
	sAlias : 'ts_i',
	
	requestCallbackHook: function($super, aData) {
		
		$super(aData);

		if(
			(
				aData.action=='openDialog' ||
				aData.action=='saveDialogCallback'
			) &&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		){	

			this.toggleGroupTab(aData.data);
			
			// Event Abfeuern für alle Checkboxen mit dieser Klasse
			$$('.fire_event').each(function(oInput){
				this._fireEvent('change', oInput);
			}.bind(this)); 
			
			// Zahlungsmethodenselect und Agenturselect de-/aktivieren
			this.togglePaymentSelect(aData.data);
			// Währungsselect de-/aktivieren
			this.toggleCurrencySelect(aData.data);

			// Bei konvertierter Anfrage das Feld zum Verknüpfen einer Buchung deaktivieren
			var oAutocompleteInput = this.getDialogSaveField('autocomplete_inquiry_id').prevAll('input[type=text]');
			if(
				aData.data.action == 'new' ||
				aData.data.enquiry_converted ||
				aData.data.group_id
			) {
				oAutocompleteInput.prop('disabled', true).addClass('readonly');
			}
		}
	
	},

	// TODO Redundanter JS-Mist, der nur bei Anfragen benötigt wird
	// Währung ist nur abänderbar, wenn noch keine Kombination erstellt wurde
	// Deshalb die Information mitschicken ob Kombinationen erstellt wurden
	toggleCurrencySelect : function(aData){
		
		var iCombinationCount = 0;
		
		if(aData.combination_count){
			iCombinationCount = aData.combination_count;
		}
		
		var aCheckSelects = [];
		aCheckSelects[0] = $('save['+this.hash+']['+aData.id+'][currency_id][ts_i]');

		if(iCombinationCount > 0){				
			this.disableSelects(aCheckSelects);
		}

	},

	// TODO Redundanter JS-Mist, der nur bei Anfragen benötigt wird
	// Zahlungsmethode/Agentur ist nur abänderbar, wenn noch kein Angebot erstellt wurde
	// Deshalb die Information mitschicken ob Angebote erstellt wurden
	togglePaymentSelect: function(aData) {
		
		var iOfferCount = 0;
		
		if(aData.offer_count){
			iOfferCount = aData.offer_count;
		}
		
		var aCheckSelects = [];
		aCheckSelects[0] = $('save['+this.hash+']['+aData.id+'][agency_id][ts_i]');
		aCheckSelects[1] = $('save['+this.hash+']['+aData.id+'][payment_method][ts_i]');
		aCheckSelects[2] = $('save['+this.hash+']['+aData.id+'][school_id][ts_ij]');
		aCheckSelects.push(this.getDialogSaveField('is_group').get(0));
		
		if(iOfferCount > 0){				
			this.disableSelects(aCheckSelects);
		}
	},
	
	// Selects die nicht editierbar sein dürfen
	disableSelects: function(aSelects) {
		
		aSelects.each(function(oSelect){
				
			oSelect.readOnly = true;
			oSelect.addClassName('readonly');			
			
			oSelect.disabled = true;
			// Alle nicht selectierten Optionen sperren!
//			oSelect.childElements().each(function(oOption){
//				if(
//					oOption.selected != true
//				){
//					oOption.disabled = true;
//				}
//			}.bind(this));  
		}.bind(this)); 
			
	},
	
	/**
	 * Toggle-Aktion für die Checkbox Gruppenanfrage ausführen
	 */
	toggleGroupTab : function(aData)
	{
		//Checkbox Gruppenanfrage
		// var oCheckbox			= this.getGroupCheckbox(aData);
		var oCheckbox = this.getDialogSaveField('is_group').get(0);

		var sTabs = '.tab_'+aData.id+'_'+this.hash;
		var aTabs = $$(sTabs);
		
		var oTabGroup = null;
		
		aTabs.each(function(oCurrentTab){
			oTabGroup = oCurrentTab;
		});
		
		if(
			oTabGroup
		){
			//Tab Gruppeninformationen
			var sTabGroupId			= oTabGroup.id;
			var sTabCount			= sTabGroupId.replace('tabHeader_','');
			sTabCount				= sTabCount.replace('_'+aData.id+'_'+this.hash,'');
			var sTabBoddyId			= 'tabBody_'+sTabCount+'_'+aData.id+'_'+this.hash;
			// Ob bereits eine Gruppe gespeichert  ist

			var iIsGoup				= aData.group_id;

			if(oTabGroup && oCheckbox && sTabCount != 0)
			{
				if(oCheckbox.checked)
				{
					oTabGroup.show();
					$$('#'+sTabBoddyId+' .required_disabled').each(function(oField){
						oField.removeClassName('required_disabled');
						oField.addClassName('required');
					});
				}
				else
				{
					// Sind Gruppen gespeichert muss es erst eine Warnung geben
					if(
						iIsGoup == 1
					){
						if(!confirm(this.getTranslation('delete_group'))){
							oCheckbox.checked = true;
							return;
						}
					}

					oTabGroup.hide();
					$$('#'+sTabBoddyId+' .required').each(function(oField){
						oField.removeClassName('required');
						oField.addClassName('required_disabled');
					});
				}
			}	
		}

	
	},
	
	// Toggeln der "Guide?" Checkbox
	toggleGuideCheckbox : function(aData, oEvent){
		var oGuideCheckbox = oEvent.target;

		// Einblenden von "Alles Kostenfrei"
		var iContactId = this.getContactIdByContactField(oGuideCheckbox);
			
		var oFreeAllCheckbox = $('save['+this.hash+']['+aData.id+'][detail_free_all][group]['+iContactId+'][contacts]');
			
		if(
			oFreeAllCheckbox &&
			oGuideCheckbox
		){
			var bShow = false;
			if(oGuideCheckbox.checked){
				oFreeAllCheckbox.up('.GUIDialogRow').show();
				if(!oFreeAllCheckbox.checked) {
					bShow = true;
				}
			}else{
				oFreeAllCheckbox.checked = false;
				oFreeAllCheckbox.up('.GUIDialogRow').hide();				
			}
	
			// Checkboxen für einzelne Positionen verstecken
			this.togglePositionCheckboxes(aData, iContactId, bShow);
			
		}
	},
	
	// Toggeln der "Alles Kostenfrei?" Checkbox
	toggleFreeCheckbox : function(aData, oEvent){
		var oFreeCheckbox = oEvent.target;
	
		// Alle anderen Felder einblenden wenn diese checkbox abgewählt ist UND
		// Guide gewählt ist
		var iContactId = this.getContactIdByContactField(oFreeCheckbox);
		var oGuideCheckbox = $('save['+this.hash+']['+aData.id+'][detail_guide][group]['+iContactId+'][contacts]');
		
		
		if(
			oFreeCheckbox &&
			oGuideCheckbox
		){
			// Alle einzelnen Checkboxen toggeln
			var bShow = false;

			if(
				!oFreeCheckbox.checked &&
				oGuideCheckbox.checked
			){
				bShow = true;
			}
	
			this.togglePositionCheckboxes(aData, iContactId, bShow);
		}
	},
	
	// Alle Checkboxen der Positionen toggeln
	togglePositionCheckboxes : function(aData, iContactId, bShow){
		
				
		var oFreeAllCheckbox = $('save['+this.hash+']['+aData.id+'][detail_free_all][group]['+iContactId+'][contacts]');

		if(oFreeAllCheckbox){
			var aCheckboxes = this.getPositionCheckboxes(aData, iContactId);
			
			for(var iCounter=0; iCounter < aCheckboxes.length; iCounter++){
				if(aCheckboxes[iCounter]){

					var bForceShow = true;
					if(oFreeAllCheckbox.checked){
						// Boxen dürfen nur angezeigt sein, wenn "Alles kostenfrei?" deselectiert ist!
						bForceShow = false;
					}else if(!bShow ){
						bForceShow = false;
					}
			
					if(bForceShow){
						aCheckboxes[iCounter].up('.GUIDialogRow').show();
					}else{
						aCheckboxes[iCounter].up('.GUIDialogRow').hide();
					}
					
				
						//aCheckboxes[iCounter].checked = false;
					
				}
			}
		}
		
		
	},
	
	// prüft ob irgendeine PositionsCheckbox aktiv ist
	checkPositionCheckboxes : function(aData, iContactId){
		var aCheckboxes = this.getPositionCheckboxes(aData, iContactId);
		
		var bActive = false;
		
		for(var iCounter=0; iCounter < aCheckboxes.length; iCounter++){
			if(
				aCheckboxes[iCounter] &&
				aCheckboxes[iCounter].checked	
			){
				bActive = true;
				break;
			}
		}
		
		
		return bActive;
	},
	
	// Liefert ale Positionscheckboxen zu einer ContactId
	getPositionCheckboxes : function(aData, iContactId){
		var aCheckboxes = [];
		aCheckboxes[0] = $('save['+this.hash+']['+aData.id+'][detail_free_course][group]['+iContactId+'][contacts]');
		aCheckboxes[1] = $('save['+this.hash+']['+aData.id+'][detail_free_accommodation][group]['+iContactId+'][contacts]');
		aCheckboxes[2] = $('save['+this.hash+']['+aData.id+'][detail_free_course_fee][group]['+iContactId+'][contacts]');
		aCheckboxes[3] = $('save['+this.hash+']['+aData.id+'][detail_free_accommodation_fee][group]['+iContactId+'][contacts]');
		aCheckboxes[4] = $('save['+this.hash+']['+aData.id+'][detail_free_transfer][group]['+iContactId+'][contacts]');

		return aCheckboxes;
	},
	
	// Holt sich die aktuelle Object ID einer Group Flag Checkbox
	getContactIdByContactField : function(oInput){
		var iContactId = 0;
		
		var aMatch = oInput.id.match(/^save\[(.*)\]\[(.*)\]\[(.*)\]\[(.*)\]\[(.*)\]\[(.*)\]$/);
		if(
			aMatch &&
			aMatch[5]
		){
			iContactId = aMatch[5];
		}
		return iContactId;
	},
	
	// siehe parent
	addJoinedObjectContainerHook : function(oRepeat, iContactId){
		var aData = {};
		aData.id = this.sCurrentDialogId; 

		// Checkboxenspalten des neuen Containers verbergen
		var aCheckboxes = this.getPositionCheckboxes(aData, iContactId);

		// "Alles Umsonst?" Checkbox muss auch verborgen werden
		aCheckboxes[aCheckboxes.length] = $('save['+this.hash+']['+aData.id+'][free_all][group]['+iContactId+'][contacts]');

		for(var iCounter=0; iCounter < aCheckboxes.length; iCounter++){
			if(
				aCheckboxes[iCounter] &&	
				aCheckboxes[iCounter].up('.GUIDialogRow')
			){
				aCheckboxes[iCounter].up('.GUIDialogRow').hide();
			}
		}

	},
	
	/**
	 * Input holen die über die createRow erstellt wurde
	 */
	getInputElement : function(sDialogId, sDbColumn, sDbAlias)
	{
		if(!sDbAlias)
		{
			sDbAlias = 'cdb1';
		}
		
		var sElementId	= 'save['+this.hash+']['+sDialogId+']['+sDbColumn+']['+sDbAlias+']';
		var oElement	= $(sElementId);
		
		return oElement;
	}

});