<?php

namespace TsGel\Api\Operations;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use TcApi\Client\Interfaces\Operation;
use TcApi\Client\Traits\ShouldQueue;
use TsGel\Handler\ExternalApp;

class SendBooking implements Operation
{
	use ShouldQueue;

	public function __construct(private \Ext_TS_Inquiry $inquiry) {}

	public function toArray(): array
	{
		return ['inquiry_id' => $this->inquiry->id];
	}

	public static function fromArray(array $data): Operation
	{
		$inquiry = \Ext_TS_Inquiry::query()->findOrFail($data['inquiry_id']);
		return new static($inquiry);
	}

	public function send(PendingRequest $request): ?Response
	{
		$traveller = $this->generateBody();
		return $request->put('/blank/studentUploadAPI/addMultipleNewUsers', [$traveller]);
	}

	private function generateBody(): array
	{
		if (!self::checkInquiry($this->inquiry)) {
			throw new \RuntimeException('Inquiry does not match external app settings');
		}

		$student = $this->inquiry->getTraveller();

		$data = [];
		$data['student_id'] = $student->getCustomerNumber();
		$data['first_name'] = $student->firstname;
		$data['last_name'] = $student->lastname;
		$data['nationality'] = $student->nationality;
		$data['date_of_birth'] = $student->birthday;
		$data['email'] = $student->getFirstEmailAddress()->email;
		$data['phone_number'] = $student->getFirstPhoneNumber();

		$visa = $this->inquiry->getVisaData();
		if ($visa && $visa->exist()) {
			$data['visa_expiry_date'] = $visa->date_until;
		}

		$data['school'] = $this->inquiry->getSchool()->short;
		$data['school_start_date'] = $this->inquiry->getFirstCourseStart();
		$data['school_end_date'] = $this->inquiry->getLastCourseEnd();

		$data['status'] = ($this->inquiry->isCancelled()) ? 'cancelled' : 'booked';

		$agency = $this->inquiry->getAgency();
		if ($agency) {
			$data['agent_id'] = $agency->getNumber();
			$data['agent'] = $agency->getName();
		}

		$attendance = \Ext_Thebing_Tuition_Attendance::getAttendanceForInquiry($this->inquiry->id, false);
		$data['attendance_percent'] = $attendance;

		$allocationIds = $this->inquiry->getTuitionAllocationIds();

		$data['classes'] = [];
		foreach ($allocationIds as $allocationId) {
			$allocation = \Ext_Thebing_School_Tuition_Allocation::getInstance($allocationId);
			$block = $allocation->getBlock();

			$class = $block->getClass();

			$weekPeriods = $block->createPeriodCollection();

			if ($weekPeriods->isEmpty()) {
				continue;
			}

			$blockStart = $weekPeriods[0]->start();
			$blockEnd = $weekPeriods[count($weekPeriods) - 1]->end();

			if (isset($data['classes'][$class->id])) {
				if ($blockStart < $data['classes'][$class->id]['class_from_date']) {
					$data['classes'][$class->id]['class_from_date'] = $blockStart;
				}
				if ($blockEnd > $data['classes'][$class->id]['class_to_date']) {
					$data['classes'][$class->id]['class_to_date'] = $blockEnd;
				}
			} else {
				$data['classes'][$class->id] = [
					'class_id' => $class->id,
					'class_name' => $class->getName(),
					'class_room' => $allocation->getRoom()->getName(),
					'class_teacher' => $block->getTeacher()->getName(),
					'class_from_date' => $blockStart,
					'class_to_date' => $blockEnd
				];
			}

		}

		$data['classes'] = array_map(function ($class) {
			$class['class_from_date'] = $class['class_from_date']->format('Y-m-d');
			$class['class_to_date'] = $class['class_to_date']->format('Y-m-d');
			return $class;
		}, $data['classes']);

		$data['classes'] = array_values($data['classes']);

		return $data;
	}



	public function handleResponse($response): void {

		// Flag setzen dass die Buchung bereits übertragen wurde
		$this->inquiry->setMeta('gel_sent', 1);

		$metadata = $this->inquiry->getJoinedObjectChilds(\WDBasic_Attribute::TABLE_KEY, true);

		foreach ($metadata as $metaObject) {
			if (!$metaObject->exist()) {
				$metaObject->save();
			}
		}
	}

	public static function checkInquiry(\Ext_TS_Inquiry $inquiry): bool {

		$alreadySent = (bool) $inquiry->getMeta('gel_sent', 0);

		if ($alreadySent) {
			// Buchung wurde bereits übertragen
			return true;
		}

		if (!in_array($inquiry->getSchool()->id, ExternalApp::getSchools())) {
			return false;
		}

		$bookingStatus = ExternalApp::getBookingStatus();
		if ($bookingStatus === 'confirmed' && !$inquiry->isConfirmed()) {
			return false;
		}

		$bookedCourseCategories = array_map(fn ($journeyCourse) => $journeyCourse->getCourse()->category_id , $inquiry->getCourses());

		if (empty(array_intersect(ExternalApp::getCourseCategories(), $bookedCourseCategories))) {
			return false;
		}

		$paymentSetting = ExternalApp::getPaymentState();

		if ($paymentSetting === 'full_payed') {
			$openAmount = $inquiry->getOpenPaymentAmount();
			if (!$inquiry->has_invoice || $openAmount > 0) {
				// Wenn es noch keine Rechnung oder noch ein Betrag offen ist nicht übertragen
				return false;
			}

		} else if ($paymentSetting === 'deposit') {
			$payedAmount = $inquiry->getTotalPayedAmount();
			if (!$inquiry->has_invoice || $payedAmount <= 0) {
				// Wenn es noch keine Rechnung oder noch keine Zahlung gibt nicht übertragen
				return false;
			}
		}

		return true;
	}

}