<?php

namespace TcCache\Handler;

abstract class AbstractCacheHandler {
    /**
     * Verzeichnis, in das die Cache-Dateien gespeichert werden
     *
     * @var string
     */
    protected $sSessionDir = 'storage/cache/';
    /**
     * Name des Unterverzeichnisses innerhalb des Session-Verzeichnisses
     *
     * @var string
     */
    protected $sSessionSubDir = '';
    /**
     * Gibt an ob die ID mit in den Dateinamen geschrieben werden soll
     *
     * @var bool
     */
    protected $bIDInFileName = true;
    /**
     * Gibt an, nach welcher Zeit die Session-Dateien gelöscht werden sollen wenn sie nicht mehr
     * benutzt wurden
     *
     * @var int
     */
    protected $iExpiration = 0;
    /**
     * die Sprache, zu der der Cache gespeichert wird (de als standard )
     *
     * @var string
     */
    protected $sLanguage = "de";

    /**
     * die Sprache, zu der der Cache gespeichert wird (de als standard )
     *
     * @var string
     */
    protected $bCacheLanguage = false;

	protected $sCacheKey;

	/**
     * Konstruktor
     *
     * - prüft das Verzeichnis der Cache Dateien
     */
    public function __construct() {
        $this->checkSessionDir();
    }

    /**
     * prüft das Verzeichnis, in dem die Preislisten serialisiert abgespeichert werden
     */
    protected function checkSessionDir() {
        $sSessionDir = $this->getSessionDirectory();

        if(!is_dir($sSessionDir)) {
            // Verzeichnis ggf. anlegen und chmod setzen
            \Util::checkDir($sSessionDir);
        }
    }

	/**
     * Liefert das Verzeichnis, in dem die Registrierungsobjekte serialisiert abgespeichert werden
     *
     * @param bool $bWithRootDir
     * @return string
     */
    public function getSessionDirectory($bWithRootDir = true) {

        $sSessionDir = $this->sSessionDir . $this->sSessionSubDir;

        if($bWithRootDir) {
            return \Util::getDocumentRoot() . $sSessionDir;
        }

        return $sSessionDir;
    }

    /**
     * @param int $iExpiration
     */
    public function setExpirationTime($iExpiration) {
        $this->iExpiration = (int) $iExpiration;
    }

    /**
     * Generiert den Dateinamen für eine Session-Datei
     *
     * @param string $sRegistrationKey
     * @return string
     */
    public function buildFilename($sCacheKey) {

        $sSessionDir = $this->getSessionDirectory() . '/';

        $sSessionDir .= $sCacheKey . '.txt';

        return $sSessionDir;
    }

    /**
     * Löscht alle Cache Dateien
     *
     * @return boolean|array
     */
   public function garbageCollector() {

		if($this->iExpiration === 0) {
			return false;
		}
		
		$this->checkSessionDir();
		
		$aSessionFiles = $this->getFiles();
		$iTime = time();

		$aDeletedFiles = [];
		foreach($aSessionFiles as $sFile) {
			if (
				is_file($sFile) &&
				$iTime - filemtime($sFile) >= $this->iExpiration
			) {
				$aDeletedFiles[] = $sFile;
				unlink($sFile);
			}
		}
				
		return $aDeletedFiles;
	}

	public function clearCache() {
		
		$aSessionFiles = $this->getFiles();

		$aDeletedFiles = [];
		foreach($aSessionFiles as $sFile) {
			if (is_file($sFile)) {
				$aDeletedFiles[] = $sFile;
				unlink($sFile);
			}
		}
				
		return $aDeletedFiles;
	}
	
    /**
     * Liefert die Dateien aus dem Verzeichnis. Je nach $bSessionInFileName können hier andere
     * Dateien zurückgeliefert werden
     *
     * @return array
     */
    protected function getFiles() {
        return glob($this->buildFilename('*'));
    }

	public function getCache($sSmartyCacheId) {

		$sFileName = $this->buildFilename($sSmartyCacheId);
		
		if(is_file($sFileName)) {
			$sContent = file_get_contents($sFileName);
			 return $sContent;
		}
		
	}

	public function deleteCache($sSmartyCacheId) {
		
		$sFileName = $this->buildFilename($sSmartyCacheId);
		
		if(is_file($sFileName)) {
			unlink($sFileName);
		}

	}
	
	public function writeCache($sSmartyCacheId, $sContent) {

		$sFileName = $this->buildFilename($sSmartyCacheId);
		
        $mWrite = file_put_contents($sFileName, $sContent);

        if($mWrite === false) {
            throw new \RuntimeException('Unable to add cache content');
        }

		return $mWrite;
	}
	
}