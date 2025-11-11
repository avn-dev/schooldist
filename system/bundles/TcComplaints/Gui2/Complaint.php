<?php

namespace TcComplaints\Gui2;

use TcComplaints\Entity\Category as TcComplaints_Entity_Category;
use TcComplaints\Entity\SubCategory as TcComplaints_Entity_SubCategory;

class Complaint extends \Ext_TC_Gui2 {

	public function getBarList($bPositionTop = true) {

		$aSubCategoriesForSelect = array();
		$iCategory = null;
		$sFromDate = '';
		$sUntilDate = '';

		$aBars = parent::getBarList($bPositionTop);

		if($bPositionTop === true) {

			$aFilter = $this->getDataObject()->getFilter();

			if(!empty($aFilter)) {

				/** @var \Ext_Gui2_Bar  $oBar */
				foreach($aBars as $oBar) {

					foreach($oBar->getElements() as $oBarElement) {

						if($oBarElement->element_type == 'filter') {

							if($aFilter['search_time_from_1']) {
								$sFromDate = $aFilter['search_time_from_1'];
							}

							if($aFilter['search_time_until_1']) {
								$sUntilDate = $aFilter['search_time_until_1'];
							}

							if(
								$oBarElement->id == 'filter_subcategory' &&
								(
									!empty($aFilter['filter_category']) ||
									$aFilter['filter_category'] !== 0
								)

							) {

								$oBarElement->visibility = true;

								$oCategory = TcComplaints_Entity_Category::getInstance($aFilter['filter_category']);
								$oSubCategoryRepository = TcComplaints_Entity_SubCategory::getRepository();
								$aSubCategories = $oSubCategoryRepository->getAllSubCategoriesPerCategoryId($oCategory);

								/** @var \TcComplaints\Entity\SubCategory $oSubCategory */
								foreach($aSubCategories as $oSubCategory) {
									$aSubCategoriesForSelect[$oSubCategory->getId()] = $oSubCategory->title;
								}

								$sLabel = '-- ' . $this->t('Unterkategorie') . ' --';
								$aList = \Util::addEmptyItem($aSubCategoriesForSelect, $sLabel);

								$oBarElement->select_options = $aList;

							}
						}
					}
				}
			}

		}

		return $aBars;
	}

}