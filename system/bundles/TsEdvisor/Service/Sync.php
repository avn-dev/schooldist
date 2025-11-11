<?php

namespace TsEdvisor\Service;

use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use TcExternalApps\Interfaces\ExternalApp;

class Sync
{
	public function syncEnrollment(int $enrollmentId)
	{
		/** @var \Ext_TS_Inquiry $inquiry */
		$inquiry = \Ext_TS_Inquiry::query()
			->select('ts_i.*')
			->join('wdbasic_attributes as a', function (JoinClause $join) use ($enrollmentId) {
				$join
					->on('a.entity_id', 'ts_i.id')
					->where('a.entity', (new \Ext_TS_Inquiry)->getTableName())
					->where('a.key', 'edvisor_id')
					->where('a.value', $enrollmentId);
			})
			->first();

		if (!$inquiry) {
			$this->createEnrollment($enrollmentId);
		} else {
			$this->updateEnrollment($enrollmentId, $inquiry);
		}
	}

	private function createEnrollment(int $enrollmentId)
	{
		$enrollment = Api::default()->queryEnrollment($enrollmentId);
	
		$school = $this->findSchool($enrollment['schoolId']);
		
		// Setzen für spätere Übersetzungen (kein Kontext vorher)
		\System::setInterfaceLanguage($school->getLanguage());
		
		$agency = $this->findOrCreateAgency($enrollment['agency']);
	
		$inbox = \Ext_Thebing_Client_Inbox::getInstance(\System::d(\TsEdvisor\Handler\ExternalApp::CONFIG_INBOX));
		$created = Carbon::parse($enrollment['created']);

		$inquiry = new \Ext_TS_Inquiry();
		$inquiry->setMeta('edvisor_id', $enrollment['studentEnrollmentId']);
		$inquiry->setMeta('edvisor_status', $enrollment['studentEnrollmentStatusId']);
		$inquiry->type = \Ext_TS_Inquiry::TYPE_BOOKING;
		$inquiry->created = $created->toDateTimeString();
		$inquiry->payment_method = 0;
		$inquiry->agency_id = $agency->id;
		$inquiry->currency_id = $school->getCurrency();
		$inquiry->inbox = $inbox->short;

		if (
			\System::d('booking_auto_confirm') == \Ext_Thebing_Client::BOOKING_AUTO_CONFIRM_ALL &&
			$enrollment['studentEnrollmentStatusId'] === Api::ENROLLMENT_STATUS_ACCEPTED
		) {
			$inquiry->confirmed = $inquiry->created;
		}

		$journey = $inquiry->getJourney();
		$journey->school_id = $school->id;
		$journey->productline_id = $school->getProductLineId();
		$journey->type = \Ext_TS_Inquiry_Journey::TYPE_BOOKING;

		$contact = $inquiry->getCustomer();
		$contact->setMeta('edvisor_id', data_get($enrollment, 'student.studentId'));
		$contact->corresponding_language = $school->getLanguage();
		$contact->nationality = strtoupper(data_get($enrollment, 'student.nationality.code'));
		$contact->firstname = data_get($enrollment, 'student.firstname');
		$contact->lastname = data_get($enrollment, 'student.lastname');
		$contact->birthday = Carbon::parse(data_get($enrollment, 'student.birthdate'))->toDateString();
		$contact->gender = match (data_get($enrollment, 'student.gender')) {
			'M' => 1,
			'F' => 2,
			default => 0
		};

		$contact->getFirstEmailAddress()->email = data_get($enrollment, 'student.email');
		$contact->getDetail(\Ext_TC_Contact_Detail::TYPE_PHONE_PRIVATE, true)->value = data_get($enrollment, 'student.phone');
		$contact->getAddress()->address = data_get($enrollment, 'student.address');

		if (!empty(data_get($enrollment, 'student.passportNumber'))) {
			$journey->getVisa()->passport_number = data_get($enrollment, 'student.passportNumber');
		}

		$this->importEnrollmentServices($enrollment, $inquiry);

		$customerNumberGenerator = new \Ext_Thebing_Customer_CustomerNumber($inquiry);
		$customerNumberGenerator->saveCustomerNumber(false, false);

		$inquiry->disableValidate();
		$inquiry->save();

		\Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->id, 2);
		\Ext_Gui2_Index_Stack::save(true);

		Api::createLogger()->info(sprintf('Created inquiry %d from enrollment %d', $inquiry->id, $enrollmentId), $enrollment);
	}

	private function updateEnrollment(int $enrollmentId, \Ext_TS_Inquiry $inquiry)
	{
		\System::setInterfaceLanguage($inquiry->getSchool()->getLanguage());

		$enrollment = Api::default()->queryEnrollment($enrollmentId);

		$inquiry->setMeta('edvisor_status', $enrollment['studentEnrollmentStatusId']);

		$inquiry->disableValidate();
		$inquiry->save();

		Api::createLogger()->info(sprintf('Updated inquiry %d from enrollment %d', $inquiry->id, $enrollmentId), $enrollment);
	}

	private function importEnrollmentServices(array $enrollment, \Ext_TS_Inquiry $inquiry)
	{
		$journey = $inquiry->getJourney();
		$school = $journey->getSchool();

		$comment = [];
		foreach (data_get($enrollment, 'studentEnrollmentOfferingItems', []) as $service) {

			$journeyService = null;

			switch ($service['__typename']) {
				case 'StudentEnrollmentOfferingCourseItem':
					$label = \L10N::t('Course', ExternalApp::L10N_PATH);
					$name = $service['courseSnapshot']['name'];
					$journeyService = $journey->getJoinedObjectChild('courses'); /** @var \Ext_TS_Inquiry_Journey_Course $journeyService */
					break;
				case 'StudentEnrollmentOfferingAccommodationItem':
					$label = \L10N::t('Accommodation', ExternalApp::L10N_PATH);
					$name = $service['accommodationSnapshot']['name'];
					$journeyService = $journey->getJoinedObjectChild('accommodations'); /** @var \Ext_TS_Inquiry_Journey_Accommodation $journeyService */
					break;
				case 'StudentEnrollmentOfferingServiceItem':
					$label = \L10N::t('Service', ExternalApp::L10N_PATH);
					$name = $service['serviceSnapshot']['name'];
					break;
				default:
					throw new \RuntimeException('Unknown service type: '.$service['__typename']);
			}

			$line = sprintf('%s: %s, ', $label, $name);
			if (empty($service['serviceQuantity'])) {

				$from = Carbon::parse($service['startDate']);
				$amount = (int)$service['durationAmount'];

				$line .= sprintf('%s: %s, %s: %d %s',
					\L10N::t('Start', ExternalApp::L10N_PATH),
					\Ext_Thebing_Format::LocalDate($from->toDateString(), $school->id),
					\L10N::t('Duration', ExternalApp::L10N_PATH),
					$amount,
					match ($service['durationTypeId']) {
						Api::DURATION_TYPE_DAY => \L10N::t('days', ExternalApp::L10N_PATH),
						Api::DURATION_TYPE_WEEK => \L10N::t('weeks', ExternalApp::L10N_PATH),
						Api::DURATION_TYPE_MONTH => \L10N::t('months', ExternalApp::L10N_PATH),
						default => '?'
					}
				);

				if ($journeyService) {
					$journeyService->from = $from->toDateString();
					$journeyService->weeks = match ($service['durationTypeId']) {
						Api::DURATION_TYPE_DAY => ceil($amount * 7),
						Api::DURATION_TYPE_WEEK => $amount,
						Api::DURATION_TYPE_MONTH => round($amount * 4.35)
					};

					if ($service['durationTypeId'] === Api::DURATION_TYPE_DAY) {
						$journeyService->until = $from->clone()->addDays($service['durationTypeId'])->toDateString();
					} else {
						if ($journeyService instanceof \Ext_TS_Inquiry_Journey_Accommodation) {
							$category = $journeyService->getCategory();
							$journeyService->until = \Ext_Thebing_Util::getAccommodationEndDate($from, $journeyService->weeks, $category->getAccommodationInclusiveNights($school))->toDateString();
						} else {
							$journeyService->until = \Ext_Thebing_Util::getCourseEndDate($from, $journeyService->weeks, $school->course_startday)->toDateString();
						}
					}
				}

			} else {
				$line .= sprintf('%s: %s', \L10N::t('Quantity', ExternalApp::L10N_PATH), $service['serviceQuantity']);
			}

			$comment[] = $line;

			if ($journeyService) {
				$journeyService->visible = 0;
				$journeyService->comment = $line;
			}

		}

		$inquiry->setMeta('edvisor_instructions', join("\n", $comment));
	}

	private function findSchool(int $edvisorSchoolId): \Ext_Thebing_School
	{
		$schools = collect(\Ext_Thebing_Client::getSchoolList(false, 0, true));
		$school = $schools->first(fn(\Ext_Thebing_School $school) => (int)$school->getMeta('edvisor_id') === $edvisorSchoolId);

		if (empty($school)) {
			throw new \RuntimeException('No matching school for edvisor campus '.$edvisorSchoolId);
		}

		return $school;
	}

	private function findOrCreateAgency(array $edvisorAgency): \Ext_Thebing_Agency
	{
		$agency = \Ext_Thebing_Agency::query()
			->select('ka.*')
			->join('wdbasic_attributes as a', function (JoinClause $join) use ($edvisorAgency) {
				$join
					->on('a.entity_id', 'ka.id')
					->where('a.entity', (new \Ext_Thebing_Agency)->getTableName())
					->where('a.key', 'edvisor_id')
					->where('a.value', $edvisorAgency['agencyId']);
			})
			->first();

		// Fake-UNIQUE (_aFormat)
		if (!$agency) {
			\Ext_Thebing_Agency::query()
				->where('ext_1', $edvisorAgency['name'])
				->first();
		}

		if (!$agency) {
			$agency = new \Ext_Thebing_Agency();
			$agency->type = \TsCompany\Entity\AbstractCompany::TYPE_AGENCY;
			$agency->ext_1 = '[Edvisor] '.$edvisorAgency['name'];
			$agency->ext_2 = $agency->ext_1;
			$agency->ext_3 = $edvisorAgency['address'];
			$agency->ext_4 = $edvisorAgency['postalCode'];
			$agency->ext_5 = $edvisorAgency['city'];
			$agency->ext_10 = $edvisorAgency['websiteUrl'];
			$agency->comment = \L10N::t('Created by incoming Edvisor enrollment.', ExternalApp::L10N_PATH);

			$agency->setMeta('edvisor_id', $edvisorAgency['agencyId']);
			$agency->setMeta('edvisor_company_id', $edvisorAgency['agencyCompany']['agencyCompanyId']);
			$agency->setMeta('edvisor_company_name', $edvisorAgency['agencyCompany']['name']);

			/** @var \Ext_Thebing_Agency_Contact $contact */
			$contact = $agency->getJoinedObjectChild('contacts');
			$contact->disableValidate();
			$contact->firstname = 'N/A';
			$contact->lastname = 'N/A';
			$contact->phone = $edvisorAgency['phone'];
			$contact->email = $edvisorAgency['email'];

			$agency->save();

			Api::createLogger()->info(sprintf('Created agency %d from Edvisor agency %d (company %d)', $agency->id, $edvisorAgency['agencyId'],  $edvisorAgency['agencyCompany']['agencyCompanyId']));
		}

		return $agency;
	}
}