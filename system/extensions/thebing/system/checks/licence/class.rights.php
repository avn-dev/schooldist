<?php

abstract class Ext_Thebing_System_Checks_Licence_Rights extends GlobalChecks {
	
	/**
	 * Angabe, welche Rechte gesetzt werden sollen
	 * 
	 * array (
	 *		'thebing_pickup_confirmation_button_request',
	 *		'thebing_pickup_confirmation_button_confirm_transfer',
	 *		'thebing_pickup_confirmation_button_confirm_accommodation',
	 *		'thebing_pickup_confirmation_button_confirm_student',
	 * )
	 * 
	 * @var array 
	 */
	protected $_aRights = array ();
	
	public function getTitle() {
		return 'Allocate access rights';
	}
	
	public function getDescription() {
		return '...';
	}
	
	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bBackUp = Ext_TC_Util::backupTable('kolumbus_access_group_access');
		
		if(!$bBackUp) {
			__pout('Backup error!');
			return false;
		}
		
		$aUserGroups = $this->_getSystemUserGroups();
		
		foreach((array) $this->_aRights as $sRight) {			
			foreach ($aUserGroups as $aUserGroup) {
				
				$iUserGroup = (int) $aUserGroup['id'];
				
				$bExist = $this->_checkRightForUserGroup($sRight, $iUserGroup);
				if(!$bExist) {
					$aEntry = array(
						'group_id' => $iUserGroup,
						'access' => $sRight
					);
					
					DB::insertData('kolumbus_access_group_access', $aEntry);
				}
				
			}			
		}
		
		return true;
	}
	
	protected function _checkRightForUserGroup($sRight, $iUserGroup) {
		
		$sSql = "
			SELECT	
				*
			FROM
				`kolumbus_access_group_access`
			WHERE
				`group_id` = :group_id AND
				`access` = :access
		";
		
		$aSql = array('group_id' => $iUserGroup, 'access' => $sRight);
		
		$aData = (array) DB::getPreparedQueryData($sSql, $aSql);
		
		if(!empty($aData)) {
			return true;
		}
		
		return false;
	}
	
	protected function _getSystemUserGroups() {
		
		$sSql = "
			SELECT
				*
			FROM	
				`kolumbus_access_group`
			WHERE
				`active` = 1
		";
		
		$aData = (array) DB::getQueryData($sSql);
		return $aData;		
	}
}
