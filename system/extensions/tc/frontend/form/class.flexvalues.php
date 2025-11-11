<?php

/**
 * Diese Klasse dient für individuelle Felder vom Typ "Kontakt bezogen auf Buchung" im 
 * Frontend-Formular, da diese i.d.R. anders abgespeichert werden müssen
 * 
 * - über die Methode set() wird sich ein Wert zu einem "falschen" Entity gemerkt
 * - über _setRealFlexValues() werden diese Werte dem "richtigen" Objekt zugewiesen
 * 
 * In der Agentur-Software ist die zum Beispiel nötig, da beim Speichern der Buchung nur die 
 * Zwischentabelle zwischen Buchung und Kontakt befüllt. Für die individuelle Felder vom Typ
 * "Kontakt bezogen auf Buchung" wird allerdings die ID dieser Zwischentabelle benötigt
 */
class Ext_TC_Frontend_Form_FlexValues {
	/**
	 * @var array 
	 */
	protected $_aEntityValues = array();
	/**
	 * @var array 
	 */
	protected $_aRealEntities = array();
	
	/**
	 * Merkt sich die Werte von Feldern vom Typ "Kontakt bezogen auf Buchung"
	 * 
	 * @param Ext_TC_Basic $oEntity
	 * @param Ext_TC_Gui2_Design_Tab_Element $oTabElement
	 * @param mixed $mValue
	 * @param Ext_TC_Basic|null $oParent
	 */
	public function set(Ext_TC_Basic $oEntity, Ext_TC_Gui2_Design_Tab_Element $oTabElement, $mValue, $oParent = null) {
		
		if(!empty($mValue)) {
			$oField = Ext_TC_Flexibility::getInstance($oTabElement->special_type);

			$oStdClass = new stdClass();
			$oStdClass->oEntity = $oEntity;
			$oStdClass->oParent = $oParent;
			$oStdClass->oField = $oField;
			$oStdClass->mValue = $mValue;		
		
			$this->_aEntityValues[$oTabElement->id] = $oStdClass;
		}

	}
	
	/**
	 * Liefert den Wert für ein individuelles Feld
	 * 
	 * @param Ext_TC_Gui2_Design_Tab_Element $oTabElement
	 * @return string
	 */
	public function getValue(Ext_TC_Basic $oEntity, Ext_TC_Gui2_Design_Tab_Element $oTabElement) {
		$mValue = '';
		
		if($this->_aEntityValues[$oTabElement->id]) {
			$oFlexDataEntry = $this->_aEntityValues[$oTabElement->id];
			$mValue = $oFlexDataEntry->mValue;
		}
		
		return $mValue;
	}
	
	/**
	 * Speichert alle Werte der individuellen Feldern
	 */
	public function save(WDBasic $oEntity) {		
		$aCacheData = array();
		
		foreach($this->_aEntityValues as $oFlexDataEntry) {
			$sCacheKey = $oFlexDataEntry->oEntity->id . '_' . $oFlexDataEntry->oField->id;
			
			if(
				isset($aCacheData[$sCacheKey]) ||
				empty($oFlexDataEntry->mValue)
			) {
				continue;
			}
			
			// Wert dem "richtigen" Objekt zuweisen
			$this->_setRealFlexValue($oFlexDataEntry->oEntity, $oFlexDataEntry->oField, $oFlexDataEntry->mValue, $oFlexDataEntry->oParent);
					
			$aCacheData[$sCacheKey] = $oFlexDataEntry->mValue;	
		}
		
		// die "richtigen" Objekte speichern
		$this->_saveRealEntities();
	}
	
	/**
	 * Speichert alle "richtigen" Objekten, denen die Werte der individuellen Felder zugewiesen
	 * wurde
	 */
	final protected function _saveRealEntities() {
		foreach($this->_aRealEntities as $sWDBasicClass => $aEntities) {
			foreach($aEntities as $oEntity) {
				if($oEntity instanceof Ext_TC_Basic) {
					$oEntity->save();
				}
			}
		}
	}
	
	/**
	 * Liefert das "richtige" Objekt zu einer Entity
	 * 
	 * - muss vorher mit _setRealEntity() gesetzt werden
	 * 
	 * @param Ext_TC_Basic $oEntity
	 * @return Ext_TC_Basic|null
	 */
	protected function _getRealEntity(Ext_TC_Basic $oEntity) {
		
		$sClass = get_class($oEntity);
		
		if(isset($this->_aRealEntities[$sClass][$oEntity->id])) {
			return $this->_aRealEntities[$sClass][$oEntity->id];
		}
		
		return null;
	}
	
	/**
	 * Merkt sich die Verbindung von einem Entity zu seinem "richtigen" Objekt
	 * 
	 * @param Ext_TC_Basic $oEntity
	 * @param Ext_TC_Basic $oRealEntity
	 */
	protected function _setRealEntity(Ext_TC_Basic $oEntity, Ext_TC_Basic $oRealEntity) {
		$sClass = get_class($oEntity);
		$this->_aRealEntities[$sClass][$oEntity->id] = $oRealEntity;
	}
	
	/**
	 * Setzt die Werte für ein individuelles Feld in das "richtige" Objekt
	 * 
	 * - diese Methode muss abgeleitet werden
	 * 
	 * @param Ext_TC_Basic $oEntity
	 * @param Ext_TC_Flexibility $oField
	 * @param mixed $mValue
	 * @param Ext_TC_Basic|null $oParent
	 */
	protected function _setRealFlexValue(Ext_TC_Basic $oEntity, Ext_TC_Flexibility $oField, $mValue, $oParent = null) {
		// ableiten
	}
	
}

