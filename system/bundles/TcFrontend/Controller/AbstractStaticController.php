<?php

namespace TcFrontend\Controller;

use TcFrontend\Controller\AbstractController;
use TcFrontend\Service\FrontendInstance;

abstract class AbstractStaticController extends AbstractController {

	protected function beforeActionHook() {
		$this->checkStaticSessionInstance();
	}
		
	protected function checkStaticSessionInstance() {
		
		$sStaticInstanceHash = $this->getStaticInstanceHash();
									
		$sCombinationKey = $this->getFrontendCombinationKeyFromRequest();
		$sTemplateKey = $this->getTemplateKeyFromRequest();

		
		$oFrontendCombination = $this->getFrontendCombinationByKey($sCombinationKey);
		$oFrontendTemplate = $this->getFrontendTemplateByKey($sTemplateKey);
			
		$oFrontendInstance = $this->buildStaticFrontendInstance($sStaticInstanceHash, $oFrontendCombination, $oFrontendTemplate);

		if($oFrontendInstance instanceof FrontendInstance) {
			$this->oSession->putFrontendInstance($oFrontendInstance);
		}
	}
	
	abstract protected function getStaticInstanceHash();
	
	/**
	 * @param \Ext_TC_Frontend_Combination $oCombination
	 * @param \Ext_TC_Frontend_Template $oTemplate
	 * @throws \RuntimeException
	 */
	abstract protected function buildStaticFrontendInstance($sStaticInstanceHash, \Ext_TC_Frontend_Combination $oCombination, \Ext_TC_Frontend_Template $oTemplate);
	
}