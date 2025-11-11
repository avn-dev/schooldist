/*
 * Util Klasse
 */
var AdminPdfLayoutsGui = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {

		// RequestCallback der Parent Klasse
		$super(aData);

		// Oberer Dialog
		if(
			aData.action == 'saveDialogCallback' ||
			aData.action == 'openDialog'
		) {

			var sSaveTag = 'save['+this.hash+']['+aData.data.id+']';

			$$('.TemplateTypeToggleElement').each(function(oCheckbox) {

				if(oCheckbox.type == 'checkbox') {

					this.toggleElementData(oCheckbox, sSaveTag);

					Event.stopObserving(oCheckbox, 'click');
					Event.observe(oCheckbox, 'click', function(){
						this.toggleElementData(oCheckbox, sSaveTag);
					}.bind(this));

				}

			}.bind(this))

			var oFormatSelect = $(sSaveTag+'[page_format]');
			var oFormatInput1 = $(sSaveTag+'[page_format_width]');
			var oFormatInput2 = $(sSaveTag+'[page_format_height]');

			if(oFormatSelect){
				Event.observe(oFormatSelect, 'change', function(){
					this.changeFormat(oFormatSelect, sSaveTag);
				}.bind(this));
			}

			if(oFormatInput1){
				Event.observe(oFormatInput1, 'keyup', function(){
					this.changeFormatSelect(sSaveTag);
				}.bind(this));
			}

			if(oFormatInput2){
				Event.observe(oFormatInput2, 'keyup', function(){
					this.changeFormatSelect(sSaveTag);
				}.bind(this));
			}
		
			// unterer Dialog
			var oTypeSelect = $(sSaveTag+'[element_type]');
			if(oTypeSelect){

				this.toogleTemplateTypeElementType(oTypeSelect, sSaveTag);
				this.toggleEditableCheckbox(oTypeSelect, sSaveTag);
				Event.observe(oTypeSelect, 'change', function(){
					this.toogleTemplateTypeElementType(oTypeSelect, sSaveTag);
					this.toggleEditableCheckbox(oTypeSelect, sSaveTag);
				}.bind(this));

			}

		}

	},

	// Blendet die Editierbar Checkbox nur ein bei HTML oder Date
	toggleEditableCheckbox : function(oSelect, sSaveTag){

		var oRow1 = $(sSaveTag+'[editable]').up('.GUIDialogRow');

		if(
			oSelect.value == 'html' ||
			oSelect.value == 'date' ||
			oSelect.value == 'text' ||
			oSelect.value == 'main_text'
		) {
			oRow1.show();
		} else {
			oRow1.hide();
		}

	},

	toogleTemplateTypeElementType : function(oSelect, sSaveTag){

		var oRow1 = $(sSaveTag+'[img_width]').up('.GUIDialogRow');
		var oRow2 = $(sSaveTag+'[img_height]').up('.GUIDialogRow');
		var oRow3 = $(sSaveTag+'[page]').up('.GUIDialogRow');
		var oRow4 = $(sSaveTag+'[y]').up('.GUIDialogRow');
		var oRow5 = $(sSaveTag+'[x]').up('.GUIDialogRow');
		var oRow6 = $(sSaveTag+'[element_width]').up('.GUIDialogRow');
		var oRow7 = $(sSaveTag+'[wysiwyg]').up('.GUIDialogRow');

		if($F(oSelect) == 'main_text') {
			
			$(sSaveTag+'[page]').value = 'first';
			
			oRow3.hide();
			oRow5.hide();
			oRow6.hide();

		} else {

			oRow3.show();
			oRow5.show();
			oRow6.show();

		}

		if(
			$F(oSelect) == 'main_text' ||
			$F(oSelect) == 'html'
		) {
			oRow7.show();
		} else {
			oRow7.hide();
		}

		if($F(oSelect) == 'img') {
			oRow1.show();
			oRow2.show();			
		} else {
			oRow1.hide();
			oRow2.hide();			
		}

	},

	changeFormat : function(oSelect, sSaveTag) {

		var iWidth = 0;
		var iHeight = 0;

		if($F(oSelect) == 'a4'){
			iWidth = 210;
			iHeight = 297;
		} else if($F(oSelect) == 'a4_q'){
			iWidth = 297;
			iHeight = 210;
		} else if($F(oSelect) == 'letter'){
			iWidth = 216;
			iHeight = 279;
		} else if($F(oSelect) == 'letter_q'){
			iWidth = 279;
			iHeight = 216;
		}

		$(sSaveTag+'[page_format_width]').value = iWidth;
		$(sSaveTag+'[page_format_height]').value = iHeight;

	},

	changeFormatSelect : function(sSaveTag){

		$(sSaveTag+'[page_format]').value = 0;

	},

	toggleElementData : function(oCheckbox, sSaveTag){

		if(!oCheckbox){
			return false;
		}

		var bValue = oCheckbox.checked;

		var sName = oCheckbox.name;
		sName = sName.replace(/save\[element_/, '');
		sName = sName.replace(/\]/, '');

		var sIdPrefix = oCheckbox.id;
		var sRegex = new RegExp('\\[element_'+sName+'\\]');
		sIdPrefix = sIdPrefix.replace(sRegex, '');

		// Alle Parameter dieses Elements
		var aInputs = new Array(
			'x',
			'y',
			'font_type',
			'font_style',
			'font_size',
			'font_spacing'
			);

		aInputs.each(function(sInput) {

			sInput = sIdPrefix+'[element_'+sName+'_'+sInput+']';

			var oInput = $(sInput);
			if(oInput) {
				if(bValue) {
					oInput.up('.GUIDialogRow').show();
				} else {
					oInput.up('.GUIDialogRow').hide();
				}
			}
		});

		var oRow;

		if(oCheckbox.id == sSaveTag+'[element_text1]') {
			if(bValue){
				oRow = $(sSaveTag+'[element_inquirypositions]').up('.GUIDialogRow');
				oRow.show();
				oRow.previous('h3').show();

				oRow = $(sSaveTag+'[element_text2]').up('.GUIDialogRow');
				oRow.show();
				oRow.previous('h3').show();

				oRow = $(sSaveTag+'[element_inquirypositions]').up('.GUIDialogRow');
				oRow.show();
				oRow.previous('h3').show();
			} else {
				oRow = $(sSaveTag+'[element_inquirypositions]').up('.GUIDialogRow');
				oRow.hide();
				oRow.previous('h3').hide();

				oRow = $(sSaveTag+'[element_text2]').up('.GUIDialogRow');
				oRow.hide();
				oRow.previous('h3').hide();

				oRow = $(sSaveTag+'[element_inquirypositions]').up('.GUIDialogRow');
				oRow.hide();
				oRow.previous('h3').hide();
			}
		}

		return true;

	}


});

// ENDE
