<?php

namespace TsRegistrationForm\Generator\ServiceBlock;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Core\Factory\ValidatorFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use TsFrontend\Entity\BookingTemplate;
use TsRegistrationForm\Dto\FrontendCourse;
use TsRegistrationForm\Dto\FrontendService;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Helper\FormValidatorHelper;
use TsRegistrationForm\Service\InquiryBuilder;
use TsRegistrationForm\Validation\Rules\CourseAgeRule;
use TsRegistrationForm\Validation\Rules\CourseStartRule;

readonly class CourseBlock implements ServiceBlockInterface
{
	public function __construct(private CombinationGenerator $combination, private Collection $data)
	{
	}

	public function generateCacheData(\Ext_TS_Frontend_Combination_Inquiry_Helper_Services $helper, array &$additionalServices): void
	{
		$dateFormat = new \Ext_Thebing_Gui2_Format_Date('frontend_date_format', $this->combination->getSchool()->id);

		$usedLevels = collect();
		$allLevels = collect($this->combination->getSchool()->getLevelList(true, $this->combination->getLanguage()->getLanguage(), 'normal', false))
			->map(fn(string $label, int $key) => compact('key', 'label'))
			->values();

		$courses = new Collection(); /** @var FrontendCourse[] $courses */
		$courseDatesPerCourse = [];
		foreach ($helper->getCourses() as $dto) {

			$startDates = collect();
			/** @var Collection|\Carbon\CarbonInterface[] $startDates */
			$datesLevelDependency = $datesLanguageDependency = false;
			foreach ($dto->aStartDates as $startDateDto) {
				if (!empty($startDateDto->levels)) {
					// Hiermit wird das Startdatum gesperrt, bis ein Level ausgewählt wurde
					$datesLevelDependency = true;
				}
				if (!empty($startDateDto->courselanguages)) {
					// Hiermit wird das Startdatum gesperrt, bis eine Sprache ausgewählt wurde
					$datesLanguageDependency = true;
				}
				$startDates[] = $startDateDto->start;
				$courseDatesPerCourse[$dto->oCourse->id][] = [
					'start' => $startDateDto->start->toDateString(),
					'min' => $startDateDto->minDuration,
					'max' => $startDateDto->maxDuration,
					'levels' => $startDateDto->levels,
					'languages' => $startDateDto->courselanguages,
				];
			}

			$levels = $allLevels->pluck('key');
			if ($dto->oCourse->start_level_id) {
				$key = $allLevels->search(fn(array $level) => $level['key'] === (int)$dto->oCourse->start_level_id);
				$levels = $allLevels->slice($key)->pluck('key');
			}

			$usedLevels = $usedLevels->merge($levels);

			$course = new FrontendCourse();
			$course->key = $dto->oCourse->id;
			$course->type = $dto->oCourse->getType();
			$course->label = $dto->oCourse->getFrontendName($this->combination->getLanguage()->getLanguage()) ?: $dto->oCourse->getName($this->combination->getLanguage()->getLanguage());
			$course->lessons_unit = $dto->oCourse->lessons_unit;
			$course->levels = $levels->toArray();
			$course->show_level = $dto->bShowLevel;
			$course->show_duration = empty($dto->oCourse->fix_duration) && !in_array($dto->oCourse->getType(), ['exam', 'program']);
			$course->lessons = $dto->oCourse->getType() === 'unit' && $dto->oCourse->lessons_fix ? $dto->oCourse->lessons_list : [];
			$course->dates_level_dependency = $datesLevelDependency;
			$course->dates_language_dependency = $datesLanguageDependency;
			$course->description = $dto->oCourse->{'description_' . $this->combination->getLanguage()->getLanguage()};
			$course->accommodations = $dto->oCourse->accommodation_combinations_joined;
			$course->age = ['min' => (int)$dto->oCourse->minimum_age, 'max' => (int)$dto->oCourse->maximum_age];
			$course->blocks = array_map(fn($block) => $block->getServiceBlockKey(), $dto->aBlocks);
			$course->dependencies = array_map('intval', $dto->oCourse->preparation_courses + $dto->oCourse->preparation_courses_parents);
			$course->languages = array_map(fn($v) => (int)$v, $dto->oCourse->course_languages);
			$course->additional_services = array_column($dto->additionalServices, 'id');
			$course->programs = $dto->oCourse->getPrograms()->reduce(function (array $carry, \TsTuition\Entity\Course\Program $program) use ($dto, $startDates, $dateFormat) {
				$from = $program->getFrom();
				if ($from === null || $startDates->contains($from)) {
					$carry[] = ['key' => (int)$program->getId(), 'label' => $program->getNameFormatted($dateFormat)];
				}
				return $carry;
			}, []);

			$courses->put($dto->oCourse->id, $course);

			foreach ($dto->additionalServices as $additionalService) {
				$additionalServices[$additionalService->id] = $additionalService;
			}

		}

		// Gleiche Startdaten-Collections sammeln, damit Datenmenge reduziert werden kann
		$courseDates = new Collection();
		foreach ($courseDatesPerCourse as $courseId => $startDates) {
			$key = md5(serialize($startDates));
			$courses->get($courseId)->dates_key = $key;
			$courseDates->put($key, $startDates);
		}

		$this->generateCourseGroupings($courses);

		$this->data->put('courses', $courses->values());
		$this->data->put('course_dates', $courseDates);
		$this->data->put('course_levels', $allLevels->filter(fn(array $level) => $usedLevels->contains($level['key']))->values());
	}

	/**
	 * Mögliche Gruppierungen der Kurse (Kategorien, Sprachen) generieren: Labels und Baum für Kurse
	 *
	 * @param Collection<FrontendCourse> $courses
	 */
	private function generateCourseGroupings(Collection $courses)
	{
		$courseGroupings = $this->data->get('course_groupings', collect());
		$categoriesPerCourse = [];

		$courseStructure = \TsFrontend\Service\CourseStructure::getInstance($this->combination->getSchool());
		$structure = $courseStructure->getStructure();

		$this->generateCourseGroupingsCategory($courseGroupings, $categoriesPerCourse, $structure->getChilds());

		// Kategorien aussortieren, die nicht verwendet werden
		$courseGroupingsUsed = Arr::flatten($categoriesPerCourse);
		$courseGroupings = $courseGroupings->filter(fn(array $grouping) => in_array($grouping['key'], $courseGroupingsUsed));

		// Kurs-Pfade vom Baum in die Kurse mergen
		foreach ($courses as $course) {
			$course->categories = [...$course->categories, ...($categoriesPerCourse[(int)$course->key] ?? [])];
		}

		$courseLanguageIds = $courses->pluck('languages')
			->flatten()
			->unique();

		$courseLanguages = \Ext_Thebing_Tuition_LevelGroup::query()
			->whereIn('id', $courseLanguageIds)
			->get()
			->map(function (\Ext_Thebing_Tuition_LevelGroup $language) {
				return [
					'type' => 'language',
					'key' => (int)$language->id,
					'label' => $language->getName($this->combination->getLanguage()->getLanguage()),
					'icon' => $language->frontend_icon_class
				];
			});

		// Da Blöcke mehrfach kommen können, könnten auch alle Gruppierungsarten in einem Formular verwendet werden
		$this->data->put('course_groupings', $courseGroupings
			->merge($courseLanguages)
			->unique(fn(array $g) => $g['type'].'_'.$g['key'])
			->values()
		);
	}

	/**
	 * Kurskategorien-Baum durchlaufen und für Form aufbereiten – es kann auch Unterkategorien geben durch das Drag&Drop
	 *
	 * @param Collection $courseGroupings
	 * @param array $categoriesPerCourse Der Einfachheit halber ein Array, damit [][] funktioniert
	 * @param \TsFrontend\DTO\CourseStructure\Node[] $childs
	 * @param \TsFrontend\DTO\CourseStructure\Node|null $parent
	 */
	private function generateCourseGroupingsCategory(Collection $courseGroupings, array &$categoriesPerCourse, array $childs, \TsFrontend\DTO\CourseStructure\Node $parent = null)
	{
		foreach ($childs as $child) {
			switch ($child->getType()) {
				case 'category':
					$this->generateCourseGroupingsCategory($courseGroupings, $categoriesPerCourse, $child->getChilds(), $child);
					$courseGroupings[] = [
						'type' => $child->getType(),
						'key' => $child->getId(),
						'label' => $child->getName($this->combination->getLanguage()->getLanguage()),
						'icon' => $child->getIcon()
					];
					if ($parent !== null) {
						// Wenn Kategorie ein Parent hat, in alle Kurse mit dieser Child-Kategorie die Parent-Kategorie hinzufügen
						$categoriesPerCourse = array_map(function (array $courseGroupingIds) use ($child, $parent) {
							if (in_array($child->getId(), $courseGroupingIds)) {
								array_unshift($courseGroupingIds, $parent->getId());
							}
							return $courseGroupingIds;
						}, $categoriesPerCourse);
					}
					continue 2;
				case 'course':
					// Es darf eigentlich keinen Kurs auf der obersten Ebene geben
					if ($parent !== null && $parent->getType() === 'category') {
						$categoriesPerCourse[$child->getId()][] = $parent->getId();
					}
					continue 2;
				default:
					throw new \RuntimeException('Unknown node type: ' . $child->getType());
			}
		}
	}

	public function generateData(): void
	{
		$classBlocks = $this->combination->getForm()->getFilteredBlocks(function (\Ext_Thebing_Form_Page_Block $b) {
			return (int)$b->block_id === \Ext_Thebing_Form_Page_Block::TYPE_COURSES && $b->getSetting('based_on') === 'scheduling';
		});

		if (empty($classBlocks)) {
			return;
		}

		$allLevels = collect();
//		$allLanguages = [];
		$courses = collect();
		$blockCourses = [];
		$courseSettings = [];

		// Kurs-IDs aus Blöcken sammeln
		foreach ($classBlocks as $block) {
			$settings = $block->getSettings();
			foreach ($settings as $key => $value) {
				if ($value && preg_match('/course_(\d+)/', $key, $matches)) {
					$blockCourses[$block->getServiceBlockKey()][] = $matches[1];
					$courseSettings[$matches[1]] = Arr::only(
						$settings,
						['use_default_template', 'default_template']
					);
				}
			}
		}

		foreach ($this->generateClasses(Arr::flatten($blockCourses)) as $classData) {
			if (empty($classData['dates'])) {
				continue;
			}

			foreach ($classData['blocks'] as $block) {
				if ($block['level_id']) {
					$allLevels->put($block['level_id'], [$block['level_name'], $block['level_position']]);
				}
			}

			$class = $classData['class']; /** @var \Ext_Thebing_Tuition_Class $class */
			$course = $class->getBookableCourse();
			$program = $course->getFirstProgram(); // Kurse in Klassen können keine Programme sein
			$language = $class->getCourseLanguage();

			$frontendCourse = new FrontendCourse();
			$frontendCourse->key = sprintf('%d:class_%d', $course->id, $class->id); // Kurs-ID am Anfang, damit parseInt() die Service-ID für Abhängigkeiten findet
			$frontendCourse->type = $course->getType();
			$frontendCourse->label = $course->getFrontendName($this->combination->getLanguage()->getLanguage()) ?: $course->getName($this->combination->getLanguage()->getLanguage());
			$frontendCourse->bookable_only_in_full = (bool)$class->bookable_only_in_full;
			$frontendCourse->description = $course->{'description_' . $this->combination->getLanguage()->getLanguage()};
			$frontendCourse->description_list = $this->buildClassDescriptionList($classData, $courseSettings[$course->id] ?? []);
			if (!empty($classData['_description_html'])) {
				$frontendCourse->description_html = (string)$classData['_description_html'];
			}
			// dd($frontendCourse->description_list);
			$frontendCourse->blocks = array_keys(array_filter($blockCourses, fn(array $courses) => in_array($course->id, $courses)));
			$frontendCourse->dates_key = 'class_' . $class->id;
			$frontendCourse->accommodations = $course->accommodation_combinations_joined;
			$frontendCourse->show_level = count($classData['levels']) > 1;
			$frontendCourse->show_duration = empty($class->bookable_only_in_full);
			$frontendCourse->dates_level_dependency = $frontendCourse->show_level;
			$frontendCourse->levels = array_keys($classData['levels']);
			$frontendCourse->age = ['min' => (int)$course->minimum_age, 'max' => (int)$course->maximum_age];
			$frontendCourse->programs = [['key' => $program->id, 'label' => $program->getName()]];
			$frontendCourse->languages = [$language->id];
			$frontendCourse->class = ['class' => (int)$class->id, 'blocks' => $classData['blocks']];

//			$allLanguages[$language->id] = $language;
			$courses->push($frontendCourse);

			$this->data->get('courses')->push($frontendCourse);
			$this->data->get('course_dates')->put('class_' . $class->id, $classData['dates']);
		}

		$this->generateCourseGroupings($courses);

//		foreach ($courses as $frontendCourse) {
//			// Sprache nur in der Klasse anzeigen wenn es in der ganzen Liste mehr als eine gibt
//			if (count($allLanguages) > 1) {
//				$language = $allLanguages[reset($frontendCourse->languages)];
//				$line = [$this->combination->getLanguage()->translate('Sprache'), $language->getName($this->combination->getLanguage()->getLanguage())];
//				array_splice( $frontendCourse->description_list, 2, 0, [$line]);
//			}
//		}

		// Interne Level hinzufügen
		$allLevels->sortBy(fn(array $l) => $l[1])
			->each(fn(array $l, int $key) => $this->data->get('course_levels')->push(['key' => $key, 'label' => $l[0]]));
	}

	private function buildClassDescriptionList(array &$classData, array $settings = []): array
	{
		$list = [];
		$class = $classData['class']; /** @var \Ext_Thebing_Tuition_Class $class */
		$course = $class->getBookableCourse();
		$language = $class->getCourseLanguage();
		$firstDate = (new \Ext_Thebing_Gui2_Format_Date('frontend_date_format', $this->combination->getSchool()->id))->formatByValue(Arr::first($classData['dates'])['start']);

		$days = [];
		foreach ($classData['blocks'] as $block) {
			$key = sprintf('%s_%s_%s', $block['days_list'], $block['from'], $block['until']);
			if (!isset($days[$key])) {
				$parts = [];
				$parts[] = \Ext_Thebing_Util::buildJoinedWeekdaysString($block['days'], $this->combination->getLanguage()->getLanguage());
				$parts[] = sprintf('%s-%s', substr($block['from'], 0 ,5), substr($block['until'], 0, 5));
				if (!empty($parts)) {
					$days[$key] = implode(' ', $parts);
				}
			}
		}


		$smarty = new \SmartyWrapper();

		if ($class->bookable_only_in_full) {
			$smarty->assign('startDate', $firstDate);
			$smarty->assign('duration', sprintf('%d %s', Arr::first($classData['dates'])['max'], $this->combination->getLanguage()->translate('Wochen')));
		} else {
			$smarty->assign('scheduleFrom', $firstDate);
		}

		if (!empty($days)) {
			$smarty->assign('days', $days);
		}

		if (count($course->course_languages) > 1) {
			$smarty->assign('languages', $language->getName($this->combination->getLanguage()->getLanguage()));
		}

		$smarty->assign('language', $language->getName($this->combination->getLanguage()->getLanguage()));

		if (!empty($classData['levels'])) {
			// Level ist optional in Klasse
			$list[] = [$this->combination->getLanguage()->translate('Level'), join(', ', $classData['levels'])];
		}

		$defaultTemplate = \Util::getDocumentRoot().'storage/templates/class/class_booking.tpl';

		try {
			$template = $settings['use_default_template'] == 0
				? 'string:' . $settings['default_template']
				: $defaultTemplate;

			$html = $smarty->fetch($template);
		} catch (\Throwable $e) {
			$html = $smarty->fetch($defaultTemplate);
		}


		$classData['_description_html'] = $html;

		return $list;
	}

	/**
	 * Klasse müssen immer sofort und live generiert werden, da diese an nichts hängen (im Gegensatz zu Aktivitäten)
	 */
	private function generateClasses(array $courseIds): Collection
	{
		// Buchungstemplate als Filter
		if (
			($process = $this->combination->getBookingGenerator()->getProcess()) instanceof BookingTemplate &&
			$process->course_as_filter
		) {
			$courseLanguageId = $process->courselanguage_id;
			$courseIds = array_intersect($courseIds, [$process->course_id]);
		}

		$from = \Ext_Thebing_Util::getNextCourseStartDay(Carbon::today('UTC'), $this->combination->getSchool()->course_startday);
		$until = $from->copy()->addYears((int)$this->combination->getSchool()->frontend_years_of_bookable_services);
		$classes = \Ext_Thebing_Tuition_Class::getRepository()
			->findOnlineBookableBySchoolAndPeriodAndLanguage($this->combination->getSchool()->id, $from, $until, $courseIds, $courseLanguageId ?? null);

		$classes->transform(function (\Ext_Thebing_Tuition_Class $class, int $index) use ($from, $until) {
			$offset = 0;
			$end = $class->getLastDate();
			$data = [
				'class' => $class,
				'blocks' => [],
				'dates' => [],
				'levels' => [],
				'start' => null,
				'position' => $index
			];
			// bookable_only_in_full Klassen müssen unabhängig von frontend_years_of_bookable_services komplett durchlaufen
			for ($week = $from->copy(); $week <= $end && ($week <= $until || $class->bookable_only_in_full); $week->addWeek()) {
				$weekBlocks = \Ext_Thebing_Tuition_Class::getRepository()
					->getFreeBlocksForWeek($class, $week, $this->combination->getLanguage()->getLanguage());

				// Keine Blöcke verfügbar, max. Wochen bei Verfügbarkeit unterbrechen
				if (empty($weekBlocks)) {
					$offset = count($data['dates']);
					continue;
				}

				$days = [];
				foreach ($weekBlocks as $block) {
					$block['week'] = $week->copy();
					$block['rooms'] = !empty($block['room_ids']) ? explode(',', $block['room_ids']) : [0];
					$block['days'] = explode(',', $block['days_list']);
					$data['blocks'][] = $block;
					$days = array_merge($days, $block['days']);
					if (!empty($block['level_id'])) {
						$data['levels'][$block['level_id']] = $block['level_name'];
					}
				}

				// Max. Wochen bei allen bisherigen Daten erhöhen
				foreach ($data['dates'] as $i => &$date) {
					if ($i >= $offset) {
						$date['max']++;
					}
				}

				$startDate = $week->copy()->addDays(min($days) - 1);
				$data['start'] = empty($data['start']) ? $startDate : $data['start'];

				$data['dates'][] = [
					'start' => $startDate->toDateString(),
					'min' => 1,
					'max' => 1,
					'levels' => array_filter(array_map(fn(array $block) => (int)$block['level_id'], $weekBlocks)),
					'languages' => []
				];
			}

			// If bookable_only_in_full then only first date and full length allowed
			if (
				$class->bookable_only_in_full &&
				count($data['dates'])
			) {
				array_splice($data['dates'],1);
				$data['dates'][0]['min'] = $data['dates'][0]['max'] = $class->weeks;
			}

			return $data;
		});

		return $classes->sortBy([
			fn(array $a, array $b) => $a['start'] <=> $b['start'],
			fn (array $a, array $b) => $a['position'] <=> $b['position'],
		]);
	}

	public function transform(InquiryBuilder $builder, int $level): void
	{
		$courseBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_COURSES, false);

		foreach ($builder->getServiceRequestData('courses') as $course) {

			// Der erste Kurs kommt immer mit, sobald der Block vorhanden ist, daher vor Validierung aussortieren
			if ($course['course'] === null) {
				continue;
			}

			$frontendCourse = $this->data->get('courses')->firstWhere('key', $course['course']);

			if ($frontendCourse === null) {
				$this->combination->log('Course '.$course['course'].' does not exist in form cache, skipping', [$course]);
				continue;
			}

			if (!$frontendCourse instanceof FrontendCourse) {
				// Siehe CombinationGenerator::getSchoolData()
				$frontendCourse = (new FrontendCourse())->fromArray($frontendCourse);
			}

			// If bookableOnlyInFull, start and duration selects are disabled in Form. Allowed is only first date and full duration.
			if ($frontendCourse->bookable_only_in_full) {
				$startDates = $this->data['course_dates']->get($frontendCourse->dates_key, []);
				if (isset($startDates[0])) {
					$course['start'] = $startDates[0]['start'];
					$course['duration'] = $startDates[0]['max'];
				}
			}

			$course['start'] = $builder->convertRequestDate($course['start']);

			if ($frontendCourse->type === 'exam') {
				// Felder werden im Form nur ausgeblendet
				$course['duration'] = 1;
				$course['units'] = 1;
			} elseif($frontendCourse->type !== 'unit') {
				// Da das Feld nur ausgeblendet wird, muss der Wert gelöscht werden
				$course['units'] = 0;
			}

			$frontendCourse->additional['student_age'] = $builder->getInquiry()->getFirstTraveller()->getAge($course['start']);
			$frontendCourse->additional['request_course'] = $course; // Ferien

			$validator = (new ValidatorFactory())->make($course, $this->buildValidationRules($level, $frontendCourse));

			if (!$validator->passes()) {
				$messages = $validator->messages()->messages();

				// Falsches Alter für Kurs Fehlermeldung von Startdatum auf Geburtsdatum ändern
				// Normalerweise wirft die Frontend-Validierung den Kurs vorher raus, das aber nicht bei gesperrten Feldern
				if (
					isset($validator->failed()['start'][CourseAgeRule::class]) &&
					isset($this->data->get('fields')['fields'][\Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE])
				) {
					unset($messages['start']); // MessageBag->forget() erst ab Laravel 10
					$message = $courseBlock->getTranslation('service_removed_age', $this->combination->getLanguage());
					$builder->addValidatorErrors([\Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE => [$message]], 'fields');
				}

				// Gesperrte Felder dürfen keine Fehler aufweisen, da diese nicht mehr entsperrt werden können im Formular
				foreach (array_keys($messages) as $field) {
					if (!empty($course['field_state'][$field])) {
						throw new \RuntimeException(sprintf('Field %s is locked but got a validation error: %s', $field, reset($messages[$field])));
					}
				}

				$builder->addValidatorErrors($messages, 'services.'.$course['block'].'.'.$course['index']);
				continue;
			}

			$from = $course['start'];
			$until = $from ? \Ext_Thebing_Util::getCourseEndDate($from, (int)$course['duration'], (int)$this->combination->getSchool()->course_startday) : null;

			$journeyCourse = new \Ext_TS_Inquiry_Journey_Course();
			$journeyCourse->transients[\Ext_TS_Inquiry_Journey_Service::TRANSIENT_FORM_SERVICE] = true;
			$journeyCourse->transients['block'] = $course['block']; // Wird benötigt, um Kurs im korrekten Block halten zu können, wenn Service-Blöcke mehrfach verwendet werden
			$journeyCourse->transients['index'] = $course['index']; // Wird für korrekte Übertragung der Feriensplittung benötigt
			$journeyCourse->transients['form_course'] = $frontendCourse; // Wird für ServiceMutation benötigt, damit gültige Unterkünfte abgeglichen werden können
			$journeyCourse->course_id = (int)$course['course'];
			$journeyCourse->courselanguage_id = $course['language'];
			$journeyCourse->level_id = !$frontendCourse->isClass() ? $course['level'] : 0; // Keine internen Level setzen
			$journeyCourse->units = $course['units'];
			$journeyCourse->weeks = $course['duration'];
			$journeyCourse->program_id = $course['program'];
			$journeyCourse->from = $from?->toDateString();
			$journeyCourse->until = $until?->toDateString();
			$journeyCourse->calculate = 1;
			$journeyCourse->visible = 1;
			$journeyCourse->for_tuition = 1;

			// Da alle Kurse beim (erneuten) Ferien-Split überschrieben werden, muss diese Info wieder gesetzt werden
			foreach ($course['field_state'] as $field => $state) {
				$journeyCourse->transients['field_state_'.$field] = $state; // field_state_course etc.
			}

			$builder->setJourneyChild('courses', $journeyCourse);

			// Zuweisung zur Klasse
			if ($frontendCourse->isClass()) {
				$week = \Ext_Thebing_Util::getPreviousCourseStartDay($from, $this->combination->getSchool()->course_startday);
				$period = CarbonPeriod::create($week, $week->copy()->addWeeks($journeyCourse->weeks)->subDay());
				$blocks = array_filter($frontendCourse->class['blocks'], fn(array $block) => $period->contains($block['week']));
				$services = $journeyCourse->getProgram()->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

				if (count($services) !== 1) {
					throw new \DomainException('Too many services! Wrong course allocated in class?');
				}

				$lessons = 0;
				foreach ($blocks as $block) {
					/** @var \Ext_Thebing_School_Tuition_Allocation $allocation */
					$allocation = $journeyCourse->getJoinedObjectChild('tuition_blocks_writeable');
					$allocation->block_id = $block['block_id'];
					$allocation->room_id = reset($block['rooms']);
					$allocation->course_id = $journeyCourse->course_id;
					$allocation->program_service_id = $services->first()->id;
					$allocation->lesson_duration = count($block['days']) * $block['lessons'] * $block['lesson_duration'];
					$allocation->automatic = 1;

					$lessons += count($block['days']) * $block['lessons'];
				}

				if ($frontendCourse->type === 'unit') {
					$journeyCourse->units = $lessons;
				}
			}

			$journeyCourse->adjustData();
			$journeyCourse->adjustLessonsContingents(); // Wird benötigt für Preisberechnung

			$builder->transformAdditionalServices($journeyCourse, $course, $frontendCourse->toArray());
		}

		// Ferien ergänzen
		if (($period = $builder->getDateHelper()->getCoursePeriod()) !== null) {
			$builder->getHelper()->mergeCourseHolidays($builder->getInquiry(), $period);
		}
	}

	public function check(\Ext_TS_Inquiry $inquiry, string $trigger, array &$actions): void
	{
		if (!str_contains($trigger, 'courses')) {
			return;
		}

		$journey = $inquiry->getJourney();
		$splitter = new \Ext_TS_Inquiry_Journey_Holiday_Split($journey);
		$splitter->split();

		if (
			$splitter->hasMoved() ||
			$splitter->hasSplittings()
		) {
			$blocks = [];
			$courseBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_COURSES, false);

			// Hier werden einfach alle Kurse ersetzt
			// Kurse, die beim Validieren nicht gültig waren, fallen hier einfach durch und bleiben erhalten (keine Mutation)
			foreach ($journey->getCoursesAsObjects() as $journeyCourse) {
				$blocks[] = $journeyCourse->transients['block'];
				$actions[] = [
					'handler' => 'insertService',
					'type' => $journeyCourse->transients['block'],
					'service' => $journeyCourse->getRegistrationFormData()
				];
			}

			if (count($journey->getCoursesAsObjects()) >= 10) {
				throw new \RuntimeException('Too many courses! Infinity loop because course merging does not work?');
			}

			// Alle Kurse löschen und ersetzen, damit Feriensplittung-Prinzip nicht durcheinander kommt
			foreach ($blocks as $block) {
				array_unshift($actions, [
					'handler' => 'deleteAllServices',
					'type' => $block
				]);
			}

			$actions[] = [
				'handler' => 'addNotification',
				'key' => 'course_changed',
				'type' => 'warning',
				'message' => $courseBlock->getTranslation('serviceChanged')
			];
		}
	}

	public function buildValidationRules(int $level, FrontendService|FrontendCourse $service = null): array
	{
		if ($level === FormValidatorHelper::VALIDATE_CLIENT_ALL) {
			return [
				'course' => ['fn:required'],
				'language' => ['fn:required'],
				'level' => ['fn:requiredIf:requiredIfCourseLevel'],
				'start' => ['fn:requiredIf:requiredIfCourseStart'],
				'duration' => ['fn:requiredIf:requiredIfCourseDuration', 'fn:integer', 'fn:between:1:156'],
				'units' => ['fn:requiredIf:requiredIfCourseUnit', 'fn:integer', 'fn:between:1:' . \Ext_Thebing_Tuition_Course::getMaxUnits()],
				'program' => ['fn:required']
			];
		}

		$ruleCourseIds = Rule::in($this->data->get('courses')->pluck('key'));

		$rules = [
			'course' => ['required', $ruleCourseIds],
			'language' => ['required', 'integer'],
			'level' => [],
			'start' => ['required', 'date'],
			'duration' => ['required', 'integer', 'between:1,156'],
			'units' => ['required', 'integer', 'between:1,' . \Ext_Thebing_Tuition_Course::getMaxUnits()],
			'program' => ['required', 'integer']
		];

		if ($service !== null) {
			$rules['language'][] = Rule::in(Arr::flatten($service->languages));

			// Level
			if ($service->show_level) {
				/** @var Collection $levels */
				$levels = $this->data->get('course_levels')->pluck('key');

				$rules['level'][] = 'required';
				$rules['level'][] = 'integer';
				$rules['level'][] = Rule::in($levels);
			}

			if ($service->type !== 'program') {
				// Startdaten
				$startDates = array_column($this->data['course_dates']->get($service->dates_key, []), 'start');
				$rules['start'][] = new CourseStartRule($startDates, $service);
			} else {
				$rules['start'] = [];
				$rules['duration'] = [];
			}

			// Lektionen
			if ($service->type !== 'unit' || $service->isClass()) {
				$rules['units'] = [];
			} elseif (!empty($service->lessons)) {
				$rules['units'][] = Rule::in($service->lessons);
			}

			$rules['program'][] = Rule::in(array_column($service->programs, 'key'));
		}

		if (
			$level & FormValidatorHelper::VALIDATE_SERVER_ALL &&
			$service !== null
		) {
			// Alter prüfen – falls Geburtsdatum nach Kurs kommt und submitted wird
			$rules['start'][] = new CourseAgeRule($service->additional['student_age'], $service->age['min'], $service->age['max']);
		}

		return $rules;
	}
}