<?php

namespace TcComplaints\Entity;

class ComplaintRepository extends \WDBasic_Repository {

	/**
	 * Holt alle Beschwerden zusammen mit den dazugehörigen Kinder-Elementen.
	 * @Todo: Wenn das Beschwerdemodul für die Agentur bereitgestellt wird, hier auch die Klassen der Agentur im @param dazu schreiben!
	 *
	 * @param \Ext_Thebing_Teacher|\Ext_Thebing_Accommodation|\Ext_Thebing_Pickup_Company $oObject
	 * @param string $sArea
	 * @return array
	 */
	public function getAllComplaintsViaAreaAndId($oObject, $sArea) {

		$aDataObjects = $this->findBy(array(
			'type_id' => $oObject->getId()
		));

		if(!empty($aDataObjects)) {
			foreach($aDataObjects as $iKey => $oComplaint) {
				/** @var Category $oCategory */
				$oCategory = $oComplaint->getCategory();
				if($oCategory->type !== $sArea) {
					unset($aDataObjects[$iKey]);
				}
			}
		}

		return $aDataObjects;

	}

	/**
	 * Holt alle Beschwerden mit Hilfe des Objektes "Kategorie"
	 *
	 * @param Category $oCategory
	 * @return array
	 * @throws \Exception
	 */
	public function getAllComplaintsPerCategoryId(Category $oCategory) {

		$aDataObjects = $this->findBy(array(
			'category_id' => $oCategory->getId()
		));

		return $aDataObjects;

	}

	/**
	 * Holt alle Beschwerden mit Hilfe des Objektes "Unterkategorie"
	 *
	 * @param SubCategory $oSubCategory
	 * @return array
	 * @throws \Exception
	 */
	public function getAllComplaintsPerSubCategoryId(SubCategory $oSubCategory) {

		$aDataObjects = $this->findBy(array(
			'sub_category_id' => $oSubCategory->getId()
		));

		return $aDataObjects;
	}

	/**
	 * Prüft ob der Benutzer eine Beschwerde erstellt hat
	 *
	 * @param int $iId
	 * @return bool
	 */
	public function hasUserCreatedComplaints($iId) {

		$aDataObjects = $this->findBy(array(
			'creator_id' => $iId
		));

		if(!empty($aDataObjects)) {
			$bReturn = true;
		} else {
			$bReturn = false;
		}

		return $bReturn;

	}

	/**
	 * Checkt ob es Beschwerden für den Unterkunftsanbieter, Lehrer oder dem Transferanbieter gibt
	 * Die Agentur kann diese Methode ebenfalls für ihre Fülle verwenden.
	 *
	 * @param string $sArea
	 * @param int $iId
	 * @return bool
	 * @throws \Exception
	 */
	public function haveComplaint($iId, $sArea) {

		$aDataObjects = $this->findBy(array(
			'type_id' => $iId
		));

		if(!empty($aDataObjects)) {
			foreach($aDataObjects as $iKey => $oComplaint) {
				/** @var Category $oCategory */
				$oCategory = $oComplaint->getCategory();
				if($oCategory->type !== $sArea) {
					unset($aDataObjects[$iKey]);
				}
			}
		}

		return !empty($aDataObjects);
	}

}