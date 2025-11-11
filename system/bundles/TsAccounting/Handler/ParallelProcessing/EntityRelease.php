<?php

namespace TsAccounting\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use TsAccounting\Service\AutomationService;

class EntityRelease extends TypeHandler {

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Automatische Freigabe', 'School');
	}

    /**
	 *  
     * @param  array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $data, $debug = false) {

		$creatorId = isset($data['creator_id']) ? (int)$data['creator_id'] : AutomationService::SYSTEM_CREATOR_ID;
		$type = $data['type'];

		switch ($type) {
			case 'document':
				$entity = $this->releaseDocument($data, $creatorId);
				break;
			case 'payment':
				$entity = $this->releasePayment($data, $creatorId);
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Unknown release type "%s"', $type));
		}

		if($entity->hasError()) {
			throw new \RuntimeException($entity->getError());
		} else if($entity->hasHint()) {
			throw new \RuntimeException($entity->getHint());
		}

		\Ext_Gui2_Index_Stack::executeCache();

		return true;
	}

	private function releaseDocument(array $data, $creatorId) {

		$document = \Ext_Thebing_Inquiry_Document::getInstance($data['entity_id']);

		if(!$document->isReleased()) {
			$documentsForReleaseIds = isset($data['other_ids']) ? (array)$data['other_ids'] : [];

			$document->releaseDocument([], $documentsForReleaseIds, $creatorId);
		}

		return $document;
	}

	private function releasePayment(array $data, $creatorId) {

		$payment = \Ext_Thebing_Inquiry_Payment::getInstance($data['entity_id']);

		if(!$payment->isReleased()) {
			$payment->releasePayment($creatorId);
		}

		return $payment;
	}
}
