
	function updateRequirement() {
		if($j('#ignore_requirement').prop('checked')) {
			$j('.requirement-invalid').show();
		} else {
			$j('.requirement-invalid').find('.provider-checkbox').prop('checked', false);
			$j('.requirement-invalid').hide();
		}
	}

	function executeSearch() {
	
		var search = $j('#provider_search').val();
		
		$j('#provider_table tbody tr').hide();
		$j('#provider_table tbody tr').filter(function() {
			var regexp = new RegExp(search, 'i')
			return $j(this).find('td').text().match(regexp) !== null;
		}).show();
		
	}
	
	$j('#provider_search').keyup(function() {
		executeSearch();
	});
	
	$j('#provider-checkbox-checkall').change(function() {
		
		$j('.provider-checkbox:visible').prop('checked', $j(this).prop('checked'));
	});
	
	$j('#ignore_requirement').change(function() {
		updateRequirement();
	});
	
	$j('.filter-backend').change(function() {
		
		const hash = Object.keys(aGUI)[0];
		aGUI[hash].reloadDialogTab(this.sCurrentDialogId, 0);
	
	});
	
	executeSearch();
	updateRequirement();