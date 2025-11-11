<?php

/**
 * Class Ext_Thebing_Email_TemplateRepository
 */
class Ext_Thebing_Email_TemplateRepository extends WDBasic_Repository {

	/**
	 * Gibt alle Templates für ein Selektfeld zurück
	 *
	 * @return array
	 */
	public function getAllForSelect() {
		/** @var Ext_Thebing_Email_Template[] $aTemplates */
		$aTemplates = $this->findAll();
		$aResult = [];
		foreach($aTemplates as $oTemplate) {
			$aResult[$oTemplate->getId()] = $oTemplate->getName();
		}
		return $aResult;
	}

}