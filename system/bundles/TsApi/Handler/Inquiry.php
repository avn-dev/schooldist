<?php

namespace TsApi\Handler;

use TsTuition\Service\CourseLessonsContingentService;

class Inquiry extends AbstractHandler {
	
	protected $type = \Ext_TS_Inquiry::TYPE_BOOKING;
	protected $typeString = \Ext_TS_Inquiry::TYPE_BOOKING_STRING;

	// Zentrale Definition der flex field usages (für Wiederverwendbarkeit).
	public const FLEX_FIELDS_USAGE = ['booking', 'enquiry_booking'];
	public const FLEX_CATEGORIES = ['student_record'];

	protected array $flexFieldsUsage = self::FLEX_FIELDS_USAGE;
	
	protected function getMapping(bool $bUpdate = false): array {
		
		$cRequired = function() use($bUpdate) {
			return $bUpdate ? '' : 'required';
		};
		
		$mapping = parent::getMapping($bUpdate);
		
		foreach($mapping as $fieldKey=>$apiField) {

			if($fieldKey == 'inbox') {
				$apiField->addValidation($cRequired());
			}
			
		}
		
		return $mapping;

	}
	
	#[\Override]
	public function buildInquiry(): \Ext_TS_Inquiry {

		$oEnquiry = new \Ext_TS_Inquiry();
		$oEnquiry->type = \Ext_TS_Inquiry::TYPE_BOOKING;
		$oEnquiry->currency_id = $this->oSchool->getCurrency();
		$oEnquiry->payment_method = 1;

		$oJourney = $oEnquiry->getJourney();
		$oJourney->school_id = $this->oSchool->id;
		$oJourney->productline_id = $this->oSchool->getProductLineId();
		$oJourney->type = \Ext_TS_Inquiry_Journey::TYPE_BOOKING;
		$this->objectsByAlias['ts_ij'] = $oJourney;
		return $oEnquiry;

	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function buildCourse(array $data): void {

		$course = collect($this->oSchool->getCourses())->firstWhere('id', $data['course_id']);
		$journeyChild = $this->objectsByAlias['ts_ij']->getJoinedObjectChild('courses');
		$journeyChild->course_id = $data['course_id'];
		$journeyChild->calculate = 1;
		$journeyChild->program_id = $course->getFirstProgram()->getId();
		$journeyChild->visible = 1;
		$journeyChild->for_tuition = 1;
		if (
			$data['lessons'] &&
			$course->canHaveLessons()
		) {
			$journeyChild->units = $data['lessons'];
			$contingent = $journeyChild->getJoinedObjectChild('lessons_contingents');
			$contingent->program_service_id = $journeyChild->program_id;
			(new CourseLessonsContingentService($contingent))->update(1);
		}
		$this->objectsByAlias['ts_ijc'][] = $journeyChild;

	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function buildAccommodation(array $data): void {

		$accommodationCombinations = $this->oSchool->getAccommodationMealCombinations();
		$journeyChild = $this->objectsByAlias['ts_ij']->getJoinedObjectChild('accommodations');
		$journeyChild->accommodation_id = $data['accommodation_id'];
		$journeyChild->roomtype_id = array_keys($accommodationCombinations[$journeyChild->accommodation_id])[0];
		$journeyChild->meal_id = $accommodationCombinations[$journeyChild->accommodation_id][$journeyChild->roomtype_id][0];
		$journeyChild->calculate = 1;
		$journeyChild->visible = 1;
		$journeyChild->for_matching = 1;
		$this->objectsByAlias['ts_ija'][] = $journeyChild;

	}

	/**
	 * @return void
	 */
	public function buildActivity(): void {

		$journeyChild = $this->objectsByAlias['ts_ij']->getJoinedObjectChild('activities');
		$journeyChild->blocks = 1;
		$this->objectsByAlias['ts_ijact'][] = $journeyChild;

	}

	/**
	 * @return void
	 */
	public function buildInsurance(): void {

		$journeyChild = $this->objectsByAlias['ts_ij']->getJoinedObjectChild('insurances');
		$journeyChild->visible = 1;
		$this->objectsByAlias['ts_iji'][] = $journeyChild;

	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function buildTransfer(array $data): void {

		$journeyChild = match($data['type']) {
			\Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE => $this->objectsByAlias['ts_ij']->getJoinedObjectChild('transfers_departure'),
			\Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL => $this->objectsByAlias['ts_ij']->getJoinedObjectChild('transfers_arrival'),
			\Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL => $this->objectsByAlias['ts_ij']->getJoinedObjectChild('transfers_additional'),
		};
		$journeyChild->transfer_type = $data['type'];
		$journeyChild->booked = 1;
		$this->objectsByAlias['ts_ijt'][] = $journeyChild;

	}

	/**
	 * Gemeinsame Paarbildung (Kategorien × Usages)
	 * @return list<array{0:string,1:string}>
	 */
	public static function sectionUsagePairs(): array
	{
		$out = [];
		foreach (self::FLEX_CATEGORIES as $cat) {
			foreach (self::FLEX_FIELDS_USAGE as $usage) {
				$out[] = [$cat, $usage];
			}
		}
		return $out;
	}

	/**
	 * @return \Ext_TC_Flexibility[]
	 */
	public static function fetchFlexFields(): array
	{
		$pairs = self::sectionUsagePairs();
		$fields = \Ext_TC_Flexibility::getSectionFieldData($pairs, true);
		return $fields;
	}
	
}
