
var EmailLayouts = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {

		if(aData.action == 'openPreviewTemplate') {
			this.openPreviewTemplateCallback(aData);
		} else {
			$super(aData);
		}

	},
	
	openPreview : function() {

		var aData = {
			html : '<div id="preview_header"><h3 style="padding:6px;margin:0;text-align:center">'+this.getTranslation('email_preview_note')+'</h3><div style="height:15px;background:url(/admin/extensions/thebing/images/ruler.png) -600px top no-repeat"></div></div><iframe id="email_preview_iframe" style="border:none">',
			id : 'email_preview',
			title : this.getTranslation('email_preview_title'),
			//width : 950,
			width: 900,
			height: 800,
			no_padding: true,
			no_scrolling: true
		};
		
		this.openDialog(aData);
		
		var oIframe = $('email_preview_iframe');
		
		var oContainer = $('content_email_preview_'+this.hash);
		
		var aDimensions = oContainer.getDimensions();
		
		oIframe.style.width = aDimensions.width+'px';
		oIframe.style.height = (aDimensions.height - $('preview_header').getHeight())+'px';

		var oIframeContent = oIframe.contentDocument || oIframe.contentWindow.document;
		
		oIframeContent.write($('email_preview_html').value);	
		
		return false;
	},
	
	openPreviewTemplate : function(oButton, sLanguage) {
		
		var sRegexp = new RegExp('save\\['+this.hash+'\\]\\[(ID_[0-9]+)\\]\\[\\]');
		
		var sDialogId = oButton.id.replace(sRegexp, '$1');
		
		var sParam = '&language='+sLanguage;
		
		sParam += '&dialog_id='+sDialogId;
		sParam += '&language_code='+sLanguage;
		
		var sSelectId = 'save['+this.hash+']['+sDialogId+'][layout_'+sLanguage+']';

		if($(sSelectId)) {
			sParam += '&layout_id='+$F(sSelectId);
		}
		
		this.request('&task=getLayoutCode'+sParam);
	},
	
	openPreviewTemplateCallback : function(aData) {

		var aDataDialog = {
			html : '<div id="preview_header"><h3 style="padding:6px;margin:0;text-align:center">'+this.getTranslation('email_preview_note')+'</h3><div style="height:15px;background:url(/admin/extensions/thebing/images/ruler.png) -600px top no-repeat"></div></div><iframe id="email_preview_iframe" style="border:none">',
			id : 'email_preview',
			title : this.getTranslation('email_preview_title'),
			width: 900,
			height: 800,
			no_padding: true,
			no_scrolling: true
		};
		
		this.openDialog(aDataDialog);
		
		var oIframe = $('email_preview_iframe');
		
		var oContainer = $('content_email_preview_'+this.hash);
		
		var aDimensions = oContainer.getDimensions();
		
		oIframe.style.width = aDimensions.width+'px';
		oIframe.style.height = (aDimensions.height - $('preview_header').getHeight())+'px';

		var oIframeContent = oIframe.contentDocument || oIframe.contentWindow.document;
		
		var oTinyMCE = $('save['+this.hash+']['+aData.data.id+'][content_'+aData.language_code+']_ifr');
		
		var sEditorId = 'save['+this.hash+']['+aData.data.id+'][content_'+aData.language_code+']';
		
		var oTiny = tinyMCE.get(sEditorId);
		
		if(aData.layout != '') {
		oIframeContent.write(aData.layout.replace(/\{email_content\}/, oTiny.getContent()));
		} else {
			oIframeContent.write(oTiny.getContent());
		}

		return false;
	}
	
});