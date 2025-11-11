<?php

class Ext_TS_System_Checks_Cms_Sites extends GlobalChecks 
{

	public function getTitle()
	{
		$sTitle = 'Check CMS Sites';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Check and add default site to cms.';
		return $sDescription;
	}

	public function executeCheck()
	{
		
		$sSql = "
			SELECT
				`sys_s`.`id`
			FROM
				`system_sites` `sys_s` INNER JOIN
				`system_sites_languages` `sys_s_l` ON
					`sys_s_l`.`site_id` = `sys_s`.`id` AND
					`sys_s_l`.`active` = 1
			WHERE
				`sys_s`.`active` = 1
			LIMIT
				1
		";
		
		$iDefaultSite = (int)DB::getQueryOne($sSql);
		
		if($iDefaultSite <= 0)
		{
			// Falls nur eine Tabelle befüllt ist, vorsichtshalber beide leeren
			$sSql = "
				TRUNCATE `system_sites`
			";
			
			$rRes = DB::executeQuery($sSql);
			
			if(!$rRes)
			{
				__pout('Couldnt truncate sites!'); 
				
				return false;
			}
			
			$sSql = "
				TRUNCATE `system_sites_languages`
			";
			
			$rRes = DB::executeQuery($sSql);
			
			if(!$rRes)
			{
				__pout('Couldnt truncate sites languages!'); 
				
				return false;
			}
			
			// Site anlegen
			$aInsert = array(
				'name' => 'Default Site'
			);
			
			$iDefaultSite = (int)DB::insertData('system_sites', $aInsert);
			
			if($iDefaultSite > 0)
			{
				$aLangs = array(
					'de' => 'german',
					'en' => 'english',
					'es' => 'spanish',
					'fr' => 'french',
				);
				
				$aLangErrors = array();
				
				$iPos = 1;
				
				foreach($aLangs as $sLang => $sTitle)
				{
					$sLocale = $sLang . '_' . strtoupper($sLang) . '.UTF-8';
					
					$aInsert = array(
						'site_id'	=> $iDefaultSite,
						'name'		=> $sTitle,
						'code'		=> $sLang,
						'charset'	=> 'UTF-8',
						'locale'	=> $sLocale,
						'position'	=> $iPos,
					);
					
					$iLang = (int)DB::insertData('system_sites_languages', $aInsert);
					
					if($iLang <= 0)
					{
						$aLangErrors[] = $aInsert;
					}
					
					$iPos++;
				}
				
				if(!empty($aLangErrors))
				{
					__pout('Site Languages Errors!');
					
					__pout($aLangErrors); 
					
					return false;
				}
			}
			else
			{
				__pout('Couldnt add site!');
				
				return false;
			}
		}
        
		return true;

	}
		
}