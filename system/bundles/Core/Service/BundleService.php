<?php

namespace Core\Service;

use Core\Entity\System;

/**
 * Diese Klasse verwaltet die Bundles.
 */
class BundleService {

	/**
	 * Der name des Corebundles.
	 * @var string
	 */
	private $_sCoreBundleName = 'Core';

	private static ?array $activeBundleCache = null;

	/**
	 * Gibt alle im Projekt vorhandenen Verzeichnisse der Bundle zurück.
	 * 
	 * @return array Alle vorhandenen Verzeichnisse der Bundles.
	 */
	public function getBundleDirs(){
		// Das Pattern für alle Bundleverzeichnisse
		$sPatternForBundleDirs = \Util::getDocumentRoot() . "system/bundles/*";
		// Alle Bundleverzeichnisse als Array
		$aBundleDirs = glob($sPatternForBundleDirs, GLOB_ONLYDIR);

		return $aBundleDirs;
	}

	/**
	 * Gibt alle im Projekt vorhandenen Bundlenamen zurück.
	 * 
	 * @return array Alle vorhandenen Bundles.
	 */
	public function getBundleNames(){
		// Alle Bundleverzeichnisse als Array
		$aBundleDirs = $this->getBundleDirs();
		// Array mit allen Bundlenamen
		$aBundleNames = array();
		/**
		 * In dieser foreach wird der absoluten Pfad eines Bundles als Array
		 * dargestellt, um das letzte Element - und somit den Bundnamen - mittels
		 * array_pop in dem array der Bundlenamen abzuspeichern
		 */
		foreach ($aBundleDirs as $sBundleDir) {
			// array mit dem absoluten Pfad eines Bundles
			$aBundleDir = explode('/', $sBundleDir);
			// Der Bundlename wobei ein leerer Wert durch das obrige Pattern ausgeschlossen ist
			$sBundleName = array_pop($aBundleDir);
			// Fügt dem array mit den Bundlenamen ein Bundlenamen hinzu
			$aBundleNames[] = $sBundleName;
		}

		return $aBundleNames;
	}

	/**
	 * <p>
	 * Gibt alle aktiven Bundlenamen zurück.
	 * Dazu werden aus der "system_elements" Tabelle alle Einträgen - für die ein
	 * Bundle existiert - selektiert.
	 * Wenn ein Eintrag "active=1" hat, also aktiv ist, dann wird der Bundlename zu
	 * diesem Eintrag im zurückgegebenen Array berücksichtigt.
	 * </p>
	 * @return array <p>
	 * Ein array gefüllt mit allen aktiven Bundlenamen, oder ein leeres Array.
	 * </p>
	 */
	public function getActiveBundleNames() {

		if (self::$activeBundleCache === null) {
			$aBundleNames = $this->getBundleNames();
			$aActiveBundles = System\Elements::query()
				->get()
				->map(fn(\Core\Entity\System\Elements $oBundle) => strtolower($oBundle->file))
				->toArray();

			self::$activeBundleCache = array_filter($aBundleNames, fn(string $sBundleName) => in_array(strtolower($sBundleName), $aActiveBundles));
		}

		return self::$activeBundleCache;

		/*
		// Inztanziiert das Repository der Klasse Core\Entity\System\Elements
		$oSystemElementsRepository = System\Elements::getRepository();
		// Alle im Projekt vorhandenen Bundles
		$aBundleNames = $this->getBundleNames();

		// Alle aktiven Bundlenamen
		$aActiveBundleNames = array();

		// Core-Bundle wurde IMMER doppelt hinzugefügt, weil $aActiveBundleNames immer leer war
//		// Corebundlename muss immer mit enthalten sein, da das Corebundle immer vorhanden und aktiv ist!
//		$this->_addCoreBundle($aActiveBundleNames);

		foreach ($aBundleNames as $sBundleName) {
			// Das Array mit den Kriterien.
			$aCriteria = array('file' => [strtolower($sBundleName), $sBundleName]);
			// Das aktive Element, falls vorhanden. Sonst ein leeres Array
			$oSystemElement = $oSystemElementsRepository->findBy($aCriteria);
			// Wenn ein aktives Element existiet, dann wird das Bundle berücksichtigt
			if (!empty($oSystemElement)) {
				// Das Array mit den Aktiven Bundlenamen um den aktiven Bundlenamen erweitern
				$aActiveBundleNames[] = $sBundleName;
			}
		}

		return $aActiveBundleNames;*/
	}

	/**
	 * <p>
	 * Fügt einem Array den Corebundlenamen hinzu, falls er nicht schon
	 * vorhanden ist.
	 * </p>
	 * @param array $aActiveBundleNames [Per Referenz] <p>
	 * In diesem Array soll der Corebundlename ergänzt werden.
	 * </p>
	 */
	/*private function _addCoreBundle(&$aActiveBundleNames){
		// Prüfe, ob Corebundlename im Array ist. Wenn nicht, dann hinzufügen.
		if (!in_array($this->_sCoreBundleName, $aActiveBundleNames)) {
			// Coreebundlename zum Array hinzufügen
			$aActiveBundleNames[] = $this->_sCoreBundleName;
		}
	}*/

}