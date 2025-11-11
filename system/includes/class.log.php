<?php

class Log {

	/**
	 * Alle MonoLog-Logger
	 * @var Monolog\Logger[]
	 */
	protected static $_aMonolog = array();

	/**
	 * Logger-Instanz erzeugen
	 *
	 * @param string $sNamespace
	 * @param string $sChannel
	 * @return Monolog\Logger
	 */
	public static function getLogger($sNamespace = 'default', $sChannel = 'Log') {

		$sKey = $sNamespace.'_'.$sChannel;

		if(!isset(self::$_aMonolog[$sKey])) {
			if(class_exists('Monolog\Logger')) {
				$sDir = Util::getDocumentRoot().'storage/logs/';
				self::$_aMonolog[$sKey] = new \Core\Helper\MonologLogger($sChannel);
				self::$_aMonolog[$sKey]->pushHandler(new Handler_Monolog_ZipFile($sDir . $sNamespace.'.log', 1, Monolog\Logger::DEBUG));
			} else {
				self::$_aMonolog[$sKey] = new \Core\Service\LogService;
			}
		}

		return self::$_aMonolog[$sKey];

	}

	/**
	 * Loggt in die »update.log«
	 * @param float $fVersion
	 * @param string $sType
	 * @param string $sData
	 * @param bool $bSuccess
	 * @param mixed $mAdditional
	 */
	public static function logUpdateAction($fVersion, $sType, $sData, $bSuccess, $mAdditional = null) {

		$oUser = System::getCurrentUser();
		$oLog = self::getLogger('update');

		switch($sType) {
			case 'FILE':
				// Bei Datei nur den Dateinamen
				$sDataMessage = substr(strrchr($sData, '/'), 1);
				break;
			case 'SQL':
				// Bei SQL nur Statment mit Tabelle
				preg_match('/^(.*?`.*?)`/', $sData, $aMatches);
				$sDataMessage = $aMatches[0];
				break;
			case 'CHECK':
			case 'CHECK_EXISTS':
				$sDataMessage = $sData;
				break;
			default:
				$sDataMessage = '';
		}

		$sMessage = sprintf('v%s: %u %s "%s"', $fVersion, $bSuccess, $sType, $sDataMessage);

		$aOptional = array(
			'data' => $sData,
			'user_id' => $oUser->id
		);

		if($mAdditional !== null) {
			if($mAdditional instanceof Exception) {
				$mAdditional = print_r($mAdditional, true);
			}
			$aOptional['additional'] = $mAdditional;
		}

		if($bSuccess) {
			$oLog->addInfo($sMessage, $aOptional);
		} else {
			$oLog->addError($sMessage, $aOptional);
		}

	}

	static public function add($sCode, int $iClassId=null, $sClassName=null, array $aAdditional=null) {
		
		$aLog = [
			'created' => date('Y-m-d H:i:s'),
			'code' => $sCode
		];
		
		if(
			$iClassId !== null &&
			$sClassName !== null
		) {
			$aLog['element_id'] = $iClassId;
			$aLog['elementname'] = $sClassName;
		}
		
		if($aAdditional !== null) {
			$aLog['additional'] = json_encode($aAdditional);
		}
		
		// Existiert ein eingeloggter Backend-User?
		
		$oAccessBackend = Access::getInstance();
		
		if(
			$oAccessBackend instanceof Access_Backend &&
			$oAccessBackend->checkValidAccess()
		) {
			// TODO Spalte wurde im Juni 2020 umbenannt, ohne die DB-Querys ist aber kein Login mehr möglich
			//  - Sollte also irgendwann entfernt werden
			$sField = 'user';
			if(DB::getDefaultConnection()->checkField('system_logs', 'user_id', true)) {
				$sField = 'user_id';
			}

			$aUserData = $oAccessBackend->getUserData();
			$aLog[$sField] = (int)$aUserData['data']['id'];
		}
		
		DB::insertData('system_logs', $aLog);

	}
	
	static public function enterLog($iPageId, $sEntry, $element_id=null, $element_name=null) {

		$oAccessBackend = Access::getInstance();
		
		$aSql = array(
			'created' => date('Y-m-d H:i:s')
		);

		if($iPageId > 0) {
			$aSql['element_id'] = (int)$iPageId;
			$aSql['elementname'] = '\Cms\Entity\Page';
		} elseif(
			$element_id !== null &&
			$element_name !== null
		) {
			$aSql['element_id'] = (int)$element_id;
			$aSql['elementname'] = (string)$element_name;
		}

		// Existiert ein eingeloggter Backend-User?
		if(
			$oAccessBackend instanceof Access_Backend &&
			$oAccessBackend->checkValidAccess()
		) {
			$aUserData = $oAccessBackend->getUserData();
			$aSql['user_id'] = (int)$aUserData['data']['id'];
		}

		$aSql['code'] = $sEntry;

		\System::wd()->executeHook('enterlog', $aSql);

		DB::insertData('system_logs', $aSql);

	}
	
	static public function getLogMessages() {
		
		$sCacheKey = __METHOD__.'_'.System::getInterfaceLanguage();

		$aMessages = WDCache::get($sCacheKey);
		
		if($aMessages === null) {
			
			$aMessages = [];

			$bundles = (new \Core\Service\BundleService())->getActiveBundleNames();
			$helper = new Core\Helper\Bundle();

			foreach ($bundles as $bundle) {
				$config = $helper->getBundleConfigData($bundle, false);
				if(
					isset($config['log_messages']) &&
					is_array($config['log_messages'])
				) {
					foreach($config['log_messages'] as $sKey=>$sMessage) {
						$aMessages[$sKey] = L10N::t($sMessage, 'Log');
					}
				}
			}

			asort($aMessages);
			
			WDCache::set($sCacheKey, (60*60*24*7), $aMessages);

		}

		return $aMessages;
	}
	
	public static function getLogEntries(int $limit=10, string $sElement=null, int $iElementId=null) {

		$aMessages = self::getLogMessages();

		$aSql = ['limit'=>$limit];
		
		$aOutput = [];
		$sQuery = "
			SELECT 
				l.id,
				l.code,
				l.created,
				l.user_id,
				l.additional,
				u.username ,
				CONCAT(
					u.`firstname`,
					' ',
					u.`lastname`
				) `name`
			FROM 
				system_logs l LEFT OUTER JOIN 
				system_user u ON 
					l.user_id = u.id 
		";
		
		if($sElement !== null) {
			$sQuery .= "
			WHERE
				l.element_id = :element_id AND
				l.elementname = :elementname
					";
			$aSql['element_id'] = $iElementId;
			$aSql['elementname'] = $sElement;
		}
		
		$sQuery .= "
			ORDER BY 
				l.created DESC 
			LIMIT 
				:limit
		";

		$aLogs = (array)DB::getQueryRows($sQuery, $aSql);
		
		foreach($aLogs as $my) {
			
			$dCreated = new \DateTime($my['created']);
			$my['ftime'] = strftime("%x %X", $dCreated->getTimestamp());
			
			if(!empty($my['code'])) {
				if(!empty($aMessages[$my['code']])) {
					$my['action'] = $aMessages[$my['code']];
				} else {
					$my['action'] = $my['code'];
				}
				
				if(!empty($my['additional'])) {
					
					$aAdditional = json_decode($my['additional'], true);
					
					array_walk(
						$aAdditional, 
						function($mValue, $sKey) use(&$my) {
							$my['action'] = str_replace('{'.$sKey.'}', $mValue, $my['action']);
						}
						
					);
				}
				
			}
			
			$aOutput[] = $my;
		}

		return $aOutput;
	}
	
	public static function addErrorMessage(string $sSubject, string $sMessage=null) {

		// TODO App-Env anders lösen
		if (defined('APP_ENV') && APP_ENV === 'local') {
			return true;
		}

		$aData = [
			'subject' => $sSubject,
			'message' => $sMessage
		];
		$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
		$oStackRepository->writeToStack('core/logging-handler', $aData, 2);

		return true;
	}

}
