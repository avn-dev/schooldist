<?php

namespace TsTeacherLogin\Controller;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Core\Exception\Entity\EntityLockedException;
use Core\Factory\ValidatorFactory;
use Core\Handler\SessionHandler as Session;
use Core\Helper\DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use TsTeacherLogin\Handler\ExternalApp;
use TsTeacherLogin\Traits\SchoolViewPeriod;
use TsTuition\Enums\ActionSource;
use TsTeacherLogin\TeacherPortal;
use TsTuition\Events\BlockCanceled;
use TsTuition\Helper\State;
use TsTuition\Service\BlockCancellationService;

class TimetableController extends InterfaceController {
	use SchoolViewPeriod;

	protected string $viewPeriod = 'timetable';

	/**
	 * @var Session
	 */
	protected $oSession;

	public function getTimetableView() {

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$oSchool = $oTeacher->getSchool();

		$sSchoolLanguage = $oSchool->getLanguage();
		$this->set('sSchoolLanguage', $sSchoolLanguage);

		$aClassTimes = $oSchool->getClassTimes();
		$oClassTime = reset($aClassTimes);
		$this->set('aClassTimes', [
			'from' => $oClassTime->from,
			'until' => $oClassTime->until,
			'interval' => $oClassTime->interval,
		]);

		$oViewPeriod = $this->getViewPeriod($oSchool);

		if ($oViewPeriod) {
			$this->set('aViewPeriod', [
				'start' => $oViewPeriod->start()->format('Y-m-d'),
				'end' => $oViewPeriod->end()->format('Y-m-d'),
			]);
		} else {
			$this->set('aViewPeriod', []);
		}

		$this->set('oTeacher', $oTeacher);

		$sDateFormat = \Ext_Thebing_Format::getDateFormat($oSchool->id,'backend_datepicker_format');
		$sDateFormat = strtoupper($sDateFormat);
		$this->set('sDateFormat', $sDateFormat);

		$sShortDateFormat = \Ext_Thebing_Format::getDateFormat($oSchool->id,'backend_datepicker_format_short');
		$sShortDateFormat = strtoupper($sShortDateFormat);
		$this->set('sShortDateFormat', $sShortDateFormat);

		$this->merge('aTranslations', [
			'block_no_students' => \L10N::t('Bitte wählen Sie zuerst einen Schüler aus')
		]);

		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/timetable.tpl';
		$this->_oView->setTemplate($sTemplate);
	}

	public function getTimetableData(Request $oRequest) {

		$blocksRepo = \Ext_Thebing_School_Tuition_Block::getRepository();

		$startDate = new DateTime($oRequest->input('start'));
		$endDate = new DateTime($oRequest->input('end'));

		$teacher = $this->getTeacher();

		$blocks = [];
		foreach ($teacher->schools as $schoolId) {
			$school = \Ext_Thebing_School::getInstance($schoolId);
			$blocks = array_merge(
				$blocks,
				$blocksRepo->getTuitionBlocks($startDate, $endDate, $school, $teacher->getTeacherForQuery())
			);
		}

		$timetableBlocks = [];

		foreach($blocks as $blockData) {
			$school = \Ext_Thebing_School::getInstance($blockData['school_id']);
			$block = \Ext_Thebing_School_Tuition_Block::getInstance($blockData['id']);

			$start = Carbon::createFromFormat('Y-m-dH:i:s', $blockData['date'].$blockData['from']);
			$end = Carbon::createFromFormat('Y-m-dH:i:s', $blockData['date'].$blockData['until']);

			if (
				!$this->checkDateInViewPeriod($school, $start) &&
				!$this->checkDateInViewPeriod($school, $end)
			) {
				continue;
			}

			// @todo Prüfen, ob Raum und Farbcode nicht auch im Query geholt werden wg. Performance
			$timetableBlocks[] = [
				'id' => $blockData['id'],
				'title' => $blockData['name']."\n".$blockData['room'],
				'start' => $blockData['date'].'T'.$blockData['from'],
				'end' => $blockData['date'].'T'.$blockData['until'],
				'backgroundColor' => $block->getClass()->getColor(),
				'borderColor' => '#cccccc',
				'textColor' => '#333333'
			];
		}

		return response()->json($timetableBlocks);

	}

	public function getTimetableNewModalData()
	{
		$school = $this->getSchool();

		$oSmarty = $this->smarty();

		$class = new \Ext_Thebing_Tuition_Class();
		$block = new \Ext_Thebing_School_Tuition_Block();
		$block->school_id = $school->id;

		$this->assignClassFieldsVariables($this->_oRequest, $oSmarty, $class, $block);

		$sTemplatePath = \Util::getDocumentRoot().'system/bundles/TsTeacherLogin/Resources/views/pages/timetable/new_class.tpl';
		$dateFormat = \Ext_Thebing_Format::getDateFormat($school->id,'backend_datepicker_format');

		$aReturnJsonArray = [];
		$aReturnJsonArray['title'] = \L10N::t('Neue Klasse');
		$aReturnJsonArray['body'] = $oSmarty->fetch($sTemplatePath);
		$aReturnJsonArray['date_format'] = strtolower($dateFormat);
		$aReturnJsonArray['students'] = [];

		return response()->json($aReturnJsonArray);
	}

	public function getTimetableModalData($iBlockId) {

		$aReturnJsonArray = [];
		$oSmarty = $this->smarty();

		$dSelected = new DateTime($this->_oRequest->get('time'));

		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		$oSmarty->assign('oBlock', $oBlock);

		$oSchool = $oBlock->getSchool();

		$aStudents = \TsTeacherLogin\Helper\Data::getBlockStudents($iBlockId);
		$oSmarty->assign('aStudents', $aStudents);

		$aNationalities = \Ext_Thebing_Nationality::getNationalities(true, null);
		$oSmarty->assign('aNationalities', $aNationalities);

		$sLanguage = \TsTeacherLogin\Helper\Data::getSelectedOrDefaultLanguage();
		$oLanguage = new \Tc\Service\Language\Frontend($sLanguage);

		$oState = new State(State::KEY_STRING, $oLanguage);
		$aStates = $oState->getOptions();

		$oSmarty->assign('aStates', $aStates);

		$aDescriptions = \TsTeacherLogin\Helper\Data::getDescriptionHistory($oBlock, $dSelected);
		$oSmarty->assign('aDescriptions', $aDescriptions);

		$aLocaleDays = \Ext_TC_Util::getLocaleDays($sLanguage, 'wide');
		$oSmarty->assign('aLocaleDays', $aLocaleDays);

		$oSmarty->assign('oSchool', $oSchool);

		$oTeacher = $this->getTeacher();
		$oSmarty->assign('oTeacher', $oTeacher);

		$oClass = $oBlock->getClass();
		$oSmarty->assign('oClass', $oClass);

		$date = Carbon::make($dSelected);

		if ($oBlock->isEditableByTeacher($oTeacher)) {
			$this->assignClassFieldsVariables($this->_oRequest, $oSmarty, $oClass, $oBlock, $date);
		}

		$sTemplatePath = \Util::getDocumentRoot().'system/bundles/TsTeacherLogin/Resources/views/pages/classes_students.tpl';
		$sBody = $oSmarty->fetch($sTemplatePath);

		$aReturnJsonArray['body'] = $sBody;

		$sTitle = $oBlock->getClass()->name;

		$aRooms = $oBlock->getRooms();

		if(!empty($aRooms)) {
		    foreach($aRooms as $oRoom) {
                $sTitle .= ', '.$oRoom->name;
            }
		}

		$sTime = $this->_oRequest->get('time');

		$dDateTime = new DateTime($sTime);
		$sDateTime = strftime($oSchool->date_format_long.' %H:%M', $dDateTime->getTimestamp());

		$sTitle .= ', '.$sDateTime;

		$allocations = $oBlock->getAllocations();
		$journeyCourses = array_map(fn ($allocation) => $allocation->getJourneyCourse(), $allocations);
		$course = Arr::first($allocations)?->getCourse();

		$oTeacher = $oBlock->getTeacher();
		if($oTeacher->exist()) {
			$sTitle .= ' ('.$oTeacher.')';
		}
		$aReturnJsonArray['title'] = $sTitle;
		$aReturnJsonArray['description'] = $oBlock->description;
		$aReturnJsonArray['date_format'] = strtolower(\Ext_Thebing_Format::getDateFormat($oSchool->id,'backend_datepicker_format'));
		$aReturnJsonArray['students'] = $this->buildStudentsSelectOptions($date, $journeyCourses, $course);

		return response()->json($aReturnJsonArray);
	}

	private function assignClassFieldsVariables(
		Request $request,
		\SmartyWrapper $smarty,
		\Ext_Thebing_Tuition_Class $class,
		\Ext_Thebing_School_Tuition_Block $block,
		Carbon $date = null
	) {
		[$view, $period] = $this->getCurrentCalendar($request);

		$school = $block->getSchool();
		$template = $block->getTemplate();

		$times = $school->getClassTimesOptions('format', 5);

		$lessonsFormat = new \Ext_Thebing_Gui2_Format_Float();

		if (!$date) {
			[$hours, $seconds] = explode(':', Arr::first($times));
			$date = $period->first()->clone()->setTime($hours, $seconds);
		}

		$smarty->assign('teacher', $this->getTeacher());
		$smarty->assign('class', $class);
		$smarty->assign('block', $block);
		$smarty->assign('view', $view);
		$smarty->assign('datePeriod', $period);
		$smarty->assign('disabled', $block->isLocked());
		$smarty->assign('times', $times);
		$smarty->assign('weekdays', \Ext_TC_Util::getWeekdaySelectOptions(\System::getInterfaceLanguage()));
		$smarty->assign('rooms', $this->getRoomsSelectOptions($block, $date));
		$smarty->assign('students', $block->getAllocations());
		$smarty->assign('values', [
			'name' => $class->name,
			'lessons' => $lessonsFormat->formatByValue(($template->exist()) ? $template->lessons : 1),
			'date' => ($date) ? \Ext_Thebing_Format::LocalDate($date, $school->id) : '',
			'time' => substr($template->from, 0, 5),
			'room_id' => Arr::first($block->rooms)
		]);
	}

	public function saveTimetableBlock(Request $request, $block_id = null) {

		$teacher = $this->getTeacher();
		$school = $this->getSchool();

		if ($block_id !== null) {
			$block = \Ext_Thebing_School_Tuition_Block::query()
				->where('teacher_id', $teacher->id)
				->findOrFail($block_id);
			$class = $block->getClass();
		} else {
			$class = new \Ext_Thebing_Tuition_Class();
			$class->teacher_can_add_students = 1;
			$block = new \Ext_Thebing_School_Tuition_Block();
			$block->teacher_id = $teacher->id;
			$block->setJoinedObject('class', $class);
		}

		if ($block->isEditableByTeacher($teacher)) {

			$data = $request->all();
			$data['lessons'] = (new \Ext_Thebing_Gui2_Format_Float)->convert($data['lessons']);
			$data['date'] = \Ext_Thebing_Format::ConvertDate($data['date'], $school, 1);

			// Klasse ist nur editierbar solange es nur einen Block gibt
			$classEditable = $class->isEditableByTeacher($teacher);

			$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))->make(
				data: $data,
				rules: [
					'class_name' => $classEditable ? ['required'] : [],
					'room_id' => ExternalApp::getAllowClassWithoutRoom() ? ['required'] : ['required', 'min:1'],
					'date' => ['required', 'date'],
					'time' => ['required', Rule::in(array_keys($school->getClassTimesOptions('format', 5)))],
					'lessons' => ['required', 'numeric', 'between:0,99.99'],
					'course_id' => ($classEditable && empty($class->courses)) ? ['required'] : [],
				],
				customAttributes: [
					'class_name' => \L10N::t('Name'),
					'date' => \L10N::t('Tag'),
					'time' => \L10N::t('Uhrzeit'),
					'lessons' => \L10N::t('Lektionen'),
					'room_id' => \L10N::t('Raum'),
					'course_id' => \L10N::t('Kurs'),
				]
			);

			if ($validator->fails()) {
				return response()
					->json([
						'success' => false,
						'errors' => $validator->getMessageBag()->toArray()
					]);
			}

			$start = Carbon::createFromFormat('Y-m-dH:i', $data['date'].$data['time']);
			$week = $start->clone()->startOfWeek(Carbon::MONDAY);

			if ($classEditable) {

				if (!empty(!empty($data['course_id']))) {
					$course = \Ext_Thebing_Tuition_Course::query()->findOrFail($data['course_id']);
				} else {
					$course = \Ext_Thebing_Tuition_Course::query()->findOrFail(Arr::first($class->courses));
				}

				$courseLanguageId = Arr::first($course->course_languages);
				
				if ($request->exists('students')) {
					$studentKey = Arr::first($request->input('students'));
					[$inquiryId, $journeyCourseId, $programServiceId] = explode('_', $studentKey, 3);
					$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($journeyCourseId);
					$courseLanguageId = $journeyCourse->courselanguage_id;
				}

				$class->school_id = $school->id;
				$class->name = $request->input('class_name');
				$class->start_week = $week->toDateString();
				$class->weeks = 1;
				$class->level_increase = 1;
				$class->lesson_duration = $course->lesson_duration;
				$class->courselanguage_id = $courseLanguageId;
				$class->courses = [$course->id];
			}

			// Template befüllen um über getLessonDuration() zu gehen
			$template = $block->getTemplate();
			$template->lessons = (float)$data['lessons'];

			$duration = $block->getLessonDuration();
			$end = $start->clone()->addMinutes($duration);

			// Alle Blöcke der Klasse in dieser Woche holen und das Array für $_aSaveBlocks aufbauen, über setSaveBlocks()
			// weil hier alle Validierungen stattfinden ($bForCopy=true wegen $iBlockId)
			$existingSaveBlocks = $class->prepareBlockSaveDataArray($class->getBlocks($week), true);
			// Daten des aktuell geöffneten Blocks anpassen
			$thisSaveBlock = Arr::first($class->prepareBlockSaveDataArray([$block], true));
			$thisSaveBlock['days'] = [$start->format('N')];
			if (!empty($data['room_id'])) {
				$thisSaveBlock['rooms'] = [$data['room_id']];
			}
			$thisSaveBlock['template'] = $thisSaveBlock['original']['template'];
			$thisSaveBlock['from'] = $start->format('H:i');
			$thisSaveBlock['until'] = $end->format('H:i');
			$thisSaveBlock['lessons'] = (float)$data['lessons'];
			$thisSaveBlock['description'] = $request->input('description');

			$saveBlocks = [];
			foreach ($existingSaveBlocks as $saveBlock) {
				// das ist zwar in der saveBlocksForWeek() abgefangen aber sicher ist sicher
				unset($saveBlock['inquiries_courses']);
				// Template beibehalten
				$saveBlock['template'] = $saveBlock['original']['template'];
				$saveBlocks[(int)$saveBlock['block_id']] = $saveBlock;
			}

			// Aktuell geöffneten Block in $saveBlocks überschreiben damit die Änderungen mit in setSaveBlocks() gehen
			$saveBlocks[(int)$thisSaveBlock['block_id']] = $thisSaveBlock;

			$class->setCurrentWeek($week->getTimestamp());
			$class->setSaveBlocks($saveBlocks);

			\DB::begin(__METHOD__);

			$errors = $class->validate();

			if ($errors === true) {
				try {

					$errors = $class->lock()->save();

				} catch (EntityLockedException) {
					return response()
						->json([
							'success' => false,
							'errors' => [
								[TeacherPortal::l10n()->translate('The class is currently being edited by someone else. Please try again at a later date.')]
							]
						]);
				}
			}

			if (is_array($errors)) {
				// Fehlermeldungen anpassen damit solche die sich auf den Lehrer beziehen nicht mit "Der Lehrer..." angezeigt werden
				$helper = new \Ext_Thebing_Tuition_Class_Helper_ErrorMessage($class, TeacherPortal::l10n());
				$helper->setCustomMessages([
					'TEACHER_ALLOCATED' => 'You are not available for the selected date and time.',
					'INVALID_TEACHER_WORKTIME' => 'You are not available for the selected date and time.',
					'ATTENDANCE_EXISTS' => 'Attendances have already been entered for this block. The date or time cannot be changed',
					'ATTENDANCE_FOUND' => 'Attendances have already been entered for this block. The time cannot be changed',
					'ATTENDANCE_EXISTS_FOR_DAYS' => 'Attendances have already been entered for this block. The date cannot be changed'
				]);

				$grouped = [];
				foreach ($errors as $field => $messages) {
					foreach ($messages as $messageKey) {
						$grouped[$field][] = $helper->getErrorMessage($messageKey, $field);
					}
				}

				\DB::rollback(__METHOD__);

				return response()
					->json([
						'success' => false,
						'errors' => $grouped
					]);
			}

			if ($class->teacher_can_add_students && $request->exists('students')) {
				$students = Arr::wrap($request->input('students'));

				if (!$block->exist()) {
					$block = Arr::first($class->getBlocks());
				}

				if (!$block || !$block->exist()) {
					throw new \RuntimeException('Missing block for student assignment');
				}

				$failed = [];
				foreach ($students as $studentKey) {
					[$inquiryId, $journeyCourseId, $programServiceId] = explode('_', $studentKey, 3);
					$inquiry = \Ext_TS_Inquiry::getInstance($inquiryId);
					$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($journeyCourseId);

					if ($journeyCourse->getJourney()->inquiry_id != $inquiryId) {
						throw new \RuntimeException('Invalid journey course object');
					}

					$check = $block->checkInquiryCourse($journeyCourse, $programServiceId, $data['room_id'], 1);

					if ($check === true) {
						$block->addInquiryCourse($journeyCourse, $programServiceId, $data['room_id']);
					} else {
						$failed[$inquiry->id] = [$inquiry, $check];
					}
				}

				if (!empty($failed)) {

					$errors = [];
					foreach ($failed as $inquiryData) {
						/* @var \Ext_TS_Inquiry_Contact_Traveller $traveller */
						$traveller = $inquiryData[0]->getTraveller();
						$errors['students[]'][] = sprintf(TeacherPortal::l10n()->translate('Student "%s %s" could not be assigned.'), $traveller->getCustomerNumber(), $traveller->getName());
					}

					\DB::rollback(__METHOD__);

					return response()
						->json([
							'success' => false,
							'errors' => $errors
						]);
				}

				// Lektionen überprüfen
				$students = $block->getAllocations();

				foreach ($students as $allocation) {
					$journeyCourse = $allocation->getJourneyCourse();
					$programService = $allocation->getProgramService();

					$remaining = $journeyCourse->getTuitionRemainingLessons($programService, $week);

					if ($remaining['allocated_lessons'] > $remaining['course_lessons']) {

						\DB::rollback(__METHOD__);

						return response()
							->json([
								'success' => false,
								'errors' => [
									'lessons' => [
										TeacherPortal::l10n()->translate('The number of lessons does not match the number of lessons available for the students.')
									]
								]
							]);
					}
				}
			}

			\DB::commit(__METHOD__);

		} else if ($request->has('description')) {
			$block->description = $request->input('description');
			$block->save();
		}

		\Ext_Gui2_Index_Stack::save();

		return response()
			->json(['success' => true]);
	}

	public function searchAvailableStudentsForClass(Request $request)
	{
		$date = $request->input('date');
		$query = $request->input('search');
		$courseId = $request->input('course_id');

		if (
			empty($date) || empty($query) ||
			empty($date =\Ext_Thebing_Format::ConvertDate($date, $this->getSchool()->id, 1)) ||
			empty($dateObject = Carbon::createFromFormat('Y-m-d', $date))
		) {
			return response()->json(['students' => []]);
		}

		$course = (!empty($courseId))
			? \Ext_Thebing_Tuition_Course::query()->findOrFail($courseId)
			: null;

		$teacher = $this->getTeacher();

		$oSearch = \Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry::buildSearchObject($query, \Ext_TS_Inquiry::TYPE_BOOKING_STRING);
		$oSearch->setFields(['_id', 'customer_number', 'customer_name', 'tuition_teachers', 'course_from_original', 'course_until_original']);

		$oBool = new \Elastica\Query\BoolQuery();
		$oBool->setMinimumShouldMatch(1);

		$oQuery = new \Elastica\Query\QueryString();
		$oQuery->setQuery((string)$this->getTeacher()->id);
		$oQuery->setDefaultField('tuition_teachers');
		$oQuery->setDefaultOperator('AND');
		$oBool->addShould($oQuery);

		$oQuery = new \Elastica\Query\Range('course_from_original', ['lte' => $dateObject->toDateString()]);
		$oBool->addMust($oQuery);
		$oQuery = new \Elastica\Query\Range('course_until_original', ['gte' => $dateObject->toDateString()]);
		$oBool->addMust($oQuery);

		$oSearch->addMustQuery($oBool);

		$oSearch->setSort('created_original');
		$oSearch->setLimit(100);

		$aResult = $oSearch->search();

		$journeyCourses = [];
		foreach($aResult['hits'] as $hit) {

			$inquiry = \Ext_TS_Inquiry::getInstance($hit['_id']);
			$inquiryJourneyCourses = array_filter($inquiry->getCourses(), function (\Ext_TS_Inquiry_Journey_Course $journeyCourse) use ($teacher, $dateObject) {
				if (
					$journeyCourse->courselanguage_id !== null &&
					!in_array($journeyCourse->courselanguage_id, $teacher->course_languages)
				) {
					return false;
				}
				$from = new Carbon($journeyCourse->getFrom());
				$until = new Carbon($journeyCourse->getUntil());
				return $from <= $dateObject && $until >= $dateObject;
			});

			$journeyCourses = array_merge($journeyCourses, $inquiryJourneyCourses);
		}

		return response()->json(['students' => $this->buildStudentsSelectOptions($dateObject, $journeyCourses, $course)]);
	}

	public function loadAvailableClassRooms(Request $request)
	{
		$classId = $request->input('class_id');
		$blockId = $request->input('block_id');
		$date = $request->input('date');
		$time = $request->input('time');

		if (empty($classId) || empty($blockId)) {
			return response('Bad request', 403);
		}

		$dateObject = null;
		if (!empty($date) && !empty($time)) {
			$date =\Ext_Thebing_Format::ConvertDate($date, $this->getSchool()->id, 1);
			$dateObject = Carbon::createFromFormat('Y-m-d H:i', implode(' ', [$date, $time]));
		}

		$block = \Ext_Thebing_School_Tuition_Block::query()
			->where('class_id', (int)$classId)
			->findOrFail((int)$blockId);

		$rooms = $this->getRoomsSelectOptions($block, $dateObject);

		return response()
			->json([
				'rooms' => $rooms
			]);
	}

	private function getRoomsSelectOptions(\Ext_Thebing_School_Tuition_Block $block, Carbon $date = null): array {

		$selectOptions = [];

		if ($date) {
			$teacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
			$school = $teacher->getSchool();

			$week = $date->clone()->startOfWeek(Carbon::MONDAY);

			$teacherPortalRoomIds = collect($school->getClassRooms(sValidUntil: $date->toDateString()))
				->filter(fn (\Ext_Thebing_Tuition_Classroom $classroom) => (bool)$classroom->teacher_portal)
				->keys()
				->merge($block->rooms);

			$availableRooms = collect(
					$block->getAvailableRooms(
						$week,
						false,
						$date->toTimeString('minutes'),
						// Wir gehen hier erstmal nur von einer halben Stunde aus, die tatsächliche Dauer ergibt sich erst
						// sobald ein Kurs ausgewählt ist
						$date->clone()->addMinutes(30)->toTimeString('minutes'),
						[$date->format('N')]
					)
				)
				->only($teacherPortalRoomIds);

			if ($availableRooms->isNotEmpty()) {

				$selectOptions = $availableRooms
					->map(fn($text, $value) => ['value' => $value, 'text' => $text]);

				if (ExternalApp::getAllowClassWithoutRoom()) {
					$selectOptions = $selectOptions->prepend(['value' => '0', 'text' => \L10N::t('No room')]);
				}

				if ($selectOptions->count() > 1) {
					$selectOptions = $selectOptions->prepend(['value' => '', 'text' => \L10N::t('Please choose')]);
				}

				$selectOptions = $selectOptions->values()
					->toArray();

			} else {
				if (ExternalApp::getAllowClassWithoutRoom()) {
					$selectOptions[] = ['value' => '0', 'text' => \L10N::t('No room')];
				} else {
					$selectOptions[] = ['value' => '', 'text' => \L10N::t('There is no room available at this time')];
				}
			}

		} else {
			$selectOptions[] = ['value' => '', 'text' => sprintf('-- %s --', \L10N::t('Please select a day and time first'))];
		}

		return $selectOptions;
	}

	private function smarty()
	{
		$oSmarty = new \SmartyWrapper();
		$oSmarty->registerPlugin("modifier","format_date", function ($date) {
			return \Ext_Thebing_Format::LocalDate($date, $this->getSchool()->id);
		});

		return $oSmarty;
	}

	private function getCurrentCalendar(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'date' => ['required', 'date'],
			'view' => ['required', Rule::in(['day', 'week', 'month'])]
		]);

		if ($validator->fails()) {
			throw new \RuntimeException('Invalid or missing calendar parameters');
		}

		$date = Carbon::make($request->input('date'));
		$view = $request->input('view');

		if ($view === 'day') {
			$period = CarbonPeriod::create(
				$date->startOfDay(),
				$date->endOfDay()
			);
		} else if ($view === 'week') {
			$period = CarbonPeriod::create(
				$date->clone()->startOfWeek(),
				$date->clone()->endOfWeek()->endOfDay()
			);
		} else {
			$period = CarbonPeriod::create(
				$date->clone()->startOfMonth(),
				$date->clone()->endOfMonth()->endOfDay()
			);
		}

		return [$view, $period];
	}

	private function buildStudentsSelectOptions(Carbon $date, array $journeyCourses, \Ext_Thebing_Tuition_Course $course = null)
	{
		$students = [];

		foreach ($journeyCourses as $journeyCourse) {
			/* @var \Ext_TS_Inquiry_Journey_Course $journeyCourse */
			$services = $journeyCourse->getProgram()->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

			$inquiry = $journeyCourse->getJourney()->getInquiry();
			$traveller = $inquiry->getTraveller();

			$selector = new \Illuminate\Translation\MessageSelector();

			foreach ($services as $service) {

				if ($course && $service->type_id != $course->id) {
					continue;
				}

				$remaining = $journeyCourse->getTuitionRemainingLessons($service, $date->startOfWeek(1));

				$lessons = sprintf(
					$selector->choose('%s Lektion|%s Lektionen', $remaining['remaining_lessons'], \System::getInterfaceLanguage()),
					\Ext_Thebing_Format::Number($remaining['remaining_lessons'], null, $inquiry->getSchool())
				);

				$student = [
					'id' => implode('_', [$inquiry->id, $journeyCourse->id, $service->id]),
					'text' => sprintf(
						'%s %s - %s (%s)',
						$traveller->getCustomerNumber(),
						$traveller->getName(),
						$service->getService()->getName(\System::getInterfaceLanguage()),
						$lessons
					),
					'course' => [
						'id' => $service->type_id,
						'text' => $service->getService()->getName(\System::getInterfaceLanguage())
					]
				];

				$students[] = $student;
			}
		}

		return array_values($students);
	}

	public function loadBlockStateModalContent(Request $request, $block_id, $day)
	{
		$smarty = $this->smarty();

		$template = \Util::getDocumentRoot().'system/bundles/TsTeacherLogin/Resources/views/pages/timetable/block_state.tpl';

		return response()
			->json([
				'title' => TeacherPortal::l10n()->translate('Cancel block unit'),
				'html' => $smarty->fetch($template)
			]);
	}

	public function saveBlockState(Request $request, $block_id, $day)
	{
		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))->make(
			data: $request->all(),
			rules: [
				'state' => ['required', Rule::in(\TsTuition\Entity\Block\Unit::STATE_CANCELLED)],
				'comment' => ['required'],
			],
			customAttributes: [
				'comment' => \L10N::t('Kommentar'),
			]
		);

		if ($validator->fails()) {
			return response()
				->json([
					'success' => false,
					'errors' => $validator->getMessageBag()->toArray()
				]);
		}

		$state = $request->input('state');

		try {

			$block = \Ext_Thebing_School_Tuition_Block::getInstance($block_id);

			$unit = $block->getUnit($day)
				->addState($state, $request->input('comment'))
				->save();

			if ($state & \TsTuition\Entity\Block\Unit::STATE_CANCELLED) {
				(new BlockCancellationService($block))->lazyUpdate();
			}

		} catch (\Throwable) {
			return response()
				->json([
					'success' => false,
					'errors' => [
						'modal' => [
							TeacherPortal::l10n()->translate('Unable to save state.')
						]
					]
				]);
		}

		if ($state & \TsTuition\Entity\Block\Unit::STATE_CANCELLED) {
			BlockCanceled::dispatch($unit, ActionSource::TEACHER_PORTAL, $this->getTeacher());
		}

		return response()
			->json([
				'success' => true
			]);
	}

}
