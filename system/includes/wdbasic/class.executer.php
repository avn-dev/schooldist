<?php

class WDBasic_Executer {

	/**
	 * @var DB
	 */
	private $_oDb;

	/**
	 * @var WDBasic
	 */
	protected $_oEntity;

	/**
	 * Initialisiert ein neuen <tt>WDBasicPersister</tt>.
	 * 
	 * @param DB $oDataBaseConnection
	 * @param WDBasic $oEntity
	 */
	public function __construct(DB $oDataBaseConnection, WDBasic $oEntity) {

		$this->_oDb = $oDataBaseConnection;
		$this->_oEntity = $oEntity;

	}

	/**
	 * Führt die SQL-Abfrage basierend auf der Kriterien aus und liefert das Ergebnis zurück.
	 * 
	 * @param array $iId Die Kriterien
	 * @return array Das Ergebnis der SQL-Abfrage
	 */
	public function load($iId) {

		$sTableName = $this->_oEntity->getTableName();
		$sPrimaryKey = $this->getPrimaryKey();

		$sSql = '
			SELECT
				*
			FROM
				#tablename
			WHERE
				`' . $sPrimaryKey . '` = :pk
		';

		$aSql = array(
			'pk' => $iId,
			'tablename' => $sTableName
		);

		$aResult = $this->_oDb->queryRow($sSql, $aSql);

		return $aResult;
	}

	/**
	 * Gibt die erste Entity basierrned auf den Kriterien zurück.
	 *  
	 * @param array $aCriteria
	 * @return array - Das <b>Ergebnis der SQL-Abfrage</b> oder <b>NULL</b>
	 */
	public function loadOneBy(array $aCriteria) {

		$aResults = $this->loadAll($aCriteria);

		if(is_array($aResults) && count($aResults) >= 1) {
			return $aResults[0];
		} else {
			return null;
		}

	}

	/**
	 * Führt die SQL-Abfrage basierend auf der Kriterien aus und liefert das Ergebnis-Entity zurück.
	 * 
	 * @param array $aCriteria Die Kriterien
	 * @param int $iOffset
	 * @param int $iLimit
	 * @return array - Das Ergebnis der SQL-Abfrage
	 */
	public function loadAll(array $aCriteria, $iLimit = null, $iOffset = null) {

		$sTableName = $this->_oEntity->getTableName();
		$aSql = [ 'tablename' => $sTableName ];
				
		$sSql = '
			SELECT
				*
			FROM
				#tablename
		';

		if (count($aCriteria) !== 0) {
			$sSql .= $this->getWherePart($aCriteria);
		}

		if($this->_oEntity->hasSortColumn()) {
			$aSql['sort_column'] = $this->_oEntity->getSortColumn();
			$sSql .= ' ORDER BY #sort_column ASC';
		}
		
		if($iLimit !== null) {
			if($iOffset === null) {
				$iOffset = 0;
			}
			$sSql .= ' LIMIT ' . $iOffset . ', ' . $iLimit;
		}

		$aSql = array_merge($aSql, $aCriteria);

		$aResults = $this->_oDb->queryRows($sSql, $aSql);

		return $aResults;
	}

	/**
	 * Gibt den Where-Teil der Sql-Abfrage zurück. Wenn dieser nicht existiert,
	 * dann wird ein leerer String zurückgegeben
	 * 
	 * @param array $aCriteria Die Kriterien
	 * @return String - <b>Leer</b>, wenn kein Where-Teil existiert, sonst 
	 * <b>Where-Teil</b>
	 */
	public function getWherePart(array $aCriteria) {

		$aTemp = array();
		foreach ($aCriteria as $sCriteria => $mValue) {
			if($mValue === null) {
				$aTemp[] = '`' . $sCriteria . '` IS NULL';
            } else if(is_array($mValue)) {
                $aTemp[] = '`' . $sCriteria . '` IN (:' . $sCriteria.')';
			} else {
				$aTemp[] = '`' . $sCriteria . '` = :' . $sCriteria;
			}
		}

		$sWherePart = '	WHERE ';
		$sWherePart .= implode(' AND ', $aTemp);

		return $sWherePart;
	}

	/**
	 * Gibt den Primärschlüssel der Tabelle zurück.
	 * 
	 * @return String <b>Primärschlüssel</b> oder <b>NULL</b>, wenn kein PK existiert.
	 */
	public function getPrimaryKey() {

		$aTableFields = $this->_oEntity->getTableFields();

		foreach ($aTableFields as $aTableField) {
			foreach ($aTableField as $aColumns) {
				if ($aColumns['Key'] === 'PRIMARY') {
					return $aColumns['Field'];
				}
			}
		}

		return null;
	}

}
