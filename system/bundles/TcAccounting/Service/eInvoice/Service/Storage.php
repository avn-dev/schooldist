<?php

namespace TcAccounting\Service\eInvoice\Service;

use TcAccounting\Service\eInvoice\Service\File;

class Storage {
		
	public static function getDirectory() {
		
		$sDir = \Util::getDocumentRoot(false).'/storage/tc/accounting/einvoice';
		
		\Util::checkDir($sDir);
		
		return $sDir;
	}
	
	public static function getPathForType(string $sType) {
		
		$sDir = self::getDirectory().'/'.\Util::getCleanFilename($sType);
		
		\Util::checkDir($sDir);
		
		return $sDir;
	}
	
	public static function storeFile(File $oFile, $sFileName, $sContent) {
		
		$sDir = self::getPathForType($oFile->getType());
		
		$sFile = $sDir.'/'.$sFileName;
		
		file_put_contents($sFile, $sContent);
		
		@chmod($sFile, 0777);
		
		$oFile->setFile($sFile);
	}
	
	public static function backupFile(File $oFile) {
		
		$sBackupDir = self::getPathForType($oFile->getType()).'/backup';
		
		\Util::checkDir($sBackupDir);
		
		$sBaseName = basename($oFile->getFile());
		
		do {			
			$sNewFileName = \Util::generateRandomString(6).'_'.$sBaseName;			
		} while(file_exists($sBackupDir.'/'.$sBaseName));
		
		copy($oFile->getFile(), $sBackupDir.'/'.$sNewFileName);
		
		$oFile->setBackupFile($sBackupDir.'/'.$sNewFileName);
	}
	
}

