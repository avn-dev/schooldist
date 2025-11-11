<?php

class Ext_TC_Systeminformation {
	
	/**
	 * Array mit den Fehlermeldung
	 * @var array 
	 */
	public $aNotifications = array();
	
	/**
	 * Zeitspanne, für die die Daten gecached werden sollen (3600 => 1h)
	 * @var int 
	 */
	protected $_iExpiration = 3600;
	
	/**
	 * gibt ein Array mit allen Informationen zurück
	 * @param bool $bShowBackup
	 * @return array
	 */
	public function getInformation($bShowBackup = false) {
	
		$aReturn = array();
		
		// Arbeitsspeicher
		$aInternalMemory = $this->_getInternalMemory();

		if($aInternalMemory !== null) {
			// Memory
			$aReturn['internal_memory_used']['current']	= $aInternalMemory['memory']['used'];
			$aReturn['internal_memory_used']['total']	= $aInternalMemory['memory']['total'];
			$aReturn['internal_memory_used']['type']	= 'byte';

			//CPU
			$aReturn['cpu']['total']	= 100;
			$aReturn['cpu']['current']	= $aReturn['cpu']['total'] - $aInternalMemory['cpu']['idle'];
			$aReturn['cpu']['type']		= 'percent';

		}

		// Caching
		$sCachingEngine = WDCache::getCachingEngine();
		$aStats = WDCache::getStats();
		$aReturn['caching']['type'] = 'int';
		$aReturn['caching']['additional'] = $sCachingEngine;
		
		if($sCachingEngine == 'MySQL') {
			$this->aNotifications['caching'] = L10N::t('Bitte verwenden Sie Memcached als Caching-Engine!');
		}
		
		if(!empty($aStats)) {

			$iCurrentItems = 0;
			if(isset($aStats['curr_items'])) {
				$iCurrentItems = $aStats['curr_items'];
			} elseif(reset($aStats)['curr_items']) {
				$iCurrentItems = reset($aStats)['curr_items'];
			}
			
			$aReturn['caching']['current'] = $iCurrentItems;	
		}		
		
		// Datenbank
		$iDatabaseMemory = $this->_getDatabaseMemory();
		$aReturn['database_memory']['current']  = $iDatabaseMemory;
		$aReturn['database_memory']['type']		= 'byte';
		
		// Dateien
		$aFileData = $this->_getFileData();
		$aReturn['files']['current']	= $aFileData['files'];
		$aReturn['files']['type']		= 'byte';
		
		$aReturn['files_other']	['current']		= $aFileData['files_other'];
		$aReturn['files_other']['type']			= 'byte';
		
		$aReturn['files_media']['current']		= $aFileData['files_media'];
		$aReturn['files_media']['type']			= 'byte';
		
		// Backup
		if($bShowBackup) {
			$aReturn['files_backup']['current'] = $aFileData['files_backup'];
			$aReturn['files_backup']['type']	= 'byte';
		}
		
		// PDF
		$iAveragePdfSize = $this->_getAveragePdfSize();
		$aReturn['average_pdf_size']['current'] = $iAveragePdfSize;
		$aReturn['average_pdf_size']['type']	= 'byte';
		
		
		return $aReturn;
	}
	
	/**
	 * liest die Speicherauslastung aus
	 * @return array
	 */
	protected function _getInternalMemory() {
		
		$oUpdate = new Update;
		$sReturn = $oUpdate->executeShellCommand('top -bn1');

		$aStats = null;
		
		if(!empty($sReturn)) {

			$bMatch = preg_match("/load average: ([0-9.]+), ([0-9.]+), ([0-9.]+).*?Cpu\(s\):\s+([0-9.]+)[ %]us,\s+([0-9.]+)[ %]sy,\s+([0-9.]+)[ %]ni,\s+([0-9.]+)[ %]id,\s+([0-9.]+)[ %]wa.*?Mem:\s+([0-9.]+)k? total,\s+([0-9.]+)k? used,\s+([0-9.]+)k? free/is", $sReturn, $aMatch); //

			if($bMatch) {
				
				$aStats = array();
				
				// 1, 5, 15 Minutes			
				$aStats['load'][1] = $aMatch[1];
				$aStats['load'][5] = $aMatch[2];
				$aStats['load'][15] = $aMatch[3];

				$aStats['cpu']['user'] = $aMatch[4];
				$aStats['cpu']['system'] = $aMatch[5];
				$aStats['cpu']['idle'] = $aMatch[7];
				$aStats['cpu']['iowait'] = $aMatch[8];

				$aStats['memory']['total'] = $aMatch[9]*1024;
				$aStats['memory']['used'] = $aMatch[10]*1024;
				$aStats['memory']['free'] = $aMatch[11]*1024;
			}

		}
		
		return $aStats;
	}
	
	/**
	 * liest die Größe der Datenbank aus
	 * @global array $db_data
	 * @return int
	 */
	protected function _getDatabaseMemory() {
		global $db_data;
		
		$sCacheKey = 'system_database_memory';
		
		$iDbSize = WDCache::get($sCacheKey);
		
		if($iDbSize === null) {
		
			$sSql = "SHOW TABLE STATUS FROM `".$db_data['system']."`";

			$aData = DB::getQueryData($sSql);

			$iDbSize = 0;
			foreach($aData as $iKey => $aTable) {
				$iDbSize += $aTable["Data_length"] + $aTable["Index_length"]; 
			}
		
			WDCache::set($sCacheKey, $this->_iExpiration, $iDbSize);
		}
		
		// 100 GB
		if($iDbSize >= 107374182400) {
			$this->aNotifications['database_memory'] = L10N::t('Ihre Datenbank ist sehr groß, bitte prüfen Sie die Archivierungseinstellungen unter "Admin > Agentureinstellungen"');
		}			

		return $iDbSize;
	}
	
	/**
	 * liest den Speicherplatz für Dateien aus
	 * @return array
	 */
	protected function _getFileData() {
		
		$sCacheKey = 'system_file_data';
		
		$aReturn = WDCache::get($sCacheKey);
		
		if($aReturn === null) {
		
			ob_start();
			system('du -s -B 1 '.\Util::getDocumentRoot());
			$sReturn = ob_get_clean();

			ob_start();
			system('du -s -B 1 '.\Util::getDocumentRoot().'storage/');
			$sReturnMedia = ob_get_clean();

			ob_start();
			system('du -s -B 1 '.\Util::getDocumentRoot().'backup/');
			$sReturnBackup = ob_get_clean();

			preg_match("/^([0-9]*)\s/", $sReturn, $aMatches);
			$iFileSize = $aMatches[1];
			preg_match("/^([0-9]*)\s/", $sReturnMedia, $aMatchesMedia);
			$iFileSizeMedia = $aMatchesMedia[1];
			preg_match("/^([0-9]*)\s/", $sReturnBackup, $aMatchesBackup);
			$iFileSizeBackup = $aMatchesBackup[1];

			$aReturn = array(
				'files'			=> $iFileSize,
				'files_other'	=> ($iFileSize - $iFileSizeMedia - $iFileSizeBackup),
				'files_media'	=> $iFileSizeMedia,
				'files_backup'	=> $iFileSizeBackup
			);
		
			WDCache::set($sCacheKey, $this->_iExpiration, $aReturn);
		}
		
		return $aReturn;
	}
	
	/**
	 * errechnet die durchnittliche PDF-Größe
	 * @return int
	 */
	protected function _getAveragePdfSize() {
		
		$sCacheKey = 'system_average_pdf_size';
		
		$iAverageSize = WDCache::get($sCacheKey);
		
		if($iAverageSize === null) {
		
			ob_start();
			system('find '.\Util::getDocumentRoot().'storage/ -type f -mtime -14 -iname "*.pdf" -printf "%s %p\n"');
			$sReturn = ob_get_clean();

			$iAverageSize = 0;

			if(!empty($sReturn)) {

				$aLines = explode("\n", $sReturn);

				$iCount = 0;
				$iSize = 0;
				foreach($aLines as $sLine) {
					if(!empty($sLine)) {
						$aLine = preg_split("/\s+/", $sLine, 2);
						$iSize += $aLine[0];
						$iCount++;
					}
				}

				$iAverageSize = $iSize / $iCount;

			}
		
			WDCache::set($sCacheKey, $this->_iExpiration, $iAverageSize);
		}
		
		// 200 KB
		if($iAverageSize >= 204800) {
			$this->aNotifications['average_pdf_size'] = L10N::t('Ihre PDF-Dokumente sind zu groß. Bitte prüfen Sie die Größe Ihrer PDF-Hintergründe und die PDF-Layout-Einstellungen unter "Admin > PDF-Layouts". Wir empfehlen hier die Option "Font-Subsetting" zu aktivieren, um die PDF-Größe zu minimieren.');
		}
		
		return $iAverageSize;
	}
	
	/**
	 * Array mit den Bezeichnungen
	 * @return array
	 */
	public function getDescriptionMapping() {

		$aMapping = array(
			'internal_memory_total'	=> 'Arbeitsspeicher total',
			'internal_memory_used'	=> 'Arbeitsspeicher benutzt',
			'cpu'					=> 'CPU Auslastung',
			'database_memory'		=> 'Datenbank',
			'files'					=> 'Dateien insgesamt',
			'files_other'			=> 'Dateien sonstige',
			'files_media'			=> 'Dateien Mediaverzeichnis',
			'files_backup'			=> 'Dateien Backup',
			'average_pdf_size'		=> 'Durchschnittliche PDF-Größe',
			'caching' => 'Einträge im Cache',
		);
		
		return $aMapping;
	}

	public function getAutoImapSize() {

		$aAccounts = Ext_TC_Communication_Imap::getAccounts();
			
		$aReturn = array();
		// Konten durchlaufen und E-Mails abrufen
		foreach($aAccounts as $oAccount) {
			/* @var Ext_TC_Communication_Imap  $oAccount */
			
			$sError = null;
			
			try {
				$iMails = $oAccount->countMails();
			} catch (Exception $e) {
				$iMails = 0;
				$sError = L10N::t('E-Mail-Abruf nicht möglich').' ('.$e->getMessage().')';
			}

			$aReturn[] = array(
				'account' => (string)$oAccount,
				'mails' => $iMails,
				'error' => $sError
			);

		}
		
		return $aReturn;
	}

}
