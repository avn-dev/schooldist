<?php

interface Ext_TS_Service_Interface_Accommodation {

	public function getInfo($iSchoolId = false, $sDisplayLanguage = false, $bShort = false);
	
	public function getCategory();
	
	public function getRoomType();
	
	public function getMeal();
	
	public function getAdditionalCosts();
	
	public function getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iAccommodationCount, Tc\Service\LanguageAbstract $oLanguage);

	public function getExtraNightInfo($iExtraNightsCurrent, Tc\Service\LanguageAbstract $oLanguage, $sPeriod = '');

	public function getExtraWeekInfo($iExtraWeeks, Tc\Service\LanguageAbstract $oLanguage, $sPeriod = '');

	public function setExtranightHelper(Ext_TS_Service_Accommodation_Helper_Extranights $oHelper = null);

}