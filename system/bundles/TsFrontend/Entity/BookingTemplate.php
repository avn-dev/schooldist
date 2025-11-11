<?php

namespace TsFrontend\Entity;

use Carbon\Carbon;
use Core\Traits\UniqueKeyTrait;
use Illuminate\Support\Arr;
use TsFrontend\Gui2\BookingTemplate\CourseSelection;
use TsRegistrationForm\Generator\CombinationGenerator;

/**
 * @desc Diese Entität gilt mit ihren Feldern weiter als WIP.
 *
 * @property $id
 * @property $created
 * @property $changed
 * @property $active
 * @property $creator_id
 * @property $editor_id
 * @property $key
 * @property $form_id
 * @property $school_id
 * @property $description
 * @property $course_as_filter
 * @property $course_id
 * @property $course_id_locked
 * @property $course_from
 * @property $course_duration
 * @property $course_duration_locked
 * @property $courselanguage_id
 * @property $courselanguage_id_locked
 */
class BookingTemplate extends \WDBasic
{
	use UniqueKeyTrait;

	protected $_sTable = 'ts_frontend_booking_templates';

	protected $_aFormat = [
		'form_id' => [
			'required' => true
		],
		'school_id' => [
			'required' => true
		],
		'course_id' => [
			'validate' => 'INT_POSITIVE'
		]
	];

	public function getForm(): \Ext_Thebing_Form
	{
		return \Ext_Thebing_Form::getInstance($this->form_id);
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return \Ext_Thebing_School::getInstance($this->school_id);
	}

	public function validate($bThrowExceptions = false)
	{
		// Prüfen, ob der Kurs überhaupt als Option verfügbar ist
		if (
			$this->active &&
			!empty($this->course_id)
		) {
			$options = (new CourseSelection())->getOptions([], [], $this);
			if (!isset($options[$this->course_id])) {
				if ($bThrowExceptions) {
					// Ausnahmsweise wird mal mit try/catch gearbeitet
					throw new \RuntimeException('COURSE_NOT_AVAILABLE');
				}
				return ['COURSE_NOT_AVAILABLE'];
			}
		}

		return parent::validate($bThrowExceptions);
	}

	public function save()
	{
		if (empty($this->key)) {
			$this->key = $this->getUniqueKey();
		}

		// Dauer basiert auf Start
		if ($this->course_from === 'empty') {
			$this->course_duration = 'empty';
		}

		// Darf nicht ausgeblendet werden, wenn eines der Felder leer ist
		// TODO Bei course_id_locked no sollte das eigentlich auch raus, da bei nicht verfügbaren Startdaten die Felder leer und gesperrt sind
		if (
			$this->course_from === 'empty' ||
			$this->course_duration === 'empty'
		) {
			$this->course_duration_locked = 0;
		}

		if ($this->course_id_locked === 'no') {
			$this->courselanguage_id_locked = 'no';
		}

		return parent::save();
	}

	/**
	 * Fake-Buchung erzeugen
	 *
	 * @param CombinationGenerator $combination
	 * @return \Ext_TS_Inquiry
	 */
	public function createBooking(CombinationGenerator $combination): \Ext_TS_Inquiry
	{
		$helper = new \TsRegistrationForm\Helper\BuildInquiryHelper($combination);

		$inquiry = $helper->createInquiryObject();
		$journey = $inquiry->getJourney();
		$journey->school_id = $this->getSchool()->id;

		if (
			!empty($this->course_id) &&
			!$this->course_as_filter
		) {
			$this->createCourse($combination, $journey);
		}

		return $inquiry;
	}

	private function createCourse(CombinationGenerator $combination, \Ext_TS_Inquiry_Journey $journey): void
	{
		$data = $combination->getSchoolData($journey->school_id);
		$course = collect($data['courses'])->firstWhere('key', $this->course_id);

		// Abbrechen, wenn Kurs nicht existiert oder keine Startdaten hat
		if (!$course) {
			return;
		}

		$journeyCourse = new \Ext_TS_Inquiry_Journey_Course();
		$journeyCourse->course_id = $this->course_id;
		$journeyCourse->courselanguage_id = !empty($this->courselanguage_id) ? $this->courselanguage_id : Arr::first($course['languages']);
		$journeyCourse->program_id = reset($course['programs'])['key'];

		if ($this->course_id_locked !== 'no') {
			$journeyCourse->transients['field_state_course'] = $this->course_id_locked;
		}

		if (
			$this->courselanguage_id &&
			$this->course_id_locked !== 'no' &&
			$this->courselanguage_id_locked !== 'no'
		) {
			$journeyCourse->transients['field_state_language'] = $this->courselanguage_id_locked;
		}

		// Kursstart
		if ($this->course_from === 'next') {
			$now = Carbon::now()->startOfDay();
			$startDates = collect($data['course_dates'])->get($course['dates_key'], []);
			$startDate = collect($startDates)->first(function (array $startDate) use ($now, $course, $journeyCourse) {
				if (!empty($startDate['languages'] && !in_array($journeyCourse->courselanguage_id, $startDate['languages']))) {
					return false;
				}
				$date = Carbon::parse($startDate['start']);
				return $date > $now && $date->isoWeekday() == $this->getSchool()->course_startday;
			});

			if ($startDate) {
				$journeyCourse->from = $startDate['start'];

				if ($this->course_duration_locked) {
					$journeyCourse->transients['field_state_start'] = 'disabled';
				}
			}
		}

		// Kursdauer
		if ($this->course_duration === 'next' && isset($startDate)) {
			$journeyCourse->weeks = $startDate['min'];

			if ($this->course_duration_locked) {
				$journeyCourse->transients['field_state_duration'] = 'disabled';
			}
		}

		$journey->setJoinedObjectChild('courses', $journeyCourse);
	}

	/**
	 * Buchungsvorlage finden oder anlegen
	 *
	 * @param string $combinationKey
	 * @param \Ext_Thebing_Tuition_Course $course
	 * @return self
	 */
	public static function firstOrCreateByCourse(string $combinationKey, \Ext_Thebing_Tuition_Course $course): self
	{
		$combination = \Ext_TC_Frontend_Combination::getUsageObjectByKey($combinationKey);
		$combination->initCombination(new \Illuminate\Http\Request());

		if (!$combination instanceof \TsRegistrationForm\Generator\CombinationGenerator) {
			throw new \InvalidArgumentException('Combination not valid: ' . $combinationKey);
		}

		$form = $combination->getForm();

		/** @var self $bookingTemplate */
		$bookingTemplate = self::query()->firstOrNew([
			'form_id' => $form->id,
			'school_id' => $course->school_id,
			'course_id' => $course->id
		]);

		if (!$bookingTemplate->exist()) {
			$bookingTemplate->description = 'Generated by frontend combination (ID ' . $combination->getCombination()->id . ')';
			$bookingTemplate->validate(true);
			$bookingTemplate->save();
		}

		return $bookingTemplate;
	}
}