/*
 * Util Klasse
 */
var ContractsGui = Class.create(ATG2, {
    
	updateIconCallbackHook: function(aData) {

		var sIcon = 'fa-check-circle';
		var sLabel = this.getTranslation('contract_confirm');
		if(
			aData.selectedRows && 
			aData.selectedRows.length === 1
		) {
			$j(aData.body[0].items).each(function(i, item) {
				if(item.db_column === 'confirmed') {
					if(item.original !== 0) {
						sIcon = 'fa-minus-circle';
						sLabel = this.getTranslation('contract_de_confirm');
					}
					return false;
				}
			}.bind(this));		
		}

		var oIconDiv = $('contract_confirm_'+this.hash);

		if(oIconDiv){
			var oImg = oIconDiv.down();
			if(oImg) {
				$j(oImg).removeClass('fa-minus-circle');
				$j(oImg).removeClass('fa-check-circle');
				$j(oImg).addClass(sIcon);
			}
			var oLabel = oIconDiv.next();

			if(oLabel){
				oLabel.update(sLabel); 
			}
		}
		
		
		return aData;
	}
	
	
});