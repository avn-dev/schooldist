
var FormGui = Class.create(ATG2,
{
	iTabFormDesigner: null,

	requestCallbackHook: function($super, aData)
	{
		var aDataOriginal = aData; // Ohne Sinn und Verstand wird aData überschrieben
		var sTask = aData.action;
		var sAction = '';
		if(
			aData.data &&
			aData.data.action
		){
			sAction = aData.data.action;
		}

		if(aData.task && aData.task != '')
		{
			sTask = aData.task;
			sAction = aData.action;
		}

		var aCopy = aData;

		if(aCopy.active_tab)
		{
			this.iActivePageTab = aCopy.active_tab;
		}

		aData = aData.data;

		if(
			sTask == 'openDialog' || 
			sTask == 'saveDialogCallback' || 
			sTask == 'reloadDialogTab'
		) {

			if(aData.tab_form_designer) {
				this.iTabFormDesigner = aData.tab_form_designer;
			}

			if(aData.action === 'edit') {
				this.getDialogSaveField('type').prop('disabled', true);
			}

			if(aCopy.plausibility_error)
			{
				var sID = 'ID_' + aCopy.parent_form_id;

				this.reloadDialogTab(sID, this.iTabFormDesigner);
			}

			if(sAction == 'new' || sAction == 'edit')
			{
				this.bUpdateSort = false;

				// var oPricesTabHeader = $j('#tabHeader_3_' + aData['id'] + '_' + this.hash);
				// if(
				// 	aData.hide_prices &&
				// 	this.getDialogSaveField('type').val() === 'enquiry'
				// ) {
				// 	oPricesTabHeader.hide();
				// } else {
				// 	oPricesTabHeader.show();
				// }

				if(sAction == 'edit')
				{
					// Wer kam auf die behinderte Idee, hier immer pauschal alles auszublenden?
					// $$('#tabBody_0_' + aData['id'] + '_' + this.hash + ' .GUIDialogNotification').each(function(oNotification)
					// {
					// 	oNotification.hide();
					// });

					$j('#note-new').hide();
				}

				if(aData.form_translations)
				{
					this.aFormTranslations = aData.form_translations;
				}

				if(aData.save_id && aData.save_id > 0)
				{
					this.setFormPagesObserver();
				}

				if(!this.iActivePageTab)
				{
					var aPages = $A($$('.form_pages_tab'));

					if(
						aPages &&
						aPages.length > 0
					){
						this.iActivePageTab = aPages[0].id.replace(/pages_tab_/, '');
					}
				}

				if(this.iActivePageTab) {
					if($('prices_block_' + this.iActivePageTab)) {
						$('block_5').hide();
					} else {
						if(
							$('save[' + this.hash + '][' + aData['id'] + '][use_prices]') !== null &&
							$('save[' + this.hash + '][' + aData['id'] + '][use_prices]').value === 0
						) {
							$('block_5').hide();
						} else {
							$('block_5').show();
						}
					}
					this.setActivePageTab(this.iActivePageTab);
					this.iActivePageTab = null;
				}

			}
			else if(sAction == 'edit_page')
			{
				var sID = 'ID_' + aCopy.parent_form_id;

				// Nicht ausführen bei einem Fehler, da ansonsten die Message direkt entfernt wird
				if (!aDataOriginal.error.length) {
					this.reloadDialogTab(sID, this.iTabFormDesigner);
				}
			}
			else if(sAction == 'edit_block')
			{
				if(aCopy.parent_form_id)
				{
					var sID = 'ID_' + aCopy.parent_form_id;

					// Dialog ID löschen, da sonst die fehler ausgeblendet werden #1875
					this.sCurrentDialogId = null;
					
					this.reloadDialogTab(sID, this.iTabFormDesigner);
				}

				this.setFormPageBlocksObserver(aData, aCopy);
			}
		}
	},


	/**
	 * Set page observers
	 */
	setFormPagesObserver: function()
	{

		var aPages = $A($$('.form_pages_content'));

		var iPages = aPages.length;

		var aSortables	= new Array();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Hide blocks

		$j('#form_blocks [data-fixed]').each(function () {
			var block = $j('#form_pages_content [data-fixed="' + $j(this).data('fixed') + '"]');
			if (block.length) {
				$j(this).remove();
			}
		});

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		aPages.each(function(oPage)
		{
			var iPageID = oPage.id.replace(/form_pages_content_/, '');

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Hide remove icons

			if(iPages == 1)
			{
				$('remove_page_' + iPageID).hide();
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			// New page icon
			Event.stopObserving($('add_page_icon'), 'click');
			Event.observe($('add_page_icon'), 'click', function()
			{
				this.request('&task=openDialog&action=edit_page&page_id=0');
			}.bind(this));

			// Edit page icon
			Event.stopObserving($('edit_page_' + iPageID), 'click');
			Event.observe($('edit_page_' + iPageID), 'click', function()
			{
				this.request('&task=openDialog&action=edit_page&page_id=' + iPageID);
			}.bind(this));

			// Remove page icon
			Event.stopObserving($('remove_page_' + iPageID), 'click');
			Event.observe($('remove_page_' + iPageID), 'click', function()
			{
				if(confirm(this.aFormTranslations.remove_message))
				{
					this.request('&task=saveDialog&action=remove_page&page_id=' + iPageID);
				}
			}.bind(this));

			// Tabs
			Event.stopObserving($('pages_tab_' + iPageID), 'click');
			Event.observe($('pages_tab_' + iPageID), 'click', function()
			{
				this.setActivePageTab(iPageID);
			}.bind(this));

			if(typeof $j(oPage).data('ui-droppable') !== 'undefined') {
				$j(oPage).droppable('destroy');
			}

			// Block in Page reinziehen (Receiver)
			$j(oPage).droppable(
			{
				hoverClass: 'accept_element',
				scope: 'blocks',
				greedy: true,
				scroll: true,
				drop: function(event, oDrag)
				{
					var oBlock = oDrag['draggable'][0];

					var iBlockKey = oBlock.id.replace(/block_/, '');

					var oGUI = $j(oPage).sortable('option', 'oGUI');

					oGUI.request('&task=openDialog&action=edit_block&block_key=' + iBlockKey + '&parent_id=' + this.id);
				}
			});
			$j(oPage).droppable('option', 'oGUI', this);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			aSortables.push($j('#form_pages_content_' + iPageID));

			if(typeof $j('#form_pages_content_' + iPageID).data('ui-sortable') !== 'undefined') {
				$j('#form_pages_content_' + iPageID).sortable('destroy');
			}

			// Blöcke innerhalb Page verschieben
			$j('#form_pages_content_' + iPageID).sortable(
			{
				handle: 'th',
				helper: 'clone',
				scroll: true,
				dropOnEmpty: true,
				opacity: 0.7,
				tolerance: 'intersect',
				update: function(e, ui)
				{
					var oGUI = $j('#form_pages_content_' + iPageID).sortable('option', 'oGUI');

					var oParent = $(ui['item'][0]).up('.sortable');

					if(oParent && !oGUI.bUpdateSort)
					{
						var iElement = ui['item'][0].id.replace(/sort_/, '');

						oGUI.bUpdateSort = true;

						var sParams = $j(oParent).sortable('serialize');

						oGUI.request('&task=saveDialog&action=sort_blocks&' + sParams + '&active_tab=' + iPageID + '&parent_id=' + oParent.id + '&element_id=' + iElement);
					}
				}
			});
			$j('#form_pages_content_' + iPageID).sortable('option', 'oGUI', this);

		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Sortable pages

		if(iPages > 1)
		{
			if(typeof $j('#form_pages_tabs').data('ui-sortable') !== 'undefined') {
				$j('#form_pages_tabs').sortable('destroy');
			}

			// Pages vertikal verschieben
			$j('#form_pages_tabs').sortable(
			{
				update: function(e, ui)
				{
					var sParams = $j('#form_pages_tabs').sortable('serialize');

					var oGUI = $j('#form_pages_tabs').sortable('option', 'oGUI');

					var oSelected = ui['item'][0];

					var iActiveTab = oSelected.id.replace(/pages_tab_/, '');

					oGUI.request('&task=saveDialog&action=sort_pages&' + sParams + '&active_tab=' + iActiveTab);
				}
			});

			$j('#form_pages_tabs').sortable('option', 'oGUI', this);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Draggable blocks

		var aBlocks = $A($$('.filter_col'));

		// var oTab = $('tabBody_2_' + this.sCurrentDialogId + '_' + this.hash);
		var oTab = $j('[id*=tabBody_].tab_form_designer');

		aBlocks.each(function(oBlock)
		{
			/*if(
				oBlock.id == 'block_2' &&
				!$('courses_block') && (
					this.getDialogSaveField('type').val() !== 'registration' ||
					this.getDialogSaveField('acc_depending_on_course', 'kf').is(':checked')
				)
			) {
				// Unterkunft deaktivieren
				oBlock.addClassName('filter_col_disabled');
			}
			/*else if(oBlock.id == 'block_3' && !$('accommodations_block'))
			{
				// Transfer deaktivieren
				oBlock.addClassName('filter_col_disabled');
			}
			else
			{*/
				if(typeof $j(oBlock).data('ui-draggable') !== 'undefined') {
					$j(oBlock).draggable('destroy');
				}

				// Linke Blöcke auf Page ziehen (Sender)
				$j(oBlock).draggable(
				{
					appendTo: oTab,
					scroll: true,
					helper: 'clone', // Breite wird in toggleDialogTabHook fix gesetzt, da eigener Helper nicht funktioniert(?)
					scope: 'blocks'
				});
			// }
		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Droppable container

		var aContainer = $A($$('.container'));

		aContainer.each(function(oContainer)
		{
			if(typeof $j(oContainer).data('ui-droppable') !== 'undefined') {
				$j(oContainer).droppable('destroy');
			}

			$j(oContainer).droppable(
			{
				hoverClass: 'accept_element',
				scope: 'blocks',
				greedy: true,
				scroll: true,
				drop: function(event, oDrag)
				{
					var oBlock = oDrag['draggable'][0];

					var iBlockKey = oBlock.id.replace(/block_/, '');

					var oGUI = $j(oContainer).sortable('option', 'oGUI');

					oGUI.request('&task=openDialog&action=edit_block&block_key=' + iBlockKey + '&parent_id=' + this.id);
				}
			});
			$j(oContainer).droppable('option', 'oGUI', this);

			var oPage = $(oContainer).up('.form_pages_content');

			var iPageID = oPage.id.replace(/form_pages_content_/, '');

			aSortables.push(oContainer);

			if(typeof $j(oContainer).data('ui-sortable') !== 'undefined') {
				$j(oContainer).sortable('destroy');
			}

			$j(oContainer).sortable(
			{
				handle: 'th',
				helper: 'clone',
				scroll: true,
				dropOnEmpty: true,
				opacity: 0.7,
				tolerance: 'intersect',
				update: function(e, ui)
				{
					var oGUI = $j(oContainer).sortable('option', 'oGUI');

					var oParent = $(ui['item'][0]).up('.sortable');

					if(oParent && !oGUI.bUpdateSort)
					{
						var iElement = ui['item'][0].id.replace(/sort_/, '');

						oGUI.bUpdateSort = true;

						var sParams = $j(oParent).sortable('serialize');

						oGUI.request('&task=saveDialog&action=sort_blocks&' + sParams + '&active_tab=' + iPageID + '&parent_id=' + oParent.id + '&element_id=' + iElement);
					}
				}
			});
			$j(oContainer).sortable('option', 'oGUI', this);
		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Blocks observer

		var aButtons = $A($$('.block_edit_img'));

		aButtons.each(function(oButton)
		{
			var iBlockID = oButton.id.replace(/edit_/, '');

			// Edit block icon
			Event.stopObserving(oButton, 'click');
			Event.observe(oButton, 'click', function()
			{
				this.request('&task=openDialog&action=edit_block&block_id=' + iBlockID);
			}.bind(this));

			// Remove block icon
			Event.stopObserving($('remove_' + iBlockID), 'click');
			Event.observe($('remove_' + iBlockID), 'click', function()
			{
				if(confirm(this.aFormTranslations.remove_message))
				{
					this.request('&task=saveDialog&action=remove_block&block_id=' + iBlockID);
				}
			}.bind(this));

			// Move block icon
			Event.stopObserving($('move_' + iBlockID), 'click');
			Event.observe($('move_' + iBlockID), 'click', function() {
				this.request('&task=openDialog&action=move_block&block_id=' + iBlockID);
			}.bind(this));

		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Make connections

		aSortables.each(function(oSort)
		{
			$j(oSort).sortable('option', 'connectWith', '.sortable');
		}.bind(this));
	},


	setFormPageBlocksObserver: function(aData, aCopy)
	{
		var oInput = $('saveid[set_number_of_cols]');

		var iBlockID = aData.id.replace(/block_/, '');

		if(oInput)
		{
			Event.stopObserving(oInput, 'keyup');
			Event.observe(oInput, 'keyup', function()
			{
				var oInput = $('saveid[set_number_of_cols]');

				if(isNaN(oInput.value) || parseInt(oInput.value) <= 0)
				{
					oInput.value = 1;
				}

				var aWidths = $A($$('.col_width_input'));

				if(parseInt(oInput.value) < aWidths.length)
				{
					if(!confirm(this.aFormTranslations.area_block_message))
					{
						oInput.value = aWidths.length;

						return;
					}
				}

				var oContainer = $('block_cols_container_' + iBlockID);

				var aCache = new Array();

				aWidths.each(function(oWidth)
				{
					aCache.push(oWidth.remove());
				});

				for(var i = 0; i < parseInt(oInput.value); i++)
				{
					if(aCache.length > 0)
					{
						oContainer.insert({bottom: aCache.shift()});
					}
					else
					{
						var sCode = '';

						sCode += '<div class="GUIDialogRow form-group form-group-sm col_width_input">';
							sCode += '<label class="GUIDialogRowLabelDiv control-label col-sm-3">' + (i + 1) + ': </label>';
							sCode += '<div class="GUIDialogRowInputDiv col-sm-9">';
								sCode += '<input type="text" name="save[set_numbers][]" class="txt form-control input-sm" />';
							sCode += '</div>';
							sCode += '<div class="divCleaner"></div>';
						sCode += '</div>';

						oContainer.insert({bottom: sCode});
					}
				}
			}.bind(this));
		}

		var oInputType = $j('#saveid\\[set_type\\]');

		// Keine Ahnung, was das für ein Relikt ist
		if(aCopy.error && aCopy.error[0]) {
			oInputType.addClass('GuiDialogErrorInput');
		} else {
			oInputType.removeClass('GuiDialogErrorInput');
		}

		// Bereits verwendete Felder sperren
		var aUsedFields = oInputType.data('used') || [];
		aUsedFields.forEach(function(sField) {
			oInputType.find('option[value="' + sField + '"]:not([selected])').prop('disabled', true);
		});

		// Placeholder setzen je nach ausgewähltem Feld
		var oDefaultLabels = oInputType.data('default-titles') || {};
		oInputType.change(function() {
			$j('.input_title').each(function() {
				var oInputTitle = $j(this);
				var sLanguage = oInputTitle.data('language');
				var sDefaultLabel = '';
				if(oDefaultLabels[sLanguage] && oDefaultLabels[sLanguage][oInputType.val()]) {
					sDefaultLabel = oDefaultLabels[sLanguage][oInputType.val()];
				}
				oInputTitle.attr('placeholder', sDefaultLabel);
			});
		}).change();

		$j('.checkbox_following_courses').each(function(iIndex, oCheckbox) {
			oCheckbox = $j(oCheckbox);
			oCheckbox.change(function() {
				var oContainer = $j('.' + oCheckbox.data('container-class'));
				if(oCheckbox.prop('checked')) {
					oContainer.show();
				} else {
					oContainer.hide();
				}
			});
			oCheckbox.change();
		});
	},


	/**
	 * Highlight tabs, show or hide contents
	 * 
	 * @param int iPageID
	 */
	setActivePageTab: function(iPageID)
	{
		var aPageTabs = $A($$('.form_pages_tab'));

		aPageTabs.each(function(oTab)
		{
			var iTabID = oTab.id.replace(/pages_tab_/, '');

			if(iTabID > 0)
			{
				oTab.removeClassName('form_pages_tab_active');

				$('form_pages_content_' + iTabID).hide();
			}
		}.bind(this));

		if($('prices_block_' + iPageID)) {
			$('block_5').hide();
		} else {
			var oUsePrices = $('save[' + this.hash + '][' + this.sCurrentDialogId + '][use_prices]');
			if(
				oUsePrices &&
				oUsePrices.value !== 1
			) {
				$('block_5').hide();
			} else {
				$('block_5').show();
			}
		}

		var oPagesTab = $j('#pages_tab_' + iPageID);
		oPagesTab.addClass('form_pages_tab_active');

		// Payment, Aktivitäten und Upload sperren
		var sIds = $j('#form_content').data('block-dependency').map(id => '#block_' + id).join(',');
		if (oPagesTab.data('type') === 'enquiry') {
			$j(sIds).addClass('filter_col_disabled');
		} else {
			$j(sIds).removeClass('filter_col_disabled');
		}

		var oPagesContent = $('form_pages_content_' + iPageID);
		
		if(oPagesContent){
		// Show content
			oPagesContent.show();
		}
	},

	toggleDialogTabHook: function(iTab, iDialogId) {
		// Breite von Drag-Blöcken fest setzen, damit clone die Breite behält
		$j('.filter_col:visible').each(function() {
			var el = $j(this);
			if(el.width()) {
				el.width(el.width());
			}
		});
	}
});
