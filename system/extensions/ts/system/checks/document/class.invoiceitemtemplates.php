<?php

class Ext_TS_System_Checks_Document_InvoiceItemTemplates extends GlobalChecks {

	public function getTitle() {
		return 'Update invoice item templates';
	}

	public function getDescription() {
		return 'New template engine';
	}

	public function executeCheck() {
		
		$aPlaceholderMapping = [
			'name',
			'description',
			'weeks_units',
			'course',
			'from',
			'until',
			'weeks', 
			'accommodation',
			'roomtype',
			'meal',
			'nights',
			'transfer',
			'to',
			'weekday',
			'date',
			'time',
			'insurance',
			'special'
		];
		
		foreach($aPlaceholderMapping as $sPlaceholder) {
			
			$aSql = [
				'placeholder' => '{'.$sPlaceholder.'}',
				'placeholder_replace' => '{$'.$sPlaceholder.'}'
			];
			DB::executePreparedQuery("UPDATE `kolumbus_positions_order` SET `title` = REPLACE(`title`, :placeholder, :placeholder_replace)", $aSql);
			
		}
		
		// Platzhalter {category} bei Unterkünften entfernen, weil nicht benutzt
		DB::executePreparedQuery("UPDATE `kolumbus_positions_order` SET `title` = REPLACE(`title`, '{category} ', '')", $aSql);
		
		return true;
	}
	
}
