
var DocumentRelease = Class.create(CoreGUI, {
	
	aData : new Array(),
	
	requestCallbackHook : function ($super, aData){
		
		$super(aData);

		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			) &&
			(
				aData.data.action == 'release' ||
                aData.data.action == 'print_invoice'||
                aData.data.action == 'xml_export_it'||
                aData.data.action == 'xml_export_it_final'
			)
		) {
			this.aData = aData.data;
		}
	},
	
	additionalFilterHook : function(sParam){
		
		if(
			this.aData.action == 'release' ||
			this.aData.action == 'print_invoice'||
			this.aData.action == 'xml_export_it'||
			this.aData.action == 'xml_export_it_final'	
		){
				
			var sDocumentId;
				
			$$('.multiple_checkbox').each(function(oCheckbox){

				if(oCheckbox.id && oCheckbox.checked)
				{
					sDocumentId = oCheckbox.id.replace('multiple_', '');
					
					sParam += '&document_ids[]=' + sDocumentId;
				} else if(oCheckbox.checked) {
                    var oRow = oCheckbox.up('tr');
                    if(oRow){
                        var sID = oRow.id;
                        var aID = sID.split('_');
                        var iID = aID[2];
                        // nur die Checkboxen der Inner GUI auslesen!
                        if(aID[1] != this.hash){
                            sParam += '&document_ids[]=' + iID;
                        }
                    }
                }
				
			}.bind(this));
			
		}
		
		return sParam;
		
	}
	
});