<?php


class Ext_Thebing_System_Checks_CancellationDynamicConvert extends GlobalChecks
{

	public function getTitle() {
		$sTitle = 'Convert Cancellation';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Import of dynamic cancellation types.';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_cancellation_fees');
		Ext_Thebing_Util::backupTable('kolumbus_cancellation_fees_dynamic');

		$aColumns = DB::describeTable('kolumbus_cancellation_fees');

		if(!isset($aColumns['fee_value']))
		{
			return true;
		}

		$sSql = "
			SELECT
				`id`,
				`fee_value`,
				`fee_type`
			FROM
				`kolumbus_cancellation_fees`
			WHERE
				`active` = 1 AND
				`fee_value` > 0
		";

		$aResult	= DB::getQueryRows($sSql);
		$aErrors	= array();
		$aConverted	= array();

		foreach($aResult as $aRowData)
		{
			$iCancellationId	= $aRowData['id'];
			$iFeeValue			= $aRowData['fee_value'];
			$iFeeType			= $aRowData['fee_type'];

			$aInsertData		= array(
				'cancellation_fee_id'	=> $iCancellationId,
				'amount'				=> $iFeeValue,
				'kind'					=> $iFeeType,
				'type'					=> 'all'
			);

			try
			{
				$mInsertId		= DB::insertData('kolumbus_cancellation_fees_dynamic', $aInsertData);
				$aConverted[]	= $mInsertId;
			}
			catch(DB_QueryFailedException $e)
			{
				$mInsertId = $e->getMessage();
			}

			if(!is_numeric($mInsertId))
			{
				$aErrors[] = 'couldnt convert cancellation_id:"'.$iCancellationId.'",'.$mInsertId;
			}
			
		}

		if(empty($aErrors))
		{
			$sSql = "ALTER TABLE `kolumbus_cancellation_fees` DROP `fee_value`";
			try
			{
				$rResult = DB::executeQuery($sSql);
			}
			catch(DB_QueryFailedException $e)
			{
				$rResult = false;
			}

			if(!$rResult)
			{
				$aErrors[] = 'couldnt drop column "fee_value"';
			}

			$sSql = "ALTER TABLE `kolumbus_cancellation_fees` DROP `fee_type`";
			try
			{
				$rResult = DB::executeQuery($sSql);
			}
			catch(DB_QueryFailedException $e)
			{
				$rResult = false;
			}

			if(!$rResult)
			{
				$aErrors[] = 'couldnt drop column "fee_type"';
			}
		}

		if(!empty($aErrors))
		{
			$oMail = new WDMail();
			$oMail->subject = "Fehler Cancellation Import";

			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= date('YmdHis')."\n\n";
			$sText .= 'Errors: '.print_r($aErrors,1)."\n\n";
			$sText .= 'Converted_ids: '.implode(',',$aConverted)."\n\n";

			$oMail->text = $sText;
			$oMail->send(array('m.durmaz@thebing.com'));
		}

		return true;
	}

}