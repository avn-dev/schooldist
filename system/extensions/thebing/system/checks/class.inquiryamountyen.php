<?php


class Ext_Thebing_System_Checks_InquiryAmountYen extends GlobalChecks {

	public function isNeeded(){

		$sServerUrl = $_SERVER['HTTP_HOST'];

		$bMatch = preg_match('/eurasia-institute.thebing.com/', $sServerUrl);

		if($bMatch){
			return true;
		}else{
			return false;
		}

	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		$sSql = "
			UPDATE
				`kolumbus_inquiries`
			SET
				`idCurrency` = 25,
				`changed` = `changed`
			WHERE
				`crs_partnerschool` = 133 AND
				`office` = 60 AND
				`active` = 1
		";

		DB::executeQuery($sSql);

		return true;
	}

	public function  getTitle() {
		return 'Inquiry Amounts';
	}

	}
