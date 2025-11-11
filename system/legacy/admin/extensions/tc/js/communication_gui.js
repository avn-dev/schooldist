
var CommunicationGui = Class.create(ATG2, {

	requestCallbackHook: function($super, oData) {
		
		$super(oData);

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

			this.sFieldPrefix = 'save['+this.hash+']['+oData.data.id+']';

			var oCorrespondantSelect = $(this.sFieldPrefix + '[notice_correspondant_key][attribute]');
			var oCorrespondantInput = $(this.sFieldPrefix + '[notice_correspondant_value][attribute]');

			// Gesprächspartner-Feld umschalten beim Anlegen von Notizen
			if(oCorrespondantSelect && oCorrespondantInput) {

				// Bei Default-Eintrag ebenso Select ausblenden
				oData.aNoticeHideCorrespondantValue.unshift(0);

				var changeCorrespondantSelectEvent = this.changeCorrespondantSelectEvent.bind(this, oData, oCorrespondantSelect, oCorrespondantInput);
				oCorrespondantSelect.observe('change', changeCorrespondantSelectEvent);
				changeCorrespondantSelectEvent();
			}

		} else if(
			oData.action == 'updateIcons'
		) {

			if(
				oData &&
				oData.preview &&
				oData.preview.content != null
			) {

				// Gelesen
				var iSelectedId = oData.data.selectedRows[0];
				var oTr = $('row_' + this.hash + '_' + iSelectedId);
				oTr.setStyle({
					fontWeight: 'normal' 
				});
				
				// Vorschau
				var oPreviewContent = $j('#preview_content_' + this.hash);
				oPreviewContent.get(0).srcdoc = oData.preview.content;

				$('preview_container_' + this.hash).show();

				$('preview_date_' + this.hash).update(oData.preview.date);
				$('preview_from_' + this.hash).update(oData.preview.from);
				$('preview_to_' + this.hash).update(oData.preview.to);

				if(oData.preview.cc) {
					$('preview_cc_' + this.hash).up('.row').show();
					$('preview_cc_' + this.hash).update(oData.preview.cc);
				} else {
					$('preview_cc_' + this.hash).up('.row').hide();
				}

				if(oData.preview.bcc) {
					$('preview_bcc_' + this.hash).up('.row').show();
					$('preview_bcc_' + this.hash).update(oData.preview.bcc);
				} else {
					$('preview_bcc_' + this.hash).up('.row').hide();
				}

				if(oData.preview.attachments) {
					$('preview_attachments_' + this.hash).up('div').show();
					$('preview_attachments_' + this.hash).update(oData.preview.attachments);
				} else {
					$('preview_attachments_' + this.hash).up('div').hide();
				}

				if(oData.preview.flags) {
					var oPreviewFlagContainer = $('preview_flags_' + this.hash);
					if(oPreviewFlagContainer) {
						var oDiv = oPreviewFlagContainer.up('div');
						if(oDiv) {
							oDiv.show();
							oPreviewFlagContainer.update(oData.preview.flags);
						}
					}
				} else {
					$('preview_flags_' + this.hash).up('div').hide();
				}

				if(oData.preview.type) {
					$('preview_type_' + this.hash).up('div').show();
					$('preview_type_' + this.hash).update(oData.preview.type);
				} else {
					$('preview_type_' + this.hash).up('div').hide();
				}

				if(!this._sDefaultTabTitle) {
					this._sDefaultTabTitle = $('Gui2ChildTableButton_preview_' + this.hash).down('span').innerHTML;
				}

				var sTabTitle = oData.preview.subject;
				if(sTabTitle.length === 0) {
					sTabTitle = '<i>'+this.getTranslation('preview_no_subject')+'</i>';
				}

				$('Gui2ChildTableButton_preview_' + this.hash).down('span').update(sTabTitle);
				
			} else {

				$('preview_container_' + this.hash).hide();
				
				if(this._sDefaultTabTitle) {
					$('Gui2ChildTableButton_preview_' + this.hash).down('span').update(this._sDefaultTabTitle);
				}
			}

			this.resizeTableBody();
			
		} else if (
			oData.action === 'openDialog' &&
			oData.data.action === 'allocate'
		) {

			// Alle Aktionen anklickbar machen
			$j('#dialog_wrapper_' + oData.data.id + '_' + this.hash + ' .allocationAction').click(function(e) {
				this.request('&task=save&action=messageAllocation&type='+$j(e.currentTarget).data('handler'));
			}.bind(this));

		}
	},

	/**
	 * Gesprächspartner-Feld umschalten beim Anlegen von Notizen
	 * @param oData
	 * @param oCorrespondantSelect
	 * @param oCorrespondantInput
	 */
	changeCorrespondantSelectEvent: function(oData, oCorrespondantSelect, oCorrespondantInput) {
		var bFound = false;

		// Wert kommt aus der Ext_TC_Communication_Message_Notice_Gui2_Data und deren Kinder
		oData.aNoticeHideCorrespondantValue.each(function(key) {
			if($F(oCorrespondantSelect).startsWith(key)) {
				bFound = true;
			}
		});

		if(bFound) {
			oCorrespondantInput.up('.GUIDialogRow').hide();
		} else {
			oCorrespondantInput.up('.GUIDialogRow').show();
		}

	},

	resizeTableBody: function($super) {

		$super();

		// Preview-Container-Container Iframe von Höhe und Breite her anpassen
		var oPreviewContainer = $j('#preview_container_' + this.hash);

		if(oPreviewContainer.length > 0) {

			// Höhe vom unteren Teil, daher Flag setzen
			this.bPageTopGui = false;
			var iHeight = this.getDocumentHeight();
			this.bPageTopGui = true;

			var oPreviewHeader = oPreviewContainer.children('div[id*=preview_header]');

			iHeight -= 35;

			oPreviewContainer.parent().height(iHeight);

			iHeight -= oPreviewHeader.height();

			iHeight -= 30;

			var oPreviewContent = oPreviewContainer.children('iframe');

			oPreviewContent.height(iHeight);
			oPreviewContent.width(oPreviewContainer.width());
		}

	}
	
});
