<?php
/**
 * Dieser Check prüft, ob Memcache-Daten vorhanden sind und wenn nicht,
 * 	dann werden sie in die Datenbank eingetragen, solange die momentane PHP-Installation
 * 	die Voraussetzungen enthält.
 *
 * Redmine Ticket #2174
 */
class Ext_TC_System_Checks_CheckMemcache extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Memcache Check';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Check if memcache is available. If so, the system is going to get a speed boost.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {

		$aMessages = array();
		$bContinue = true;

		$sHost = System::d('memcache_host');
		$sPort = System::d('memcache_port');

		// --- Nachschauen, ob Memcache Konfiguration vorhanden ist
		if(!empty($sHost)) {
			$bHostIsMissing = false;
			$aMessages[] = 'memcache_host is already defined: '.$sHost;
		} else {
			$bHostIsMissing = true;
			$sHost = 'localhost';
			$aMessages[] = 'memcache_host is missing. Set default to: '.$sHost;
		}

		if(!empty($sPort)) {
			$bPortIsMissing = false;
			$aMessages[] = 'memcache_port is already defined: '.$sPort;
		} else {
			$bPortIsMissing = true;
			$sPort = '11211';
			$aMessages[] = 'memcache_port is missing. Set default to: '.$sPort;
		}

		// --- Wenn beide Daten vorhanden, Check beenden ---
		if(!$bHostIsMissing && !$bPortIsMissing) {
			$aMessages[] = 'Memcache config data is already set. Skip check.';
			$bContinue = false;
		}

		// --- Prüfen, ob Memcache Extension geladen ist ---
		if($bContinue) {
			if(extension_loaded('memcached')) {
				$aMessages[] = 'Memcache extension is loaded.';
			} else {
				$bContinue = false;
				$aMessages[] = 'Memcache extension is not loaded. Skip check.';
			}
		}

		// --- Versuchen, ob man sich mit dem Memcache-Server denn auch verbinden kann ---
		if($bContinue) {

			/** @var $mMemcache bool|resource */
			try {

				$mMemcache = new Memcached();
				$mMemcache->addServer($sHost, $sPort);

			} catch (\Throwable $e) {
				$mMemcache = null;
			}

			if(
				$mMemcache &&
				$mMemcache->getVersion() === false
			) {
				$bContinue = false;
				$aMessages[] = 'Memcache connection failed!';
			} else {
				$aMessages[] = 'Memcache connection could be established.';
			}
		}

		// --- Memcache-Daten setzen, da alles erfolgreich war ---
		if($bContinue) {

			if($bHostIsMissing) {
				System::s('memcache_host', $sHost);
			}
			if($bPortIsMissing) {
				System::s('memcache_port', $sPort);
			}
		}

		if(
			$bContinue || (
				!$bHostIsMissing &&
				!$bPortIsMissing
			)
		) {
			$aMessages[] = 'Memcache is activated';
		} else {
			$aMessages[] = 'Memcache is not activated!';
		}

		Ext_TC_Util::reportMessage('(TC) Update: Memcache Check - '.end($aMessages).' ('.$_SERVER['HTTP_HOST'].')', $aMessages);

		__pout($aMessages);

		return true;

	}

}
