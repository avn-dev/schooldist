<?php

class Ext_TS_System_Checks_Filterset_Generate extends GlobalChecks {

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return 'Default Filter Sets';
	}

	/**
	 * @inheritdoc
	 */
	public function getDescription() {
		return 'Add new default filter sets.';
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
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$oUpdate = new Ext_Thebing_Update('test');
		
		$aFiltersets = $oUpdate->getFiltersets();
		
		$aUserGroups = $this->_getUserGroups();
		
		$aLangs = $this->_getTranslationLanguages();
		
		foreach($aFiltersets as $aFilterset)
		{
			if(isset($aFilterset['data']))
			{
				$oFilterset = new Ext_TC_Gui2_Filterset();
				
				$aFiltersetData = $aFilterset['data'];
				
				$sApplication = $aFiltersetData['application'];
				
				$bCheckFilterset = $this->_checkFilterset($sApplication, $oFilterset);
				
				if(!$bCheckFilterset)
				{
					$oFilterset->name = $aFiltersetData['name'];
					$oFilterset->application = $sApplication;
					
					if(isset($aFilterset['bars']))
					{
						$aBars = $aFilterset['bars'];
						
						foreach($aBars as $aBar)
						{
							if(isset($aBar['data']))
							{
								$aBarData = $aBar['data'];
					
								$oBar = $oFilterset->getJoinedObjectChild('bars');

								$oBar->name = $aBarData['name'];
								
								$oBar->usergroups = $aUserGroups;

								if(isset($aBar['elements']))
								{
									$aElements = $aBar['elements'];
									
									foreach($aElements as $aElement)
									{
										if(isset($aElement['data']))
										{
											$aElementData = $aElement['data'];
											
											$oElement = $oBar->getJoinedObjectChild('elements');
											
											$oElement->type = $aElementData['type'];
											
											$oElement->display_label = $aElementData['display_label'];
											
											$oElement->basedon = $aElement['basedon'];
											
											$aLanguageData = $aElement['i18n'];
											
											foreach($aLangs as $aLang)
											{
												if(isset($aLanguageData[$aLang['iso']]))
												{
													$sLabel = $aLanguageData[$aLang['iso']];

													$oElement->setI18NName($sLabel, $aLang['iso'], 'label');
												}
											}
											
											$oElement->timefilter_from_count = $aElement['timefilter_from_count'];
											
											$oElement->timefilter_until_count = $aElement['timefilter_until_count'];
											
											$oElement->timefilter_from_type = $aElement['timefilter_from_type'];
											
											$oElement->timefilter_until_type = $aElement['timefilter_until_type'];
										}
									}
								}
							}
						}
						
						$oFilterset->save();
					}
				}
			}
		}
		
		return true;
	}
}