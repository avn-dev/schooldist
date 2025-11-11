
var FrontendTemplatesFieldGui = Class.create(CoreGUI,
{

	requestCallbackHook: function($super, aData)
	{
		$super(aData);

		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback' ||
				aData.action == 'reloadDialogTab'
			) && (
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {

			this.sFieldPrefix = 'save['+this.hash+']['+aData.data.id+']';
			var oOverwriteTemplate = $(this.sFieldPrefix + '[overwrite_template][tc_ftf]');

			this.toggleTemplateTextarea();
			oOverwriteTemplate.observe('change', this.toggleTemplateTextarea.bind(this))

		}

		// Dafür sorgen, dass die Vorlage geladen wird
		if(aData.action == 'update_select_options') {
			this.reloadDialogTab(this.sCurrentDialogId, -1);
		}

	},

	toggleTemplateTextarea: function()
	{
		var oOverwriteTemplate = $(this.sFieldPrefix + '[overwrite_template][tc_ftf]');
		var oTemplateTextareaContainer = $(this.sFieldPrefix + '[template][tc_ftf]').up(1);

		if(!oOverwriteTemplate.checked) {
			oTemplateTextareaContainer.hide();
		} else {
			oTemplateTextareaContainer.show();
		}
	}

});