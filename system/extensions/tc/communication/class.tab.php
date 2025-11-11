<?php

class Ext_TC_Communication_Tab extends Ext_Gui2_Dialog_Tab
{
	/**
	 * @var Ext_Gui2_Dialog 
	 */
	protected $_oDialog;
	
	/**
	 * @var Ext_TC_Communication 
	 */
	protected $_oCommunication;
	
	protected $_sType;
	
	public function __construct(Ext_TC_Communication $oCommunication, $sType) {

		$this->_oCommunication = $oCommunication;
		$this->_oDialog = $oCommunication->getDialogObject();
		$this->_sType = $sType;
		
		// Ticket #8191 - Kommunikation ist ziemlich häßlich
		$this->_oDialog->width = 1200;
		
		if($sType === 'email') {
			$sTitle = Ext_TC_Communication::t('E-Mail');
		} elseif($sType === 'sms') {
			$sTitle = Ext_TC_Communication::t('SMS');
		} elseif($sType === 'app') {
			$sTitle = Ext_TC_Communication::t('App');
		} elseif($sType === 'notice') {
			$sTitle = Ext_TC_Communication::t('Notizen');
		} elseif($sType === 'history') {
			$sTitle = Ext_TC_Communication::t('Verlauf');
		} elseif($sType === 'placeholder') {
			$sTitle = Ext_TC_Communication::t('Platzhalter');
		} else {
			throw new Exception('No valid communication tab type given!');
		}

		parent::__construct($sTitle, false, $this->_oDialog->iTabCounter++);

		if($sType === 'email') {
			$this->_setEmailTabContent();
		} elseif($sType === 'sms') {
			$this->_setSMSTabContent();
		} elseif($sType === 'app') {
			$this->_setAppTabContent();
		} elseif($sType === 'notice') {
			$this->_setNoticeTabContent();
		} elseif($sType === 'history') {
			$this->_setHistoryTabContent();
		} elseif($sType === 'placeholder') {
			$this->_setPlaceholderTab();
		}

	}
	
	protected function _setEmailTabContent()
	{
		$this->setBaseTabContent();
	}
	
	protected function _setSMSTabContent()
	{
		$this->setBaseTabContent();
	}

	protected function _setAppTabContent()
	{
		$this->setBaseTabContent();
	}

	protected function _setPlaceholderTab() {

		$aObjects = $this->getCommunicationObject()->getSelectedObjects();
		$oObject = reset($aObjects);
		
		$oPlaceholder = $oObject->getPlaceholderObject();

		if($oPlaceholder) {
			
			$iObjectId = $oObject->getSubObject();
			$oPlaceholder->setObjects($iObjectId);
			
			$sPlaceholders = $oPlaceholder->displayPlaceholderTable('communication');
			
			$oHtml = new Ext_TC_Placeholder_Html();
			$sHtml = $oHtml->createPlaceholderContent($sPlaceholders);

			$this->setElement($sHtml);
			$this->no_padding = 1;
			$this->no_scrolling = 1;
			
			$this->class = 'communication_placeholder';
			
		}
		
	}

	protected function _setNoticeTabContent() {

		$aAccess = ['core_communication_notes', ''];
		$oGui = Ext_TC_Communication_Gui2_Data::getPage(true, $this->_oDialog->getDataObject()->getGui(), $aAccess, 'notice');

		// Kommunikationsklasse in die GUI einsetzen
		$aGuis = $oGui->getElements();
		$aGuis[0]->setOption('communication', $this->_oCommunication);

		$this->class = 'communication_notes';

		$this->setElement($oGui);
	}

	protected function _setHistoryTabContent() {

		/**
		 * 1. Wird in createDialogObject in Ext_TC_Communication gesetzt, welche vom Icon aufgerufen wird
		 * 2. Enthält die Rechte des Icons
		 * 3. Wird weitergereicht bis zur oberen GUI in der History
		 */
		$aAccess = $this->_oDialog->getDataObject()->aAccess;
		$oPage = Ext_TC_Communication_Gui2_Data::getPage(true, $this->_oDialog->getDataObject()->getGui(), $aAccess);
		$this->class = 'communication_history';

		$this->setElement($oPage);

	}
	
	/**
	 * Definieren, welche Tabs in welcher Tabarea zur Verfügung stehen.
	 * return Ext_TC_Communication_Tab_TabArea[]
	 */
	public function getInnerTabs()
	{
		throw new Exception('Please overwrite getInnerTabs()!');
	}
	
	protected function setBaseTabContent()
	{
		$aIdentities = $this->createIdentitySelect();
		$this->setElement($aIdentities);
		
		$aInnerTabs = $this->getInnerTabs();

		$oGui2TabArea = new Ext_Gui2_Dialog_TabArea();
		
		foreach($aInnerTabs as $oInnerTab) {
			/* @var Ext_TC_Communication_Tab_TabArea $oInnerTab */
			// Ext_TC_Communication_Tab_TabArea => Ext_Gui2_Dialog_TabArea
			$oTab = $oGui2TabArea->createTab($this->getCommunicationObject()->t($oInnerTab->sTitle));
			$oInnerTab->setBaseContent($oTab);
			$oGui2TabArea->setElement($oTab);
		}

		$this->setElement($oGui2TabArea);
		
	}
	
	/**
	 * Erzeugt ein Absender-Identitäten-Select
	 * @param string $sPrefix
	 * @return Ext_Gui2_Html_Div
	 */
	public function createIdentitySelect() {

		$oUser = System::getCurrentUser();
		
		$aIdentities = array();
		$iIdentityId = 0;
		
		if($oUser instanceof Ext_TC_User) {
			$aIdentities = $oUser->getIdentities(true, true);
			
			$aValues = $this->_oCommunication->getSaveValues();

			$iIdentityId = $oUser->id;
			if(!empty($aValues[$this->_sType]['identity_id'])) {
				$iIdentityId = $aValues[$this->_sType]['identity_id'];
			}			
		}
		
		$oRow = $this->_oDialog->createRow(Ext_TC_Communication::t('Absender'), 'select', array(
			'name' => 'save['.$this->_sType.'][identity_id]',
			'select_options' => $aIdentities,
			'default_value' => $iIdentityId
		));
		
		return $oRow;
	}
	
	public function createInnerTab($sTitle, $sKey)
	{
		$sTabAreaClassName = $this->_oCommunication->getClassName('Tab_Tabarea');
		$oTabArea = new $sTabAreaClassName($this);
		$oTabArea->sTitle = $sTitle;
		$oTabArea->setType($sKey);
		return $oTabArea;
	}
	
	/**
	 * @return Ext_TC_Communication
	 */
	public function getCommunicationObject()
	{
		return $this->_oCommunication;
	}
	
	public function getDialogObject()
	{
		return $this->_oDialog;
	}
	
	public function getType()
	{
		return $this->_sType;
	}
	
}
