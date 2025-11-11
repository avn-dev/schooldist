
var FrontendTemplatesGUI = Class.create(CoreGUI, {

	requestCallbackHook: function($super, aData) {
		$super(aData);

		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			) && (
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {

			var oUsageField = this.getDialogSaveField('usage');
			var oUseDefaultTemplateCheckbox = this.getDialogSaveField('use_default_template');
			var oCodeTextareaRow = this.getDialogSaveField('code').closest('.GUIDialogRow');
			var oNoDefaultTemplateWarning = $j('#no_default_template_warning');

			oUsageField.change(function() {
				// Default-Checkbox einblenden und anwählen
				if(this.hasUsageDefaultTemplate(oUsageField, aData.data.default_templates)) {
					oUseDefaultTemplateCheckbox.closest('.GUIDialogRow').show();
					if(aData.data.action == 'new') {
						oUseDefaultTemplateCheckbox.prop('checked', true);
					}
				} else {
					oUseDefaultTemplateCheckbox.closest('.GUIDialogRow').hide();
					oUseDefaultTemplateCheckbox.prop('checked', false);
				}

				// Alle weiteren Tabs einblenden/ausblenden
				$j('.GUIDialogTabDiv ul > li').each(function(iIndex, oTab) {
					if(iIndex == 0) {
						return true;
					}

					if(
						aData.data.action === 'new' ||
						!this.hasUsageTabs(oUsageField, aData.data.usages_with_tabs)
					) {
						$j(oTab).hide();
					} else {
						$j(oTab).show();
					}
				}.bind(this));

				oUseDefaultTemplateCheckbox.change();
			}.bind(this));

			// Code-Feld anzeigen oder verstecken (plus Warnung)
			oUseDefaultTemplateCheckbox.change(function() {
				oNoDefaultTemplateWarning.hide();

				if(oUseDefaultTemplateCheckbox.prop('checked')) {
					oCodeTextareaRow.hide();
				} else {
					oCodeTextareaRow.show();

					if(this.hasUsageDefaultTemplate(oUsageField, aData.data.default_templates)) {
						oNoDefaultTemplateWarning.show();
					}
				}
			}.bind(this));

			oUsageField.change();

		}

	},

	/**
	 * @param {jQuery} oUsageField
	 * @param {Array} aDefaultTemplates
	 * @returns {Boolean}
	 */
	hasUsageDefaultTemplate: function(oUsageField, aDefaultTemplates) {
		return aDefaultTemplates.indexOf(oUsageField.val()) !== -1;
	},

	/**
	 * @param {jQuery} oUsageField
	 * @param {Array} aUsagesWithTabs
	 * @returns {Boolean}
	 */
	hasUsageTabs: function(oUsageField, aUsagesWithTabs) {
		return aUsagesWithTabs.indexOf(oUsageField.val()) !== -1;
	}

});


