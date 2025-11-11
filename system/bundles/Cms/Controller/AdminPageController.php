<?php

namespace Cms\Controller;

class AdminPageController extends AbstractPageController {
	
	protected $_sAccessRight = 'edit';
	
	public function editPage($iPageId=null, $sLanguage=null, $sMode=null) {

		$oPage = \Cms\Entity\Page::getInstance($iPageId);
		
		if($oPage->element === 'template') {
			$this->redirectUrl('/admin/extensions/cms/pagetemplate.html?template_id='.$oPage->id, false);
		} elseif($sMode === 'settings') {
			$this->redirectUrl('/admin/extensions/cms/preferences.html?page_id='.$oPage->id, false);
		} elseif($sMode === 'structure') {
			$this->redirectUrl('/cms/content/structure?page_id='.$oPage->id.'&language='.$sLanguage, false);
		} elseif($sMode === 'edit') {
			$oPage->setMode(\Cms\Entity\Page::MODE_EDIT);
		} elseif($sMode === 'preview') {
			$oPage->setMode(\Cms\Entity\Page::MODE_PREVIEW);
		} else {
			$oPage->setMode(\Cms\Entity\Page::MODE_LIVE);
		}

		// Das generieren der Seite an sich muss im Frontend laufen
		\System::wd()->getInstance('frontend');
		
		$this->generatePage($oPage, $sLanguage, [], true);

	}
	
}
