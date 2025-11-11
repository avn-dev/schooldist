<?php
/**
 * Klass für ein einfaches Matching
 */
class Ext_TC_Gui2_Selection_Matching
{

	protected $_aIndex = array();

	/**
	 * Prüft, ob der Kontext schon existiert; ansonsten wird er angelegt
	 * @param $mContext
	 */
	protected function _checkIndex($mContext) {
		if(!isset($this->_aIndex[$mContext])) {
			$this->_aIndex[$mContext] = array();
		}
	}

	/**
	 * Fügt einen Wert hinzu, dient dann eher als Collector
	 * @param mixed $mContext Ein Kontext
	 * @param int $iId
	 * @param string $sDescription
	 */
	public function addValue($mContext, $iId, $sDescription) {

		$this->_checkIndex($mContext);
		$this->_aIndex[$mContext][$iId] = $sDescription;

	}

	/**
	 * Fügt einen Wert hinzu
	 *
	 * Diese Methode MUSS benutzt werden für getAllMatchingResult()!
	 * Sie erwartet ein komplettes $aResult eines Objektes.
	 *
	 * @param string $mContext
	 * @param mixed $mArray
	 */
	public function addArrayValue($mContext, $mArray) {

		$this->_checkIndex($mContext);

		if(
			is_array($mArray) &&
			!empty($mArray)
		) {
			foreach($mArray as $iId => $sDescription) {
				$this->addValue($mContext, $iId, $sDescription);
			}
		}

	}

	/**
	 * Liefert ein Array aller Einträge, die in allen Kontexten vorkommen
	 *
	 * @return array
	 */
	public function getAllMatchingResult() {

		$iContexts = count($this->_aIndex);
		$aCounter = array();
		$aResult = array();

		// Index durchlaufen und Funde zählen
		foreach($this->_aIndex as $sContext => $aData) {
			foreach($aData as $iId => $sDescription) {

				if(!isset($aCounter[$iId])) {
					$aCounter[$iId] = 0;
				}

				++$aCounter[$iId];

			}
		}

		// Nun alle Elemente zurückgeben, die in allen Kontexten vorkommen
		foreach($this->_aIndex as $sContext => $aData) {
			foreach($aData as $iId => $sDescription) {

				if($aCounter[$iId] == $iContexts) {
					$aResult[$iId] = $sDescription;
				}

			}
		}

		return $aResult;

	}


}