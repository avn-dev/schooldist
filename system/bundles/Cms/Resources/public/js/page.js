var Page = Page || {

	oContainer: null,
	iPageId: null,
	sLanguage: null,

	initialize: function(oContainer) {
		
		this.oContainer = oContainer;
		
		$('.toggle-page-tree').click(function() {
			$('.cms-page-tree').toggle();
			$('.cms-page-edit').toggleClass('col-md-10');
			$('.cms-page-edit').toggleClass('col-md-12');
		});

		$('.cms-add-page').click(function() {
			window.open('/admin/extensions/cms/newpage.html', 'cms-page-frame');
		});

		$('.cms-index').click(function() {
			window.open('/admin/extensions/cms/edit_index.html', 'cms-page-frame');
		});

		$('.cms-sitemap').click(function() {
			window.open('/admin/cms/structure/edit', 'cms-page-frame');
		});

		var oTab = $('.toggle-page-tree').parents('.tab-pane');

		oTab.on('admin:resize-content-tab', $.proxy(this.resizeContentTab, this));

		$('.cms-page-tree .site_id').change($.proxy(this.loadLanguages, this));
		$('.cms-page-tree .language').change($.proxy(this.loadStructure, this));
		
		$('#publish_accept a').click($.proxy(this.publish, this, 'accept'));
		$('#publish_deny a').click($.proxy(this.publish, this, 'deny'));

		this.oContainer.find('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
			Page.changeMode($(e.target).attr('data-mode'));
		});

		Page.loadLanguages();

		$('#cms-modal').on('hidden.bs.modal', function(e) {
			$('#cms-modal').find('iframe').attr('src', 'about:blank');
		});

	},
	
	resizeContentTab: function(oEvent) {
		
		var oTab = oEvent.target;
		
		$(oTab).find('.cms-page-tree').height($(oTab).height() - 30);
		$(oTab).find('.page-tree').height($(oTab).height() - 30 - $(oTab).find('.toolbar').outerHeight());
		$(oTab).find('.cms-page-frame').height($(oTab).height() - $(oTab).find('.nav-tabs-custom').outerHeight() - 30);

	},
	
	publish: function(sAction) {
	
		$.post(
			'/admin/cms/publish/'+this.iPageId+'/'+this.sLanguage+'/'+sAction,
			'',
			$.proxy(this.handlePublish, this),
			'json'
		);
		
	},

	handlePublish: function(oResponse) {
		if(oResponse.success === true) {
			toastr.success(oResponse.message);
			this.showNoChanges();
		} else {
			toastr.error(oResponse.message);
		}
	},

	openBlockAdmin: function(contentId, blockId, numberId, strLanguage) {
		this.loadModal('/admin/extensions/cms/block_admin.html?language='+strLanguage+'&content_id='+contentId+'&block_id=' + blockId+'&page_id='+this.iPageId+'&number='+numberId);
	},

	loadLanguages: function() {

		var iSiteId = $('.cms-page-tree .site_id').val();

		$.post(
			'/admin/cms/site/'+iSiteId+'/languages',	
			function(oResponse) {
				var oSelect = $('.cms-page-tree .language');
				oSelect.empty();
				if(oResponse.languages) {

					$(oResponse.languages).each(function(i, oLanguage) {
						oSelect.append('<option value="'+oLanguage.code+'">'+oLanguage.name+'</option>');
					});

					if(oResponse.languages.length > 1) {
						oSelect.parent().show();
					} else {
						oSelect.parent().hide();
					}
					
				} else {
					oSelect.parent().hide();
				}

				Page.loadStructure();
				
			},
			'json'
		);

	},
	
	loadStructure: function() {

		var iSiteId = $('.cms-page-tree .site_id').val();

		var sLanguage = $('.cms-page-tree .language').val();

		if(!sLanguage) {
			sLanguage = 'null';
		}

		$.post(
			'/admin/cms/structure/'+iSiteId+'/'+sLanguage,	
			function(oResponse) {
				$('.page-tree').treeview({
					data: oResponse.structure,
					enableLinks: true,
					onNodeSelected: function(event, data) {
					
						var sPageUrl = '/admin/cms/page/'+data['page-id']+'/'+data['language']+'/live';
						$('.cms-page-frame').attr('src', sPageUrl);

					}
				});
			},
			'json'
		);

		var oEvent = {
			target: this.oContainer.parents('.content-tab').get(0)
		};

		this.resizeContentTab(oEvent);

	},
	
	changeMode: function(sMode) {

		var oFrame = this.oContainer.find('.cms-page-frame').get(0);

		oFrame.src = '/admin/cms/page/'+this.iPageId+'/'+this.sLanguage+'/'+sMode;
		
	},
	
	showActions: function() {

		this.oContainer.find('.page-action').show();
		
		var oEvent = {
			target: this.oContainer.parents('.content-tab').get(0)
		};

		this.resizeContentTab(oEvent);

	},
	
	hideActions: function() {

		this.oContainer.find('.page-action').hide();
		
		var oEvent = {
			target: this.oContainer.parents('.content-tab').get(0)
		};

		this.resizeContentTab(oEvent);

	},

	selectTab: function(sTab) {
		$('.cms-page-edit a[href="#mode_'+sTab+'"]').tab('show');
	},

	resetPublish: function() {
		document.getElementById('publish_accept').style.display = "none"; 
		document.getElementById('publish_deny').style.display = "none";
		document.getElementById('publish_changes').style.display = "none"; 
		document.getElementById('publish_nochanges').style.display = "none"; 
	},

	showPublish: function() {
		document.getElementById('publish_accept').style.display = "block"; 
		document.getElementById('publish_deny').style.display = "block"; 
		document.getElementById('publish_changes').style.display = "none"; 
		document.getElementById('publish_nochanges').style.display = "none"; 
	},

	showNoChanges: function() {
		document.getElementById('publish_accept').style.display = "none"; 
		document.getElementById('publish_deny').style.display = "none";
		document.getElementById('publish_changes').style.display = "none"; 
		document.getElementById('publish_nochanges').style.display = "block"; 
	},

	showChanges: function() {
		document.getElementById('publish_accept').style.display = "none"; 
		document.getElementById('publish_deny').style.display = "none";
		document.getElementById('publish_changes').style.display = "block"; 
		document.getElementById('publish_nochanges').style.display = "none"; 
	},

	saveContent: function(oEditor) {

		var oBody = oEditor.bodyElement;

		if($(oBody).is('.content-inpage-text')) {
			var sContent = oEditor.getContent({format: 'text'});
		} else {
			var sContent = oEditor.getContent();
		}
		
		var sId = $(oBody).prop('id');
		
		var aMatch = sId.match(/content-inpage-([0-9]+)-([0-9]+)-([0-9]+)-(.+)/);

		$.post(
			'/admin/cms/content/save',
			{
				page_id: aMatch[1],
				content_id: aMatch[2],
				number: aMatch[3],
				language: aMatch[4],
				content: sContent
			},
			function(oResponse) {
				if(
					oResponse.success && 
					oResponse.success === true
				) {
					toastr.success(oResponse.message);
					this.showPublish();
				} else {
					toastr.error(oResponse.message);
				}
				return true;
			}.bind(this),
			'json'
		);

	},

	addBlock: function(iParentId, contentId, levelId) {
		
		this.loadModal('/admin/extensions/cms/ins_block.html?parent_id='+iParentId+'&content_id='+contentId+'&level='+levelId+'&page_id='+this.iPageId);

	},

	editArea: function(iParentId, idNumber, strLanguage) {

		if(
			!strLanguage &&
			this.sLanguage
		) {
			strLanguage = this.sLanguage;
		}

		this.loadModal('/admin/extensions/cms/content.html?parent_id='+iParentId+'&number='+idNumber+'&language='+strLanguage+'&page_id='+this.iPageId);

	},
	
	editPage: function(iPageId) {

		this.loadModal('/admin/extensions/cms/preferences.html?page_id='+iPageId);

	},
	
	loadModal: function(sUrl) {
		
		$('#cms-modal').find('iframe').attr('src', sUrl);
		$('#cms-modal').modal('show');

	},
	
	closeModal: function() {

		$('#cms-modal').modal('hide');

	}
	
};