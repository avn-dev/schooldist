<?php

/**
 * Bei manchen Mandanten wurden invalide Datumseingaben gespeichert (z.B. '2013-00-23'),
 * wir müssen diese Datensätze per Check korrigieren, da sonst das indizieren nicht klappt
 * 
 * @author Mehmet Durmaz
 */
class Ext_TS_System_Checks_Document_Version_InvalidDate extends GlobalChecks
{
	public function getTitle()
	{
		return 'Check Documents';
	}

	public function getDescription()
	{
		return 'Check for invalid document date';
	}

	public function executeCheck()
	{
		$sSql = "
			SELECT 
				`kidv`.*
			FROM 
				`kolumbus_inquiries_documents_versions` `kidv` INNER JOIN 
				`kolumbus_inquiries_documents` `kid` ON 
					`kid`.`id` = `kidv`.`document_id` AND
					`kid`.`active` = 1
			WHERE 
				MONTH( `kidv`.`date` ) = '0' AND 
				`kidv`.`date` != '0000-00-00'	
		";
		
		$aErrors = array();
		
		$aRows = (array)DB::getQueryRows($sSql);

		foreach($aRows as $aRowData)
		{
			try
			{
				$oDateTime = new DateTime($aRowData['created']);

				if($oDateTime)
				{
						$sNewDate = $oDateTime->format('Y-m-d');

						$aUpdate = array(
							'date' => $sNewDate,
						);

						$sWhere = ' id = ' . $aRowData['id'];

						$rRes = DB::updateData('kolumbus_inquiries_documents_versions', $aUpdate, $sWhere);

						if(!$rRes)
						{
							$aErrors[] = array(
								'data' => $aRowData,
								'message' => 'UPDATE_FAILED',
							);
						}
				}
				else
				{
					$aErrors[] = array(
						'data' => $aRowData,
						'message' => 'DATETIME_PARSE_ERROR',
					);
				}	
			}
			catch(Exception $e)
			{
				$aErrors[] = array(
					'data' => $aRowData,
					'message' => $e->getMessage(),
				);
			}

		}
		
		if(empty($aErrors))
		{
			return true;
		}
		else
		{
			__pout($aErrors); 

			Ext_TC_Log::error('Ext_TS_System_Checks_Document_Version_InvalidDate', $aErrors);
			
			return false; 
		}
	}
}