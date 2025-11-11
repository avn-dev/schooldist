<?php

class Ext_TC_Gui2_Bar extends Ext_Gui2_Bar {

	/**
	 * Erzeugt ein Icon zum Deaktivieren des Eintrages (valid_until).
	 * 
	 * Die Tabelle darf kein valid_from enthalten!
	 * 
	 * In der WDBASIC immer setzen!
	 * 
			protected $_aFormat = array(
				'valid_until' => array(
					'format' => 'DATE'
				)
			);
	 * 
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createDeactivateIcon($sTitle, $sLabel, $aOptions = array()) {

		$oIcon = new Ext_Gui2_Bar_Icon(Ext_Gui2_Util::getIcon('cancel'), 'openDialog', $sTitle);
		$oIcon->label = $sLabel;
		$oIcon->multipleId 	= 0;
		$oIcon->active = 0;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;
		$oIcon->action = 'edit';
		$oIcon->additional = 'deactivate';
		
		if(is_array($this->_oGui->access)) {
			$oIcon->access = array($this->_oGui->access[0], 'deactivate');
		}
		
		$sAlias = '';
		if(isset($aOptions['db_alias'])) {
			$sAlias = $aOptions['db_alias'];
		} elseif($this->_oGui->query_id_alias != '') {
			$sAlias = $this->_oGui->query_id_alias;
		}

		$oDialog = $this->_oGui->createDialog(L10N::t('Ablaufdatum bearbeiten', Ext_Gui2::$sAllGuiListL10N));
		$oDialog->setElement($oDialog->createRow(L10N::t('Gültig bis', Ext_Gui2::$sAllGuiListL10N), 'calendar', array(
			'db_alias'	=> $sAlias, 
			'db_column' => 'valid_until', 
			'format'	=> Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date')
		)));
		
		$oDialog->width = 600;
		$oDialog->height = 300;
		
		$aButtonDelete = array(
					'label'			=> L10N::t('Löschen', Ext_Gui2::$sAllGuiListL10N),
					'task'			=> 'saveDialog',
					'action'		=> 'deactivate',
					'request_data'	=> '&reset=1'
		);
		
		$oDialog->aButtons = array($aButtonDelete);
		
		$oIcon->dialog_data = $oDialog;

		return $oIcon; 

	}

	public function createEditIcon($sTitle, $oDialog, $sLabel = '', $sDialogTitel = '', $bSwitchToShowAction = true) {

		$aAccess = $this->_oGui->access;
		
		$oAccess = Access::getInstance();
		
		if(
			$bSwitchToShowAction === true &&
			$oAccess instanceof Access_Backend &&
			$oAccess->checkValidAccess() === true &&
			!empty($aAccess) &&
			is_array($aAccess) &&
			count($aAccess) == 2
		) {
			$sArea = $aAccess[0];
			$sRight = $aAccess[1];
			$bEdit = $oAccess->hasRight(array($sArea, 'edit')); 
			$bEditOwn = $oAccess->hasRight(array($sArea, 'edit_own')); 
			
			if(
				$bEdit ||
				$bEditOwn
			) {
				
				$oIcon = parent::createEditIcon($sTitle, $oDialog, $sLabel, $sDialogTitel);
				
				if($bEdit) {
					$oIcon->access = [$sArea, 'edit'];
				} else {
					$oIcon->access = [$sArea, 'edit_own'];
				}
				
			} else {

				$oIcon = $this->createShowIcon($sTitle, $oDialog);
				$oIcon->access = array($sArea, 'show');

			}

		} else {
			$oIcon = parent::createEditIcon($sTitle, $oDialog, $sLabel, $sDialogTitel);
		}	

		return $oIcon;
		
	}
	
	public function createDeleteIcon($sTitle, $sLabel = ''){
	
		$oIcon = parent::createDeleteIcon($sTitle, $sLabel);
		
		$aAccess = $this->_oGui->access;
		
		if(
			!empty($aAccess) &&
			is_array($aAccess) &&
			count($aAccess) == 2
		) {
			$oIcon->access = array($aAccess[0], 'delete');
		}
		
		return $oIcon;
		
	}

	public function createNewIcon($sTitle, $oDialog, $sLabel = '', $sDialogTitel = '') {

		$oIcon = parent::createNewIcon($sTitle, $oDialog, $sLabel, $sDialogTitel);

		$aAccess = $this->_oGui->access;
		
		if(
			!empty($aAccess) &&
			is_array($aAccess) &&
			count($aAccess) == 2
		) {
			$oIcon->access = array($aAccess[0], 'new');
		}

		return $oIcon;
		
	}	

	/**
	 * Erzeugt einen Gültigkeitsfilter, der standardmäßig auf aktiv steht.
	 * 
	 * @param string $sLabel
	 * @param array $aOptions
	 * @return Ext_Gui2_Bar_Filter
	 */
	public function createValidFilter($sLabel, $aOptions = array()) {

		$aSelectOptions = array(
			'active' => L10N::t('Aktiviert', Ext_Gui2::$sAllGuiListL10N),
			'inactive' => L10N::t('Deaktiviert', Ext_Gui2::$sAllGuiListL10N)
		);
		$aSelectOptions = Ext_Gui2_Util::addLabelItem($aSelectOptions, L10N::t($sLabel));

		$oFilter = $this->createFilter('select');
		$oFilter->id = 'valid_filter';
		$oFilter->select_options = $aSelectOptions;
		$oFilter->value	= 'active';
		
		$sCurDate = date('Y-m-d'); // Damit MySQL den Query vielleicht cachen kann
		
		$sGuiQueryAlias = $this->_oGui->query_id_alias;
		
		$sAlias = '';
		if(isset($aOptions['db_alias']) && !empty($aOptions['db_alias'])) {
			$sAlias = '`'.$aOptions['db_alias'].'`.';
		} elseif(!empty($sGuiQueryAlias)) {
			$sAlias = '`'.$this->_oGui->query_id_alias.'`.';
		}
		
		$oFilter->filter_query = array(
			'active' => $sAlias."`valid_until` IS NULL OR ".$sAlias."`valid_until` = '0000-00-00' OR ".$sAlias."`valid_until` >= '".$sCurDate."'",
			'inactive' => $sAlias."`valid_until` < '".$sCurDate."' AND ".$sAlias."`valid_until` != '0000-00-00' AND ".$sAlias."`valid_until` IS NOT NULL"
		);
		
		return $oFilter;
	}
	
	/**
	 * Erzeugt ein Kommunikations-Icon
	 * 
	 * BITTE den Schlüssel der Application nutzen, wie ihn auch die Templates nutzen!
	 * 
	 * @param string $sLabel
	 * @param string $sApplication
	 * @param array $aAccess
	 */
	public function createCommunicationIcon($sLabel, $sApplication, $aAccess=null) {

		$oIcon = new Ext_Gui2_Bar_Icon('fa-envelope', 'request', $sLabel);
		$oIcon->label = $sLabel;
		$oIcon->active = 0;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;
		$oIcon->action = 'communication';
		$oIcon->additional = $sApplication;

		if(is_array($this->_oGui->access)) {
			$aAccess = array($this->_oGui->access[0], 'communication');
			$oIcon->access = $aAccess;
		}
		
		#$oDialog = Ext_TC_Factory::executeStatic('Ext_TC_Communication', 'createDialogObject', array($this->_oGui, $aAccess, $sApplication), array(0));
		#$oIcon->dialog_data = $oDialog;
		
		$this->_oGui->include_jquery = true;
		$this->_oGui->include_jquery_multiselect = true;

		$this->_oGui->include_jquery_contextmenu = true;

		#Ext_TC_Factory::executeStatic('Ext_TC_Communication', 'addJsFile', array($this->_oGui), array(0));

		#$this->_oGui->addJs('js/communication_gui.js', 'tc');
		#$this->_oGui->addOptionalData(['css' => '/assets/tc/css/communication.css']);

		return $oIcon;

	}
	
	/**
	 * Erstellt ein CSV-Export Icon mit Labelgruppe und Label
	 */
	public function createCSVExportWithLabel(){
		
		$oLabelGroup = $this->createLabelGroup($this->_oGui->t('Export'));
		$this->setElement($oLabelGroup);
		
		$oIcon = $this->createCSVExport($this->_oGui->t('Export CSV'));
		$oIcon->label = $this->_oGui->t('CSV');	
		$this->setElement($oIcon);
		
		$oIcon = $this->createExcelExport();
		$this->setElement($oIcon);
		
	}
	
}
