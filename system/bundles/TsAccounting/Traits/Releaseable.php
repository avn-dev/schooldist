<?php

namespace TsAccounting\Traits;

/**
 * TODO - das $this->_sError und $this->_sHint ist nicht schön (übernommen aus Ext_Thebing_Inquiry_Document)
 *
 * Trait Releaseable
 * @package TsAccounting\Traits
 */
trait Releaseable {

	/**
	 * Fehler die beim freigeben passieren können
	 *
	 * @var string
	 */
	protected $_sError = null;

	/**
	 * Warnmeldungen die beim freigeben passieren können
	 *
	 * @var string
	 */
	protected $_sHint = null;
	protected $_sHintCode = null;

	/**
	 * Datumm der Freigabe
	 *
	 * @param string $sReturnPart
	 * @return mixed
	 */
	public function getReleaseTime($sReturnPart = \WDDate::TIMESTAMP) {
		$sReturn = null;

		if($this->isReleased()) {
			$aRelease = $this->getRelease();

			$sReleaseDate = $aRelease['created'];

			if($sReleaseDate != '0000-00-00 00:00:00' && \WDDate::isDate($sReleaseDate, \WDDate::DB_TIMESTAMP)) {
				$oDate = new \WDDate($sReleaseDate, \WDDate::DB_TIMESTAMP);
				$sReturn = $oDate->get($sReturnPart);
			}
		}

		return $sReturn;
	}

	/**
	 * Benutzer der Freigabe
	 *
	 * @return int
	 */
	public function getReleaseUser() {
		$iCreatorId = null;

		if($this->isReleased()) {
			$aRelease = $this->getRelease();
			$iCreatorId = $aRelease['creator_id'];
		}

		return $iCreatorId;
	}

	/**
	 * @param $iCreatorId
	 * @throws \Exception
	 */
	public function insertRelease($iCreatorId = null) {

		$aJoinTableConfig = $this->getJoinTable('release');

		if(empty($aJoinTableConfig)) {
			throw new \RuntimeException('Missing release config');
		}

		if(is_null($iCreatorId)) {
			global $user_data;
			$iCreatorId = (int)$user_data['id'];
		}

		$oDate = new \Carbon\Carbon();

		$aData = array(
			$aJoinTableConfig['primary_key_field'] => $this->getId(),
			'creator_id' => $iCreatorId,
			'created' => $oDate->toDateTimeString(),
		);

		// TODO - Warum nicht über ->release =
		\DB::insertData($aJoinTableConfig['table'], $aData);
		\Ext_Thebing_Log::w($this->getClassName(), $this->id, \Ext_Thebing_Log::UPDATED, array('release' => $aData));

	}

	public function removeRelease() {
		
		$aJoinTableConfig = $this->getJoinTable('release');

		if(empty($aJoinTableConfig)) {
			throw new \RuntimeException('Missing release config');
		}

		$sqlQuery = "DELETE FROM #table WHERE #field = :id";

		$sqlParam = [
			'table' => $aJoinTableConfig['table'],
			'field' => $aJoinTableConfig['primary_key_field'],
			'id' => $this->getId()
		];
		
		\DB::executePreparedQuery($sqlQuery, $sqlParam);
		\Ext_Thebing_Log::w($this->getClassName(), $this->id, \Ext_Thebing_Log::UPDATED, array('release' => $aData));

	}
	
	/**
	 * Freigabe Daten holen
	 *
	 * @return array
	 */
	public function getRelease() {
		$aRelease = (array)$this->release;

		if(!empty($aRelease)) {
			$aRelease = reset($aRelease);
		}

		return $aRelease;
	}

	/**
	 * Überprüfen ob Dokument freigegeben ist
	 *
	 * @return bool
	 */
	public function isReleased() {
		$aRelease = $this->getRelease();
		return !empty($aRelease);
	}


	/**
	 * Überprüfen ob Fehler existiert
	 *
	 * @return bool
	 */
	public function hasError() {
		return $this->_sError !== null;
	}

	/**
	 * Überprüfen ob Warnung existiert
	 *
	 * @return bool
	 */
	public function hasHint() {
		return $this->_sHint !== null;
	}

	/**
	 * Warnung bekommen
	 *
	 * @return string | null
	 */
	public function getHint() {
		return $this->_sHint;
	}

	public function getHintCode() {
		return $this->_sHintCode;
	}

	/**
	 * Fehler bekommen
	 *
	 * @return string | null
	 */
	public function getError() {
		return $this->_sError;
	}

}
