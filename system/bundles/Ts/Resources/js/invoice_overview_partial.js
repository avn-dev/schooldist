(function () {

	$j('#partial_invoices_container .mark-generated, #partial_invoices_container .unmark-generated').off('click').click(function() {

		$j('.partial-invoices-loading').show();

		var guiHash = Object.keys(aGUI)[0];

		const id = $j(this).parents('tr').data('id');

		if($j(this).hasClass('mark-generated')) {
			aGUI[guiHash].requestBackground('&task=request&action=mark-generated&partial_invoice_id='+id);
		} else {
			aGUI[guiHash].requestBackground('&task=request&action=mark-generated&undo=1&partial_invoice_id='+id);
		}

	});

	$j('.partial-invoices-refresh').off('click').click(function() {

		$j(this).hide();
		$j('.partial-invoices-loading').show();

		var guiHash = Object.keys(aGUI)[0];

		aGUI[guiHash].requestBackground('&task=request&action=partial-invoices-refresh');

	});

	$j('.partial-invoices-add').off('click').click(function() {

		$j('#partial_invoices_deposit_row').show();

	});

	$j('#partial_invoices_deposit_row button[type=reset]').off('click').click(function() {

		$j('#partial_invoices_deposit_row').hide();

	});

	$j('#partial_invoices_deposit_row button[type=submit]').off('click').click(function(event) {

		event.preventDefault();

		var guiHash = Object.keys(aGUI)[0];

		aGUI[guiHash].requestBackground('&task=request&action=partial-invoices-save-deposit&date='+$j('#partial_invoices_deposit_date').val()+'&amount='+$j('#partial_invoices_deposit_amount').val());

	});

	$j('.generate-invoice').off('click').click(function() {

		const params = new URLSearchParams();
		params.append('task', 'request');
		params.append('action', 'generate-invoice');
		params.append('company_id', $j(this).data('company-id'));

		var guiHash = Object.keys(aGUI)[0];

		aGUI[guiHash].requestBackground(params);

	});

})();
