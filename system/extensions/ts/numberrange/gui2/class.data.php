<?php

class Ext_TS_NumberRange_Gui2_Data extends Ext_TC_NumberRange_Gui2_Data {

	/**
	 * Dialog mit den Einstellungen
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	protected function _getSettingsDialogRows(&$oDialog) {

		parent::_getSettingsDialogRows($oDialog);
				
		$oRow = $oDialog->createRow($this->t('Kundennummer für Angebotskunden generieren?'), 'checkbox', array('db_column'=>'customernumber_enquiry'));
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Gruppennummer für Gruppenangebote generieren?'), 'checkbox', array('db_column'=>'groupnumber_enquiry'));
		$oDialog->setElement($oRow);

		$oNumberrangeSelect = new Ext_TC_Numberrange_Gui2_Selection_Numberranges('global');
        $oFakeWDBasic = null;
        $aNumberranges = $oNumberrangeSelect->getOptions(array(), array(), $oFakeWDBasic);

		$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Agenturen'), 'select', array(
            'db_column' => 'ts_agencies_numbers',
            'select_options' => $aNumberranges
        )));

		$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Firmen'), 'select', array(
			'db_column' => 'ts_companies_numbers',
			'select_options' => $aNumberranges
		)));

		$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Sponsoren'), 'select', array(
			'db_column' => 'ts_sponsors_numbers',
			'select_options' => $aNumberranges
		)));

		$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Unterkünfte'), 'select', array(
            'db_column' => 'ts_accommodations_numbers',
            'select_options' => $aNumberranges
        )));

		$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Unterkunftsdokumente'), 'select', array(
            'db_column' => 'ts_accommodations_documents_numbers',
            'select_options' => $aNumberranges
        )));

//		$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Bezahlvorgänge'), 'select', array(
//            'db_column' => 'ts_payment_groupings_numbers',
//            'select_options' => $aNumberranges
//        )));

		$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Unterkunftsbezahlungen'), 'select', array(
			'db_column' => 'ts_accommodations_payments_groupings_numbers',
			'select_options' => $aNumberranges
		)));

		if(Ext_Thebing_Access::hasRight('thebing_admin_numberranges_visa')) {
			$oDialog->setElement($oDialog->createRow($this->t('Nummernkreis für Visa-Dokumente'), 'select', array(
	            'db_column' => 'ts_visas_numbers',
	            'select_options' => $aNumberranges
	        )));
		}

	}
	
	/**
	 * Gibt die Standardanwendungen mit den Schulanwendungen zurück
	 * @return array
	 */
	public static function getApplications() {

		$aApplications = parent::getApplications();

		$aApplications['document']['creditnote'] = L10N::t('Agenturgutschrift', Ext_TC_NumberRange_Gui2_Data::$sL10NPath);
		$aApplications['document']['cancellation'] = L10N::t('Stornierung', Ext_TC_NumberRange_Gui2_Data::$sL10NPath);
		$aApplications['document']['enquiry'] = L10N::t('Anfragen', Ext_TC_NumberRange_Gui2_Data::$sL10NPath);
		$aApplications['document']['manual_creditnote'] = L10N::t('Manuelle Creditnotes', Ext_TC_NumberRange_Gui2_Data::$sL10NPath);
		$aApplications['document']['certificate'] = L10N::t('Zertifikate', Ext_TC_NumberRange_Gui2_Data::$sL10NPath);
		$aApplications['document']['proforma_creditnote'] = L10N::t('Agenturgutschrift (Proforma)', Ext_TC_NumberRange_Gui2_Data::$sL10NPath);

		// Angebote gibt es noch nicht
		unset($aApplications['document']['offer']);

		$aApplications['other']['customer_agency'] = L10N::t('Agenturkunde', self::$sL10NPath);
		$aApplications['other']['invoice_contact'] = L10N::t('Rechnungskontakt', self::$sL10NPath);
		$aApplications['other']['booking'] = L10N::t('Buchung', self::$sL10NPath);
		$aApplications['other']['group'] = L10N::t('Gruppen', self::$sL10NPath);

		return $aApplications;
	}

	/**
	 * Kategorien der Nummernkreise
	 * @return array
	 */
	public static function getApplicationCategories() {
				
		$aApplications = parent::getApplicationCategories();
		$aApplications['global'] = L10N::t('Global', self::$sL10NPath);
//		$aApplications['agency'] = L10N::t('Agenturen', self::$sL10NPath);
//		$aApplications['accommodation'] = L10N::t('Unterkünfte', self::$sL10NPath);
//		$aApplications['account'] = L10N::t('Konten', self::$sL10NPath);
//		$aApplications['payment_grouping'] = L10N::t('Bezahlvorgänge', self::$sL10NPath);
//
//		if(Ext_Thebing_Access::hasLicenceRight('thebing_admin_numberranges_visa')) {
//			$aApplications['visa'] = L10N::t('Visum', self::$sL10NPath);
//		}

		return $aApplications;
	}
	
	/**
	 * InnerGui mainpulieren
	 * @param Ext_TC_Gui2 $oInnerGui
	 */
	protected function _setInnerGuiAdditionalData(Ext_TC_Gui2 &$oInnerGui) {
		$oInnerGui->class_js = 'NumberrangeAllocation';
	}

	/**
	 * Dialog der InnerGui manipulieren (Sets)
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2_Dialog_JoinedObjectContainer $oJoinContainer
	 */
	protected function _manipulateNumberrangeSetElements(Ext_Gui2_Dialog &$oDialog, Ext_Gui2_Dialog_JoinedObjectContainer &$oJoinContainer) {
		
		$oGui = $oDialog->oGui;
		$oRow = $this->_createInboxMultiselect($oGui, $oJoinContainer);
		
		if($oRow) {
			$oJoinContainer->setElement($oRow);
		}
		
		$aOptions = array(
			'db_alias' => 'tc_nras',
			'db_column' => 'currencies',
			'multiple'=> 3, 
			'jquery_multiple'=> 1,
			'selection' => new Ts\Gui2\Selection\Numberrange\Currencies,
            'dependency' => [
                [
                    'db_alias' => 'tc_nra',
                    'db_column' => 'objects',
                ],
            ]
		);
		$oRow = $oJoinContainer->createRow($oGui->t('Währungen'), 'select', $aOptions);
		$oJoinContainer->setElement($oRow);
		
		$aOptions = array(
			'db_alias' => 'tc_nras',
			'db_column' => 'companies',
			'multiple'=> 3, 
			'jquery_multiple'=> 1,
			'selection' => new Ts\Gui2\Selection\Numberrange\Companies,
            'dependency' => [
                [
                    'db_alias' => 'tc_nra',
                    'db_column' => 'objects',
                ],
            ]
		);
		$oRow = $oJoinContainer->createRow($oGui->t('Firmen'), 'select', $aOptions);
		$oJoinContainer->setElement($oRow);
		
	}
	
	/**
	 * Dialog der InnerGui manipulieren (Zahlungsbelege)
	 * @param Ext_Gui2_Dialog_Tab $oTab
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_TC_NumberRange $oNumberrange
	 */
	protected function _manipulateNumberrangeDocumentElements(Ext_Gui2_Dialog &$oDialog, Ext_Gui2_Dialog_Tab &$oTab, $oNumberrange) {

		$sName = 'receipt_numberrange_inbox['.$oNumberrange->id.'][]';
		$oGui = $oDialog->oGui;
		
		$sSql = "
			SELECT
				`ts_nrari`.`inbox_id`
			FROM
				`ts_number_ranges_allocations_receipts_inboxes` `ts_nrari` JOIN
				`tc_number_ranges_allocations_receipts` `ts_nrar` ON
					`ts_nrar`.`id` = `ts_nrari`.`allocation_receipt_id` AND
					`ts_nrar`.`invoice_numberrange_id` = :invoice_numberrange_id
				
		";
		
		$aSql = array(
			'invoice_numberrange_id' => $oNumberrange->id
		);
		
		$aData = (array)DB::getQueryCol($sSql, $aSql);

		$oRow = $this->_createInboxMultiselect($oGui, $oDialog, $sName, $aData);
		
		if($oRow) {
			$oTab->setElement($oRow);
		}
	}	
	
	/**
	 * generiert das Multiselect mit den Inboxen als Auswahl
	 * @param type $oGui
	 * @param type $oElement
	 * @param type $sName
	 * @param type $aDefaultValue
	 * @return type
	 */
	protected function _createInboxMultiselect($oGui, $oElement, $sName = '', $aDefaultValue = array()) {
		
		$oRow = false;
		
		$oClientInbox = new Ext_Thebing_Client_Inbox();
		$aInboxes = $oClientInbox->getArrayList(true);
		
		if(!empty($aInboxes)) {
		
			$aOptions = array(
				'db_alias' => 'tc_nras',
				'db_column' => 'inboxes',
				'multiple'=> 5, 
				'jquery_multiple'=> 1,
				'required' => true,
				'select_options' => $aInboxes
			);
			
			if($sName != '') {
				$aOptions['name'] = $sName;
			}		
						
			if(!empty($aDefaultValue)) {
				$aOptions['default_value'] = $aDefaultValue;
			}
			
			$oRow = $oElement->createRow($oGui->t('Inboxen'), 'select', $aOptions);
		
		}
		
		return $oRow;
		
	}
	
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		global $_VARS;
		
		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		
		if($sAction == 'allocate') {
			
			$sSql = "TRUNCATE TABLE `ts_number_ranges_allocations_receipts_inboxes`";
			DB::executeQuery($sSql);

			foreach((array)$_VARS['receipt_numberrange_inbox'] as $iInvoiceNumberrangeId=>$aInboxes) {
				
				$sSql = "
					SELECT 
						`id`
					FROM 
						`tc_number_ranges_allocations_receipts`
					WHERE 
						`invoice_numberrange_id` = :numberrange_id
				";
				
				$aSql = array(
					'numberrange_id' => $iInvoiceNumberrangeId
				);
				
				$aData = (array)DB::getQueryCol($sSql, $aSql);
								
				foreach($aInboxes as $iInboxId) {
					$aInsert = array(
						'allocation_receipt_id' => (int)reset($aData),
						'inbox_id' => (int)$iInboxId
					);
					DB::insertData('ts_number_ranges_allocations_receipts_inboxes', $aInsert);
				}
			}
			
		}
		
		return $aTransfer;
	}
	
}
