<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * https://laravel.com/docs/7.x/queries
 */
class WDBasic_Repository {

	/**
	 * @var WDBasic_Executer
	 */
	protected $_oWDBasicExecuter;

	/**
	 * @var	WDBasic
	 */
	protected $_oEntity;

	/**
	 * @var	string
	 */
	protected $_sTableName;

	/**
	 * @var boolean 
	 */
	protected $_bCheckActive = false;

	/**
	 * Initialisiert ein neuen <tt>WDBasicRepository</tt>.
	 *
	 * @param DB $oDataBaseConnection Die Datenbankverbindung, die benutzt wird.
	 * @param WDBasic $oEntity Die Entität(WDBasic-Objekt).
	 */
	public function __construct(DB $oDataBaseConnection, WDBasic $oEntity) {

		$this->_oEntity = $oEntity;

		// auf active prüfen
		if($this->_oEntity->hasActiveField()) {
			$this->_bCheckActive = true;
		}

		$this->_sTableName = $oEntity->getTableName();
		$this->_oWDBasicExecuter = new WDBasic_Executer($oDataBaseConnection, $oEntity);

	}

	/**
	 * Finden eines Eintrags nach ihrem Primärschlüssel
	 *
	 * @param int $iId Der Primärschlüssel.
	 * @return WDBasic Eine <b>Entität</b><i>(WDBasic-Objekt)</i> oder <b>NULL</b>.
	 */
	public function find($iId) {

		$aResult = $this->_oWDBasicExecuter->load($iId);
		$oEntity = $this->_getEntity($aResult);

		return $oEntity;
	}

	/**
	 * Sucht einen Eintrag anhand des Primärschlüssels oder schmeißt eine Exception
	 *
	 * @param $iId
	 * @return WDBasic|null
	 * @throws ModelNotFoundException
	 */
	public function findOrFail($iId) {

		if(is_null($oEntity = $this->find($iId))) {
			throw (new ModelNotFoundException())->setModel(get_class($this->_oEntity), $iId);
		}

		return $oEntity;
	}

	/**
	 * Findet alle Spalten der Tabelle.
	 *
	 * @param int $iLimit
	 * @param int $iOffset
	 * @return array Ein <b>Array</b>, das ggf. mit <i>Entitäten(WDBasic-Objekten)</i> gefüllt ist.
	 */
	public function findAll($iLimit = null, $iOffset = null) {

		$aEntities = $this->findBy(array(), $iLimit, $iOffset);

		return $aEntities;
	}

	/**
	 * Findet einen bzw. mehrere Einträge nach den gegebenen Kriterien.
	 *
	 * @param array $aCriteria
	 * @param int $iLimit
	 * @param int $iOffset
	 * @return array Ein <b>Array</b>, das ggf. mit <i>Entitäten(WDBasic-Objekten)</i> gefüllt ist.
	 */
	public function findBy(array $aCriteria, $iLimit = null, $iOffset = null) {

		$this->_manipulateCriteria($aCriteria);

		$aResults = $this->_oWDBasicExecuter->loadAll($aCriteria, $iLimit, $iOffset);

		$aEntities = array();
		if(is_array($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}

	/**
	 * Findet genau einen Eintrag nach den gegebenen Kriterien.
	 *
	 * @param array $aCriteria Die Kriterien
	 * @return WDBasic Eine <b>Entität</b><i>(WDBasic-Objekt)</i> oder <b>NULL</b>.
	 */
	public function findOneBy(array $aCriteria) {

		$this->_manipulateCriteria($aCriteria);

		$aResult = $this->_oWDBasicExecuter->loadOneBy($aCriteria);

		$oEntity = null;
		if(is_array($aResult)) {
			$oEntity = $this->_getEntity($aResult);
		}

		return $oEntity;
	}

	/**
	 * Sucht einen Eintrag anhand gegebener Kriterien oder schmeißt eine Exception
	 *
	 * @param $iId
	 * @return WDBasic|null
	 * @throws ModelNotFoundException
	 */
	public function findOneByOrFail(array $aCriteria) {

		if(is_null($oEntity = $this->findOneBy($aCriteria))) {
			throw (new ModelNotFoundException())->setModel(get_class($this->_oEntity), $aCriteria);
		}

		return $oEntity;
	}

	/**
	 * Gibt eine Entität zurück.
	 * 
	 * @param array $aResult Die Ergebnisse
	 * @return WDBasic Eine <b>Entität</b><i>(WDBasic-Objekt)</i> oder <b>NULL</b>.
	 */
	protected function _getEntity(array $aResult=null) {

		if (!empty($aResult)) {
			/* @var $oEntity \WDBasic */
			$oEntity = $this->_oEntity->getObjectFromArray($aResult);
		} else {
			$oEntity = null;
		}

		return $oEntity;
	}

	/**
	 * Gibt ein Array aus Entitäten zurück.
	 * 
	 * @param array $aResults Die Ergebnisse
	 * @return array Ein <b>Array</b>, das ggf. mit <i>Entitäten(WDBasic-Objekten)</i> gefüllt ist.
	 */
	protected function _getEntities(array $aResults) {

		$aEntities = array();
		foreach ($aResults as $aResult) {
			$aEntities[] = $this->_getEntity($aResult);
		}		

		return $aEntities;
	}

	/**
	 * Manipuliert die Standard-Kriterien
	 * 
	 * @param array $aCriteria
	 */
	protected function _manipulateCriteria(array &$aCriteria) {

		// auf active prüfen
		if ($this->_bCheckActive === true) {
			$aCriteria['active'] = 1;
		}

	}
	
	/**
	 * @param bool $bCheckActive
	 * @return $this
	 */
	public function setCheckActive(bool $bCheckActive) {
		$this->_bCheckActive = $bCheckActive;
		
		return $this;
	}

}
