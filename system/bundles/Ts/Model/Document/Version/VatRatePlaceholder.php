<?php

namespace Ts\Model\Document\Version;

class VatRatePlaceholder extends \Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'oVatRate'
	];

	protected $_aPlaceholders = [
		'amount' => [
			'label' => 'Brutto-Betrag',
			'type' => 'field',
			'source' => 'amount'
		],
		'amount_net' => [
			'label' => 'Netto-Betrag',
			'type' => 'field',
			'source' => 'amount_net'
		],
		'vat' => [
			'label' => 'Umsatzsteuer-Betrag',
			'type' => 'field',
			'source' => 'vat'
		],
		'vat_rate' => [
			'label' => 'Steuersatz',
			'type' => 'field',
			'source' => 'vat_rate'
		],
		'note' => [
			'label' => 'Hinweis',
			'type' => 'field',
			'source' => 'note'
		],
		'lines' => [
			'label' => 'Positionen',
			'type' => 'method',
			'source' => 'getLines',
			'variable_name' => 'sVatRateLines'
		],
		'lines_count' => [
			'label' => 'Anzahl Positionen',
			'type' => 'method',
			'source' => 'getLinesCount',
			'variable_name' => 'iVatRateLinesCount'
		],
	];
	
}
