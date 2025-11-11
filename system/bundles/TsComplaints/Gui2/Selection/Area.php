<?php
namespace TsComplaints\Gui2\Selection;

use \Ext_Gui2_View_Selection_Abstract;
use TcComplaints\Entity\Category as CategoryRepository;
use \Ext_Thebing_Util;

class Area extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @var string
	 */
	private $sType;

	public function __construct($sType) {
		$this->sType = $sType;
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array|\TcComplaints\Entity\SubCategory[]
	 * @throws \Exception
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = array();

		$iInquiryId = (int)$oWDBasic->inquiry_id;

		$oInquiry = \Ext_TS_Inquiry::getInstance($iInquiryId);

			switch($this->sType) {

				case 'generally':
				case 'activity':
					// Do Nothing!
					break;
				case 'teacher':
					$aTeachers = $oInquiry->getTuitionTeachers();
					foreach($aTeachers as $aTeacher) {
						$aOptions[$aTeacher['teacher_id']] = $aTeacher['teacher_name'];
					}
					break;
				case 'accommodation':
					$aAccomodationProviders = $oInquiry->getAccommodationProvider();
					foreach($aAccomodationProviders as $oAccomodationProvider) {
						$aOptions[$oAccomodationProvider->id] = $oAccomodationProvider->getName();
					}
					break;
				case 'transfer':
					$aJourneys = $oInquiry->getJourneys();
					foreach($aJourneys as $oJourney) {
						$aJourneyTransfers = $oJourney->getUsedTransfers();

						$aJourneyTransferIds = array();
						foreach($aJourneyTransfers as $oJourneyTransfer) {
							$aJourneyTransferIds[] = $oJourneyTransfer->id;
						}

						$aTransferProviders = \Ext_TS_Inquiry_Journey_Transfer::getProvider($aJourneyTransferIds);
						$aOptions = array();
						foreach($aTransferProviders as $iKey => $oTransferProvider) {
							$aOptions[$iKey] = $oTransferProvider->getName();
						}

					}
					break;
			}

		$aOptions = Ext_Thebing_Util::addEmptyItem($aOptions);
		asort($aOptions);

		return $aOptions;
	}

}