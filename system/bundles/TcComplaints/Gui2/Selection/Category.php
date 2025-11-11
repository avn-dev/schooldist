<?php

namespace TcComplaints\Gui2\Selection;

use \Ext_Gui2_View_Selection_Abstract;
use TcComplaints\Entity\Category as TcComplaints_Entity_Category;
use TcComplaints\Entity\CategoryRepository;

class Category extends Ext_Gui2_View_Selection_Abstract {

    /**
     * @var string
     */
    private $sType;

    /**
     * @param string $sType
     */
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

        /** @var CategoryRepository $oCategoryRepository */
        $oCategoryRepository = TcComplaints_Entity_Category::getRepository();
        $aCategories = $oCategoryRepository->getAllCategoriesPerType($this->sType);

        if(!empty($aCategories)) {
            /** @var TcComplaints_Entity_Category $oCategory */
            foreach($aCategories as $oCategory) {
                $aOptions[$oCategory->getId()] = $oCategory->title;
            }
        }

        $aOptions = \Ext_TC_Util::addEmptyItem($aOptions);

        return $aOptions;
    }

}