<?php

namespace TsActivities\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TsActivities\Entity\Activity;
use TsActivities\Dto\ActivityBlockWeekCombination;
use TsActivities\Enums\AssignmentSource;
use TsActivities\Events\ActivityCancelled;
use TsActivities\Exceptions\AlreadyAllocatedException;
use TsActivities\Exceptions\InvalidAssignmentException;
use TsActivities\Exceptions\OverbookingException;
use TsActivities\Gui2\Data\BlockData;
use TsActivities\Service\ActivityService;

class SchedulingController extends \Illuminate\Routing\Controller {

	public function index() {

		$gui = BlockData::createGui();
		$gui->save();

		return response()->view('scheduling', [
			'router' => new \Core\Service\RoutingService(),
			'version' => \System::d('version'),
			'gui' => $gui
		]);

	}

	public function events(Request $request, ActivityService $service) {

		$school = \Ext_Thebing_School::getSchoolFromSession();
		$start = Carbon::parse($request->input('start'));
		$end = Carbon::parse($request->input('end'));

		$blocks = $service->searchBlocksForTimeframe($start, $end, $school)->map(function (array $block) {
			$block['title'] = $block['name'];
			return $block;
		});

		return response($blocks->values());

	}

	public function unallocated(Request $request) {

		$school = \Ext_Thebing_School::getSchoolFromSession();
		$language = \System::getInterfaceLanguage();
		$from = Carbon::parse($request->input('start'));
		$until = Carbon::parse($request->input('end'))->subSecond(); // Enddatum von Fullcalendar ist exklusiv

		$students = Activity\BlockTraveller::getRepository()->getUnallocatedStudents($school, $from, $until, $language, $request->input('filter'));

		return response(compact('students'));

	}

	public function allocated(Request $request) {

		$week = Carbon::instance(\Ext_Thebing_Util::getPreviousCourseStartDay(Carbon::parse($request->input('date')), 1));
		$block = (int)$request->input('block_id');

		$format = new \Ext_Gui2_View_Format_Name();
		$students = array_map(function (array $student) use ($format) {
			$student['name'] = $format->formatByResult($student);
			// Belasse alle anderen Infos wie group_name
			return $student;
		}, Activity\BlockTraveller::getRepository()->getAllocatedStudents($block, $week));

		return response(compact('students'));

	}

	public function allocate(Request $request, ActivityService $service) {

		\DB::begin(__METHOD__);

		$messages = $needsConfirm = [];
		$week = Carbon::instance(\Ext_Thebing_Util::getPreviousCourseStartDay(Carbon::parse($request->input('date')), 1));
		$block = Activity\Block::getInstance($request->input('block_id'));
		$ignoreErrors = $request->input('confirm', []);

		foreach ($request->input('students', []) as $student) {

			$inquiry = \Ext_TS_Inquiry::getInstance($student['inquiry_id']);
			$name = $inquiry->getFirstTraveller()->getName();
			// Entweder hat der Schüler direkt eine Aktivität (Buchung), es wurde über den Dialog eine ausgewählt bei mehr als einer im Block, oder es bleibt nur noch eine
			$activity = Activity::getInstance($student['activity_id'] ?? $request->input('activity_id') ?? reset($block->activities));
			$combination = new ActivityBlockWeekCombination($block, $activity, $week);
			$combination->dates = $combination->createBlockEvents();
			$journeyActivity = !empty($student['journey_activity_id']) ? \Ext_TS_Inquiry_Journey_Activity::getInstance($student['journey_activity_id']) : null;

			// Hierüber abwickeln, damit Batch-Zuweisung besser funktioniert
			if (!in_array($activity->id, $block->activities)) {
				$messages[] = ['danger', sprintf(\L10N::t('Schüler "%s" hat nicht die korrekte Aktivität für diesen Block gebucht.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH), $name)];
				continue;
			}

			try {
				$service->assignBlockToInquiry($inquiry, $combination, $journeyActivity, $ignoreErrors);
				$messages[] = ['success', sprintf(\L10N::t('Schüler "%s" wurde erfolgreich zugewiesen.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH), $name)];
			} catch (AlreadyAllocatedException $e) {
				$message = match ($e->getType()) {
					'same' => 'Schüler "%s" ist der Aktivität bereits zugewiesen.',
					'other' => 'Schüler "%s" ist bereits der Aktivität "%s" zugewiesen.',
					'class' => 'Schüler "%s" ist bereits der Klasse "%s" zugewiesen.'
				};

				if ($e->isSkipable()) {
					$message = sprintf(\L10N::t($message, \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH), $name, $e->getLabel());
					$messages[] = ['warning', $message.' '.\L10N::t('Bitte bestätigen Sie dass Sie den Schüler trotzdem zuweisen möchten.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH), $e->getErrorCode()];
					$needsConfirm[] = $student;
				} else {
					$messages[] = ['danger', sprintf(\L10N::t($message, \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH), $name, $e->getLabel())];
				}
			} catch (InvalidAssignmentException) {
				$messages[] = ['danger', sprintf(\L10N::t('Schüler "%s" hat nicht den richtigen Kurs gebucht, um diesem Block zugewiesen werden zu können.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH), $name)];
			} catch (OverbookingException) {
				$messages[] = ['danger', sprintf(\L10N::t('Schüler "%s" konnte nicht zugewiesen werden, da die Aktivität bereits voll ist.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH), $name)];
			}

		}

		\DB::commit(__METHOD__);

		return response(compact('messages', 'needsConfirm'));

	}

	public function deleteAllocation(Request $request) {

		$id = $request->input('block_traveller_id');

		if (empty($id)) {
			throw new \RuntimeException('No block_traveller_id for deleteAllocation');
		}

		// TODO ActivityService::removeBlockFromInquiry

		$allocation = Activity\BlockTraveller::getInstance($id);
		$allocation->delete();

		ActivityCancelled::dispatch(AssignmentSource::SCHEDULER, $allocation);

		return response('', 200);

	}

	public function deleteBlock(Request $request) {

		$id = $request->input('block_id');

		$block = Activity\Block::getInstance($id);
		$block->delete();

		return response('', 200);

	}

	public function exportBlock(Request $request): BinaryFileResponse
	{
		$block = Activity\Block::getInstance($request->input('block_id'));
		$from = $request->input('date');
		$week = Carbon::instance(\Ext_Thebing_Util::getPreviousCourseStartDay(Carbon::parse($from), 1));
		$travellers = Activity\Block::getRepository()->getTravellersForExport($block, $week);

		$export = [];
		$export[] = [];
		$export[] = [
			\L10N::t('Vorname', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('Nachname', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('Kundennummer', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('Geschlecht', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('E-Mail Adresse', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('Alter', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('Nationalität', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('Klasse(n)', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			\L10N::t('Niveau(s)', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
		];

		foreach ($travellers as $id) {
			$contact = \Ext_TS_Inquiry_Contact_Traveller::getInstance($id);
			if (!$contact) {
				continue;
			}

			$classRows = $this->extractClassAllocations($contact, $week);;
			$export[] = $this->buildExportRow($contact, $classRows);
		}

		/** @var BinaryFileResponse $file */
		\WDExport::exportXLSX(
			\L10N::t('Zugewiesene Schüler', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH),
			$export
		);

		return response('', 200);
	}

	private function extractClassAllocations(\Ext_TS_Inquiry_Contact_Traveller $contact, string $week): array
	{
		$classRows = [];
		$seen = [];

		$inquiries = $contact->getInquiriesByDate($week) ?? [];
		foreach ($inquiries as $inquiry) {
			foreach ($inquiry->getCourses() ?? [] as $course) {
				$this->processCourseAllocations($course, $classRows, $seen, $week);
			}
		}

		return $classRows ?: [['name' => '', 'niveau' => '']];
	}

	private function processCourseAllocations(\Ext_TS_Inquiry_Journey_Course $inquiryCourse, array &$rows, array &$seen, string $week): void
	{
		$allocations = $this->getUniqueBlockAllocations($inquiryCourse, $week);

		foreach ($allocations as $allocation) {
			$block = $allocation->getBlock();
			$progress = $allocation->getProgress();
			$className = $block?->getClass()?->name ?? '';
			$key = strtolower(trim($className . '|' . $progress));

			if (!isset($seen[$key])) {
				$seen[$key] = true;
				$rows[] = ['name' => $className, 'niveau' => $progress ?? 'None'];;
			}
		}
	}

	/**
	 * @param \Ext_TS_Inquiry_Journey_Course $inquiryCourse
	 * @param string $week Wochenstart (YYYY-MM-DD)
	 * @return \Ext_Thebing_School_Tuition_Allocation[]
	 */
	private function getUniqueBlockAllocations(\Ext_TS_Inquiry_Journey_Course $inquiryCourse, string $week): array
	{
		$unique = [];
		/** @var \Ext_Thebing_School_Tuition_Allocation[] $allocations */
		//$allocations = $inquiryCourse->getJoinedObjectChilds('tuition_blocks');
		$allocations = $inquiryCourse->getAllocationsByWeek($week);
		foreach (($allocations ?? []) as $alloc) {
			$id = $alloc->block_id ?? null;
			if ($id && !isset($unique[$id])) {
				$unique[$id] = $alloc;
			}
		}
		return array_values($unique);
	}

	private function buildExportRow(\Ext_TS_Inquiry_Contact_Traveller $contact, array $classRows): array
	{
		$classNames = implode("\n", array_column($classRows, 'name'));
		$classLevels = implode("\n", array_column($classRows, 'niveau'));

		return [
			$contact->firstname ?? '',
			$contact->lastname ?? '',
			$contact->getCustomerNumber() ?? '',
			$contact->getGender() ?? '',
			$contact->getFirstEmailAddress()?->email ?? '',
			$contact->getAge() ?? '',
			$contact->nationality ?? '',
			$classNames,
			$classLevels,
		];
	}

}
