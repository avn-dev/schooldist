<?php

class Ext_Thebing_System_Checks_ImportAccommodationCostCategory extends GlobalChecks {

	
	public function isNeeded(){
		global $user_data;

		if($user_data['name'] == 'admin' || $user_data['name'] == 'wielath') {
			return true;
		}

		return false;
	}
	


	public function executeCheck(){
		global $user_data, $system_data;

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_costs_kategorie_accommodation');
		Ext_Thebing_Util::backupTable('kolumbus_accommodations_costs_categories');
		Ext_Thebing_Util::backupTable('kolumbus_accommodations_salaries');
		Ext_Thebing_Util::backupTable('kolumbus_costprice_accommodation');

		$sSql = " SELECT * FROM `kolumbus_costs_kategorie_accommodation` WHERE `active` = 1 AND `school_id` > 0 ";
		$aResult = DB::getQueryData($sSql);

		$aTemp = array();

		foreach((array)$aResult as $aData){

			$oCategory = new Ext_Thebing_Accommodation_Cost_Category();
			$oCategory->school_id	= (int)$aData['school_id'];
			$oCategory->cost_type	= 'night';
			$oCategory->name		= $aData['name'];
			$oCategory->save();

			$aTemp[(int)$aData['id']] = $oCategory->id;
		}

		$sSql = " SELECT *, DATE(`created`) `created` FROM `customer_db_4` WHERE `active` = 1 ";
		$aResult = DB::getQueryData($sSql);

		foreach((array)$aResult as $aData){

			if($aTemp[(int)$aData['ext_79']] > 0){

				$sSql = 'SELECT * FROM `kolumbus_accommodations_salaries` WHERE `accommodation_id` = :accommodation_id AND `active` = 1';
				$aSql = array('accommodation_id' => (int)$aData['id']);
				$aFounds = DB::getPreparedQueryData($sSql, $aSql);

				if(empty($aFounds)){

					if($aData['created'] == '0000-00-00'){
						$aData['created'] = '2008-01-01';
					}

					$iOldCategory = (int)$aData['ext_79'];
					$iNewCategory = (int)$aTemp[(int)$aData['ext_79']];

					if($iOldCategory > 0){

						$aNewData = array();
						$aNewData['accommodation_id']	= (int)$aData['id'];
						$aNewData['costcategory_id']	= $iNewCategory;
						$aNewData['created']			= date('Y-m-d');
						$aNewData['valid_from']			= $aData['created'];
						DB::insertData('kolumbus_accommodations_salaries', $aNewData);

						$aPriceData = array('costkategorie_id' => $iNewCategory);
						DB::updateData('kolumbus_costprice_accommodation', $aPriceData, ' `costkategorie_id` = '.$iOldCategory);

					}
				}
			}
		}

		$sSql = "DROP TABLE `kolumbus_costs_kategorie_accommodation`";
		DB::executeQuery($sSql);

		return true;

	}

}
