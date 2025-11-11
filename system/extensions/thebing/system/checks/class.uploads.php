<?
/*
 * Importiert die PDF/Mainuploads in die neue Struktur
 */
class Ext_Thebing_System_Checks_Uploads extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'File Upload Import';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'File Upload Import';
		return $sDescription;
	}

	/*
	 * Check importiert die PDF/Mainuploads in die neue Struktur
	 */
	public function executeCheck(){
		global $user_data, $_VARS;

		Ext_Thebing_Util::backupTable('kolumbus_upload');
		Ext_Thebing_Util::backupTable('kolumbus_upload_languages');

		$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_upload` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL default '1',
			  `school_id` int(11) NOT NULL default '0',
			  `client_id` int(11) NOT NULL default '0',
			  `user_id` int(11) NOT NULL default '0',
			  `category_id` int(11) NOT NULL default '0',
			  `description` varchar(255) NOT NULL,
			  `filename` varchar(255) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `active` (`active`),
			  KEY `school_id` (`school_id`),
			  KEY `client_id` (`client_id`),
			  KEY `user_id` (`user_id`),
			  KEY `category_id` (`category_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
		DB::executeQuery($sSql);

		$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_upload_languages` (
			  `upload_id` int(11) NOT NULL default '0',
			  `language` varchar(50) NOT NULL,
			  KEY `upload_id` (`upload_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		DB::executeQuery($sSql);

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		// Filetypen
		$aTypes = array();
		$aTypes[0] = 'pdf/';
		$aTypes[1] = 'signatur/';
		$aTypes[2] = 'contact/';
		$aTypes[3] = 'communication/';
		$aTypes[4] = 'pdf_attachments/';

		// Alle Schulen holen
		$sSql = "SELECT
						`id`
					FROM
						`customer_db_2`
					WHERE
						`active` = 1
				";

		$aSchools = DB::getQueryData($sSql);

		$aFileData = array();
		// START Schulen durchgehen
			foreach((array)$aSchools as $aSchool){
				$oSchool = new Ext_Thebing_School(NULL, $aSchool['id'], true);
				// Schulsprachen
				$aLanguages = $oSchool->getLanguageList();

				$aLanguages[(string)''] = 'all';
				ksort($aLanguages);


				// Alle Filetypen
				foreach((array)$aTypes as $iKey => $sPathPart){

					foreach((array)$aLanguages as $sLangKey => $sLang){

						$aOldFiles = $this->getSchoolFiles($iKey, $sLangKey, false, true, $oSchool->id);
						$aFileData[$aSchool['id']][$iKey][$sLang] = $aOldFiles;
					}
				}

			}
		// ENDE

		// START Daten neu ablegen
			$aError = array();

			foreach((array)$aFileData as $iSchool => $aTypeData){

				$oSchool = new Ext_Thebing_School(NULL, $iSchool, true);

				// Alle Sprachen
				$aLanguages = $oSchool->getLanguageList();
				$aLanguages = array_keys($aLanguages);

				foreach((array)$aTypeData as $iType => $aLangData){

					foreach((array)$aLangData as $sLang => $aOldFiles){

						foreach((array)$aOldFiles as $iFileKey => $aOldFile){

							if(!empty($aOldFile['file'])){

								$bError = false;


								$oFile = new Ext_Thebing_Upload_File(0);
								$oFile->active = 1;
								$oFile->school_id = (int)$iSchool;
								$oFile->client_id = (int)$oSchool->getClientId();
								$oFile->category_id = ($iType + 1); // Neue Reihenfolge
								$oFile->description = $aOldFile['file']; // darf nicht leer sein deswegen erst mal alt...
								$oFile->filename = $aOldFile['file'];

								// Sprachen
								if($sLang == 'all'){
									$oFile->languages = $aLanguages;
								}else{
									$oFile->languages = array($sLang);
								}

								$oFile->save();

								$iId = $oFile->id;
								$sNewName = $iId . '_' . $aOldFile['file'];

								$oFile->description = $sNewName; // jetzt neu... :)
								$oFile->filename = $sNewName;

								// Daten verschieben

								// Prüfen ob Ordner existiert
								$sPath = $oSchool->getSchoolFileDir();
								$sPath .= '/uploads';

								if(!is_dir($sPath)){
									 mkdir($sPath, 0777);
									 chmod($sPath, 0777);
								}

								if(is_dir($sPath)){
									if(is_file($aOldFile['path'])){
										if(copy($aOldFile['path'], $sPath . '/' . $sNewName)){
											if(!is_file($sPath . '/' . $sNewName)){
												$bError = true;
												$aError[] = 'Neue Datei nicht vorhanden: ' . $sPath . '/' . $aOldFile['file'];
											}
										}else{
											$bError = true;
											$aError[] = 'Alte Datei nicht kopieren: ' . $aOldFile['path'];
										}
									}else{
										$bError = true;
										$aError[] = 'Alte Datei nicht vorhanden: ' . $sPath;
									}
								}else{
									$bError = true;
									$aError[] = 'Pfad nicht erzeugen: ' . $sPath;
								}

								if($bError){
									$oFile->active = 0;
								}

								$oFile->save();
							}
						}
					}
				}
			}
		// ENDE

		if(!empty($aError)){
			$oMail = new WDMail();
			$oMail->subject = "error";
			$oMail->html = print_r($aError,1);
			$oMail->send(array('developer@thebing.com'));
		}

		// Username löschen
		$sSql = "UPDATE
						`kolumbus_upload`
					SET
						`user_id` = 0
				";
		DB::executeQuery($sSql);
		

		## START PDF Attachments umschreiben auf neue Struktur

			$sSql = "SELECT
							*
						FROM
							`kolumbus_pdf_templates_options`
						WHERE
							`option` = 'attachments' AND
							`value` != ''
			";
			// serialisiert gespeicherte Daten die umgeschrieben werden sollen
			$aOptionData = DB::getQueryData($sSql);

			foreach((array)$aOptionData as $aData){
				$aAttachments = unserialize($aData['value']) ;

				foreach((array)$aAttachments as $iAttachment){
					if(
						is_numeric($iAttachment) &&
						$iAttachment > 0
					){
						$sSql = "INSERT INTO
									`kolumbus_pdf_templates_options_attachment`
								SET
									`option_id` = :option_id,
									`file_id`	= :file_id
							";

						$aSql = array();
						$aSql['option_id'] = (int)$aData['id'];
						$aSql['file_id'] = (int)$iAttachment;
						DB::executePreparedQuery($sSql, $aSql);
					}

				}
			}

		return true;
	}
	
	/*
	 * aus oSchool rauskopiert da die bereits auf "neu" gemacht wird.
	 */
	public function getSchoolFiles($iType, $sLang = '', $bOnlySecurePath = false, $bNoGlobalFiles = false, $iSchool) {

		$oSchool = new Ext_Thebing_School(NULL, $iSchool, true);

		// ALT MUSS FÜR DEN IMPORT entghalten bleiben!!!!! 08.11.10
		$aList = array();
		switch ($iType){

			case 0:
				$SubOrdner = "pdf/";
				break;

			case 1:
				$SubOrdner = "signatur/";
				break;

			case 2:
				$SubOrdner = "contact/";
				break;

			case 3:
				$SubOrdner = "communication/";
				break;

			case 4:
				$SubOrdner = "pdf_attachments/";
				break;

		}

		if(!empty($sLang)){
			$SubOrdner .= $sLang.'/';
		}

		$ordner = $oSchool->getSchoolFileDir(true, true);

		$ordner = $ordner.'/'.$SubOrdner;

		if (is_dir($ordner)) {
			$handle = opendir($ordner);
			$i = 1;
			while ($file = readdir ($handle)) {
			    if($file != "." && $file != "..") {
			        if(!is_dir($ordner.$file)) {

			        	$sPath = $ordner.$file;

			        	if($bOnlySecurePath){
			        		$sPath = str_replace($oSchool->getSchoolFileDir(true, true),'', $sPath);
			        	}
			        	$aList[$i]['path'] = $sPath;
			        	$aList[$i]['file'] = $file;
			        	$i++;
			        }
	 	 		}

			}
			closedir($handle);
		}

		if(!empty($sLang) && !$bNoGlobalFiles){
			$aAllLangFiles = $this->getSchoolFiles($iType, '', $bOnlySecurePath, false, $oSchool->id);
			$aList = array_merge($aAllLangFiles, $aList);
		}

		return $aList;

	}

}
