
var sCommunicationHash;
var sCurrentRecipientField = 'to';
var oCommunicationRecipientsObserver = null;

function checkCommunicationRecipients(oEvent) {

	if(oEvent) {
		oCommunicationRecipientsObserver = oEvent;
	}

	if(oCommunicationRecipientsObserver){
		clearTimeout(oCommunicationRecipientsObserver);
	}

	oCommunicationRecipientsObserver = setTimeout(this.loadTable.bind(this, false, this.hash), 500);
	
}

/**
 *
 */
function reloadCommunicationDialogTab(sHash, sId) {

	var aElements = ['save_to', 'save_cc', 'save_bcc'];

	aElements.each(function(sElement) {
		aGUI[sHash].removeEditor(sElement);
	});

	aGUI[sHash].reloadDialogTab(sId, 0);

}

function reloadIdentitySignatures(sHash, sId){

	var oGui = aGUI[sHash];

	var sParam	= '&task=updateIdentity';

	sParam += '&'+$('dialog_form_'+sId+'_'+sHash).serialize();

	oGui.request(sParam);
}

/**
 * Übernimmt die Auswahl und die manuellen Eingaben in das Eingabefeld
 */
function updateCommunicationRecipients(sIdPrefix, sName) {

	var sEditor = 'save_'+sName;

	var aCurrent = $H();
	var aRemaining = $H();
	var aItems = $H();
	var aSelects = new Array(
							'student',
							'emergency',
							'other',
							'provider',
							'agency',
							'teacher'
						);

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
	
	aSelects.each(function(sSelect) {

		var oSelect = $(sIdPrefix+'['+sName+'_'+sSelect+']');

		if(oSelect) {
			$A(oSelect.options).each(function(oOption) {
				if(oOption.selected) {
					aItems.set(oOption.value, oOption.text);
				}
			});
		}

	});

	sContent = '';
	aItems.each(function(oItem) {
		if(
			oItem.key &&
			oItem.value
		) {
			sContent += '<span title="'+oItem.key+'" style="text-decoration: underline;">'+oItem.value+'</span>; ';
		} else {
			sContent += ''+oItem.key+'; ';
		}
	});

	//Löst das Problem des nicht angezeigten Cursors in Google Chrome
	if(sContent !== '') {
		sContent = '<p>'+sContent+'</p>';
	}

	oTinyMCE.setContent(sContent, {format : 'raw'});

}

function executeHook_tscommunication_gui2_close_all_editors(sHook, sDialogID, sHash) {

	// HTML Editoren entfernen
	var aEditorFields = $$('#dialog_'+sDialogID+'_'+sHash+' .simple_editor');

	var oGui = aGUI[sHash];

	aEditorFields.each(function(oEditor) {
		oGui.removeEditor(oEditor.id);
	}.bind(oGui));

}

function executeHook_tscommunication_gui2_request_callback_hook(sHook, mInput, mData) {

	var sCommunicationHash = mData;
	var oGui = aGUI[sCommunicationHash];

	if(
		mInput &&
		(
			mInput.action == 'openDialog' ||
			mInput.action == 'reloadDialogTab' ||
			mInput.action == 'saveDialogCallback'
		) &&
		mInput.data.action == 'communication'
	) {

		//var sTemplateSelect = 'save['+sCommunicationHash+']['+mInput.data.id+'][template_id]';
		//var oTemplateSelect = $(sTemplateSelect);
		//Event.observe(oTemplateSelect, 'change', function(oEvent, sId) {
		//	reloadCommunicationDialogTab(sCommunicationHash, sId);
		//}.bindAsEventListener(sCommunicationHash, mInput.data.id));

		// 2. TemplateSelect
		$$('#tabs_content_'+mInput.data.id+'_'+sCommunicationHash+' .template_select_communication').each(function(oSelect){
			oSelect.stopObserving('change');

			Event.observe(oSelect, 'change', function(oEvent, sId) {
				reloadCommunicationDialogTab(sCommunicationHash, sId);
			}.bindAsEventListener(sCommunicationHash, mInput.data.id));

			var sIdentitySelectId	= oSelect.id.replace('template_id', 'identity_id');
			var oIdentitySelect		= $(sIdentitySelectId);

			if(oIdentitySelect){
				oIdentitySelect.stopObserving('change');
				Event.observe(oIdentitySelect, 'change', function(oEvent, sId) {
					reloadIdentitySignatures(sCommunicationHash, mInput.data.id);
				}.bindAsEventListener(sCommunicationHash, mInput.data.id));
			}

		}.bind(this));

		$$('.jQm').each(function(oElement) {
			Event.observe(oElement, 'change', function() {

				var sName = this.name.replace(/save\[/, '').replace(/\]\[\]/, '');

				var oRegexp = new RegExp('\\['+sName+'\\]', 'g');
				var sIdPrefix = this.id.replace(oRegexp, '');

				var aParts = sName.split(/_/);
				sName = aParts[0];

				updateCommunicationRecipients(sIdPrefix, sName);

			}.bindAsEventListener(oElement));
		});

		tinyMCE.init({
			// General options
			mode : "exact",
			elements : "save_to,save_cc,save_bcc",
			theme : "modern",
			valid_elements : "span[class|style|title]",
			menubar: false,
			toolbar: false,
			statusbar: false,
			width: 750,
			height: 40,
			setup: function(oEditor) {
				oEditor.on('focus', function(e) {
					var sField = e.target.id.replace(/save_/, '');
					if(sCurrentRecipientField != sField) {
						if(sCurrentRecipientField) {
							$j('#recipient_'+sCurrentRecipientField).hide();
						}
						$j('#recipient_'+sField).show();
						sCurrentRecipientField = sField;
					}
				});
			},
			init_instance_callback : function(oEditor) {
				document.getElementById(oEditor.id+'_ifr').style.height= '40px';
			}
		});

	} else if(
		mInput &&
		mInput.action == 'updateIdentityCallback'
	) {
		if(
			mInput.dialog_id &&
			mInput.aSignatures
		) {
			var aSignatures = mInput.aSignatures;
			var sDialogId	= mInput.dialog_id;
			var oSignatureInput;

			var sKey;
			var sText;

			$H(aSignatures).each(function(aArray){

				sKey = aArray[0];
				sText = aArray[1];

				oSignatureInput = $('save['+sCommunicationHash+']['+sDialogId+'][signature]['+sKey+']');
				if(oSignatureInput){

					if(oSignatureInput.hasClassName('GuiDialogHtmlEditor')){
						var oTiny = tinyMCE.get(oSignatureInput.id);
						if(oTiny){
							oTiny.setContent(sText);
						}
					}else{
						oSignatureInput.value = sText;
					}

				}
			});

		}
	}

	return mInput;
}

oWdHooks.addHook('gui2_request_callback_hook', 'tscommunication');
oWdHooks.addHook('gui2_close_all_editors', 'tscommunication');