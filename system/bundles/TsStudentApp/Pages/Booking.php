<?php

namespace TsStudentApp\Pages;

use Carbon\Carbon;
use TsStudentApp\AppInterface;

class Booking extends AbstractPage {

	private $appInterface;

	private $inquiry;

	private $school;

	public function __construct(AppInterface $appInterface, \Ext_TS_Inquiry $inquiry, \Ext_Thebing_School $school) {
		$this->appInterface = $appInterface;
		$this->inquiry = $inquiry;
		$this->school = $school;
	}

	public function init(): array {
		return $this->refresh();
	}

	public function refresh(): array {

		$journeyCourses = $this->inquiry->getCourses(true);
		$journeyAccommodations = $this->inquiry->getAccommodations();
		$journeyTransfers = $this->inquiry->getTransfers('', false);
		$journeyInsurances = $this->inquiry->getInsurances(true);
		$journeyActivities = $this->inquiry->getActivities();

		$courses = array_map(fn ($journeyCourse) => $this->buildCourseArray($journeyCourse), $journeyCourses);
		$accommodations = array_map(fn ($journeyAccommodation) => $this->buildAccommodationArray($journeyAccommodation), $journeyAccommodations);
		$transfers = array_map(fn ($journeyTransfer) => $this->buildTransferArray($journeyTransfer), $journeyTransfers);
		$insurances = array_map(fn ($journeyInsurance) => $this->buildInsuranceArray($journeyInsurance), $journeyInsurances);
		$activities = array_map(fn ($journeyActivity) => $this->buildActivityArray($journeyActivity), $journeyActivities);

		$data = [
			'courses' => array_values($courses),
			'insurances' => array_values($insurances),
		];

		//if (version_compare($this->appInterface->getAppVersion(), '3.0', '<')) {
			$data['accommodations'] = array_values($accommodations);
			$data['transfers'] = array_values($transfers);
			$data['activities'] = array_values($activities);
		//} else {
		//		$data['timeline'] = $this->buildTimeline($accommodations, $activities, $transfers);
		//}

		$data['cancelled'] = $this->inquiry->isCancelled();

		return $data;
	}

	private function buildCourseArray(\Ext_TS_Inquiry_Journey_Course $journeyCourse) {

		$course = $journeyCourse->getCourse();

		$from = new \DateTime($journeyCourse->from);
		$until = new \DateTime($journeyCourse->until);

		$courseData = [];
		$courseData['icon'] = 'library-outline';
		$courseData['name'] = $course->getName($this->appInterface->getLanguage());
		$courseData['from'] = $this->appInterface->formatDate2($from, 'll');
		$courseData['until'] = $this->appInterface->formatDate2($until, 'll');
		$courseData['weeks'] = $this->buildDurationString((int)$journeyCourse->weeks);
		$courseData['date_time'] = $from->format('Y-m-d H:i:s');

		return $courseData;
	}

	private function buildAccommodationArray(\Ext_TS_Inquiry_Journey_Accommodation $journeyAccommodation) {

		$description = array();
		$category = $journeyAccommodation->getCategory();
		$roomType = $journeyAccommodation->getRoomType();
		$meal = $journeyAccommodation->getMeal();

		if($category->exist()) {
			$description[] = $category->getName($this->appInterface->getLanguage());
		}

		if($roomType->exist()) {
			$description[] = $roomType->getName($this->appInterface->getLanguage());
		}

		if($meal->exist()) {
			$description[] = $meal->getName($this->appInterface->getLanguage());
		}

		$from = new \DateTime($journeyAccommodation->from);
		$until = new \DateTime($journeyAccommodation->until);

		$accommodationData = [];
		$accommodationData['icon'] = 'home-outline';
		$accommodationData['name'] = implode(', ', $description);
		$accommodationData['from'] = $this->appInterface->formatDate2($from, 'll');
		$accommodationData['until'] = $this->appInterface->formatDate2($until, 'll');
		$accommodationData['weeks'] = $this->buildDurationString((int)$journeyAccommodation->weeks);
		$accommodationData['date_time'] = $from->format('Y-m-d H:i:s');

		return $accommodationData;
	}

	private function buildTransferArray(\Ext_TS_Inquiry_Journey_Transfer $journeyTransfer) {

		$transferTypes = \Ext_TS_Inquiry_Journey_Transfer::getTransferTypes($this->appInterface->getLanguageObject(), true);

		$transferData = [];
		$transferData['icon'] = 'car-outline';
		$transferData['type'] = $transferTypes[$journeyTransfer->transfer_type];
		$transferData['pick_up'] = $journeyTransfer->getStartLocation($this->appInterface->getLanguageObject());
		$transferData['drop_off'] = $journeyTransfer->getEndLocation($this->appInterface->getLanguageObject());

		if ($journeyTransfer->transfer_date !== '0000-00-00') {
			$date = Carbon::parse(sprintf('%s %s', $journeyTransfer->transfer_date, $journeyTransfer->transfer_time));
			$transferData['date'] = $this->appInterface->formatDate2($date, 'll');
			$transferData['date_time'] = $date->format('Y-m-d H:i:s');
			if (!empty($journeyTransfer->transfer_time)) {
				$transferData['time'] = $this->appInterface->formatDate2($date, 'LT');
			}
		}

		$journey = $this->inquiry->getJourney();

		if(
			(
				$journeyTransfer->transfer_type == $journeyTransfer::TYPE_ARRIVAL &&
				$journey->transfer_mode & $journey::TRANSFER_MODE_ARRIVAL
			) || (
				$journeyTransfer->transfer_type == $journeyTransfer::TYPE_DEPARTURE &&
				$journey->transfer_mode & $journey::TRANSFER_MODE_DEPARTURE
			)
		) {
			$transferData['booked'] = 1;
		} else {
			if($journeyTransfer->transfer_type != 0) {
				$transferData['booked'] = 0;
			}
		}

		return $transferData;
	}

	private function buildInsuranceArray(\Ext_TS_Inquiry_Journey_Insurance $journeyInsurance) {

		$insurance = $journeyInsurance->getInsurance();

		$from = new \DateTime($journeyInsurance->from);
		$until = new \DateTime($journeyInsurance->until);

		$insuranceData = [];
		$insuranceData['icon'] = 'shield-outline';
		$insuranceData['name'] = $insurance->getName($this->appInterface->getLanguage());
		$insuranceData['from'] = $this->appInterface->formatDate2($from, 'll');
		$insuranceData['until'] = $this->appInterface->formatDate2($until, 'll');
		$insuranceData['date_time'] = $from->format('Y-m-d H:i:s');
		return $insuranceData;
	}

	private function buildActivityArray(\Ext_TS_Inquiry_Journey_Activity $journeyActivity) {

		$activity = $journeyActivity->getActivity();

		$from = new \DateTime($journeyActivity->from);
		$until = new \DateTime($journeyActivity->until);

		$activityData = [];
		$activityData['icon'] = 'bicycle';
		$activityData['image'] = '/interface/image/activity/'.$journeyActivity->activity_id;
		$activityData['name'] = $activity->getName($this->appInterface->getLanguage());
		$activityData['from'] = $this->appInterface->formatDate2(new \DateTime($journeyActivity->from), 'll');
		$activityData['until'] = $this->appInterface->formatDate2(new \DateTime($journeyActivity->until), 'll');
		$activityData['date_time'] = $from->format('Y-m-d H:i:s');

		if($activity->isCalculatedPerBlock()) {
			$activityData['blocks'] = $this->buildBlockDurationString((int)$journeyActivity->blocks);
		} else {
			$activityData['weeks'] = $this->buildDurationString((int)$journeyActivity->weeks);
		}

		return $activityData;
	}

	private function buildTimeline(...$args)
	{
		$allServices = array_merge(...$args);

		usort($allServices, function($a, $b) {
			$date1 = new \DateTime($a['date_time']);
			$date2 = new \DateTime($b['date_time']);
			if($date1 > $date2){
				return 1;
			} else if($date1 < $date2){
				return -1;
			} else {
				return 0;
			}
		});

		return $allServices;
	}

	public function buildDurationString(int $weeks) {
		return ($weeks === 1)
			? $weeks.' '.$this->appInterface->t('week')
			: $weeks.' '.$this->appInterface->t('weeks');
	}

	public function buildBlockDurationString(int $blocks) {
		return ($blocks === 1)
			? $blocks.' '.$this->appInterface->t('block')
			: $blocks.' '.$this->appInterface->t('blocks');
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.booking.cancelled.title' => $appInterface->t('Booking cancelled'),
			'tab.booking.cancelled.content' => $appInterface->t('This booking has been cancelled.'),
			'tab.booking.courses' => $appInterface->t('Courses'),
			'tab.booking.accommodations' => $appInterface->t('Accommodations'),
			'tab.booking.transfers' => $appInterface->t('Transfer'),
			'tab.booking.insurances' => $appInterface->t('Insurances'),
			'tab.booking.activities' => $appInterface->t('Activities'),
			'tab.booking.dates' => $appInterface->t('Dates'),
			'tab.booking.duration' => $appInterface->t('Duration'),
			'tab.booking.not_booked' => $appInterface->t('Not booked'),
			'tab.booking.transfer.date' => $appInterface->t('Date'),
			'tab.booking.transfer.yes' => $appInterface->t('Booked'),
			'tab.booking.transfer.no' => $appInterface->t('Not booked'),
		];
	}

}
