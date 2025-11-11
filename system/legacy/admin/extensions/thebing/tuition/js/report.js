 
// Needs for global access of active draggable
var aDraggedOffset = [];

var ReportGui = Class.create(ATG2, {

	 resize: function($super) {
		 
		$super();
		 
		// Höhe für "Eigene Übersichten"
		if (typeof setWindowSizes !== "undefined") {
			setWindowSizes();
		}

	},
	
	requestCallbackHook: function($super, aData) {

		var sTask = aData.action;
		var sAction = aData.data.action;

		if(
			aData.task && 
			aData.task != ''
		) {
			sTask = aData.task;
			sAction = aData.action;
		}

		var aCopy = aData;

		aData = aData.data;

		if(aData.cols_tab_data) {
			this.aColsTabData = aData.cols_tab_data;
		}
		if(aData.cols_tab_messages) {
			this.aColsTabMessages = aData.cols_tab_messages;
		}
		if(aData.cols_tab_saved_cols) {
			this.aSavedCols = aData.cols_tab_saved_cols;
		}

		if(sTask == 'openDialog' || sTask == 'saveDialogCallback') {
			if(sAction == 'new' || sAction == 'edit') {
				
				this.sReportsPrefix = this.hash + '_' + aData.id;

				// ignorieren der Fehlercheckbox
				var oCheckboxIgnore = $('ignoreErrors');
				if(oCheckboxIgnore) {
					Event.observe(oCheckboxIgnore, 'change', function() {
						var oInputHiddenIgnoreField = $('save['+this.hash+']['+aData.id+'][ignore_errors]');
						if(oCheckboxIgnore.checked == true){
							oInputHiddenIgnoreField.value = '1';
						}else{
							oInputHiddenIgnoreField.value = '0';
						}
					}.bind(this));
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

				this.calculateColsTabContentSizes(aData.id);

				this.initColsTab(aData.id);
			}
		}
	},

	calculateColsTabContentSizes : function(sDataID) {

		if(!$('cols_filter')) {
			return;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		var oDialog = $('dialog_' + sDataID + '_' + this.hash);

		var aDimensions = oDialog.getDimensions();
		var iInfoboxWidth = $j('#divColTabInfobox').outerWidth();

		$('cols_filter').style.height		= (aDimensions['height'] - 115) + 'px';
		$('scroll_container').style.width	= (aDimensions['width'] - iInfoboxWidth - 50) + 'px';
		$('divColTabLabels').style.width	= (aDimensions['width'] - iInfoboxWidth - 50) + 'px';

		$('scroll_container_content').style.width = $('scroll_container').style.width;
	},

	initColsTab : function(sDataID) {
		
		if(!$('cols_filter')) {
			return;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write saved columns

		$j('#scroll_container_content').empty();

		if(this.aSavedCols && this.aSavedCols.length > 0) {
			for(var i = 0; i < this.aSavedCols.length; i++) {
				
				var oElement = $('filter_col_' + this.aSavedCols[i].column_id);

				if (oElement !== null) {
					var oCopy = this.initAfterAdding(sDataID, oElement, $('scroll_container_content'));
					$j(oCopy).find('.column-width').val(this.aSavedCols[i].width);
					$j(oCopy).find('.column-label').val(this.aSavedCols[i].label);
					$j(oCopy).find('.column-setting').val(this.aSavedCols[i].setting);
				}
				
			}

			this.aSavedCols = false;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create caching array for draggables

		if(!this.aCachedDrags) {
			this.aCachedDrags = {};
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create draggables

		$j('#cols_filter .filter_col').draggable({
			connectToSortable: '#scroll_container_content',
			helper: "clone",
			revert: "invalid",
			start: function( event, ui ) {
				ui.helper.id = '';
			}
		});

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create sortable container
		$j('#scroll_container_content').sortable({
			revert: true,
			receive: function( event, ui ) {
				var oNewElement = $j('#scroll_container_content').find('.ui-draggable').get(0);
				$j(oNewElement).removeClass('ui-draggable');
				$j(oNewElement).removeClass('ui-draggable-handle');
				$j(oNewElement).css('height', '');
				$j(oNewElement).css('position', '');
				this.prepareColumn(oNewElement);
			}.bind(this),
			over : function(){
				$j(this).addClass('boxHover');
			},
			out : function(){
				$j(this).removeClass('boxHover');
			}
		});
		
		$j('#scroll_container_content, .filter_col').disableSelection();

		this.searchFilterCols(sDataID);
	},

	initAfterAdding : function(sDataID, oDragged, oDropped) {

		if(this.aCachedDrags && this.aCachedDrags[oDragged.id]) {
			this.aCachedDrags[oDragged.id].destroy();
		}

		var oCopy = oDragged.cloneNode(true);

		oCopy.id = '';
		oDropped.insert({bottom: oCopy});

		this.prepareColumn(oCopy);

		this.searchFilterCols(sDataID);

		return oCopy;
	},

	prepareColumn: function(oCopy) {

		

		$j(oCopy).find('input, select').prop('disabled', false);
		$j(oCopy).find('select.dummy').prop('disabled', true);

		oCopy.style.width = '150px';
		oCopy.down('.remover').show();
		oCopy.down('.remover').stopObserving('click');
		Event.observe(oCopy.down('.remover'), 'click', function(oEvent) {
			oEvent.target.up('.filter_col').remove();
		}.bind(this));

		$j(oCopy).find('.column-config').show();
		
		var iMainWidth		= parseInt($('scroll_container').style.width.replace(/px/, ''));
		var aAddedFields	= $A($$('#scroll_container_content .filter_col'));
		
		$j(oCopy).find('select,input').each(function(oField) {
			this.name = this.name.replace(/\[COUNTER\]/, '['+aAddedFields.length+']');
		});

		var iWidth = (parseInt(oCopy.style.width)) + 8;

		if((aAddedFields.length * iWidth) >= iMainWidth) {
			$('scroll_container_content').style.width = (aAddedFields.length * iWidth) + 'px';
		} else {
			$('scroll_container_content').style.width = iMainWidth + 'px';
		}

		if($j('#scroll_container_content').sortable( "instance" )) {
			$j('#scroll_container_content').sortable('refresh');
		}
		
	},

	searchFilterCols : function(sDataID) {
		
		$('filter_group').stopObserving('change');
		Event.observe($('filter_group'), 'change', function()
		{
			this.searchFilterCols(sDataID);
		}.bind(this));

		$('filter_string').stopObserving('keyup');
		Event.observe($('filter_string'), 'keyup', function()
		{
			this.searchFilterCols(sDataID);
		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Check and set the items visibility in filter container

		$H(this.aColsTabData).each(function(oGroup, iGroupID)
		{
			if((typeof oGroup.value != 'undefined') && (typeof oGroup.value != 'function'))
			{
				iGroupID	= oGroup[0];
				oGroup		= oGroup[1];

				$H(oGroup.fields).each(function(oItem, iKey)
				{
					if((typeof oItem.value != 'undefined') && (typeof oItem.value != 'function'))
					{
						iKey	= oItem[0];
						oItem	= oItem[1];

						//$('filter_col_' + iKey).style.backgroundColor = '#' + oGroup.color;

						$('filter_col_' + iKey).down('.spanTitle').innerHTML =
							$('filter_col_' + iKey).down('.spanTitle').innerHTML.replace(/<b><u>/, '');
						$('filter_col_' + iKey).down('.spanTitle').innerHTML =
							$('filter_col_' + iKey).down('.spanTitle').innerHTML.replace(/<\/u><\/b>/, '');

						/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Group filter visibility

						if(
							$('filter_col_' + iKey).up('#cols_filter') &&
							parseInt($('filter_group').value) > 0
						) {
							if(parseInt($('filter_group').value) == iGroupID) {
								$('filter_col_' + iKey).show();
							} else {
								$('filter_col_' + iKey).hide();
							}
						} else if(parseInt($('filter_group').value) == 0) {
							$('filter_col_' + iKey).show();
						}

						/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // String filter visibility

						if(
							$('filter_col_' + iKey).up('#cols_filter') &&
							(
								!$('filter_col_' + iKey).style.display ||
								$('filter_col_' + iKey).style.display != 'none'
							) &&
							$('filter_string').value != ''
						) {
							var sString	= $('filter_string').value.toLowerCase();
							var sText	= oItem.label.toLowerCase();
		
							if(sText.indexOf(sString) != -1) {
								var sLabel = $('filter_col_' + iKey).down('.spanTitle').innerHTML;

								$('filter_col_' + iKey).down('.spanTitle').innerHTML =
									sLabel.substr(0, sText.indexOf(sString)) + '<b><u>' +
									sLabel.substr(sText.indexOf(sString), $('filter_string').value.length) + '</u></b>' +
									sLabel.substr(sText.indexOf(sString) + $('filter_string').value.length);

								$('filter_col_' + iKey).show();
							}
							else
							{
								$('filter_col_' + iKey).hide();
							}
						}
					}
				}.bind(this));
			}
		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Sort filter items

		var aColumns = $A($$('#cols_filter .filter_col'));

		var aCacheCols	= [];
		var aCacheStrs	= [];

		var z = 0;

		for(var i = 0; i < aColumns.length; i++)
		{
			if(!aColumns[i].style.display || aColumns[i].style.display != 'none')
			{
				aCacheStrs[z] = aColumns[i].innerHTML;

				aCacheCols[z]			= {};
				aCacheCols[z]['text']	= aColumns[i].innerHTML;
				aCacheCols[z]['obj']	= aColumns[i].remove();

				z++;
			}
		}

		aCacheStrs.sort();

		for(var i = 0; i < aCacheStrs.length; i++)
		{
			for(var n = 0; n < aCacheCols.length; n++)
			{
				if(aCacheStrs[i] == aCacheCols[n]['text'])
				{
					$('cols_filter').insert({bottom: aCacheCols[n]['obj']});

					// Unset written item
					aCacheCols.splice(n, 1);
				}
			}
		}
	},

});