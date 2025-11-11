<?php


class Ext_Thebing_Gui2_Selection_School_Referer extends Ext_Gui2_View_Selection_Abstract
{
	/**
	 * Get the options
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		$oSchool	= $oWDBasic->getSchool();
		
		$aReferer 	= $oSchool->getRefererList();
		$aReferer	= Ext_Thebing_Util::addEmptyItem($aReferer);

		return $aReferer;
	}

}