
var ExchangeRate = Class.create(CoreGUI, {
	
	requestCallbackHook : function ($super, aData){

		$super(aData);
		
		if(
			( 
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			) &&
			aData.data.additional == null &&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {

            this.loadXMLFile(aData.data);

		} else if(aData.action == 'openXmlFile') {
			
			$('XmlOutput').innerHTML = aData.xml;
			
		}
		
	},
	
	loadXMLFile : function(aData) {		
		
		var oXmlUrl = $('save['+this.hash+']['+aData.id+'][url][tc_ets]');
		
		if(oXmlUrl){ 
			if(oXmlUrl.value != ''){
				
				var sValue = oXmlUrl.value;
				
				var sParam = '&task=openXmlFile';
				sParam += '&XmlUrl=' + encodeURIComponent(sValue);
				
				this.request(sParam);
			}
		}
	}
	
});

