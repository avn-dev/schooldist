<?php

class Ext_Thebing_Communication_Message extends Ext_TC_Communication_Message {
	
	protected $_aMainData			= array();
	protected $_aRelationData		= array();
	
	public function setMainData($aMainData){
		$this->_aMainData = $aMainData;
	}

	public function setRelationData($aRelationData){
		$this->_aRelationData = $aRelationData;
	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		parent::manipulateSqlParts($aSqlParts, $sView);
		
		$aMainData		= (array)$this->_aMainData;
		$aRelationData	= (array)$this->_aRelationData;

		$aRelationParts = [];		
		
		$iCounter = 1;
		
		foreach($aMainData as $sObject => $aIds) {
			$aRelationParts[] = "
			(
				`relations`.`relation` = :main_object_".$iCounter." AND
				`relations`.`relation_id` IN(:main_ids_".$iCounter.")
			)";

			$iCounter++;
		}

		$iCounter = 1;

		foreach($aRelationData as $sObject => $aIds) {
			$aRelationParts[] = "
				(
					`relations`.`relation` = :relation_object_".$iCounter." AND
					`relations`.`relation_id` IN(:relation_ids_".$iCounter.")
				)";

			$iCounter++;
		}

		if(!empty($aRelationParts)) {
			$aSqlParts['where'] .= ' AND ('.implode(' OR ', $aRelationParts).') ';
		}

		// Da es mehr als eine Relation geben kann, muss hier auch ein GROUP BY rein
		$aSqlParts['groupby'] = "
			`tc_cm`.`id`
		";

	}/**
	 * Formatiert die »Adressen« für Notizen
	 *
	 * @param $sType
	 * @param bool $bLong
	 * @param bool $bShowType
	 * @return string
	 */
	public function getFormattedContactsNotices($sType, $bLong=true, $bShowType=false) {

		$sReturn = '';

		// Alles außer From und To ignorieren
		if($sType !== 'from' && $sType !== 'to') {
			return $sReturn;
		}

		// Kunde/Schule
		if(
			$this->direction === 'in' && $sType === 'from' ||
			$this->direction === 'out' && $sType === 'to'
		) {

			if($bShowType) {
				if(mb_strpos($this->notice_correspondant_key, 'agency') !== false) {
					$sReturn .= Ext_TC_Communication::t('Agentur').': ';
				} else {
					$sReturn .= Ext_TC_Communication::t('Schüler').': ';
				}
			}

			// Gespeicherter Kontakt hat eine ID
			if(in_array($this->notice_correspondant_key, Factory::executeStatic('Ext_TC_Communication_Message_Notice_Gui2_Data', 'getEncodedCorrespondantFields'))) {

				$oContact = Ext_TC_Contact::getInstance($this->notice_correspondant_value);
				$sReturn .= $oContact->getName();

			} else {

				$sReturn .= $this->notice_correspondant_value;

			}

		} else { // Agentur

			if($bShowType) {
				$sReturn .= Ext_TC_Communication::t('Benutzer').': ';
			}

			$oUser = Ext_Thebing_User::getInstance($this->creator_id);
			$sReturn .= $oUser->getName();

		}

		return $sReturn;
	}
	
}
