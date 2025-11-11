var RequestConvertGui = Class.create(StudentlistGui, {

	requestCallbackHook: function($super, aData) {
		$super(aData);

		if(
			aData &&
			aData.data &&
			aData.action
		) {
			if(
				aData.action === 'openDialog' &&
				aData.data.action === 'convert_offer_to_inquiry'
			) {
				// Observer manuell setzen auf Selects zum Konvertieren einer Buchung
				this.setConvertDialogObserver(aData.data);
			} else if(aData.action === 'closeDialogAndReloadTable') {
				var oParentGui = this.getOtherGuiObject(this.sParentGuiHash);
				oParentGui.loadTable();
			}
		}
	},

	setConvertDialogObserver : function(aData) {

		this.oDocumentSelect = $('save['+this.hash+']['+this.sCurrentDialogId+'][document_type]');
		this.oTemplateSelect = $('save['+this.hash+']['+this.sCurrentDialogId+'][template_id]');
		this.oNumberrangeSelect = $('save['+this.hash+']['+this.sCurrentDialogId+'][numberrange_id]');
		this.oInboxSelect = $('save['+this.hash+']['+this.sCurrentDialogId+'][inbox_id]');
		this.iInboxId = aData.default_inbox_id;
		
		if(
			this.oDocumentSelect &&
			this.oTemplateSelect &&
			this.oNumberrangeSelect &&
			aData.templates &&
			aData.numberranges 
		) {
			this.oDocumentSelect.observe('change', function() {
				this.reloadConvertSelectOptions(this.oTemplateSelect, aData.templates);
				this.reloadConvertSelectOptions(this.oNumberrangeSelect, aData.numberranges);
			}.bind(this));

			if(this.oInboxSelect) {
				this.oInboxSelect.observe('change', function() {
					this.reloadConvertSelectOptions(this.oTemplateSelect, aData.templates);
					this.reloadConvertSelectOptions(this.oNumberrangeSelect, aData.numberranges);
				}.bind(this));
			}

			this._fireEvent('change', this.oDocumentSelect);
		}
		
	},

	reloadConvertSelectOptions: function(oDependencySelect, aSelectOptions) {
		var iInboxId, sDocument = $F(this.oDocumentSelect);

		if(this.oInboxSelect) {
			iInboxId = $F(this.oInboxSelect);
		} else {
			// Default-Fall, wenn das Select nicht da ist
			iInboxId = this.iInboxId;
		}

		if(aSelectOptions[sDocument]) {
			var aOptions = aSelectOptions[sDocument][iInboxId];

			oDependencySelect.update();

			if(aOptions) {
				$H(aOptions).each(function(oOption) {
					if(oOption.key > 0) {
						oDependencySelect.insert({
							bottom: new Element('option',
							{
								value: oOption.key
							}).update(oOption.value)
						});
					}
				}.bind(this));
			}
		}
	}
});