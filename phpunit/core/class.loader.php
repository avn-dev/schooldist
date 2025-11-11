<?php

class Loader {
	
	protected $_aFiles = array();
	protected $_aFileStatus = array();
	
	protected $_sSystem = 'agency';
	
	protected $_iMicrotime = 0;
	protected $_iTotalTime = 0;
	
	protected $_aCache = array();
	protected $_sCachePath = '';
	
	public function __construct($sSystem = 'agency') {
		$this->_sSystem		= $sSystem;
		
		$this->_sCachePath = Util::getDocumentRoot().'phpunit/phpunit_cache.json';
		
		$this->_getCache();

	}

	protected function _getCache() {
		$sCacheData = file_get_contents($this->_sCachePath);
		
		$this->_aCache = (array)json_decode($sCacheData, true);
	}
	
	protected function _writeCache() {
		
		$sCacheData = json_encode($this->_aCache);

		file_put_contents($this->_sCachePath, $sCacheData);

	}

	public function resetCache() {
		
		$this->_aCache = array();
		
		unlink($this->_sCachePath);
		
	}
	
	public function execute($sTestFile = ''){
		global $_VARS;
		
		$this->_iMicrotime	= getmicrotime();
		
		$sDir				= \Util::getDocumentRoot().'phpunit/'.$this->_sSystem;
		$sDirCore			= \Util::getDocumentRoot().'phpunit/core';
		
		$this->_prepareFiles($sDir);
		$this->_prepareFiles($sDirCore);

		if($_VARS['cache'] == 'truncate') {
			$this->resetCache();
		}
		
		if(!empty($sTestFile) && $sTestFile !== 'all'){
			$this->_executeTest(\Util::getDocumentRoot().$sTestFile);
		} elseif($sTestFile === 'all') {
			$this->_executeTests();
		}		

		$sHtml2 = '';
		
		$iCount = 1;
		foreach($this->_aFiles as $sFilePath){
			$sHtml2 .= $this->_printTestTr($sFilePath, $iCount);
		}
		
		$sDatabase = '';
		
		if($_VARS['database']){
			$sDatabase = '<span style="color:green">Datenbank aktualisiert"</span>';
		}
		
		$sHtml = '
		
		<div class="divHeader">
			<h1>Thebing » PHPUnit</h1>
		</div>
		
		<div style="padding: 30px;">

			<form method="get">
				<input type="hidden" value="1" name="database" />
				<button onclick="submit();">Datenbank aktualisieren</button>
				'.$sDatabase.'
			</form>
			<form method="get">
				<input type="hidden" value="all" name="file" />
				<button onclick="submit();">Alle Tests starten</button>
			</form>
			<form method="get">
				<input type="hidden" value="truncate" name="cache" />
				<button onclick="submit();">Cache leeren</button>
			</form>
			<br/><br/>
			<table class="table" style="width:100%; border-spacing: 0; border-collapse:collapse;">
				<tr>
					<th style="width:5px; padding: 3px;">&nbsp;</th>
					<th style="width:5px; padding: 3px;">#</th>
					<th style="width:auto; padding: 3px;">Test</th>
					<th style="width:100px; padding: 3px;">Anzahl</th>
					<th style="width:100px; padding: 3px;">Erfolgreich</th>
					<th style="width:100px; padding: 3px;">Fehler</th>
					<th style="width:100px; padding: 3px;">Laufzeit</th>
				</tr>
				'.$sHtml2.'
				<tr>
					<th style="width:5px; padding: 3px;">&nbsp;</th>
					<th style="width:5px; padding: 3px;">&nbsp;</th>
					<th style="width:auto; padding: 3px;">&nbsp;</th>
					<th style="width:100px; padding: 3px;">&nbsp;</th>
					<th style="width:100px; padding: 3px;">&nbsp;</th>
					<th style="width:100px; padding: 3px;">&nbsp;</th>
					<th style="width:100px; padding: 3px;">'.$this->_iTotalTime.' sec</th>
				</tr>
			</table>
			
		</div>

		';
		
		Admin_Html::loadAdminHeader();
		
		echo $sHtml;
		
		Admin_Html::loadAdminFooter();
		
	}
	
	protected function _printTestTr($sFilePath, &$iCount){
		
		$sTest = str_replace(\Util::getDocumentRoot(), '', $sFilePath);
		
		if(strpos($sTest, 'Test.php') !== false){
			
			$aStatus = $this->_getStatusMessages($sFilePath);
			$sStyle = $sStyleSuccess = 'background: #ABDBAB;';
			$sStyleError = '';
			
			if(
				!empty($aStatus['error']) || 
				empty($aStatus)
			){
				$sStyle = $sStyleError = 'background: #F78583;';
				$sStyleSuccess = '';
			}
			
			$sHtml = '
			<tr>
				<td style="'.$sStyle.' padding: 3px;">&nbsp;</td>
				<td style="padding: 3px;">'.$iCount.'.</td>
				<td style="padding: 3px;"><a href="'.$_SERVER['PHP_SELF'].'?file='.$sTest.'">'.$sTest.'</a></td>
				<td style="padding: 3px;">'.$aStatus['count'].'</td>
				<td style="'.$sStyleSuccess.'">'.$aStatus['success'].'</td>
				<td style="'.$sStyleError.'">'.$aStatus['error'].'</td>
				<td style="padding: 3px;">'.$aStatus['time'].' sec</td>
			</tr>
			';
			++$iCount;
		}
		
		
		
		return $sHtml;
	}

	
	protected function _getStatusMessages($sFilePath){
		
		if(isset($this->_aCache['status'][$sFilePath])) {
			$this->_aFileStatus[$sFilePath] = $this->_aCache['status'][$sFilePath];
		}
		
		$aStatusData = $this->_aFileStatus[$sFilePath];
		$aStatus = explode(',', $aStatusData['status']);

		$sCount = $aStatus[0];
		$sCount = str_replace('tests', '', $sCount);
		$sCount = str_replace('test', '', $sCount);
		
		$aBack = array();
		$aBack['count']		= $sCount;
		$aBack['success']	= $aStatus[1];
		$aBack['error']		= (string)$aStatus[2];
		$aBack['time']		= $aStatusData['time'];
		
		if(empty($sCount)){
			$aBack['error'] = 'unknown';
		}

		return $aBack;
		
	}

	protected function _prepareFiles($sDir){
		
		$oHandle = opendir($sDir);

		while ($sFile = readdir ($oHandle)) {
			if($sFile != "." && $sFile != "..") {
				$sFilePath = $sDir."/".$sFile;
				if(is_dir($sFilePath) && $sFilePath != $sDir) {
					$this->_prepareFiles($sFilePath);
				} else if(is_file($sFilePath)) {
					$this->_aFiles[] = $sFilePath;
				}
			}
		}
	
		closedir($oHandle);
	}
	
	protected function _executeTests(){
		ini_set('memory_limit', '1G');
		ini_set('max_execution_time', '600');
		foreach($this->_aFiles as $sFilePath){
			$this->_executeTest($sFilePath);
		}
	}
	
	protected function _executeTest($sFilePath){
		
		$sCmd = 'phpunit --debug --verbose --process-isolation --configuration '.Util::getDocumentRoot().'phpunit/'.$this->_sSystem.'/config.xml '.$sFilePath.' 2>&1';

		//$sCmd = 'phpunit --debug --verbose --configuration '.Util::getDocumentRoot().'phpunit/'.$this->_sSystem.'/config.xml '.$sFilePath.' 2>&1';
 
		$sOutput = shell_exec($sCmd);

		$iTimeNow = getmicrotime();

		$iTime = $iTimeNow - $this->_iMicrotime;
		$iTime = number_format($iTime, 6);
		
		$this->_iMicrotime = $iTimeNow;
		
		$this->_iTotalTime += $iTime;
		
		$aMatch = array();

		preg_match('/OK \((.*)\)/', $sOutput, $aMatch);

		if(
			$aMatch &&	
			!empty($aMatch[1])
		){
			$this->_aFileStatus[$sFilePath]['status'] = $aMatch[1];
			$this->_aFileStatus[$sFilePath]['time'] = $iTime;
		} else {
			if(!empty($sOutput)){
				__pout($sOutput);
			}
		}
		
		$this->_aFileStatus[$sFilePath]['output'] = $sOutput;

		$this->_getCache();
		
		$this->_aCache['status'][$sFilePath] = $this->_aFileStatus[$sFilePath];
		
		$this->_writeCache();

	}
	
	public function prepareDatabase(){

		// DB Struktur updaten
		$aDirs = array($this->_sSystem, 'core');
		
		
		$aDbData = array(
			'system' => 'thebing_unittest',
			'module' => 'thebing_unittest',
			'username' => 'thebing_unittest',
			'password' => 'koeln23',
			'host' => 'localhost',
			'port' => '3306'
		);

		if($this->_sSystem == 'school'){
            $aDbData = array(
                'system' => 'ts_unittest',
                'module' => 'ts_unittest',
                'username' => 'ts_unittest',
                'password' => 'porsche07',
                'host' => 'localhost',
                'port' => '3306'
            );
		}
		
		$oDB = DB::createConnection('unittest', $aDbData['host'], $aDbData['username'], $aDbData['password'], $aDbData['system']);
	
		foreach($aDirs as $sDir) {

			$sDir = Util::getDocumentRoot().'update_queries/'.$sDir.'/';

			$aFiles = glob($sDir.'*.sql');

			// Wenn keine Dateien da sind, dann nix machen
			if(!empty($aFiles)) {

				foreach($aFiles as $sFile) {

					// In einzelne Queries aufsplitten
					$sQueries = file_get_contents($sFile);
					$aQueries = preg_split("/\s*;\s*/", $sQueries, -1 , PREG_SPLIT_NO_EMPTY);

					if(!empty($aQueries)) {

						foreach($aQueries as $sQuery) {

							// Query ausführen
							try {
								$mReturn = $oDB->executeQuery($sQuery);
							} catch(DB_QueryFailedException $e) {
								$mReturn = false;
							}

						}

					}

				}

			}

		}
	}

}
