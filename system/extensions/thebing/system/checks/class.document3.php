<?php
// Check korrigiert die importierten Rechnungen da beim standardimport
// date und created nicht mit geschrieben wurden

class Ext_Thebing_System_Checks_Document3 extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		if(
			$user_data['name'] == 'admin' ||
			$user_data['name'] == 'wielath'
		){
			return true;
		}

		return false;
	}

	public function executeCheck(){
		global $user_data, $system_data;
/*
		if(!hasRight('modules_admin')){
			$this->_aFormErrors[] = 'Only an full CMS Administrator hast the Right to start this Script!';
			return false;
		}
*/

		$oDb = DB::getDefaultConnection();

try{
		// Feld umbenennen
		$sSql = "ALTER TABLE
					`kolumbus_inquiries_documents_versions`
				CHANGE
					`date` `date`
				DATE NOT NULL ";

		$oDb->query($sSql);
}catch(Exception $e){

}

		$aTables = $oDb->listTables();

		$aDocTables = array();
		foreach((array)$aTables as $aTable){
			if(
				strpos($aTable, '_kolumbus_inquiries_documents') &&
				!strpos($aTable, 'positions') &&
				!strpos($aTable, 'backup') 
			){

				$aDocTables[] = $aTable;
			}
		}
		sort($aDocTables);

		$sBackupTable = (string)array_pop  ( $aDocTables );

		if($sBackupTable != ''){

			$sSql = "SELECT
							`kidv`.`id` `id`, `kidv`.`document_id` `document_id`
						FROM 
							`kolumbus_inquiries_documents_versions` `kidv`
						WHERE
							`kidv`.`date` = '0000-00-00' OR
							`kidv`.`created` = '0000-00-00'";
			$aSql = array();

			$aAllEmptyDateVersions = DB::getPreparedQueryData($sSql,$aSql);

			foreach((array)$aAllEmptyDateVersions as $aEmptyDateVersion){
				// Fehlende Infos aus der Backuptabelle holen
				$sSql = "SELECT
								`created`, `date`
							FROM
								`" . $sBackupTable . "`
							WHERE
								`id` = :document_id
						";
				$aSql					= array();
				$aSql['document_id']	= $aEmptyDateVersion['document_id'];
				$aInfos = DB::getQueryRow($sSql,$aSql);

				// Alle Leeren Versionen durchgehen und updaten
				$sSql = "UPDATE
								`kolumbus_inquiries_documents_versions` `kidv`
							SET
								`kidv`.`date` = :date,
								`kidv`.`created` = :created,
								`kidv`.`changed` = `kidv`.`changed`
							WHERE
								`kidv`.`document_id` = :document_id
							";
				$aSql					= array();
				$aSql['date']			= $aInfos['date'];
				$aSql['created']		= $aInfos['created'];
				$aSql['document_id']	= $aEmptyDateVersion['document_id'];

				DB::executePreparedQuery($sSql, $aSql);
			}


		}else{
			__pout('Fehler');
			return false;
		}

		return true;

	}

}


?>