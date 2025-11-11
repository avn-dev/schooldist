var CommunicationTemplateGui = Class.create(CoreGUI, {

	sTemplateGuiHash: '9de7286bb26b17182a083bcb7697e0f6',
	//sLayoutGuiHash: '630afe46ea86deb3bba3ee4808186aef',
	
	requestCallbackHook: function($super, oData) {
		
		if(this.hash == this.sTemplateGuiHash) {
			
			$super(oData);
			
			if(
				oData &&
				oData.action &&
				oData.data &&
				oData.data.id &&
				oData.data.action && (
					oData.action == 'openDialog' || 
					oData.action == 'saveDialogCallback' ||
					oData.action == 'reloadDialogTab'
				) && (
					oData.data.action == 'new' || 
					oData.data.action == 'edit'
				)
			) {

				this.sFieldPrefix = 'save['+this.hash+']['+oData.data.id+']';

				// Markierungen ein- und ausblenden, wenn »Automatische E-Mails« ausgewählt wurde
				var oApplicationSelect = $(this.sFieldPrefix + '[applications]');
				var oFlagSelectRow = $(this.sFieldPrefix + '[flags]').up('.GUIDialogRow');
				this.observeApplicationSelect(oApplicationSelect, oFlagSelectRow);
				oApplicationSelect.observe('change', this.observeApplicationSelect.bind(this, oApplicationSelect, oFlagSelectRow));

				/*var oLanguages = $(this.sFieldPrefix + '[languages]');
				oLanguages.observe('change', this.doLanguageAction.bind(this, oLanguages));*/
				
				// Dafür sorgen, dass alle ausgewählten Markierungen beim Aktualisieren gelöscht werden
				/*var oFlagSelect = $(this.sFieldPrefix + '[flags]');
				$j(oFlagSelect).multiselect('refreshOptions');*/

			} else if(oData.action == 'openPreviewTemplate') {
				this.openTemplatePreviewCallback(oData);
			}
			
		} else {
			$super(oData);
		}
		
	},

	/**
	 * Markierungen ein- und ausblenden, wenn »Automatische E-Mails« ausgewählt wurde
	 */
	observeApplicationSelect: function(oApplicationSelect, oFlagSelectRow) {
		if($F(oApplicationSelect).indexOf('automatic') >= 0) {
			oFlagSelectRow.hide();
		} else {
			oFlagSelectRow.show();
		}
	},

	/**
	 * HTML-Vorschau
	 * Wird von den E-Mail Layouts direkt von einem Onclick in der HTML aufgerufen.
	 */
	openPreview : function(sContent) {

		var aData = {
			html : '\
				<div id="preview_header">\
					<h3 class="layout-note">'
						+ this.getTranslation('email_preview_note') +
					'</h3>\
					<div class="layout-ruler"></div>\
				</div>\
				<div>\
					<iframe id="email_preview_iframe" style="border:none">\
				</div>\
			',
			id : 'email_preview',
			title : this.getTranslation('email_preview_title'),
			width: 900,
			height: 800,
			no_padding: true,
			no_scrolling: true
		};
		
		this.openDialog(aData);
		
		var oIframe = $('email_preview_iframe');
		
		var oContainer = $('content_email_preview_' + this.hash);
		
		var aDimensions = oContainer.getDimensions();
		
		oIframe.style.width = (aDimensions.width * 0.98) + 'px';
		oIframe.style.height = ((aDimensions.height - $('preview_header').getHeight()) * 0.94) + 'px';

		var oIframeContent = oIframe.contentDocument || oIframe.contentWindow.document;
		
		oIframeContent.open();
		oIframeContent.write(sContent);	
		oIframeContent.close();
		
		return false;
		
	},
	
	openLayoutPreview: function() {
		this.openPreview($('email_preview_html').value);
	},
	
	openTemplatePreview : function(sIso, oButton) {
		
		var oRegexp = new RegExp('save\\['+this.hash+'\\]\\[(ID_[0-9]+)\\]\\[\\]');
		this.sCurrentDialogId = oButton.id.replace(oRegexp, '$1');
		
		var sParam = '&language=' + sIso;
		
		sParam += '&dialog_id=' + this.sCurrentDialogId;
		sParam += '&language_code=' + sIso;
		
		var sSelectId = 'save[' + this.hash + '][' + this.sCurrentDialogId + '][layout_id_' + sIso + ']';

		if($(sSelectId)) {
			sParam += '&layout_id=' + $F(sSelectId);
		}
		
		this.request('&task=getLayoutCode' + sParam);
		
	},
	
	openTemplatePreviewCallback: function(oData) {
		
		var sEditorId = 'save[' + this.hash + '][' + oData.data.id + '][content_' + oData.language_code + ']';
		
		var oTiny = tinyMCE.get(sEditorId);
		var sPreview = '';
		
		if(oData.layout != '') {
			sPreview = oData.layout.replace(/\{email_content\}/, oTiny.getContent());
		} else {
			sPreview = oTiny.getContent();
		}
		
		this.openPreview(sPreview);
		
	}
	
	/**
	 * Beim Ändern von der Auswahl von Sprachen Tabs klonen und neu laden
	 */
	/*doLanguageAction: function(oLanguages)
	{
		var iSelectedLanguages = $F(oLanguages).length;
		var oTabUL = $('tabs_' + this.sCurrentDialogId + '_' + this.hash).down();
		var oTabContent = $('tabs_content_' + this.sCurrentDialogId + '_' + this.hash).down();
		var iTabs = oTabUL.childElements().length;
		var iNewTabIndex = iTabs++;
		
		var oFirstTab = oTabContent.childElements()[0];
		//var iTabHeight = oFirstTab.getHeight();
		
		var oFirstTabHeaderClone = oTabUL.childElements()[0].clone(false);
		var oFirstTabClone = oFirstTab.clone(false);
		
		var sTabHeaderId = 'tabHeader_' + iNewTabIndex + this.sCurrentDialogId + this.hash;
		oFirstTabHeaderClone.id = sTabHeaderId;
		
		var sTabBodyId = 'tabBody_' + iNewTabIndex + this.sCurrentDialogId + this.hash;
		oFirstTabClone.id = sTabBodyId;
		
		oTabUL.appendChild(oFirstTabHeaderClone);
		oTabContent.appendChild(oFirstTabClone);
		
		// »Einstellungen« abziehen, +1 für for und +1 bei for für das Überspringen von »Einstellungen«
		var iAddTabs = iSelectedLanguages - (iTabs - 1) + 2;
		
		for(var i = 1; i < iAddTabs; ++i) {
			this.reloadDialogTab(this.sCurrentDialogId, i);
		}
	}*/
	
});