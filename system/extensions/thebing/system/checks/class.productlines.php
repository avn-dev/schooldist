<?php

/**
 * Versucht zu allen getätigten Zahlungen, die NUR über eine Verrechnungskonto Transaktion verfügen
 * ein passendes Einnahmenkonto hinzu zu fügen.
 */
class Ext_Thebing_System_Checks_Productlines extends GlobalChecks
{

	public function getTitle()
	{
		$sTitle = 'Generate Productlines';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Generate default productline for each school';
		return $sDescription;
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_productlines_schools` (
			  `school_id` mediumint(8) NOT NULL,
			  `productline_id` mediumint(8) NOT NULL,
			  PRIMARY KEY  (`school_id`,`productline_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";

		DB::executeQuery($sSql);
		
		$this->_truncateTables();

		$sSelectAddon			= '';
		$iClientId				= (int)Ext_Thebing_System::getClientId();
		$aErrors				= array();

		$aColumns = DB::describeTable('customer_db_2');
		if(isset($aColumns['creator_id']))
		{
			$sSelectAddon .= ',`creator_id`';
		}

		$sSql = "
			SELECT
				`id`,
				`changed`,
				`created`,
				`user_id`,
				`ext_1`
				".$sSelectAddon."
			FROM
				`customer_db_2` `cdb2` LEFT JOIN
				`ts_productlines_schools` `ts_stp` ON
					`ts_stp`.`school_id` = `cdb2`.`id`
			WHERE
				`active` = 1 AND
				`idClient` = :client_id AND
				`ts_stp`.`school_id` IS NULL
			GROUP BY
				`cdb2`.`id`
		";

		$aSql = array(
			'client_id' => $iClientId
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		foreach($aResult as $aRowData)
		{
			$aInsertData = array(
				'created'	=> $aRowData['created'],
				'changed'	=> $aRowData['changed'],
				'editor_id'	=> (int)$aRowData['user_id']
			);

			if(
				isset($aRowData['creator_id'])
			){
				$aInsertData['creator_id'] = (int)$aRowData['creator_id'];
			}

			$iProductLine = (int)DB::insertData('tc_productlines', $aInsertData);

			if(
				$iProductLine > 0
			)
			{
				$sName = 'Productline '.$aRowData['ext_1'];

				$aInsertData = array(
					'productline_id'	=> $iProductLine,
					'language_iso'		=> 'en',
					'name'				=> $sName
				);

				$bSuccess = DB::insertData('tc_productlines_i18n', $aInsertData);

				if(
					$bSuccess === false
				)
				{
					$aErrors['productline_i18n'][] = $aRowData;

					$this->_deleteInsertedProductLine($iProductLine);
				}
				else
				{
					$aInsertData = array(
						'school_id'			=> (int)$aRowData['id'],
						'productline_id'	=> $iProductLine
					);

					$bSuccess = DB::insertData('ts_productlines_schools', $aInsertData);

					if(
						$bSuccess === false
					)
					{
						$aErrors['school_to_productline'][] = $aRowData;

						$this->_deleteInsertedProductLine($iProductLine);
					}
				}
			}
			else
			{
				$aErrors['productline'][] = $aRowData;
			}
		}

		if(
			!empty($aErrors)
		)
		{
			$oMail = new WDMail();
			$oMail->subject = 'Productline Error';

			$sText = '';
			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= date('Y-m-d H:i:s')."\n\n";
			$sText .= print_r($aErrors, 1)."\n\n";

			$oMail->text = $sText;

			$oMail->send(array('m.durmaz@thebing.com'));
		}

		return true;
	}

	protected function _deleteInsertedProductLine($iProductLine)
	{
		$aSql = array(
			'productline_id' => $iProductLine
		);
		
		$sSql = "
			DELETE FROM
				`tc_productlines`
			WHERE
				`id` = :productline_id
		";

		DB::executePreparedQuery($sSql, $aSql);
		
		$sSql = "
			DELETE FROM
				`tc_productlines_i18n`
			WHERE
				`productline_id` = :productline_id
		";
		
		DB::executePreparedQuery($sSql, $aSql);
	}
	
	protected function _truncateTables()
	{
		$aTables = array(
			'tc_productlines',
			'tc_productlines_i18n',
			'ts_productlines_schools',
		);
		
		$sSql = "TRUNCATE TABLE #table";
		
		foreach($aTables as $sTable)
		{
			if(Util::checkTableExists($sTable))
			{
				$aSql = array(
					'table' => $sTable,
				);
				
				DB::executePreparedQuery($sSql, $aSql);
			}
		}
	}
}
