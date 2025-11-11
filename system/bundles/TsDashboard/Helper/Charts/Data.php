<?php

namespace TsDashboard\Helper\Charts;

class Data {

	private $sLanguage;
	
	/**
	 * @var \Ext_Thebing_School 
	 */
	private $oSchool;
	
	public function __construct(\Ext_Thebing_School $oSchool=null, $sLanguage='') {
		$this->sLanguage = $sLanguage;
		$this->oSchool = $oSchool;
	}
	
	public function __call($sName, array $aArguments) {
		
//		$sCacheKey = $this->getCacheKey($sName);
//		
//		$aData = \WDCache::get($sCacheKey);
//
//		if($aData === null) {
			
			$aData = call_user_func_array([$this, $sName], $aArguments);
			
//			\WDCache::set($sCacheKey, (60*60*24), $aData);
//
//		}

		return $aData;
	}

	protected function getCacheKey($sFunction) {

		$sCacheKey = 'TsDashboard\Charts_'.$this->sLanguage.'_'.$sFunction;

		if(
			$this->oSchool !== null &&
			$this->oSchool->exist()
		) {
			$sCacheKey .= '_'.$this->oSchool->id;
		}

		return $sCacheKey;
	}
	
	private function getConvertedEnquiries() {

		$aSql = [];
		$sWhere = "";

		if(
			$this->oSchool !== null &&
			$this->oSchool->exist()
		) {
			$sWhere .= " AND `ts_ij`.`school_id` = :school_id ";
			$aSql['school_id'] = $this->oSchool->id;
		}

		$sSql = "
			SELECT
				COUNT(`ts_i`.`id`)
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 AND
					`ts_i`.`confirmed` > 0 AND
					`ts_i`.`canceled` = 0
			WHERE
			    `ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_BOOKING." AND
			    `ts_i`.`active` = 1 AND
				DATE(`ts_i`.`converted`) BETWEEN (CURDATE() - INTERVAL 31 DAY) AND CURDATE()
				{$sWhere}
		";

//		$sSql = "
//			SELECT
//				*
//			FROM
//				`ts_enquiries` AS `ts_e` JOIN
//				(
//					`ts_enquiries_to_inquiries` AS `ts_e_to_i`		INNER JOIN
//					`ts_inquiries` AS `ts_i`						INNER JOIN
//					`ts_inquiries_journeys` AS `ts_ij`
//				) ON
//					`ts_e_to_i`.`enquiry_id`	= `ts_e`.`id`	AND
//					`ts_e_to_i`.`inquiry_id`	= `ts_i`.`id`	AND
//					`ts_i`.`active`				= 1				AND
//					`ts_ij`.`inquiry_id`		= `ts_i`.`id`	AND
//					`ts_ij`.`active`			= 1
//			WHERE
//				`ts_i`.`created` BETWEEN (NOW() - INTERVAL 31 DAY) AND NOW()
//		";
//
//		$aConvertedEnquiries = (array)\DB::getQueryRows($sSql, $aSql);

		$iCount = (int)\DB::getQueryOne($sSql, $aSql);

		return $iCount;

	}

	private function getStudentsAtSchool() {

		$aSql = [];
		$sWhere = "";

		if(
			$this->oSchool !== null &&
			$this->oSchool->exist()
		) {
			$sWhere .= " AND `ts_ij`.`school_id` = :school_id ";
			$aSql['school_id'] = $this->oSchool->id;
		}

		$sWhere .= \Ext_Thebing_System::getWhereFilterStudentsByClientConfig("`ts_i`");

		$sSql = "
			SELECT 
				COUNT(DISTINCT `ts_i`.`id`) `count`
			FROM
				`ts_inquiries` AS `ts_i` JOIN
				`ts_inquiries_journeys` AS `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				    `ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` AS `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 AND
					`ts_ijc`.`for_tuition` = 1
			WHERE
				`ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_BOOKING." AND
				`ts_i`.`active` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`canceled` = 0 AND
				`ts_ijc`.`from` <= CURDATE() AND
				`ts_ijc`.`until` >= CURDATE()
				{$sWhere}
		";

		$iCount = \DB::getQueryOne($sSql, $aSql);

		return $iCount;

	}

	private function getStudentsByNationality($iLimit=20) {

		$aSql = [];
		$sWhere = "";
		if(
			$this->oSchool !== null &&
			$this->oSchool->exist()
		) {
			$sWhere .= " AND `ts_ij`.`school_id` = :school_id ";
			$aSql['school_id'] = $this->oSchool->id;
		}

		$sWhere .= \Ext_Thebing_System::getWhereFilterStudentsByClientConfig("`ts_i`");

		$sSql = "
			SELECT
				`tc_c`.`nationality`,
				COUNT(DISTINCT `ts_i`.`id`) `count`
			FROM
				`ts_inquiries` AS `ts_i` INNER JOIN
				`ts_inquiries_journeys` AS `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` AS `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 AND
					`ts_ijc`.`for_tuition` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_ic` ON
					`ts_i`.`id` = `ts_ic`.`inquiry_id` AND
					`ts_ic`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_ic`.`contact_id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`canceled` = 0 AND
				`ts_ijc`.`from` <= CURDATE() AND
				`ts_ijc`.`until` >= CURDATE()
				{$sWhere}
			GROUP BY
				`tc_c`.`nationality`
			ORDER BY
				`count` DESC
		";


		$aResult = (array)\DB::getQueryPairs($sSql, $aSql);

		$aReturn = [];
		$aNationalities = \Ext_Thebing_Nationality::getNationalities(true, $this->sLanguage);
		foreach($aResult as $sNationality => $iCount) {
			// Fehlende oder falsche Nationalität durch Import
			if(!empty($aNationalities[$sNationality])) {
				$aReturn[$aNationalities[$sNationality]] = $iCount;
			}
		}

		// Überschüssige Einträge summieren und als letzten Eintrag setzen
		if(count($aReturn) > $iLimit) {
			$aSum = array_slice($aReturn, $iLimit-1);
			$aReturn = array_slice($aReturn, 0, $iLimit-1);
			$aReturn[\L10N::t('Andere', 'Framework')] = array_sum($aSum);
		}

		return $aReturn;
	}

	private function getEnquiryAndBookingStats() {

		$sSql = "
			SELECT 
				DATE_FORMAT(`ts_i`.`created`, '%Y-%m') `month`,
				COUNT(DISTINCT `ts_i`.`id`) `count`
			FROM
				`ts_inquiries` AS `ts_i` JOIN
				`ts_inquiries_journeys` AS `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1
			WHERE
				`ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_BOOKING." AND
				`ts_i`.`active` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`created` BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH, :format_from) AND DATE_FORMAT(LAST_DAY(CURDATE()), :format_until)
		";

		$aSql = [
			'format_from' => '%Y-%m-01 00:00:00',
			'format_until' => '%Y-%m-%d 23:59:59'
		];

		if(
			$this->oSchool !== null &&
			$this->oSchool->exist()
		) {
			$sSql .= " AND `ts_ij`.`school_id` = :school_id ";
			$aSql['school_id'] = $this->oSchool->id;
		}

		$sSql .= " GROUP BY
				`month`";

		$aInquiries = (array)\DB::getQueryPairs($sSql, $aSql);

		// TODO Querys vereinen
		$sSql = "
			SELECT 
				DATE_FORMAT(`ts_i`.`created`, '%Y-%m') `month`,
				COUNT(DISTINCT `ts_i`.`id`) `count`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
				    /* Typ des Journeys ist egal */
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`active` = 1
			WHERE
			    `ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_ENQUIRY." AND
				`ts_i`.`active` = 1 AND
				`ts_i`.`created` BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH, '%Y-%m-01 #format_from') AND DATE_FORMAT(LAST_DAY(CURDATE()), '%Y-%m-%d #format_until')
		";

		$aSql = [
			'format_from' => '00:00:00',
			'format_until' => '23:59:59'
		];
		
		if(
			$this->oSchool !== null &&
			$this->oSchool->exist()
		) {
			$sSql .= " AND `ts_ij`.`school_id` = :school_id ";
			$aSql['school_id'] = $this->oSchool->id;
		}

		$sSql .= " GROUP BY
				`month`";

		$aEnquiries = (array)\DB::getQueryPairs($sSql, $aSql);
		
		$aReturn = [];
		
		$oNow = new \Core\Helper\DateTime;
		$oNow->modify('first day of -5 month');
		for($i=0;$i<6;$i++) {

			$sMonth = $oNow->format('Y-m');
			$sMonthLabel = strftime('%b %Y', $oNow->getTimestamp());

			$aReturn[$sMonthLabel] = [
				'enquiries' => (int)$aEnquiries[$sMonth],
				'inquiries' => (int)$aInquiries[$sMonth]
			];
			
			$oNow->modify('first day of next month');
			
		}
		
		return $aReturn;
	}
	
}