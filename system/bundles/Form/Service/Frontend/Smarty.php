<?php

namespace Form\Service\Frontend;

class Smarty extends \Form\Service\Frontend {
	
	/**
	 * type \SmartyWrapper
	 */
	private $oSmarty;
	
	public function __construct($sInstanceHash) {
		
		$this->oSmarty = new \Cms\Service\Smarty();

		parent::__construct($sInstanceHash);

	}
	
	public function __wakeup() {
		
		parent::__wakeup();
		
		$this->oSmarty = new \Cms\Service\Smarty();
		
	}
	
	public function parse() {

		$oConditionService = $this->getConditionService();
		$sConditionJavaScript = $oConditionService->getFormConditionJavaScript();
		
		$sConditionJavaScript = $this->spamShield->html($this).$sConditionJavaScript;
		
		$this->oSmarty->assign('sConditionJavaScript', $sConditionJavaScript);
		$this->oSmarty->assign('sCaptchaHtml', $this->getFormCaptchaHtml());
		$this->oSmarty->assign('sCaptchaJavaScript', $this->getFormCaptchaJavaScript());
					
		$aPages	= $this->oForm->getJoinedObjectChilds('pages', true);

		$this->oSmarty->assign('oForm', $this->oForm);
		$this->oSmarty->assign('aPages', $aPages);
		$this->oSmarty->assign('aFields', $this->getFieldProxies());
		
		if($this->oPage !== null) {
			$this->oSmarty->assign('iCurrentPageId', $this->oPage->id);
		} else {
			$this->oSmarty->assign('iCurrentPageId', 0);
		}

		$this->oSmarty->assign('iLastPageId', $this->oLastPage->id);
		$this->oSmarty->assign('iFirstPageId', $this->oFirstPage->id);
		$this->oSmarty->assign('iPreviousPageId', $this->oPreviousPage->id);
		
		$this->oSmarty->assign('bSuccess', $this->bSuccess);
		$this->oSmarty->assign('sFormAction', $_SERVER['PHP_SELF']);
		$this->oSmarty->assign('iContentId', $this->aElementData['content_id']);
		$this->oSmarty->assign('sInstanceHash', $this->sInstanceHash);

		$this->oSmarty->displayExtension($this->aElementData);

	}
	
	public function handleSuccess() {
		
		parent::handleSuccess();

		$this->oPage = null;

		$this->oSmarty->assign('sMessage', $this->oForm->message_success);
		$this->oSmarty->assign('aAllFields', $this->aFieldProxies);
		
	}
	
	public function handleErrors() {
		
		// Fehler anzeigen
		$this->oSmarty->assign('sMessage', $this->oForm->message_failed);
		
	}

	public function setMessage($sMessage) {
		$this->oSmarty->assign('sMessage', $sMessage);
	}
		
}
