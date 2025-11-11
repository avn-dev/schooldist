<?php

namespace TcCache\Handler;

use TcCache\Handler\AbstractCacheHandler;

class RegistrationCache extends AbstractCacheHandler {

	/**
	 * Verzeichnis, in das die Session-Dateien gespeichert werden
	 * 
	 * @var string 
	 */
	protected $sSessionDir = 'storage/ta/frontend/';	
	/**
	 * Name des Unterverzeichnisses innerhalb des Session-Verzeichnisses
	 * 
	 * @var string 
	 */
	protected $sSessionSubDir = 'registration';	
	/**
	 * Gibt an ob die Session-ID mit in den Dateinamen geschrieben werden soll
	 * 
	 * @var bool
	 */
	protected $bSessionInFileName = true;
	/**
	 * Gibt an, nach welcher Zeit die Session-Dateien gelöscht werden sollen wenn sie nicht mehr
	 * benutzt wurden
	 * 
	 * @var int
	 */
	protected $iExpiration = 0;
	/**
	 * Gibt an, ob die Session-Dateien in ein temporäres Verzeichnis geschrieben werden oder in das 
	 * generelle Unterverzeichnis
	 * 
	 * @var bool 
	 */
	protected $bTmpSessionDir = false;

	protected $sSessionId;

    /**
     * Generiert einen Identifikationskey für ein Registrierungsobjekt
     *
     * @param int $iChars
     * @return string
     */
    protected function generateRegistrationKey($iChars = 8) {
        return \Util::generateRandomString($iChars);
    }
	
    /**
     * @param bool $bSessionInFileName
     */
    public function setSessionInFileName($bSessionInFileName) {
        $this->bSessionInFileName = $bSessionInFileName;
    }

	public function setSessionId($sSessionId) {
		$this->sSessionId = $sSessionId;
	}
	
	public function activateTmpDir() {
		$this->bTmpSessionDir = true;
	}
	
	public function getSessionDirectory($bWithRootDir = true) {
		
		$bSessionDirectory = parent::getSessionDirectory($bWithRootDir);

        if($this->bTmpSessionDir === true) {
            $bSessionDirectory .= '_tmp';
        }

		return $bSessionDirectory;
	}
	
    public function writeToSession(\Ext_TA_Frontend_Abstract_Form $oRegistration) {

        if($this->bSessionInFileName === true) {
            $iChars = 8;
        } else {
            $iChars = 32;
        }

        $sRegistrationKey = $this->generateRegistrationKey($iChars);

        // den Key innerhalb des Warenkorbes merken, damit dieses Objekt nicht erneut hinzugefügt werden kann
        $oRegistration->setSessionFileKey($sRegistrationKey);

        $sContent = serialize($oRegistration);

        $this->writeCache($sRegistrationKey, $sContent);

        return $sRegistrationKey;
    }

    public function updateSession(\Ext_TA_Frontend_Abstract_Form $oRegistration, $sRegistrationKey) {
        
		$sContent = serialize($oRegistration);
		
        $this->writeCache($sRegistrationKey, $sContent);

        return $sRegistrationKey;
    }

    /**
     * Liefert ein Registrierungsobjekt anhand eines Identifikationskeys
     *
     * @param string $sRegistrationKey
     * @return Ext_TA_Frontend_Abstract_Form|null
     */
    public function getFromSession($sRegistrationKey) {

		$this->setSessionId(null);
		
        $sFileName = $this->buildFilename($sRegistrationKey);

        return $this->getRegistrationObjectFromFile($sFileName);
    }

    public function findRegistrationSessionFile($sRegistrationKey, $sSessionId = null) {

		$this->setSessionId($sSessionId);
		
        $sFileName = $this->buildFilename($sRegistrationKey);

        return $this->getRegistrationObjectFromFile($sFileName);

    }

    protected function getRegistrationObjectFromFile($sFile) {

        if(is_file($sFile)) {

            // Zugriffszeitpunkt der Session-Datei aktualisieren damit diese nicht ggf. durch den GarbageCollector gelöscht wird
            touch($sFile);

            $sContent = file_get_contents($sFile);
			
			try {
				// Objekte werden serialisiert in die Session-Dateien geschrieben
				$oRegistration = unserialize($sContent);
			} catch(\Throwable $e) {}

            if($oRegistration instanceof \Ext_TA_Frontend_Abstract_Form) {
                return $oRegistration;
            }
        }

        return null;
    }

    /**
     * Generiert den Dateinamen für eine Session-Datei
     *
     * @param string $sRegistrationKey
     * @return string
     */
    public function buildFilename($sCacheKey) {

        $sFileName = $this->getSessionDirectory() . '/';

		if(!empty($this->sSessionId)) {
			$sFileName .= $this->sSessionId.'_';
		} else if($this->bSessionInFileName === true) {
			$sFileName .= session_id().'_';
		}

        $sFileName .= $sCacheKey . '.txt';

		return $sFileName;
    }

}