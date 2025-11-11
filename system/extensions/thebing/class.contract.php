<?php

/**
 * Beschreibung der Klasse
 */
class Ext_Thebing_Contract extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_contracts';
	protected $_sTableAlias = 'kcont';
	
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * Gibt die Vetragsvorlage zurück
	 * @return Ext_Thebing_Contract_Template
	 */
	public function getContractTemplate() {

		if($this->contract_template_id > 0) {

			$oTemplate = Ext_Thebing_Contract_Template::getInstance($this->contract_template_id);
			return $oTemplate;

		} else {
			throw new Exception('You have to set a contract template!');
		}

	}

	public function getBasicContract() {
		$oTemplate = $this->getContractTemplate();

		if($oTemplate->type == 1) {
			return $this;
		} else {

			$oVersion = $this->getLatestVersion();

			if($oVersion) {
				$iMainContract = $oVersion->searchMainContract(true);

				if(!empty($iMainContract)) {
					$oMainContract = Ext_Thebing_Contract::getInstance($iMainContract);
					return $oMainContract;
				}
			}

		}

		return false;

	}

	public function getLatestVersion() {

		$sSql = "
				SELECT
					`id`
				FROM
					kolumbus_contracts_versions
				WHERE
					`contract_id` = :contract_id AND
					`active` = 1
				ORDER BY
					`created` DESC
				LIMIT 1
				";
		$aSql = array('contract_id'=>$this->id);
		$iVersionId = DB::getQueryOne($sSql, $aSql);

		if(empty($iVersionId)) {
			return false;
		}

		$oVersion = Ext_Thebing_Contract_Version::getInstance($iVersionId);

		return $oVersion;

	}

	/**
	 * @return Ext_Thebing_Contract_Version[]
	 */
	public function getVersions() {

		$sSql = "
			SELECT
				*
			FROM
				kolumbus_contracts_versions
			WHERE
				`contract_id` = :contract_id AND
				`active` = 1
			ORDER BY
				`created` DESC
		";

		$aSql = ['contract_id' => $this->id];
		$aVersions = (array)DB::getQueryRows($sSql, $aSql);

		$aVersions = array_map(function($aVersion) {
			return Ext_Thebing_Contract_Version::getObjectFromArray($aVersion);
		}, $aVersions);

		return $aVersions;

	}

	/**
	 * Generate number depending on contract template id
	 */
	public function generateNumber() {

		if($this->contract_template_id > 0) {

			$oTemplate = $this->getContractTemplate();
			$oBasicContract = $this->getBasicContract();
			$oDate = new WDDate($this->date, WDDate::DB_DATE);

			$sNumber = $oTemplate->number;
			$sNumber = str_replace('%d', $oDate->get(WDDate::DAY), $sNumber);
			$sNumber = str_replace('%m', $oDate->get(WDDate::MONTH), $sNumber);
			$sNumber = str_replace('%Y', $oDate->get(WDDate::YEAR), $sNumber);
			$sNumber = str_replace('%partner_counter', $this->_getNextCounter(true), $sNumber);
			$sNumber = str_replace('%contract_counter', $this->_getNextCounter(false), $sNumber);
			if($this != $oBasicContract) {
				$sNumber = str_replace('%basic_contract', $oBasicContract->number, $sNumber);
			}

			$this->number = $sNumber;

			$this->save();

		} else {
			throw new Exception('You have to set a contract template!');
		}

		return $this->number;

	}

	protected function _getNextCounter($bUseItem=false) {

		$sSql = "
				SELECT
					`counter`
				FROM
					`kolumbus_contract_counter`
				WHERE
					`template_id` = :template_id AND
					`item_id` = :item_id
				";
		$aSql = array();
		$aSql['template_id'] = (int)$this->contract_template_id;
		if($bUseItem) {
			$aSql['item_id'] = (int)$this->item_id;
		} else {
			$aSql['item_id'] = 0;
		}

		$iCounter = DB::getQueryOne($sSql, $aSql);
		$iCounter = (int)$iCounter;
		$iCounter++;

		$sSql = "
				REPLACE
					`kolumbus_contract_counter`
				SET
					`template_id` = :template_id,
					`item_id` = :item_id,
					`counter` = :counter
				";
		$aSql['counter'] = $iCounter;
		DB::executePreparedQuery($sSql, $aSql);

		return $iCounter;

	}

	public function getItemObject() {

		switch($this->item) {
			case 'accommodation':
				$oItem = Ext_Thebing_Accommodation::getInstance($this->item_id);
				break;
			case 'teacher':
			default:
				$oItem = Ext_Thebing_Teacher::getInstance($this->item_id);
				break;
		}

		return $oItem;

	}

	public function getSchool() {
		$oSchool = Ext_Thebing_School::getInstance($this->school_id);
		return $oSchool;
	}

	public function delete($bLog = true) {

		$bSuccess = parent::delete($bLog);

		if($bSuccess) {
			$aVersions = $this->getVersions();
			foreach($aVersions as $oVersion) {
				$oVersion->bPurgeDelete = $this->bPurgeDelete;
				$oVersion->delete(true, true);
			}
		}

		return $bSuccess;

	}

	/**
	 * Setzt den Zeitpunkt der letzten Bearbeitung und den Bearbeiter
	 * @see Ext_Thebing_Contract_Version::save()
	 */
	public function updateChanged() {

		$this->_bOverwriteCurrentTimestamp = true;
		$this->changed = time();

		$oAccess = Access::getInstance();

		if($oAccess instanceof Access_Backend) {
			$this->editor_id = $oAccess->id;
		}

	}

}