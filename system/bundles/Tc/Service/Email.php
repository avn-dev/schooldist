<?php

namespace Tc\Service;

class Email {

	/**
	 * @var \Ext_Thebing_Email_Template|\Ext_TC_Communication_Template
	 */
	protected $oTemplate;

	/**
	 * @var string
	 */
	protected $sLanguage;

	/**
	 * @var \Ext_Thebing_Placeholder|\Ext_TC_Placeholder_Abstract
	 */
	protected $oPlaceholder;

	/**
	 * @var \WDBasic
	 */
	protected $oEntity;

	/**
	 * @var array
	 */
	protected $aAttachments;

	protected $communicationCode;

	function __construct(\Ext_Thebing_Email_Template|\Ext_TC_Communication_Template $oTemplate, string $sLanguage) {

		$this->oTemplate = $oTemplate;
		$this->sLanguage = $sLanguage;

	}

	public function setEntity(\WDBasic $oEntity) {

		$this->oEntity = $oEntity;

	}

	public function setPlaceholder(\Ext_Thebing_Placeholder|\Ext_TC_Placeholder_Abstract $oPlaceholder) {

		$this->oPlaceholder = $oPlaceholder;

	}

	public function setAttachments(array $aAttachments) {

		$this->aAttachments = $aAttachments;

	}

	public function send(array $aRecipients) {

		// Für jede E-Mail muss ein eigener Code erzeugt werden, daher zurücksetzen
		$this->communicationCode = null;

		$sSubject = $this->oTemplate->{'subject_'.$this->sLanguage};
		$sContent = $this->oTemplate->{'content_'.$this->sLanguage};

		// Gibts nur bei School
		$oUser = $this->getDefaultIdentityUser();

		$this->setLayoutAndSignature($oUser, $sContent);

		$this->replaceMailCodePlaceholder($sSubject);
		$this->replaceMailCodePlaceholder($sContent);

		if($this->oPlaceholder !== null) {

			$this->oPlaceholder->setType('communication');
			if($oUser) {
				$this->oPlaceholder->setCommunicationSender($oUser);
			}

			$this->oPlaceholder->setDisplayLanguage($this->sLanguage);

			$sSubject = $this->oPlaceholder->replace($sSubject);
			$sContent = $this->oPlaceholder->replace($sContent);

		}

		if(empty($sContent)) {
			throw new \RuntimeException('No e-mail content available!');
		}

		$oMail = new \WDMail();
		$oMail->subject = $sSubject;

		if($oUser !== null) {

			// Absender für self::g()
			$oMail->from_user = $oUser;
		}

		$oMail->html = $sContent;

		if(!empty($this->oTemplate->cc)) {
			$oMail->cc = \Ext_TC_Communication_EmailAccount::splitEmails($this->oTemplate->cc);
		}

		if(!empty($this->oTemplate->bcc)) {
			$oMail->bcc = \Ext_TC_Communication_EmailAccount::splitEmails($this->oTemplate->bcc);
		}

		if(!empty($this->aAttachments)) {
			$oMail->attachments = $this->aAttachments;
		}

		$bSuccess = $oMail->send($aRecipients);

		if($bSuccess) {

			// Save relation
			$this->saveLog($oMail);

		}

		return $bSuccess;
	}

	protected function replaceMailCodePlaceholder(&$string) {

		// Mail-Code Platzhalter ersetzen
		if(strpos($string, '[#]') !== false) {

			if($this->communicationCode === null) {
				$this->communicationCode = \Ext_TC_Communication::generateCode();
			}

			$communicationCodeTag = '[#'.$this->communicationCode.']';
			$string = str_replace('[#]', $communicationCodeTag, $string);

		}

	}

	protected function saveLog(\WDMail $mail) {

		$log = new \Ext_TC_Communication_Message();
		$log->date = date('Y-m-d H:i:s');
		$log->direction = 'out';
		$log->content_type = 'html';
		$log->sent = 1;

		$relations = [];

		if($this->oEntity) {

			$relations[] = ['relation' => get_class($this->oEntity), 'relation_id' => (int)$this->oEntity->id];

			if(method_exists($this->oEntity, 'setAdditionalMessageRelations')) {
				$this->oEntity->setAdditionalMessageRelations($relations);
			}

		}

		$sender = $mail->sender_object;

		if(!empty($sender)) {

			$relations[] = ['relation' => get_class($sender), 'relation_id' => $sender->id];

			$senderLog = $log->getJoinedObjectChild('addresses');
			$senderLog->type = 'from';
			$senderLog->address = $sender->email;
			$senderLog->name = $sender->sFromName;
			if($sender->id > 0) {
				$senderLog->relations =
					[
						[
							'relation' => get_class($sender),
							'relation_id' => $sender->id
						]
					];
			}

		}

		if(!empty($this->communicationCode)) {
			$log->codes = [$this->communicationCode];
		}

		$logTemplate = $log->getJoinedObjectChild('templates');
		$logTemplate->template_id = $this->oTemplate->id;

		$recipients = [
			'to' => $mail->to,
			'cc' => $mail->cc,
			'bcc' => $mail->bcc
		];

		foreach($recipients as $type=>$recipientAddresses) {
			foreach($recipientAddresses as $sRecipientAddress) {
				$oRecipient = $log->getJoinedObjectChild('addresses');
				$oRecipient->type = $type;
				$oRecipient->address = $sRecipientAddress;
			}
		}

		foreach($mail->attachments as $path=>$filename) {
			$oFile = $log->getJoinedObjectChild('files');
			$oFile->file = str_replace(\Util::getDocumentRoot(false), '', $path);
			$oFile->name = $filename;
		}

		$log->relations = $relations;
		$log->subject = (string)$mail->subject;
		$log->content = (string)$mail->html;

		if($mail->message instanceof \Swift_Message) {
			$log->imap_message_id = '<'.$mail->message->getId().'>';
			$log->unseen = 0;
			if(!empty($sender)) {
				$log->account_id = $sender->id;
			}
		}

		$log->save();

	}

}