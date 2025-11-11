<?php


class Ext_TC_Access_SectionRepository extends \WDBasic_Repository {
	
	
	/**
	 * @return array
	 */
	public function getAccessIdsforSection($sSectionKey) {

		$iSectionId = $this->getSectionId($sSectionKey);
		
		$sSql = "
			SELECT 
				`id`
			FROM 
				`tc_access`
			WHERE
				`section_id` = :section_id
			";
		
		$aSql = array(
			'section_id' => $iSectionId
		);
		
		$aResults = \DB::getQueryCol($sSql, $aSql);

		return $aResults;
	}
	
	public function getAccessId($sSectionKey, $sAccessKey) {
		
		$iSectionId = $this->getSectionId($sSectionKey);
		
		#__out($iSectionId);
		
		$sSql = "
			SELECT 
				`id` 
			FROM 
				`tc_access`
			WHERE
				`key` = :accesskey AND
				`section_id` = :section_id
			";
		
		$aSql = array(
			'accesskey' => $sAccessKey,
			'section_id' => $iSectionId
		);
		
		$iAccessId = \DB::getQueryOne($sSql , $aSql);
		
		return $iAccessId;
	}
	
	public function getSectionId($sSectionKey) {

		$sSql = "
			SELECT 
				`id` 
			FROM 
				`tc_access_sections`
			WHERE
				`key` = :sectionkey
			";
		
		$aSql = array(
			'sectionkey' => $sSectionKey
		);
		
		$iSectionId = \DB::getQueryOne($sSql , $aSql);
		
		return $iSectionId;
	}
	
	
}
