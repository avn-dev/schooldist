<?
/*
 * Importiert die Provisionen
 */
class Ext_Thebing_System_Checks_ProvisionImport extends Ext_Thebing_System_ThebingCheck {

	public function isNeeded(){
		global $user_data;

		if( //$user_data['name'] == 'admin' ||
			//	$user_data['name'] == 'wielath' ||
				$user_data['name'] == 'clicred'
		){
			return true;
		}

		return false;
	}

	/*
	 * Check importiert die Transferdaten in die neue Struktur
	 */
	public function executeCheck(){
		global $user_data, $_VARS;

		Ext_Thebing_Util::backupTable('kolumbus_provision_new');


		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sSql = "SELECT
						*
					FROM
						`kolumbus_provision_new`
					WHERE
						`value` > 0
				";

		$aProvisions = DB::getQueryData($sSql);


		// Array mit allen Gruppen
		$aProvisionGroups = array();
		// Zusatzkosten Kurse
		$aAdditionalProvisionCourses = array();
		// Zusatzkosten Unterkünfte
		$aAdditionalProvisionAccommodations = array();
		// Zusatzkosten Accomm
		$aAdditionalProvisionAccommodations = array();
		// Zusatzkosten Transfer
		$aProvisionTransfer = array();

		/*
		// Array mit allen Unterkünften
		$aAllAccommodations = array();
		// Unterkünfte vorbereiten
		// Alle Unterkünfte holen
		$sSql = "SELECT
						*
					FROM
						`kolumbus_rooms_allocation`
					WHERE
						`active` = 1

				";
		$aAccommodations = DB::getQueryData($sSql);

		foreach((array)$aAccommodations as $aData){
			$aMeals = json_decode($aData['ext_6']);
			$aRooms = json_decode($aData['ext_5']);

			foreach((array)$aMeals as $iMealId){
				foreach((array)$aRooms as $iRoomId){
					$aAllAccommodations[$iMealId.'_'.$iRoomId][] = $aData['id'];
				}
			}
		}
		*/

		foreach((array)$aProvisions as $aProvision) {

			$bSave = true;

			if(isset($aProvisionGroups[$aProvision['agency_id']])){
				$oProvisionGroup = $aProvisionGroups[$aProvision['agency_id']];
			}else{
				// ClientId bestimmen
				$sSql = "SELECT
								`idClient`
							FROM
								`customer_db_2`
							WHERE
								`id` = :school_id
							LIMIT 1
							";
				$aSql = array();
				$aSql['school_id'] = (int)$aProvision['idSchool'];
				$aResult = DB::getPreparedQueryData($sSql, $aSql);

				// Agentur für Namen
				$oAgency = new Ext_Thebing_Agency($aProvision['agency_id']);
				$sAgencyName = $oAgency->ext_1;

				$oProvisionGroup = new Ext_Thebing_Provision_Group(0);
				$oProvisionGroup->active	= 1;
				$oProvisionGroup->client_id = (int)$aResult[0]['idClient'];
				$oProvisionGroup->name		= $sAgencyName . ' commission Group';
				$oProvisionGroup->user_id	= 0;
				$oProvisionGroup->save();

				// Agentur auch mit dieser Gruppe verknüpfen
				$oAgencyProvisionGroup = new Ext_Thebing_Agency_Provision_Group(0);
				$oAgencyProvisionGroup->active = 1;
				$oAgencyProvisionGroup->agency_id = (int)$oAgency->id;
				$oAgencyProvisionGroup->group_id = (int)$oProvisionGroup->id;
				$oAgencyProvisionGroup->description = 'commission';
				$oAgencyProvisionGroup->valid_from = '2008-01-01';
				$oAgencyProvisionGroup->valid_until = '0000-00-00';
				$oAgencyProvisionGroup->comment = 'commission import';
				$oAgencyProvisionGroup->save();

				$aProvisionGroups[$aProvision['agency_id']] = $oProvisionGroup;
			}


			// Neuer Provisionseintrag

			$aData = explode('_', $aProvision['parent_type']);

			$oProvision = new Ext_Thebing_Provision_Group_Provision(0);
			$oProvision->active = 1;
			$oProvision->group_id = (int)$oProvisionGroup->id;
			$oProvision->client_id = (int)$oProvisionGroup->client_id;
			$oProvision->school_id = (int)$aProvision['idSchool'];
			$oProvision->season_id = (int)$aProvision['saison_id'];
			$oProvision->provision = (float)$aProvision['value'];

			switch($aData[0]){
				case 'course':
					$oProvision->type = 'course';
					$oProvision->type_id = (int)$aProvision['parent_id'];

					// Kurskategory rausfinden
					$oCourse = new Ext_Thebing_Tuition_Course($aProvision['parent_id']);
					$oProvision->category_id = (int)$oCourse->getCategory()->id;
					break;
				case 'accommodation':
					$oProvision->type = 'accommodation';
					$oProvision->category_id = (int)$aData[1];

					// Unterkunfts ID rausfinden
					$iRoomId = (int)$aData[2];
					$iMealId = (int)$aData[3];

					$oProvision->additional_id = (int)$iRoomId;
					$oProvision->type_id = (int)$iMealId;

					break;
				case 'transfer':
					$oProvision->type = 'transfer';
					$oProvision->additional_id = (int)0;
					$oProvision->category_id = (int)0;

					// Hier muss der Transfer 3 mal gespeichert werden...
					for($i = 0; $i < 3; $i++){
						$aInfo = array();
						$aInfo['object'] = $oProvision;
						$aInfo['data'] = $i;
						$aProvisionTransfer[] = $aInfo;
					}

					// Provision hier nicht speichern!
					$bSave = false;
					break;
				case 'additional':

					// Zusatzkostenobjekt
					$oAdditionalCost = new Ext_Thebing_School_Additionalcost($aData[1]);

					switch($oAdditionalCost->type){
						
						case 0: // Kurszusatzkosten
							$oProvision->type = 'additional_course';
							$oProvision->category_id = (int)$aData[1];

							// Alle Kurse suchen die diese Kosten haben
							$sSql = "SELECT
											`customer_db_3_id` `id`
										FROM
											`kolumbus_costs_courses`
										WHERE
											`kolumbus_costs_id` = :cost_id
									";
							$aSql = array();
							$aSql['cost_id'] = (int)$aData[1];

							$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

							// Hier muss für jeden Kurs diese Provision gespeichert werden
							foreach((array)$aResult as $aTempData){
								$aInfo = array();
								$aInfo['object'] = $oProvision;
								$aInfo['data'] = $aTempData['id'];
								$aAdditionalProvisionCourses[] = $aInfo;
							}

							// Provision hier nicht speichern!
							$bSave = false;
							break;

						case 1: // Unterkunftszusatzkosten
							$oProvision->type = 'additional_accommodation';
							$oProvision->category_id = (int)$aData[1];

							// Alle Unterkunftskategorien suchen zu den Kosten
							$sSql = "SELECT
											`customer_db_8_id` `id`
										FROM
											`kolumbus_costs_accommodations`
										WHERE
											`kolumbus_costs_id` = :cost_id
									";
							$aSql = array();
							$aSql['cost_id'] = (int)$aData[1];

							$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

							// Hier muss für jede Kategorie die Provision gespeichert werden
							foreach((array)$aResult as $aTempData){
								$aInfo = array();
								$aInfo['object'] = $oProvision;
								$aInfo['data'] = $aTempData['id'];
								$aAdditionalProvisionAccommodations[] = $aInfo;
							}
							// Provision hier nicht speichern!
							$bSave = false;
							break;
						case 2: // generelle Zusatzkosten
							$oProvision->type = 'general';
							$oProvision->category_id = (int)0;
							$oProvision->type_id = (int)$aData[1];
							break;
					}
					break;
				default: continue;
			}

			if(
				$bSave &&
				$oProvision->group_id > 0 &&
				$oProvision->client_id > 0 &&
				$oProvision->school_id > 0 &&
				$oProvision->season_id > 0 &&
				$oProvision->type_id > 0 &&
				$oProvision->type != ''
			){
				$oProvision->save();
			}



		}

		$this->saveMultipleProvision($aProvisionTransfer);
		$this->saveMultipleProvision($aAdditionalProvisionCourses);
		$this->saveMultipleProvision($aAdditionalProvisionAccommodations);

		// Username löschen
		$sSql = "UPDATE
						`kolumbus_provision`
					SET
						`user_id` = 0
				";
		DB::executeQuery($sSql);

		// Username löschen
		$sSql = "UPDATE
						`kolumbus_provision_groups`
					SET
						`user_id` = 0
				";
		DB::executeQuery($sSql);

		return true;
	}


	public function saveMultipleProvision ($aDataArray){

		foreach((array)$aDataArray as $aData){
			$oProvision = $aData['object'];

			// Kopieren
			$oProvisionNew = new Ext_Thebing_Provision_Group_Provision(0);
			$oProvisionNew->active		= $oProvision->active;
			$oProvisionNew->group_id	= $oProvision->group_id;
			$oProvisionNew->client_id	= $oProvision->client_id;
			$oProvisionNew->school_id	= $oProvision->school_id;
			$oProvisionNew->season_id	= $oProvision->season_id;
			$oProvisionNew->provision	= $oProvision->provision;
			$oProvisionNew->type		= $oProvision->type;
			$oProvisionNew->category_id = $oProvision->category_id;


			$oProvisionNew->type_id = (int)$aData['data'];

			$oProvisionNew->save();

		}

	}

}