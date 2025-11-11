<?php

namespace TsContactLogin\Combination\Handler;

class Bookings extends HandlerAbstract {
	protected function handle(): void {

		$inquiry = $this->login->getInquiry();
		$school = $inquiry->getSchool();
		$schoolId = $school->id;
		$language = $this->login->getLanguage();
		$inquiryCourses = $inquiry->getCourses();
		$inquiryAccommodations = $inquiry->getAccommodations();
		$formatDate = new \Ext_Thebing_Gui2_Format_Date(false, $schoolId);

		/*
		 * Get Courses
		 */
		$formCourses = [];

		foreach ($inquiryCourses as $inquiryCourse) {

			$courseData = $inquiryCourse->getData();
			$formCourses[$courseData['course_id']] = [];
			$course = \Ext_Thebing_Tuition_Course::getInstance($courseData['course_id']);
			$level = \Ext_Thebing_Tuition_Level::getInstance($courseData['level_id']);

			$formCourses[$courseData['course_id']]['name'] = $course->getName($language);
			$formCourses[$courseData['course_id']]['level'] = $level->getName($language);

			$formCourses[$courseData['course_id']]['weeks'] = (int)$courseData['weeks'];
			$formCourses[$courseData['course_id']]['from'] = $formatDate->format($courseData['from']);
			$formCourses[$courseData['course_id']]['until'] = $formatDate->format($courseData['until']);
		}

		$this->assign('coursesData', $formCourses);

		/*
		 * Get Transfers
		 */
		$arrival = $inquiry->getTransfers('arrival');
		$departure	= $inquiry->getTransfers('departure');
		$additional = $inquiry->getTransfers('additional');

		$transferArrival = $inquiry->getTransferLocations('arrival');
		$transferDeparture = $inquiry->getTransferLocations('departure');
		$transferIndividual	= $inquiry->getTransferLocations();

		$transferData['arrival'] = [];
		$transferData['departure'] = [];
		$transferData['additional'] = [];

		if (is_object($arrival)) {
			$startId = (int)$arrival->start;
			$endId = (int)$arrival->end;
			$startId = $arrival->start_type . '_' . $startId;
			$endId = $arrival->end_type . '_' . $endId;

			$transferData['arrival']['pickup'] = $transferArrival[$startId];
			$transferData['arrival']['drop_off'] = $transferArrival[$endId];
			$transferData['arrival']['airline'] = $arrival->airline;
			$transferData['arrival']['flight_number'] = $arrival->flightnumber;
			$transferData['arrival']['date'] = $arrival->transfer_date;
			$transferData['arrival']['arrival_time'] = substr($arrival->transfer_time, 0 , 5);
			$transferData['arrival']['pickup_time'] = substr($arrival->pickup, 0 , 5);
		}

		if (is_object($departure)) {
			$startId = (int)$departure->start;
			$endId = (int)$departure->end;
			$startId = $departure->start_type . '_' . $startId;
			$endId = $departure->end_type . '_' . $endId;

			$transferData['departure']['pickup'] = $transferDeparture[$startId];
			$transferData['departure']['drop_off'] = $transferDeparture[$endId];
			$transferData['departure']['airline'] = $departure->airline;
			$transferData['departure']['flight_number'] = $departure->flightnumber;
			$transferData['departure']['date'] = $departure->transfer_date;
			$transferData['departure']['arrival_time'] = substr($departure->transfer_time, 0 , 5);
			$transferData['departure']['pickup_time'] = substr($departure->pickup, 0 , 5);
		}

		foreach ((array)$additional as $transfer) {
			$transferInfo = [];
			$startId				= (int) $transfer->start;
			$endId					= (int) $transfer->end;
			// Select IDs zusammensetzen
			$startId	= $transfer->start_type . '_' . $startId;
			$endId		= $transfer->end_type . '_' . $endId;

			$transferInfo['pickup'] = $transferIndividual[$startId];
			$transferInfo['drop_off'] = $transferIndividual[$endId];
			$transferInfo['airline'] = $transfer->airline;
			$transferInfo['flight_number'] = $transfer->flightnumber;
			$transferInfo['date'] = $transfer->transfer_date;
			$transferInfo['arrival_time'] = substr($transfer->transfer_time, 0 , 5);
			$transferInfo['pickup_time'] = substr($transfer->pickup, 0 , 5);
			$transferData['additional'][] = $transferInfo;
		}
		$this->assign('transferData', $transferData);

		/*
		 * Get Accommodations
		 */
		$formAccommodations = [];

		foreach ($inquiryAccommodations as $inquiryAccommodation) {
			$data = $inquiryAccommodation->getData();

			$formAccommodations[$data['id']] = [];
			$accommodation = \Ext_Thebing_Accommodation_Category::getInstance($data['accommodation_id']);
			$roomType = \Ext_Thebing_Accommodation_Roomtype::getInstance($data['roomtype_id']);
			$meal = \Ext_Thebing_Accommodation_Meal::getInstance($data['meal_id']);

			$formAccommodations[$data['id']]['name'] = $accommodation->getName($language);
			$formAccommodations[$data['id']]['room'] = $roomType->getName($language);
			$formAccommodations[$data['id']]['meal'] = $meal->getName($language);

			$formAccommodations[$data['id']]['weeks'] = (int)$data['weeks'];
			$formAccommodations[$data['id']]['from'] = $formatDate->format($data['from']);
			$formAccommodations[$data['id']]['until'] = $formatDate->format($data['until']);
		}

		$this->assign('accommodationsData', $formAccommodations);

		/*
		 * Get Insurances
		 */
		$insurances = $inquiry->getInsurances(true);

		$insuranceData = [];
		foreach ($insurances as $insurance) {
			$insuranceData[$insurance->id] = [
				'insurance' => $insurance->getInsuranceName(),
				'from' => $formatDate->format($insurance->from),
				'until' => $formatDate->format($insurance->until)
			];
		}

		$this->assign('insuranceDetails', $insuranceData);

		/*
		 * Get Activities
		 */
		$activities = $inquiry->getActivities();
		$data = [];
		foreach ($activities as $activity) {
			$data[$activity->id]['info'] = $activity->getInfo($language);
		}
		$this->assign('activityDetails', $data);
		$bookings = $this->login->getActiveInquiries();
		$this->assign('bookings', $bookings);
		$schools = \Ext_Thebing_Client::getSchoolList(true);
		$schoolIds = collect($this->login->getInquiries())->map(function ($inquiry) use ($schools) {
			return [$inquiry->getSchool()->id => $schools[$inquiry->getSchool()->id]];
		})->mapWithKeys(fn($a) => $a)->toArray();
		$this->assign('schools', $schoolIds);
		$this->assign('travellers', $this->login->getTravellers());

		$this->login->setTask('showBookingData');
	}
}