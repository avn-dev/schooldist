<?php

class Ext_Individualupdate {
	
	protected $_aValues = array();
	
	protected $_aDirectories = array();
	protected $_aFiles = array();
	protected $_iCurrentDepth = 0;
	protected $_sUpdatePassword = '';
	protected $_aEmailLog = array();
	protected $_sLastConnectionError = '';
	
	public function __construct() {

		set_time_limit(600);
		ini_set('memory_limit', '512M');
		
		$this->_aValues = array();
		
		$sSql = "
				SELECT
					`key`,
					`value`
				FROM
					individual_update
				WHERE
					active = 1
				";
		$aValues = DB::getQueryPairs($sSql);

		if($aValues['type'] == 'live') {
			$this->_aValues['type'] = 'live';
			$this->_aValues['url'] = 'http://';
			$this->_aValues['directories'] = array('');
			$this->_aValues['db_structure'] = array('');
			$this->_aValues['db_data'] = array('');
			$this->_aValues['page_templates'] = 0;
			$this->_aValues['styles'] = 0;
			$this->_aValues['extensions'] = 0;
			$this->_aValues['blocks'] = 0;
			$this->_aValues['imgbuilder'] = 0;
			$this->_aValues['global_checks'] = 0;
			$this->_aValues['last_update'] = time();
		} else {
			$this->_aValues['type'] = 'dev';
			$this->_aValues['password'] = '';
		}
		
		if(!empty($aValues)) {
			foreach((array)$aValues as $sKey=>$sValue) {
				if(array_key_exists($sKey, $this->_aValues)) {
					if(is_array($this->_aValues[$sKey])) {
						$sValue = json_decode($sValue);
					}
					$this->_aValues[$sKey] = $sValue;
				}
			}
		}
		
	}
	
	public static function getTypes() {
		
		$aTypes = array();
		$aTypes['dev'] = L10N::t('Entwicklungsserver', 'Erweiterungen » Individuelles Update');
		$aTypes['live'] = L10N::t('Liveserver', 'Erweiterungen » Individuelles Update');
		
		return $aTypes;
		
	}

	public function __get($sKey) {
		
		if(array_key_exists($sKey, $this->_aValues)) {
			return $this->_aValues[$sKey];
		}
		
	}

	public function __set($sKey, $mValue) {

		if(array_key_exists($sKey, $this->_aValues)) {
			$this->_aValues[$sKey] = $mValue;
			return true;
		}

		return false;

	}

	public function save() {
		
		foreach((array)$this->_aValues as $sKey=>$mValue) {
			
			$sSql = "
					SELECT
						*
					FROM
						individual_update
					WHERE
						`key` = :key
					LIMIT 1
					";
			$aSql = array('key'=>$sKey);
			$aCheck = DB::getQueryRow($sSql, $aSql);
			
			if(is_array($mValue)) {
				$mValue = json_encode($mValue);
			} elseif($sKey == 'password') {
				$mValue = md5($mValue);
			}
			
			$aData = array();
			$aData['active'] = 1;
			$aData['key'] = $sKey;
			$aData['value'] = $mValue;
			
			if(empty($aCheck)) {
				$aData['created'] = date("YmdHis");
				DB::insertData('individual_update', $aData);
			} else {
				DB::updateData('individual_update', $aData, "`key` = '".DB::escapeQueryString($sKey)."'");
			}
			
		}

		unset($this->_aValues['password']);

	}

	public function setUpdateTimestamp() {

		$this->last_update = time();
		$this->save();

	}

	public function checkUpdatePassword($sMD5Password) {

		if($this->password == $sMD5Password) {
			return true;
		} else {
			return false;
		}

	}

	public function setUpdatePassword($sPassword) {

		$this->_sUpdatePassword = $sPassword;

		$sParameter = 'task=check_password';

		$aResponse = $this->_queryDevelopmentServer($sParameter);

		if($aResponse['check'] == 1) {
			return true;
		} else {
			$this->_sUpdatePassword = '';
			return false;
		}

	}

	public function updateDirectories() {

		$bSuccess = true;
		foreach((array)$this->directories as $sDirectory) {
			$bTemp = $this->_updateDirectory($sDirectory);
			if(!$bTemp) {
				$bSuccess = false;
			}
		}
		
		return $bSuccess;

	}
	
	public function updateStyles() {

		$bSuccess = $this->_rewriteTable('cms_styles_files');
		if($bSuccess === true) {
			$bSuccess = $this->_rewriteTable('cms_styles');
		}

		return $bSuccess;

	}
	
	public function updateImgbuilder() {

		$bSuccess = $this->_rewriteTable('system_imgbuilder');

		return $bSuccess;

	}
	
	public function updateBlocks() {

		$bSuccess = $this->_rewriteTable('cms_blocks');

		return $bSuccess;

	}
	
	public function updateExtensions() {

		$bSuccess = $this->_rewriteTable('system_elements');

		return $bSuccess;

	}
	
	public function updateTableStructure() {

		$bSuccess = true;
		foreach((array)$this->db_structure as $sTable) {
			$bTemp = $this->_updateTableStructure($sTable);
			if(!$bTemp) {
				$bSuccess = false;
			}
		}

		return $bSuccess;
	}
	
	public function updateTables() {
		
		$bSuccess = true;
		foreach((array)$this->db_data as $sTable) {
			$bTemp = $this->_rewriteTable($sTable);
			if(!$bTemp) {
				$bSuccess = false;
			}
		}

		return $bSuccess;

	}
	
	/**
	 * Aktualisiert Seitenvorlagen und deren Inhalten
	 */
	public function updatePageTemplates() {
		
		// cms_pages und cms_content vom Entwicklungsserver holen und in temporäre Tabellen schreiben
		$sTablePages = $this->_writeTable('cms_pages', true);
		$sTableContent = $this->_writeTable('cms_content', true);
		
		// Wenn eine Tabelle nicht kopiert werden konnte -> abbrechen
		if(
			$sTablePages === false ||
			$sTableContent === false
		) {
			return false;
		}
		
		// Alle Seitenvorlagen auslesen
		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`element` = 'template' AND
				`active` = 1
			";
		$aSql = array('table'=>$sTablePages);
		$aTemplates = (array)DB::getQueryRows($sSql, $aSql);
		
		foreach($aTemplates as $aTemplate) {
			
			// Template suchen
			$sSql = "
				SELECT
					`id`
				FROM
					`cms_pages`
				WHERE
					`path` = :path AND
					`file` = :file AND
					`element` = 'template'
				LIMIT 1
				";
			$aSql = array(
				'path' => $aTemplate['path'],
				'file' => $aTemplate['file']
			);
			$iTemplateId = DB::getQueryOne($sSql, $aSql);
			
			$aData = $aTemplate;
			unset($aData['id']);
			unset($aData['changed']);
			
			if($iTemplateId > 0) {
				$sWhere = " `id` = ".(int)$iTemplateId;
				DB::updateData('cms_pages', $aData, $sWhere);
			} else {
				$iTemplateId = DB::insertData('cms_pages', $aData);
			}
			
			// Inhalte suchen
			$sSql = "
				SELECT
					*
				FROM
					#table
				WHERE
					`page_id` = :page_id AND
					`active` = 1
				";
			$aSql = array(
				'table' => $sTableContent,
				'page_id' => $aTemplate['id']
			);
			$aContents = (array)DB::getQueryRows($sSql, $aSql);
			
			foreach($aContents as $aContent) {

				// Template suchen
				$sSql = "
					SELECT
						`id`
					FROM
						`cms_content`
					WHERE
						`page_id` = :page_id AND
						`number` = :number AND
						`level` = :level
					LIMIT 1
					";
				$aSql = array(
					'page_id' => $iTemplateId,
					'number' => $aContent['number'],
					'level' => $aContent['level']
				);
				$iContentId = DB::getQueryOne($sSql, $aSql);

				$aData = $aContent;
				unset($aData['id']);
				unset($aData['changed']);
				$aData['page_id'] = $iTemplateId;

				if($iContentId > 0) {
					$sWhere = " `id` = ".(int)$iContentId;
					DB::updateData('cms_content', $aData, $sWhere);
				} else {
					$iContentId = DB::insertData('cms_content', $aData);
				}

			}
			
		}
		
		return true;
		
	}
	
	protected function _updateDirectory($sDir) {

		$aResponse = $this->_getFileList($sDir);
		$aFiles = $aResponse['files']; 
		$aErrorFiles = array();
		foreach($aFiles as $sFile){
			$sFileContent = $this->_getFileContent($sFile);
			$bSuccess = $this->_writeFile($sFile, $sFileContent);
			if(!$bSuccess){
				$aErrorFiles[] = $sFile;
			}
			$this->_aEmailLog['files'][] = 'Update of file '.$sFile.' '.($bSuccess?'succeeded':'failed').' (Directory: '.$sDir.').';
		}
		if(empty($aFiles)){
			$this->_aEmailLog['files'][] = 'No new files (Directory: '.$sDir.').';
		}
		$iSuccess = 0;
		if(empty($aErrorFiles)){
			$iSuccess = 1;
		}

		if($iSuccess == 1){
			return true;
		}

		return false;

	}

	protected function _getTableStructure($sTable) {
		
		$sParameter = 'task=get_table_structure&table='.$sTable;
		$aStructure = $this->_queryDevelopmentServer($sParameter);

		return $aStructure['structure'];
		
	}

	protected function _getTableDescription($sTable) {
		
		$sParameter = 'task=get_table_description&table='.$sTable;
		$aDescription = $this->_queryDevelopmentServer($sParameter);

		return $aDescription['description'];

	}
	
	protected function _getTableData($sTable) {
		
		$sParameter = 'task=get_table_data&table='.$sTable;
		$aData = $this->_queryDevelopmentServer($sParameter);

		return $aData['data'];
		
	}

	/**
	 * Holt eine Tabelle mit Struktur und Daten vom Entwicklungsserver
	 * 
	 * @param type $sTable
	 * @param type $bTemporary
	 * @return type 
	 */
	protected function _writeTable($sTable, $bTemporary=false) {
		
		$aSqlTable = array('table'=>$sTable);

		$sStucture = $this->_getTableStructure($sTable);
		$aData = $this->_getTableData($sTable);

		if(empty($sStucture)) {
			$this->_aEmailLog['write_table'][] = 'No structure (Table: '.$sTable.').';
			return false;
		}

		if(empty($aData)) {
			$this->_aEmailLog['write_table'][] = 'No data (Table: '.$sTable.').';
			return false;
		}

		$sTempName = \Util::generateRandomString(8);
		$sStucture = str_replace($sTable, $sTempName, $sStucture);
		
		if($bTemporary === true) {
			$sStucture = preg_replace("/CREATE(\s+)TABLE/", "CREATE TEMPORARY TABLE", $sStucture);	
		}
		
		$bCreate = DB::executeQuery($sStucture);

		if($bCreate === false) {
			$this->_aEmailLog['write_table'][] = 'Create temporary table failed (Table: '.$sTable.').';
			return false;
		}

		$iRows = 0;
		foreach((array)$aData as $aRow) {
			$bInsert = DB::insertData($sTempName, $aRow);
			if($bInsert) {
				$iRows++;
			}
		}

		if($iRows != count($aData)) {
			$this->_aEmailLog['write_table'][] = 'Insert data failed (Table: '.$sTable.').';
			return false;
		}

		return $sTempName;

	}
	
	protected function _rewriteTable($sTable) {	
		
		if(method_exists('Util', 'backupTable')) {
			Util::backupTable($sTable);
		}
		
		$aSqlTable = array('table'=>$sTable);
		
		$sTempName = $this->_writeTable($sTable);

		if($sTempName === false) {
			$this->_aEmailLog['rewrite_tables'][] = 'Write table failed (Table: '.$sTable.').';
			return false;
		}

		$sSql = "DROP TABLE #table";
		DB::executePreparedQuery($sSql, $aSqlTable);

		$sSql = "RENAME TABLE #table1 TO #table2";
		$aSql = array('table1'=>$sTempName, 'table2'=>$sTable);
		DB::executePreparedQuery($sSql, $aSql);

		$this->_aEmailLog['rewrite_tables'][] = 'Rewrite succeeded (Table: '.$sTable.').';

		return true;

	}
	
	protected function _updateTableStructure($sTable) {
	
		if(method_exists('Util', 'backupTable')) {
			Util::backupTable($sTable);
		}
		
		$bSuccess = true;
		
		$aTableDescriptionsOld = array();
		$aTableDescriptionsOld['cols'] = $this->getTableDescription($sTable);
		$aTableDescriptionsOld['create'] = $this->getTableStructure($sTable);
		
		$aTableDescriptionsNew = array();
		$aTableDescriptionsNew['cols'] = $this->_getTableDescription($sTable);
		$aTableDescriptionsNew['create'] = $this->_getTableStructure($sTable);

		$aTableCols = $aTemp['cols'];
		$sCreate = $aTemp['create'];
		
		$aOLDExplode = self::prepareString($aTableDescriptionsOld['create']);
		$aNEWExplode = self::prepareString($aTableDescriptionsNew['create']);

		$aLog = array();
		
		foreach($aNEWExplode as $iRows => $sQueryValues) {
			if(!in_array($sQueryValues, $aOLDExplode)) {
				$aLog['difference']['new'][] = $sQueryValues;
			}
		}

		foreach($aOLDExplode as $iRows => $sQueryValues) {
			if(!in_array($sQueryValues, $aNEWExplode)) {
				$aLog['difference']['old'][] = $sQueryValues;
			}
		}

		$aQuerys = array();
		$sRegEx = '/`([a-zA-Z_0-9]*)`/ms';

		// entfernte felder oder indizes löschen
		foreach((array)$aLog['difference']['old'] as $sQuery) {
			if(strpos($sQuery, 'KEY') === false) {
				
			} else {
				
				preg_match("/KEY `(.*?)` \(/", $sQuery, $aIndexMatch);
				
				if(!empty($aIndexMatch[1])) {
					 //index löschen
					$aQuerys[] = 'ALTER TABLE '.$sTable.' DROP INDEX `'.$aIndexMatch[1].'`';
				}

			}				

		}
		
		foreach((array)$aLog['difference']['new'] as $sQuery) {
			
			//schauen ob es eine INDEX angabe ist
			if(strpos($sQuery, 'KEY') === false){

				preg_match($sRegEx, $sQuery, $aColumnName);
				$sColumn = $aColumnName[1];

				if($sColumn != "") {
					$aOldCoulmns = array();
					foreach((array)$aLog['difference']['old'] as $sOldQuery) {
						preg_match($sRegEx, $sOldQuery, $aOldColumnName);
						$sOldColumn = $aOldColumnName[1];
						$aOldCoulmns[] = $sOldColumn;
					}
					if(in_array($sColumn, $aOldCoulmns)) {
						//spalten ändern
						$aQuerys[] = 'ALTER TABLE '.$sTable.' CHANGE `'.$sColumn.'` '.$sQuery;
					} else {
						//spalten hinzufügen
						$aQuerys[] = 'ALTER TABLE '.$sTable.' ADD '.$sQuery;
					}
				}

			} else {

				//index hinzufügen
				$aQuerys[] = 'ALTER TABLE '.$sTable.' ADD '.$sQuery;

			}

		}
		
		foreach((array)$aQuerys as $sQuery){
			$this->_aEmailLog['queries'][] = $sQuery;
			$bQuery = DB::executeQuery($sQuery);
			if(!$bQuery) {
				$bSuccess = false;
			}
		}

		return $bSuccess;
		
	}
	
	public function getTableStructure($sTable) {
		
		$sSql = " SHOW CREATE TABLE #table";
		$aSql = array('table'=>$sTable);
		$aTable = DB::getQueryRow($sSql, $aSql);
		$sCreate = $aTable['Create Table'];
		
		return $sCreate;
		
	}

	public function getTableDescription($sTable) {
		
		$sSql = " DESCRIBE #table";
		$aSql = array('table'=>$sTable);
		$aDescribe = DB::getPreparedQueryData($sSql, $aSql);

		return $aDescribe;

	}

	public function getTableData($sTable) {
		
		$sSql = " SELECT * FROM #table";
		$aSql = array('table'=>$sTable);
		DB::setResultType(MYSQL_ASSOC);
		$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);
		
		return $aResult;
		
	}
	
	protected function _getFileList($sDir) {

		$sParameter = 'task=get_file_list&dir='.$sDir.'&last_update_timestamp='.$this->last_update;
		$aList = $this->_queryDevelopmentServer($sParameter);

		return $aList;

	}
	
	protected function _getFileContent($sFile) {
		$sParameter = 'task=get_file&file='.$sFile;
		$sFile = $this->_queryDevelopmentServer($sParameter, false);

		return $sFile;
	}
	
	protected function _writeFile($sFile, $sFileContent) {
		global $system_data;
		
		try {

			$sDir = substr($sFile, 0, strrpos($sFile, '/'));

			$bDirectory = Util::checkDir(\Util::getDocumentRoot().$sDir);
			
			if(!$bDirectory) {
				throw new Exception(sprintf(L10N::t('Verzeichnis %s konnte nicht erstellt werden!'), $sDir));
			}

			$sStart	= "#"."##START"."###";
			$sEnd	= "#"."##END"."###";
			if(
				strpos($sFileContent, $sStart) !== false && 
				strpos($sFileContent, $sEnd) !== false
			) {
				$pos1 = strpos($sFileContent, $sStart);
				$pos2 = strpos($sFileContent, $sEnd);
				$len1 = strlen($sStart);
				$len2 = strlen($sEnd);
				$sFileContent = substr($sFileContent,($pos1+$len1),($pos2-$pos1-$len1));
				$handle = fopen(\Util::getDocumentRoot().$sFile, 'wb');
		
				fwrite($handle, $sFileContent);
				fclose($handle);
				chmod(\Util::getDocumentRoot().$sFile, $system_data['chmod_mode_file']);
	
			}
	
		} catch (Exception $e) {
			return false;
		}
	
		return true;

	}
	
	public function checkUpdateConnection() {

		$bCheck = $this->_queryDevelopmentServer();
		
		if($bCheck === false) {
			return false;
		} else {
			return true;
		}
				
	}
	
	public function getLastConnectionError() {
		return $this->_sLastConnectionError;
	}
	
	protected function _queryDevelopmentServer($sParameter='', $bDecodeJson=true) {

		$sUrl = $this->url;

		if(substr($sUrl, -1) != '/') {
			$sUrl .= '/';
		}

		$sUrl .= 'admin/extensions/individualupdate.html';

		$sParameter .= '&mode=api&password='.md5($this->_sUpdatePassword);

		// check if https request
		$bSSL = false;
		if(strpos($sUrl, 'https://') !== false) {
			$bSSL = true;
		}

		$rCurl = curl_init(); 
		curl_setopt($rCurl, CURLOPT_URL, $sUrl);
		curl_setopt($rCurl, CURLOPT_POSTFIELDS, $sParameter);
		curl_setopt($rCurl, CURLOPT_POST, true);
		if($bSSL) {
			curl_setopt($rCurl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($rCurl, CURLOPT_SSL_VERIFYHOST, 0);
		}
		curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($rCurl, CURLOPT_USERAGENT, 'webDynamics update-agent'); 
		$sHtml = curl_exec($rCurl);

		if($sHtml === false) {
			$this->_sLastConnectionError = curl_error($rCurl);
			return false;
		}

		if($bDecodeJson) {
			$mResponse = json_decode($sHtml, true);
			return $mResponse;
		} else {
			return $sHtml;
		}

	}
	
	public function getTables($sSearch='', $sSearchNegative='') {

		$aTables = DB::listTables();

		$aReturn = array();
		foreach((array)$aTables as $sTable) {
			if(
				(		
					$sSearch == "" ||
					strpos($sTable, $sSearch) !== false
				) &&
				(
					$sSearchNegative == "" ||
					strpos($sTable, $sSearchNegative) === false
				)
			) {
				$aReturn[$sTable] = $sTable;
			}
		}
		
		return $aReturn;
		
	}
	
	public function getDirectories($sDir='', $sSearch='', $sSearchNegative='', $iMaxDepth=4, $bFirstCall=true) {

		if($bFirstCall) {
			$this->_aDirectories = array();
		}

		if(substr($sDir, -1) != '/') {
			$sDir .= '/';
		}

		$aFiles = scandir(\Util::getDocumentRoot().$sDir);
		
		$aDirectories = array();
		
		foreach((array)$aFiles as $iKey=>$sFile) {

			if(
				$sFile != '.' &&
				$sFile != '..' &&
				is_dir(\Util::getDocumentRoot().$sDir.$sFile) &&
				(
					$sSearch == "" ||
					strpos($sFile, $sSearch) !== false
				) &&
				(
					$sSearchNegative == "" ||
					strpos($sFile, $sSearchNegative) === false
				)
			) {
				$this->_aDirectories[$sDir.$sFile] = $sDir.$sFile;
				$this->_iCurrentDepth++;
				
				if($this->_iCurrentDepth < $iMaxDepth) {
					$this->getDirectories($sDir.$sFile, $sSearch, $sSearchNegative, $iMaxDepth, false);
				}
				
				$this->_iCurrentDepth--;
			}
		}

		return $this->_aDirectories;

	}

	public function listFilesOfDir($sDir, $iLastTime = 0) {

		$sOut = "";
		if ($handle = opendir($sDir)) {
	
		    /* Das ist der korrekte Weg, ein Verzeichnis zu durchlaufen. */
		    while (false !== ($file = readdir($handle))) {

		    	if($file == '..') {
		    		continue;
		    	}

				if(
					is_dir($sDir.'/'.$file)
				){
					if(
						$file != '.' && 
						$file != '..' && 
						strpos($file, '.') === false &&
						strpos($file, 'secure') === false &&
						strpos($file, 'backup') === false &&
						strpos($file, 'dbModels') === false &&
						strpos($file, 'picture_library') === false &&
						strpos($file, 'plesk') === false &&
						$file != 'german' &&
						$file != 'english' &&
						$file != 'tools' &&
						$file != 'test' &&
						$file != 'nbproject' &&
						$file != 'install' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'zend' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'img' &&
						strpos($sDir.'/'.$file, 'admin/stats') === false  &&
						strpos($sDir.'/'.$file, 'media/temp') === false  &&
						strpos($sDir.'/'.$file, 'media/original') === false &&
						strpos($sDir.'/'.$file, 'media/auto') === false &&
						strpos($sDir.'/'.$file, 'system/extensions/zend') === false
					) {
						$this->listFilesOfDir($sDir.'/'.$file, $iLastTime);
					}
				} else {
					if(
						strpos($file, '%%') === false &&
						strpos($file, '__') === false &&
						strpos($file, '.svn') === false &&
						strpos($file, 'nbproject') === false &&
						substr($file, -4) !== '.pdf' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'system/includes/config.inc.php' &&
						$file != '.' && 
						$file != '..'
					) {
	
						if(filemtime($sDir.'/'.$file) > (int)$iLastTime){
							$sDir_ = str_replace(\Util::getDocumentRoot(), '', $sDir);
							$this->_aFiles[] = $sDir_.'/'.$file;
						}
						
					}
				}
					   
		    }
	
	    	closedir($handle);
		}

		return $this->_aFiles;
		
	}
	
	public function getDebug() {
		return $this->_aEmailLog;	
	}

	public static function prepareString($sString) {
		
		$sString = str_ireplace("AUTO_INCREMENT", "AUTO_INCREMENT", $sString);
		$sString = str_ireplace("default", "default", $sString);
		$sString = str_ireplace("ON UPDATE", "ON UPDATE", $sString);
		$sString = str_ireplace("CHARACTER SET", "CHARACTER SET", $sString);
		$sString = str_ireplace("COLLATE", "COLLATE", $sString);
		$sString = str_ireplace("  ", " ", $sString);
		
		$sString = preg_replace("/ AUTO_INCREMENT=[0-9]+/", "", $sString);
		$sString = preg_replace("/ default '([0\-\.]*)'/", "", $sString);
		$aArray = preg_split("/[\s,]*\n[\s,]*/", $sString);

		return $aArray;
	}
}