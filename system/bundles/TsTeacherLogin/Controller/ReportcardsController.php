<?php

namespace TsTeacherLogin\Controller;

use Carbon\Carbon;
use Core\Handler\SessionHandler as Session;
use Core\Helper\DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\Period\Period;
use TsTeacherLogin\Traits\SchoolViewPeriod;
use TsTuition\Entity\Course\Program\Service;

class ReportcardsController extends InterfaceController {
	use SchoolViewPeriod;

	protected string $viewPeriod = 'reportcards';

	/**
	 * @var Session
	 */
	protected $oSession;

	const SESSION_KEY_ACCESSIBLE_VERSIONS = 'teacherlogin_reportcards_accessible_versions';

	/**
	 * * @throws \RuntimeException
	 *
	 * @return mixed
	 */
	public function getReportcardsView(Request $oRequest) {

		$aViewData = [];

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$oSchool = $oTeacher->getSchool();

		$dWeekFrom = new Carbon();
		$dWeekUntil = new Carbon();

		$this->prepareDates($oRequest, $dWeekFrom, $dWeekUntil, $aViewData, $oSchool);

		$examinationFrom = $dWeekFrom;
		$examinationUntil = $dWeekUntil;

		if (!$this->checkDateInViewPeriod($oSchool, $examinationFrom)) {
			$examinationFrom = Carbon::make($this->getViewPeriod($oSchool)->start());
		}

		if (!$this->checkDateInViewPeriod($oSchool, $examinationUntil)) {
			$examinationUntil = Carbon::make($this->getViewPeriod($oSchool)->end());
		}

		$sDaterangepickerFormat = \Ext_Thebing_Format::getDateFormat($oSchool->id,'backend_datepicker_format');
		$sDaterangepickerFormat = strtoupper($sDaterangepickerFormat);
		$aViewData['sDaterangepickerFormat'] = $sDaterangepickerFormat;

		$aExaminationResult = $this->getExaminationData($examinationFrom, $examinationUntil);
		$aViewData['aExamsData'] = [];

		// Nach existierenden und automatisch generierten Einträgen aufsplitten da die existierenden zuerst durchlaufen
		// werden müssen damit \Ext_Thebing_Examination_Gui2::checkIfTableRowExists() was liefert
		[$aExistingEntries, $aGeneratedEntries] = collect($aExaminationResult)
			->partition(fn ($result) => $result['examination_id'] > 0);

		$aExistingReportCards = [];
		// Bereits vorhandene
		foreach($aExistingEntries as $aData) {

			$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($aData['inquiry_course_id']);

			$this->setIndependentData($aData, $oJourneyCourse);

			$oVersion = \Ext_Thebing_Examination_Version::getInstance($aData['examination_version_id']);

			if (empty($oVersion->examination_date)) {
				throw new \RuntimeException('Invalid examination date ' . $oVersion->examination_date);
			}

			$dDate = new DateTime($oVersion->examination_date);

			if ($dDate < $examinationFrom || $dDate > $examinationUntil) {
				continue;
			}

			$sDate = \Ext_Thebing_Format::LocalDate($dDate, $oSchool->id);
			$aData['examination_date_formatted'] = $sDate;
			$aData['examination_date_object'] = $dDate;

			$aExistingReportCards[] = $aData;

		}

		// Automatisch generierte
		foreach($aGeneratedEntries as $aData) {

			$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($aData['inquiry_course_id']);

			$this->setIndependentData($aData, $oJourneyCourse);

			$dCourseFrom = new DateTime($oJourneyCourse->from);
			$dCourseUntil = new DateTime($oJourneyCourse->until);

			$aData['examination_id'] = 0;
			$aData['examination_version_id'] = 0;
			$oExaminationTemplate = \Ext_Thebing_Examination_Templates::getInstance($aData['template_id']);

			$aTerms = $oExaminationTemplate->getTerms();

			foreach($aTerms as $oTerm) {

				$aDates = $oTerm->getExaminationDates($dCourseFrom, $dCourseUntil);
				$aData['examination_term_id'] = $oTerm->id;

				foreach($aDates as $dDate) {

					if ($dDate < $examinationFrom || $dDate > $examinationUntil) {
						continue;
					}

					$sDate = \Ext_Thebing_Format::LocalDate($dDate, $oSchool->id);
					$aData['examination_date_formatted'] = $sDate;
					$aData['examination_date_object'] = $dDate;

					$mExistingIndex = \Ext_Thebing_Examination_Gui2::checkIfTableRowExists($aData, $aExistingReportCards);

					if($mExistingIndex === false) {
						$aViewData['aExamsData'][$aData['examination_date_object']->format('Ymd')][] = $aData;
					} else {
						$aExistingReportCards[$mExistingIndex]['add'] = true;
					}
				}

			}

		}

		$oSession = Session::getInstance();
		$aAccessibleVersions = $oSession->get(self::SESSION_KEY_ACCESSIBLE_VERSIONS, []);

		// Übrige, manuell angelegte Reportcards des Lehrers werden ergänzt
		foreach($aExistingReportCards as $aExistingReportCard) {

			$aTeachers = (array)explode(',', $aExistingReportCard['teacher_ids']);

			// Nur ergänzen, wenn eingeloggter Lehrer = Prüfer oder wenn ein berechneter Eintrag existiert
			if(
				$aExistingReportCard['add'] === true ||
				in_array($this->_oAccess->id, $aTeachers)
			) {
				$aAccessibleVersions[] = $aExistingReportCard['examination_version_id'];
				$aViewData['aExamsData'][$aExistingReportCard['examination_date_object']->format('Ymd')][] = $aExistingReportCard;
			}

		}

		$oSession->set(self::SESSION_KEY_ACCESSIBLE_VERSIONS, $aAccessibleVersions);

		ksort($aViewData['aExamsData']);

		$aViewData['oTeacher'] = $oTeacher;
		$aViewData['sSystemLogo'] = $this->get('sSystemLogo');
		$aViewData['aLogos'] = $this->get('aLogos');
		$aViewData['aExamsData'] = Arr::flatten($aViewData['aExamsData'], 1);

		return response()->view('pages/reportcards', $aViewData);
	}

	/**
	 * Setzt die Daten, die in beiden Fällen (manuell erstellte und automatisch generierte Einträge) gesetzt werden müssen.
	 *
	 * @param array $aData
	 * @param \Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 */
	private function setIndependentData(array &$aData, \Ext_TS_Inquiry_Journey_Course $oJourneyCourse) {

		$oExamination = \Ext_Thebing_Examination::getInstance($aData['examination_id']);
		$oContact = \Ext_TS_Contact::getInstance($aData['contact_id']);
		$oProgramService = Service::getInstance($aData['program_service_id']);

		/*$oCourseObj = \Ext_Thebing_Tuition_Course::getInstance($oJourneyCourse->course_id);

		$aCourses = [];
		foreach($oCourseObj->getChildCoursesOrSameCourse() as $oCourse) {
			$aCourses[$aData['inquiry_course_id'].'_'.$oCourse->id.'_'.$oCourse->getPrograms()->first()->id] = $oCourse->name_short;
		}*/

		$aData['course_name'] = $oProgramService->getService()->name_short;

		/*if(!empty($oExamination->id)) {
			$aData['course_name'] = $aCourses[$oExamination->inquiry_course_course];
		}*/

		$dCourseFrom = new DateTime($oJourneyCourse->from);
		$dCourseUntil = new DateTime($oJourneyCourse->until);

		$aData['examination_term_id'] = $oExamination->examination_term_id;
		$aData['examination_name'] = $aData['examination_template_name'];

		$sCourseFrom = \Ext_Thebing_Format::LocalDate($dCourseFrom, $oJourneyCourse->getSchoolId());
		$aData['course_from'] = $sCourseFrom;

		$sCourseUntil = \Ext_Thebing_Format::LocalDate($dCourseUntil, $oJourneyCourse->getSchoolId());
		$aData['course_until'] = $sCourseUntil;

		$aData['student_id'] = $oContact->id;
		$aData['student_name'] = $oContact->getName();
		$aData['student_email'] = $oContact->getFirstEmailAddress()->getEmail();

	}

	/**
	 * @param Request $oRequest
	 * @param Carbon $dWeekFrom
	 * @param Carbon $dWeekUntil
	 * @param array $aViewData
	 * @param \Ext_Thebing_School $oSchool
	 * @return void
	 */
	protected function prepareDates(Request $oRequest, Carbon &$dWeekFrom, Carbon &$dWeekUntil, array &$aViewData, \Ext_Thebing_School $oSchool) {

		if($oRequest->has('week')) {
			$dWeekFrom = Carbon::createFromFormat('Y-m-d', $oRequest->input('week'));
			if(!$dWeekFrom || !$this->checkDateInViewWeekPeriod($oSchool, $dWeekFrom)) {
				$dWeekFrom = Carbon::now()->startOfWeek(Carbon::MONDAY);
			}
		} else {
			$dWeekFrom = $dWeekFrom->startOfWeek(Carbon::MONDAY);
		}

		$dWeekUntil = $dWeekFrom->clone()->endOfWeek(Carbon::SUNDAY);

		$bIsCurrentWeek = $dWeekUntil > new DateTime();

		$aViewData['sBackendWeekFrom'] = $dWeekFrom->format('Y-m-d');
		$aViewData['sWeekFrom'] = \Ext_Thebing_Format::LocalDate($dWeekFrom, $oSchool->id);
		$aViewData['sWeekUntil'] = \Ext_Thebing_Format::LocalDate($dWeekUntil, $oSchool->id);
		$aViewData['bPreviousWeek'] = $this->checkDateInViewWeekPeriod($oSchool, $dWeekFrom->clone()->subWeek());
		$aViewData['bNextWeek'] = $this->checkDateInViewWeekPeriod($oSchool, $dWeekFrom->clone()->addWeek());
		$aViewData['bIsCurrentWeek'] = $bIsCurrentWeek;
	}

	/**
	 * @param Carbon $dWeekFrom
	 * @param Carbon $dWeekUntil
	 * @return array
	 */
	public function getExaminationData(Carbon $dWeekFrom, Carbon $dWeekUntil) {

		$dWeekFrom = $dWeekFrom->startOfWeek(Carbon::MONDAY);
		$dWeekUntil = $dWeekUntil->endOfWeek(Carbon::SUNDAY);

		$sSql = "
			SELECT
				*,
			  	NULL `add`
			FROM ( 
			(
				/* Zu generieren */
				SELECT 
					NULL `examination_id`,
					NULL `examination_version_id`,
					`kext`.`id` `template_id`,
					`kext`.`title` `examination_template_name`,
					`ts_itc`.`contact_id`,
					`ts_ijc`.`id` `inquiry_course_id`,
				    `ktbic`.`program_service_id`,
					NULL `teacher_ids`
				FROM
					`kolumbus_tuition_blocks` `ktb` LEFT JOIN
					`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
						`ktbst`.`block_id` = `ktb`.`id` AND
						`ktbst`.`active` = 1 INNER JOIN
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
						`ktbic`.`block_id` = `ktb`.`id` AND
						`ktbic`.`active` = 1 INNER JOIN
					`kolumbus_examination_templates_courses` `kextc` ON
						`kextc`.`course_id` = `ktbic`.`course_id` INNER JOIN 	
					`kolumbus_examination_templates` `kext` ON
						`kextc`.`examination_template_id` = `kext`.`id` AND
						`kext`.`active` = 1 INNER JOIN
					`ts_inquiries_journeys_courses` `ts_ijc` ON
						`ts_ijc`.`id` = `ktbic`.`inquiry_course_id` INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ijc`.`journey_id` = `ts_ij`.`id` INNER JOIN
					`ts_inquiries` `ts_i` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` INNER JOIN
					`ts_inquiries_to_contacts` `ts_itc` ON
						`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
						`ts_itc`.`type` = 'traveller'
				WHERE
					`ktb`.`week` = :week_from AND
					`ktb`.`active` = 1 AND
				  	`ts_i`.`canceled` <= 0 AND
					(
						`ktb`.`teacher_id` = :teacher_id OR 
						`ktbst`.`teacher_id` = :teacher_id
					)
		  		GROUP BY
		  			`ts_ijc`.`id`, `ktbic`.`program_service_id`, `kext`.`id`
			) UNION ALL (
				/* Bereits vorhandene */
				SELECT 
					`kex`.`id` `examination_id`,
					`kexv`.`id` `examination_version_id`,
					`kext`.`id` `template_id`,
					`kext`.`title` `examination_template_name`,
					`ts_itc`.`contact_id`,
					`ts_ijc`.`id` `inquiry_course_id`,
					`kex`.`program_service_id`,
				    GROUP_CONCAT(`kexvt`.`teacher_id`) `teacher_ids`
				FROM
					`kolumbus_examination` `kex` INNER JOIN
					`kolumbus_examination_templates` `kext` ON
					  	`kext`.`id` = `kex`.`examination_template_id` INNER JOIN
					`kolumbus_examination_version` `kexv` ON
						`kexv`.`examination_id` = `kex`.`id` AND
						`kexv`.`active` = 1 AND
						`kexv`.`id` = (
							SELECT
								`id`
							FROM
								`kolumbus_examination_version`
							WHERE
								`examination_id` = `kexv`.`examination_id`
							ORDER BY
								`created` DESC
							LIMIT 1
						) INNER JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON 
					    `ts_tcps`.`id` = `kex`.`program_service_id` AND 
						`ts_tcps`.`type` = '".Service::TYPE_COURSE."' INNER JOIN	
					`kolumbus_tuition_courses` `ktc` ON
						`ts_tcps`.`type_id` = `ktc`.`id` AND
						`ktc`.`active` = 1 INNER JOIN
					`ts_inquiries_journeys_courses` `ts_ijc` ON
						`ts_ijc`.`id` = `kex`.`inquiry_course_id` AND
						`ts_ijc`.`active` = 1 INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
						`ts_ij`.`active` = 1 INNER JOIN
					`ts_inquiries` `ts_i` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i`.`active` = 1 INNER JOIN
					`ts_inquiries_to_contacts` `ts_itc` ON
						`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
						`ts_itc`.`type` = 'traveller' INNER JOIN
					`kolumbus_inquiries_documents` `kid` ON
						`kid`.`id` = `kex`.`document_id` AND
						`kid`.`active` = 1 AND
						`kid`.`type` = 'examination' LEFT JOIN
					`kolumbus_examination_version_teachers` `kexvt` ON
						`kexv`.`id` = `kexvt`.`examination_version_id`
				WHERE
					`kex`.`active` = 1 AND
					`kexv`.`examination_date` BETWEEN :week_from AND :week_until
				GROUP BY
					`kex`.`id`
			)
		) `result`
		";

		$aSql = [
			'teacher_id' => (int)$this->_oAccess->id,
			'week_from' => $dWeekFrom->toDateString(),
			'week_until' => $dWeekUntil->toDateString()
		];

		$aData = \DB::getPreparedQueryData($sSql, $aSql);

		return $aData;
	}

	/**
	 * @param $iVersionId
	 * @return mixed
	 */
 	public function openFile($iVersionId) {

		$oSession = Session::getInstance();
		$aAccessibleVersions = $oSession->get(self::SESSION_KEY_ACCESSIBLE_VERSIONS, []);

		if(in_array($iVersionId, $aAccessibleVersions)) {

			$oVersion = \Ext_Thebing_Examination_Version::getInstance($iVersionId);

			$oExamination = $oVersion->getExamination();
			$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($oExamination->document_id);
			$oDocumentVersion = $oDocument->getLastVersion();

			$sPDFPath = '';

			if(is_object($oDocumentVersion) && $oDocumentVersion instanceof \Ext_Thebing_Inquiry_Document_Version) {
				$sPDFPath = $oDocumentVersion->getPath(true);
			}

			return response()->file($sPDFPath);

		} else {
			return abort(403, 'Unauthorized action.');
		}

	}

	/**
	 * @param $iTemplateId
	 * @param $iInquiryCourseId
	 * @param $sExaminationDate
	 * @param $iVersionId
	 * @param $iExaminationId
	 * 
	 * @return mixed
	 */
	public function getReportcardsModal($iTemplateId, $iInquiryCourseId, $sExaminationDate, $iVersionId, $iExaminationId, $iProgramServiceId) {

		$oSmarty = new \SmartyWrapper();

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$oSchool = $oTeacher->getSchool();

		$aReturnJsonArray = [];

		$oSession = Session::getInstance();
		$aAccessibleVersions = $oSession->get(self::SESSION_KEY_ACCESSIBLE_VERSIONS, []);

		if(
			in_array($iVersionId, $aAccessibleVersions) ||
			$iVersionId == 0
		) {
			$oProgramService = Service::getInstance($iProgramServiceId);

			$oVersion = \Ext_Thebing_Examination_Version::getInstance($iVersionId);
			$aVersionData = $oVersion->getData();

			if(is_numeric($aVersionData['score'])) {

				if((float)$aVersionData['score'] >= (float)$oSchool->examination_score_passed) {
					$aVersionData['passed'] = 1;
				} else {
					$aVersionData['passed'] = 0;
				}

			}
			$oSmarty->assign('aVersionData', $aVersionData);

			$dExaminationDate = new DateTime($sExaminationDate);
			$aReturnJsonArray['examination_date'] = \Ext_Thebing_Format::LocalDate($dExaminationDate, $oSchool->id);

			$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
			$oInquiry = $oJourneyCourse->getInquiry();

			$dCourseFrom = new DateTime($oJourneyCourse->from);
			$aReturnJsonArray['date_from'] = $dCourseFrom->format('Y-m-d');
			$dCourseUntil = new DateTime($oJourneyCourse->until);
			$aReturnJsonArray['date_until'] = $dCourseUntil->format('Y-m-d');

			$oCourse = $oProgramService->getService();

			$oExaminationTemplate = \Ext_Thebing_Examination_Templates::getInstance($iTemplateId);
			$oSmarty->assign('oExaminationTemplate', $oExaminationTemplate);

			$aCourses = [];
			#foreach($oCourseObj->getChildCoursesOrSameCourse() as $oCourse) {
			$aCourses[$iInquiryCourseId.'_'.$oCourse->id.'_'.(int)$iProgramServiceId] = $oCourse->getName();
			#}

			$oSmarty->assign('aCourses', $aCourses);

			$sSelectedCourse = array_key_first($aCourses);

			if(!empty($iExaminationId)) {
				$oExamination = \Ext_Thebing_Examination::getInstance($iExaminationId);
				$sSelectedCourse = $oExamination->inquiry_course_course;
			}
			$oSmarty->assign('sSelectedCourse', $sSelectedCourse);

			$aLevels = $oSchool->getLevelList(true, $oSchool->getInterfaceLanguage(), 'internal');
			$oSmarty->assign('aLevels', $aLevels);

			if($iVersionId == 0) {
				$iSelectedLevel = $oInquiry->getLastLevel('id', $oJourneyCourse, ['date_until' => $sExaminationDate]);
			} else {
				$iSelectedLevel = $oVersion->level_id;
			}
			$oSmarty->assign('iSelectedLevel', $iSelectedLevel);

			$oContact = $oInquiry->getCustomer();
			$oSmarty->assign('oContact', $oContact);

			$aSections = $oVersion->getSections($iTemplateId);

			$aCategories = [];
			foreach($aSections as $sCategoryName => &$aCategoryEntities) {

				foreach($aCategoryEntities as &$aEntity) {

					$aEntity['model_class'] = new $aEntity['model_class']();
					$aEntity['model_class']->setSectionId($aEntity['id']);
					$oSection = \Ext_Thebing_Examination_Sections::getInstance($aEntity['id']);

					if($aEntity['model_class']->getInput() === 'select') {
						$aEntity['section_select_options'] = $oSection->getOptions();
					}

					$aEntity['section_value'] = $aEntity['model_class']->getValueByVersion($iVersionId, false);

				}

				$aCategories[$sCategoryName] = $aCategoryEntities;

			}

			$oSmarty->assign('aCategories', $aCategories);

			$sTemplatePath = \Util::getDocumentRoot().'system/bundles/TsTeacherLogin/Resources/views/pages/reportcards_modal_form.tpl';
			$sBody = $oSmarty->fetch($sTemplatePath);

			$aReturnJsonArray['authorized'] = true;
			$aReturnJsonArray['body'] = $sBody;

		} else {
			$aReturnJsonArray['authorized'] = false;
		}

		return response()->json($aReturnJsonArray);
	}

	/**
	 * @return mixed
	 *
	 */
	public function saveReportcardsModal() {

		\DB::begin(__METHOD__);

		$aReturnJsonArray = [];

		$sSelectedCourse = $this->_oRequest->input('course');
		$aIds = explode('_', $sSelectedCourse);
		$iInquiryCourseId = (int)$aIds[0];

		$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);

		if(!empty($this->_oRequest->get('examination_id'))) {

			// Bereits vorhandene
			$oExamination = \Ext_Thebing_Examination::getInstance($this->_oRequest->get('examination_id'));

		} else {

			// Automatisch generierte
			$oExamination = \Ext_Thebing_Examination::getInstance();
			$oExamination->examination_term_id = $this->_oRequest->get('examination_term_id');
			$oExamination->examination_template_id = $this->_oRequest->get('template_id');

		}

		$oExamination->inquiry_course_course = $sSelectedCourse;
		
		$oVersion = \Ext_Thebing_Examination_Version::getInstance();
		$oVersion->level_id = $this->_oRequest->get('level');
		$oVersion->score = $this->_oRequest->get('score');
		$oVersion->passed = $this->_oRequest->get('passed');
		$oVersion->grade = $this->_oRequest->get('grade');
		$oVersion->examination_date = $this->_oRequest->get('examination_date');
		$oVersion->comment_sections = $this->_oRequest->get('sections_comment');
		$oVersion->comment = $this->_oRequest->get('comment');
		$oVersion->from = $oJourneyCourse->from;
		$oVersion->until = $oJourneyCourse->until;
		$oVersion->sections = $this->_oRequest->input('sections');
		$oVersion->teachers = [$this->_oAccess->id];

		$mValidate = $oVersion->validate();

		if($mValidate === true) {

			$oExamination->save();

			/*
			 * Das darf nicht über die WDBasic Verknüpfungen gespeichert werden, da die Version-Entity komplett
			 * vergewaltigt wurde und nicht zu verwenden ist.
			 */
			$oVersion->examination_id = $oExamination->id;
			$oVersion->save();

			$oPersister = \WDBasic_Persister::getInstance();
			$oPersister->save();

			$aReturnJsonArray['valid'] = true;

			\DB::commit(__METHOD__);

		} else {

			$aReturnJsonArray['valid'] = false;

			\DB::rollback(__METHOD__);

		}

		$oLog = \Log::getLogger('teacherlogin_reportcards');
		$oLog->addInfo('Save', [$aReturnJsonArray, $this->_oRequest->getAll()]);

		return response()->json($aReturnJsonArray);
	}

	/**
	 * @param $iInquiryCourseId
	 * @param $iProgramServiceId
	 * @return mixed
	 */
	public function calculateAverageScore($iInquiryCourseId, $iProgramServiceId) {

		$aReturnJsonArray = [];

		//$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
		//$oCourse = \Ext_Thebing_Tuition_Course::getInstance($oJourneyCourse->course_id);

		$mAverage = \Ext_Thebing_Tuition_Attendance::getAverageScoreForInquiryCourse($iInquiryCourseId, $iProgramServiceId);
		$aReturnJsonArray['average_score'] = $mAverage;

		return response()->json($aReturnJsonArray);
	}

	public function emailToStudent(int $iVersionId, int $iStudentId) {

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$oContact = \Ext_TS_Contact::getInstance($iStudentId);

		$oVersion = \Ext_Thebing_Examination_Version::getInstance($iVersionId);
		$oExamination = $oVersion->getExamination();
		$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($oExamination->document_id);
		$oInquiry = $oDocument->getInquiry();
		$iTravellerId = $oInquiry->getFirstTraveller()->id;

		if(
			!$oContact->exist() ||
			$iTravellerId != $iStudentId
		) {
			return abort(403, 'Unauthorized action.');
		}

		$aReturnJsonArray = [];

		$oSession = Session::getInstance();
		$aAccessibleVersions = $oSession->get(self::SESSION_KEY_ACCESSIBLE_VERSIONS, []);

		if(in_array($iVersionId, $aAccessibleVersions)) {

			$oDocumentVersion = $oDocument->getLastVersion();

			if(is_object($oDocumentVersion) && $oDocumentVersion instanceof \Ext_Thebing_Inquiry_Document_Version) {

				$sPDFPath = $oDocumentVersion->getPath(true);

				$oSchool = $oTeacher->getSchool();
				$oTemplate = $oSchool->getJoinedObject('teacherlogin_reportcard_template');

				if($oTemplate->id == 0) {
					$aReturnJsonArray['message'] = \L10N::t('The e-mail could not be sent, no template was found! Please get in touch with your contact person at the school');

					return response()->json($aReturnJsonArray);
				}

				$oPlaceholder = new \Ext_Thebing_Teacher_Placeholder($oTeacher->id);

				$oEmail = new \Ts\Service\Email($oTemplate, $oSchool->language);
				$oEmail->setSchool($oSchool);
				$oEmail->setEntity($oTeacher);
				$oEmail->setPlaceholder($oPlaceholder);
				$oEmail->setAttachments([$sPDFPath => $oDocumentVersion->id]);

				$aTo = [$oContact->getFirstEmailAddress()->getEmail()];

				$bSent = $oEmail->send($aTo);

				if($bSent) {
					$aReturnJsonArray['success'] = true;
					$aReturnJsonArray['message'] = \L10N::t('The email has been sent successfully.');
				} else {
					$aReturnJsonArray['message'] = \L10N::t('Error occurred! The email could not be sent.');
				}

			} else {
				$aReturnJsonArray['message'] = \L10N::t('The document could not be found!');
			}

		} else {
			return abort(403, 'Unauthorized action.');
		}

		return response()->json($aReturnJsonArray);
	}

}
