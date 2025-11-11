<?php

/**
 * Rechteverwaltung WDBASIC
 */
class Ext_TC_Access extends Ext_TC_Basic {

	protected $_sTable = 'tc_access';

	static protected $sClassName = 'Ext_TC_Access';

	private static $aInstance = null;
	
	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent'
	 *			'check_active'=>true,
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'section'=>array(
	 			'class'=>'Ext_TC_Access_Section',
	 			'key'=>'section_id',
	 			'type'=>'parent'
	 		)
	);
	
	
	public function getClassName() {
		return self::$sClassName;
	}
	
	public function check(){
		$oSection = $this->getJoinedObject('section');
		$oData = new Ext_TC_Access_Data();
		$bAccess = $oData->checkAccess($oSection->key, $this->key);
		return $bAccess;
	}

	/**
	 * @param int $iDataId
	 * @return Ext_TC_Access
	*/ 
	static public function getInstance($iDataId = 0) {

		$sClass = self::$sClassName;

		if($iDataId == 0) {
			return new $sClass($iDataId);
		}
		
		$oInstance = self::$aInstance[$sClass][$iDataId];
		if(empty($oInstance)) {
			try {
				self::$aInstance[$sClass][$iDataId] = new $sClass($iDataId);
			} catch(Exception $e) {
				error(print_r($e, 1));
			}
		}

		return self::$aInstance[$sClass][$iDataId];
	}
	
	/**
	 * Prüft die Lizenz, Rechtedatei und Version der Rechtedatei
	 * @param $user_data
	 * @return unknown_type
	 */
	public static function checkLoginData(&$oAccess){
		global $system_data;

		if(!$oAccess->checkValidAccess()) {
			Ext_TC_Error::log('Failed to load accessfile, you are not logged in!', $oAccess->getUserData());
			return Ext_TC_Error::lastLog();  
		}
	
		$sLicense = $system_data['license'];
				
		$bServerOnline	= Ext_TC_Util::checkAccessServer();

		if($bServerOnline){
			$bFile = Ext_TC_Access_File::create($sLicense);
		} else {
			$bFile = true;
		}

		if($bFile){

			if(
				$bServerOnline && 
				!Ext_TC_Util::isCoreSystem()
			) {
				
				$sLatestAccessUpdate = System::d('latest_access_update');

				// Wenn das Update heute noch nicht ausgeführt wurde
				if($sLatestAccessUpdate !== date('Y-m-d')) {
					$oRepository = Core\Entity\ParallelProcessing\Stack::getRepository();
					$oRepository->writeToStack('tc/access-update', array('update'=>true));	
				}

			}

			// Datei version checken
			$bCheck = (boolean)Ext_TC_Access_Data::checkVersion();

			if(!$bCheck){
				Ext_TC_Error::log('Wrong accessfile version!', $sLicense);
				return Ext_TC_Error::lastLog();   
			}

		} else {
			Ext_TC_Error::log('Failed to create accessfile!', $sLicense);
			return Ext_TC_Error::lastLog();  
		}

		Ext_TC_User::resetAccessCache();

		return true;
	}
	
	public static function hasRight($sSectionKey, $sAccessKey = ''){
		$oData = new Ext_TC_Access_Data();
		$bAccess = $oData->checkAccess($sSectionKey, $sAccessKey);
		return $bAccess;
	}
}
