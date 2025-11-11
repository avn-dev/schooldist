<?php

namespace Form\Entity;

class OptionRepository extends \WDBasic_Repository {
	
	public function getFields(\Form\Entity\Init $oForm, \Form\Entity\Page $oPage) {
		
		$sQuery = "
			SELECT
				*
			FROM
				`form_options`
			WHERE
				`form_id` = :form_id AND
				`page_id` = :page_id AND
				`active` = 1 AND
				`display` = 1
			ORDER BY
				`position`
		";
		$aSql = [
			'form_id' => (int)$oForm->id,
			'page_id' => (int)$oPage->id
		];
		$aOptions = \DB::getQueryRows($sQuery, $aSql);

		$aFields = [];
		foreach ($aOptions as $aOption) {
			$aFields[$aOption['id']] = $this->_getEntity($aOption);
		}

		return $aFields;
	}
	
}
