<?php
/**
 * E-Mail Address GUI
 * Build automatic Default Elements into the gui
 */
class Ext_TC_Email_Address_Gui2 extends Ext_TC_Gui2 {

	/**
	 * Check variable for checking first display comment
	 * @var boolean 
	 */
	protected $_bFirstDisplay = true;
	
	/**
	 * See Parent + set default WDBasic
	 * @param type $sHash
	 * @param type $sDataClass
	 * @param type $sViewClass 
	 */
	public function __construct($sHash='', $sDataClass = 'Ext_TC_Gui2_Data', $sViewClass = '', $sInstanceHash = null) {
		parent::__construct($sHash, $sDataClass, $sViewClass, $sInstanceHash);

		$this->setWDBasic('Ext_TC_Email_Address');
		$this->multiple_selection = 0;
		
	}
	
	/**
	 * see Parent
	 * @param type $bFirst
	 * @return type 
	 */
	public function startPageOutput($bFirst = true){
		
		if($this->_bFirstDisplay){
		
			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;
		
		}
		
		return parent::startPageOutput($bFirst);
	}
	
	/**
	 * see Parent
	 * @param type $aOptionalData
	 * @param type $bNoJavaScript 
	 */
	public function display($aOptionalData = array(), $bNoJavaScript = false) {
		
		if($this->_bFirstDisplay){
		
			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;
		
		}
		
		parent::display($aOptionalData, $bNoJavaScript);
		
	}

	/**
	 * Add E-Mail Addresses Default columns
	 * @param type $sTableAlias
	 * @param type $oColumnGroup 
	 */
	public function addDefaultColumns($sTableAlias='', $oColumnGroup = NULL) {
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'email';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('E-Mail');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$this->setColumn($oColumn);
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'master';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Standard E-Mail');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_Gui2_Format_YesNo();
		$this->setColumn($oColumn);
		
		//parent::addDefaultColumns($sTableAlias, $oColumnGroup);
	}
	
	/**
	 * Set Default Bars with Elements
	 * New/Edit/Delete/Filter
	 */
	protected function setDefaultBars(){
		
		$aAccess = $this->access;
		$sAccess = $aAccess[0];
		
		if(empty($sAccess)){
			$sAccess = 'tc_contact';
		}
		
		$oDialog = $this->createDialog($this->t('E-Mail-Adresse "{email}" bearbeiten'), $this->t('Neue E-Mail-Adresse'));
		$oDialog->access = array($sAccess, 'edit');
		
		$aCountries = Ext_TC_Country::getSelectOptions();
		
		$oTab = $oDialog->createTab($this->t('Informationen'));
		$oTab->setElement(
			$oDialog->createRow(
					$this->t('E-Mail'), 
					'input', 
					array(
						'db_column' => 'email',
						'required' => 1,
						'db_alias' => 'tc_e'
						)
					)
			);
		$oTab->setElement(
			$oDialog->createRow(
					$this->t('Standard E-Mail'), 
					'checkbox', 
					array(
						'db_column' => 'master',
						'db_alias' => 'tc_e'
						)
					)
			);
		$oDialog->setElement($oTab);
		$this->setAdditionalTabsByRef($oDialog);
		
		$oBar = $this->createBar();
		$oBar->width = '100%';
		$oIcon = $oBar->createNewIcon($this->t('Neuer Eintrag'), $oDialog, $this->t('Neuer Eintrag'));
		$oIcon->access = array($sAccess, 'new');
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createEditIcon($this->t('Editieren'), $oDialog, $this->t('Editieren'));
		$oIcon->access = array(
			array($sAccess, 'show'),
			array($sAccess, 'edit')
		);
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createDeleteIcon($this->t('Löschen'), $this->t('Löschen'));
		$oIcon->access = array($sAccess, 'delete');
		$oBar->setElement($oIcon);
		
		$this->setAdditionalIconsByRef($oBar);
		
		$this->setBar($oBar);
		

		$oBar = $this->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';
		$oPagination = $oBar->createPagination(false, true);
		$oBar->setElement($oPagination);
		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$this->setBar($oBar);
		
	}
	
	/**
	 * Methode for overridung
	 * Set Additional Elements to the Dialog
	 * @param Ext_Gui2_Dialig $oDialog 
	 */
	public function setAdditionalTabsByRef(&$oDialog){
		
	}
	
	/**
	 * Methode for overridung
	 * Set Additional Elements to the Icon Bar
	 * @param Ext_Gui2_Bar $oIconBar 
	 */
	public function setAdditionalIconsByRef(&$oIconBar){
		
	}
	
}
