<?php


class Ext_Thebing_System_Checks_Enquiry_Filterset extends GlobalChecks
{
	
	public function getDescription() 
	{
		return 'Predefined filters are inserted for enquiries';
	}
	
	public function getTitle()
	{
		return 'Standard filter elements'; 
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('ts_enquiries');
		
		$aLangs			= $this->_getTranslationLanguages();
		$aUsergroups	= $this->_getUserGroups();
		
		$oFilterset = new Ext_TC_Gui2_Filterset();

		$bExists = $this->_checkFilterset('ts_enquiry', $oFilterset);
		if($bExists === true) {
			return true;
		}
		
		// Filterzeile erstellen
		$oFilterset->name = 'Default';
		$oFilterset->application = 'ts_enquiry';
		$oBar = $oFilterset->getJoinedObjectChild('bars');
		$oBar->name = 'all Users';
		$oBar->usergroups = $aUsergroups;
		
		// Input
		$oElement	= $oBar->getJoinedObjectChild('elements');
		foreach($aLangs as $aLang){
			$oElement->setI18NName(Ext_TC_L10N::t('Suche', $aLang['iso']), $aLang['iso'], 'label');
		}
		$oElement->type = 'input';
		$oElement->basedon = array('customer', 'customerNumber');
		
		// Datum
		$oElement	= $oBar->getJoinedObjectChild('elements');
		foreach($aLangs as $aLang){
			$oElement->setI18NName(Ext_TC_L10N::t('Zeitraum', $aLang['iso']), $aLang['iso'], 'label');
		}
		$oElement->type = 'date';
		$oElement->basedon = array('created_original');
		$oElement->timefilter_from_count = '2';
		$oElement->timefilter_from_type = 'month';
		$oElement->timefilter_until_count = '1';
		$oElement->timefilter_until_type = 'month';
		
		// Statusselect
		$this->_createSelectFilter('Status', 'status_filter', $oBar, $aLangs);
		
		// Agenturselect
		$this->_createSelectFilter('Agentur', 'agency_id', $oBar, $aLangs);
		
		// Landselect
		$this->_createSelectFilter('Land', 'customer_country_original', $oBar, $aLangs);
		
		$oFilterset->save();
		
		return true;
	}
	
	/**
	 * Prüft ob es bereits ein Filterset für einen Bereich gibt
	 * @param string $sKey
	 * @param Ext_TS_Gui2_Filterset $oFilterset
	 * @return boolean
	 */
	protected function _checkFilterset($sKey, $oFilterset) {
		
		$aList = $oFilterset->getObjectList();
		// Wenn schon einer da ist
		foreach($aList as $oCurrentFilterset){
			if($oCurrentFilterset->application == $sKey){
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Holt die IDs aller verfügbaren Benutzergruppen
	 * @return array
	 */
	protected function _getUserGroups() {
		$oAccess		= new Ext_Thebing_Access();
		$aUsergroups	= $oAccess->getAccessGroups();
		$aUsergroups	= array_keys($aUsergroups);
		
		return $aUsergroups;
	}	
	
	/**
	 * Liefert die Sprachen, für die eine Übersetzung eingetraghen werden muss
	 * @return array
	 */
	protected function _getTranslationLanguages() {
		$aLangs	= Ext_Thebing_Util::getTranslationLanguages();
		return $aLangs;
	}
	
	/**
	 * Generiert ein Element vom Type 'select'
	 * @param string $sTitle
	 * @param mixed $mBasedOn
	 * @param Ext_TC_Gui2_Filterset_Bar $oBar
	 * @param array $aLangs
	 */
	protected function _createSelectFilter($sTitle, $mBasedOn, &$oBar, $aLangs) {
		
		if(is_array($mBasedOn)) {
			$aBasedOn = $mBasedOn;
		} else {
			$aBasedOn = array($mBasedOn);
		}

		$oElement = new Ext_TC_Gui2_Filterset_Bar_Element();
		foreach($aLangs as $aLang){
			$oElement->setI18NName(Ext_TC_L10N::t($sTitle, $aLang['iso']), $aLang['iso'], 'label');
		}

		$oElement->type = 'select';
		$oElement->display_label = 0;
		$oElement->basedon = $aBasedOn;

		$oBar->setChildObject($oElement);
	}	
	
	
}