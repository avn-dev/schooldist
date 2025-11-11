<?php

namespace TcComplaints\Entity;

use TcComplaints\Entity\Complaint as TcComplaint_Entity_Complaint;

class CategoryRepository extends \WDBasic_Repository {

	/**
	 * Holt alle Kategorien anhand einer Kategorie Id der Beschwerde
	 *
	 * @param TcComplaint_Entity_Complaint $oComplaint
	 * @return array
	 */
	public function getAllCategoriesPerCategoryId(TcComplaint_Entity_Complaint $oComplaint) {

		$aDataObjects = $this->findBy(array(
			'category_id' => $oComplaint->category_id
		));

		return $aDataObjects;

	}

	/**
	 * Gibt alle Kategorien anhand des Bereiches (type) wieder
	 *
	 * @param string $sType
	 * @return array
	 */
	public function getAllCategoriesPerType($sType) {

		$aDataObjects = $this->findBy(array(
			'type' => $sType
		));

		return $aDataObjects;

	}

	/**
	 * Holt alle Kategorien und gibt alle Objekte in einem Array zurück
	 *
	 * @param array $aCriteria
	 * @return array
	 */
//	public function findBy(array $aCriteria) {
//
//		$oDateTime = new \DateTime();
//		$sDate = $oDateTime->format('Y-m-d');
//
//		$sSql = "
//		SELECT
//			*
//		FROM
//			`tc_complaints_categories`
//		WHERE
//			`id` = :category AND
//			`active` = 1 AND
//			( `valid_until` = '0000-00-00' OR `valid_until` >= :today)
//	";
//
//		$aSql = array(
//			'category' => $aCriteria['category_id'],
//			'today' => $sDate
//		);
//
//		$aResults = \DB::getQueryRows($sSql, $aSql);
//
//		$aEntities = array();
//		if(is_array($aResults)) {
//			$aEntities = $this->_getEntities($aResults);
//		}
//
//		return $aResults;
//	}

}