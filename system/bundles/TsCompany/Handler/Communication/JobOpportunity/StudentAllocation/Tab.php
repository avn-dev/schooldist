<?php

namespace TsCompany\Handler\Communication\JobOpportunity\StudentAllocation;

/**
 * @deprecated
 */
class Tab extends \Ext_TC_Communication_Tab {

	/**
	 * Definieren, welche Tabs in welcher Tabarea zur Verfügung stehen.
	 */
	public function getInnerTabs() {

		$innerTabs = [
			$this->createInnerTab('Kunde/Agentur', 'customer'),
			$this->createInnerTab('Firma', 'company'),
		];
		
		return $innerTabs;

	}
	
}
