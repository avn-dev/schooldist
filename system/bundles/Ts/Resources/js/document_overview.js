(function () {
	const container = $j('#table-additional-service-documents');
	const button = container.find('input[type=button]');

	button.click(async () => {
		button.prop('disabled', true);

		const params = new URLSearchParams();
		params.append('task', 'request');
		params.append('action', 'create-additional-service-documents');
		params.append('inquiry_id', container.data('inquiry-id'));

		for (let el of container.find('tbody tr')) {
			el = $j(el);
			if (el.find('input[type=checkbox]').prop('checked')) {
				const templateId = el.data('template-id');
				params.append('template_id[]', templateId);
			}
		}

		const hash = Object.keys(aGUI)[0];
		const response = await aGUI[hash].request2(params);

		if (response.success) {
			container.prev('.alert-success').show();
			button.prop('disabled', false);
			container.find('tbody input[type=checkbox]').prop('checked', false);
		}
	});
})();
