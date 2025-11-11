<?php

namespace Office\Service;

class Email {
	
	private $aAttachments = array();
	private $sHtml;
	private $sText;
	private $sSubject;
	private $sReplyTo;
	
	public function __construct() {
	}
	
	public function addAttachment($sFile) {
		$this->aAttachments[$sFile] = null;
	}
	
	public function setSubject($sSubject) {
		$this->sSubject = $sSubject;
	}
	
	public function setHtml($sHtml) {
		$this->sHtml = $sHtml;
	}
	
	public function setText($sText) {
		$this->sText = $sText;
	}
	
	public function setReplyTo($sReplyTo) {
		$this->sReplyTo = $sReplyTo;
	}
	
	public function send(array $aTo) {

		$sError = null;
		
		$oLog = \Log::getLogger('office');

		// Leere Empfänger entfernen
		$aTo = array_filter($aTo);

		$mailConfig = [];
		
		$mailer = new \WDMail;
		$mailConfig = $mailer->mailConfig;
		$mailConfig['mailers']['smtp']['host'] = \Ext_Office_Config::get('email_smtp_host');
		$mailConfig['mailers']['smtp']['port'] = \Ext_Office_Config::get('email_smtp_port');
		$mailConfig['mailers']['smtp']['encryption'] = \Ext_Office_Config::get('email_smtp_encryption');
		$mailConfig['mailers']['smtp']['username'] = \Ext_Office_Config::get('email_smtp_user');
		$mailConfig['mailers']['smtp']['password'] = \Ext_Office_Config::get('email_smtp_pass');
		
		$mailConfig['from']['address'] = \Ext_Office_Config::get('email_smtp_email');
		$mailConfig['from']['name'] = \Ext_Office_Config::get('email_smtp_name');
		
		$mailer->mailConfig = $mailConfig;
		
		$mailer->subject = $this->sSubject;
		
		if(!empty($this->sReplyTo)) {
			$mailer->replyto = $this->sReplyTo;
		}

		if($this->sText !== null) {
			$mailer->text = $this->sText;
		}

		if($this->sHtml !== null) {
			$mailer->html = $this->sHtml;
		}
		
		// Attachment
		if(!empty($this->aAttachments)) {
			$mailer->attachments = $this->aAttachments;
		}

		// Send the message
		try {
			$result = $mailer->send($aTo);		
		} catch(\Exception $e) {
			$result = false;
			$sError = $e->getMessage();
		}

		$oLog->addInfo('E-mail', [$result, $this->sSubject, $this->sText, $this->sHtml, $aTo, $sError]);

		if(
			!empty($result) &&
			$result > 0
		) {
			return true;
		}
		
		return false;
	}
	
}