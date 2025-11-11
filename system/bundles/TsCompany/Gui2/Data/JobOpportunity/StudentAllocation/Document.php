<?php

namespace TsCompany\Gui2\Data\JobOpportunity\StudentAllocation;

use Illuminate\Support\Arr;
use TsCompany\Entity\JobOpportunity\StudentAllocation;

class Document extends \Ext_TS_Inquiry_Document_Gui2_Data {

	/**
	 * Wichtig für Ext_Thebing_Document. Methode muss die Entität zurückliefern
	 *
	 * @param int|null $iObjectId
	 * @return \Ts\Interfaces\Entity\DocumentRelation
	 */
	public function getSelectedObject(int $iObjectId = null): \Ts\Interfaces\Entity\DocumentRelation {

		if ($iObjectId !== null) {
			return \Ext_TS_Inquiry::getInstance($iObjectId);
		}

		$parentGuiId = Arr::first($this->request->input('parent_gui_id'));
		/** @var StudentAllocation $oJourney */
		$studentAllocation = $this->_getParentGui()->getWDBasic($parentGuiId);

		$inquiry = $studentAllocation->getInquiry();

		return $inquiry;
	}

	public function getDialogHTML(&$iconAction, &$dialog, $selectedIds = array(), $additional = false) {

		if (in_array($iconAction, ['new_additional_document', 'edit_additional_document'])) {

			$parentGuiIds = $this->getParentGuiIds();

			/** @var StudentAllocation $oJourney */
			$studentAllocation = $this->_getParentGui()->getWDBasic(reset($parentGuiIds));
			$inquiry = $studentAllocation->getInquiry();


			$documentHelper = new \Ext_Thebing_Document();
			$documentHelper->setInquiry($inquiry);
			$this->_oGui->setOption('document_class', $documentHelper);

			$dialog = $documentHelper->getEditDialog($this->_oGui, reset($selectedIds), 'job_opportunity', $selectedIds);

		}

		$data = parent::getDialogHTML($iconAction, $dialog, $selectedIds, $additional);

		return $data;

	}

}
