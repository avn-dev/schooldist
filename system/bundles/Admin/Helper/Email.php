<?php

namespace Admin\Helper;

use Admin\Helper\Core;

class Email {
	
	private $sBundleDir;
	private $sFallbackLanguage = 'en';
	private $sSubject;
	
	/**
	 * @var Core\Service\Templating
	 */
	private $oTemplating;

	/**
	 * @var \WDMail
	 */
	private $oMail;

	private $aAttachments;

	public function __construct(string $sBundle) {

		$oBundleHelper = new \Core\Helper\Bundle();
		$sBundleDir = $oBundleHelper->getBundleDirectory($sBundle);
		
		$this->sBundleDir = $sBundleDir;
		
		$this->oTemplating = new \Core\Service\Templating;

		$this->oMail = new \WDMail();

	}
	
	public function setFallbackLanguage(string $sLanguage) {
		$this->sFallbackLanguage = $sLanguage;
	}

	public function setReplyTo($aReplyTo) {

		$this->oMail->replyto = $aReplyTo;

	}

	public function setSubject(string $sSubject) {

		$this->sSubject = $sSubject;

	}

	/**
	 * @param string $sName
	 * @param array $aTo
	 * @param array $aVariables
	 * @return bool
	 */
	public function send(string $sName, array $aTo, array $aVariables) {
		
		$this->oTemplating->assign($aVariables);
		
		$sSubject = $this->getSubject($sName);
		$sContent = $this->getContent($sName);

		$this->oMail->subject = $sSubject;
		$this->oMail->html = $sContent;

		$this->oMail->attachments = $this->aAttachments;

		$bSuccess = $this->oMail->send($aTo);
	
		return $bSuccess;
	}
	
	private function getSubject(string $sName) {

		if ($this->sSubject !== null) {
			return $this->sSubject;
		}

		$sTemplatePath = $this->sBundleDir.'/Resources/views/emails/'.$sName.'.subject.tpl';

		if(file_exists($sTemplatePath) === false) {
			throw new \RuntimeException('No e-mail template "'.$sName.'" in "'.$this->sBundleDir.'" found!');
		}
		
		$sSubject = $this->oTemplating->fetch($sTemplatePath);

		return $sSubject;
	}
	
	private function getContent(string $sName) {
		
		$sTemplatePath = $this->sBundleDir.'/Resources/views/emails/'.$sName.'.tpl';

		if(file_exists($sTemplatePath) === false) {
			throw new \RuntimeException('No e-mail template "'.$sName.'" in "'.$this->sBundleDir.'" found!');
		}
		
		$sContent = $this->oTemplating->fetch($sTemplatePath);
		
		return $sContent;
	}

	public function addAttachement($sPath, $sName) {

		$this->aAttachments[$sPath] = $sName;
	}
	
	public function getMail() {
		return $this->oMail;
	}
	
}
