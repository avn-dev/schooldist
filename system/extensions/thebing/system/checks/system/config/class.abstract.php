<?php

/**
 * Check um System-Variablen zu ergänzen/verändern
 * 
 * @author Mehmet Durmaz
 */
abstract class Ext_Thebing_System_Checks_System_Config_Abstract extends GlobalChecks
{
	public function getTitle()
	{
		return 'Change System Config';
	}
	
	public function getDescription()
	{
		$sConfig = (string)$this->_getConfigChangeString();
		
		return 'Change system "' . $sConfig . '" config.';
	}
	
	public function executeCheck()
	{
		$aChangeData = (array)$this->_getConfigChangeData();
		
		foreach($aChangeData as $sKey => $mData)
		{
			$sSql = '
				REPLACE INTO
					`system_config` (`c_key`, `c_value`, `c_description`)
				VALUES
					(:key, :value, :description)
			';
			
			$mValue			= '';
			
			$sDescription	= '';
			
			if(is_array($mData))
			{
				if(isset($mData['description']))
				{
					$sDescription	= $mData['description'];
				}
				
				if(isset($mData['value']))
				{
					$mValue			= $mData['value'];
				}
			}
			else
			{
				$mValue = $mData;
			}
			
			if(strlen($mValue) <= 0)
			{
				throw new Exception('You have to define a value for the config!');
			}
			
			$aSql = array(
				'key'			=> $sKey,
				'value'			=> $mValue,
				'description'	=> $sDescription,
			);
			
			$rRes = DB::executePreparedQuery($sSql, $aSql);
			
			if($rRes === false)
			{
				__pout('Query failed!');
			}
		}
		
		return true;
	}
	
	/**
	 * Die Config-Werte die verändert/ergänzt werden in diesem Check
	 * 
	 * @return string
	 */
	abstract protected function _getConfigChangeString();
	
	/**
	 * Die Config keys/values die verändert/ergänzt werden in diesem Check
	 * 
	 * @return array
	 */
	abstract protected function _getConfigChangeData();
}