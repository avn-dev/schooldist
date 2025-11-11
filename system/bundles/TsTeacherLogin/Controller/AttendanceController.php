<?php

namespace TsTeacherLogin\Controller;

use Carbon\Carbon;
use Core\Handler\SessionHandler as Session;
use Core\Helper\DateTime;
use Core\Handler\CookieHandler;
use \BaconQrCode\Renderer\ImageRenderer;
use \BaconQrCode\Renderer\Image\SvgImageBackEnd;
use \BaconQrCode\Renderer\RendererStyle\RendererStyle;
use \BaconQrCode\Writer;
use Illuminate\Http\Request;
use Spatie\Period\Period;
use TsTeacherLogin\Traits\SchoolViewPeriod;
use TsTuition\Service\TrackingSession;

class AttendanceController extends InterfaceController {
	use SchoolViewPeriod;

	protected string $viewPeriod = 'attendance';

	/**
	 * @var Session
	 */
	protected $oSession;


	public function getAttendanceView(Request $oRequest) {

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$this->set('oTeacher', $oTeacher);

		$oSchool = $oTeacher->getSchool();

		$dWeekFrom = new Carbon();
		$dWeekUntil = new Carbon();

		$this->prepareDates($oRequest, $dWeekFrom, $dWeekUntil, $oSchool);

		$sViewType = $oSchool->teacherlogin_attendance_view;

		if($oRequest->has('view_type')) {
			$sViewType = $oRequest->input('view_type');
		} elseif(CookieHandler::is('teacherlogin_attendance_view')) {
			$sViewType = CookieHandler::get('teacherlogin_attendance_view');
		}

		$iCookieExpiration = time() + (30 * 24 * 3600);

		CookieHandler::set('teacherlogin_attendance_view', $sViewType, $iCookieExpiration);

		$this->set('sViewType', $sViewType);

		$aAbsenceReasons = \TsTuition\Entity\AbsenceReason::getOptions(true);
		if (!empty($aAbsenceReasons)) {
			$aAbsenceReasons = \Util::addEmptyItem($aAbsenceReasons);
		}
		$this->set('aAbsenceReasons', $aAbsenceReasons);
		$aAbsenceReasonsAll = \TsTuition\Entity\AbsenceReason::getOptions();
		$this->set('aAbsenceReasonsAll', \Util::addEmptyItem($aAbsenceReasonsAll));
		
		if($oRequest->has('period')) {
			$sPeriod = $oRequest->input('period');
		} else {
			$sPeriod = 'daily';
		}

		if(
			$sPeriod === 'daily' ||
			$sViewType === 'simple'
		) {
			$bDaily = true;
			$bWeekly = false;
		} else {
			$bDaily = false;
			$bWeekly = true;
		}

		$this->set('sPeriod', $sPeriod);
		$this->set('bDaily', $bDaily);
		$this->set('bWeekly', $bWeekly);

		$selectedTeacher = $oTeacher;
		$teacherWasSelected = false;
		if ($oTeacher->access_right_teachers != 0) {
			// Wenn Lehrer das Recht auf alle Klassen hat, dann ihn ein Lehrer (sich selber z.B.) auswählen lassen können
			// und dann später nur die Klassen dieses Lehrers anzeigen.
			$this->set('teachers', \Util::addEmptyItem($oSchool->getTeacherList(true), '-- '.\L10N::t('Lehrer').' --'));

			$selectedTeacherId = $oRequest->input('teacher');
			if(!empty($selectedTeacherId)) {
				$this->set('selectedTeacherId', $selectedTeacherId);
				$selectedTeacher = \Ext_Thebing_Teacher::getInstance($selectedTeacherId);
				$teacherWasSelected = true;
			}
		}

		$aAttendanceBlocks = $this->prepareBlocks($selectedTeacher, $dWeekFrom, $dWeekUntil, $sPeriod, $teacherWasSelected);

		// Die Blocktage, auf denen der Lehrer Zugriff hat
		$aBlocksAccessibleDays = $aAttendanceBlocks['blocks_days'];

		// Item raus nehmen, damit nur Block-IDs als Keys bleiben.
		unset($aAttendanceBlocks['blocks_days']);

		// Wenn keine Blöcke vorhanden sind, wird das im Template abgefragt und ein entsprechender Hinweis angezeigt!
		if(empty($aAttendanceBlocks['blocks'])) {

			$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/attendance.tpl';
			$this->_oView->setTemplate($sTemplate);

			return;
		}

		$sBlockKey = '';
		if(
			$oRequest->has('block') &&
			array_key_exists($oRequest->input('block'), $aAttendanceBlocks['blocks'])
		) {
			// Wenn der ausgewählte Block auch vom Lehrer ausgewählt würden könnte bzw. wenn der Lehrer auf den Block auch
			// rechte hat, ansonsten wird sowieso der 1. Eintrag vom Select ausgewählt, dann stimmen aber die Blöcke nicht
			// mit dem ausgewählten Eintrag überein.
			$sBlockKey = $oRequest->input('block');

		} elseif(!empty($aAttendanceBlocks['blocks'])) {

			reset($aAttendanceBlocks['blocks']);
			$sBlockKey = key($aAttendanceBlocks['blocks']);

		}

		$this->set('sBlockKey', $sBlockKey);

		$fLessonDuration = 0.0;

		if(!empty($sBlockKey)) {

			list($iBlockId, $sBlockDay) = explode('_', $sBlockKey, 2);
			$this->set('iBlockId', $iBlockId);

			$aBlockAccessibleDays = $aBlocksAccessibleDays[$iBlockId];

			if(empty($aBlockAccessibleDays)) {
				$aBlockAccessibleDays = [];
			}
			$aDays = $this->prepareDays($aBlockAccessibleDays, $sBlockDay);

			$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

			$fLessonDuration = $oBlock->getLessonDuration();

			$this->prepareStudents($oBlock, $aDays, $sViewType);

		} else {
			$this->set('aDays', []);
		}

		$this->set('fLessonDuration', $fLessonDuration);

		$bExpandFieldsBlock = false;

		if($oSchool->teacherlogin_flex_expand === '1') {
			$bExpandFieldsBlock = true;
		}

		$this->set('bExpandFieldsBlock', $bExpandFieldsBlock);

		$this->prepareFlexFields($oSchool);

		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/attendance.tpl';
		$this->_oView->setTemplate($sTemplate);

	}

	/**
	 * @param Request $oRequest
	 * @param Carbon $dWeekFrom
	 * @param Carbon $dWeekUntil
	 * @param \Ext_Thebing_School $oSchool
	 * @return void
	 */
	protected function prepareDates(Request $oRequest, Carbon &$dWeekFrom, Carbon &$dWeekUntil, \Ext_Thebing_School $oSchool) {

		$checkDateIsAvailable = function($date) use ($oSchool) {
			$dEndOfCurrentWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);
			if ($date <= $dEndOfCurrentWeek && $this->checkDateInViewWeekPeriod($oSchool, $date)) {
				return true;
			}
			return false;
		};

		if($oRequest->has('week')) {
			$dWeekFrom = Carbon::createFromFormat('Y-m-d', $oRequest->input('week'));
			if(!$dWeekFrom || !$checkDateIsAvailable($dWeekFrom)) {
				$dWeekFrom = Carbon::now()->startOfWeek(Carbon::MONDAY);
			}
		} else {
			$dWeekFrom = $dWeekFrom->clone()->startOfWeek(Carbon::MONDAY);
		}

		$dWeekFrom = $dWeekFrom->clone()->startOfDay();
		$dWeekUntil = $dWeekFrom->clone()->endOfWeek(Carbon::SUNDAY)->endOfDay();

		$this->set('sBackendWeekFrom', $dWeekFrom->format('Y-m-d'));
		$this->set('sWeekFrom', \Ext_Thebing_Format::LocalDate($dWeekFrom, $oSchool->id));
		$this->set('sWeekUntil', \Ext_Thebing_Format::LocalDate($dWeekUntil, $oSchool->id));
		$this->set('bPreviousWeek', $this->checkDateInViewWeekPeriod($oSchool, $dWeekFrom->clone()->subWeek()));
		$this->set('bNextWeek', $checkDateIsAvailable($dWeekFrom->clone()->addWeek()));
		$this->set('bIsCurrentWeek', $dWeekUntil > new DateTime());
	}

	/**
	 * @param \Ext_Thebing_Teacher $oTeacher
	 * @param Carbon $dWeekFrom
	 * @param Carbon $dWeekUntil
	 * @param $sPeriod
	 * @return array|array[]|mixed|null
	 * @throws \Exception
	 */
	protected function prepareBlocks(\Ext_Thebing_Teacher $oTeacher, Carbon $dWeekFrom, Carbon $dWeekUntil, $sPeriod, $teacherWasSelected = false) {

		$oSchool = $oTeacher->getSchool();
		// Betroffene Schulen ermitteln
		$schools = $oTeacher->schools;
		if ($teacherWasSelected) {
			// Lehrer ist nicht der eingeloggte Lehrer, Schulen zusätzlich auf dessen Schulen begrenzen
			$loggedInTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
			$schools = array_intersect($schools, $loggedInTeacher->schools);
		}

		$sLanguage = \TsTeacherLogin\Helper\Data::getSelectedOrDefaultLanguage();

		$sCacheKey = __METHOD__.'_'.$sLanguage.'_'.$oTeacher->id.'_'.$dWeekFrom->getTimestamp().'_'.$sPeriod;
		$aAttendanceBlocks = \WDCache::get($sCacheKey);
		$aAttendanceBlocks = null;

		if($aAttendanceBlocks === null) {

			$oBlocksRepo = \Ext_Thebing_School_Tuition_Block::getRepository();

			$blocksFrom = $dWeekFrom;
			$blocksUntil = $dWeekUntil;

			if (!$this->checkDateInViewPeriod($oSchool, $blocksFrom)) {
				$blocksFrom = Carbon::make($this->getViewPeriod($oSchool)->start());
			}

			if (!$this->checkDateInViewPeriod($oSchool, $blocksUntil)) {
				$blocksUntil = Carbon::make($this->getViewPeriod($oSchool)->end());
			}

			if ($blocksUntil > Carbon::now()) {
				$blocksUntil = Carbon::now()->endOfDay();
			}

			if (!$teacherWasSelected) {
				$teacherToGetBlocksFor = $oTeacher->getTeacherForQuery();
			} else {
				$teacherToGetBlocksFor = $oTeacher;
			}

			$aBlocks = [];
			foreach ($schools as $schoolId) {
				$school = \Ext_Thebing_School::getInstance($schoolId);
				$aBlocks = array_merge(
					$aBlocks,
					$oBlocksRepo->getTuitionBlocks($blocksFrom, $blocksUntil, $school, $teacherToGetBlocksFor)
				);
			}

			$aBlocksAccessibleDays = [];
			$aAttendanceBlocks = [
				'blocks' => [],
				'blocks_days' => []
			];

			foreach ($aBlocks as $aBlockData) {

				$dBlockFromDateTime = new DateTime($aBlockData['date'].' '.$aBlockData['from']);
				// Früher erlauben, wegen Cache.
				$dBlockFromDateTime->modify('-15 minutes');

				if($dBlockFromDateTime < new DateTime()) {

					$dFrom = new DateTime($aBlockData['from']);
					$dUntil = new DateTime($aBlockData['until']);

					$sFrom = $dFrom->format('H:i');
					$sUntil = $dUntil->format('H:i');

					if ($sPeriod === 'daily') {

						$aLocaleDays = \Ext_TC_Util::getLocaleDays($sLanguage, 'wide');

						$aBlocksAccessibleDays[$aBlockData['id']][] = $aBlockData['day'];
						
						$dDate = new DateTime($aBlockData['date']);

						$sDayDate = \Ext_Thebing_Format::LocalDate($dDate, $oSchool->id);

						$iDay = $dDate->format('w');

						$iBlockCompositeKey = $aBlockData['id'] . '_' . $iDay;

						$aAttendanceBlocks['blocks'][$iBlockCompositeKey] = $aBlockData['name'] . ', ';

						if (!empty($aBlockData['room'])) {
							$aAttendanceBlocks['blocks'][$iBlockCompositeKey] .= $aBlockData['room'] . ', ';
						}

						$aAttendanceBlocks['blocks'][$iBlockCompositeKey] .= $aLocaleDays[$iDay] . ' ' . $sDayDate . ' ' . $sFrom . ' – ' . $sUntil;

					} else {

						$aBlocksAccessibleDays[$aBlockData['id']][] = $aBlockData['day'];

						$aAttendanceBlocks['blocks'][$aBlockData['id']] = $aBlockData['name'];

						if (!empty($aBlockData['room'])) {
							$aAttendanceBlocks['blocks'][$aBlockData['id']] .= ', ' . $aBlockData['room'];
						}
						
						$aAttendanceBlocks['blocks'][$aBlockData['id']] .= ', ' . $sFrom . ' – ' . $sUntil;

					}

				}

			}

			$aAttendanceBlocks['blocks_days'] = $aBlocksAccessibleDays;

			\WDCache::set($sCacheKey, 60*5, $aAttendanceBlocks);

		}

		$this->set('aBlocks', $aAttendanceBlocks['blocks']);

		return $aAttendanceBlocks;
	}

	/**
	 * @param array $aBlockAccessibleDays
	 * @param string|null $sBlockDay
	 *
	 * @return mixed $aDays
	 */
	protected function prepareDays(array $aBlockAccessibleDays = [], string $sBlockDay = null) {

		$sLanguage = \TsTeacherlogin\Helper\Data::getSelectedOrDefaultLanguage();

		$aLocaleDays = \Ext_TC_Util::getLocaleDays($sLanguage, 'wide');

		$aDays = [];

		if(empty($sBlockDay)) {

			foreach($aBlockAccessibleDays as $iDay) {
				$aDays[$iDay] = $aLocaleDays[$iDay];
			}

		} else {
			$aDays[$sBlockDay] = $aLocaleDays[$sBlockDay];
		}

		$this->set('aDays', $aDays);

		return $aDays;
	}

	/**
	 * @param \Ext_Thebing_School $oSchool
	 */
	protected function prepareFlexFields(\Ext_Thebing_School $oSchool) {

		$aSectionFlexFields = $oSchool->getAllowedFlexFields();

		$aFlexFields = [];
		$aFlexFieldsSelectOptions = [];

		foreach($aSectionFlexFields as $oFlexField) {

			// Nur aktive Felder holen (input - checkbox - dropdown)
			if(
				(
					$oFlexField->type === '0' ||
					$oFlexField->type === '2' ||
					$oFlexField->type === '5'
				) &&
				$oFlexField->active === '1'
			) {

				$aFlexFields[$oFlexField->aData['id']] = $oFlexField->aData;

				if($oFlexField->type === '5') {
					$aFlexFieldsSelectOptions[$oFlexField->aData['id']] = \Ext_Tc_Flexibility::getOptions($oFlexField->aData['id']);
				}

			}
		}

		$this->set('aFlexFields', $aFlexFields);
		$this->set('aFlexFieldsSelectOptions', $aFlexFieldsSelectOptions);

	}

	/**
	 * @param \Ext_Thebing_School_Tuition_Block $oBlock
	 * @param array $aDays
	 * @param string $sViewType
	 */
	protected function prepareStudents(\Ext_Thebing_School_Tuition_Block $oBlock, array $aDays, string $sViewType) {

		$dDate = new DateTime($oBlock->week);

		$fLessonDuration = $oBlock->getLessonDuration();

		$class = $oBlock->getClass();
		
		$aPhotos = [];
		$aStudentsAttendanceTime = [];
		$aAbsenceReason = $aExcused = $aComments = $aScores = [];
		$aFlexFieldsValues = [];
		$aAttendanceValueExists = [];
		$aPresentStudents = [];
		$aTotalAttendance = [];
		$potentialStudents = [];

		$bAccess = \TcExternalApps\Service\AppService::hasApp(\TsTeacherLogin\Handler\ExternalApp::APP_NAME);

		if(!$bAccess) {
			$class->teacher_can_add_students = false;
		}
		if($class->teacher_can_add_students) {
			
			$potentialStudents = \TsTeacherLogin\Helper\Data::getPotentialStudents($oBlock);

		}
		
		$aStudents = \TsTeacherLogin\Helper\Data::getBlockStudents($oBlock->id);

		foreach($aStudents as $oProxy) {

			$oInquiry = \Ext_TS_Inquiry::getInstance($oProxy->getInquiryId());
			$oTraveller = $oInquiry->getTraveller();
			$oAllocation = $oProxy->getAllocation();

			$sPhoto = $oTraveller->getPhoto();
			
			if(!empty($sPhoto)) {
				$sPhoto = str_replace('/storage', '', $sPhoto);
				$oImageBuilder = new \Ext_TC_Gui2_Format_Image('', '', 'gui2_format_image',140, 180, -1);
				$sPhoto = $oImageBuilder->buildImage($sPhoto);
			}

			$aStorageFiles = $this->oSession->get('ts_teacherlogin_storage_files');
			$aStorageFiles[] = $sPhoto;
			$this->oSession->set('ts_teacherlogin_storage_files', $aStorageFiles);

			$aPhotos[$oInquiry->id] = $sPhoto;

			$oAttendanceRepository = \Ext_Thebing_Tuition_Attendance::getRepository();
			$oAttendance = $oAttendanceRepository->getOrCreateAttendanceObject($oAllocation);

			foreach($aDays as $iDay => $sDay) {

				$sDayAbbreviation = \Ext_Tc_Util::convertWeekdayToString($iDay);
				$aStudentsAttendanceTime[$oInquiry->id.'_'.$iDay] = $fLessonDuration - (float)$oAttendance->$sDayAbbreviation;

				if($oAttendance->$sDayAbbreviation !== null) {
					$aAttendanceValueExists[$oInquiry->id] = true;

					if((float)$oAttendance->$sDayAbbreviation < $fLessonDuration) {
						$aPresentStudents[$oInquiry->id] = true;
					}
				}

				if($oAttendance->excused & pow(2, ($iDay - 1))) {
					$aExcused[$oInquiry->id][$iDay] = true;
				}

				if($oAttendance->online & pow(2, ($iDay - 1))) {
					$aOnline[$oInquiry->id][$iDay] = true;
				}

				if(isset($oAttendance->absence_reasons[$iDay])) {
					$aAbsenceReason[$oInquiry->id][$iDay] = $oAttendance->absence_reasons[$iDay];
				}
				
			}

			$sInquiryTotalAttendanceSql = \Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', array(
				'inquiry_id' => $oInquiry->id
			));
			$aInquiryTotalAttendance = \DB::getQueryCol($sInquiryTotalAttendanceSql);

			$aTotalAttendance[$oInquiry->id] = reset($aInquiryTotalAttendance);
			$aScores[$oInquiry->id] = $oAttendance->score;
			$aComments[$oInquiry->id] = $oAttendance->comment;

			$aSectionFlexFields = \Ext_TC_Flexibility::getSectionFieldData(array('tuition_attendance_register'), true);

			foreach($aSectionFlexFields as $oFlexField) {
				$aFlexFieldsValues[$oFlexField->aData['id']][$oInquiry->id] = $oAllocation->getFlexValue($oFlexField->aData['id']);
			}

			if(isset($potentialStudents[$oProxy->getId()])) {
				unset($potentialStudents[$oProxy->getId()]);
			}

			$showCourseCommentsInAttendance[$oProxy->getId()] = $oProxy->showCourseCommentInAttendance();
			
		}

		$this->set('oBlock', $oBlock);
		$this->set('aScores', $aScores);
		$this->set('aComments', $aComments);
		$this->set('aExcused', $aExcused);
		$this->set('aOnline', $aOnline);
		$this->set('aAbsenceReason', $aAbsenceReason);
		$this->set('aFlexFieldsValues', $aFlexFieldsValues);
		$this->set('aStudentsAttendanceTime', $aStudentsAttendanceTime);
		$this->set('aAttendanceValueExists', $aAttendanceValueExists);
		$this->set('aPresentStudents', $aPresentStudents);
		$this->set('aStudents', $aStudents);
		$this->set('aPhotos', $aPhotos);
		$this->set('aTotalAttendance', $aTotalAttendance);
		$this->set('teacherCanAddStudents', $class->teacher_can_add_students);
		$this->set('potentialStudents', $potentialStudents);
		$this->set('showCourseCommentsInAttendance', $showCourseCommentsInAttendance);

	}

	protected function addStudentsToBlock(\Ext_Thebing_School_Tuition_Block $block, array $journeyCourseIds) {

		$roomIds = $block->getRoomIds();

		foreach($journeyCourseIds as $journeyCourseId=>$programServiceId) {
			$block->addInquiryCourse($journeyCourseId, $programServiceId, reset($roomIds));
		}

	}

	public function addStudents() {

		$week = $this->_oRequest->get('week');
		$sBlockKey = $this->_oRequest->get('block');
		list($iBlockId, $sBlockDay) = explode('_', $sBlockKey, 2);
		
		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		
		$addPotentialStudents = $this->_oRequest->input('potential-student');

		if(!empty($addPotentialStudents)) {
			$this->addStudentsToBlock($oBlock, $addPotentialStudents);
			$this->oSession->getFlashBag()->add('success', \L10N::t('The students have been successfully added!'));
		} else {
			$this->oSession->getFlashBag()->add('error', \L10N::t('No students have been marked!'));
		}		
		
		$this->redirect('TsTeacherLogin.teacher_attendance', ['block'=>$sBlockKey, 'week'=>$week]);
	}
	
	/**
     * @todo Es gibt mehrere Stellen wo die Anwesenheit gespeichert wird, vllt sollte man hier mal eine Klasse für schreiben
     * - \Ext_Thebing_Tuition_AttendanceRepository::saveAttendance()
     * - \TsStudentApp\Pages\Attendance::scanQrCode()
     * - \TsTeacherLogin\Controller::saveAttendance()
     * @throws \Exception
     */
	public function saveAttendance(Request $request) {

		$sBlockKey = $this->_oRequest->get('block');
		$aDailyComment = $this->_oRequest->input('daily_comment');
		$sViewType = $this->_oRequest->get('view_type');

		list($iBlockId, $sBlockDay) = explode('_', $sBlockKey, 2);
		$aDays = explode(',', $this->_oRequest->input('days', ''));

		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		$fLessonDuration = $oBlock->getLessonDuration();
				
		$persister = \WDBasic_Persister::getInstance();

		if(!empty($aDailyComment)) {
			foreach($aDailyComment as $iDay=>$sComment) {
				$unit = $oBlock->getUnit($iDay);
				$unit->comment = $sComment;
				$persister->attach($unit);
			}		
		}
		
		if(!DateTime::isDate($oBlock->week, 'Y-m-d')) {
			throw new \RuntimeException('Invalid block week '.$oBlock->week);
		}

		// Es dürfen nur die Tage abgespeichert werden, die auch übergeben bzw. angezeigt werden
		if (!empty(array_diff($aDays, $oBlock->days))) {
			throw new \RuntimeException('Invalid days given for block '.$oBlock->id);
		}

//		$aDays = [];
//
//		if(empty($sBlockDay)) {
//
//			foreach($oBlock->days as $iDay) {
//				$aDays[] = $iDay;
//			}
//
//		} else {
//			$aDays[] = $sBlockDay;
//		}

		$aPresentStudents = $this->_oRequest->input('attendant');
		$aAttendanceTime = $this->_oRequest->input('attendance');

		$aExcused = $this->_oRequest->input('excused');
		$aOnline = $this->_oRequest->input('online');
		$aAbsenceReason = $this->_oRequest->input('absence_reason');
		$aScores = $this->_oRequest->input('score');
		$aComments = $this->_oRequest->input('comment');

		$aAbsenceReasonOptions = \TsTuition\Entity\AbsenceReason::getOptions(true);
		
		$aStudents = \TsTeacherLogin\Helper\Data::getBlockStudents($iBlockId);

		foreach($aStudents as $oProxy) {

			$oAllocation = $oProxy->getAllocation();
			$oInquiry = \Ext_TS_Inquiry::getInstance($oProxy->getInquiryId());

			$oAttendanceRepository = \Ext_Thebing_Tuition_Attendance::getRepository();
			$oAttendance = $oAttendanceRepository->getOrCreateAttendanceObject($oAllocation);

			if($sViewType === 'extended') {
				$oAttendance->score = $aScores[$oInquiry->id];
				$oAttendance->comment = $aComments[$oInquiry->id];
			}

			foreach($aDays as $iDay) {

				$sDay = \Ext_TC_Util::convertWeekdayToString($iDay);

				if ($oBlock->getUnit($iDay)->isCancelled()) {
					continue;
				}

				if($sViewType === 'simple') {

					/* Wenn Schüler schon teilweise anwesend sind, werden die in der einfachen Ansicht beim Speichern nicht berücksichtigt
					denn die werden dann halt als komplett anwesend eingetragen */
					if(
						$oAttendance->$sDay > 0.0 &&
						$oAttendance->$sDay < $fLessonDuration
					) {
						continue;
					}

					if($aPresentStudents[$oInquiry->id] == 1) {
						$oAttendance->$sDay = 0.0;
					} else {
						$oAttendance->$sDay = $fLessonDuration;
					}

				} else {

					// Nur wenn die Anwesenheit für den Tag auch übergeben wurde speichern
					if(isset($aAttendanceTime[$oInquiry->id][$iDay])) {
						
						$oAttendance->$sDay = $fLessonDuration - $aAttendanceTime[$oInquiry->id][$iDay];

						if(isset($aExcused[$oInquiry->id][$iDay])) {
							if($aExcused[$oInquiry->id][$iDay] == 1) {
								$oAttendance->excused |= pow(2, ($iDay - 1));
							} else {
								$oAttendance->excused &= ~pow(2, ($iDay - 1));
							}
						}
						
						if(isset($aOnline[$oInquiry->id][$iDay])) {
							if($aOnline[$oInquiry->id][$iDay] == 1) {
								$oAttendance->online |= pow(2, ($iDay - 1));
							} else {
								$oAttendance->online &= ~pow(2, ($iDay - 1));
							}
						}
						
						// Nur Gründe speichern, die auch im Portal zur Verfügung stehen
						if(
							isset($aAbsenceReason[$oInquiry->id][$iDay]) &&
							array_key_exists($aAbsenceReason[$oInquiry->id][$iDay], $aAbsenceReasonOptions)
						) {
							$absenceReason = $oAttendance->absence_reasons??[];
							$absenceReason[$iDay] = (int)$aAbsenceReason[$oInquiry->id][$iDay];
							$oAttendance->absence_reasons = $absenceReason;
						}

					} else {
						$oAttendance->$sDay = $fLessonDuration;
					}

				}

			}

			// TODO Das macht doch so keinen Sinn, da in save() dann Exceptions geschmissen werden
			$bSaved = $oAttendance->validate();

			$oAttendance->save();

			if($sViewType === 'extended') {

				$aFlexFields = $this->_oRequest->input('flex', []);

				foreach($aFlexFields as $sFlexFieldId => $aValueForStudent) {
					\Ext_Thebing_Flexibility::saveData([(int)$sFlexFieldId => $aValueForStudent[$oInquiry->id]], $oAllocation->id);
				}

			}

			if($bSaved !== true) {
				$this->oSession->getFlashBag()->add('error', \L10N::t('Your changes could not be saved!'));
			}

		}

		$this->oSession->getFlashBag()->add('success', \L10N::t('Your changes have been saved successfully!'));

		$persister->save();
		
		// TODO Hier muss umgeleitet werden, da ansonsten /save in der Route verbleibt und ohne POST die Seite tot ist, mit POST wieder gespeichert wird
		$this->getAttendanceView($request);
		
	}

	/**
	 * Funktionalität wird noch nicht benutzt, wird erst später gemacht.
	 */
	public function getAttendanceCodeView() {

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);

		$oSchool = $oTeacher->getSchool();
		
		$oBlocksRepo = \Ext_Thebing_School_Tuition_Block::getRepository();

		$dFrom = new DateTime();
		$dTo = new DateTime;
		$dTo->setTime(23, 59, 59);

		$aBlocks = [];
		foreach ($oTeacher->schools as $schoolId) {
			$school = \Ext_Thebing_School::getInstance($schoolId);
			$aBlocks = array_merge(
				$aBlocks,
				$oBlocksRepo->getTuitionBlocks($dFrom, $dTo, $school, $oTeacher->getTeacherForQuery())
			);
		}

		$aCurrentBlock = reset($aBlocks);

        $sCode = "";
		if(!empty($aCurrentBlock)) {

            $oBlock = \Ext_Thebing_School_Tuition_Block::getInstance((int)$aCurrentBlock['id']);

            $oRenderer = new ImageRenderer(
                new RendererStyle(270),
                new SvgImageBackEnd()
            );

            $oWriter = new Writer($oRenderer);

            $sKey = TrackingSession::generate($oTeacher, $oBlock, (int)$aCurrentBlock['day']);

            $aEncode = ['code' => $sKey];
            $sEncoded = json_encode($aEncode);

            $sCode = $oWriter->writeString($sEncoded);
        }

		$this->set('sCode', $sCode);

		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/attendance_code.tpl';
		$this->_oView->setTemplate($sTemplate);

	}

}
