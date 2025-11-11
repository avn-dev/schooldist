<?php

/**
 * Helferklasse für das Prüfen von Flags von ihren Empfängerobjekten, wie Unterobjekten
 *
 * 1. Warnung: Potentielles SubObject wurde nicht ausgewählt
 * 2. Warnung: Nicht alle SubObjects des Contacts wurden ausgewählt
 */
class Ext_TC_Communication_Helper_CheckFlag
{
	public $aCachedFlags;
	public $aSelectedFlags;

	protected $_aMatching = array();
	protected $_aSupportedFlags = array();
	protected $_aWarnings = array();
	protected $_oTabArea;

	public function __construct(Ext_TC_Communication_Tab_TabArea $oTabArea)
	{
		$this->_oTabArea = $oTabArea;
		$this->_aSupportedFlags = $this->_oTabArea->getFlags();
	}

	/**
	 * Flags den Empfängern zuweisen, von denen sie stammen und zwischenspeichern für die 1. Warnung
	 *
	 * @param $aObject
	 * @return array
	 */
	public function getFlagsOfObject($aObject)
	{
		$aObjectFlags = array();

		$sObject = $aObject['object'];
		$iObjectId = $aObject['object_id'];

		// Alle Markierungen durchlaufen
		foreach((array)$this->aCachedFlags as $sFlagKey => $aContactSubObjects) {

			// Alle Unterobjekte des aktuellen Empfängerobjektes
			$aCachedObjectSubObjects = $aContactSubObjects[$sObject][$iObjectId];
			$aSelectedFlag = $this->aSelectedFlags[$sFlagKey];

			// Wenn es Unterobjekte gibt
			if(
				$aSelectedFlag['checked'] &&
				!empty($aCachedObjectSubObjects)
			) {
				
				// Unterobjektklassen durchlaufen
				foreach($aCachedObjectSubObjects as $sSubObjectClass => $aSubObjectIds) {

					// Unterobjekte durchlaufen
					foreach($aSubObjectIds as $iSubObjectId) {

						// Array mit allen selektierten Unterobjekten dieser Markierung und Unterobjektklasse
						$aSelectedSubObjects = (array)$aSelectedFlag['subobjects'][$sSubObjectClass];

						// Wenn Unterobjekt dieses Empfängerobjektes selektiert
						if(in_array($iSubObjectId, $aSelectedSubObjects)) {

							$aObjectFlags[$sFlagKey][$sSubObjectClass][] = $iSubObjectId;

							// Verwendung speichern (damit man die 1. Warnung überprüfen kann)
							$this->_aMatching[$sFlagKey][$sSubObjectClass][$iSubObjectId] = true;

						}

					}

					// Wenn dem Empfängerobjekt trotz vorhandener Unterobjekte keines zugewiesen ist
					if(empty($aObjectFlags[$sFlagKey])) {

						// 2. Warnung
						$this->_setWarning(
							'RECIPIENT_HAS_NO_SUBOBJECTS',
							$sFlagKey,
							$sSubObjectClass,
							$aObject
						);
					}
				}
			} else {

				// Flag hat keine SubObjects, wurde aber ausgewählt
				// Da man dem Flag keine Empfänger zuordnen kann, gilt es für jedes Objekt
				if(!empty($this->aSelectedFlags[$sFlagKey])) {
					$aObjectFlags[$sFlagKey] = array();
				}

			}
		}

		return $aObjectFlags;
	}

	/**
	 * Generiert die Warnungen, die in dieser Klasse zwischengespeichert wurden und generiert außerdem die 1. Warnung
	 */
	public function generateWarnings()
	{
		foreach($this->aSelectedFlags as $sFlagKey => $aSelectedFlag) {

			// Wenn Unterobjekte vorhanden
			if(!empty($aSelectedFlag['subobjects'])) {

				// Unterobjekte durchlaufen und prüfen, ob diese einem Empfängerobjekt zugeordnet werden konnten
				foreach($aSelectedFlag['subobjects'] as $sSubObjectClass => $aSubObjects) {

					if(!empty($aSubObjects)) {

						foreach($aSubObjects as $iSubObjectId) {

							// Wenn das Unterobjekt nicht als "verwendet" markiert wurde, Fehler werfen
							if(!isset($this->_aMatching[$sFlagKey][$sSubObjectClass][$iSubObjectId])) {

								// 1. Warnung
								$this->_setWarning(
									'SUBOBJECT_NOT_USED',
									$sFlagKey,
									array(
										'object' => $sSubObjectClass,
										'object_id' => $iSubObjectId
									)
								);

							}
						}
					}
				}
			}
		}
	}

	/**
	 * Liefert alle generierten Warnungen
	 * Setzt voraus, dass die Methoden zuvor auch entsprechend aufgerufen wurden
	 * @return array
	 */
	public function getWarnings()
	{
		return $this->_aWarnings;
	}

	/**
	 * Warnung generieren und Platzhalter in der Fehlermeldung ersetzen
	 * @param string $sKey Fehler-Key
	 * @param string $sFlag Flag-Key
	 * @param array|string $mSubObject Klasse des SubObjects oder Array mit Klasse und ID
	 * @param array $aContact Kontakt-Array aus der Kommunikation
	 */
	protected function _setWarning($sKey, $sFlag, $mSubObject, $aContact = array())
	{
		$sWarning = $sSubObject = '';

		// 1. Warnung
		if($sKey === 'SUBOBJECT_NOT_USED') {

			$sSubObject = Ext_TC_Factory::getInstance($mSubObject['object'], $mSubObject['object_id'])->getName();
			$sWarning = Ext_TC_Communication::t('Es ist bei der Markierung "{flag}" das Unterobjekt "{subobject}" gewählt, mit dem kein Empfänger verknüpft ist.');

		}

		// 2. Warnung
		elseif($sKey === 'RECIPIENT_HAS_NO_SUBOBJECTS') {

			$sSubObject = Ext_TC_Factory::executeStatic($mSubObject, 'getClassLabel', array(false));
			$sWarning = Ext_TC_Communication::t('Es ist der Empfänger "{recipient}" gewählt für den bei der Markierung "{flag}" kein entsprechendes Unterobjekt "{subobject}" ausgewählt ist.');
			$sWarning = str_replace('{recipient}', $aContact['name'], $sWarning);

		}

		$sFlag = $this->_aSupportedFlags[$this->_oTabArea->getType()][$sFlag]['label'];

		$sWarning = str_replace('{flag}', $sFlag, $sWarning);
		$sWarning = str_replace('{subobject}', $sSubObject, $sWarning);

		$this->_aWarnings[] = $sWarning;
	}

}