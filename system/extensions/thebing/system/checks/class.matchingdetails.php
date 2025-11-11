<?php 

class Ext_Thebing_System_Checks_MatchingDetails extends GlobalChecks {
	
	public function executeCheck(){
		global $user_data, $system_data;

		// Alle Clients
		$sSql = "SELECT id FROM kolumbus_clients WHERE active = 1";
		$aClients = DB::getQueryData($sSql);
		
		foreach((array)$aClients as $aClient){
			// Alle Schulen für diesen Client
			$sSql = "SELECT id FROM customer_db_2 WHERE active = 1 AND idClient = " . (int)$aClient['id'];
			$aSchools = DB::getQueryData($sSql);
	
			foreach((array)$aSchools as $aSchool){

				$sSql =	"SELECT 
							*
						FROM 
							`customer_db_4`
						WHERE 
							`idClient` = :client AND 
							`ext_2` = :idSchool AND 
							`active` = 1
						";
				$aSql = array();
				$aSql['client'] = (int) $aClient['id'];
				$aSql['idSchool'] = (int) $aSchool['id'];
				
				$aFamilyInfo = DB::getPreparedQueryData($sSql,$aSql);
				// jede Familie durchgehen
				foreach((array)$aFamilyInfo as $aFamily){
					
					$aSql = array();
					$aSql['id'] = $aFamily['id'];
					
					// Beschreibung
					if(isset($aFamily['ext_73']) && !empty($aFamily['ext_73'])){
						$aSql['info'] = $aFamily['ext_73'];
						// Jede Systemsprache durchgehen
						foreach((array)$system_data['allowed_languages'] as $sLang=>$sLangName){
							if(isset($aFamily['family_description_' . $sLang]) && empty($aFamily['family_description_' . $sLang])){
								$sSql = "UPDATE `customer_db_4` SET `family_description_" . $sLang . "` = :info WHERE `id` = :id";
								DB::executePreparedQuery($sSql,$aSql);
								break;
							}else{
								break;
							}
						}
					}
					
					// Weg
					if(isset($aFamily['ext_74']) && !empty($aFamily['ext_74'])){
						$aSql['way'] = $aFamily['ext_74'];
						// Jede Systemsprache durchgehen
						foreach((array)$system_data['allowed_languages'] as $sLang=>$sLangName){
							if(isset($aFamily['way_description_' . $sLang]) && empty($aFamily['way_description_' . $sLang])){
								$sSql = "UPDATE `customer_db_4` SET `way_description_" . $sLang . "` = :way WHERE `id` = :id";
								DB::executePreparedQuery($sSql,$aSql);
								break;
							}else{
								break;
							}
						}
					}
					
					// Generell
					if(isset($aFamily['ext_75']) && !empty($aFamily['ext_75'])){
						$aSql['general'] = $aFamily['ext_75'];
						// Jede Systemsprache durchgehen
						foreach((array)$system_data['allowed_languages'] as $sLang=>$sLangName){
							if(isset($aFamily['additional_information_' . $sLang]) && empty($aFamily['additional_information_' . $sLang])){
								$sSql = "UPDATE `customer_db_4` SET `additional_information_" . $sLang . "` = :general WHERE `id` = :id";
								DB::executePreparedQuery($sSql,$aSql);
								break;
							}else{
								break;
							}
						}
					}
					
				}

				}
				
			}

		return true;
		
	}
	
}


?>