/*
 * Util Klasse
 */
var Flexibility = Class.create(ATG2, {

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
			this.prepareEvent(aData.data);
			//this.generateSectionId(aData.data);
		}

	},
	
	prepareEvent : function (aDialogData) {
		// Configuration
		// var aToggleElements = new Array('title', 'description', 'placeholder', 'required', 'validate_by', 'regex', 'error');
		// var aBundled = new Array();
		// var aInputOptions = new Array(aToggleElements[0], aToggleElements[1], aToggleElements[2], aToggleElements[3], aToggleElements[4], aToggleElements[6]);
		// aBundled[0] = aInputOptions;
		// var aTextareaOptions = new Array(aToggleElements[0], aToggleElements[1], aToggleElements[2], aToggleElements[3]);
		// aBundled[1] = aTextareaOptions;
		// var aCheckboxOptions = new Array(aToggleElements[0], aToggleElements[1], aToggleElements[2]);
		// aBundled[2] = aCheckboxOptions;
		// var aHeadlineOptions = new Array(aToggleElements[0]);
		// aBundled[3] = aHeadlineOptions;
		// 	var aDateOptions = new Array(aToggleElements[0], aToggleElements[1], aToggleElements[2], aToggleElements[3]);
		// aBundled[4] = aDateOptions;
		// var aDropdownOptions = new Array(aToggleElements[0], aToggleElements[1], aToggleElements[2], aToggleElements[3]);
		// aBundled[5] = aDropdownOptions;
		// aBundled[6] = aTextareaOptions;
		// aBundled[7] = aDropdownOptions;
		// aBundled[8] = aDropdownOptions;

		var aToggleElements = ['title', 'visible', 'description', 'placeholder', 'required', 'validate_by', 'regex', 'error'];

		var aBundled = [];
		aBundled[0] = aToggleElements;
		aBundled[1] = ['title', 'visible', 'description', 'placeholder', 'required'];
		aBundled[2] = ['title', 'visible', 'description', 'placeholder'];
		aBundled[3] = ['title'];
		aBundled[4] = aBundled[1];
		aBundled[5] = aBundled[1];
		aBundled[6] = aBundled[1];
		aBundled[7] = aBundled[1];
		aBundled[8] = aBundled[1];
		aBundled[9] = ['title', 'description', 'placeholder'];

		// ----------------------------------

		this.aToggleElements = aToggleElements;
		this.aBundled		 = aBundled;

		var oSelect = $('save['+this.hash+']['+aDialogData.id+'][type]');

		if(oSelect){
			this.oSelect = oSelect;
			this.aDialogData = aDialogData;
			// Bei "edit form" müssen die Felder ein/ausgeblendet werden
			this.displayFormElements();
			// Bei onchange müssen die Felder ein/ausgeblendet werden
			oSelect.observe('change', this.displayFormElements.bind(this));

			// Tab für Drowdown optionen aus/einblenden
			this.toggleOptionTab(aDialogData, oSelect);
			Event.observe(oSelect, 'change', function() {
				this.toggleOptionTab(aDialogData, oSelect);
			}.bind(this));

			// Tab für Drowdown optionen aus/einblenden
			this.toggleChildFieldsTab(aDialogData, oSelect);
			Event.observe(oSelect, 'change', function() {
				this.toggleChildFieldsTab(aDialogData, oSelect);
			}.bind(this));

			// Regex felder ein/ausblenden
			var oSelectValidate = $('save['+this.hash+']['+aDialogData.id+'][validate_by]');
			if(oSelectValidate){
				this.toggleRegexFields(aDialogData, oSelectValidate);
				Event.observe(oSelectValidate, 'change', function() {
					this.toggleRegexFields(aDialogData, oSelectValidate);
				}.bind(this));
			}
		}

		// Geht nicht über dependency_visibility, da section_id nicht in Dialog-Definition existiert
		var oSectionSelect = this.getDialogSaveField('section_id'); // Select oder hidden
		var oVisibleCheckbox = this.getDialogSaveField('visible');
		var oPlaceholderDiv = $j('.div_placeholder');
		oSectionSelect.on('change', function() {
			if(
				oVisibleCheckbox.is(':visible') &&
				aDialogData.sections_without_list.indexOf(parseInt(oSectionSelect.val())) === -1
			) {
				oVisibleCheckbox.closest('.GUIDialogRow').show();
			} else {
				oVisibleCheckbox.closest('.GUIDialogRow').hide();
			}

			if(
				aDialogData.sections_with_placeholders &&
				aDialogData.sections_with_placeholders.length > 0
			) {
				if(aDialogData.sections_with_placeholders.indexOf(parseInt(oSectionSelect.val())) !== -1) {
					oPlaceholderDiv.show();
				} else {
					oPlaceholderDiv.hide();
				}
			}
		}).change();

	},

	// Felder für Regulären Ausdruch toggeln
	toggleRegexFields : function(aDialogData, oSelect){

		var oInputRegex = $('save['+this.hash+']['+aDialogData.id+'][regex]');
			
		if(oSelect.value == 'REGEX'){
			// Regex
			
			if(
				oInputRegex
			){
				oInputRegex.up('.GUIDialogRow').show();
			}
		}else{
			if(
				oInputRegex
			){
				oInputRegex.up('.GUIDialogRow').hide();
			}
		}
	},


	// Tab für Optionen aus/einblenden
	toggleOptionTab : function(aDialogData, oSelect){

		// Tab
		var oTabLi = '';
		if($('tabHeader_1_'+aDialogData.id+'_'+this.hash)){
			oTabLi  = $('tabHeader_1_'+aDialogData.id+'_'+this.hash);
		}

		if(
			(
				oSelect.value == '5' ||
				oSelect.value == '8'
			) &&
			oTabLi != ''
		){
			oTabLi.show();
		}else if(
			oTabLi != ''
		){
			oTabLi.hide();
		}

	},

	// Tab für Optionen aus/einblenden
	toggleChildFieldsTab : function(aDialogData, oSelect){

		// Tab
		var oTabLi = '';
		if($('tabHeader_2_'+aDialogData.id+'_'+this.hash)){
			oTabLi  = $('tabHeader_2_'+aDialogData.id+'_'+this.hash);
		}

		if(
			oSelect.value == '9' &&
			oTabLi != ''
		){
			oTabLi.show();
		}else if(
			oTabLi != ''
		){
			oTabLi.hide();
		}

	},

	displayFormElements : function () {

		var iSelected = this.oSelect.selectedIndex;
		var iValue	  = this.oSelect.options[iSelected].value;
		// Text, Checkbox etc. bezogene Felder
		var aBundled  = this.aBundled;

		// alle Elemente
		var aToggleElements = this.aToggleElements;

		var sHash	 = this.hash;
		var aDialogData = this.aDialogData;

		var aBundledPos = aBundled[iValue];

		if( 0 < aBundledPos.length ){
			var oElement;
			var oDiv;
			
			// alle Felder ausblenden
			for(i=0; i<aToggleElements.length; i++){
				oElement = $('save['+sHash+']['+aDialogData.id+']['+aToggleElements[i]+']');

				if(oElement){
					oDiv = oElement.up('.GUIDialogRow');
					if(oDiv){
						oDiv.hide();
					}
				}
			}

			// nötige Felder einblenden
			for(i=0; i<aBundledPos.length; i++){
				oElement = $('save['+sHash+']['+aDialogData.id+']['+aBundledPos[i]+']');
				if(oElement){
					oDiv = oElement.up('.GUIDialogRow');
					if(oDiv){
						oDiv.show();
					}
				}
			}

		}
	},

	// Es muss immer nach irgendetwas gefiltert werden da sonst das drag-drop der Zeilen nicht funktioniert beim speichern
	additionalFilterHook : function(sParam){

		if(!sParam.match(/section_filter/)){
			//sParam += '&filter[search]=&filter[section_filter]=xNullx';
			sParam += '&filter[search]=&filter[section_filter]=-1';

		}

		return sParam;
	}

});
