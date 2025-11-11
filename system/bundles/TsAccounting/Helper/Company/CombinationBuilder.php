<?php

namespace TsAccounting\Helper\Company;

use Ext_Thebing_System;

class CombinationBuilder
{
	/**
	 *
	 * @var \TsAccounting\Entity\Company\CombinationAbstract
	 */
	protected $_Allocation;

	/**
	 * Cache der allen möglichen Kombinationen
	 *
	 * @var array
	 */
	protected static $_aAllCombinations = null;

	/**
	 * Cache der nicht belegten Kombinationen
	 *
	 * @var type
	 */
	protected static $_aFreeCombinations = null;

	/**
	 *
	 * @param \TsAccounting\Entity\Company\CombinationAbstract $oAllocation
	 */
	public function __construct(\TsAccounting\Entity\Company\CombinationAbstract $oAllocation)
	{
		$this->_oAllocation = $oAllocation;
	}

	/**
	 * Alle Kombinationen zwischen Firma / Schule / Inbox generieren
	 *
	 * @return array
	 */
	public function getAllCombinations()
	{
		if (self::$_aAllCombinations === null) {
			$aCombinations = array();

			// Firmen laden

			if (!$this->_oAllocation->useCompanies() || !Ext_Thebing_System::hasAccountingCompanies()) {
				$aCompanyIds = array(0);
			} else {
				$aCompanies = (array)Ext_Thebing_System::getAccountingCompanies(true);

				$aCompanyIds = array_keys($aCompanies);
			}

			foreach ($aCompanyIds as $iCompanyId) {
				$oCompany = \TsAccounting\Entity\Company::getInstance($iCompanyId);

				$iCompanyId = $oCompany->getId();

				// Alle Schulen die zu der Firma gehört laden
				$aSchools = $oCompany->getSchools();

				$aInboxes = $oCompany->getInboxes();

				if (empty($aInboxes)) {
					// Wenn keine Inboxen vorhanden, dann trotzdem eins leer definieren,
					// es müssen keine Inboxen vorhanden sein für die Kombinationen
					$aInboxes = array(new \Ext_Thebing_Client_Inbox());
				}

				foreach ($aSchools as $oSchool) {
					$iSchoolId = $oSchool->id;

					// Alle Inboxe anhand der Schule / Recht Verknüpfung laden
					#$aInboxes = $oSchool->getInboxList();

					foreach ($aInboxes as $oInbox) {
						$iInboxId = $oInbox->getId();

						if (!isset($aCombinations[$iCompanyId])) {
							$aCombinations[$iCompanyId] = array();
						}

						if (!isset($aCombinations[$iCompanyId][$iSchoolId])) {
							$aCombinations[$iCompanyId][$iSchoolId] = array();
						}

						$aCombinations[$iCompanyId][$iSchoolId][$iInboxId] = 1;
					}

				}

			}

			self::$_aAllCombinations = $aCombinations;
		}

		return self::$_aAllCombinations;
	}

	/**
	 * Kombinationen bilden, die über die Vorlage besetzt wurde
	 *
	 * @return array
	 */
	public function getAllocations()
	{
		$aAllocations = array();

		$aCompanyIds = $this->_oAllocation->getCompanyIds();
		$aSchoolIds = $this->_oAllocation->getSchoolIds();
		$aInboxIds = $this->_oAllocation->getInboxIds();

		if (empty($aCompanyIds)) {
			// Falls keine Firmen vorhanden, ein leeres definieren, da die nicht unbedingt vorhanden sein müssen
			$aCompanyIds = array(0);
		}

		$bHasCompanies = Ext_Thebing_System::hasAccountingCompanies();

		$bHasInbox = true;

		if (!Ext_Thebing_System::hasInbox()) {
			// Falls keine Inboxen vorhanden, ein leeres definieren, da die nicht unbedingt vorhanden sein müssen
			$aInboxIds = array(0);

			$bHasInbox = false;
		}

		foreach ($aCompanyIds as $iCompanyId) {
			if ($iCompanyId > 0) {
				$oCompany = \TsAccounting\Entity\Company::getInstance($iCompanyId);
				$aCompanySchools = $oCompany->getSchools();
				$aCompanyInboxes = $oCompany->getInboxes();
			}

			foreach ($aSchoolIds as $iSchoolId) {
				if (!isset($aCompanySchools[$iSchoolId]) && $this->_oAllocation->useCompanies() && $bHasCompanies) {
					continue;
				}

				foreach ($aInboxIds as $iInboxId) {
					if ($bHasInbox && !isset($aCompanyInboxes[$iInboxId]) && $this->_oAllocation->useCompanies() && $bHasCompanies) {
						continue;
					}

					if (!isset($aAllocations[$iCompanyId])) {
						$aAllocations[$iCompanyId] = array();
					}

					if (!isset($aAllocations[$iCompanyId][$iSchoolId])) {
						$aAllocations[$iCompanyId][$iSchoolId] = array();
					}

					$aAllocations[$iCompanyId][$iSchoolId][$iInboxId] = 1;
				}
			}
		}

		return $aAllocations;
	}

	/**
	 *
	 * @return array
	 */
	public function getFreeCombinations($bUseJoinDataCache = true)
	{
		if (!$bUseJoinDataCache) {
			// In manchen Fällen, dürfen nur die Kombinationen wieder
			// reingenommen werden, die schon abgespeichert wurden (z.B. validate() in der \TsAccounting\Entity\Company\CombinationAbstract)
			$this->_oAllocation->resetCombinationCache();
		}

		if (!$bUseJoinDataCache || self::$_aFreeCombinations === null) {
			// Alle möglichen Kombinationen
			$aAllCombinations = $this->getAllCombinations();

			$sClassName = $this->_oAllocation->getClassName();

			$oAllocation = new $sClassName();

			// Alle Erstellten Vorlagen
			$aArrayList = $oAllocation->getArrayList();

			foreach ($aArrayList as $aArrayData) {
				$oAllocation = call_user_func(array($sClassName, 'getInstance'), (int)$aArrayData['id']);

				/* @var $oAllocation \TsAccounting\Entity\Company\CombinationAbstract */

				$oAllocation->removeAllocations($aAllCombinations);
			}

			self::$_aFreeCombinations = $aAllCombinations;
		}

		$aFreeCombinations = self::$_aFreeCombinations;

		if ($bUseJoinDataCache) {
			$aFreeCombinations = $this->_oAllocation->manipulateAllocations($aFreeCombinations);
		}

		$aCurrentAllocation = $this->getAllocations();

		// Das Hauptobjekt darf die eigenen Kombinationen nicht als besetzt sehen, man sollte aber auch nicht den
		// Cache verändern, darum bauen wir das hier ein
		$aFreeCombinations = \Ext_TC_Util::arrayMergeRecrusivePreserveKeys($aFreeCombinations, $aCurrentAllocation);

		if (!$bUseJoinDataCache) {
			// Der Cache muss natürlich nach der Überprüfung wieder reingesetzt werden, damit
			// das Speichern auch die richtigen Datensätze nach dem validieren abspeichert
			$this->_oAllocation->rollbackCache();
		}

		return $aFreeCombinations;
	}

	/**
	 * Zuweisungen aus allen möglichen Kombinationen entfernen
	 *
	 * @param array $aAllCombinations
	 * @param array $aAllocations
	 */
	public function removeCombinations(&$aAllCombinations, $aAllocations)
	{

		// Zuweisungen durchgehen und aus allen möglichen Kombinationen entfernen
		foreach ($aAllocations as $iCompanyId => $aAllocationBySchool) {

			if (isset($aAllCombinations[$iCompanyId])) {

				foreach ($aAllocationBySchool as $iSchoolId => $aAllocationByInbox) {
					if (isset($aAllCombinations[$iCompanyId][$iSchoolId])) {
						foreach ($aAllocationByInbox as $iInboxId => $mValue) {
							if (isset($aAllCombinations[$iCompanyId][$iSchoolId][$iInboxId])) {
								// Letzte Ebene (Inbox-Ebene) aus dem Array entfernen
								unset($aAllCombinations[$iCompanyId][$iSchoolId][$iInboxId]);

								// Falls die 2.Ebene leer ist(Schul-Ebene) weil die Inbox gelöscht wurde,
								// dann die 2. Ebene ebenfall entfernen
								if (empty($aAllCombinations[$iCompanyId][$iSchoolId])) {
									unset($aAllCombinations[$iCompanyId][$iSchoolId]);
								}

								// Falls die 1.Ebene leer ist(Firmen-Ebene) weil die Schule gelöscht wurde,
								// dann die 1.Ebene ebenfalls enfernen
								if (empty($aAllCombinations[$iCompanyId])) {
									unset($aAllCombinations[$iCompanyId]);
								}
							}
						}
					}
				}
			}
		}
	}
}
