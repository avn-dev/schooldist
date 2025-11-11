<?php

namespace TsAccounting\Handler\Communication\Invoice;

/**
 * @deprecated
 */
class Tab extends \Ext_TC_Communication_Tab {



	/**
	 * Definieren, welche Tabs in welcher Tabarea zur Verfügung stehen.
	 */
	public function getInnerTabs() {

		$innerTabs = [
			$this->createInnerTab('Kunde', 'customer')
		];

		if ($this->_sType !== 'email') {
			return $innerTabs;
		}

		/* @var \Ext_Thebing_Inquiry_Document[] $documents */
		$documents = $this->_oCommunication->getSelectedObjects();

		$hasAgency = $hasSponsor = $hasGroup = false;

		foreach ($documents as $document) {

			$inquiry = $document->getInquiry();

			if (!$hasAgency && $inquiry->hasAgency()) {
				$hasAgency = true;
			}

			if (!$hasSponsor && $inquiry->isSponsored()) {
				$hasSponsor = true;
			}

			if (!$hasGroup && $inquiry->hasGroup()) {
				$hasGroup = true;
			}
		}

		if ($hasAgency) {
			$innerTabs[] = $this->createInnerTab('Agentur', 'agency');
		}

		if ($hasSponsor) {
			$innerTabs[] = $this->createInnerTab('Sponsor', 'sponsor');
		}

		if ($hasGroup) {
			$innerTabs[] = $this->createInnerTab('Gruppe', 'group');
		}

		return $innerTabs;

	}
	
}
