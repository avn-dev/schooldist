<?php

namespace TsMobile\Generator;

use TsMobile\Service\AbstractApp;

class Navigation {

	/**
	 * @var \TsMobile\Service\AbstractApp 
	 */
	protected $_oApp = null;	
	/**
	 * @var array 
	 */
	protected $_aElements = array();
	
	/**
	 * Konstruktor
	 * 
	 * @param \TsMobile\Service\AbstractApp $oApp
	 */
	public function __construct(AbstractApp $oApp) {
		$this->_oApp = $oApp;
	}

	/**
	 * Liefert alle Menüpunkte und Überschriften der App
	 * 
	 * @return array
	 */
	public function generate() {
		
		if(empty($this->_aElements)) {			
			// Alle Pages der App holen
			$aPages = $this->_oApp->getPages();
			$aPages = $this->_oApp->filterPages($aPages);
			
			foreach($aPages as $sPosition => $aItem) {
				// Überschrift generieren
				$this->_generateNavigationHeading($sPosition, $aItem);			
				foreach((array)$aItem['items'] as $sPage => $aSubItem) {
					// Menüpunkt generieren
					$this->_generateNavigationItem($sPosition, $sPage, $aSubItem);
				}
			}
		}
		
		return $this->_aElements;
	}

	/**
	 * Generiert eine Überschrift für die Navigation der App
	 * 
	 * @param string $sPosition
	 * @param array $aItem
	 */
	protected function _generateNavigationHeading($sPosition, $aItem) {		
		$this->_aElements[$sPosition]['heading'] =  array(
			'item_type' => 'heading',
			'title' => $aItem['title']
		);
	}
	
	/**
	 * Generiert einen Menüpunkt für die Navigation der App
	 * 
	 * @param string $sPosition
	 * @param string $sPage
	 * @param array $aItem
	 */
	protected function _generateNavigationItem($sPosition, $sPage, $aItem) {		
		$this->_aElements[$sPosition][$sPage] =  array(
			'item_type' => 'item',
			'title' => $aItem['title'],
			'type' => $aItem['type']
		);
	}

}
