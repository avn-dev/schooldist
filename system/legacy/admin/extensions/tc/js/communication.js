
var CoreCommunication = {
	
	oGui: null,
	sCurrentField: '',
	aCurrentFields: [],
	
	requestCallbackHook: function(oData, sHash)
	{
		if(
			oData &&
			(
				oData.action == 'openDialog' ||
				oData.action == 'reloadDialogTab' ||
				oData.action == 'saveDialogCallback'
			) &&
			oData.data.action == 'communication'
		) {

			if(!oData.data.communication) {
				return;
			}

			this.oGui = aGUI[sHash];
			this.aCurrentFields = oData.data.communication.recipient_input_fields;
			this.oCommunicationTranslations = oData.data.communication.translations;

			
			// Improvisiertes Überladen von GUI's toggleDialogTabHook()
			this.oGui.toggleDialogTabHook = this.toggleDialogTabHook.bindAsEventListener(this);
			
			if(oData.action == 'reloadDialogTab') {
				this.sCurrentField = this.aCurrentFields[0];
			}
			
			$$('.template_select_communication').each(function(oSelect) {
				oSelect.stopObserving('change');
				
				oSelect.observe('change', function(oEvent, sId) {
					this.reloadCommunicationDialogTab(sId);
				}.bindAsEventListener(this, oData.data.id));
				
			}.bind(this));
			
			// Empfänger-Multiselects observieren
			var oRecipientSelects = $$('#dialog_' + oData.data.id + '_' + sHash + ' .recipientSelect');
			oRecipientSelects.each(function(oElement) {
				oElement.observe('change', function() {

					var sName = oElement.name.replace(/save\[/, '').replace(/\]\[\]/, '').replace(/\]\[/, '\\]\\[');

					var oRegexp = new RegExp('\\['+sName+'\\]', 'g');

					var sIdPrefix = oElement.id.replace(oRegexp, '');

					var aParts = sName.split(/\\]/);
					sName = aParts[0];

					aParts = sName.split('_');
					aParts.pop();
					
					sName = aParts.join('_');

					this.updateCommunicationRecipients(sIdPrefix, sName, oElement);

				}.bindAsEventListener(this, oElement));
			}.bind(this));
			
			// Spezielle TinyMCE-Felder intialisieren
			var sCurrentField = this.sCurrentField;
			tinyMCE.init({
				mode : "exact",
				elements : this.aCurrentFields.join(','),
				theme : "modern",
				valid_elements : "span[class|style|title]",
				menubar: false,
				toolbar: false,
				statusbar: false,
				width: 600,
				height: 40,
				setup: function(oEditor) {

					oEditor.on('focus', function(e) {
						var sField = e.target.id;

						if(sCurrentField != sField) {
							if(sCurrentField) {
								$j('#recipient_'+sCurrentField).toggle('blind', {direction: 'up'}, 500);
								//Effect.BlindUp('recipient_'+sCurrentField, { duration: 0.5 });
							}

							$j('#recipient_'+sField).toggle('blind', {direction: 'down'}, 500);
							//Effect.BlindDown('recipient_'+sField, { duration: 0.5 });
							sCurrentField = sField;
						}

					});

				},
				init_instance_callback : function(oEditor) {
					document.getElementById(oEditor.id+'_ifr').style.height= '40px';
				},

			});

			// Alle Markierungen und ggf. all ihre SubObjects auswählen beim Laden des Templates
			if(oData.action == 'reloadDialogTab') {
				var aCheckboxes = $$('.communication_flag_checkbox');

				aCheckboxes.each(function(oCheckbox) {

					oCheckbox.observe('change', function(oCheckbox) {

						var oCheckboxRow = oCheckbox.up('.GUIDialogRow');
						var aMultiselectRows = oCheckboxRow.nextSiblings();

						aMultiselectRows.each(function(oCheckbox, oMultiselectRow) {

							// Verstecktes Select, welches durch das jQuery-Multiselect versteckt wird
							var oSelect = oMultiselectRow.down('.jQm');

							if(oCheckbox.checked) {
								oMultiselectRow.show();
								$j(oSelect).multiselect('addAllOptions')
							} else {
								oMultiselectRow.hide();
								$j(oSelect).multiselect('removeAllOptions')
							}

						}.bind(this, oCheckbox));
					}.bind(this, oCheckbox));

					// Wenn keine SubObjects vorhanden, dann nicht auswählen (readonly wird im PHP gesetzt)
					if(!oCheckbox.readOnly) {
						oCheckbox.checked = true;
						aGUI[sHash]._fireEvent('change', oCheckbox);
					}

				}.bind(this));
			}

			this.sCurrentField = sCurrentField;

		}
		
	},
	
	updateCommunicationRecipients: function(sIdPrefix, sName, oSelect) {

		var sEditor = 'save_'+sName;

		var aCurrent = $H();
		var aRemaining = $H();
		var aItems = $H();

		var oTinyMCE = tinyMCE.get(sEditor);

		if(!oTinyMCE) {
			return;
		}

		var sContent = oTinyMCE.getContent();
		sContent = sContent.replace(/<p>/, '').replace(/<\/p>/, '');

		var oRegex = /<span.*?title="(.*?)".*?>(.*?)<\/span>/gi;
		var aMatches;

		var sRemaining = sContent;
		while(aMatches = oRegex.exec(sContent)) {
			sRemaining = sRemaining.gsub(aMatches[0], '');
			aCurrent.set(aMatches[1], aMatches[2]);
		}

		sRemaining = sRemaining.replace(/;\s*;/g, '');
		var aParts = sRemaining.split(/\s*;\s*/g);

		aParts.each(function(sPart) {
			sPart = sPart.strip();
			if(sPart != '') {
				aRemaining.set(sPart, '');
				aItems.set(sPart, '');
			}
		});

		var aRecipientSelects = $j(oSelect).closest('fieldset.simple_editor_container').find('select.recipientSelect');
		$j(aRecipientSelects).find('option:selected').each(function() {
			aItems.set($j(this).attr('value'), $j(this).html());
		});

		sContent = '<p>';
		aItems.each(function(oItem) {
			if(oItem.key && oItem.value) {
				sContent += '<span title="'+oItem.key+'" style="text-decoration: underline;">'+oItem.value+'</span>; ';
			} else {
				sContent += ''+oItem.key+'; ';
			}
		});

		sContent += '</p>';

		oTinyMCE.setContent(sContent, {format : 'raw'});

	},

	reloadCommunicationDialogTab: function(sId)
	{
		
		this.aCurrentFields.each(function(sElement) {
			this.oGui.removeEditor(sElement);
		}.bind(this));
		
		if(this.oGui.sCurrentDialogId === null) {
			this.oGui.sCurrentDialogId = Object.keys(this.oGui.aLastDialogTab)[0];
		}
		
		var iCurrentDialogTab = this.oGui.aLastDialogTab[this.oGui.sCurrentDialogId];
		this.oGui.reloadDialogTab(sId, iCurrentDialogTab);
		
	},
	
	closeDialogs: function(sDialogID, sHash)
	{
		// HTML Editoren entfernen
		var aEditorFields = $$('#dialog_'+sDialogID+'_'+sHash+' .simple_editor');

		var oGui = aGUI[sHash];
		aEditorFields.each(function(oEditor) {
			oGui.removeEditor(oEditor.id);
		}.bind(oGui));

	},
	
	/**
	 * Tab und TabArea mit in den Request packen, da man ja wissen muss, in welchem Tab man sich befindet
	 */
	prepareAction: function(aElement, sHash) {

		if(aElement.task == 'saveDialog') {

			if(!aElement.request_data) {
				aElement.request_data = '';
			}

			urlParams = new URLSearchParams(aElement.request_data);

			if(aGUI[sHash].sCurrentDialogId === null) {
				aGUI[sHash].sCurrentDialogId = Object.keys(aGUI[sHash].aLastDialogTab)[0];
			}

			var iCurrentDialogTab = aGUI[sHash].aLastDialogTab[aGUI[sHash].sCurrentDialogId];

			var iTabAreaIndex = null;
			var oCurrentTabArea = $$('#dialog_' + aGUI[sHash].sCurrentDialogId + '_' + sHash + ' #tabBody_' + iCurrentDialogTab + '_' + aGUI[sHash].sCurrentDialogId + '_' + sHash + ' .tab_area li.active a').first();

			if(oCurrentTabArea) {
				var oRegExp = new RegExp('tab_area_li_' + iCurrentDialogTab + '_([0-9].*)_([0-9].*)');
				iTabAreaIndex = oCurrentTabArea.id.replace(oRegExp, '$2');
			}

			urlParams.set('save[current_tab]', iCurrentDialogTab);
			urlParams.set('save[current_tabarea]', iTabAreaIndex);

			aElement.request_data = '&'+urlParams.toString();

		}

		return aElement;
		
	},
	
	toggleDialogTabHook: function(iTabId, iDialogId) {

		var sCurrentDialogId = this.oGui.sCurrentDialogId;

		// Nur bei Kommunikationsdialog
		if(sCurrentDialogId && sCurrentDialogId.indexOf('COMMUNICATION_') !== 0) {
			return;
		}

		var oDialogMessages = $('dialog_messages_' + sCurrentDialogId + '_' + this.oGui.hash);

		if(oDialogMessages) {
			var oButtonContainer = oDialogMessages.next();
		}
		
		var oTabHeader = $j('#tabHeader_'+parseInt(iTabId)+'_' + sCurrentDialogId + '_' + this.oGui.hash);
		
		if(
			oTabHeader && 
			(
				oTabHeader.hasClass('communication_notes') ||
				oTabHeader.hasClass('communication_history') ||
				oTabHeader.hasClass('communication_placeholder')
			) &&
			oButtonContainer
		) {
			$j(oButtonContainer).hide();
		} else {
			$j(oButtonContainer).show();
		}

		this.oGui.resizeDialogSize({id: sCurrentDialogId});

	}
	
};


/*
 * V5-Kompatibilitätsmodus
 */
function executeHook_communication_gui2_request_callback_hook(sHook, oData, sHash) {
	CoreCommunication.requestCallbackHook(oData, sHash);
	return oData;
}

function executeHook_communication_gui2_close_all_editors(sHook, sDialogID, sHash) {
	CoreCommunication.closeDialogs(sDialogID, sHash);
}

function executeHook_communication_gui2_prepare_action(sHook, aElement, sHash) {
	aElement = CoreCommunication.prepareAction(aElement, sHash);
	return aElement;
}

oWdHooks.addHook('gui2_request_callback_hook', 'communication');
oWdHooks.addHook('gui2_close_all_editors', 'communication');
oWdHooks.addHook('gui2_prepare_action', 'communication');
