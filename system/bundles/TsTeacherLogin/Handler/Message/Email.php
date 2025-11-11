<?php

namespace TsTeacherLogin\Handler\Message;

use Symfony\Component\Mime\Address;

class Email extends AbstractMessage {

	public function send() {

		$contactInformations = $this->oRequest->input('students');
		$blockId = (int)$this->oRequest->input('block_id');

		$block = \Ext_Thebing_School_Tuition_Block::getInstance($blockId);
		$class = $block->getClass();

		$bSuccess = true;

		if(empty($contactInformations)) {
			$this->aErrors[] = \L10N::t('Please select recipients!');
			return false;
		}

		foreach($contactInformations as $contactInformation) {

			[$contactType, $inquiryId, $contactId] = explode('_', $contactInformation);

			$inquiry = \Ext_TS_Inquiry::getInstance($inquiryId);

			if ($contactType === 'booker') {
				$contact = \Ext_TS_Inquiry_Contact_Booker::getInstance($contactId);
			} else {
				$contact = \Ext_TS_Inquiry_Contact_Traveller::getInstance($contactId);
			}

			$aTo = [$contact->email];

			$sTeacherEmailAddress = $this->oTeacher->email;
			$sTeacherName = $this->oTeacher->firstname.' '.$this->oTeacher->lastname;

			$sMessage = $this->oRequest->get('message');
			
			$aPlaceholders = [
				'{studentName}' => $contact->getName(),
				'{studentFirstname}' => $contact->firstname,
				'{teacherName}' => $sTeacherName,
				'{teacherEmailAddress}' => $sTeacherEmailAddress,
				'{className}' => $class->name,
				'{classContent}' => $block->description,
			];

			$sMessage = strtr($sMessage, $aPlaceholders);
			
			$aVariables = [];
			$aVariables['sMessage'] = $sMessage;
			$aVariables['sSubject'] = $this->oRequest->get('subject');
			
			$oEmail = new \Admin\Helper\Email('TsTeacherLogin');

			// Reply-to nur ergänzen, wenn Einstellung gesetzt bei Schule
			if($this->oTeacher->getSchool()->teacherlogin_teacher_email_replyto) {
				$oEmail->setReplyTo(new Address($sTeacherEmailAddress, $sTeacherName));
			}

			$aAttachments = $this->oRequest->getFilesData();

			if(isset($aAttachments['file']['name'])) {
				for($i = 0; $i < count($aAttachments['file']['name']); $i++) {
					$oEmail->addAttachement($aAttachments['file']['tmp_name'][$i], $aAttachments['file']['name'][$i]);
				}
			}

			$bSent = $oEmail->send('communication', $aTo, $aVariables);

			if($bSent !== true) {
				// @todo Fehler genauer spezifizieren
				$this->aErrors[] = $contact->getName().': '.\L10N::t('Your e-mail could not be sent!');
				$bSuccess = false;
			} else {
				$this->log($contact, $inquiry, $oEmail);
			}

		}

		return $bSuccess;
	}

	public function log(\Ext_TS_Inquiry_Contact_Abstract $contact, \Ext_TS_Inquiry $inquiry, \Admin\Helper\Email $email) {
		
		$oLog = new \Ext_TC_Communication_Message();
		$oLog->date = date('Y-m-d H:i:s');
		$oLog->direction = 'out';
		$oLog->content_type = 'html';
		$oLog->type = 'email';

		$oMail = $email->getMail();
		
		$oSender = $oMail->sender_object;

		if(!empty($oSender)) {

			$aRelations[] = ['relation' => get_class($oSender), 'relation_id' => $oSender->id];

			$oSenderLog = $oLog->getJoinedObjectChild('addresses');
			$oSenderLog->type = 'from';
			$oSenderLog->address = $oSender->email;
			$oSenderLog->name = $oSender->sFromName;
			if($oSender->id > 0) {
				$oSenderLog->relations = 
				[
					[
						'relation' => get_class($oSender),
						'relation_id' => $oSender->id
					]
				];
			}

		}

		$aRelations[] = ['relation' => get_class($this->oTeacher), 'relation_id' => $this->oTeacher->id];

		$aRelations[] = ['relation' => get_class($inquiry), 'relation_id' => $inquiry->id];

		$aRelations[] = ['relation' => get_class($contact), 'relation_id' => $contact->id];
		
		$oRecipient = $oLog->getJoinedObjectChild('addresses');
		$oRecipient->type = 'to';
		$oRecipient->address = $contact->email;
		$oRecipient->name = $contact->getName();
		$oRecipient->relations = [[
			'relation' => get_class($contact),
			'relation_id' => $contact->id
		]];
		
		$oLog->relations = $aRelations;
		$oLog->subject = (string)$oMail->subject;
		$oLog->content = (string)$oMail->html;

		$aAttachments = $oMail->attachments;

		if (!empty($aAttachments)) {
			$sUploadsDir = \Util::getDocumentRoot().'storage/ts/teachers/uploads/mails/'.(int)$this->oTeacher->id;
			\Util::checkDir($sUploadsDir);

			foreach ($aAttachments as $sFilePath => $sFileName) {

				$sNewFilePath = $sUploadsDir.'/'.\Ext_TC_Util::getCleanFilename($sFileName);

				copy($sFilePath, $sNewFilePath);

				$oAttachment = $oLog->getJoinedObjectChild('files');
				$oAttachment->file = str_replace(\Ext_TC_Util::getDocumentRoot(), '/', $sNewFilePath);
				$oAttachment->name = $sFileName;
			}
		}

		if($oMail->message instanceof \Swift_Message) {
			$oLog->imap_message_id = '<'.$oMail->message->getId().'>';
			$oLog->unseen = 0;
			if(!empty($oSender)) {
				$oLog->account_id = $oSender->id;
			}
		}

		$oLog->save();
	}

}