<?php

namespace TsAccounting\Service\Interfaces;

use TsAccounting\Dto\BookingStack\ExportFileContent;

class Xero extends AbstractInterface {
	
	private $export_file_extension = 'csv';
	private $export_headlines = '1';
	private $export_delimiter = ',';
	private $export_linebreak = 'unix';
	private $export_enclosure = 'double_quotes';
	private $export_charset = 'US-ASCII';
	private $export_filename = '';	

	private $columns = 
	[
		[
			'column' => 'address_lastname',
			'content' => '',
			'headline' => '*ContactName'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'EmailAddress'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'POAddressLine1'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'POAddressLine2'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'POAddressLine3'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'POAddressLine4'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'POCity'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'PORegion'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'POPostalCode'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'POCountry'
		],
		[
			'column' => 'document_number',
			'content' => '',
			'headline' => '*InvoiceNumber'
		],
		[
			'column' => 'service_from',
			'content' => '',
			'headline' => 'Reference'
		],
		[
			'column' => 'document_date',
			'content' => '',
			'headline' => '*InvoiceDate'
		],
		[
			'column' => 'due_date',
			'content' => '',
			'headline' => '*DueDate'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'Total'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'InventoryItemCode'
		],
		[
			'column' => 'stack_description',
			'content' => '',
			'headline' => '*Description'
		],
		[
			'column' => 'static',
			'content' => '1',
			'headline' => '*Quantity'
		],
		[
			'column' => 'amount',
			'content' => '',
			'headline' => '*UnitAmount'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'Discount'
		],
		[
			'column' => 'account_number_income',
			'content' => '',
			'headline' => '*AccountCode'
		],
		[
			'column' => 'tax_key',
			'content' => '',
			'headline' => '*TaxType'
		],
		[
			'column' => 'tax',
			'content' => '',
			'headline' => 'TaxAmount'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'TrackingName1'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'TrackingOption1'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'TrackingName2'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'TrackingOption2'
		],
		[
			'column' => 'currency_iso_original',
			'content' => '',
			'headline' => 'Currency'
		],
		[
			'column' => 'empty',
			'content' => '',
			'headline' => 'BrandingTheme'
		]
	];
	
	/**
	 * 
	 * @param string $name
	 * @return string|array|null
	 */
	public function get($name, $default) {
		
		if($name === 'columns_export') {
			return array_column($this->columns, 'column');
		} elseif($name === 'columns_export_full') {
			return $this->columns;
		} elseif(property_exists($this, $name)) {
			return $this->$name;
		}
		
	}

}
