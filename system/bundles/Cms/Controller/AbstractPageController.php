<?php

namespace Cms\Controller;

use DebugBar\DebugBar;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;

/**
 * v3
 */
class AbstractPageController extends \MVC_Abstract_Controller {
	
	const CACHE_GROUP = 'cms_page_cache';
	
	protected function generatePage($oPage, $sLanguage, $aParameters=[], $bSkipCache=false) {
		global $objWebDynamicsDAO, $session_data, $system_data, $page_data, $template_data, $_VARS, $site_data, $user_data;

		// Möglichst früh, wegen Zeitmessung
		$objPageParser = new \Cms\Service\PageParser($oPage);
		$objPageParser->setStartTime($this->fStartTime);

		if(
			\System::d('debugmode') == 2 &&
			class_exists('DebugBar')
		) {
			
			$oDebugBar = new DebugBar();
			$oDebugBar->addCollector(new PhpInfoCollector());
			$oDebugBar->addCollector(new MessagesCollector());
			$oDebugBar->addCollector(new RequestDataCollector());
			$oDebugBar->addCollector(new \Cms\Helper\Collector\TimeCollector($this->fStartTime));
			$oDebugBar->addCollector(new MemoryCollector());
			$oDebugBar->addCollector(new ExceptionsCollector());
		
			$oDebugBarRenderer = $oDebugBar->getJavascriptRenderer();
			
			$oQueryCollector = new \Cms\Helper\Collector\QueryCollector();
			$oDebugBar->addCollector($oQueryCollector);

			$oTimeCollector = $oDebugBar['time'];
			
			$oTimeCollector->setMeasureStart('init', $this->fStartTime);
			$oTimeCollector->stopMeasure('init');
			
			$oTimeCollector->startMeasure('prepare');
			
		}

		$objWebDynamicsDAO = new \Cms\Helper\Data();
		
		$_VARS = $this->_oRequest->getAll();

		$oSite = \Cms\Entity\Site::getInstance($oPage->site_id);

		
		// Globale Werte überschreiben
		$system_data['project_name'] 		= $oSite->name;
		$system_data['admin_email'] 		= $oSite->email;
		$system_data['site_id'] 			= $oSite->id;
		$system_data['no_redirect_to_host']	= ($oSite->redirect_to_domain)?0:1;
		$system_data['force_https']			= (bool)$oSite->force_https;

		if($_SERVER['REDIRECT_URL'] == '/index.php') {
			$aParts = parse_url($_SERVER['REQUEST_URI']);
		} else {
			$aParts = parse_url($_SERVER['REDIRECT_URL']);
		}		
		
		$_SERVER['PHP_SELF'] = $aParts['path'];
		
		$system_data['site_id'] = $oSite->id;
		
		$page_data = $this->getPageData($oPage, $aParameters);
		$page_data['language'] = $sLanguage;

		$user_data = $this->_oAccess->getUserData();
		$this->_oAccess->reworkUserData($user_data);
		
		if(!$user_data['cms']) {

			$sMainDomain = $oSite->getMainDomain();

			// prüft, ob man sich auf der hauptdomain befindet, wenn nicht wird weitergeleitet
			if(
				$oSite->redirect_to_domain &&
				$_SERVER['HTTP_HOST'] != $sMainDomain
			) {

				if($oSite->force_https) {
					$sTarget = 'https://';
				} else {
					$sTarget = 'http://';
				}

				$sTarget .= $sMainDomain.$_SERVER['REQUEST_URI'];

				header("HTTP/1.0 301 Moved Permanently");
				header("Location: ".$sTarget);
				die();

			}

			// Wenn HTTPS erzwungen werden soll, aber auf HTTP zugegriffen wird
			if(
				$_SERVER['HTTPS'] != 'on' &&
				$oSite->force_https
			) {

				$sURI = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

				header("HTTP/1.0 301 Moved Permanently");
				header("Location: ".$sURI);
				die();

			}

		}
		
		\System::setInterfaceLanguage($sLanguage);
		
		// Muss nochmal gesetzt werden weil erst hier der Internetauftritt gesetzt ist
		\Factory::executeStatic('System', 'setLocale');

		$bCache = false;
		if(
			$bSkipCache === false &&
			\System::d('debugmode') === 0 &&
			$oPage->dynamic === '0' &&
			empty($_VARS)
		) {

			$oRouting = \Factory::getObject('\Cms\Service\Routing');

			$sCacheKey = $oRouting->getCacheKey($oPage, $sLanguage, $aParameters);

			$sContent = \WDCache::get($sCacheKey);

			if(
				!empty($sContent) &&
				$_SERVER['HTTP_USER_AGENT'] !== 'framework_warmup'
			) {
				ob_start("ob_gzhandler");
				echo $sContent;
				echo '<!-- delivered by cache '.round(microtime(true)-$this->fStartTime, 3).' -->';
				$objPageParser->insertStats();
				return;
			}
			
			$bCache = true;
			
			ob_start();
			
		}
		
		if(
			\System::d('debugmode') === 0 ||
			\System::d('debugmode') === 2 ||
			\System::d('debugmode') === 3
		) {
			$objPageProcessor = new \Cms\Service\PageProcessor($objPageParser);
			// Start des Ausgabepuffers wenn kein editmode
			$session_data['ob'] = 1;
		}

		if(isset($oTimeCollector)) {
			$oTimeCollector->stopMeasure('prepare');
			$oTimeCollector->startMeasure('generate');
		}

		include_once(__DIR__."/../../../bundles/Cms/Includes/main.inc.php");

		if($bCache === true) {
			$sContent = $objPageProcessor->content;
			\WDCache::set($sCacheKey, (60*60*24), $sContent, false, self::CACHE_GROUP);
		}
		
		if($objPageProcessor) {
			$objPageProcessor->output();
		}
		
	}
	
	public function showMessage(\Cms\Entity\Page $oPage, $sMessage) {
		
		$oSmarty = new \SmartyWrapper;

		$oSmarty->assign('oPage', $oPage);
		$oSmarty->assign('sMessage', $sMessage);

		$sContent = $oSmarty->fetch(\Util::getDocumentRoot().'system/bundles/Cms/Resources/views/admin/message.tpl');

		echo $sContent;
		
	}
	
	public function showLockingHint(\Cms\Entity\Page $oPage, array $aPageData) {
		
		$oUser = \User::getInstance($aPageData['locked_by']);
		$sMessage = sprintf(\L10N::t('Diese Seite wird gerade durch Benutzer "%s" bearbeitet.', 'Framework'), $oUser->getName());
		
		$oSmarty = new \SmartyWrapper;

		$oSmarty->assign('oPage', $oPage);
		$oSmarty->assign('sMessage', $sMessage);
		$oSmarty->assign('sButtonLabel', \L10N::t('Seite trotzdem bearbeiten', 'Framework'));
		
		$aParameters = [
			'iPageId' => $oPage->id,
			'sLanguage' => $aPageData['language'],
			'sMode' => 'edit'
		];

		$sButtonTarget = \Core\Helper\Routing::generateUrl('Cms.cms_edit_page', $aParameters).'?skip_locked=1';
		
		$oSmarty->assign('sButtonTarget', $sButtonTarget);

		$sContent = $oSmarty->fetch(\Util::getDocumentRoot().'system/bundles/Cms/Resources/views/admin/message.tpl');

		echo $sContent;
		
	}
	
	private function getPageData(\Cms\Entity\Page $oPage, $aParameters=[]) {
		
		$aPageData = $oPage->aData;
		$aPageData['original_language'] = $aPageData['language'];
		$aPageData['access'] = \Util::decodeSerializeOrJson($aPageData['access']);

		if(!empty($aParameters['title'])) {
			
			$aPageData['title'] = $aParameters['title'];
			
		} elseif(!empty($aParameters['parameters'])) {
			
			$oDynamicRouting = \Factory::getObject('\Cms\Service\DynamicRouting');
			
			if(isset($aParameters['key_parameters'])) {
				$aKeyParameters = $aParameters['key_parameters'];
			} else {
				$aKeyParameters = $aParameters['parameters'];
			}

			$aKey = [];
			foreach($aKeyParameters as $iValue) {
				if(empty($iValue)) {
					break;
				}
				$aKey[] = $iValue;
			}

			/**
			 * @todo Das hier ist nicht ausreichend. Hier muss die Sprache auf jeden Fall noch Teil sein
			 */
			$aPageData['title'] = $oDynamicRouting->getTitle(implode('_', $aKey));

		}

		if(!empty($aParameters['parameters'])) {
			$aPageData['routing']['parameters'] = $aParameters['parameters'];
		}
		
		return $aPageData;
	}

	public function getOutput() {
		
	}
	
}
