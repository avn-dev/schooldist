<?php

namespace TsTeacherLogin\Proxy;

use Core\Helper\DateTime;
use TsTeacherLogin\TeacherPortal;

/**
 * @property \Ext_TS_Inquiry_Journey_Course $oEntity
 */
class JourneyCourseProxy extends \Ts\Proxy\AbstractProxy {

	protected $sEntityClass = 'Ext_TS_Inquiry_Journey_Course';

	/**
	 * @var \Ext_Thebing_School_Tuition_Block
	 */
	protected $oBlock;
	public $oSchool;
	protected ?\Ext_TS_Inquiry_Contact_Traveller $oContact;
	protected $iAllocationId;
	protected $aStudentData;

	public function setBlock(\Ext_Thebing_School_Tuition_Block $oBlock) {
		$this->oBlock = $oBlock;
		$this->oSchool = $oBlock->getSchool();
	}

	public function getInquiryId() {
		return $this->oEntity->getInquiry()->id;
	}

	public function setData($aStudentData) {
		$this->aStudentData = $aStudentData;
		$this->oContact = $this->oEntity->getJourney()?->getInquiry()?->getCustomer();
	}

	public function setAllocationId(int $iAllocationid) {
		$this->iAllocationId = $iAllocationid;
	}

	public function getCustomerNumber() {
		return $this->aStudentData['customerNumber']['text'];
	}

	public function getProgramServiceId() {
		return $this->aStudentData['program_service_id']['text'];
	}

	public function getName($sLanguageIso = null) {
		$sName = strip_tags($this->aStudentData['cdb1.lastname']['text']);
		return $sName;
	}

	public function getFirstName() {
		$sName = strip_tags($this->aStudentData['cdb1.firstname']['text']);
		return $sName;
	}

	public function getLastName() {
		$sName = strip_tags($this->aStudentData['cdb1.lastname_2']['text']);
		return $sName;
	}

	public function getNationalityIso() {
		return $this->aStudentData['nationality_iso']['original'];
	}

	public function getAge() {
		return $this->aStudentData['age']['text'];
	}

	public function getGender() {
		return $this->aStudentData['gender']['text'];
	}

	public function getEmail() {
		return $this->oContact->getFirstEmailAddress()->email;
	}

	public function getPhone() {
		return $this->oContact->getFirstPhoneNumber();
	}

	public function getCourseState() {
		return $this->aStudentData['state_course']['text'];
	}

	public function getInquiryState() {
		return $this->aStudentData['state']['text'];
	}

	public function getGroup() {
		return $this->aStudentData['kg.short']['text'];
	}

	public function getLevel() {

		$oSchool = \Ext_Thebing_School::getInstance($this->oBlock->getSchoolId());

		$aLevels = $oSchool->getLevelList(true, '', 1, false, true);
		$sKey = $this->aStudentData['level']['original'];

		return $aLevels[$sKey];
	}

	public function getCourse() {
		return $this->oEntity->getCourseName(true);
	}

	public function getStartDate() {

		$oSchool = \Ext_Thebing_School::getInstance($this->oBlock->getSchoolId());

		$dFrom = new DateTime($this->aStudentData['tijcti.from']['original']);
		$sFrom = \Ext_Thebing_Format::LocalDate($dFrom, $oSchool->id);

		return $sFrom;
	}

	public function getEndDate() {

		$oSchool = \Ext_Thebing_School::getInstance($this->oBlock->getSchoolId());

		$dFrom = new DateTime($this->aStudentData['tijcti.until']['original']);
		$sFrom = \Ext_Thebing_Format::LocalDate($dFrom, $oSchool->id);

		return $sFrom;
	}

	public function getCourseComment() {
		return $this->aStudentData['kic.comment']['original'];
	}

	/**
	 * @return \Ext_Thebing_School_Tuition_Allocation
	 */
	public function getAllocation() {
		$oAllocation = \Ext_Thebing_School_Tuition_Allocation::getInstance($this->iAllocationId);
		return $oAllocation;
	}

	public function getPreviousAllocations() {

		$aAllocations = \Ext_Thebing_School_Tuition_Allocation::getAllocationByInquiry($this->oBlock->week, $this->oEntity->getInquiry());

		if(empty($aAllocations)) {
			return [];
		}

		$aWeeksAllocations = [];

		foreach($aAllocations as $aAllocation) {

			$sLanguage = \TsTeacherLogin\Helper\Data::getSelectedOrDefaultLanguage();
			$aDays = explode(',', $aAllocation['days']);

			$aAllocation['days'] = \Ext_Thebing_Util::buildJoinedWeekdaysString($aDays, $sLanguage);

			$dWeekFrom = new DateTime($aAllocation['block_week']);
			$dWeekUntil = clone $dWeekFrom;
			$dWeekUntil->modify('+6 days');
			$dWeekUntil->setTime(23, 59, 59);

			$sWeekFrom = \Ext_Thebing_Format::LocalDate($dWeekFrom, $this->oBlock->getSchool()->id);
			$sWeekUntil = \Ext_Thebing_Format::LocalDate($dWeekUntil, $this->oBlock->getSchoolId());
			$sCalendarWeek = $dWeekFrom->format('W');

			$aAllocation['block_from'] = substr($aAllocation['block_from'], 0, -3);
			$aAllocation['block_until'] = substr($aAllocation['block_until'], 0, -3);

			$aWeeksAllocations[$aAllocation['block_week']]['week_from'] = $sWeekFrom;
			$aWeeksAllocations[$aAllocation['block_week']]['week_until'] = $sWeekUntil;
			$aWeeksAllocations[$aAllocation['block_week']]['week_num'] = $sCalendarWeek;
			$aWeeksAllocations[$aAllocation['block_week']]['allocations'][] = $aAllocation;

		}

		// Wochen auf 4 limitieren
		$aWeeksAllocations = array_slice($aWeeksAllocations, 0, 10);

		return $aWeeksAllocations;
	}

	public function getPlacementTestResult() {
		return \Ext_Thebing_Placementtests_Results::getResultByInquiryAndCourseLanguage($this->getInquiryId(), $this->oEntity->getCourseLanguage()->id);
	}

	public function getMainColumnPayload($withName = true, $withCourseComment = false): string
	{
		$config = (array)$this->oSchool->teacherlogin_student_informations;

		$rows = [];
		if ($withName) {
			$rows[] = $this->getName();
		}
		$row = [$this->getCustomerNumber()];
		if (!$this->oSchool || !empty(array_intersect(['age', 'gender'], $config))) {
			$data = [];
			if (in_array('age', $config)) {
				$age = $this->getAge();

				if ((int)$age < 18) {
					$age = '<span style="color:red">'.$age.'</span>';
				}

				$data[] = $age;
			}
			if (in_array('gender', $config)) {
				if ($this->getGender() == 'm') {
					$data[] = '<i class="fa fa-mars"></i>';
				} else if ($this->getGender() == 'f') {
					$data[]= '<i class="fa fa-venus"></i>';
				}
			}
			$row[] = implode(' ', $data);
		}

		if (in_array('nationality', $config)) {
			$row[] = $this->getNationalityIso();
		}

		$courseComment = $this->showCourseCommentInAttendance();
		if (
			$withCourseComment &&
			$courseComment
		) {
			$row[] = $courseComment;
		}

		$rows[] = implode(', ', $row);

		return implode('<br/>', $rows);
	}

	public function getTooltipValue($withCourseComment = false): string
	{
		$config = (array)$this->oSchool->teacherlogin_student_informations;

		$nationalities = \Ext_Thebing_Nationality::getNationalities(true, null);

		$tooltip = [];
		$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Lastname'), $this->getLastName());
		$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Firstname'), $this->getFirstName());
		$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Customer number'), $this->getCustomerNumber());
		if (in_array('age', $config)) {
			$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Age'), $this->getAge());
		}
		if (in_array('gender', $config)) {
			if ($this->getGender() == 'm') {
				$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Gender'), TeacherPortal::l10n()->translate('male'));
			} else if ($this->getGender() == 'f') {
				$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Gender'), TeacherPortal::l10n()->translate('female'));
			}
		}
		if (in_array('nationality', $config)) {
			$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Nationality'), $nationalities[$this->getNationalityIso()] ?? '');
		}

		if (in_array('email', $config) && !empty($email = $this->getEmail())) {
			$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('E-Mail'), $email);
		}

		if (in_array('phone', $config) && !empty($phone = $this->getPhone())) {
			$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Phone'), $phone);
		}

		$emergencyContact = $this->oEntity->getJourney()?->getInquiry()?->getEmergencyContact();
		if ($emergencyContact && $emergencyContact->exist()) {
			if (
				in_array('emergency_email', $config) &&
				!empty($emergencyEmail = $emergencyContact->getFirstEmailAddress()->email)
			) {
				$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('E-Mail (Emergency)'), $emergencyEmail);
			}
			if (
				in_array('emergency_phone', $config) &&
				!empty($emergencyPhone = $emergencyContact->getFirstPhoneNumber())
			) {
				$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Phone (Emergency)'), $emergencyPhone);
			}
		}

		$booker = $this->oEntity->getJourney()?->getInquiry()?->getBooker();
		if ($booker && $booker->exist()) {
			if (
				in_array('booker_email', $config) &&
				!empty($bookerEmail = $booker->getFirstEmailAddress()->email)
			) {
				$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('E-Mail (Billing)'), $bookerEmail);
			}
			if (
				in_array('booker_phone', $config) &&
				!empty($bookerPhone = $booker->getFirstPhoneNumber())
			) {
				$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Phone (Billing)'), $bookerPhone);
			}
		}

		$courseComment = $this->showCourseCommentInAttendance();
		if (
			$withCourseComment &&
			$courseComment
		) {
			$tooltip[] = sprintf('%s: %s', TeacherPortal::l10n()->translate('Kurskommentar'), $courseComment);
		}

		return implode('<br/>', $tooltip);
	}

	public function showCourseCommentInAttendance() {
		$courseComment = $this->getCourseComment();
		if (
			$this->oSchool->teacherlogin_show_course_comment_in_attendance &&
			!empty($courseComment)
		) {
			return $courseComment;
		}

		return false;
	}

}
