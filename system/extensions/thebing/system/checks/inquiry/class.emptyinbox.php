<?php

// Achtung: Dieser Check wird in Ext_Thebing_System_Checks_Combination_InboxAndNumberRange aufgerufen!
class Ext_Thebing_System_Checks_Inquiry_EmptyInbox extends GlobalChecks
{
	public function getTitle()
	{
		return 'Check Inbox of Bookings';
	}
	
	public function getDescription()
	{
		return 'Check for booking without inbox and allocate.';
	}
	
	public function executeCheck()
	{
		$iClientId	= (int)Ext_Thebing_System::getClientId();
		
		$sBackup	= Util::backupTable('ts_inquiries');
		$sBackup2	= Util::backupTable('kolumbus_access_group_access');
		
		if(!$sBackup || !$sBackup2)
		{
			__pout('couldnt backup inquiries!'); 


			return true;
		}
		
		$aFirstInbox = $this->_getFirstInbox();
		
		if(empty($aFirstInbox))
		{
			$aInsert = array(
				'client_id' => $iClientId,
				'active'	=> 1,
				'name'		=> 'Default Inbox',
				'short'		=> 'default',
			);
			
			$rRes = DB::insertData('kolumbus_inboxlist', $aInsert);
			
			if(!$rRes)
			{
				__pout('couldnt add default inbox!');
				
				return true;
			}
			
			$aFirstInbox = $this->_getFirstInbox();
			
			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_access_group`
				WHERE
					`active` = 1 AND
					`client_id` = :client_id
			";
			
			$aSql = array(
				'client_id' => $iClientId,
			);
			
			$aGroups = (array)DB::getPreparedQueryData($sSql, $aSql);
			
			foreach($aGroups as $aGroup)
			{
				$aInsert = array(
					'group_id'	=> $aGroup['id'],
					'access'	=> 'thebing_invoice_inbox_' . $aFirstInbox['id'],
				);
				
				$rRes = DB::insertData('kolumbus_access_group_access', $aInsert);
				
				if(!$rRes)
				{
					__pout('couldnt add access!'); 
				}
			}
		}
		
		$aUpdate = array('inbox' => $aFirstInbox['short'],);
		$sWhere = "`inbox` = ''";

		DB::updateData('ts_inquiries', $aUpdate, $sWhere);
		DB::updateData('kolumbus_forms', $aUpdate, $sWhere);

		$oCheck = new Ext_TS_System_Checks_Index_Reset_Inquiry();
		$oCheck->executeCheck();

		return true;
	}
	
	protected function _getFirstInbox()
	{
		$iClientId	= (int)Ext_Thebing_System::getClientId();
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inboxlist`
			WHERE
				`client_id` = :client_id AND
				`active` = 1
			ORDER BY
				`created` ASC
		";
		
		$aSql = array(
			'client_id' => $iClientId
		);
		
		$aFirstInbox = (array)DB::getQueryRow($sSql, $aSql);
		
		return $aFirstInbox;
	}
}