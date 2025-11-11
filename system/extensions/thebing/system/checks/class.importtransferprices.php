<?php
class Ext_Thebing_System_Checks_ImportTransferPrices extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		return true;
		
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_days');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_from_accommodations_categories');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_from_accommodations_providers');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_from_locations');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_providers');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_saisons_costs');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_saisons_prices');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_to_accommodations_categories');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_to_accommodations_providers');
		Ext_Thebing_Util::truncateTable('kolumbus_transfers_packages_to_locations');

		$sSql = " SELECT
						`kpn`.*,
						COALESCE(
							(
								SELECT
									`value`
								FROM
									`kolumbus_prices_new`
								WHERE
									 REPLACE(
										`typeParent`,
										'transfer_airport_packet',
										''
									) =
									REPLACE(
										`kpn`.`typeParent`,
										'transfer_airport',
									'') AND
									`idSchool` = `kpn`.`idSchool` AND
									`idCurrency` = `kpn`.`idCurrency` AND
									`idSaison` = `kpn`.`idSaison`
								LIMIT 1
							), 0
						)`two_way_amount`
					FROM
						`kolumbus_prices_new` `kpn`
					WHERE
						`kpn`.`value` > 0 AND
						`kpn`.`idSchool` > 0 AND
						`kpn`.`idCurrency` > 0 AND
						`kpn`.`idSaison` > 0 AND
						`kpn`.`typeParent` LIKE '%transfer_airport%' AND
						`kpn`.`typeParent` NOT LIKE '%transfer_airport_packet%'";
		
		$aResult = DB::getQueryData($sSql);

		$aTransferData = array();

		foreach((array)$aResult as $aData){
			$aTransferData[$aData['idSchool']][$aData['idCurrency']][$aData['two_way']][$aData['value'].'_'.$aData['two_way_amount']][] = $aData;
		}

		$aPackageList = array();

		foreach((array)$aTransferData as $iSchoolId => $aData){
			$i = 0;
			foreach((array)$aData as $iCurrencyId => $aTypes){

				foreach((array)$aTypes as $iTwoWay => $aAmounts){

					foreach((array)$aAmounts as $mAmount => $aPrices){


						$aPackage = array();
						$aPackage['locations'] = array();
						$aPackage['categories'] = array();
						$aPackage['days'] = array();
						$aPackage['saisons'] = array();
						$aPackage['school_id'] = $iSchoolId;
						$aPackage['currency_id'] = $iCurrencyId;

						foreach((array)$aPrices as $aPrice){

							$bTwoWay = false;
							if(strpos('transfer_airport_packet', $aPrice['typeParent'])){
								$bTwoWay = true;
							}

							$sType = str_replace('transfer_airport_packet_', '', $aPrice['typeParent']);
							$sType = str_replace('transfer_airport_', '', $sType);

							$aTemp = explode('_', $sType);

							$aPackage['locations'][] = (int)$aTemp[0];
							$aPackage['categories'][] = (int)$aTemp[1];
							$aPackage['saisons'][] = (int)$aPrice['idSaison'];
							$aPackage['client_id'] = (int)$aPrice['idClient'];
							$aPackage['amount'] = (float)$aPrice['value'];
							$aPackage['amount_two_way'] = (float)$aPrice['two_way_amount'];
							$aPackage['name'] = 'Imported Package '.$i;
							$aDays = array(1,2,3,4,5,6,7,8);

							$aPackage['days'] = array_merge($aPackage['days'], $aDays);


						}

						$aPackage['saisons'] = array_unique($aPackage['saisons']);
						$aPackage['locations'] = array_unique($aPackage['locations']);
						$aPackage['categories'] = array_unique($aPackage['categories']);
						$aPackage['days'] = array_unique($aPackage['days']);

						$aPackageList[] = $aPackage;
						$i++;
						
					}
				}
			}
			
		}


		foreach($aPackageList as $aPackage){
			if(
				empty($aPackage['saisons']) ||
				empty($aPackage['locations']) ||
				empty($aPackage['categories']) ||
				empty($aPackage['days'])
			) {
				continue;
			}

			$oPackage = new Ext_Thebing_Transfer_Package();

			$oPackage->client_id = $aPackage['client_id'];
			$oPackage->school_id = $aPackage['school_id'];
			$oPackage->currency_id = $aPackage['currency_id'];
			$oPackage->name = $aPackage['name'];
			$oPackage->price_package = 1;
			$oPackage->time_from = '00:00:00';
			$oPackage->time_until = '23:59:59';
			$oPackage->amount_price = $aPackage['amount'];
			$oPackage->amount_price_two_way = $aPackage['amount_two_way'];

			$oPackage->join_from_accommodation_categories = $aPackage['categories'];
			$oPackage->join_to_accommodation_categories = $aPackage['categories'];
			$oPackage->join_from_locations = $aPackage['locations'];
			$oPackage->join_to_locations = $aPackage['locations'];
			$oPackage->join_days = $aPackage['days'];
			$oPackage->join_saisons_prices = $aPackage['saisons'];

			$oPackage->save();
		}

		return true;

	}

}