<?php

namespace TsTeacherLogin\Handler\Message;

class Sms extends AbstractMessage {

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

			$oSMS = new \Ext_TC_Communication_SMS_Gateway();
			$sNumber = $oTraveller->detail_phone_mobile;
			$oSMS->setRecipient($sNumber);
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
			
			$oSMS->setMessage($sMessage);
			$oSMS->setSender('Test');
			$sReturn = $oSMS->send();

			if($sReturn !== 'SENT') {

				$this->aErrors[] = $oTraveller->getName().': '.\L10N::t(\Ext_TC_Communication_SMS_Gateway::convertErrorKeyToMessage($sReturn));

				$bSuccess = false;
			} else {
				$this->log($oInquiry, $oSMS);
			}

		}

		return $bSuccess;
	}
	
	public function log(\Ext_TS_Inquiry $inquiry, \Ext_TC_Communication_SMS_Gateway $sms) {
		
		$oLog = new \Ext_TC_Communication_Message();
		$oLog->date = date('Y-m-d H:i:s');
		$oLog->direction = 'out';
		$oLog->content_type = 'html';
		$oLog->type = 'sms';

		$sSender = $sms->getSender();
		
		if(!empty($sSender)) {

			$oSenderLog = $oLog->getJoinedObjectChild('addresses');
			$oSenderLog->type = 'from';
			$oSenderLog->name = $sSender;

		}

		$aRelations[] = ['relation' => get_class($this->oTeacher), 'relation_id' => $this->oTeacher->id];
		
		$traveller = $inquiry->getTraveller();
		
		$aRelations[] = ['relation' => get_class($traveller), 'relation_id' => $traveller->id];
		
		$aRelations[] = ['relation' => get_class($inquiry), 'relation_id' => $inquiry->id];
		
		$oRecipient = $oLog->getJoinedObjectChild('addresses');
		$oRecipient->type = 'to';
		$oRecipient->address = $traveller->email;
		$oRecipient->name = $traveller->getName();
		$oRecipient->relations = [[
			'relation' => get_class($traveller),
			'relation_id' => $traveller->id
		]];
		
		$oLog->relations = $aRelations;
		$oLog->content = (string)$sms->getMessage();

		$oLog->save();
	}

}