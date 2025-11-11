<?php

namespace Ts\Handler\Communication\Booking;

/**
 * @deprecated
 */
class Tab extends \Ext_TC_Communication_Tab {

	/**
	 * Definieren, welche Tabs in welcher Tabarea zur Verfügung stehen.
	 */
	public function getInnerTabs() {

		$aInnerTabs = array(
			$this->createInnerTab('Kunde', 'customer')
		);
		
		return $aInnerTabs;

	}
	
}
