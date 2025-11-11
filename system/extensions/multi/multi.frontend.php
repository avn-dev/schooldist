<?php

class multi_frontend
{
	function executeHook($strHook, &$mixInput)
	{
		global $_VARS, $session_data;

		switch($strHook)
		{
			case 'manage_track_tree':
			{
				if
				(
					isset($_VARS['mu_id']) && (int)$_VARS['mu_id'] > 0 &&
					isset($_VARS['mu_action']) && $_VARS['mu_action'] == 'detail'
				)
				{
					$aLast = array_pop($mixInput);
					$aLast['link'] = $_SERVER['PHP_SELF'];
					$mixInput[] = $aLast;

					// Get multi data configuration
					$iMultiID = $session_data['hooks']['content_ids']['multi'][0];
					$sSQL = "
						SELECT `param`
						FROM `cms_extensions_config`
						WHERE `content_id` = :iContentID
						LIMIT 1
					";
					$aMultiConfig = DB::getPreparedQueryData($sSQL, array('iContentID' => $iMultiID));
					$oMultiConfig = unserialize($aMultiConfig[0]['param']);

					if($oMultiConfig->multi_id > 0) {
						// Get the multi data entry by ID
						$sSQL = "
							SELECT `title`
							FROM `multi_table_".$oMultiConfig->multi_id."`
							WHERE `id` = :iEntryID
							LIMIT 1
						";
						$aTitle = DB::getPreparedQueryData($sSQL, array('iEntryID' => $_VARS['mu_id']));

						$mixInput[] = array('title' => $aTitle[0]['title']);
					}
	
				}
				break;
			}
		}
	}
}

\System::wd()->addHook('manage_track_tree', 'multi');

?>