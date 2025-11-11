<?php

namespace FileManager\Entity;

class FileRepository extends \WDBasic_Repository {

	/**
	 * @param $oEntity
	 * @param null $sTag
	 * @return File[]
	 */
	public function getByEntityAndTag($oEntity, $sTag=null) {

		$aSql = [
			'entity' => get_class($oEntity),
			'entity_id' => $oEntity->id
		];
		
		$sJoin = '';
		$sWhere = '';
		if($sTag !== null) {
			$sJoin = " LEFT JOIN
				`filemanager_files_tags` `fft` ON
					`ff`.`id` = `fft`.`file_id` LEFT JOIN
				`filemanager_tags` `ft` ON
					`fft`.`tag_id` = `ft`.`id`";
			$sWhere = " AND
				`ft`.`tag` = :tag ";
			$aSql['tag'] = $sTag;
		}
		
		$sSql = "
			SELECT
				`ff`.*
			FROM	
				`filemanager_files` `ff` ".$sJoin."
			WHERE
				`ff`.`active` = 1 AND
				`ff`.`entity` = :entity AND
				`ff`.`entity_id` = :entity_id ".$sWhere."
			ORDER BY
				`ff`.`position` ASC,
				`ff`.`id` ASC
				";

		$aFiles = \DB::getQueryRows($sSql, $aSql);

		if(is_array($aFiles)) {
			return $this->_getEntities($aFiles);
		}

		return [];

	}
	
}