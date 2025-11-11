<?php

namespace TsCompany\Entity\JobOpportunity;

use Core\Traits\BitwiseFlag;
use Ts\Interfaces\Entity\DocumentRelation;
use Ts\Traits\Entity\HasDocuments;
use TsCompany\Entity\Company;
use TsCompany\Entity\JobOpportunity;
use Tc\Traits\Communication\FlagNotify;

class StudentAllocation extends \Ext_Thebing_Basic {
	use BitwiseFlag, FlagNotify, HasDocuments;

	const STATUS_REQUESTED = 1;

	const STATUS_CONFIRMED = 2;

	const STATUS_ALLOCATED = 4;

	protected $_sTable = 'ts_companies_job_opportunities_inquiries_courses';

	protected $_sTableAlias = 'ts_cjoic';

	protected $_sPlaceholderClass = \TsCompany\Service\Placeholder\JobOpportunity\StudentAllocation::class;

	protected $_aJoinedObjects = [
		'inquiry_course' => [
			'class' => \Ext_TS_Inquiry_Journey_Course::class,
			'key' => 'inquiry_course_id',
			'type' => 'parent',
			'readonly' => true
		],
		'job_opportunity' => [
			'class' => JobOpportunity::class,
			'key' => 'job_opportunity_id',
			'type' => 'parent',
			'readonly' => true
		]
	];

	/**
	 * @return JobOpportunity
	 */
	public function getJobOpportunity(): JobOpportunity {
		return $this->getJoinedObject('job_opportunity');
	}

	/**
	 * @return Company
	 */
	public function getCompany(): Company {
		return $this->getJobOpportunity()->getCompany();
	}

	/**
	 * @return \Ext_TS_Inquiry_Journey_Course
	 */
	public function getInquiryCourse(): \Ext_TS_Inquiry_Journey_Course {
		return $this->getJoinedObject('inquiry_course');
	}

	/**
	 * @return \Ext_TS_Inquiry
	 */
	public function getInquiry(): \Ext_TS_Inquiry {
		return $this->getInquiryCourse()->getJourney()->getInquiry();
	}

	/**
	 * @return \Ext_Thebing_Currency|int
	 */
	public function getCurrency() {
		return $this->getInquiry()->getCurrency(false);
	}

	public function getDocumentLanguage() {
		return $this->getInquiry()->getDocumentLanguage();
	}

	/**
	 * @return \Ext_Thebing_School|null
	 */
	public function getSchool() {
		return $this->getInquiry()->getSchool();
	}

	/**
	 * @return int
	 */
	public function getSubObject() {
		return $this->getInquiry()->getSchool()->id;
	}

	/**
	 * @param string $type
	 * @return string|null
	 */
	public function getCorrespondenceLanguage(string $type = 'customer') {

		if ($type === 'company') {
			return $this->getJobOpportunity()->getCompany()->getLanguage();
		} else if ($type === 'customer') {
			return $this->getInquiry()->getCorrespondenceLanguage($type);
		}

		return null;
	}

	/**
	 * Ist die Zuweisung angefragt?
	 *
	 * @return bool
	 */
	public function isRequested(): bool {
		return $this->hasFlag('status', self::STATUS_REQUESTED);
	}

	/**
	 * Ist die Zuweisung bestätigt?
	 *
	 * @return bool
	 */
	public function isConfirmed(): bool {
		return $this->hasFlag('status', self::STATUS_CONFIRMED);
	}

	/**
	 * Ist die Zuweisung final zugewiesen?
	 *
	 * @return bool
	 */
	public function isAllocated(): bool {
		return $this->hasFlag('status', self::STATUS_ALLOCATED);
	}

	/**
	 * Als "Angefragt" markieren
	 *
	 * @return $this
	 */
	public function request() {
		$this->addFlag('status', self::STATUS_REQUESTED);
		return $this;
	}

	/**
	 * Als "Bestätigt" markieren
	 *
	 * @return $this
	 */
	public function confirm() {
		$this->removeFlag('status', self::STATUS_REQUESTED);
		$this->addFlag('status', self::STATUS_CONFIRMED);
		return $this;
	}

	/**
	 * Als "Zugewiesen" markieren
	 *
	 * @return $this
	 */
	public function allocate() {
		$this->removeFlag('status', self::STATUS_CONFIRMED);
		$this->addFlag('status', self::STATUS_ALLOCATED);
		return $this;
	}

	public function setCommunicationFlags(array $saveFlags, array $email, \Ext_TC_Communication_Message $message) {

		if (isset($saveFlags['job_opportunity_requested'])) {
			$this->request();
		}

		if (isset($saveFlags['job_opportunity_allocated'])) {
			$this->allocate();
		}

		if ($this->status != $this->getOriginalData('status')) {
			$this->save();
		}

	}

	public function getAllStudentAllocations(): array {
		return self::getRepository()->findBy(['inquiry_course_id' => $this->inquiry_course_id, 'program_service_id' => $this->program_service_id]);
	}

	public function manipulateSqlParts(&$sqlParts, $view = null) {

		$sqlParts['select'] .= "
			, `ts_cjoic`.`status` `job_opportunity_status`
		";

		$withJobOpportunityData = function (&$sqlParts) {
			$sqlParts['select'] .= "	
				, `ts_cjo`.`name` `job_opportunity_name`
				, `ts_cjo`.`industry_id` `job_opportunity_industry_id`
				, `ts_cjo`.`wage` `job_opportunity_wage`
				, `ts_cjo`.`wage_per` `job_opportunity_wage_per`
				, `ts_cjo`.`hours` `job_opportunity_hours`
				, `ts_cjo`.`hours_per` `job_opportunity_hours_per`
				, `ts_co`.`ext_1` `company_name`
			";

			$sqlParts['from'] .= " INNER JOIN
				`ts_companies_job_opportunities` `ts_cjo` ON
					`ts_cjo`.`id` = `ts_cjoic`.`job_opportunity_id` INNER JOIN
				`ts_companies` `ts_co` ON
					`ts_co`.`id` = `ts_cjo`.`company_id` INNER JOIN
				`ts_companies_industries` `ts_coi` ON
					`ts_coi`.`id` = `ts_cjo`.`industry_id`
			";
		};

		$withInquiryData = function (&$sqlParts) {

			$sqlParts['select'] .= "
				, `ts_i`.`id` `inquiry_id`
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
				, IF (`ts_tcps`.`from` IS NULL, `ts_ijc`.`from`, `ts_tcps`.`from`) `student_allocation_from`
				, IF (`ts_tcps`.`until` IS NULL, `ts_ijc`.`until`, `ts_tcps`.`until`) `student_allocation_until`
			";

			$sqlParts['from'] .= " INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`id` = `ts_cjoic`.`inquiry_course_id` AND
					`ts_ijc`.`active` = 1 INNER JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`id` = `ts_cjoic`.`program_service_id` AND 
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
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijc`.`journey_id` AND 
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
					`ts_i`.`active` = 1	INNER JOIN
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
				`ts_companies` `ts_ca` ON
					`ts_ca`.`id` = `ts_i`.`agency_id` AND
					`ts_ca`.`active` = 1					
			";

		};

		if ($view === 'journey_employments') {
			$withJobOpportunityData($sqlParts);
		} else if ($view === 'job_opportunities') {
			$withInquiryData($sqlParts);
		}

		$sqlParts['groupby'] = "`ts_cjoic`.`id`";

	}

}
