<?php

namespace TcComplaints\Entity;

use TcComplaints\Entity\Category as TcComplaints_Entity_Category;

class SubCategoryRepository extends \WDBasic_Repository {

	/**
	 * Holt alle Unterkategorien anhand einer Kategorie Id
	 *
	 * @param TcComplaints_Entity_Category $oCategory
	 * @return array
	 */
	public function getAllSubCategoriesPerCategoryId(TcComplaints_Entity_Category $oCategory) {

		$aDataObjects = $this->findBy(array(
			'category_id' => $oCategory->getId()
		));

		return $aDataObjects;

	}

}