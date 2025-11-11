<?php

namespace Core\Helper;

use Core\Service\BundleService;

/**
 * Diese Klasse sammelt Dateien ein und gibt diese zurück.
 */
class FileCollector {

	protected $sFilePattern;
	protected $sFileClass;
	
	/**
	 * <p>
	 * Diese Methode sammelt alle "composer.json"-Dateien ein.
	 * Damit solch eine Composer-Datei eingesammelt werden kann, muss sich die
	 * Datei im Unterverzeichnis "/Resources/config/" eines
	 * Bundles("/system/bundles/") befindet und "composer.json" heißen.
	 * </p>
	 * @return \Core\Helper\Routing\AbstractFile[]
	 * Entweder ein Array gefüllt mit Core\Helper\Composer\File-Objekten
	 * oder ein leeres Array, wenn keine "composer.json"-Dateien gefunden wurden.
	 * </p>
	 */
	public function collectAllFileParts(){
		// Das Pattern um alle "composer.json"-Dateiein eines aktiven Bundles zu suchen.
		$sPattern = $this->_getPatternForActiveComposerFiles();
		// Alle "composer.json"-Dateien, auf die das Pattern zutrifft.
		$aComposerFileNames = $this->sortFiles(glob($sPattern, GLOB_BRACE));

		// Initialisiert ein leeres Array, um es mit Core\Helper\Composer\File-Objekten zu füllen
		$aFiles = array();
		// Erweitert das array "$aFiles" für jede gefundene "composer.json-Datei
		// um ein Core\Helper\Composer\File-Objekt
		foreach ($aComposerFileNames as $sComposerFileName) {

			// Workraound dafür, dass auf Installationen niemals alte Dateien gelöscht werden: PHP immer vor YML
			// \Core\Helper\Config\FileCollector ist sonst so intellgent und parst beide Dateien
			if (
				strpos($sComposerFileName, '.yml', -4) !== false &&
				file_exists(substr($sComposerFileName, 0, -4).'.php')
			) {
				continue;
			}

			// Instanziiere ein Core\Helper\Composer\File-Objekt
			$oFile = new $this->sFileClass($sComposerFileName);
			// Parse die Datei um die Requirements im Objekt zu füllen.
			$oFile->parseContent();
			// Erweitere das Array um das Core\Helper\Composer\File-Objekt
			array_push($aFiles, $oFile);
		}

		// Gib alle gefundenen Dateien zurück.
		return $aFiles;
	}

	/**
	 * <p>
	 * Die Methode gibt ein Pattern zurück, um alle aktiven
	 * "composer.json"-Dateien zu suchen.
	 * </p>
	 * @return string <p>
	 * Ein Pattern, um nach den benötigten "composer.json"-Dateien zu suchen.
	 * </p>
	 */
	private function _getPatternForActiveComposerFiles(){
		// Inztanziiert ein BundleService
		$oBundleService = new BundleService();
		// Alle im Projekt vorhandenen Bundle
		$aActiveBundleNames = $oBundleService->getActiveBundleNames();
		// Der Teil der Bundle für das Pattern
		$sBundlePatternPart = '{' . implode(',', $aActiveBundleNames) . '}';
		// Das Muster, um die composer-Dateien zu finden. Dabei trifft das Pattern
		// auf alle "composer.json"-Dateien zu, die sich im Untervezeichnis
		// "/Resources/config/" eines aktiven Bundles("/system/bundles/") befindet.
		$sPattern = \Util::getDocumentRoot() . "system/bundles/" . $sBundlePatternPart . $this->sFilePattern;

		return $sPattern;
	}

	protected function sortFiles(array $fileNames): array {
		return $fileNames;
	}
}