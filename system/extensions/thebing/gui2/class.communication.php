<?php


class Ext_Thebing_Gui2_Communication extends Ext_TC_Communication_Gui2 {

	protected $_aMainData			= array();
	protected $_aRelationData		= array();
	protected $_sApplication;

	/**
	 * Hier mag sich der geneigte Leser fragen, was der Scheiß soll.
	 * Die History-Gui zeigt im Normalfall alle Einträge mit einer Relation zum Elterneintrag an. 
	 * Wenn aber manuell Relationen gesetzt wurden, dann darf das nicht zusätzlich noch passiert und wird daher
	 * hier zurückgesetzt.
	 */
	protected function resetHistoryWhere() {

		$this->foreign_key = null;
		$this->foreign_key_alias = null;
		$this->parent_primary_key = 'id';

		$this->setTableData('where', []);
		
	}


	/*
	 * Setzt das aktuelle Object auf dem die Hauptliste basiert
	 */
	public function setMainObject($mSelectedMainIds, $sObject){

		$aMainIds = (array)($this->_aMainData[$sObject] ?? []);
		$aMainIds = array_merge($aMainIds,(array)$mSelectedMainIds);
		$aMainIds = array_unique($aMainIds);

		$this->_aMainData[$sObject] = $aMainIds;

		// Main ebenfalls auch noch als Relation setzen
		// in manchen listen wird dies benötigt
		$this->setRelationObject($mSelectedMainIds, $sObject);
		
		$this->resetHistoryWhere();
		
	}

	/*
	 * Setzt die Verknüpfungen auf die Relationstabelle die zusätzlich angezeigt werden sollen
	 */
	public function setRelationObject($mSelectedRelationIds, $sObject){

//		if($sObject === 'Ext_TS_Inquiry') {
//			$mSelectedRelationIds = (array)$mSelectedRelationIds;
//			foreach($mSelectedRelationIds as $iObjectId) {
//				$oInquiry = Ext_TS_Inquiry::getInstance($iObjectId);
//				$aEnquiries = $oInquiry->enquiries;
//				if(!empty($aEnquiries)) {
//					$this->setRelationObject($aEnquiries, 'Ext_TS_Enquiry');
//				}
//			}
//		}
		
		$aRelationIds = (array)($this->_aRelationData[$sObject] ?? []);
		$aRelationIds = array_merge($aRelationIds,(array)$mSelectedRelationIds);
		$aRelationIds = array_unique($aRelationIds);

		$this->_aRelationData[$sObject] = $aRelationIds;
		
		$this->resetHistoryWhere();
		
	}

	public function setApplication($sApplication)
	{
		$this->_sApplication = $sApplication;
	}

	public function getMainData()
	{
		return $this->_aMainData;
	}

	public function getRelationData()
	{
		return $this->_aRelationData;
	}

	public function getApplication()
	{
		return $this->_sApplication;
	}
}