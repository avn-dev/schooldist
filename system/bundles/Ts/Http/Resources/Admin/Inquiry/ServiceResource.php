<?php

namespace Ts\Http\Resources\Admin\Inquiry;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
	public function toArray($request)
	{
		$array = [];

		if ($this->resource instanceof \Ext_TS_Inquiry_Journey_Course) {
			$array = $this->buildCourseArray($this->resource);
			$array['type'] = 'course';
		} else if ($this->resource instanceof \Ext_TS_Inquiry_Journey_Accommodation) {
			$array = $this->buildAccommodationArray($this->resource);
			$array['type'] = 'accommodation';
		} else if ($this->resource instanceof \Ext_TS_Inquiry_Journey_Transfer) {
			$array = $this->buildTransferArray($this->resource);
			$array['type'] = 'transfer';
		} else if ($this->resource instanceof \Ext_TS_Inquiry_Journey_Insurance) {
			$array = $this->buildInsuranceArray($this->resource);
			$array['type'] = 'insurance';
		} else if ($this->resource instanceof \Ext_TS_Inquiry_Journey_Activity) {
			$array = $this->buildActivityArray($this->resource);
			$array['type'] = 'activity';
		}

		return $array;
	}

	private function buildCourseArray(\Ext_TS_Inquiry_Journey_Course $journeyCourse)
	{
		$format = new \Ext_Thebing_Gui2_Format_Date();
		$from = new \DateTime($journeyCourse->from);
		$until = new \DateTime($journeyCourse->until);

		$courseData = [];
		$courseData['icon'] = 'fas fa-chalkboard-teacher';
		$courseData['name'] = $journeyCourse->getCourse()->getName();
		$courseData['from'] = $format->formatByValue($from);
		$courseData['until'] = $format->formatByValue($until);
		$courseData['weeks'] = (int)$journeyCourse->weeks;

		return $courseData;
	}

	private function buildAccommodationArray(\Ext_TS_Inquiry_Journey_Accommodation $journeyAccommodation)
	{
		$category = $journeyAccommodation->getCategory();
		$roomType = $journeyAccommodation->getRoomType();
		$meal = $journeyAccommodation->getMeal();

		$description = [];
		if($category->exist()) $description[] = $category->getName();
		if($roomType->exist()) $description[] = $roomType->getName();
		if($meal->exist()) $description[] = $meal->getName();

		$format = new \Ext_Thebing_Gui2_Format_Date();
		$from = new \DateTime($journeyAccommodation->from);
		$until = new \DateTime($journeyAccommodation->until);

		$accommodationData = [];
		$accommodationData['icon'] = 'fa fa-home';
		$accommodationData['name'] = implode(', ', $description);
		$accommodationData['from'] = $format->formatByValue($from);
		$accommodationData['until'] = $format->formatByValue($until);
		$accommodationData['weeks'] = (int)$journeyAccommodation->weeks;

		return $accommodationData;
	}

	private function buildTransferArray(\Ext_TS_Inquiry_Journey_Transfer $journeyTransfer)
	{
		$transferTypes = \Ext_TS_Inquiry_Journey_Transfer::getTransferTypes(\System::getInterfaceLanguage());

		$transferData = [];
		$transferData['icon'] = 'fa fa-car';
		$transferData['transfer_type'] = $transferTypes[$journeyTransfer->transfer_type];
		$transferData['pick_up'] = $journeyTransfer->getStartLocation();
		$transferData['drop_off'] = $journeyTransfer->getEndLocation();

		if ($journeyTransfer->transfer_date !== '0000-00-00') {
			$date = Carbon::parse(sprintf('%s %s', $journeyTransfer->transfer_date, $journeyTransfer->transfer_time));
			$transferData['date'] = (new \Ext_Thebing_Gui2_Format_Date())->formatByValue($date);
			if (!empty($journeyTransfer->transfer_time)) {
				$transferData['time'] = (new \Ext_Thebing_Gui2_Format_Time())->formatByValue($date);
			}
		}

		$journey = $journeyTransfer->getJourney();

		if(
			(
				$journeyTransfer->transfer_type == $journeyTransfer::TYPE_ARRIVAL &&
				$journey->transfer_mode & $journey::TRANSFER_MODE_ARRIVAL
			) || (
				$journeyTransfer->transfer_type == $journeyTransfer::TYPE_DEPARTURE &&
				$journey->transfer_mode & $journey::TRANSFER_MODE_DEPARTURE
			)
		) {
			$transferData['booked'] = true;
		} else {
			if($journeyTransfer->transfer_type != 0) {
				$transferData['booked'] = false;
			}
		}

		return $transferData;
	}

	private function buildInsuranceArray(\Ext_TS_Inquiry_Journey_Insurance $journeyInsurance)
	{
		$format = new \Ext_Thebing_Gui2_Format_Date();
		$from = new \DateTime($journeyInsurance->from);
		$until = new \DateTime($journeyInsurance->until);

		$insuranceData = [];
		$insuranceData['icon'] = 'fa fa-shield';
		$insuranceData['name'] = $journeyInsurance->getInsurance()->getName();
		$insuranceData['from'] = $format->formatByValue($from);
		$insuranceData['until'] = $format->formatByValue($until);
		return $insuranceData;
	}

	private function buildActivityArray(\Ext_TS_Inquiry_Journey_Activity $journeyActivity)
	{
		$format = new \Ext_Thebing_Gui2_Format_Date();
		$from = new \DateTime($journeyActivity->from);
		$until = new \DateTime($journeyActivity->until);

		$activity = $journeyActivity->getActivity();

		$activityData = [];
		$activityData['icon'] = 'fa fa-bicycle';
		$activityData['name'] = $activity->getName();
		$activityData['from'] = $format->formatByValue($from);
		$activityData['until'] = $format->formatByValue($until);

		if($activity->isCalculatedPerBlock()) {
			$activityData['blocks'] = (int)$journeyActivity->blocks;
		} else {
			$activityData['weeks'] = (int)$journeyActivity->weeks;
		}

		return $activityData;
	}
}