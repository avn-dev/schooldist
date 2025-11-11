<?php

namespace Cms\Gui2\Selection;

class PageSelection extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$sSql = "
			SELECT 
				`id`,
				`title`
			FROM 
				`cms_pages`
			WHERE	
				`element` = 'page' AND
				`site_id` = :site_id AND
				`active` = 1 AND
				(
					`language` = :language OR
					`language` = ''
				)
			ORDER BY
				`title`
			";
		$aSql = [
			'site_id' => (int)$oWDBasic->site_id,
			'language' => $oWDBasic->language_iso
		];
		$aPages = \DB::getQueryPairs($sSql, $aSql);

		$aPages = \Util::addEmptyItem($aPages);

		return $aPages;
	}

}