<?php

namespace Cms\Controller;

/**
 * Description of AdminController
 *
 * @author Mark Koopmann
 */
class AdminController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'edit';
	
	public function page() {
		
		$oSmarty = new \SmartyWrapper;

		$oSiteSelection = new \Cms\Gui2\Selection\SiteSelection();
		$aSites = $oSiteSelection->getOptions([], [], new \stdClass);
		unset($aSites['']);
		
		$oSmarty->assign('aSites', $aSites);
		$oSmarty->assign('iSiteId', \Cms\Helper\Data::getSessionSiteId());

		$sContent = $oSmarty->fetch(\Util::getDocumentRoot().'system/bundles/Cms/Resources/views/admin/page.tpl');

		$this->set('title', 'CMS');
		$this->set('content', $sContent);
		$this->set('scroll', false);
		
	}
	
	private function buildStructureArray($sLanguage, $aStructure): array {
		
		if(empty($aStructure)) {
			return [];
		}
		
		$aReturn = [];

		foreach($aStructure as $aItem) {
			
			$aNodes = $this->buildStructureArray($sLanguage, $aItem['childs']);

			$aAdd = [
				'text' => $aItem['page']->title,
				'icon' => 'glyphicon glyphicon-file',
				'selectable' => true,
				'page-id' => $aItem['page']->id,
				'active' => $aItem['page']->active,
				'language' => $sLanguage,
				'folder' => false,
				'indexpage' => $aItem['page']->indexpage
			];

			if(
				$aItem['page']->file === 'index' ||
				$aItem['page']->file === ''
			) {
				$aAdd['icon'] = 'fa fa-folder';
				$aAdd['folder'] = true;
			} elseif($aItem['page']->indexpage == 1) {
				$aAdd['icon'] = 'fa fa-file-text';
			} else {
				$aAdd['icon'] = 'fa fa-file';
			}
			
			if($aItem['page']->isActive() === false) {
				$aAdd['icon'] .= '-o';
			}

			if(!empty($aNodes)) {
				$aAdd['nodes'] = $aNodes;
			}
			
			$aReturn[] = $aAdd;
		}
		
		return $aReturn;
	}
	
	public function structure($iSiteId, $sLanguage) {
		
		// Template
		if($iSiteId == 0) {
			
			$aStructure = \Cms\Helper\Template::getStructure();
			
		} else {

			$oSite = \Cms\Entity\Site::getInstance($iSiteId);

			$aStructure = $oSite->getPageStructure($sLanguage, '', -1);	

		}
		
		$aReturn = $this->buildStructureArray($sLanguage, $aStructure);
		
		$this->set('structure', $aReturn);
		
	}
	
	public function siteLanguages($iSiteId) {
		
		if(is_numeric($iSiteId)) {
			
			$oSite = \Cms\Entity\Site::getInstance($iSiteId);
			
			\Cms\Helper\Data::setSessionSiteId((int)$oSite->id);

			$aLanguages = $oSite->getLanguages();
			
			$this->set('languages', $aLanguages);
		}
		
	}
	
	public function publishContent($iPageId, $sLanguage, $sAction) {
		
		$oPage = \Cms\Entity\Page::getInstance($iPageId);
		
		$oPageAccess = new \Cms\Helper\Access\Page($oPage);

		if($oPageAccess->checkRightInPath("publish")) {

			$aHook = [
				'page' => $oPage,
				'action' => $sAction
			];
			
			\System::wd()->executeHook('publish_page', $aHook);

			// ignore 'STOP' button
			ignore_user_abort(true);

			if($sAction == "accept") {

				$res_content = (array)\DB::getQueryRows("SELECT * FROM cms_content WHERE page_id = ".(int)$oPage->id."");
				foreach($res_content as $my) {

					\DB::executeQuery("UPDATE cms_content SET backup = content, public = '".\DB::escapeQueryString($my['content'])."', uptodate = 1 WHERE id = ".(int)$my['id']."");

					$res_block = (array)\DB::getQueryRows("SELECT * FROM cms_blockdata WHERE page_id IS NULL AND content_id = ".(int)$my['id'].""); 
					foreach($res_block as $my_block) {

						$sSql = "UPDATE cms_blockdata SET public = '".\DB::escapeQueryString($my_block['content'])."', uptodate = 1 WHERE id = ".(int)$my_block['id']."";
						\DB::executeQuery($sSql);

					}
				}

				$res_block = (array)\DB::getQueryRows("SELECT * FROM cms_blockdata WHERE page_id = ".(int)$oPage->id.""); 
				foreach($res_block as $my_block) {
					$sSql = "UPDATE cms_blockdata SET public = '".\DB::escapeQueryString($my_block['content'])."', uptodate = 1 WHERE id = ".(int)$my_block['id']."";
					\DB::executeQuery($sSql);
				}
				
				\Log::enterLog($oPage->id, \Cms\Helper\Log::LOG_PAGE_PUBLISHED);

				$oPage->clearCache($sLanguage);
				
				$this->set('message', \L10N::t('Der Inhalt der Seite wurde erfolgreich freigegeben!', 'CMS'));
				$this->set('success', true);
				
			} elseif($sAction == "deny") {

				$res = \DB::executeQuery("UPDATE cms_content SET content = backup, uptodate = 1 WHERE page_id = ".(int)$oPage->id."");

				$res_content = (array)\DB::getQueryRows("SELECT * FROM cms_content WHERE page_id = ".(int)$oPage->id."");
				foreach($res_content as $my) {
					\DB::executeQuery("UPDATE cms_blockdata SET content = public, uptodate = 1 WHERE page_id IS NULL AND content_id = ".(int)$my['id']."");
				}

				\DB::executeQuery("UPDATE cms_blockdata SET content = public, uptodate = 1 WHERE page_id = ".(int)$oPage->id."");

				$this->set('message', \L10N::t('Der Inhalt der Seite wurde erfolgreich abgelehnt und zurückgesetzt!', 'CMS'));
				$this->set('success', true);

			}
			
		} else {
			
			$this->set('message', \L10N::t('Der Inhalt der Seite konnte nicht freigegeben werden!', 'CMS'));
			$this->set('success', false);
			$this->set('error', 'no_access');
			
		}
			
	}
	
	public function saveContent() {

		$iPageId = (int)$this->_oRequest->get('page_id');
		$iContentId = (int)$this->_oRequest->get('content_id');
		$iNumber = (int)$this->_oRequest->get('number');
		$sContent = $this->_oRequest->get('content');
		$sLanguage = $this->_oRequest->get('language');
		
		$oRepository = \Cms\Entity\BlockData::getRepository();

		$aCriteria = [
			'page_id' => $iPageId,
			'content_id' => $iContentId,
			'data_id' => $iNumber,
			'language' => $sLanguage
		];
		$oBlockData = $oRepository->findOneBy($aCriteria);
		
		// Fallback für page_id = NULL
		if($oBlockData === null) {
			$aCriteriaFallback = [
				'page_id' => NULL,
				'content_id' => $iContentId,
				'data_id' => $iNumber,
				'language' => $sLanguage
			];
			$oBlockData = $oRepository->findOneBy($aCriteriaFallback);
		}

		// Fallback für language = NULL
		if($oBlockData === null) {
			$aCriteriaFallback = [
				'content_id' => $iContentId,
				'data_id' => $iNumber,
				'language' => ''
			];
			$oBlockData = $oRepository->findOneBy($aCriteriaFallback);
		}
		
		if($oBlockData === null) {
			$oBlockData = \Cms\Entity\BlockData::getInstance();
		}

		// Auch bei vorhandenem Block Daten setzen, damit das vervollständigt wird
		foreach($aCriteria as $sKey=>$mValue) {
			$oBlockData->$sKey = $mValue;
		}
		
		$oBlockData->content = $sContent;
		$oBlockData->uptodate = 0;
		
		$bValidate = $oBlockData->validate();
		
		if($bValidate === true) {
			
			$oBlockData->save();
			
			$this->set('message', \L10N::t('Inhalt erfolgreich gespeichert!', 'CMS'));
			$this->set('success', true);
			
		} else {
			
			$this->set('message', \L10N::t('Inhalt konnte nicht gespeichert werden!', 'CMS'));
			$this->set('success', false);
			
		}

	}
	
	public function editStructure() {
		
		$aTransfer = [];

		$oSiteSelection = new \Cms\Gui2\Selection\SiteSelection();
		$aSites = $oSiteSelection->getOptions([], [], new \stdClass);
		unset($aSites['']);

		$aTransfer['aSites'] = $aSites;
		
		$iSiteId = $this->_oRequest->get('site_id');
		
		if(!empty($iSiteId)) {
			$aTransfer['iSiteId'] = $iSiteId;
		
			$oSite = \Cms\Entity\Site::getInstance($iSiteId);
			
			$aTransfer['aLanguages'] = $oSite->getLanguages();

			$sLanguage = $this->_oRequest->get('language');

			if(empty($sLanguage)) {
				$sLanguage = reset($aTransfer['aLanguages'])['code'];
			}

			$aTransfer['sLanguage'] = $sLanguage;
				
			$aStructure = $oSite->getPageStructure($sLanguage, '', -1);	
			
			$aStructure = $this->buildStructureArray($sLanguage, $aStructure);

			$aTransfer['aStructure'] = $aStructure;
			
		}
		
		return response()->view('page/structure', $aTransfer);
	}
	
	
	public function saveStructure() {

		$aPages = $this->_oRequest->getJSONDecodedPostData();
		
		// Mapping of Ids and Folders
		$aPage = reset($aPages);
		$iPageId = (int)str_replace('page_', '', $aPage['id']);
		$oPage = \Cms\Entity\Page::getInstance($iPageId);
		
		$oSite = $oPage->getJoinedObject('site');

		foreach($aPages as $aPage) {

			$iPageId = (int)str_replace('page_', '', $aPage['id']);
			$iParentPageId = (int)str_replace('page_', '', $aPage['parentId']);

			$oPage = \Cms\Entity\Page::getInstance($iPageId);

			if(!empty($iParentPageId)) {

				$oParentPage = \Cms\Entity\Page::getInstance($iParentPageId);

				if(
					$oParentPage->file !== '' &&
					$oParentPage->file !== 'index'
				) {
					throw new \RuntimeException('Invalid parent page object!');
				}
				
				$oPage->setParentPage($oParentPage);
			}

			$oPage->position = $aPage['order'];
			$oPage->setLevel();

			$oPage->save();
			
		}

		// Routen aktualisieren
		$oRoutingService = new \Core\Service\RoutingService();
		$oRoutingService->buildRoutes();

		return response()->json(['success' => true]);
	}
	
	public function deleteStructure() {

		$iPageId = $this->_oRequest->get('page_id');

		$oPage = \Cms\Entity\Page::getInstance($iPageId);

		\Cms\Helper\Trash::dump($oPage, $iPageId);
		
		return response()->json(['success' => true]);
	}
	
	public function homeStructure($iPageId) {
		
		$oPage = \Cms\Entity\Page::getInstance($iPageId);
		$oPage->setIndex();
		
		$oPage->save();
		
		return response()->json(['success' => true]);
	}
	
}
