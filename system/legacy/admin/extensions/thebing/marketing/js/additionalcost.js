var AdditionalCost = Class.create(ATG2,
{

	requestCallbackHook: function($super, aData)
	{
		$super(aData);

		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			)
			&&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {
	
			this.toggleStockTab();
			this.costToggle(aData.data);

			// Feld ist nur da, wenn Mandant das Recht dafür hat
			// TODO Auf dependency_visibility umstellen?
			var oAccountingTimepoint = $('save['+this.hash+']['+aData.data.id+'][timepoint][kcos]');
			if(oAccountingTimepoint) {
				var oPriceCalcSelect = $('save['+this.hash+']['+aData.data.id+'][calculate][kcos]');
				oPriceCalcSelect.observe('change', this.togglePriceCalcSelect.bind(this, oPriceCalcSelect, oAccountingTimepoint));
				this.togglePriceCalcSelect(oPriceCalcSelect, oAccountingTimepoint);
			}

		}
	},

	togglePriceCalcSelect: function(oPriceCalcSelect, oAccountingTimepoint) {

		// Pro Kurs- / Unterkunftswoche
		if($F(oPriceCalcSelect) == 2) {
			oAccountingTimepoint.up('.GUIDialogRow').hide();
			// oAccountingTimepoint.up('.GUIDialogRow').previous().hide();
		} else {
			oAccountingTimepoint.up('.GUIDialogRow').show();
			// oAccountingTimepoint.up('.GUIDialogRow').previous().show();
		}

	},

	costToggle : function (aDialogData) {

		var oSelectType = $('save['+this.hash+']['+aDialogData.id+'][type][kcos]');

		var oSelectCourses = $('save['+this.hash+']['+aDialogData.id+'][costs_courses][kcos]');
		var oSelectAccommodation = $('save['+this.hash+']['+aDialogData.id+'][costs_accommodations][kcos]');
		var oDependencyOnDuration = $('save['+this.hash+']['+aDialogData.id+'][dependency_on_duration][kcos]');
		var oAgeDependency = this.getDialogSaveField('dependency_on_age');
		var oDivCourses = oSelectCourses.up('.GUIDialogRow');
		var oDivAccommodation = oSelectAccommodation.up('.GUIDialogRow');
		var oDivDependencyOnDuration = oDependencyOnDuration.up('.GUIDialogRow');
		var oDivNoPriceDisplay = $('save['+this.hash+']['+aDialogData.id+'][no_price_display][kcos]').up('.GUIDialogRow');
		var sCharge = $('save['+this.hash+']['+aDialogData.id+'][charge][kcos]').value;

		var oDivGroupsettings = $('groupsettings');

		var oFrontendTab = $j('.GUIDialogTabHeader.frontend-tab');
		oFrontendTab.hide();

		if(oSelectType) {

			// Kurs
			if(0 == oSelectType.selectedIndex ) {

				oDivAccommodation.hide();
				oDivCourses.show();
				this.resetElementOptions(oSelectAccommodation);

				// gruppeneinstellungen verbergen
				oDivGroupsettings.hide();
				
				if(sCharge === 'auto') {
					oDivDependencyOnDuration.show();
					oAgeDependency.closest('.GUIDialogRow').show();
					oDivNoPriceDisplay.show();
				} else {
					oDivDependencyOnDuration.hide();
					oAgeDependency.closest('.GUIDialogRow').hide();
					oDivNoPriceDisplay.hide();
					oFrontendTab.show();
				}

			// Unterkunft
			} else if(1 == oSelectType.selectedIndex) {

				oDivCourses.hide();
				oDivAccommodation.show();
				this.resetElementOptions(oSelectCourses);

				// gruppeneinstellungen verbergen
				oDivGroupsettings.hide();

				if(sCharge === 'auto') {
					oDivDependencyOnDuration.show();
					oAgeDependency.closest('.GUIDialogRow').show();
					oDivNoPriceDisplay.show();
				} else {
					oDivDependencyOnDuration.hide();
					oAgeDependency.closest('.GUIDialogRow').hide();
					oDivNoPriceDisplay.hide();
					oFrontendTab.show();
				}

			// Generell (2)
			} else {

				oDivCourses.hide();
				oDivAccommodation.hide();
				this.resetElementOptions(oSelectAccommodation);
				this.resetElementOptions(oSelectCourses);

				// gruppeneinstellungen verbergen
				oDivGroupsettings.show();
				oDivDependencyOnDuration.hide();
				oDependencyOnDuration.checked = false;
				oAgeDependency.prop('checked', false).change().closest('.GUIDialogRow').hide();
				oDivNoPriceDisplay.hide();
				oFrontendTab.show();

			}

		}
		
		this.toggleCalculationSettings(aDialogData);
		
	},

	resetElementOptions : function (oElement) {
		var oDiv = oElement.up('.GUIDialogRow');
		var oLinkRemove = oDiv.down('a.remove-all');
		this._fireEvent('click', oLinkRemove);
	},

	toggleCalculationSettings: function(aDialogData) {
		
		var oDependencyOnDuration = $('save['+this.hash+']['+aDialogData.id+'][dependency_on_duration][kcos]');
		var oCombinationContainer	= $('joinedobjectcontainer_calculation_combination');
		if(oDependencyOnDuration.checked) {
			oCombinationContainer.show();
		} else {
			oCombinationContainer.hide();
		}

		var oAgeContainer = $j('.age_container');
		var oAgeDependency = this.getDialogSaveField('dependency_on_age');
		if(oAgeDependency.prop('checked')) {
			oAgeContainer.show();
		} else {
			oAgeContainer.hide();
		}
		
	},
	
	toggleStockTab : function() {
		
		var bChecked = $j('.stock-checkbox').is(':checked');
		
		if(bChecked) {
			$j('.GUIDialogTabHeader.stock-tab').show();
		} else {
			$j('.GUIDialogTabHeader.stock-tab').hide();
		}
		
	},

});


