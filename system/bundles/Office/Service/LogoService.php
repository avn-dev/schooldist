<?php

namespace Office\Service;

use Office\Interfaces\LogoInterface;

class LogoService {

	/**
	 * Gibt den relativen Webpfad des Logos zurück.
	 * 
	 * @param Object $oEntity	Diese Entität <b>muss</b> das Attribut 
	 * 							"<b>logo_extension</b>" enthalten.
	 */
	public function getWebPath(LogoInterface $oEntity) {

		$sExtension = $oEntity->logo_extension;
		if ($sExtension === null) {
			return null;
		}

		$sName = $this->_getName($oEntity);
		$sWebPath = '/' . $oEntity->getLogoWebDir() . $sName;

		return $sWebPath;
	}

	/**
	 * Speichert ein Logo.
	 * Lädt dabei das Logo auf dem Server hoch und aktualisiert das Entity.
	 * 
	 * @param Object $oEntity	Diese Entität <b>muss</b> das Attribut 
	 * 							"<b>logo_extension</b>" enthalten.
	 * @param Array $aLogo Die Informationen über das zu speichernde Logo.
	 */
	public function save(LogoInterface $oEntity, $aLogo) {
	
		// Die Dateinamenserweiterung des Logos abhängig vom Typ (z.B. Typ: 'image/png' Endung: png')
		$sLogoName = $aLogo['name'];
		$sLogoExtension = substr($sLogoName, (strrpos($sLogoName, '.') + 1));

		// Wenn kein Bild hochgeladen werden soll.
		if (!$sLogoExtension) {
			return;
		}

		// Wenn die Dateinamenserweiterung des alten Logos anders als die des 
		// neuen Logos und wenn ein altes Logo existiert, dann lösche das alte Logo.
		if(
			$oEntity->logo_extension !== $sLogoExtension &&
			$oEntity->logo_extension !== null
		) {
			$this->_delete($oEntity);
		}

		// Entität aktualisieren
		$this->_updateEntity($oEntity, $sLogoExtension);

		$sAbsolutePath = $this->_getAbsolutePath($oEntity);
		$sTempLogoName = $aLogo['tmp_name'];
		$bSuccess = move_uploaded_file($sTempLogoName, $sAbsolutePath);
		
		return $bSuccess;
	}

	/**
	 * Löscht das Logo der Entität vom Server und aktualisiert die Entität.
	 * 
	 * @param type $oEntity
	 */
	public function delete(LogoInterface $oEntity) {
		// Logo löschen
		$this->_delete($oEntity);
		// Entität akutalisieren
		$this->_updateEntity($oEntity, null);
	}

	/**
	 * Löscht ein Logo. Der Name des Logos ist zu übergeben.
	 * 
	 * @param Object $oEntity	Diese Entität <b>muss</b> das Attribut 
	 * 							"<b>logo_extension</b>" enthalten.
	 * @return boolean	Wenn das Logo geslöscht wurde <b>TRUE</b>, sonst
	 * 					<b>FALSE</b>
	 */
	private function _delete(LogoInterface $oEntity) {
		$bDeleted = false;

		$sAbsolutePath = $this->_getAbsolutePath($oEntity);
		if (file_exists($sAbsolutePath)) {
			unlink($sAbsolutePath);
			$bDeleted = true;
		}
		return $bDeleted;
	}

	/**
	 * 
	 * @param Object $oEntity	Diese Entität <b>muss</b> das Attribut 
	 * 							"<b>logo_extension</b>" enthalten.
	 * @return string	Der Name des Logos.<br />
	 * 					<b>Beispiel: "Test.jpeg"</b>
	 */
	private function _getName(LogoInterface $oEntity) {
		$sLogoExtenstion = $oEntity->logo_extension;

		$sEntityId = (string) $oEntity->id;
		$sLogoName = $sEntityId . '.' . $sLogoExtenstion;

		return $sLogoName;
	}

	/**
	 * 
	 * @param Object $oEntity	Diese Entität <b>muss</b> das Attribut 
	 * 							"<b>logo_extension</b>" enthalten.
	 * @return string	Der absolute Pfad zum Logo.<br />
	 * 					<b>Beispiel: "/var/www/framework/media/office/customers/logos/Test.jpeg"</b>
	 */
	private function _getAbsolutePath(LogoInterface $oEntity) {

		$sRootDir = \Util::getDocumentRoot() . $oEntity->getLogoWebDir();
		\Util::checkDir($sRootDir);

		$sLogoName = $this->_getName($oEntity);
		$sAbsolutePath = $sRootDir . $sLogoName;

		return $sAbsolutePath;
	}

	/**
	 * Aktualisiert das Attribut logo_extension der Entity.
	 * 
	 * @param String $sExtension Die neue Dateinamenserweiterung
	 */
	private function _updateEntity(LogoInterface $oEntity, $sExtension) {
		$oEntity->logo_extension = $sExtension;
		$oEntity->save();
	}

}