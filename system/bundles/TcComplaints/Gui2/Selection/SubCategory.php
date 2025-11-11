<?php

namespace TcComplaints\Gui2\Selection;

use \Ext_Gui2_View_Selection_Abstract;
use \TcComplaints\Entity\Category as TcComplaints_Entity_Category;

class SubCategory extends Ext_Gui2_View_Selection_Abstract {

    /**
     * @param array $aSelectedIds
     * @param array $aSaveField
     * @param \WDBasic $oWDBasic
     * @return array|\TcComplaints\Entity\SubCategory[]
     * @throws \Exception
     */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

        $aOptions = array();
        $iCategoryId = (int)$oWDBasic->category_id;

        if($iCategoryId > 0) {

            $oCategory = TcComplaints_Entity_Category::getInstance($iCategoryId);

            $aSubCategories	= $oCategory->getSubCategories();
            foreach($aSubCategories as $oSubCategory) {
                $aOptions[$oSubCategory->getId()] = $oSubCategory->title;
            }

            $aOptions = \Ext_TC_Util::addEmptyItem($aOptions);

        }

        return $aOptions;
    }

}