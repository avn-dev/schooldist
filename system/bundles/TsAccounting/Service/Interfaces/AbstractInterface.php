<?php

namespace TsAccounting\Service\Interfaces;

use TsAccounting\Dto\BookingStack\ExportFileContent;

abstract class AbstractInterface {
	
	/**
	 * 
	 * @var \TsAccounting\Entity\Company
	 */
	protected $company;
	
	public function __construct(\TsAccounting\Entity\Company $company) {
		$this->company = $company;
	}

	public function get($name, $default) {
		return $default;
	}

	public function generateAdditionalFilesForExportFile(\Ext_TS_Accounting_Bookingstack_Export $export, ExportFileContent $exportFileContent): array {
		return [];
	}

	public function generateDocumentItemStackEntries(array &$entries, \Ext_Thebing_Inquiry_Document_Version_Item $item) {		
	}
	
	public function generateDocumentStackEntries(array &$entries) {		
	}
	
}
