
var TranslationsGui = Class.create(CoreGUI,
{
	requestCallbackHook: function(aData)
	{

		var sReleaseId = 'filter_release_'+this.hash;

		if(aData.action == 'createTable') {
			
			// Event auf Release-Select setzen
			Event.observe($(sReleaseId), 'change', function() {
				this.reloadReleaseVersions();
			}.bind(this));

		}

	},
	
	reloadReleaseVersions : function() {
		
		var sReleaseId = 'filter_release_'+this.hash;
		
		var sRelease = $F(sReleaseId);
			
		console.debug(sRelease);
		
	}

});