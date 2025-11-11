<?php

namespace TsMobile\Generator;

use TsMobile\Service\AbstractApp;
use TsMobile\Generator\Pages\Error as ErrorPage;

class PageHandler {
	
	protected $_oApp = null;
	
	protected $_sPage = 'welcome';
	
	/**
	 * Konstruktor
	 * 
	 * @param \TsMobile\Service\AbstractApp $oApp
	 * @param string $sPage
	 */
	public function __construct(AbstractApp $oApp, $sPage) {
		$this->_oApp = $oApp;
		
		if(!empty($sPage)) {
			$this->_sPage = $sPage;
		}	
	}
	
	/**
	 * Liefert das angefragte Page-Objekt zurück
	 * 
	 * @return \TsMobile\Generator\AbstractPage
	 */
	public function getPage() {
		
		$aIgnoredPages = array('logout', 'change_password');
		if(in_array($this->_sPage, $aIgnoredPages)) {
			return null;
		}
		
		$aPages = $this->_oApp->getPages();
		$aPages = $this->_oApp->filterPages($aPages);
		
		$oPage = null;
				
		foreach($aPages as $aItem) {
			if(
				!empty($aItem['items'][$this->_sPage]) &&
				!empty($aItem['items'][$this->_sPage]['class'])	
			) {
				$sClass = (string) $aItem['items'][$this->_sPage]['class'];
				$oPage = new $sClass($this->_oApp);
				break;
			}
		}

		// Wenn die angeforderte Seite nicht den Vorgaben der App entspricht wird eine 
		// Fehlerseite generiert				
		if(!$oPage) {
			$oPage = new ErrorPage($this->_oApp);
			$oPage->setErrorMessage($this->_oApp->t('Site not found!'));
		}
		
		return $oPage;
	}
	
}
