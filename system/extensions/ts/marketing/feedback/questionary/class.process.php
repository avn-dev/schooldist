<?php

use Communication\Interfaces\Model\HasCommunication;

class Ext_TS_Marketing_Feedback_Questionary_Process extends Ext_TC_Marketing_Feedback_Questionary_Process implements HasCommunication {
	
	protected string $uniqueKeyColumn = 'link_key';

	/**
	 * @inheritdoc
	 */
	public function __get($sName) {
		// (Leider) benötigt für Kommunikation und GUI-Dialog-Platzhalter
		if($sName === 'customer_name') {
			$oContact = $this->getInquiry()->getCustomer();
			return $oContact->getName();
		} else {
			return parent::__get($sName);
		}
	}

	/**
	 * @param array $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		parent::manipulateSqlParts($aSqlParts, $sView);

		$sLanguage = \System::getInterfaceLanguage();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = (int)$oSchool->id;

		$sJourneyJoinPart = '';
		if($iSchoolId > 0) {
			$sJourneyJoinPart = " AND `ts_ij`.`school_id` = ".$iSchoolId;
		}

		$aSqlParts['select'] .= ",
			`tc_c`.`firstname`,
			`tc_c`.`lastname`,
			IF(
				`tc_c`.`lastname` = '',
				`tc_c`.`firstname`,
				IF(
					`tc_c`.`firstname` = '',
					`tc_c`.`lastname`,
					CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`)
				)
			) `customer_name`,
			`tc_fq`.`name` as `questionary_name`,
			`tc_cn`.`number` `customer_number`,
			`kg`.`short` `group_short`,
			`kg`.`name` `group_name`,
			`ts_an`.`number` `agency_number`,
			`ka`.`ext_2` `agency_short`,
			`ka`.`ext_1` as `agency_full_name`,
			`tc_fqp`.`email`,
			`ts_i`.`service_from`,
			`ts_i`.`service_until`,
			`ts_ijc`.`from`,
			`ts_ijc`.`until`,
			`cdb2`.`ext_1` `school`,
			`tc_fqpn`.`editor_id`,
			`tc_fqpn`.`changed`,
			GROUP_CONCAT(CONCAT(`tc_f_q`.`dependency_on`, '{|}',`tc_fqpr`.`dependency_id`)) `dependency`,
			GROUP_CONCAT(DISTINCT `k_tc`.`name_short` SEPARATOR '{|}') `course_name`,
			IF(`dl_nationality`.`nationality_".$sLanguage."` != '', `dl_nationality`.`nationality_".$sLanguage."`, `dl_nationality`.`nationality_en`) `nationality`,
			getAge(`tc_c`.`birthday`) `age`
		";

		$aSqlParts['from'] .= "
			INNER JOIN `ts_inquiries_journeys` `ts_ij` ON
				`ts_ij`.`id` = `tc_fqp`.`journey_id` AND
				`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_ij`.`active` = 1 ".$sJourneyJoinPart." INNER JOIN
			`customer_db_2` `cdb2` ON
				`cdb2`.`id` = `ts_ij`.`school_id` AND
				`cdb2`.`active` = 1 INNER JOIN
			`ts_inquiries_to_contacts` `ts_itc` ON
				`ts_itc`.`inquiry_id` = `ts_ij`.`inquiry_id` AND
				`ts_itc`.`type` = 'traveller' INNER JOIN
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
				`ts_i`.`active` = 1 INNER JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_itc`.`contact_id` AND
				`tc_c`.`active` = 1 LEFT JOIN
			`data_languages` `dl_mother_tongue` ON
				`dl_mother_tongue`.`iso_639_1` = `tc_c`.`language` LEFT JOIN
			`data_countries` `dl_nationality` ON
				`dl_nationality`.`cn_iso_2` = `tc_c`.`nationality` LEFT JOIN
			`data_languages` `dl_corresponding_language` ON
				`dl_corresponding_language`.`iso_639_1` = `tc_c`.`corresponding_language` INNER JOIN
			`tc_feedback_questionaries` `tc_fq` ON
				`tc_fq`.`id` = `tc_fqp`.`questionary_id` AND
				`tc_fq`.`active` = 1 LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id` LEFT JOIN
			`ts_companies` `ka` ON
				`ka`.`id` = `ts_i`.`agency_id` AND
				`ka`.`active` = 1 LEFT JOIN
			`ts_companies_numbers` `ts_an` ON
				`ts_an`.`company_id` = `ka`.`id` LEFT JOIN
			`tc_feedback_questionaries_processes_results` `tc_fqpr` ON
				`tc_fqpr`.`questionary_process_id` = `tc_fqp`.`id` AND
				`tc_fqpr`.`active` = 1 LEFT JOIN
			`tc_feedback_questionaries_childs_questions_groups_questions` `tc_f_q_c_q_g_q` ON
				`tc_f_q_c_q_g_q`.`id` = `tc_fqpr`.`questionary_question_group_question_id` AND
				`tc_f_q_c_q_g_q`.`active` = 1 LEFT JOIN
			`tc_feedback_questions` `tc_f_q` ON
				`tc_f_q`.`id` = `tc_f_q_c_q_g_q`.`question_id` AND
				`tc_f_q`.`active` = 1 LEFT JOIN
			`tc_feedback_questionaries_processes_notices` `tc_fqpn` ON
				`tc_fqpn`.`questionary_process_id` = `tc_fqpr`.`questionary_process_id` AND
				`tc_fqpn`.`active` = 1 LEFT JOIN
			`ts_inquiries_journeys_courses` as `ts_ijc` ON
				`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
				`ts_ijc`.`active` = 1 LEFT JOIN
			`kolumbus_tuition_courses` `k_tc` ON
				`k_tc`.`id` = `ts_ijc`.`course_id` AND
				`k_tc`.`active` = 1 
		";

		$aSqlParts['groupby'] .= "
			`tc_fqp`.`id`
		";

	}

	/**
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry() {
		return $this->getJourney()->getInquiry();
	}

	public function getCommunicationAdditionalRelations(): array
	{
		return [
			$this->getInquiry()
		];
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \Ts\Communication\Application\FeedbackList::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $this->getJourney()->getInquiry()->getCommunicationLabel($l10n);
	}

	public function getCommunicationSubObject(): \Communication\Interfaces\Model\CommunicationSubObject
	{
		return $this->getJourney()->getInquiry()->getCommunicationSubObject();
	}

}
