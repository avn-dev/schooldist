<?php

namespace TsMobile\Service\App;

use TsMobile\Service\AbstractApp;

class Teacher extends AbstractApp {
	
	/**
	 * @var \Ext_thebing_Teacher 
	 */
	protected $_oUser = null;
	/**
	 * @var string 
	 */
	protected $_sType = 'teacher';
	
	/**
	 * Liefert die Schule des Lehrers für die Daten geholt werden sollen
	 * 
	 * @return \Ext_Thebing_School
	 */
	public function getSchool() {
		
		if(!($this->_oSchool instanceof \Ext_Thebing_School)) {
			$this->_oSchool = $this->_oUser->getSchool();
		}
		
		return $this->_oSchool;
	}

	/**
	 * Bindet den angemeldeten Benutzer an das Data-Model
	 * 
	 * @param \Ext_Thebing_Teacher $oUser
	 */
	public function setUser($oUser) {
		
		if(!$oUser instanceof \Ext_Thebing_Teacher) {
			throw new \RuntimeException('The parameter must be an instanceof "\Ext_Thebing_Teacher".');
		}
		
		$this->_oUser = $oUser;
	}	
	
	/**
	 * Liefert alle Menüpunkte der Lehrer-App
	 * 
	 * @return array
	 */
	public function getPages() {
		$aPages = parent::getPages();
		
		$aPages['top']['items']['personal'] = array(
			'title' => $this->t('Personal data'),
			'class' => '\\TsMobile\\Generator\\Pages\\Personal\\Teacher',
			'type' => 'html'
		);
		$aPages['top']['items']['classes'] = array(
			'title' => $this->t('Classes'),
			'class' => '\\TsMobile\\Generator\\Pages\\Classes',
			'type' => 'nested_list_view'
		);
		
		return $aPages;
	}	
	
}