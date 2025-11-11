var ReceiptText = Class.create(CoreGUI, {
	
	showBasedOnElements : function(oDialogData)
	{
		var sDialogId = oDialogData.id;
		
		this.reloadDialogTab(sDialogId, 0);
	}
	
});