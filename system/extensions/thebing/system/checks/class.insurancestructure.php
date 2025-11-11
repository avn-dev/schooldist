<?php


class Ext_Thebing_System_Checks_InsuranceStructure extends GlobalChecks
{

	protected $_aErrors = array();


	public function getTitle()
	{
		$sTitle = 'Change Insurance Structure';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Convert insurances to new structure';
		return $sDescription;
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$bExistsBackupTable			= Ext_Thebing_Util::checkTableExists('__kolumbus_inquiries_insurances_backup');
		$bExistsOldInsuranceTable	= Ext_Thebing_Util::checkTableExists('kolumbus_inquiries_insurances');
		$bExistsNewInsuranceTable	= Ext_Thebing_Util::checkTableExists('ts_inquiries_journeys_insurances');
		$bExistsAllocationTable		= Ext_Thebing_Util::checkTableExists('ts_inquiries_journeys_insurances_to_travellers');

		/**
		 * Wenn das passiert, haben wir ein großes Problem
		 * -- hier sollten wir am besten nie reinkommen
		 */
		if(
			!$bExistsBackupTable &&
			!$bExistsOldInsuranceTable
		)
		{
			$this->_aErrors[] = array(
				'message'			=> 'NO OLD TABLE DATA FOUND',
				'backup_exists'		=> (int)$bExistsBackupTable,
				'old_exists'		=> (int)$bExistsOldInsuranceTable,
			);
			$this->_reportError();
			return true;
		}

		/**
		 * Wenn neue Tabelle existiert, wieder löschen, damit wir später wieder die alte unbenennen können
		 * -- beim x.mal sollten wir hier reinkommen
		 */
		if(
			$bExistsNewInsuranceTable
		)
		{
			$sSql = "DROP TABLE `ts_inquiries_journeys_insurances`";
			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
				return true;
			}
		}

		/**
		 * Alle Einträge in der Zwischentabelle entfernen
		 * -- beim x.mal sollten wir hier reinkommen
		 */
		if(
			$bExistsAllocationTable
		)
		{
			$sSql = "TRUNCATE `ts_inquiries_journeys_insurances_to_travellers`";
			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
				return true;
			}
		}

		/**
		 * Backuptabelle Namen in die alte Form bringen, damit alles so laufen kann wie beim ersten Checkdurchlauf
		 * -- beim x.mal sollten wir hier reinkommen
		 */
		if(
			$bExistsBackupTable &&
			!$bExistsOldInsuranceTable
		)
		{
			$sSql = "RENAME TABLE `__kolumbus_inquiries_insurances_backup` TO `kolumbus_inquiries_insurances`";
			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
				return true;
			}
		}

		//muss erneut überprüft werden, da eventuell die tabelle unbenannt wurde
		$bExistsBackupTable			= Ext_Thebing_Util::checkTableExists('__kolumbus_inquiries_insurances_backup');
		$bExistsOldInsuranceTable	= Ext_Thebing_Util::checkTableExists('kolumbus_inquiries_insurances');
		
		/**
		 * Wenn Backup immer noch existiert, dann ist $bExistsBackupTable && $bExistsOldInsuranceTable === true
		 * Dann Backuptabelle entfernen, es sollte immer nur einer der beiden Tabellen existieren
		 * -- hier sollten wir hoffentlich nie reinkommen
		 */
		if(
			$bExistsBackupTable
		)
		{
			$sSql = "DROP TABLE `__kolumbus_inquiries_insurances_backup`";
			$rRes = $this->_executeQuery($sSql);
			if(
				!$rRes
			)
			{
				$this->_reportError();
				return true;
			}
		}

		/**
		 * Backup erstellen und Tabelle unbenennen
		 * -- beim 1.mal & x.mal sollten wir hier reinkommen
		 */
		if(
			$bExistsOldInsuranceTable
		)
		{
			$bBackup = Ext_Thebing_Util::backupTable('kolumbus_inquiries_insurances', false, '__kolumbus_inquiries_insurances_backup');
			if(
				!$bBackup
			)
			{
				$this->_reportError();
				return true;
			}

			$sSql = "RENAME TABLE `kolumbus_inquiries_insurances` TO `ts_inquiries_journeys_insurances`";
			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
				return true;
			}

			$sSql = "
				ALTER TABLE
					`ts_inquiries_journeys_insurances` 
				ADD
					`journey_id` MEDIUMINT NOT NULL AFTER `inquiry_id`
			";
			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
				return true;
			}

			$sSql = "
				ALTER TABLE
					`ts_inquiries_journeys_insurances`
				ADD INDEX
					`journey_id` ( `journey_id` )
			";
			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
				return true;
			}
		}

		/**
		 * Zwischentabelle erstellen
		 */
		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_insurances_to_travellers` (
			  `journey_insurance_id` mediumint(9) NOT NULL,
			  `contact_id` mediumint(9) NOT NULL,
			  UNIQUE KEY `journey_insurance_contact` (`journey_insurance_id`,`contact_id`),
			  KEY `contact_id` (`contact_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		$rRes = $this->_executeQuery($sSql);

		if(
			!$rRes
		)
		{
			$this->_reportError();
			return true;
		}

		$sSql = "
			SELECT
				`kii`.`id` `inquiry_insurance_id`,
				`ts_i_j`.`id` `journey_id`,
				`tc_c`.`id` `traveller_id`
			FROM
				`ts_inquiries_journeys_insurances` `kii` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kii`.`inquiry_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_i_to_c`.`contact_id`
			GROUP BY
				`kii`.`id`
		";

		$aResult = $this->_getQueryRows($sSql);

		if(
			$aResult === false
		)
		{
			$this->_reportError();
			
			return true;
		}

		$aResult		= (array)$aResult;

		foreach($aResult as $aRowData)
		{
			$sWhere = 'id = '.(int)$aRowData['inquiry_insurance_id'];

			$aUpdate = array(
				'journey_id' => (int)$aRowData['journey_id']
			);

			$bSuccess = $this->_updateData('ts_inquiries_journeys_insurances', $aUpdate, $sWhere);

			if(
				$bSuccess
			)
			{
				$aInsertData = array(
					'journey_insurance_id'	=> (int)$aRowData['inquiry_insurance_id'],
					'contact_id'			=> (int)$aRowData['traveller_id']
				);

				$bSuccess2 = $this->_insertData('ts_inquiries_journeys_insurances_to_travellers', $aInsertData);
			}
		}

		$aErrors = (array)$this->_aErrors;
		if(
			!empty($aErrors)
		)
		{
			$this->_reportError();
		}
		else
		{
			$sSql = "
				ALTER TABLE
					`ts_inquiries_journeys_insurances`
				DROP INDEX
					`inquiry_id`
			";

			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
			}

			$sSql = "
				ALTER TABLE
					`ts_inquiries_journeys_insurances`
				DROP
					`inquiry_id`
			";

			$rRes = $this->_executeQuery($sSql);

			if(
				!$rRes
			)
			{
				$this->_reportError();
			}
		}

		return true;
	}

	/**
	 * Egal ob Debugmodus an oder nicht, alle Queryfehler abfangen
	 * @param <string> $sSql
	 * @return boolean 
	 */
	protected function _executeQuery($sSql)
	{
		$bSuccess = $this->_callDBFunc('executeQuery', array($sSql));

		return $bSuccess;
	}

	/**
	 * Egal ob Debugmodus an oder nicht, alle Queryfehler abfangen
	 * @param <string> $sSql
	 * @return boolean
	 */
	protected function _getQueryRows($sSql)
	{
		$bSuccess = $this->_callDBFunc('getQueryRows', array($sSql));

		return $bSuccess;
	}

	protected function _updateData($sTable, $aUpdate, $sWhere)
	{
		$bSuccess = $this->_callDBFunc('updateData', array((string)$sTable, (array)$aUpdate, (string)$sWhere));

		return $bSuccess;
	}

	protected function _insertData($sTable, $aInsert)
	{
		$bSuccess = $this->_callDBFunc('insertData', array((string)$sTable, (array)$aInsert));

		return $bSuccess;
	}

	protected function _callDBFunc($sFunc, $aParams)
	{
		$aError = array();

		try
		{
			$bSuccess = call_user_func_array(
					array('DB', $sFunc),
					$aParams
			);
		}
		catch(DB_QueryFailedException $e)
		{
			$aError['message'] = $e->getMessage();
			$bSuccess = false;
		}
		catch(Exception $e)
		{
			$aError['message'] = $e->getMessage();
			$bSuccess = false;
		}

		if(
			$bSuccess === false
		)
		{
			$aError['sql']		= $sSql;
			if(
				!isset($aError['message'])
			)
			{
				$aError['message'] = DB::fetchLastErrorMessage();
			}
			$this->_aErrors[]	= $aError;
		}

		return $bSuccess;
	}

	protected function _reportError()
	{
		$oMail = new WDMail();
		$oMail->subject = 'Inquiry Insurance Structure';

		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($this->_aErrors, 1)."\n\n";

		$oMail->text = $sText;

		$oMail->send(array('m.durmaz@thebing.com'));
	}
}