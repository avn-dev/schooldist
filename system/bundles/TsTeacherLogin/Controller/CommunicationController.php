<?php

namespace TsTeacherLogin\Controller;

use Core\Handler\SessionHandler as Session;
use Core\Helper\DateTime;
use TsTeacherLogin\Handler\Message;

class CommunicationController extends InterfaceController {

	/**
	 * @var Session
	 */
	protected $oSession;

	public function getCommunicationView() {

		$this->prepareStudents();

		$oBlocksRepo = \Ext_Thebing_School_Tuition_Block::getRepository();

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$this->set('oTeacher', $oTeacher);

		$oSchool = $oTeacher->getSchool();

		$dWeekFrom = new DateTime();
		$dWeekUntil = new DateTime();

		$this->prepareDates($dWeekFrom, $dWeekUntil);

		$sBackendWeekFrom = $dWeekFrom->format('Y-m-d');
		$this->set('sBackendWeekFrom', $sBackendWeekFrom);

		$sWeekFrom = \Ext_Thebing_Format::LocalDate($dWeekFrom, $oSchool->id);
		$this->set('sWeekFrom', $sWeekFrom);

		$sWeekUntil = \Ext_Thebing_Format::LocalDate($dWeekUntil, $oSchool->id);
		$this->set('sWeekUntil', $sWeekUntil);

		$this->set('sSchoolDateFormat', $oSchool->date_format_long);

		$blocks = [];
		foreach ($oTeacher->schools as $schoolId) {
			$school = \Ext_Thebing_School::getInstance($schoolId);
			$blocks = array_merge(
				$blocks,
				$oBlocksRepo->getTuitionBlocks($dWeekFrom, $dWeekUntil, $school, $oTeacher->getTeacherForQuery())
			);
		}

		$aBlocksNames = [];
		$aLocaleDays = \Ext_TC_Util::getLocaleDays($oSchool->getInterfaceLanguage());

		foreach($blocks as $aBlock) {

			$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($aBlock['id']);

			$aBlockDays = $oBlock->days;

			$aBlockDaysStrings = [];

			foreach($aBlockDays as $iDay) {
				$aBlockDaysStrings[$iDay] = $aLocaleDays[$iDay];
			}

			$sDays = implode(', ', $aBlockDaysStrings);

			$aBlocksNames[$aBlock['id']] = $aBlock['name'].', ';

			if(!empty($aBlock['room'])) {
				$aBlocksNames[$aBlock['id']] .= $aBlock['room'].', ';
			}

			$aBlocksNames[$aBlock['id']] .= $sDays;

		}

		$sLabel = '-- '.\L10N::t('Select course').' --';
		$aBlocksForSelect = \Util::addEmptyItem($aBlocksNames, $sLabel);

		$this->set('aBlocksForSelect', $aBlocksForSelect);

		$sMessage = $this->_oRequest->get('message');
		
		if(empty($sMessage)) {
			$sMessage = \L10N::t('
Hello {studentFirstname},



Best regards
{teacherName}
');
			$sMessage = str_replace('{teacherName}', $oTeacher->firstname.' '.$oTeacher->lastname, $sMessage);

		}
		
		$this->set('sMessage', $sMessage);
		
		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/communication.tpl';
		$this->_oView->setTemplate($sTemplate);

	}

	/**
	 * @param \DateTime $dWeekFrom
	 * @param \DateTime $dWeekUntil
	 */
	protected function prepareDates(\DateTime &$dWeekFrom, \DateTime $dWeekUntil) {

		$iWeekday = $dWeekFrom->format('N');

		if($iWeekday != 1) {
			$dWeekFrom->modify('last monday');
		}

		if($this->_oRequest->exists('week')) {
			$dWeekFrom = new DateTime($this->_oRequest->get('week'));
		}

		$this->set('dWeekFrom', $dWeekFrom);

		$dWeekUntil->setTimestamp($dWeekFrom->getTimestamp());

		$dWeekUntil->modify('+6 days');
		$dWeekUntil->setTime(23, 59, 59);

		$this->set('dWeekUntil', $dWeekUntil);

		$bIsCurrentWeek = false;
		if($dWeekUntil > new DateTime()) {
			$bIsCurrentWeek = true;
		}

		$this->set('bIsCurrentWeek', $bIsCurrentWeek);

	}

	protected function prepareStudents() {

		$this->set('oRequest', $this->_oRequest);

		if ($this->_oRequest->exists('block_id')  === true) {

			$iBlockId = $this->_oRequest->input('block_id');

			$sMessageType = $this->_oRequest->input('message_type', 'email');

			$this->set('sMessageType', $sMessageType);

			$aStudents = \TsTeacherLogin\Helper\Data::getBlockStudents($iBlockId);

			$aStudentsForSelect = [];

			$bAddStudent = true;

			foreach($aStudents as $oProxy) {

				$oInquiry = \Ext_TS_Inquiry::getInstance($oProxy->getInquiryId());

				$oTraveller = $oInquiry->getTraveller();

				if($sMessageType === 'email') {

					if(!empty($oTraveller->email)) {
						$aStudentsForSelect['traveller_'.$oInquiry->id.'_'.$oTraveller->id] = $oProxy->getName();
					}
					if ($oInquiry->getSchool()->teacherlogin_communication_enable_booking_contact_email) {
						$booker = $oInquiry->getBooker();

						if(!empty($booker->email)) {
							$label = \L10N::t('Rechnungskontakt von ').$oProxy->getName();
							if (!empty($booker->getName())) {
								$label .= ' ('.$booker->getName().')';
							}
							$aStudentsForSelect['booker_'.$oInquiry->id.'_'.$booker->id] = $label;
						}
					}

					continue;
				} elseif($sMessageType === 'sms') {

					if($oTraveller->detail_phone_mobile === '') {
						$bAddStudent = false;
					} else {
						$bAddStudent = true;
					}
				} elseif ($sMessageType === 'app') {

					$oLogin = $oTraveller->getLoginData();

					$bAddStudent = false;
					if ($oLogin) {
						// Nur Schüler welche die App auch benutzen
						$bAddStudent = !empty($oLogin->getDevices());
					}
				}

				if($bAddStudent) {
					$aStudentsForSelect[$oInquiry->id] = $oProxy->getName();
				}

			}

			$this->set('aStudents', $aStudentsForSelect);

		} else {
			$iBlockId = 0;
		}

		$this->set('iBlockId', $iBlockId);

	}

	public function submitCommunicationForm() {

		$aReturnJsonArray = [];

		$sMessageType = $this->_oRequest->get('message_type');
		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);

		$oMessage = new Message($sMessageType);
		$oMessage->setRequest($this->_oRequest);
		$oMessage->setTeacher($oTeacher);

		$bReturn = $oMessage->send();

		if($bReturn !== true) {

			$aReturnJsonArray['error'] = true;
			$aReturnJsonArray['messages'] = $oMessage->getErrorMessages();
			$aReturnJsonArray['message'] = $this->_oRequest->get('message');
			$aReturnJsonArray['subject'] = $this->_oRequest->get('subject');

		} else {

			$aReturnJsonArray['error'] = false;
			$aReturnJsonArray['messages'] = [\L10N::t('Your message has been sent successfully!')];

		}

		return response()->json($aReturnJsonArray);
	}
}
