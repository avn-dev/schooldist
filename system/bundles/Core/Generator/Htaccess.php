<?php

namespace Core\Generator;

use System;

class Htaccess {

	private $aErrors = array();

	private function createBackup() {
		
		$sPath = $this->getFilename();
		
		$sTarget = \Util::getDocumentRoot().'backup/'.date('YmdHis').'.htaccess';

		$bCopy = copy($sPath, $sTarget);
		
		return $bCopy;
	}

	/**
	 * Legt ein Backup der aktuellen .htaccess an und generiert sie .htaccess
	 * @return boolean
	 */
	public function run() {

		$bBackup = $this->createBackup();
		
		if($bBackup !== true) {
			$this->aErrors[] = "backup_failed";
			return false;
		}
		
		$this->writeMaster();
		
		$this->writeOthers();
		
		$oLog = \Log::getLogger();
		
		if(empty($this->aErrors)) {
			$oLog->addInfo('.htaccess files generated successfully');
			return true;
		} else {
			$oLog->addError('Generating .htaccess files failed', $this->aErrors);
			return false;
		}

	}

	public function getErrors() {
		return $this->aErrors;
	}

	private function writeOthers() {
		
		$sContent = '
Deny from all
	';

		$sPath = \Util::getDocumentRoot().'backup/.htaccess';
		$mWrite = file_put_contents($sPath, $sContent);

		if($mWrite === false) {
			$this->aErrors[] = 'backup_failed';
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sContent = '
# Zugriff auf alle PHP Dateien unterbinden
<Files ~ "\.(php3|php4|php5|phtml|php|html|mod|inc)$">
Deny from all
</Files>
	';

		$sPath = \Util::getDocumentRoot().'media/.htaccess';
		$mWrite = file_put_contents($sPath, $sContent);

		if($mWrite === false) {
			$this->aErrors[] = "media_failed";
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sContent = '';

		$sPath = \Util::getDocumentRoot().'storage/.htaccess';
		$mWrite = file_put_contents($sPath, $sContent);

		if($mWrite === false) {
			$this->aErrors[] = "secure_failed";
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sContent = '
Deny from all
	';

		$sPath = \Util::getDocumentRoot().'system/includes/.htaccess';
		$mWrite = file_put_contents($sPath, $sContent);

		if($mWrite === false) {
			$this->aErrors[] = "includes_failed";
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

//		$sContent = '
//Deny from all
//	';
//
//		$sPath = \Util::getDocumentRoot().'system/smarty/.htaccess';
//		$mWrite = file_put_contents($sPath, $sContent);
//
//		if($mWrite === false) {
//			$this->aErrors[] = "smarty_failed";
//		}

	}
	
	private function writeMaster() {
		
		switch(System::d('provider')) {
			case '1und1':
				$sAddType = '
AddType x-mapp-php5 .php .html .mod .inc
AddHandler x-mapp-php5 .php .html .mod .inc';
				break;
			case 'individual':
				$sAddType = '
'.System::d('htaccess_individual_addtype');
				break;
			case 'plan-i':
			default:
				$sAddType = '
AddType application/x-httpd-php .php .html .mod .inc';
				break;
		}

		$sContent = '
<Files *.mod>
	Deny from all
</Files>

'.$sAddType.'

AddType image/x-icon .ico

AddDefaultCharset UTF-8

Options -Indexes

RewriteEngine On
RewriteBase /

RewriteRule ^storage/(.*)$ /secure.php?f=$1&t=show [L,QSA]
RewriteRule ^index.html$ /index.php?index.html [L,QSA]
RewriteRule ^robots.txt$ /robots.php [L]
RewriteRule ^favicon.ico$ /favicon.php [L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php [L,QSA]

		';

		$sIndividual = System::d('individual_htaccess');
		if(!empty($sIndividual)) {
			$sContent .= $sIndividual;
		}

		$sPath = $this->getFilename();
		$mWrite = file_put_contents($sPath, $sContent);

		if($mWrite === false) {
			$this->aErrors[] = "master_failed";
		}

	}
	
	private function getFilename() {
		$sPath = \Util::getDocumentRoot().'.htaccess';
		return $sPath;
	}
	
}
