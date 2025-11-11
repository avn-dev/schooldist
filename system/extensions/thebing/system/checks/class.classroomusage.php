<?php


class Ext_Thebing_System_Checks_ClassroomUsage extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('customer_db_2');

		$sSql = "
			SELECT
				*
			FROM
				`customer_db_2`
			WHERE
				`active` = 1
		";

		$sInsertString	= "
			INSERT INTO `kolumbus_classroom_usage`
			VALUES(:referer_school_id,:using_school_id)
		";

		$aSchools = (array)DB::getQueryRows($sSql);

		foreach($aSchools as $aSchoolData)
		{
			$iSchoolId			= $aSchoolData['id'];

			$sClassRoomUsage	= $aSchoolData['ext_325'];
			$aClassRoomUsage	= json_decode($sClassRoomUsage);

			if(!empty($aClassRoomUsage))
			{
				$aClassRoomUsage	= (array)$aClassRoomUsage;
				$aSql				= array();

				foreach($aClassRoomUsage as $iUsingSchool)
				{
					$aSql['referer_school_id']	= $iSchoolId;
					$aSql['using_school_id']	= $iUsingSchool;
					try
					{
						$rRes = DB::executePreparedQuery($sInsertString, $aSql);
						if(!$rRes)
						{
							//Fehler beim Speichern
							__pout('Der Json kodierte String '.$sClassRoomUsage.' konnte nicht in die Datenbank gespeichert werden');
							$oDb = DB::getDefaultConnection();
							__pout($oDb->getLastQuery());
						}
					}
					catch(DB_QueryFailedException $e)
					{
						__pout($e->getMessage());
					}
				}
			}
			else
			{
				if(
					strlen($sClassRoomUsage)>0 &&
					!is_array($aClassRoomUsage)
				)
				{
					__pout($sClassRoomUsage.' konnte nicht dekodiert werden');
				}
			}
		}

		$sSql = "
			ALTER TABLE
				`customer_db_2`
			DROP COLUMN
				`ext_325`
		";
		$rRes = DB::executeQuery($sSql);
		if(!$rRes)
		{
			__pout("ext_325 konnte nicht gelöscht werden");
			$oDb = DB::getDefaultConnection();
			__pout($oDb->getLastQuery());
		}

		return true;
	}

	public function getTitle()
	{
		return 'Classroom Usage';
	}

	public function getDescription()
	{
		return 'Convert classroom usages -> Clear classroom / school assignments.';
	}
}