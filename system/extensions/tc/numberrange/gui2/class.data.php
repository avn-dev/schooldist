<?php

class Ext_TC_NumberRange_Gui2_Data extends Ext_TC_Gui2_Data
{

	use \Tc\Traits\Gui2\AccessMatrix;

	public static $sL10NPath = 'Thebing Core » Number ranges';

	public static function getApplications()
    {
		
		$aApplications = array(
			'document' => array(
				'invoice' => L10N::t('Rechnung', self::$sL10NPath),
				'proforma' => L10N::t('Proforma', self::$sL10NPath),
				'creditnote' => L10N::t('Gutschrift', self::$sL10NPath),
				'offer' => L10N::t('Angebot', self::$sL10NPath)
			),
			'receipt' => array(
				'payment_receipt' => L10N::t('Zahlungsbeleg', self::$sL10NPath),
				'invoice_payments' => L10N::t('Zahlungsübersicht einer Rechnung', self::$sL10NPath),
				'inquiry_payments' => L10N::t('Zahlungsübersicht einer Buchung', self::$sL10NPath)
			),
			'other' => array(
				'customer' => L10N::t('Kunde', self::$sL10NPath)
			)
		);
		
		return $aApplications;

	}
	
	public static function getApplicationCategories()
    {
		
		$aCategories = array(
			'document' => L10N::t('Dokumente', self::$sL10NPath),
			'receipt' => L10N::t('Zahlungsbelege', self::$sL10NPath),
			'other' => L10N::t('Sonstige', self::$sL10NPath)
		);
		
		return $aCategories;

	}
	
	protected function _getInnerGui($sCategory)
    {

		$aSubObjects = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true));
		$sSubObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel');
		
		$oInnerGui = $this->_oGui->createChildGui(md5('core_numberranges_allocate_'.$sCategory), Ext_TC_Factory::getClassName('Ext_TC_NumberRange_Gui2_Data'));
		$oInnerGui->gui_description = $this->_oGui->gui_description;
		$oInnerGui->access = array($this->_oGui->access[0], 'allocate');
		
		$oInnerGui->include_jquery				= true;
		$oInnerGui->include_jquery_multiselect	= true;
		$oInnerGui->column_sortable				= false;
		$oInnerGui->row_sortable				= true;

		$this->_setInnerGuiAdditionalData($oInnerGui);
		
		$oInnerGui->setWDBasic(Ext_TC_Factory::getClassName('Ext_TC_NumberRange_Allocation'));
		$oInnerGui->setTableData('orderby', array('tc_nra.name'=>'ASC'));
		$oInnerGui->setTableData('where', array('tc_nra.category'=>$sCategory));

		/**
		 * Dialog
		 */
		$oDialog = $oInnerGui->createDialog($oInnerGui->t('Zuweisung editieren'), $oInnerGui->t('Zuweisung anlegen'));
		$oDialog->height = 700;

		$oDialog->setElement(
			$oDialog->createRow(
				$oInnerGui->t('Name'),
				'input',
				array(
					'db_column' => 'name',
					'db_alias' => 'tc_nra',
					'required' => true
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$sSubObjectLabel,
				'select',
				array(
					'db_column' => 'objects',
					'db_alias' => 'tc_nra',
					'select_options'=>$aSubObjects,
					'multiple'=> 5,
					'jquery_multiple'=> 1,
					'required' => true
				)
			)
		);

		/**
		 * JoinedObjectContainer
		 */
		$oJoinContainer = $oDialog->createJoinedObjectContainer('sets', array('min'=>1, 'max'=>5));
		
		$oJoinContainer->setElement(
			$oJoinContainer->createRow(
				$this->t('Nummernkreis'), 
				'select', 
				array(
					'db_alias' => 'tc_nras',
					'db_column' => 'numberrange_id',
					'selection' => new Ext_TC_Numberrange_Gui2_Selection_Numberranges($sCategory)
				)
			)
		);

		$oJoinContainer->setElement(
			$oJoinContainer->createRow(
				$this->t('Anwendungsfall'), 
				'select', 
				array(
					'db_alias' => 'tc_nras',
					'db_column' => 'applications',
					'multiple'=> 5, 
					'jquery_multiple'=> 1,
					'selection' => new Ext_TC_Numberrange_Gui2_Selection_Applications($sCategory),
					'class' => 'numberrange_applications'
				)
			)
		);
		
		$this->_manipulateNumberrangeSetElements($oDialog, $oJoinContainer);
		
		$oDialog->setElement($oJoinContainer);
		
		/**
		 * Bars
		 */
		$oBar = $oInnerGui->createBar();

		$oIcon = $oBar->createNewIcon($oInnerGui->t('Neuer Eintrag'), $oDialog, $oInnerGui->t('Neuer Eintrag'));
		$oIcon->access = array($this->_oGui->access[0], 'allocate');
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createEditIcon($oInnerGui->t('Editieren'), $oDialog, $oInnerGui->t('Editieren'));
		$oIcon->access = array($this->_oGui->access[0], 'allocate');
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createDeleteIcon($oInnerGui->t('Löschen'), $oInnerGui->t('Löschen'));
		$oIcon->access = array($this->_oGui->access[0], 'allocate');
		$oBar->setElement($oIcon);

		$oInnerGui->setBar($oBar);

		/**
		 * Spalten
		 */
		$oColumn				= new Ext_Gui2_Head();
		$oColumn->db_column		= 'name';
		$oColumn->db_alias		= 'tc_nra';
		$oColumn->title			= $oInnerGui->t('Name');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oColumn->sortable		= 1;
		$oInnerGui->setColumn($oColumn);

		$oColumn				= new Ext_Gui2_Head();
		$oColumn->db_column		= 'numberrange_id';
		$oColumn->db_alias		= 'tc_nras';
		$oColumn->title			= $oInnerGui->t('Anwendungsfall');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_NumberRange_Format_Application();
		$oColumn->sortable		= 0;
		$oInnerGui->setColumn($oColumn);

		$oColumn				= new Ext_Gui2_Head();
		$oColumn->db_column		= 'applications';
		$oColumn->db_alias		= 'tc_nras';
		$oColumn->title			= $oInnerGui->t('Nummernkreis');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_NumberRange_Format_Numberrange();
		$oColumn->sortable		= 0;
		$oInnerGui->setColumn($oColumn);

		$oColumn				= new Ext_Gui2_Head();
		$oColumn->db_column		= 'objects';
		$oColumn->db_alias		= 'tc_nra';
		$oColumn->title			= $sSubObjectLabel;
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_NumberRange_Format_Object();
		$oColumn->sortable		= 0;
		$oInnerGui->setColumn($oColumn);

		$oInnerGui->addDefaultColumns();

		return $oInnerGui;

	}

	public function getAllocateDialog()
    {
		
		$aCategories = self::getApplicationCategories();
		
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		
		$oDialog = $this->_oGui->createDialog($this->t('Zuweisungen editieren'), $this->t('Zuweisungen editieren'));
		$oDialog->height = 750;
		$oDialog->access = array($this->_oGui->access[0], 'settings');
		
		$oTab = $oDialog->createTab($aCategories['document']);
		$oTab->setElement($this->_getInnerGui('document'));
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($aCategories['receipt']);
		if($oConfig->getValue('receipt_dependingon_invoice')) {
			
			$aNumberranges = array();
			
			$oAllocation = new Ext_TC_NumberRange_Allocation;
			$aAllocations = (array)$oAllocation->getObjectList();
			
			foreach($aAllocations as $oAllocation) {
				$aSets = (array)$oAllocation->getJoinedObjectChilds('sets');
				foreach($aSets as $oSet) {
					$aNumberranges[$oSet->numberrange_id] = 1;
				}
			}
			
			$oNumberrange = new Ext_TC_NumberRange;
			$aNumberrangeOptions = $oNumberrange->getArrayList(true);
			$aNumberrangeOptions = Ext_TC_Util::addEmptyItem($aNumberrangeOptions);
			
			$aAllocations = Ext_TC_NumberRange_Allocation::getReceiptAllocations();

			foreach($aNumberranges as $iNumberrangeId=>$iNumberrange) {

				$oNumberrange = Ext_TC_NumberRange::getInstance($iNumberrangeId);

				$oH3 = new Ext_Gui2_Html_H4();
				$oH3->setElement(sprintf($this->t('Rechnungsnummernkreis "%s"'), $oNumberrange->name));
				$oTab->setElement($oH3);

				$this->_manipulateNumberrangeDocumentElements($oDialog, $oTab, $oNumberrange);
				
				$oTab->setElement(
					$oDialog->createRow(
						$this->t('Nummernkreis'),
						'select', 
						array(
							'name' => 'receipt_numberrange_id['.$oNumberrange->id.']',
							'select_options'=> $aNumberrangeOptions, 
							'required' => true,
							'default_value' => (int)$aAllocations[$oNumberrange->id]
						)
					)
				);

			}

		} else {
			$oTab->setElement($this->_getInnerGui('receipt'));
		}
		$oDialog->setElement($oTab);		
		
		$oTab = $oDialog->createTab($aCategories['other']);
		$oTab->setElement($this->_getInnerGui('other'));
		$oDialog->setElement($oTab);
		
		return $oDialog;
	}
	
	public function getSettingsDialog()
    {
		
		$oDialog = $this->_oGui->createDialog($this->t('Einstellungen editieren'), $this->t('Einstellungen editieren'));
		$oDialog->width = 900;
		$oDialog->height = 500;
		$oDialog->access = array($this->_oGui->access[0], 'settings');
		$oDialog->bBigLabels = true;
		
		$this->_getSettingsDialogRows($oDialog);

		return $oDialog;
	}
	
	protected function _getSettingsDialogRows(&$oDialog)
    {
		
		$oRow = $oDialog->createRow($this->t('Zahlungsbelege abhängig von Rechnungsnummern'), 'checkbox', array('db_column'=>'receipt_dependingon_invoice'));

		$oDialog->setElement($oRow);
		
	}

	public function getEditDialogData($aSelectedIds, $aSaveData = array(), $aAction = false)
    {

		if($aAction['additional'] == 'settings') {
			
			$aData = array();
			$oConfig = \Factory::getInstance('Ext_TC_Config');

			foreach($aSaveData as $aOption) {

				$aTemp = array();

				$aTemp['value'] = $oConfig->getValue($aOption['db_column']);

				$aTemp['db_column']			= (string)$aOption['db_column'];
				$aTemp['id']				= (string)$aOption['id'];
				$aTemp['default_value']		= $aOption['value'];
				
				$aData[] = $aTemp;
				
			}		
			
		} else {
			$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $aAction);
		}
		
		return $aData;
		
	}
	
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true)
    {
		global $_VARS;
		
		if($sAction == 'allocate') {

			// Nicht optimal wg. Race Condition
			$sSql = "TRUNCATE TABLE `tc_number_ranges_allocations_receipts`";
			DB::executeQuery($sSql);

			foreach((array)$_VARS['receipt_numberrange_id'] as $iInvoiceNumberrangeId=>$iReceiptNumberrangeId) {
				$aInsert = array(
					'invoice_numberrange_id' => (int)$iInvoiceNumberrangeId,
					'receipt_numberrange_id' => (int)$iReceiptNumberrangeId
				);
				DB::insertData('tc_number_ranges_allocations_receipts', $aInsert);
			}

			$sIconKey = self::getIconKey($sIconAction, $sAdditional);
			$oDialog = $this->aIconData[$sIconKey]['dialog_data'];
			
			$aTransfer					= array();
			$aTransfer['action'] 		= 'saveDialogCallback';
			$aTransfer['dialog_id_tag']	= $oDialog->sDialogIDTag;
			$aTransfer['error'] 		= (array)$aErrorsAll;
			$aTransfer['data']			= (array)$aData;
			#$aTransfer['save_id'] 		= reset($aSelectedIds);

			global $_VARS;

		} elseif($sAction == 'openAccessDialog') {

			return $this->saveAccessDialog($aSelectedIds, $aData);

		} else {
		
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
			
		}

		return $aTransfer;

	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $aAction = array(), $bPrepareOpenDialog = true)
    {

		if($aAction['additional'] == 'settings') {

			$oConfig = \Factory::getInstance('Ext_TC_Config');
			foreach($aSaveData as $sColumn=>$mValue) {
				$oConfig->set($sColumn, $mValue);
			}
			$oConfig->save();

			$aSaveData = array();
			
			$bSave = false;
			
		}

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $aAction, $bPrepareOpenDialog);
		
		return $aTransfer;
		
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true)
    {

		if($sIconAction == 'allocate') {

			$sIconKey = self::getIconKey($sIconAction, $sAdditional);

			if(!$this->oWDBasic){
				$this->_getWDBasicObject($aSelectedIds);
			}

			$oDialog = $this->getAllocateDialog();

			$this->aIconData[$sIconKey]['dialog_data'] = $oDialog;

		} else if(
			$sIconAction == 'edit' &&
			$sAdditional == 'settings'
		) {
			$oDialog = $this->getSettingsDialog();

			$this->aIconData['edit_settings']['dialog_data'] = $oDialog;
		}

		
		
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		return $aData;

	}

	/**
	 * Get matrix data
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false)
    {

		if ($sIconAction === 'openAccessDialog') {
			return $this->getAccessDialogData($aSelectedIds);
		}

		return parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);

	}
	
	/**
	 * Methode, um den Dielaog der InnerGui zu manipulieren (Sets)
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2_Dialog_JoinedObjectContainer $oJoinContainer
	 */
	protected function _manipulateNumberrangeSetElements(Ext_Gui2_Dialog &$oDialog, Ext_Gui2_Dialog_JoinedObjectContainer &$oJoinContainer)
    {

	}
	
	/**
	 * Methode, um den Dielaog der InnerGui zu manipulieren (Zahlungsbelege)
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2_Dialog_Tab $oTab
	 * @param Ext_TC_NumberRange $oNumberrange
	 */
	protected function _manipulateNumberrangeDocumentElements(Ext_Gui2_Dialog &$oDialog, Ext_Gui2_Dialog_Tab &$oTab, $oNumberrange)
    {
		
	}
	
	/**
	 * Methode, um die InnerGui zu manipulieren
	 * @param Ext_TC_Gui2 $oInnerGui
	 */
	protected function _setInnerGuiAdditionalData(Ext_TC_Gui2 &$oInnerGui)
    {
		
	}

	public static function getOrderby()
    {

		return ['tc_nr.name'=>'ASC'];
	}

	public static function getDialog(Ext_TC_Gui2 &$oGui)
    {

		$aApplicationCategories = Ext_TC_Factory::executeStatic('Ext_TC_NumberRange_Gui2_Data', 'getApplicationCategories');
		$aApplicationCategories = Ext_TC_Util::addEmptyItem($aApplicationCategories);

		$oDialog	= $oGui->createDialog($oGui->t('Nummernkreis "{name}" editieren'), $oGui->t('Neuen Nummernkreis anlegen'));
		$oDialog->height = 700;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Name'),
				'input',
				array(
					'db_alias' => 'tc_nr',
					'db_column' => 'name',
					'required' => true
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Kategorie'),
				'select',
				array(
					'db_alias' => 'tc_nr',
					'db_column' => 'category',
					'required' => true,
					'select_options' => $aApplicationCategories
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Initialwert des Zählers'),
				'input',
				array(
					'db_alias' => 'tc_nr',
					'db_column' => 'offset_abs',
					'required' => true,
					'class' => 'reload_preview'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Startwert des Zählers'),
				'input',
				array(
					'db_alias' => 'tc_nr',
					'db_column' => 'offset_rel',
					'required' => true,
					'class' => 'reload_preview'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Mindestanzahl Stellen des Zählers'),
				'input',
				array(
					'db_alias' => 'tc_nr',
					'db_column' => 'digits',
					'required' => true,
					'format' => Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Int'),
					'class' => 'reload_preview'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Format der Nummer'),
				'input',
				array(
					'db_alias' => 'tc_nr',
					'db_column' => 'format',
					'required' => true,
					'class' => 'reload_preview'
				)
			)
		);

		$oNotification = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getDateFormatDescription', [$oDialog]);
		$oNotification->setElement($oGui->t('Beispiel: %Y%m%count => 200901001'));
		$oDialog->setElement($oNotification);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Vorschau'),
				'hidden',
				array(
					'no_savedata' => true,
					'row_id' => 'preview_container'
				)
			)
		);

		return $oDialog;
	}

	protected function getAccessMatrix(): \Ext_TC_Access_Matrix
	{
		return new Ext_TC_Numberrange_AccessMatrix();
	}
}
