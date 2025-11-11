<?php

namespace Ts\Proxy;

use Illuminate\Support\Arr;

/**
 * @property \Ext_Thebing_Tuition_Course $oEntity
 */
class Course extends \Ts\Proxy\AbstractProxy {
	
	protected $sEntityClass = \Ext_Thebing_Tuition_Course::class;

	public function getAccommodationCombinations() {
		return $this->oEntity->accommodation_combinations_joined;
	}

	/**
	 * return Ext_Thebing_Tuition_Course $oEntity->getFlexValues()
	 *
	 * @return array
	 */
	public function getFlexValues(): array {
		return $this->oEntity->getFlexValues();
	}

	/**
	 * return Ext_Thebing_Tuition_Course $oEntity->canBeOnline()
	 *
	 * @return bool
	 */
	public function canBeOnline(): bool {
		return $this->oEntity->canBeOnline();
	}

	/**
	 * return Ext_Thebing_Tuition_Course $oEntity->isHybrid()
	 *
	 * @return bool
	 */
	public function isHybrid(): bool {
		return $this->oEntity->isHybrid();
	}

	/**
	 * return Ext_Thebing_Tuition_Course $oEntity->getCategory()->getName()
	 *
	 * @return string
	 */
	public function getCategory(): string {
		return $this->oEntity->getCategory()->getName();
	}

	public function getCourseLanguage($language) {
		return $this->oEntity->getLevelgroup()->getName($language);
	}
	
	public function getPrice(\Ts\Proxy\Season $oSeason, $iWeek) {

		$oSchool = \Ext_Thebing_School::getInstance($this->oEntity->school_id);
		
		$oInquiry = new \Ext_TS_Inquiry();
		
		$oJourney = $oInquiry->getJoinedObjectChild('journeys');
		$oJourney->school_id = $oSchool->getId();
		$oJourney->productline_id = $oSchool->getProductLineId();

		$oInquiry->currency_id = $oSchool->currency;

		$oAmount = new \Ext_Thebing_Inquiry_Amount($oInquiry);

		$oInquiryCourse = $oJourney->getJoinedObjectChild('courses');
		$oInquiryCourse->course_id = $this->oEntity->id;
		$oInquiryCourse->from = $oSeason->getProperty('valid_from');
		$oInquiryCourse->until = $oSeason->getProperty('valid_until');
		$oInquiryCourse->weeks = $iWeek;
		$oInquiryCourse->units = Arr::first($this->oEntity->lessons_list);

		if($this->oEntity->per_unit != 1) {
			$oAmount->setTimeData($oInquiryCourse->from, $oInquiryCourse->until, $oInquiryCourse->weeks, 0);
		} else {
			$oAmount->setTimeData($oInquiryCourse->from, $oInquiryCourse->until, 0, $oInquiryCourse->units);
		}
		
		$iAmount = $oAmount->calculateCourseAmount($oInquiryCourse);

		return $iAmount;
	}
	
	public function getStartDatesWithDurations(\DateTime $dFrom, \DateTime $dUntil) {
		return $this->oEntity->getStartDatesWithDurations($dFrom, $dUntil);
	}
	
	public function getSchool() {

		$school = \Ext_Thebing_School::getInstance($this->oEntity->school_id);
		$schoolProxy = School::getInstance($school);
		
		return $schoolProxy;
	}

	public function getBookingTemplateKey(string $sCombinationKey): ?string {

		try {
			$oTemplate = \TsFrontend\Entity\BookingTemplate::firstOrCreateByCourse($sCombinationKey, $this->oEntity);
			return $oTemplate->key;
		} catch (\Throwable $e) {
			return null;
		}

	}
	
	public function getChildCoursesOrSameCourse() {
		
		$courses = $this->oEntity->getChildCoursesOrSameCourse();
		
		$courseProxies = [];
		foreach($courses as $course) {
			$courseProxy = \Ts\Proxy\Course::getInstance($course);
			$courseProxy->setLanguage($this->sLanguage);
			$courseProxies[] = $courseProxy;
		}
		
		return $courseProxies;
	}
	
}
