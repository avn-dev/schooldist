<?php

class Ext_Thebing_System_Checks_Document2 extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		if($user_data['name'] == 'schneider'){
			return true;
		}

		return false;
	}

	public function executeCheck(){
		global $user_data, $system_data;

		if($_SERVER['HTTP_HOST'] != 'live0.thebing.com')
		{
			return true;
		}

		$oDb = DB::getDefaultConnection();

		if(!hasRight('modules_admin')){
			$this->_aFormErrors[] = 'Only an full CMS Administrator hast the Right to start this Script!';
			return false;
		}

		set_time_limit(14400);
		@ini_set("memory_limit", '2048M');

		// Backup der Tabellen
		try{
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions_items');
		} catch(Exception $e){
			__pout($e);
			//return false;
		}


		$aSql = array();


		## Start Daten umschreiben

			$sSql = "SELECT
							`kidp`.`id`, `kidp`.`invoice_id`
						FROM
							`__2010_08_11_kolumbus_inquiries_documents_positions` `kidp` 
						WHERE
							`kidp`.`active` = 1
						";
			$aOldItems = DB::getPreparedQueryData($sSql,$aSql);

			$i = $n = 0;
			foreach((array)$aOldItems as $aItem){

				$sSql = " SELECT
								`id`
							FROM
								`kolumbus_inquiries_documents_versions` `kidv`
							WHERE
								`kidv`.`document_id` = :document_id
							ORDER BY
								`kidv`.`id`
							LIMIT 1
								";
				$aSql = array();
				$aSql['document_id'] = (int)$aItem['invoice_id'];

				$aVersion = DB::getPreparedQueryData($sSql,$aSql);
				$aVersion = $aVersion[0];

				if($aVersion['id'] > 0){
					
					$sWhere = '`id` = '.(int)$aItem['id'];
					DB::updateData('kolumbus_inquiries_documents_versions_items', array('version_id'=> (int)$aVersion['id']), $sWhere);
					$n++;
				} else {
					__pout($aItem);
					$i++;
				}

				if($i == 100){
					die('zuviele nicht gefunden');
				}

				
			}
			__pout('I: ' . $i . ' :: N: ' . $n);

		return true;

	}

	public function getTitle()
	{
		return 'Bugfix of LIVE0';
}

	public function getDescription()
	{
		return 'New assigment of version items.';
	}
}


?>