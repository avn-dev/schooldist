
var Price = Class.create(ATG2, {

	aCalenderConfig: {},

	requestCallbackHook: function($super, aData) {

		if(
			aData.action == 'translations'
		)
		{
			this.initCalendar();
		}

	},

	initCalendar: function(){
		$j('.datepicker').datepicker(this.aCalenderConfig);
	},

	addNewPriceRow: function(iCategorie, bCosts) {
		var oElement = $('copy_row_'+iCategorie);
		var oRow = oElement.cloneNode(true);
		
		$j(oRow).find('input').val('');

		oElement.insert({
			after: oRow
		} );
		oElement.id = '';

		if(bCosts) {
			oRow.descendants().each(function(oChild) {
				if(oChild.name) {
					var oRegExp = /^(cost.*\[nights\]\[)(-?\d+)(\].*)/;
					var iCount = parseInt(oChild.name.match(oRegExp)[2]);
					if(isNaN(iCount)) {
						throw 'Regex for name field did not return a valid number!';
					}
					iCount--;
					oChild.name = oChild.name.replace(oRegExp, '$1' + iCount + '$3');
				}
			});
		}

		oGui.initCalendar();
		
		
	}

});