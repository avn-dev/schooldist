<?php

namespace TsCompany\Entity;

use TsCompany\Entity\JobOpportunity\StudentAllocation;
use TsTuition\Entity\Course\Program\Service;

/**
 * Fake-WDBasic für die Gui
 */
class JourneyEmployment extends \Ext_TS_Inquiry_Journey_Course {

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$sLanguage = \Ext_TC_System::getInterfaceLanguage();

		$aSqlParts['select'] .= "
			, `ts_i`.`id` `inquiry_id`
			, UNIX_TIMESTAMP(`ts_i`.`canceled`) `canceled`				
			, `ts_i`.`number` `inquiry_number`
			, `ts_i`.`inbox` `inquiry_inbox`
			, `ts_i`.`agency_id` `inquiry_agency_id`
			, `kg`.`short` `inquiry_group_name`
			, `tc_c`.`firstname` `customer_firstname`
			, `tc_c`.`lastname` `customer_lastname`
			, `tc_c_n`.`number` `customer_number`			
			, `tc_c`.`birthday` `customer_birthday`
			, `tc_c`.`gender` `customer_gender`
			, `tc_c`.`nationality` `customer_nationality`
			, `ts_ijc`.`id` `inquiry_journey_course_id`
			, `ts_tcps`.`id` `program_service_id`
			, `ktc`.`name_".$sLanguage."` `course_name`
			, `ktc`.`name_".$sLanguage."` `course_name`
			, IF (`ts_tcps`.`from` IS NULL, `ts_ijc`.`from`, `ts_tcps`.`from`) `from`
			, IF (`ts_tcps`.`until` IS NULL, `ts_ijc`.`until`, `ts_tcps`.`until`) `until`
		";

		$aSqlParts['from'] .= " INNER JOIN
			`ts_tuition_courses_programs` `ts_tcp` ON
				`ts_tcp`.`id` = `ts_ijc`.`program_id` AND
				`ts_tcp`.`active` = 1 INNER JOIN
			`ts_tuition_courses_programs_services` `ts_tcps` ON
				`ts_tcps`.`program_id` = `ts_tcp`.`id` AND
				`ts_tcps`.`type` = '".Service::TYPE_COURSE."' AND
				`ts_tcps`.`active` = 1 AND
				(
					(
						`ts_tcps`.`from` IS NULL AND
						`ts_tcps`.`until` IS NULL
					) OR (
						`ts_tcps`.`from` <= :filter_until_1 AND 
						`ts_tcps`.`until` >= :filter_from_1
					)
				) INNER JOIN
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `ts_tcps`.`type_id` AND
				`ktc`.`per_unit` = ".\Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." AND
				`ktc`.`active` = 1 INNER JOIN
			`ts_inquiries_journeys` `ts_ij` ON
				`ts_ij`.`id` = `ts_ijc`.`journey_id` AND
				`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_ij`.`active` = 1 INNER JOIN
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`active` = 1 INNER JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c` ON
				`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_to_c`.`type` = 'traveller' INNER JOIN
			`tc_contacts` AS `tc_c`	ON
				`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
				`tc_c`.`active` = 1 INNER JOIN
			`tc_contacts_numbers` AS `tc_c_n`	ON
				`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id` AND
				`kg`.`active` = 1 LEFT JOIN
			`ts_companies` `ts_co` ON
				`ts_co`.`id` = `ts_i`.`agency_id` AND
				`ts_co`.`active` = 1 LEFT JOIN
			/* TODO --------------------------------------------------------------------*/	
			`ts_companies_job_opportunities_inquiries_courses` `ts_cjoic_allocated` ON
				`ts_cjoic_allocated`.`inquiry_course_id` = `ts_ijc`.`id` AND
				`ts_cjoic_allocated`.`program_service_id` = `ts_tcps`.`id` AND
				`ts_cjoic_allocated`.`status` & ".StudentAllocation::STATUS_ALLOCATED." AND
				`ts_cjoic_allocated`.`active` = 1 LEFT JOIN
			`ts_companies_job_opportunities_inquiries_courses` `ts_cjoic_requested` ON
				`ts_cjoic_requested`.`inquiry_course_id` = `ts_ijc`.`id` AND
				`ts_cjoic_requested`.`program_service_id` = `ts_tcps`.`id` AND
				`ts_cjoic_requested`.`status` & ".StudentAllocation::STATUS_REQUESTED." AND
				`ts_cjoic_requested`.`active` = 1
			/* /TODO -------------------------------------------------------------------*/		
		";

		$aSqlParts['groupby'] = "`ts_ijc`.`id`, `ts_tcps`.`id`";
		//$aSqlParts['groupby'] = "";

	}

}
