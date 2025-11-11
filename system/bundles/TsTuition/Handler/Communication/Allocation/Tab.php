<?php

namespace TsTuition\Handler\Communication\Allocation;

/**
 * @deprecated
 */
class Tab extends \Ext_TC_Communication_Tab {

	/**
	 * Definieren, welche Tabs in welcher Tabarea zur Verfügung stehen.
	 */
	public function getInnerTabs() {

		$aInnerTabs = [
			$this->createInnerTab('Kunde', 'customer'),
			$this->createInnerTab('Lehrer', 'teacher'),
		];

		return $aInnerTabs;

	}
	
}
