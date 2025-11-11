<?php

namespace Notices\Entity;

class NoticeRepository extends \WDBasic_Repository {

	public function getByEntity($oEntity=null) {

		if($oEntity) {
			$aSql = [
				'entity' => get_class($oEntity),
				'entity_id' => $oEntity->id
			];
			$sWhere = " AND
				`n`.`entity` = :entity AND
				`n`.`entity_id` = :entity_id";
		}
				
		$sSql = "
			SELECT
				`n`.*
			FROM	
				`notices` `n`
			WHERE
				`n`.`active` = 1 ".$sWhere."
			ORDER BY 
				`created` DESC
				";
		
		$aNotices = \DB::getQueryRows($sSql, $aSql);

		if(is_array($aNotices)) {
			$aEntities = $this->_getEntities($aNotices);
			return $aEntities;
		}

		return [];
	}
	
}