
// Needs for global access of active draggable
var aDraggedOffset = [];

var iListTypeValue;
var iStartWithValue;

var StatisticGui = Class.create(CoreGUI,
{
	customError: false,

	requestCallbackHook: function($super, aData)
	{
		var sTask = aData.action;
		var sAction = aData.data.action;

		if(aData.task && aData.task != '')
		{
			sTask = aData.task;
			sAction = aData.action;
		}

		var aCopy = aData;

		aData = aData.data;

		if(aData.column_count) {
			this.iColumnCount = aData.column_count;
		}
		if(aData.cols_tab_data)
		{
			this.aColsTabData = aData.cols_tab_data;
		}
		if(aData.cols_tab_periods_access)
		{
			this.aColsTabPeriodsAccess = aData.cols_tab_periods_access;
		}
		if(aData.cols_tab_messages)
		{
			this.aColsTabMessages = aData.cols_tab_messages;
		}
		if(aData.cols_tab_saved_cols)
		{
			this.aSavedCols = aData.cols_tab_saved_cols;
		}
		if(aData.cols_tab_relations_details)
		{
			this.aRelationsDetails = aData.cols_tab_relations_details;
		}
		if(aData.cols_tab_relations_sums)
		{
			this.aRelationsSums = aData.cols_tab_relations_sums;
		}

		if(sTask == 'openDialog' || sTask == 'saveDialogCallback')
		{
			if(sAction == 'openAccessDialog')
			{
				$super(aCopy);
			}
			else
			{
				if(sAction == 'new' || sAction == 'edit')
				{
					this.iNewStatisticIntervals = 0;

					this.sStatisticsPrefix = this.hash + '_' + aData.id;

					iListTypeValue = $('save[' + this.hash + '][' + aData.id + '][list_type]').value;
					iStartWithValue = $('save[' + this.hash + '][' + aData.id + '][start_with]').value;
					this.iCurrentPeriodValue = $('save[' + this.hash + '][' + aData.id + '][period]').value;

					/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create intervals container

					var oIntervalDD = $('save[' + this.hash + '][' + aData.id + '][interval]');

					var sCode = '';

					sCode += '<div id="intervals_' + this.sStatisticsPrefix + '">';
						sCode += '<div id="based_on_' + this.sStatisticsPrefix + '">';
							if(oIntervalDD.value > 0)
							{
								sCode += this.translations.statistics.based_on[oIntervalDD.value];
							}
						sCode += '</div>';
						sCode += '<div>';
							sCode += '<div id="new_' + this.sStatisticsPrefix + '"></div>';
						sCode += '</div>';
					sCode += '</div>';

					oIntervalDD.insert({ after: sCode });

					this.addStatisticInterval(oIntervalDD.value);

					/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write selected intervals

					var oNewSelect	= $('intervals_select_' + this.sStatisticsPrefix + '_0');
					var oNewInput	= $('intervals_amount_' + this.sStatisticsPrefix + '_0');

					for(var i = 0; i < aData.values.length; i++)
					{
						if(aData.values[i]['db_column'] == 'intervals')
						{
							for(var n = 0; n < aData.values[i]['value'].length; n++)
							{
								if(aData.values[i]['value'][n] < 0)
								{
									oNewSelect.value = 0;

									// Display correcture: show only positive values
									aData.values[i]['value'][n] *= -1;
								}
								else
								{
									oNewSelect.value = 1;
								}

								oNewInput.value = aData.values[i]['value'][n];

								this.checkNewInterval(oNewInput, aData.id);
							}

							$('save[' + this.hash + '][' + aData.id + '][title]').focus();

							break;
						}
					}

					/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

					Event.observe(oNewInput, 'keyup', function()
					{
						this.checkNewInterval(oNewInput, aData.id);
				    }.bind(this));

					/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

					this.toogleStatisticFields(aData.id);

					this.calculateColsTabContentSizes(aData.id);

					this.initColsTab(aData.id);
				}
			}
		}
	},

	checkNewInterval : function(oNewInput, iDataID)
	{
		var oIntervalDD = $('save[' + this.hash + '][' + iDataID + '][interval]');

		var iAmount = parseInt(oNewInput.value);

		if(!isNaN(iAmount) && iAmount >= 0)
		{
			this.addStatisticInterval(oIntervalDD.value);
		}
	},

	addStatisticInterval : function(iSelectedInterval)
	{
		var iID = this.iNewStatisticIntervals;

		var sCode = '';

		var sName = '';

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		sCode += '<div style="width:65px; float:left;">';
			if(iID != 0)
			{
				sName = 'save[intervals][dir][' + iID + ']';
			}
			sCode += '<select name="' + sName + '" class="txt form-control input-sm" style="width:60px;" id="intervals_select_' + this.sStatisticsPrefix + '_' + iID + '">';
				sCode += '<option value="1">+</option>';
				sCode += '<option value="0">-</option>';
			sCode += '</select>';
		sCode += '</div>';
		sCode += '<div style="width:60px; float:left;">';
			if(iID != 0)
			{
				sName = 'save[intervals][cnt][' + iID + ']';
			}
			sCode += '<input name="' + sName + '" class="txt form-control input-sm" style="width:55px;" id="intervals_amount_' + this.sStatisticsPrefix + '_' + iID + '" />';
		sCode += '</div>';
		sCode += '<div style="width:100px; float:left;">';
			sCode += '<div style="margin-top:3px;" class="intervals_text_' + this.sStatisticsPrefix + '">';
			if(iSelectedInterval > 0)
			{
				sCode += this.translations.statistics.intervals[iSelectedInterval];
			}
			sCode += '</div>';
		sCode += '</div>';
		if(iID != 0)
		{
			sCode += '<div style="float:left;">';

				var sStyle = 'display:block; cursor:pointer; margin-top:2px;';

				sCode += '<button type="button" style="' + sStyle + '"id="intervals_remove_' + this.sStatisticsPrefix + '_' + iID + '" /><i class="fa fa-remove"></i></button>';
			sCode += '</div>';
		}
		sCode += '<div class="divCleaner"></div>';

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Insert line and set remove observers

		if(iID == 0)
		{
			$('new_' + this.sStatisticsPrefix).insert({top: sCode});
		}
		else
		{
			var sContainerID = 'new_' + this.sStatisticsPrefix + '_' + iID;

			sCode = '<div id="' + sContainerID + '">' + sCode + '</div>';

			$('new_' + this.sStatisticsPrefix).insert({before: sCode});

			$('intervals_select_' + this.sStatisticsPrefix + '_' + iID).value = $('intervals_select_' + this.sStatisticsPrefix + '_0').value;
			$('intervals_amount_' + this.sStatisticsPrefix + '_' + iID).value = $('intervals_amount_' + this.sStatisticsPrefix + '_0').value;

			$('intervals_amount_' + this.sStatisticsPrefix + '_' + iID).focus();

			var oIcon = $('intervals_remove_' + this.sStatisticsPrefix + '_' + iID);

			Event.observe(oIcon, 'click', function()
			{
				$(sContainerID).remove();
		    }.bind(this));
		}

		$('intervals_select_' + this.sStatisticsPrefix + '_0').selectedIndex = 0;
		$('intervals_amount_' + this.sStatisticsPrefix + '_0').value = '';

		this.iNewStatisticIntervals--;
	},

	toogleStatisticFields : function(sDataID, bNoRemove)
	{
		var oListTypeDD				= $('save[' + this.hash + '][' + sDataID + '][list_type]');
		var oTypeDD					= $('save[' + this.hash + '][' + sDataID + '][type]');
		var oIntervalDD				= $('save[' + this.hash + '][' + sDataID + '][interval]');
		var oStartWithDD			= $('save[' + this.hash + '][' + sDataID + '][start_with]');
		var oPeriodDD				= $('save[' + this.hash + '][' + sDataID + '][period]');
		//var oStartPageCB			= $('save[' + this.hash + '][' + sDataID + '][start_page]');

		if(oTypeDD)
		{
			var oTypeDIV			= oTypeDD.up('.GUIDialogRow');
			var oIntervalDIV		= oTypeDD.up('.GUIDialogRow').next();
			var oStartWithDIV		= oIntervalDD.up('.GUIDialogRow').next();
			//var oStartPageDIV		= oPeriodDD.up('.GUIDialogRow').next();

			oStartWithDIV.hide();

			//oStartPageCB.disabled	= true;
		}

		var oAgencyCB				= $('save[' + this.hash + '][' + sDataID + '][agency]');
		var oGroupByDD				= $('save[' + this.hash + '][' + sDataID + '][group_by]');
		var oDirectCustomerCB		= $('save[' + this.hash + '][' + sDataID + '][direct_customer]');

		var oGroupByDIV				= oAgencyCB.up('.GUIDialogRow').next();
		var oAgenciesDIV			= oGroupByDIV.next();
		var oAgencyGroupsDIV		= oAgenciesDIV.next();
		var oAgencyCategoriesDIV	= oAgencyGroupsDIV.next();
		var oAgencyCountriesDIV		= oAgencyCategoriesDIV.next();

		var oFilterGroupSelect = $j('#filter_group');

		// Da das alles total schlecht programmiert wurde: Hiermit verhindern, dass Reset-Dialog mehrfach kommt
		var bColumnsResetted = false;
		if(bNoRemove) {
			bColumnsResetted = true;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Reset observer

		if(oListTypeDD)
		{
			oListTypeDD.stopObserving('change');
			Event.observe(oListTypeDD, 'change', function()
			{
				this.toogleStatisticFields(sDataID);
			}.bind(this));

			oStartWithDD.stopObserving('change');
			Event.observe(oStartWithDD, 'change', function()
			{
				this.toogleStatisticFields(sDataID);
			}.bind(this));

			// Detail: »Basierend auf«-Wert löschen bei Auswahl von unpassendem »Ausgehend von«
			if(
				oListTypeDD.value == 2 &&
				(
					!this.aColsTabPeriodsAccess[oPeriodDD.value] ||
					!this.aColsTabPeriodsAccess[oPeriodDD.value][oStartWithDD.value]
				)
			)
			{
				oPeriodDD.selectedIndex = 0;
			}

			// Detail: »Basierend auf«-Options aktivieren/deaktivieren für »Ausgehend von«
			for(var i = 0; i < oPeriodDD.length; i++)
			{
				if(oPeriodDD.options[i].value == 0)
				{
					continue;
				}

				if(
					oListTypeDD.value == 2 &&
					(
						!this.aColsTabPeriodsAccess[oPeriodDD.options[i].value] ||
						!this.aColsTabPeriodsAccess[oPeriodDD.options[i].value][oStartWithDD.value]
					)
				) {
					oPeriodDD.options[i].disabled = true;
				}
				else
				{
					oPeriodDD.options[i].disabled = false;
				}
			}

			// Basierend auf Anfragen: Filter setzen/umschalten und Spalten löschen
			if(
				this.iLastPeriodSelectValue != 5 &&
				oPeriodDD.value == 5
			) {
				oFilterGroupSelect.children('option[value=4]').prop('enabled', true);
				oFilterGroupSelect.val(4);
				oFilterGroupSelect.prop('disabled', true);

				if(!bColumnsResetted) {
					bColumnsResetted = true;
					this.resetAddedColumns(sDataID, true);
				}
			} else if(
				this.iLastPeriodSelectValue == 5 &&
				oPeriodDD.value != 5
			) {
				oFilterGroupSelect.val(0);
				oFilterGroupSelect.prop('disabled', false);
				oFilterGroupSelect.children('option[value=4]').prop('disabled', true);

				if(!bColumnsResetted) {
					bColumnsResetted = true;
					this.resetAddedColumns(sDataID, true);
				}
			}

			oIntervalDD.stopObserving('change');
		    Event.observe(oIntervalDD, 'change', function()
			{
		    	$('based_on_' + this.sStatisticsPrefix).innerHTML = this.translations.statistics.based_on[oIntervalDD.value];

				var aTexts = $A($$('.intervals_text_' + this.sStatisticsPrefix));

				aTexts.each(function(oText)
				{
					oText.innerHTML = this.translations.statistics.intervals[oIntervalDD.value];
				}.bind(this));
		    }.bind(this));

			if(oListTypeDD.value == 1) // Summe
			{
				oTypeDIV.show();
				oIntervalDIV.show();
				oStartWithDIV.hide();

				oTypeDD.stopObserving('change');
				Event.observe(oTypeDD, 'change', function()
				{
					this.toogleStatisticFields(sDataID);
				}.bind(this));

				switch(oTypeDD.value)
				{
					case '1': // Relativ
					{
						$('intervals_' + this.sStatisticsPrefix).show();

						//oStartPageCB.disabled = false;

						break;
					}
					default:
					{
						$('intervals_' + this.sStatisticsPrefix).hide();

						//oStartPageCB.disabled = true;
					}
				}
			}
			else
			{
				oTypeDIV.hide();
				oIntervalDIV.hide();

				if(oListTypeDD.value > 0)
				{
					oStartWithDIV.show();
				}
			}
		}

		if(oPeriodDD)
		{
			$j(oPeriodDD).off('focus').on('focus', function() {
				this.iLastPeriodSelectValue = parseInt($j(oPeriodDD).val());
			}.bind(this));

			oPeriodDD.stopObserving('change');
			Event.observe(oPeriodDD, 'change', function()
			{
				this.toogleStatisticFields(sDataID);
			}.bind(this));
		}

		oAgencyCB.stopObserving('click');
		Event.observe(oAgencyCB, 'click', function()
		{
			this.toogleStatisticFields(sDataID);
		}.bind(this));

		oGroupByDD.stopObserving('change');
		Event.observe(oGroupByDD, 'change', function()
		{
			this.toogleStatisticFields(sDataID);
		}.bind(this));

		oDirectCustomerCB.stopObserving('click');
		Event.observe(oDirectCustomerCB, 'click', function()
		{
			this.toogleStatisticFields(sDataID);
		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(oListTypeDD && $('groups_container') && !bColumnsResetted)
		{
			this.resetAddedColumns(sDataID);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		oGroupByDIV.hide();
		oAgenciesDIV.hide();
		oAgencyGroupsDIV.hide();
		oAgencyCategoriesDIV.hide();
		oAgencyCountriesDIV.hide();

		if(oAgencyCB.checked)
		{
			oGroupByDIV.show();

			switch(parseInt(oGroupByDD.value))
			{
				case 1: oAgenciesDIV.show();			break;
				case 2: oAgencyGroupsDIV.show();		break;
				case 3: oAgencyCategoriesDIV.show();	break;
				case 4: oAgencyCountriesDIV.show();		break;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		var oCountriesDIV = oDirectCustomerCB.up('.GUIDialogRow').next();

		switch(oDirectCustomerCB.checked)
		{
			case true:
			{
				oCountriesDIV.show();

				break;
			}
			case false:
			{
				oCountriesDIV.hide();

				break;
			}
		}
	},

	calculateColsTabContentSizes : function(sDataID)
	{
		if(!$('cols_filter'))
		{
			return;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		var oDialog = $('dialog_' + sDataID + '_' + this.hash);

		var aDimensions = oDialog.getDimensions();

		$('cols_filter').style.height		= (aDimensions['height'] - 140) + 'px';
		$('groups_container').style.width	= (aDimensions['width'] - 298) + 'px';
		$('scroll_container').style.width	= (aDimensions['width'] - 286) + 'px';
		$('divColTabLabels').style.width	= (aDimensions['width'] - 270) + 'px';
	},

	initColsTab : function(sDataID)
	{
		if(!$('cols_filter'))
		{
			return;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write saved columns

		if(this.aSavedCols) {
			if(this.aSavedCols['groups']) {
				if(this.aSavedCols['groups'].length > 0) {
					for(var i = 0; i < this.aSavedCols['groups'].length; i++) {
						var oElement = $('filter_col_' + this.aSavedCols['groups'][i]);

						this.initAfterAdding(sDataID, oElement, $('groups_container'));
					}
				}
			}
			
			if(this.aSavedCols['cols']) {
				if(this.aSavedCols['cols'].length > 0) {
					for(var i = 0; i < this.aSavedCols['cols'].length; i++) {
						var oElement = $('filter_col_' + this.aSavedCols['cols'][i]);

						this.initAfterAdding(sDataID, oElement, $('column_box_' + (i + 1)));
					}
				}
			}

			this.aSavedCols = false;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create caching array for draggables

		if(!this.aCachedDrags) {
			this.aCachedDrags = {};
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create draggables

		var aColumns = $A($$('#cols_filter .filter_col'));

		for(var i = 0; i < aColumns.length; i++)
		{
			if(!this.aCachedDrags[aColumns[i].id])
			{
				this.aCachedDrags[aColumns[i].id] = {};
			}

			if(this.aCachedDrags[aColumns[i].id].handle)
			{
				this.aCachedDrags[aColumns[i].id].destroy();
			}

			var oColumn = aColumns[i];

			this.aCachedDrags[aColumns[i].id] = new Draggable(aColumns[i].id,
			{
				revert: true,
				zindex: 1000001,
				onStart: function(event)
				{
					aDraggedOffset = this.cumulativeScrollOffset();

					var oTemp = this.remove();

					document.getElementsByTagName("BODY")[0].insert({bottom: oTemp});

				}.bind(oColumn),
				change: function(oDragged)
				{
					var iTop = parseInt(oDragged.element.style.top.replace(/px/, ''));

					oDragged.element.style.top = (iTop - parseInt(aDraggedOffset[1])) + 'px';
				},
				onEnd: function(oGUI)
				{
					if(this.up().tagName == 'BODY')
					{
						var oTemp = this.remove();

						$('cols_filter').insert({bottom: oTemp});

						oGUI.searchFilterCols(sDataID);
					}
				}.bind(oColumn, this)
			});
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create sortable container

		Sortable.destroy('columns_container');
		Sortable.create('columns_container',
		{
			tag: 'div',
			dropOnEmpty: true,
			constraint: false,
			zindex: 1000001,
			onChange: function(oElement)
			{
				Droppables.remove(oElement.id);
			},
			onUpdate: function(oElement)
			{
				this.initColsTab(sDataID);
			}.bind(this)
		});

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create groups droppables

		Droppables.remove($('groups_container'));
		Droppables.add('groups_container',
		{
			accept: 'filter_col',
			hoverclass: 'boxHover',
			zindex: 1000001,
			onDrop: function(oDragged, oDropped, oEvent)
			{
				this.initAfterAdding(sDataID, oDragged, oDropped)
			}.bind(this)
		});

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create columns droppables

		$j('#columns_container').width(132 * this.iColumnCount);

		for(var i = 1; i <= this.iColumnCount; i++)
		{
			Droppables.add('column_box_' + i,
			{
				accept: 'filter_col',
				hoverclass: 'boxHover',
				zindex: 1000001,
				onDrop: function(oDragged, oDropped, oEvent)
				{
					this.initAfterAdding(sDataID, oDragged, oDropped)
				}.bind(this)
			});
		}

		this.searchFilterCols(sDataID);
	},

	initAfterAdding : function(sDataID, oDragged, oDropped) {

		if(this.aCachedDrags && this.aCachedDrags[oDragged.id]) {
			this.aCachedDrags[oDragged.id].destroy();
		}

		// If the container is empty
		if($A($$('#' + oDropped.id + ' .filter_col')).length == 0) {

			var iWidth = oDropped.getWidth();

			if(iWidth == 0) {
				// Get width from hidden containers
				iWidth = parseInt(oDropped.style.width.replace(/px/, '')) + 2;
			}

			oDragged.style.width = (iWidth - 4) + 'px';

			oDragged.down('.remover').show();

			var oCopy = oDragged.remove();

			oDropped.insert({top: oCopy});

			// Set the column ID for save request
			$(oDropped.id + '_col').value = oDragged.id.replace(/filter_col_/, '');

			oDragged.down('.remover').stopObserving('click');
			Event.observe(oDragged.down('.remover'), 'click', function() {
				this.removeBoxColumn(sDataID, oDropped, oDragged);
			}.bind(this));

			if(this.checkColsCompatibility(sDataID, oDropped, oDragged)) {
				var oListTypeDD = $('save[' + this.hash + '][' + sDataID + '][list_type]');

				if($(oDragged.id + '_settings') && parseInt(oListTypeDD.value) == 1) {
					$(oDragged.id + '_settings').show();
				}
				if($(oDragged.id + '_maxby') && parseInt(oListTypeDD.value) == 1) {
					$(oDragged.id + '_maxby').show();
				}
			}

			this.searchFilterCols(sDataID);
			
		} else {
			this.customError = true;
			this.displayErrors(new Array(this.aColsTabMessages.double_item), sDataID);

			this.initColsTab(sDataID);
		}
	},

	searchFilterCols : function(sDataID) {
		var oListTypeDD		= $('save[' + this.hash + '][' + sDataID + '][list_type]');
		var oStartWithDD	= $('save[' + this.hash + '][' + sDataID + '][start_with]');

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

		$H(this.aColsTabData).each(function(oGroups, iGroupID)
		{
			if((typeof oGroups.value != 'undefined') && (typeof oGroups.value != 'function'))
			{
				$H(oGroups[1]).each(function(oItem, iKey)
				{
					if((typeof oItem.value != 'undefined') && (typeof oItem.value != 'function'))
					{
						oItem = oItem[1];

						$('filter_col_' + oItem.id).style.backgroundColor = '#' + oItem.color;

						$('filter_col_' + oItem.id).down('.spanTitle').innerHTML =
							$('filter_col_' + oItem.id).down('.spanTitle').innerHTML.replace(/<b><u>/, '');
						$('filter_col_' + oItem.id).down('.spanTitle').innerHTML =
							$('filter_col_' + oItem.id).down('.spanTitle').innerHTML.replace(/<\/u><\/b>/, '');

						/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Default visibility

						if(oListTypeDD.value == 1 && oItem.sum == 1)
						{
							$('filter_col_' + oItem.id).show();
						}
						else if(oListTypeDD.value == 2 && oItem.detail == 1)
						{
							$('filter_col_' + oItem.id).show();
						}
						else
						{
							$('filter_col_' + oItem.id).hide();
						}

						/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Group filter visibility

						if(
							$('filter_col_' + oItem.id).up('#cols_filter') &&
							(
								!$('filter_col_' + oItem.id).style.display ||
								$('filter_col_' + oItem.id).style.display != 'none'
							)
						) {
							if(parseInt($('filter_group').value) > 0) {
								if(parseInt(oItem.group_id) == parseInt($('filter_group').value)) {
									$('filter_col_' + oItem.id).show();
								} else {
									$('filter_col_' + oItem.id).hide();
								}
							}

							// Anfragen-Spalten unter »Alle Kategorien« immer ausblenden
							if(
								$('filter_group').value == 0 &&
								oItem.group_id == 4
							) {
								$('filter_col_' + oItem.id).hide();
							}

						}

						/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // String filter visibility

						if(
							$('filter_col_' + oItem.id).up('#cols_filter') &&
							(
								!$('filter_col_' + oItem.id).style.display ||
								$('filter_col_' + oItem.id).style.display != 'none'
							) &&
							$('filter_string').value != ''
						)
						{
							var sString	= $('filter_string').value.toLowerCase();
							var sText	= oItem.title.toLowerCase();
		
							if(sText.indexOf(sString) != -1)
							{
								var sLabel = $('filter_col_' + oItem.id).down('.spanTitle').innerHTML;

								$('filter_col_' + oItem.id).down('.spanTitle').innerHTML =
									sLabel.substr(0, sText.indexOf(sString)) + '<b><u>' +
									sLabel.substr(sText.indexOf(sString), $('filter_string').value.length) + '</u></b>' +
									sLabel.substr(sText.indexOf(sString) + $('filter_string').value.length);

								$('filter_col_' + oItem.id).show();
							}
							else
							{
								$('filter_col_' + oItem.id).hide();
							}
						}

						/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

						if(oListTypeDD.value == 2) // Details
						{
							if(
								this.aRelationsDetails[parseInt(oStartWithDD.value)] &&
								this.aRelationsDetails[parseInt(oStartWithDD.value)][parseInt(oItem.id)]
							)
							{
								if(
									!$('filter_col_' + oItem.id).style.display ||
									$('filter_col_' + oItem.id).style.display != 'none'
								)
								{
									$('filter_col_' + oItem.id).show();
								}
							}
							else
							{
								$('filter_col_' + oItem.id).hide();
							}
						}
					}
				}.bind(this));
			}
		}.bind(this));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(oListTypeDD.value == 1)
		{
			var aColumns = $A($$('#divColTabLabels .filter_col'));

			var aFilters = $A($$('#cols_filter .filter_col'));

			for(var i = 0; i < aColumns.length; i++)
			{
				var iID = aColumns[i].id.replace(/filter_col_/, '');

				if(this.aRelationsSums[iID])
				{
					for(var n = 0; n < aFilters.length; n++)
					{
						var iFilterID = aFilters[n].id.replace(/filter_col_/, '');

						if(!this.aRelationsSums[iID][iFilterID])
						{
							aFilters[n].hide();
						}
					}
				}
				else
				{
					for(var n = 0; n < aFilters.length; n++)
					{
						aFilters[n].hide();
					}
				}
			}
		}

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

	checkColsCompatibility : function(sDataID, oDropped, oDragged)
	{
		if(oDropped.id == 'groups_container')
		{
			if(!oDragged.down('#' + oDragged.id + '_group_by'))
			{
				this.removeBoxColumn(sDataID, oDropped, oDragged);

				this.customError = true;
				this.displayErrors(new Array(this.aColsTabMessages.invalid_item), sDataID);

				return false;
			}
		}

		// Nur entfernen, wenn das JS auch manuell etwas hinzugefügt hat, sonst wird immer alles ausgeblendet
		if (this.customError) {
			this.customError = false;
			this.removeErrors(sDataID);
		}

		return true;
	},

	removeBoxColumn : function(sDataID, oDropped, oDragged)
	{
		var iWidth = $('cols_filter').getWidth();

		if(iWidth == 0)
		{
			iWidth = 200;
		}

		oDragged.style.width = (iWidth - 32) + 'px';

		oDragged.down('.remover').hide();

		if($(oDragged.id + '_settings'))
		{
			$(oDragged.id + '_settings').selectedIndex = 0;

			$(oDragged.id + '_settings').hide();
		}
		if($(oDragged.id + '_maxby'))
		{
			$(oDragged.id + '_maxby').selectedIndex = 0;

			$(oDragged.id + '_maxby').hide();
		}

		var oRemover = oDragged.remove();

		$('cols_filter').insert({top : oRemover});

		// Reset the column ID for save request
		$(oDropped.id + '_col').value = '';

		// Resort the filter container items
		this.searchFilterCols(sDataID);

		this.initColsTab(sDataID);
	},

	resetAddedColumns : function(sDataID, bForce)
	{
		var aCache = [];
		var oListTypeDD	= $('save[' + this.hash + '][' + sDataID + '][list_type]');
		var oStartWithDD = $('save[' + this.hash + '][' + sDataID + '][start_with]');
		var oPeriodDD = $('save[' + this.hash + '][' + sDataID + '][period]');

		this.searchFilterCols(sDataID);

		$A($$('#columns_container .column_block')).each(function(oBlock)
		{
			if(
				oBlock.down('.filter_col') && (
					bForce ||
					oBlock.down('.filter_col').style.display == 'none'
				)
			) {
				var oTemp = {};

				oTemp['block'] = oBlock;
				oTemp['column'] = oBlock.down('.filter_col');

				aCache.push(oTemp);
			}
		}.bind(this));

		var oBlock = $('groups_container');

		if(
			oBlock.down('.filter_col') && (
				bForce ||
				oListTypeDD.value == 2
			)
		) {
			var oTemp = {};

			oTemp['block'] = oBlock;
			oTemp['column'] = oBlock.down('.filter_col');

			aCache.push(oTemp);
		}

		if(aCache.length > 0 && oListTypeDD.value > 0)
		{
			var sMessage = this.aColsTabMessages.reset_columns_start + '\n\n';

			for(var i = 0; i < aCache.length; i++)
			{
				sMessage += '\t- ' + aCache[i].column.down('.spanTitle').lastChild.data + '\n';
			}

			sMessage += '\n' + this.aColsTabMessages.reset_columns_end;

			if(!confirm(sMessage))
			{
				oListTypeDD.value = iListTypeValue;
				oStartWithDD.value = iStartWithValue;
				oPeriodDD.value = this.iCurrentPeriodValue;

				this.toogleStatisticFields(sDataID, true);

				this.searchFilterCols(sDataID);

				return;
			}

			for(var i = 0; i < aCache.length; i++)
			{
				this.removeBoxColumn(sDataID, aCache[i].block, aCache[i].column);
			}
		}

		switch(oListTypeDD.value)
		{
			case '1': // Summenansicht
			{
				$('groups_container').up('.GUIDialogRow').show();

				$A($$('#divColTabLabels .txt')).each(function(oDD)
				{
					oDD.show();
				});

				break;
			}
			case '2': // Listenansicht
			{
				$('groups_container').up('.GUIDialogRow').hide();

				$A($$('#divColTabLabels .txt')).each(function(oDD)
				{
					oDD.hide();
				});

				break;
			}
		}

		iListTypeValue = oListTypeDD.value;
		iStartWithValue = oStartWithDD.value;
		this.iCurrentPeriodValue = oPeriodDD.value;
	}
});
