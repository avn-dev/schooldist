<?php
/**
 * Default GUI Class for Addresses
 */
class Ext_TC_Address_Gui2 extends Ext_TC_Gui2 {

	protected $_bFirstDisplay = true;


	/**
	 * see parent + set default wdbasic
	 * @param type $sHash
	 * @param type $sDataClass
	 * @param type $sViewClass 
	 */
	public function __construct($sHash='', $sDataClass = 'Ext_TC_Gui2_Data', $sViewClass = '', $sInstanceHash = null) {
		parent::__construct($sHash, $sDataClass, $sViewClass, $sInstanceHash);

		$this->setWDBasic(Factory::getClassName('Ext_TC_Address'));
		$this->multiple_selection = 0;
		
	}
	
	/**
	 * see parent
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
	 * see parent
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
	 * add default addresses columns
	 * @param type $sTableAlias
	 * @param type $oColumnGroup 
	 */
	public function addDefaultColumns($sTableAlias='', $oColumnGroup = NULL) {

		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'company';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Firma');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$this->setColumn($oColumn);

		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'address';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Adresse');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$this->setColumn($oColumn);
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'zip';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('PLZ');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$this->setColumn($oColumn);
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'city';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Stadt');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$this->setColumn($oColumn);
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'country_iso';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Land');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$aCountries = Ext_TC_Country::getSelectOptions();
		$oColumn->format		= new Ext_Gui2_View_Format_Selection($aCountries);
		$this->setColumn($oColumn);
		
		//parent::addDefaultColumns($sTableAlias, $oColumnGroup);
	}
	
	/**
	 * set the Defailt Address Bars + Icons
	 */
	protected function setDefaultBars(){
		
		$aAccess = $this->access;
		$sAccess = $aAccess[0];
		
		if(empty($sAccess)){
			$sAccess = 'tc_contact';
		}
		
		$oDialog = $this->createDialog($this->t('Adresse bearbeiten'), $this->t('Neue Adresse'));
		$oDialog->width = 950;
		$oDialog->access = array($sAccess, 'edit');
		
		$aCountries = Ext_TC_Country::getSelectOptions();
		
		$oTab = $oDialog->createTab($this->t('Informationen'));

		$oTab->setElement(
			$oDialog->createRow(
				$this->t('Firma'),
				'input',
				array(
					'db_alias'=>'tc_a',
					'db_column' => 'company',
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
					$this->t('Adresse'), 
					'textarea', 
					array(
					'db_alias'=>'tc_a',
						'db_column' => 'address',
						'required' => 1
						)
					)
			);
		
		$oTab->setElement(
			$oDialog->createRow(
					$this->t('Adresszusatz'), 
					'textarea', 
					array(
					'db_alias'=>'tc_a',
						'db_column' => 'address_addon'
						)
					)
			);
		
		$oTab->setElement(
			$oDialog->createRow(
				$this->t('PLZ'), 
				'input', 
				array(
					'db_alias'=>'tc_a',
					'db_column' => 'zip'
				)
			)
		);
		
		$oTab->setElement(
			$oDialog->createRow(
					$this->t('Stadt'), 
					'input', 
					array(
					'db_alias'=>'tc_a',
						'db_column' => 'city'
						)
					)
			);
		
		$oTab->setElement(
			$oDialog->createRow(
					$this->t('Land'), 
					'select', 
					array(
						'db_alias'=>'tc_a',
						'db_column' => 'country_iso',
						'select_options' => $aCountries,
						'required' => 1
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
	 *
	 * @param Ext_Gui2_Dialog $oDialog 
	 */
	public function setAdditionalTabsByRef(&$oDialog){
		
	}
	
	/**
	 *
	 * @param Ext_Gui2_Dialog_Bar $oIconBar 
	 */
	public function setAdditionalIconsByRef(&$oIconBar){
		
	}
	
}
