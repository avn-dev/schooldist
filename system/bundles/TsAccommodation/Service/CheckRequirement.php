<?php

namespace TsAccommodation\Service;

use TsAccommodation\Entity\Requirement\Document;
use \TsAccommodation\Entity\Requirement;
use \TsAccommodation\Entity\Member;

class CheckRequirement {

	private $oRequirement;

	private $oAccommodation;

	private $bHasMissingDocument;

	private $bHasExpiredDocument;

	private $aDiffMissingDocuments;

	private $aIntersectExpiredDocuments;

	/**
	 * @param \Ext_Thebing_Accommodation $oAccommodation
	 * @param \TsAccommodation\Entity\Requirement $oRequirement
	 */
	public function __construct(\Ext_Thebing_Accommodation $oAccommodation, Requirement $oRequirement) {
		$this->oAccommodation = $oAccommodation;
		$this->oRequirement = $oRequirement;
	}

	/**
	 * Holt die Dokumente des übergebenen Unterkunftsanbieter und der übergebenen Voraussetzung
	 *
	 * @return Document[]
	 */
	private function getDocuments() {

		$aDocuments = $this->oAccommodation->getJoinedObjectChilds('requirement_documents');

		if(!empty($aDocuments)) {
			$aDocuments = array_filter($aDocuments, function($oDocument) {
				return $oDocument->requirement_id == $this->oRequirement->id;
			});
		}

		return $aDocuments;
	}

	/**
	 * Überprüft die Dokumente der Unterkunftsanbieter bzw. ihrer Mitgliedern, ob die fehlen oder abgelaufen sind
	 *
	 * @param \DateTime|null $dEnd
	 * @throws \Exception
	 */
	public function check(\DateTime $dEnd = null) {

		$aDocuments = $this->getDocuments();

		if($this->oRequirement->requirement !== 'member') {

			if(!empty($aDocuments)) {

				$this->bHasMissingDocument = false;
				$this->bHasExpiredDocument = true;

				foreach($aDocuments as $oDocument) {

					if($oDocument->isValid($dEnd) === true) {

						$this->bHasExpiredDocument = false;
					}

				}

			} else {

				$this->bHasExpiredDocument = false;
				$this->bHasMissingDocument = true;
			}

		} else {

			$aMembersWithExpiredDocuments = [];
			$aMembersWithValidDocuments = [];

			$aMembersByAge = Member::getRepository()->findByAge($this->oAccommodation, $this->oRequirement->age);

			//überprüfen ob eine Familie Mitglieder hat, um von denen Dokumente anzufordern
			if(empty($aMembersByAge)) {

				$this->bHasMissingDocument = false;
				$this->bHasExpiredDocument = false;

			} else {

				$aMembersByAgeIds = [];

				foreach ($aMembersByAge as $oMembersByAge) {

					$aMembersByAgeIds[$oMembersByAge->id] = $oMembersByAge;
				}

				if(!empty($aDocuments)) {

					$this->bHasMissingDocument = false;
					$this->bHasExpiredDocument = false;

					foreach($aDocuments as $oDocument) {

						if($oDocument->isValid($dEnd) === true) {

							foreach($oDocument->members as $iMemberId) {
								$aMembersWithValidDocuments[$iMemberId] = $iMemberId;
								unset($aMembersWithExpiredDocuments[$iMemberId]);
							}

						} else {

							foreach($oDocument->members as $iMemberId) {
								if(empty($aMembersWithValidDocuments[$iMemberId])) {
									$aMembersWithExpiredDocuments[$iMemberId] = $iMemberId;
								}
							}

						}
					}

					$aDiffMissingDocuments = array_diff_key($aMembersByAgeIds, $aMembersWithValidDocuments, $aMembersWithExpiredDocuments);

					if(!empty($aDiffMissingDocuments)) {
						$this->bHasMissingDocument = true;
					}

					$aIntersectExpiredDocuments = array_intersect_key($aMembersByAgeIds, $aMembersWithExpiredDocuments);

					if(!empty($aIntersectExpiredDocuments)) {
						$this->bHasExpiredDocument = true;
					}

					$this->aDiffMissingDocuments = $aDiffMissingDocuments;
					$this->aIntersectExpiredDocuments = $aIntersectExpiredDocuments;

				} else {

					$this->bHasExpiredDocument = false;
					$this->bHasMissingDocument = true;

				}
			}
		}
	}

	/**
	 * @return array $this->aDiffMissingDocuments
	 */
	public function getMembersWithMissingDocuments() {
		return $this->aDiffMissingDocuments;
	}

	/**
	 * @return array $this->aIntersectExpiredDocuments
	 */
	public function getMembersWithExpiredDocuments() {
		return $this->aIntersectExpiredDocuments;
	}

	/**
	 * @return bool $this->bHasMissingDocument
	 */

	public function hasMissingDocument() {
		return $this->bHasMissingDocument;
	}

	/**
	 * @return bool $this->hasExpiredDocument
	 */
	public function hasExpiredDocument() {
		return $this->bHasExpiredDocument;
	}

}
