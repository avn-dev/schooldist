<?php

/**
 * @property Ext_Thebing_Email_Log $oWDBasic
 */
class Ext_Thebing_Communication_Gui2_Data extends Ext_TC_Communication_Gui2_Data {

	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit=false) {
		/**
		 * buildQueryParts überprüft, ob schon $this->oWdBasic definiert wurde,
		 * so können wir bevor der Query aufgebaut wird in die WdBasic variablen definieren
		 */
		$this->_getWDBasicObject(array(0));

		// TODO Kann man das nicht auf _buildQueryParts umstellen und die ganzen Abhängigkeiten in der WDBasic entfernen?
		//die übergebenen Daten in die WdBasic weiterleiten zum QueryAufbau 
		$this->oWDBasic->setMainData($this->_oGui->getMainData());
		$this->oWDBasic->setRelationData($this->_oGui->getRelationData());
		#$this->oWDBasic->setApplication($this->_oGui->getApplication());

		$aResult = parent::getTableQueryData($aFilter, $aOrderBy, $aSelectedIds, $bSkipLimit);

		return $aResult;
	}
	
	/**
	 * Platzhalter vorbereiten
	 * @param <array> $aSql
	 */
	protected function _prepareTableQueryData(&$aSql, &$sSql) {

		$aMainData		= (array)$this->_oGui->getMainData();
		$aRelationData	= (array)$this->_oGui->getRelationData();

		$iCounter = 1;
		foreach($aMainData as $sObject => $aIds) {
			$aSql['main_object_'.$iCounter] = $sObject;
			$aSql['main_ids_'.$iCounter]	= $aIds;
			$iCounter++;
		}

		$iCounter = 1;
		foreach($aRelationData as $sObject => $aIds) {
			$aSql['relation_object_'.$iCounter] = $sObject;
			$aSql['relation_ids_'.$iCounter]	= $aIds;
			$iCounter++;
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function getAllocationHandler(): array {
		return [
			'new_enquiry' => [
				'label' => \Ext_TC_Communication::t('In Anfrage umwandeln'),
				'class' => \Ts\Handler\Communication\Allocation\CreateEnquiry::class
			],
			'existing_enquiry' => [
				'label' => \Ext_TC_Communication::t('Zu bestehender Anfrage zuweisen'),
				'class' => \Ts\Handler\Communication\Allocation\ExistingEnquiry::class
			],
			'existing_inquiry' => [
				'label' => \Ext_TC_Communication::t('Zu bestehender Buchung zuweisen'),
				'class' => \Ts\Handler\Communication\Allocation\ExistingInquiry::class
			],
		];
	}

}
