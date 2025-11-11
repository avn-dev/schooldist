
var SpecialGui = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {
		
		// RequestCallback der Parent Klasse
		$super(aData);

		this.aConditionCounterCache = new Array();

		var sTask = aData.action;
		var sAction = aData.data.action;
		var aData = aData.data;

		if(	
			sTask == 'openDialog' ||
			sTask == 'saveDialogCallback'
		){


			if(
				sAction == 'edit' ||
				sAction == 'new'
			){
				// Observer setzen
				this.aData = aData;
				this.setDialogObserver(aData); 
			}

		}
	},

	// ------------------------------------------------------------------------------------

	/*
	 * Observer für Tabelle
	 */
	setDialogObserver : function(aData){

		// Discount-Codes Tab
		var discountCodeEnabledField = document.getElementById('save['+this.hash+']['+aData.id+'][discount_code_enabled][ts_sp]');

		$j(discountCodeEnabledField).change(function() {
			if($j(this).is(':checked')) {
				$j('.discount-code-tab').show();
			} else {
				$j('.discount-code-tab').hide();
			}
		});

		var oPeriodType = this.getDialogSaveField('period_type');
		var oAmountType = this.getDialogSaveField('amount_type');
		$j().add(oPeriodType).add(oAmountType).change(function() {
			if(
				oPeriodType.val() === '1' &&
				oAmountType.val() === '1'
			) {
				this.getDialogSaveField('service_period_calculation').closest('.GUIDialogRow').show();
			} else {
				this.getDialogSaveField('service_period_calculation').closest('.GUIDialogRow').hide();
				this.getDialogSaveField('service_period_calculation').val('full_service_period');
			}
		}.bind(this)).change();

		// Limit
		var oSelectLimit = $('save['+this.hash+']['+aData.id+'][limit_type][ts_sp]');
		if(oSelectLimit){
			this.switchLimitType(aData, oSelectLimit);

			oSelectLimit.stopObserving('change');
			Event.observe(oSelectLimit, 'change', function(){
				this.switchLimitType(aData, oSelectLimit);
			}.bind(this));
		}
		
		// Gültig für
		var oSelectAvailableFor = $('save['+this.hash+']['+aData.id+'][direct_booking][ts_sp]');
		if(oSelectAvailableFor){
			this.switchAvailableFor(aData, oSelectAvailableFor);

			oSelectAvailableFor.stopObserving('change');
			Event.observe(oSelectAvailableFor, 'change', function(){
				this.switchAvailableFor(aData, oSelectAvailableFor);
			}.bind(this));
		}

		var oSelectDirectAll = $('save['+this.hash+']['+aData.id+'][all_countries][ts_sp]');

		if(oSelectDirectAll){
			this.switchDirectAll(aData, oSelectDirectAll);

			oSelectDirectAll.stopObserving('click');
			Event.observe(oSelectDirectAll, 'click', function(){
				this.switchDirectAll(aData, oSelectDirectAll);
			}.bind(this));
		}
		
		var oSelectAllNationalities = $('save['+this.hash+']['+aData.id+'][all_nationalities][ts_sp]');
		if(oSelectAllNationalities){
			this.switchDirectAll(aData, oSelectAllNationalities);

			oSelectAllNationalities.stopObserving('click');
			Event.observe(oSelectAllNationalities, 'click', function(){
				this.switchDirectAll(aData, oSelectAllNationalities);
			}.bind(this));
		}
		
		// Agenturen gruppieren nach
		var oSelectAgencyGrouping = $('save['+this.hash+']['+aData.id+'][agency_grouping][ts_sp]');
		if(oSelectAgencyGrouping){
			this.switchAgencyGrouping(aData, oSelectAgencyGrouping);

			oSelectAgencyGrouping.stopObserving('change');
			Event.observe(oSelectAgencyGrouping, 'change', function(){
				this.switchAgencyGrouping(aData, oSelectAgencyGrouping);
			}.bind(this));
		}

		// Für was
		var oSelectHowMany = $('save['+this.hash+']['+aData.id+'][amount_type][ts_sp]');
		if(oSelectHowMany){
			this.switchHowMany(aData, oSelectHowMany);

			oSelectHowMany.stopObserving('change');
			Event.observe(oSelectHowMany, 'change', function(){
				this.switchHowMany(aData, oSelectHowMany);
			}.bind(this));
		}

		// Prozent - Art
		$$('.percent_kind').each( function (oSelectPercentKind) {

				this.switchPercentKind(aData, oSelectPercentKind);

				oSelectPercentKind.stopObserving('change');
				Event.observe(oSelectPercentKind, 'change', function(){
					this.switchPercentKind(aData, oSelectPercentKind);
				}.bind(this));

		}.bind(this));

		// Absolut - Art
		$$('.absolut_kind').each( function (oSelectAbsolutKind) {

				this.switchAbsolutKind(aData, oSelectAbsolutKind);

				oSelectAbsolutKind.stopObserving('change');
				Event.observe(oSelectAbsolutKind, 'change', function(){
					this.switchAbsolutKind(aData, oSelectAbsolutKind);
				}.bind(this));

		}.bind(this));

		// Week - Art
		$$('.week_kind').each( function (oSelectWeekKind) {

				this.switchWeekKind(aData, oSelectWeekKind);

				oSelectWeekKind.stopObserving('change');
				Event.observe(oSelectWeekKind, 'change', function(){
					this.switchWeekKind(aData, oSelectWeekKind);
				}.bind(this));

		}.bind(this));


		// Blöcke durchgehen und "delete" icon verstecken wenn es ein letzter Block ist
		this.disableDeleteBlock();

	},

	/*
	 * Blöcke durchgehen und "delete" icon verstecken wenn es ein letzter Block ist
	 */
	disableDeleteBlock : function(){

		var aBlockClasses = new Array();
		aBlockClasses[0] = 'div_percent_class';
		aBlockClasses[1] = 'div_absolut_class';
		aBlockClasses[2] = 'div_week_class';

		aBlockClasses.each(function(sBlockClass, iIndex){
			var iCount = 0;
			$$('.'+sBlockClass).each( function (oDivBlock) {
					var oDeleteRow = oDivBlock.down('.row_delete_box');
					if(oDeleteRow){
						// Erster Löschbutton darf nicth zu sehen sein
						if(iCount == 0){
							oDeleteRow.hide();
						}else{
							oDeleteRow.show();
						}
					}
					iCount++;
			}.bind(this));
		});


	},

	/*
	 * Limit Select
	 */
	switchLimitType : function(aData, oSelect){
		var iValue = $F(oSelect);
		var oRow = $('limit_input');
		if(oRow){
			if(iValue == 1){
				oRow.hide();
			}else{
				oRow.show();
			}
		}		
	},


	switchDirectAll : function(aData, oSelect) {

		var joinTableKey = 'join_countries';
		if($j(oSelect).prop('name').indexOf('all_nationalities') !== -1) {
			joinTableKey = 'nationalities';
		}
		
		var iValue = $F(oSelect);

		var oDiv = $('save['+this.hash+']['+aData.id+']['+joinTableKey+'][ts_sp]');
		
		if(oDiv){
			
			oDiv = oDiv.up('.GUIDialogRow');
			
			if(oDiv){
				
				if(iValue == 1){
					oDiv.hide();
				} else {
					oDiv.show();
				}
				
			}
		}

		if (joinTableKey == 'join_countries') {
			var oDiv = $('save[' + this.hash + '][' + aData.id + '][join_country_groups][ts_sp]');

			if (oDiv) {

				oDiv = oDiv.up('.GUIDialogRow');

				if (oDiv) {

					if (iValue == 1) {
						oDiv.hide();
					} else {
						oDiv.show();
					}

				}
			}
		}
	},
	
	/*
	 * Gültig Für Select
	 */
	 switchAvailableFor : function(aData, oSelect){
		var iValue = $F(oSelect);
		var oDivDirectBooking = $('direct_booking');
		var oDivAgencyBooking = $('agency_booking');
		
		var oDivDirectBookingAll = $('save['+this.hash+']['+aData.id+'][all_countries][ts_sp]');

		if(
			oDivDirectBooking &&
			oDivAgencyBooking
		){
			if(iValue == 1){
				oDivDirectBooking.show();
				oDivAgencyBooking.hide();
				this._fireEvent('click', oDivDirectBookingAll);
			}else{
				oDivDirectBooking.hide();
				oDivAgencyBooking.show();
			}
		}
	},

	/*
	 * Agency Grouping select
	 */
	switchAgencyGrouping : function(aData, oSelect){
		var iValue = $F(oSelect);
		iValue = iValue.parseNumber();
		
		var oRowAgencyCountry	= $('row_agency_country');
		var oRowAgencyGroup		= $('row_agency_group');
		var oRowAgencyCategory	= $('row_agency_category');
		var oRowAgencies		= $('row_agencies');
		var oRowAgencyCountryGroup	= $('row_agency_country_group');

		if(
			oRowAgencyCountry &&
			oRowAgencyGroup &&
			oRowAgencyCategory &&
			oRowAgencies &&
			oRowAgencyCountryGroup
		){
				
			
			oRowAgencyCountry.hide();
			oRowAgencyGroup.hide();
			oRowAgencyCategory.hide();
			oRowAgencies.hide();
			oRowAgencyCountryGroup.hide();
				
			switch(iValue){
				case 1: // alle agenturen
					break;
				case 2: // Agenturländer
					oRowAgencyCountry.show();
					break;
				case 3: // Agenturgruppen
					oRowAgencyGroup.show();
					break;
				case 4: // Agenturkategorien
					oRowAgencyCategory.show();
					break;
				case 5: // Agenturen
					oRowAgencies.show();
					break;
				case 6: // Ländergruppen
					oRowAgencyCountryGroup.show();
					break;

			}
		}
	},

	/*
	 * Wieviel Select
	 */
	switchHowMany : function(aData, oSelect){
		var iValue = $F(oSelect);
		iValue = iValue.parseNumber();

		this.removeRequiredFields();
				
		switch(iValue){
			case 1:
				this.toggleBlocks('show', 'div_percent_class');
				this.toggleBlocks('hide', 'div_absolut_class');
				this.toggleBlocks('hide', 'div_week_class');

				// Required Felder
				this.setRequiredFields('div_percent_class');
				break;
			case 2:
				this.toggleBlocks('hide', 'div_percent_class');
				this.toggleBlocks('show', 'div_absolut_class');
				this.toggleBlocks('hide', 'div_week_class');

				// Required Felder
				this.setRequiredFields('div_absolut_class');
				break;
			case 3:
				this.toggleBlocks('hide', 'div_percent_class');
				this.toggleBlocks('hide', 'div_absolut_class');
				this.toggleBlocks('show', 'div_week_class');

				// Required Felder
				this.setRequiredFields('div_week_class');
				break;
			default:
				this.toggleBlocks('hide', 'div_percent_class');
				this.toggleBlocks('hide', 'div_absolut_class');
				this.toggleBlocks('hide', 'div_week_class');
		}

		
	},


	/*
	 * Required Felder setzen
	 */
	setRequiredFields : function(sClass){
		switch(sClass){
			case 'div_percent_class':
				$$('.percent_required').each( function (oInput) {
					oInput.addClassName('required');
				}.bind(this));
				break;
			case 'div_absolut_class':
				$$('.absolut_required').each( function (oInput) {
					oInput.addClassName('required');
				}.bind(this));
				break;
			case 'div_week_class':
				$$('.week_required').each( function (oInput) {
					oInput.addClassName('required');
				}.bind(this));
				break;
		}
	},

	/*
	 * entfernt die flexieblen required felder wieder
	 */
	removeRequiredFields : function(){
		$$('.percent_required').each( function (oInput) {
			oInput.removeClassName('required');
		}.bind(this));

		$$('.absolut_required').each( function (oInput) {
			oInput.removeClassName('required');
		}.bind(this));

		$$('.week_required').each( function (oInput) {
			oInput.removeClassName('required');
		}.bind(this));
	},

	/*
	 * Toggelt formularblöcke anhand der Div Klasse
	 */
	toggleBlocks : function(sShow, sClass){
		if(sShow == 'show'){
			$$('.'+sClass).each( function (oBlock) {
				oBlock.show();
			}.bind(this));
		}else{
			$$('.'+sClass).each( function (oBlock) {
				oBlock.hide();
			}.bind(this));
		}
				
	},

	/*
	 * Prozent Art Select
	 */
	switchPercentKind : function(aData, oSelect){
		var iValue = $F(oSelect);
		iValue = iValue.parseNumber();

		var oPercentCourse = null;
		var oPercentAccommodation = null;
		var oPercentTransfer = null;
		var oPercentAdditional = null;
		var oPercentDependency = null;
		var oPercentConditionBlock = null;

		if(
			oSelect.up('.GUIDialogRow') &&
			oSelect.up('.GUIDialogRow').next('.percent_course')
		){
			oPercentCourse = oSelect.up('.GUIDialogRow').next('.percent_course');
			oPercentAccommodation = oSelect.up('.GUIDialogRow').next('.percent_accommodation');
			oPercentTransfer = oSelect.up('.GUIDialogRow').next('.percent_transfer');
			oPercentAdditional = oSelect.up('.GUIDialogRow').next('.percent_additional');
			oPercentDependency = oSelect.up('.GUIDialogRow').next('.percent_dependency_on_duration');
			oPercentDependencyCheckbox = $j(oPercentDependency).find('input[type=checkbox].switch_condition_block').get(0);
			oPercentConditionBlock = oSelect.up('.GUIDialogRow').next('.percent_condition_block');
		}

		switch(iValue){
			case 1:
				oPercentCourse.show();
				oPercentDependency.show();
				if(oPercentDependencyCheckbox.checked) {
					oPercentConditionBlock.show();
				}
				oPercentAccommodation.hide();
				oPercentTransfer.hide();
				oPercentAdditional.hide();
				break;
			case 2:
				oPercentCourse.hide();
				oPercentDependency.show();
				if(oPercentDependencyCheckbox.checked) {
					oPercentConditionBlock.show();
				}
				oPercentAccommodation.show();
				oPercentTransfer.hide();
				oPercentAdditional.hide();
				break;
			case 3:
				oPercentCourse.hide();
				oPercentDependency.hide();
				oPercentConditionBlock.hide();
				oPercentAccommodation.hide();
				oPercentTransfer.show();
				oPercentAdditional.hide();
				break;
			case 4:
				oPercentCourse.hide();
				oPercentDependency.hide();
				oPercentConditionBlock.hide();
				oPercentAccommodation.hide();
				oPercentTransfer.hide();
				oPercentAdditional.show();
				break;
			default:
				oPercentCourse.hide();
				oPercentDependency.hide();
				oPercentConditionBlock.hide();
				oPercentAccommodation.hide();
				oPercentTransfer.hide();
				oPercentAdditional.hide();
		}

		if(
			oPercentDependency &&
			oPercentConditionBlock
		) {
			this.setDependencyConditionEvents();
		}

	},

	setDependencyConditionEvents: function() {
		
		var aDependencyCheckboxes = $$('.switch_condition_block');
		aDependencyCheckboxes.each(function(oCheckbox) {
			if(oCheckbox.type !== 'hidden') {
				oCheckbox.stopObserving('click');
				Event.observe(oCheckbox, 'click', function(){

					var sBlockId = oCheckbox.id.replace('dependencyduration', 'conditionblock');
					var oPercentConditionBlock = $(sBlockId);

					if(oPercentConditionBlock) {
						if(oCheckbox.checked) {
							oPercentConditionBlock.show();
						} else {
							oPercentConditionBlock.hide();
						}
					}
				}.bind(this));
			}
		}.bind(this));
		
		var aButtons = $$('.condtion_row_button');
		aButtons.each(function(oButton){
			oButton.stopObserving('click');
			Event.observe(oButton, 'click', function(){				
				if(oButton.hasClassName('add_condtion_row')) {
					var oConditionBlock = oButton.up('.condition_block');
					this.addConditionRow(oButton, oConditionBlock);
				} else if(oButton.hasClassName('delete_condtion_row')) {
					if(confirm(this.getTranslation('delete_condition_row_question'))) {
						var oConditionRow = oButton.up('.condition_row');
						this.deleteConditionRow(oConditionRow);
					}
				}
			}.bind(this));
		}.bind(this));
	},

	addConditionRow: function(oButton, oConditionBlock) {
		
		if(!this.aConditionCounterCache[oConditionBlock.id]) {
			this.aConditionCounterCache[oConditionBlock.id] = -1;
		}
		
		var aRows = $$('#'+oConditionBlock.id + ' .condition_row');
		var oRow = aRows[0];
		if(oRow) {
					
			var sHtml = oRow.innerHTML;
			
			var iBlock = oRow.getAttribute('data-block');
			
			var oNewRow = new Element('div', {
				id: 'condition_row_'+ iBlock +'_' + this.aConditionCounterCache[oConditionBlock.id],
				'class': 'condition_row',
				'style': 'padding: 0'
			}).update(sHtml);
			
			var oWeekInput = oNewRow.down('.condition_week');
			var oSymbolSelect = oNewRow.down('.condition_symbol');
			oWeekInput.value = '';
			oSymbolSelect.value = 0;

			aRows[aRows.length - 1].insert({
				after: oNewRow
			});
			
			var oDeleteIcon = oNewRow.down('.delete_condtion_row');
			oDeleteIcon.show();
			oDeleteIcon.style.visibility = 'visible';
			oDeleteIcon.stopObserving('click');
			Event.observe(oDeleteIcon, 'click', function(){
				if(confirm(this.getTranslation('delete_condition_row_question'))) {
					this.deleteConditionRow(oNewRow);
				}
			}.bind(this));
			
			--this.aConditionCounterCache[oConditionBlock.id];

		}
	},

	deleteConditionRow: function(oConditionRow) {
		if(oConditionRow) {
			oConditionRow.remove();
		}
	},

	/*
	 * Absolut Art Select
	 */
	switchAbsolutKind : function(aData, oSelect){

		var iValue = $F(oSelect);
		iValue = iValue.parseNumber();

		var oAbsolutCourse = null;
		var oAbsolutAccommodation = null;
		var oAbsolutDependency = null;
		var oAbsolutConditionBlock = null;

		if(
			oSelect.up('.GUIDialogRow') &&
			oSelect.up('.GUIDialogRow').next('.absolut_course')
		){
			oAbsolutCourse = oSelect.up('.GUIDialogRow').next('.absolut_course');
			oAbsolutAccommodation = oSelect.up('.GUIDialogRow').next('.absolut_accommodation');
			oAbsolutDependency = oSelect.up('.GUIDialogRow').next('.absolut_dependency_on_duration');
			oAbsolutDependencyCheckbox = $j(oAbsolutDependency).find('input[type=checkbox].switch_condition_block').get(0);
			oAbsolutConditionBlock = oSelect.up('.GUIDialogRow').next('.absolut_condition_block');
		}
		
		oAbsolutCourse.hide();
		oAbsolutAccommodation.hide();

		switch(iValue){
			case 1:
				oAbsolutDependency.hide();
				oAbsolutConditionBlock.hide();
				oAbsolutDependencyCheckbox.checked = false;
				break;
			case 2:
				oAbsolutCourse.show();
				oAbsolutDependency.show();
				if(oAbsolutDependencyCheckbox.checked) {
					oAbsolutConditionBlock.show();
				}	
				break;
			case 3:
				oAbsolutAccommodation.show();
				oAbsolutDependency.show();
				if(oAbsolutDependencyCheckbox.checked) {
					oAbsolutConditionBlock.show();
				}
				break;
			case 4:
				oAbsolutDependency.show();
				if(oAbsolutDependencyCheckbox.checked) {
					oAbsolutConditionBlock.show();
				}	
				oAbsolutCourse.show();
				break;
			case 5:
				oAbsolutAccommodation.show();
				oAbsolutDependency.show();
				if(oAbsolutDependencyCheckbox.checked) {
					oAbsolutConditionBlock.show();
				}	
				break;
		}

		if(
			oAbsolutDependency &&
			oAbsolutConditionBlock
		) {
			this.setDependencyConditionEvents();
		}

	},

	/*
	 * Week Art Select
	 */
	switchWeekKind : function(aData, oSelect){
		var iValue = $F(oSelect);
		iValue = iValue.parseNumber();

		var oWeekCourse = null;
		var oWeekAccommodation = null;

		if(
			oSelect.up('.GUIDialogRow') &&
			oSelect.up('.GUIDialogRow').next('.week_course')
		){
			oWeekCourse = oSelect.up('.GUIDialogRow').next('.week_course');
			oWeekAccommodation = oSelect.up('.GUIDialogRow').next('.week_accommodation');
		}

		switch(iValue){
			case 1:
				oWeekCourse.show();
				oWeekAccommodation.hide();
				break;
			case 2:
				oWeekCourse.hide();
				oWeekAccommodation.show();
				break;
			default:
				oWeekCourse.hide();
				oWeekAccommodation.hide();
		}

	},



	// -------------------------------------------------------------------------------------

	/*
	 * Clont den aktuellen Special-Block
	 */
	cloneSpecialBlock : function(oIcon){

		// Prüfen welcher geclont werden soll
		var oSelectHowMany = null;
		$$('.select_how_many').each( function (oDivHowMany) {
			if(oDivHowMany.down('select')){
				oSelectHowMany = oDivHowMany.down('select');
				return;
			}
		}.bind(this));

		if(oSelectHowMany){
			var iValue = $F(oSelectHowMany);
			iValue = iValue.parseNumber();

			// Klasse bestimmen des zu klonenden blocks
			var sBlockClass = '';
			var sNewId		= '';
			switch(iValue){
				case 1:
					sBlockClass = 'div_percent_class';
					sNewId		= 'div_percent_';
					break;
				case 2:
					sBlockClass = 'div_absolut_class';
					sNewId		= 'div_absolut_';
					break;
				case 3:
					sBlockClass = 'div_week_class';
					sNewId		= 'div_week_';
					break;
				default:
					sBlockClass = 'div_percent_class';
					sNewId		= 'div_percent_';
			}

			// Letzter Block holen
			var oBlock = null;
			$$('.'+sBlockClass).each( function (oDivBlock) {
				oBlock = oDivBlock;
			}.bind(this));

			// Wenn gefunden
			if(oBlock){

				// Block ID muss hochgezählt werden
				var iBlockCount = 0;
				var aMatchBlockId = oBlock.id.match(/([a-z].*?)_([a-z].*?)_([0-9].*?)/);
				if(aMatchBlockId){
					iBlockCount = aMatchBlockId[3];
				}
				iBlockCount = iBlockCount.parseNumber();


				// HTML bearbeiten
				var sHtml = oBlock.innerHTML;

				// Währungsblöcke anpassen ---------------------------------------------------
				var aMatchCurrencyName = sHtml.match(/name="save\[([a-z].*?)\]\[([a-z].*?)\]\[([0-9].*?)\]\[([0-9].*?)\]"/g);

				if(aMatchCurrencyName){
					// Jeden Fund durchgehen und id abändern
					aMatchCurrencyName.each(function(sFound, iIndex){
						var aMatchTemp = sFound.match(/name="save\[([a-z].*?)\]\[([a-z].*?)\]\[([0-9].*?)\]\[([0-9].*?)\]"/);

						if(aMatchTemp){
							var sType		= aMatchTemp[1];
							var sField		= aMatchTemp[2];
							var iCountField = aMatchTemp[3].parseNumber();
							var iCurrency = aMatchTemp[4].parseNumber();

							// Counter für Feld hochzählen
							var iCountFieldNew = iCountField + 1;
							sHtml = sHtml.replace('name="save['+sType+']['+sField+']['+iCountField+']['+iCurrency+']"', 'name="save['+sType+']['+sField+']['+iCountFieldNew+']['+iCurrency+']"');
						}

					});
				}

				// Multiselects anpassen -----------------------------------------------------
				// var aMatchMultiselectName = sHtml.match(/name="save\[([a-z]+?)\]\[([a-z]+?)\]\[([0-9]+?)\]\[\]"/g);
				// Achtung: Nicht nur Multiselects, sondern auch Felder wie condition_weeks, aber nicht dependency_on_duration
				var aMatchMultiselectName = sHtml.match(/name="save\[([a-z]+?)\]\[([a-z_]+?)\]\[([0-9]+?)\]\[\]"/g);

				if(aMatchMultiselectName){
					// Jeden Fund durchgehen und id abändern
					aMatchMultiselectName.each(function(sFound, iIndex){
						var aMatchTemp = sFound.match(/name="save\[([a-z].*?)\]\[([a-z].*?)\]\[([0-9].*?)\]\[\]"/);

						if(aMatchTemp){
							var sType		= aMatchTemp[1];
							var sField		= aMatchTemp[2];
							var iCountField = aMatchTemp[3].parseNumber();

							// Counter für Feld hochzählen
							var iCountFieldNew = iCountField + 1;
							sHtml = sHtml.replace('name="save['+sType+']['+sField+']['+iCountField+'][]"', 'name="save['+sType+']['+sField+']['+iCountFieldNew+'][]"');
							
							if(sField === 'condition_weeks') {
								sHtml = sHtml.replace('name="save['+sType+'][condition_symbol]['+iCountField+'][]"', 'name="save['+sType+'][condition_symbol]['+iCountFieldNew+'][]"');
							}
						}
					});
				}

				// Abhänigkeit - Checkbox anpassen -----------------------------------------------------
				var aMatchConditionCheckboxName = sHtml.match(/name="save\[([a-z]+?)\]\[dependency_on_duration]\[([0-9]+?)\]"/g);

				if(aMatchConditionCheckboxName){
					// Jeden Fund durchgehen und id abändern
					aMatchConditionCheckboxName.each(function(sFound, iIndex){
						var aMatchTemp = sFound.match(/name="save\[([a-z].*?)\]\[dependency_on_duration]\[([0-9].*?)\]"/);
						if(aMatchTemp){
							var sType		= aMatchTemp[1];
							var iBlock		= aMatchTemp[2].parseNumber();

							var iNewBlock = iBlock + 1;
							sHtml = sHtml.replace('name="save['+sType+'][dependency_on_duration]['+iBlock+']"', 'name="save['+sType+'][dependency_on_duration]['+iNewBlock+']"');
						}
					});
				}

				// Ersetzungen für IDs finden -------------------------------------------------
				var aMatch = sHtml.match(/id="([a-z].*?)_([a-z].*?)_([0-9].*?)"/g);

				if(aMatch){
					// Jeden Fund durchgehen und id abändern
					aMatch.each(function(sFound, iIndex){
						var aMatchTemp = sFound.match(/id="([a-z].*?)_([a-z].*?)_([0-9].*?)"/);

						if(aMatchTemp){
							var sType		= aMatchTemp[1];
							var sField		= aMatchTemp[2];
							var iCountField = aMatchTemp[3].parseNumber();

							// Counter für Feld hochzählen
							var iCountFieldNew = iCountField + 1;
							sHtml = sHtml.replace('id="'+sType+'_'+sField+'_'+iCountField+'"', 'id="'+sType+'_'+sField+'_'+iCountFieldNew+'"');
						}

					});
				}
				//-----------------------------------------------------------------------------

				iBlockCount = iBlockCount + 1;
				var sNewId = sNewId + iBlockCount;


				$(oBlock).insert({
					after: new Element('div', {
						id: sNewId,
						'class': sBlockClass + ' div_special_block  GUIDialogJoinedObjectContainerRow',
						'style': 'margin-bottom: 5px'
					}).update(sHtml)
				});

				// Geclonte Multiselects killen
				$$('#'+sNewId+' .ui-multiselect').each( function (oDiv) {
					oDiv.remove();				
				}.bind(this));
				
				var oNewFieldSet = $(sNewId);	
				
				this.prepareBlockConditions(oNewFieldSet, iBlockCount);

				// Multiselects neu initialisieren
				this.initializeMultiselects(this.aData);

				// Observer für Dialog neu setzen
				this.setDialogObserver(this.aData);
			}

		}

	},

	prepareBlockConditions: function(oFieldset, iBlockCount) {
	
		var oConditionBlock = oFieldset.down('.condition_block');
		if(oConditionBlock) {
			var aRows = $$('#'+oConditionBlock.id + ' .condition_row');
			var iRowCount = 0;
			aRows.each(function(oRow) {
				if(iRowCount > 0) {
					oRow.remove();
				} else {
					var oWeekInput = oRow.down('.condition_week');
					var oSymbolSelect = oRow.down('.condition_symbol');
					oWeekInput.value = '';
					oSymbolSelect.value = 0;
				}
				++iRowCount;
				
				oRow.setAttribute('data-block', iBlockCount);	
			}.bind(this));
			
			// Block ausblenden
			var aDependencyCheckbox = $$('#'+oFieldset.id+' .switch_condition_block');
			aDependencyCheckbox.each(function(oCheckbox) {
				if(
					oCheckbox.tagName === 'INPUT' &&
					oCheckbox.type === 'checkbox'
				) {		
					oCheckbox.checked = false;
				}
			}.bind(this));
			
			oConditionBlock.hide();
		}

	},

	/*
	 * Löscht einen Stornoblock
	 */
	deleteSpecialBlock : function(oIcon){
		oIcon.up('.div_special_block').remove();
	}

    
});
