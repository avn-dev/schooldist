<?php

namespace Ts\Service\Import;

use Illuminate\Support\Arr;
use TsApi\Exceptions\ApiError;

class Inquiry extends Enquiry {

	protected $apiHandler = \TsApi\Handler\Inquiry::class;
	
	public function getFields() {
		
		$fields =  parent::getFields();
		
		$oClient = \Ext_Thebing_Client::getFirstClient();

		$inboxes = $oClient->getInboxList(true);
		
		array_unshift($fields, ['field'=> 'Bestätigt (Datum)', 'target' => 'confirmed', 'special'=>'date', 'additional' => $this->sExcelDateFormat]);
		array_unshift($fields, ['field'=> 'Eingang', 'target' => 'inbox', 'special'=>'array', 'additional'=>array_flip($inboxes)]);
		$fields[] = [
			'field'=> 'Kursname (Kürzel)',
			'target' => 'courses.0.name_short'
		];
		$fields[] = [
			'field'=> 'Kurssprache (ID)',
			'target' => 'courses.0.language_id'
		];
		$fields[] = [
			'field'=> 'Kurslevel',
			'target' => 'courses.0.level_id'
		];
		$fields[] = [
			'field'=> 'Kurslektionen',
			'target' => 'courses.0.lessons'
		];
		$fields[] = [
			'field'=> 'Kursstart',
			'target' => 'courses.0.from',
			'special' => 'date',
			'additional' => $this->sExcelDateFormat
		];
		$fields[] = [
			'field'=> 'Kursende',
			'target' => 'courses.0.until',
			'special' => 'date',
			'additional' => $this->sExcelDateFormat
		];

		return $fields;
	}
	
	public function getFlexibleFields() {

		$aFlexFields['Main'] = \Ext_TC_Flexibility::getFields('student_record_general', false, ['booking', 'enquiry_booking']);

		return $aFlexFields;
	}

	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData = null): int {

		$school = \Ext_Thebing_School::getSchoolFromSession();
		if (!$school->exist()) {
			throw new \RuntimeException('No school');
		}
		// Beim Import hier sind die Regeln anders als beim Import über Api. Email und Kurs sind optional.
		$mappingModifications = [
			'email' => [
				'validation' => ['email_mx']
			],
			'courses.*.course_id' => [
				'validation' => ['integer'],
			],
			'courses.*.level_id' => [
				'validation' => ['integer'],
			],
			'courses.*.weeks' => [
				'validation' => ['required_with:courses.*.course_id'],
			],
			'courses.*.language_id' => [
				'validation' => ['integer', 'required_with:courses.*.course_id'],
			],
			'courses.*.from' => [
				'validation' => ['date_format:Y-m-d', 'required_with:courses.*.course_id'],
			],
			'courses.*.until' => [
				'validation' => ['date_format:Y-m-d', 'required_with:courses.*.course_id'],
			],
		];

		$inquiryHandler = new ($this->apiHandler)($school, false, $mappingModifications);

		try {

			$data = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $data);

			if(
				!empty($data['phone_private']) &&
				!empty($data['country_iso'])
			) {
				$oWDValidator = new \WDValidate();
				$data['phone_private'] = $oWDValidator->formatPhonenumber($data['phone_private'], $data['country_iso']);
			}

			if (!empty($data['courses.0.name_short'])) {
				/** @var \Ext_Thebing_Tuition_Course $course */
				$course = \Ext_Thebing_Tuition_Course::query()
					->where('school_id', $school->getId())
					->where('name_short', $data['courses.0.name_short'])
					->first();
				if (!$course) {
					throw new \Exception(\L10N::t('Ein Kurs mit diesem Namen existiert nicht!',
						\Ext_Thebing_Accommodation_Gui2::L10N_PATH));
				}
				$data['courses.0.course_id'] = $course->id;
				// Sicher gehen, dass Kurssprache korrekt ist.
				if (
					!collect($course->getCourseLanguages())
						->pluck('id')
						->contains($data['courses.0.language_id'])
				) {
					throw new \Exception(\L10N::t('Falsche Kurssprache für diesen Kurs!',
						\Ext_Thebing_Accommodation_Gui2::L10N_PATH));
				}
				// Weeks setzen
				$data['courses.0.weeks'] = ceil(
					\Carbon\Carbon::parse($data['courses.0.from'])
						->diffInDays(\Carbon\Carbon::parse($data['courses.0.until'])) / 7
				);
			}
			// Feld existiert nicht im Import Handler
			unset($data['courses.0.name_short']);
			$oValidator = $inquiryHandler->createValidator(Arr::undot($data));

			if($oValidator->fails()) {
				throw new ApiError('Validation failed', $oValidator);
			}

			$indexAgencyField = array_search('agency_id', array_column($this->aFields, 'target'));

			if(
				!empty($aItem[$indexAgencyField]) &&
				empty($data['agency_id'])
			) {
				throw new \Exception(\L10N::t('Agentur nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH));
			}

			// Buchungsnummer darf nicht nochmal verwendet werden
			if (!empty($data['booking_number'])) {
				$inquiry = \Ext_TS_Inquiry::query()
					->where('number', $data['booking_number'])
					->first();
				if ($inquiry) {
					throw new \Exception(\L10N::t('Buchungsnummer existiert bereits!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH));
				}
			}

			/** @var \Ext_TS_Inquiry|\Ext_TS_Enquiry $inquiry */
			$inquiry = $inquiryHandler->buildInquiry();

			if (!empty($data['agency_id'])) {
				// Wenn Agentur existiert, dann setzen wir payment_method auf Agenturstandard
				$agency = \Ext_Thebing_Agency::getInstance($data['agency_id']);
				$inquiry->payment_method = $agency->ext_26;
			}

			$ignoreAliases = [];
			// Wenn contact_number existiert, dann wird der customer genommen und kein neuer angelegt
			if (!empty($data['contact_number'])) {
				$customer = \Ext_TS_Inquiry_Contact_Traveller::query()
					->select('cdb1.*')
					->join('tc_contacts_numbers', 'cdb1.id', '=', 'tc_contacts_numbers.contact_id')
					->where('tc_contacts_numbers.number', $data['contact_number'])
					->first();
				if ($customer) {
					// Customer existiert, setzen
					$inquiry->addJoinTableObject('travellers', $customer);
					// Werte des Customers sollen nicht geupdated werden, wenn er existiert.
					$ignoreAliases = ['tc_c', 'tc_cd', 'tc_cn'];
				}
			}

			if (!empty($course)) {
				$courseData = ['course_id' => $course->id];
				if (!empty($data['courses.0.lessons']) && $course->canHaveLessons()) {
					$courseList = $course->getLessons()?->getLessons();
					if (!in_array((float)$data['courses.0.lessons'], $courseList)) {
						throw new \Exception(\L10N::t('Kurslektionenzahl existiert nicht im Kurs!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH));
					}
					$courseData['lessons'] = $data['courses.0.lessons'];
				}
				$inquiryHandler->buildCourse($courseData);
			}

			$inquiryHandler->setObjectData($inquiry, $data, $ignoreAliases);

			$this->aReport['insert']++;

			return $inquiry->id;

		} catch(\Exception $e) {

			$this->handleProcessItemError($iItem,  $e);

		}

		return 0;
	}
	
}
