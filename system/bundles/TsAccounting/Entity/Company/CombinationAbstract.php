<?php

namespace TsAccounting\Entity\Company;

abstract class CombinationAbstract extends \Ext_Thebing_Basic
{

	/**
	 *
	 * @var array
	 */
	protected $_aJoinDataOld = array();

	/**
	 *
	 * @return array
	 */
	public function getCompanyIds()
	{
		$sJoinKeyCompanies = $this->_getCompaniesJoinKey();

		if ($sJoinKeyCompanies) {
			$aCompanyIds = (array)$this->$sJoinKeyCompanies;
		} else {
			$aCompanyIds = array();
		}

		if (empty($aCompanyIds)) {
			// Wenn keine Firmen vorhanden, dann 0 zurück geben, weil die Kombinationsüberprüfung
			// damit umgehen kann & dann erst ab der Schule begonnen wird
			$aCompanyIds = array(0);
		}

		return $aCompanyIds;
	}

	/**
	 *
	 * @return array
	 */
	public function getSchoolIds()
	{
		$sJoinKeySchool = $this->_getSchoolJoinKey();

		return $this->$sJoinKeySchool;
	}

	/**
	 *
	 * @return array
	 */
	public function getInboxIds()
	{
		$sJoinKeyInbox = $this->_getInboxJoinKey();

		return $this->$sJoinKeyInbox;
	}

	/**
	 * Beim validieren muss der joinedtable cache geleert werden, damit richtig
	 * überprüft werden kann (anhand der Datenbank) ob Kombinationen wirklich frei sind
	 */
	public function resetCombinationCache()
	{
		$sCompaniesKey = $this->_getCompaniesJoinKey();
		$sSchoolKey = $this->_getSchoolJoinKey();
		$sInboxKey = $this->_getInboxJoinKey();

		if ($sCompaniesKey) {
			$this->_aJoinDataOld[$sCompaniesKey] = $this->_aJoinData[$sCompaniesKey];
			$this->_aJoinTablesLoaded[$sCompaniesKey] = null;
		}

		$this->_aJoinDataOld[$sSchoolKey] = $this->_aJoinData[$sSchoolKey];
		$this->_aJoinTablesLoaded[$sSchoolKey] = null;

		$this->_aJoinDataOld[$sInboxKey] = $this->_aJoinData[$sInboxKey];
		$this->_aJoinTablesLoaded[$sInboxKey] = null;
	}

	/**
	 * Wieder den Cache reinsetzen, sonst würde das speichern nicht richtig funktionieren
	 */
	public function rollbackCache()
	{
		foreach ($this->_aJoinDataOld as $sJoinKey => $mJoinData) {
			$this->_aJoinTablesLoaded[$sJoinKey] = 1;
			$this->_aJoinData[$sJoinKey] = $mJoinData;
		}
	}

	/**
	 *
	 * @param bool $bThrowExceptions
	 * @return boolean
	 */
	public function validate($bThrowExceptions = false)
	{
		$mReturn = parent::validate($bThrowExceptions);

		if ($mReturn === true) {
			$mReturn = array();
		}

		// Überprüfen ob erstellte Kombinationen noch alle Frei sind

		$oCombination = $this->getBuilder();

		$aAllocations = $oCombination->getAllocations();

//		$aFreeAllocations	= $oCombination->getFreeCombinations(false);
//
//		$aCheck				= Ext_TC_Util::arrayMergeRecrusivePreserveKeys($aFreeAllocations, $aAllocations);
//
//		if($aCheck !== $aFreeAllocations)
//		{
//			$iErrorKey = $this->_getErrorKey();
//
//			if($iErrorKey !== false)
//			{
//				$mReturn[$iErrorKey] = 'COMBINATION_NOT_FREE';
//			}
//			else
//			{
//				$mReturn[] = 'COMBINATION_NOT_FREE';
//			}
//		}

		if (empty($mReturn)) {
			$mReturn = true;
		}

		return $mReturn;
	}

	/**
	 * Kombinationsobjekt erstellen Verknüpft mit diesem Objekt
	 *
	 * @return \TsAccounting\Helper\Company\CombinationBuilder
	 */
	public function getBuilder()
	{
		$oCombination = new \TsAccounting\Helper\Company\CombinationBuilder($this);

		return $oCombination;
	}

	/**
	 * Nicht besetzte Kombinationen
	 *
	 * @return array
	 */
	public function getFreeCombinations()
	{
		$oCombination = $this->getBuilder();

		$aFreeCombinations = $oCombination->getFreeCombinations();

		return $aFreeCombinations;
	}

	/**
	 *
	 * @return bool
	 */
	public function useCompanies()
	{
		$sCompanyJoinKey = $this->_getCompaniesJoinKey();

		if ($sCompanyJoinKey) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Aus allen Kombinationen die Zuweisungen aus dieser Kombination entfernen
	 *
	 * @param array $aAllCombinations
	 */
	public function removeAllocations(&$aAllCombinations)
	{
		$oBuilder = $this->getBuilder();

		$aAllocations = $oBuilder->getAllocations();

		$oBuilder->removeCombinations($aAllCombinations, $aAllocations);
	}

	/**
	 * Falls bei einem Kind irgendwas noch zusätzlich gemacht werden muss an den Zuweisungen
	 *
	 * @param array $aFreeCombinations | hier kommen die übrigen freien Kombinationen an
	 * @return array
	 */
	public function manipulateAllocations(array $aFreeCombinations)
	{
		return $aFreeCombinations;
	}

	/**
	 * Erorkey falls beim validieren ein Fehler geschmissen wird
	 *
	 * @return mixed
	 */
	protected function _getErrorKey()
	{
		return false;
	}

	/**
	 * Jointable Key um die Firmen zu bekommen
	 *
	 * @return string
	 */
	abstract protected function _getCompaniesJoinKey();

	/**
	 * Jointable Key um die Schulen zu bekommen
	 *
	 * @return string
	 */
	abstract protected function _getSchoolJoinKey();

	/**
	 * Jointable Key um die Inboxen zu bekommen
	 *
	 * @return string
	 */
	abstract protected function _getInboxJoinKey();
}