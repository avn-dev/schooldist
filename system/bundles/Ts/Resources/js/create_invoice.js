function recalculateSums() {

	var sums = {};
	var guiHash = Object.keys(aGUI)[0];
	var gui = aGUI[guiHash];

	$j('.create-invoice-container tbody tr').each(function() {
		var amoutNetField = $j(this).find('.amount_net');
		if(amoutNetField.length) {
			amoutNetField.val(gui.thebingNumberFormat(gui.getNumericValue($j(this).find('.amount').get(0)) - gui.getNumericValue($j(this).find('.amount_provision').get(0))));
		}		
	});

	$j('.create-invoice-container input.amount-field').each(function() {

		if(!sums[$j(this).data('company-id')]) {
			sums[$j(this).data('company-id')] = {};
		}
		if(!sums[$j(this).data('company-id')][$j(this).data('field')]) {
			sums[$j(this).data('company-id')][$j(this).data('field')] = 0;
		}
		sums[$j(this).data('company-id')][$j(this).data('field')] += gui.getNumericValue(this);

	});

	var amountTotal = {};
	for (const companyId in sums) {
		for(const field in sums[companyId]) {
			$j('#'+field+'-total-'+companyId).val(gui.thebingNumberFormat(sums[companyId][field]));
			if(!amountTotal[field]) {
				amountTotal[field] = 0;
			}
			amountTotal[field] += sums[companyId][field];
		}
	}

	for (const field in amountTotal) {
		$j('#'+field+'-total').val(gui.thebingNumberFormat(amountTotal[field]));
	}

}

(function () {

	$j('.create-invoice-container input.amount-field').off('update').on('input', function() {
		recalculateSums();
	});

	recalculateSums();

})();
