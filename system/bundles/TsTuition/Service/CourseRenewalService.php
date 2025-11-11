<?php

namespace TsTuition\Service;

use Carbon\Carbon;
use Core\Entity\ParallelProcessing\Stack;
use Core\Exception\ParallelProcessing\RewriteException;
use TsTuition\Enums\CourseRenewalType;

final class CourseRenewalService
{
	const MAX_WEEKS = 12;

	private \Ext_Thebing_Inquiry_Document $document;

	private \Ext_Thebing_Inquiry_Document_Numberrange $numberrange;

	private \Monolog\Logger $logger;

	public function __construct()
	{
		$this->logger = \Log::getLogger('default', 'course_renewal');
	}

	/**
	 * PP-Tasks generieren
	 */
	public function generateTasks(): void
	{
		\DB::begin(__CLASS__);

		$result = $this->queryCourses();

		foreach ($result as $row) {
			$this->logger->info(sprintf('Added task for inquiry %d, journey course %d', $row['inquiry_id'], $row['journey_course_id']));
			Stack::getRepository()->writeToStack('ts-tuition/' . \TsTuition\Handler\ParallelProcessing\CourseRenewal::TASK_NAME, $row, 5);
		}

		\DB::commit(__CLASS__);
	}

	private function queryCourses(): array
	{
		// TODO Sollte irgendwann weiter optimiert werden (z.B. Starttag(Woche) = aktueller Wochentag -1)
		return (array)\DB::getQueryRows("
			SELECT
			    ts_i.id inquiry_id,
				ts_ijc.id journey_course_id
			FROM
			    kolumbus_tuition_courses ktc INNER JOIN
				ts_inquiries_journeys_courses ts_ijc ON
					ts_ijc.course_id = ktc.id AND 
			        ts_ijc.active = 1 AND
					ts_ijc.visible = 1 AND
					ts_ijc.automatic_renewal_origin IS NULL AND
					ts_ijc.automatic_renewal_cancellation IS NULL INNER JOIN
				ts_inquiries_journeys ts_ij ON
					ts_ij.id = ts_ijc.journey_id AND
					ts_ij.type & " . \Ext_TS_Inquiry_Journey::TYPE_BOOKING . " AND
					ts_ij.active = 1 INNER JOIN
				ts_inquiries ts_i ON
					ts_i.id = ts_ij.inquiry_id AND
					ts_i.type & " . \Ext_TS_Inquiry::TYPE_BOOKING . " AND
					ts_i.active = 1 AND
					ts_i.has_invoice = 1 AND
					ts_i.canceled = 0
			WHERE
			    ktc.active = 1 AND
			    ktc.automatic_renewal = 1 AND
			    ts_i.service_until + INTERVAL " . self::MAX_WEEKS . " WEEK >= NOW()
		");
	}

	/**
	 * Generierung im PP ausführen
	 */
	public function runTask(\Ext_TS_Inquiry $inquiry, \Ext_TS_Inquiry_Journey_Course $journeyCourse): void
	{
		$journey = $inquiry->getJourney();
		$course = $journeyCourse->getCourse();

		// Einstellungen (Dialog) überprüfen
		if (
			empty($course->automatic_renewal_weeks_before) ||
			!CourseRenewalType::tryFrom($course->automatic_renewal_duration_type) || (
				CourseRenewalType::from($course->automatic_renewal_duration_type) === CourseRenewalType::ADJUSTED &&
				empty($course->automatic_renewal_duration_weeks)
			)
		) {
			$this->logger->error(sprintf('Wrong course settings for course renewal for inquiry %d, journey course %d', $inquiry->id, $journeyCourse->id));
			return;
		}

		if (!($maxEnd = $this->checkRenewal($journey, $course))) {
			$this->logger->info(sprintf('Skipped course renewal for inquiry %d, journey course %d', $inquiry->id, $journeyCourse->id));
			return;
		}
		$this->document = \Ext_Thebing_Inquiry_Document_Search::search($inquiry->id, ['brutto', 'netto'], false, true);
		$this->numberrange = \Ext_Thebing_Inquiry_Document_Numberrange::getInstance($this->document->numberrange_id);

		if (!$this->numberrange->acquireLock()) {
			$this->logger->warning(sprintf('Number range lock rewrite for course renewal for inquiry %d, journey course %d', $inquiry->id, $journeyCourse->id));
			throw new RewriteException('CourseRenewalService: Number range already locked.');
		}

		// Dokumentengenerierung ist fragil und unlock() muss aufgerufen werden
		try {
			\DB::begin(__CLASS__);

			$this->createCourse($inquiry, $journeyCourse, $maxEnd);

			$this->createInvoice($inquiry);

			\DB::commit(__CLASS__);

			$this->numberrange->removeLock();

			// TODO Event

			$this->logger->info(sprintf('Successfully renewed course for inquiry %d, journey course %d', $inquiry->id, $journeyCourse->id));

		} catch (\Throwable $e) {

			$this->logger->error(sprintf('Error while course renewal for inquiry %d, journey course %d: %s', $inquiry->id, $journeyCourse->id, $e->getMessage()));

			\DB::rollback(__CLASS__);

			$this->numberrange->removeLock();

			// TODO Event (evtl. auch für throwErrorHandler benötigt?)

			throw $e;

		}
	}

	private function checkRenewal(\Ext_TS_Inquiry_Journey $journey, \Ext_Thebing_Tuition_Course $course): ?Carbon
	{
		$journeyCourses = $journey->getCoursesAsObjects(true, true);
		$endDates = collect();

		foreach ($journeyCourses as $journeyCourse) {
			if (
				!$journeyCourse->isEmpty() &&
				$journeyCourse->course_id == $course->id
			) {
				$endDates->push(Carbon::parse($journeyCourse->until));
			}
		}

		/** @var Carbon $maxEnd */
		$maxEnd = $endDates->max();

		if ($maxEnd <= Carbon::now()->addWeeks($course->automatic_renewal_weeks_before)->startOfDay()) {
			return $maxEnd;
		}

		return null;
	}

	private function createCourse(\Ext_TS_Inquiry $inquiry, \Ext_TS_Inquiry_Journey_Course $journeyCourse, Carbon $maxEnd)
	{
		$journey = $inquiry->getJourney();
		$course = $journeyCourse->getCourse();

		$school = $journey->getSchool();
		$from = \Ext_Thebing_Util::getNextCourseStartDay($maxEnd, $school->course_startday);
		$weeks = CourseRenewalType::from($course->automatic_renewal_duration_type) === CourseRenewalType::ADJUSTED ? $course->automatic_renewal_duration_weeks : $journeyCourse->weeks;
		$dateFormatted = \Ext_Thebing_Format::LocalDate(Carbon::now(), $inquiry->getSchool()->id);

		$newCourse = $journey->getJoinedObjectChild('courses');
		/** @var \Ext_TS_Inquiry_Journey_Course $newCourse */
		$newCourse->course_id = $journeyCourse->course_id;
		$newCourse->courselanguage_id = $journeyCourse->courselanguage_id;
		$newCourse->level_id = $journeyCourse->level_id;
		$newCourse->units = $journeyCourse->units;
		$newCourse->weeks = $weeks;
		$newCourse->program_id = $journeyCourse->program_id;
		$newCourse->from = $from->toDateString();
		$newCourse->until = \Ext_Thebing_Util::getCourseEndDate($from, $journeyCourse->weeks, $school->course_startday)->toDateString();
		$newCourse->comment = sprintf('%s (%s)', \L10N::t('Automatisch durch Kursverlängerung angelegt.', \Ext_Thebing_Inquiry_Gui2::TRANSLATION_PATH), $dateFormatted);
		$newCourse->automatic_renewal_origin = $journeyCourse->id;

		$inquiry->save();
	}

	private function createInvoice(\Ext_TS_Inquiry $inquiry): void
	{
		$lastVersion = $this->document->getLastVersion();

		$documentService = new \Ts\Helper\Document($inquiry, $inquiry->getSchool(), $lastVersion->getTemplate(), $lastVersion->template_language);
		$documentService->create('brutto_diff');

		$items = $documentService->getVersion()->buildItems();
		$diffService = new \Ts\Service\Invoice\Diff($inquiry);
		$diffService->loadItemsFromInvoices();
		$diffItems = $diffService->getDiff($items);

		$documentService->setAddress();
		$documentService->setItems($diffItems);
		$documentService->setPaymentConditions($inquiry->getPaymentCondition());

		$documentService->getDocument()->numberrange_id = $this->numberrange->id;
		$documentService->getDocument()->document_number = $this->numberrange->generateNumber();

		$documentService->save();
	}
}