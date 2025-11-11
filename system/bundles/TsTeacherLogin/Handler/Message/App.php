<?php

namespace TsTeacherLogin\Handler\Message;

use Communication\Enums\MessageStatus;
use Core\Service\NotificationService;
use TsStudentApp\Service\MessengerService;

class App extends AbstractMessage {

	public function send() {

		$aStudentsIds = $this->oRequest->input('students');
		$blockId = (int)$this->oRequest->input('block_id');

		$block = \Ext_Thebing_School_Tuition_Block::getInstance($blockId);
		$class = $block->getClass();

		$bSuccess = true;

		if(empty($aStudentsIds)) {
			$this->aErrors[] = \L10N::t('Please select recipients!');
			return false;
		}

		$sTeacherEmailAddress = $this->oTeacher->email;
		$sTeacherName = $this->oTeacher->firstname.' '.$this->oTeacher->lastname;

		foreach($aStudentsIds as $iInquiryId) {

			$oInquiry = \Ext_TS_Inquiry::getInstance($iInquiryId);

			$oTraveller = $oInquiry->getTraveller();

			$oLogin = $oTraveller->getLoginData();

			if (!$oLogin) {
				$this->aErrors[] = $oTraveller->getName().': '.\L10N::t('No Student login available');
				$bSuccess = false;
				continue;
			}

			$sSubject = $this->oRequest->get('subject');
			$sMessage = $this->oRequest->get('message');

			$aPlaceholders = [
				'{studentName}' => $oTraveller->getName(),
				'{studentFirstname}' => $oTraveller->firstname,
				'{teacherName}' => $sTeacherName,
				'{teacherEmailAddress}' => $sTeacherEmailAddress,
				'{className}' => $class->name,
				'{classContent}' => $block->description,
			];

			$sMessage = strtr($sMessage, $aPlaceholders);

			$oMessengerService = MessengerService::getInstance($oTraveller, $oInquiry);

			try {
				$bStudentSuccess = $oMessengerService->sendMessageToDevices($this->oTeacher, $sSubject, $sMessage);

				if ($bStudentSuccess) {
					$this->log($oMessengerService, $sSubject, $sMessage);
				} else {
					$aErrors = $oMessengerService->getErrors();

					foreach($aErrors as &$aError) {
						$this->aErrors[] = sprintf(\L10N::t('Die Nachricht konnte nicht an "%s" versendet werden. Die Verbindung zur App konnte nicht hergestellt werden.'), $oTraveller->getName()).' ('.$aError.')';
					}

					$bSuccess = false;
				}

			} catch(\RuntimeException $e) {

				NotificationService::getLogger('TsTeacherLogin')->error('Message could not be sent', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

				$this->aErrors[] = \L10N::t('Die Nachricht konnte nicht versendet werden. Bitte versuchen Sie es später erneut.');
				$bSuccess = false;

			}

		}

		return $bSuccess;
	}
	
	public function log(MessengerService $oMessengerService, string $sSubject, string $sMessage) {

		$oThread = $oMessengerService->getThreadForEntity($this->oTeacher);

		$oThread->storeMessage($sMessage, time(), 'out', MessageStatus::SENT, $sSubject);

	}

}