<?php

/**
 * Class Ext_Thebing_Accounting_Gui2_Overview
 */
class Ext_Thebing_Accounting_Gui2_Overview extends Ext_Thebing_Document_Gui2 {

	protected $_sDialogIDTag = 'PRINT_INVOICE_';
	
	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false){

		switch($sIconAction){
			case 'print_invoice':
					$oDialogData = $this->getPrintDialog($aSelectedIds);
					
					$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);
				
					break;
			default:
				$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
		}
	

		return $aData;
	}
	
	
	public function getPrintDialog($aSelectedIds){

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$oPdfMerge = $this->_getPdfMergeObject();
		
        $aDocuments = (array)$aSelectedIds;
        
        foreach($aDocuments as $iDocument){
            $oDocument      = Ext_Thebing_Inquiry_Document::getInstance($iDocument);
            $oVersion       = $oDocument->getLastVersion();
            if(
				$oVersion->id > 0
			) {
				$oPdfMerge->addPdf('storage/' . $oVersion->path);
			}
        }

		$sFilePart = '/temp/';
		$sFileData = 'invoice_overview_'.md5(join('_', $aSelectedIds)).'.pdf';
		
        $sDir           = $oSchool->getSchoolFileDir() . $sFilePart;
        Util::checkDir($sDir);
		$sFile			= $oSchool->getSchoolFileDir() . $sFilePart . $sFileData;
		$sFileDisplay	= $oSchool->getSchoolFileDir(false, true) . $sFilePart . $sFileData;
	
		$oPdfMerge->save($sFile);	
		                          

		$oDialog = $this->_oGui->createDialog($this->t('Rechnungen drucken'), $this->t('Rechnungen drucken'), $this->t('Rechnungen drucken'));
		                          
		$oDialog->width			= 800;
		$oDialog->height		= 700;
		$oDialog->bBigLabels	= true;
		$oDialog->sDialogIDTag = $this->_sDialogIDTag;
                     
		$oTab1 = $oDialog->createTab($this->_oGui->t('Rechnungen'));
		$oTab1->no_padding	 = 1; 
		$oTab1->no_scrolling = 1; 
		                          
	                              
		$oTab1->setElement($this->getEmbeddedIframe($sFileDisplay, $oDialog->height - 130));   //PDF-Document einbetten
		                          
		$oTab2 = $oDialog->createTab($this->_oGui->t('Erfolgreich gedruckt?'));

		$oGuiInner					= $this->_getInnerPrintDialog();

		$oTab2->setElement($oGuiInner);
		                          
		$oDialog->setElement($oTab1);
		$oDialog->setElement($oTab2);
		                          
		return $oDialog;          
	}                             
	                              
	private function getEmbeddedIframe($sUrl, $iHeight){
		                          
		$sIframe = '<iframe src="'.$sUrl.'"  width="100%" height="' . $iHeight . 'px" frameborder="0"></iframe>';
		return $sIframe;          
	}                             
	                              
	/**
	 * Speichert ob eine Rechnung erfolgreich, oder nicht gedruckt wurde
	 * 
	 * @global type $user_data
	 * @global type $_VARS
	 * @param type $aSaveData 
	 */
	public function savePrintedInvoices($aSaveData){
		
		global $user_data, $_VARS;

		$aSelectedIds	= (array)$_VARS['id'];
		$iUserId		= (int)$user_data['id'];
		$aSaveData		= (array)$aSaveData;

		foreach($aSelectedIds as $iSelectedId)
		{
			$oDocument  = Ext_Thebing_Inquiry_Document::getInstance($iSelectedId);
            $oVersion   = $oDocument->getLastVersion();
            
			if(
				$oVersion->id <= 0
			)
			{
				continue;
			}
            
            if(in_array($iSelectedId, (array)$_VARS['document_ids'])){
                $bSuccess = true;
            } else {
                $bSuccess = false;
            }
            
			$oVersion->savePrintstatus($iUserId, $bSuccess);

            Ext_Gui2_Index_Stack::add('ts_document', $oVersion->document_id, 0);
		}
	}                             
	                              
	public function switchAjaxRequest($_VARS){
                                  
		if(                       
			$_VARS['task'] == 'saveDialog' &&
			$_VARS['action'] == 'print_invoice'
		){                        
			$this->savePrintedInvoices($_VARS['save']);
			
            sort($_VARS['id']);
            
			$aTransfer = array(); 
			$aTransfer['data']['id']	= $this->_sDialogIDTag .  implode('_', $_VARS['id']);
            $aTransfer['data']['hash']          = 'e15b20f36e95f15cd137f4300cee6b2b';
			$aTransfer['action']		= 'closeDialogAndReloadTable';
			$aTransfer['dialog_id_tag']	= $this->_sDialogIDTag;
			$aTransfer['error']			= array();
			                      
			echo json_encode($aTransfer);
		} else if($_VARS['action'] == 'extended_export'){
            $this->createExtendedExport();
            die();
        }else{                    
			parent::switchAjaxRequest($_VARS);
		}                         
	}

	public function requestAsUrlZipExport($aVars) {

		ini_set('memory_limit', '8G');
		set_time_limit(600);

		$oNow = new \DateTime;
		
		$sZipFileName = 'document_export_'.$oNow->format('YmdHisv').'.zip';
		
		$sZipFile = Util::getDocumentRoot().'storage/tmp/'.$sZipFileName;

		$oZip = new \ZipArchive();
		$oZip->open($sZipFile, \ZIPARCHIVE::CREATE);

		foreach($aVars['id'] as $iDocumentId) {

			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
			$oVersion = $oDocument->getLastVersion();
			
			$sFullPath = $oVersion->getPath(true);
			
			$dDate = new \Carbon\Carbon($oVersion->date);

			if(!file_exists($sFullPath)) {
				continue;
			}

			$aFile = pathinfo($sFullPath);

			if(!empty($aFile['basename'])) {
				$oZip->addFile($sFullPath, $dDate->format('Y').'/'.$dDate->format('m').'/'.$aFile['basename']);
			}

		}
		
		$oZip->close();

		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=".$sZipFileName);
		header("Content-Length: " . filesize($sZipFile));

		readfile($sZipFile);
		
		unlink($sZipFile);
		die();
	
	}
	
	public function createExtendedExport() {
		global $_VARS;

		$sCharset = $this->_oGui->getDataObject()->getCharsetForExport();
		$sSeparator = $this->_oGui->getDataObject()->getSeparatorForExport();
		$oExport = new Gui2\Service\Export\Csv('Export');
		$oExport->setCharset($sCharset);
		$oExport->setSeperator($sSeparator);
		$oExport->sendHeader();

		$aLine = array(
			L10N::t('Rechnungsnummer'),
			L10N::t('Dokumentdatum'),
			L10N::t('Kundennummer'),
			L10N::t('Name'),
			L10N::t('Agentur'),
			L10N::t('Position'),
			L10N::t('Leistungszeitraum'),
			L10N::t('Betrag'),
			L10N::t('Währung')
		);
		$oExport->sendLine($aLine);

		$aLine = array_fill(0, 9, '');
		$oExport->sendLine($aLine);

		$oFormat = new Ext_Thebing_Gui2_Format_Date();
		$oFormatDoc = new Ext_TS_Document_Release_Gui2_Format_DocNumber('document_number');
		$oDummy = null;

		// Immer aufsteigend sortieren
		sort($_VARS['id']);

		foreach($_VARS['id'] as $iDocument) {

			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocument);
			$oVersion = $oDocument->getLastVersion();
			$aItems = $oVersion->getItemObjects(true);
			$oInquiry = $oDocument->getInquiry();
			$oCustomer = $oInquiry->getFirstTraveller();
			$oAgency = $oInquiry->getAgency();
			$oCurrency = $oDocument->getCurrency();
			$bProvision = false;
			$bCreditnote = false;
			$dCommissionFrom = $dCommissionUntil = null;

			$aResultData = array('id' => $iDocument);

			$sSystemLanguage = System::getInterfaceLanguage();
			$sDocumentNumber = $oFormatDoc->format($oDocument->document_number, $oDummy, $aResultData);
			$sDocumentDate = $oVersion->date;
			$sDocDateFormat = $oFormat->formatByValue($sDocumentDate);
			$sCustomernumber = $oCustomer->getCustomerNumber();
			$sName = $oCustomer->getName();
			$sAgency = '';

			if($oAgency) {
				$sAgency = $oAgency->getName();
			}

			$fTotal = 0;
			$fTotalProvision = 0;
			$sCurrency = $oCurrency->getSign();

			if(
				strpos($oDocument->type, 'netto') !== false ||
				strpos($oDocument->type, 'storno') !== false
			) {
				$bProvision = true;
			}

			if(strpos($oDocument->type, 'creditnote') !== false) {
				$bCreditnote = true;
			}

			foreach($aItems as $oItem) {

				$sPosition = $oItem->description;
				$sTime = $oFormat->formatByValue($oItem->index_from) . ' - ' . $oFormat->formatByValue($oItem->index_until);

				// Bei Creditnotes werden die Provisionsbeträge direkt angezeigt
				if($oDocument->type === 'creditnote') {
					$fAmount = $oItem->amount_provision * -1;
				} else {
					$fAmount = $oItem->amount;
				}

				$fTotal += $fAmount;
				$sAmount = Ext_Thebing_Format::Number($fAmount);

				if($bProvision) {
					$fTotalProvision += $oItem->amount_provision;

					// Leistungszeitraum aller Items mit Provision
					if(
						abs($oItem->amount_provision) > 0 &&
						\Core\Helper\DateTime::isDate($oItem->index_from, 'Y-m-d') &&
						\Core\Helper\DateTime::isDate($oItem->index_until, 'Y-m-d')
					) {
						$dCommissionFrom = min($dCommissionFrom ?: $oItem->getFrom(), $oItem->getFrom());
						$dCommissionUntil = max($dCommissionUntil ?: $oItem->getUntil(), $oItem->getUntil());
					}
				}

				$aLine = array(
					$sDocumentNumber,
					$sDocDateFormat,
					$sCustomernumber,
					$sName,
					$sAgency,
					$sPosition,
					$sTime,
					$sAmount,
					$sCurrency
				);
				$oExport->sendLine($aLine);

				// reseten da nur in erster Zeile angezeigt wird
				$sDocumentNumber = '';
				$sDocDateFormat = '';
				$sCustomernumber = '';
				$sName = '';
				$sAgency = '';

				if($oItem->amount_discount != 0) {

					// amount_discount ist nur der Prozentwert!
					$fAmountDiscount = $oItem->amount * ($oItem->amount_discount / 100) * -1;

					// Provision ausrechnen
					if($bProvision || $bCreditnote) {
						$fProvsionInPercent = $oItem->amount_provision / $oItem->amount * 100;
						$fAmountCommission = $fAmountDiscount * ($fProvsionInPercent / 100);
					}

					// Bei Gutschriften (an Agentur) muss die Provision als Wert dargestellt werden
					if($bCreditnote) {
						$fAmountDiscount = $fAmountCommission * -1;
					}

					// Bei Netto muss die gesamte Provision für den Rabatt zusammen gerechnet werden
					if($bProvision) {
						$fTotalProvision += $fAmountCommission;
					}

					$sAmount = Ext_Thebing_Format::Number($fAmountDiscount);
					$fTotal += $fAmountDiscount;
					$aLine = array(
						$sDocumentNumber,
						$sDocDateFormat,
						$sCustomernumber,
						$sName,
						$sAgency,
						$oItem->description_discount,
						$sTime,
						$sAmount,
						$sCurrency
					);
					$oExport->sendLine($aLine);
				}

			}

			// Bei Nettorechnungen wird die Provision gesamt in einer Zeile ausgegeben
			if($bProvision) {

				$sCommissionPeriod = '';
				if($dCommissionFrom && $dCommissionUntil) {
					$sCommissionPeriod = $oFormat->formatByValue($dCommissionFrom) . ' - ' . $oFormat->formatByValue($dCommissionUntil);
				}

				$aLine = array(
					'',
					'',
					'',
					'',
					'',
					L10N::t('Agenturprovision'),
					$sCommissionPeriod,
					Ext_Thebing_Format::Number($fTotalProvision * -1),
					$sCurrency
				);
				$oExport->sendLine($aLine);

			}

			// Wichtig: Provision vom Gesamtbetrag abziehen
			$fTotal -= $fTotalProvision;

			$sTotal = Ext_Thebing_Format::Number($fTotal);

			$aComments = array();

			// Bei Agenturen an die Gutschrift immer »Provision« vorhängen
			if($oDocument->type === 'creditnote') {
				$aComments[] = Ext_TC_Placeholder_Abstract::translateFrontend('Provision', $sSystemLanguage);
			}

			$sVersionComment = $oVersion->comment;
			if(!empty($sVersionComment)) {
				$aComments[] = $sVersionComment;
			}

			$aLine = array(
				L10N::t('Rechnungskommentar') . ':',
				'',
				'',
				'',
				'',
				join(' - ', $aComments),
				'',
				$sTotal,
				$sCurrency
			);
			$oExport->sendLine($aLine);

			$aLine = array_fill(0, 9, '');
			$oExport->sendLine($aLine);

		}

		$oExport->end();

	}

	protected function _getInnerPrintDialog()
	{

        $oFatory            = new Ext_Gui2_Factory('ts_document');
        $oGuiInner          = $oFatory->createGui('print_dialog_inner', $this->_oGui);
        $oGuiInner->foreign_key = 'id';
        $oGuiInner->hash = md5($oGuiInner->hash);
		return $oGuiInner;
	}
    
    public static function getProformaFilterOptions() {

		$aList = array(
			0 => L10N::t('Nur Proforma', Ext_Thebing_Document::$sL10NDescription),
			1 => L10N::t('Nur Rechnungen', Ext_Thebing_Document::$sL10NDescription)
		);

		return $aList;

	}

	/**
	 * @return array
	 */
	public static function getCancellationFilterOptions() {

		return [
			0 => L10N::t('Nicht storniert', Ext_Thebing_Document::$sL10NDescription),
			1 => L10N::t('Storniert', Ext_Thebing_Document::$sL10NDescription)
		];

	}
    
    public static function getPrintedFilterOptions()
	{
		$aList = array(
          'not_printed' => L10N::t('Nicht gedruckte Dokumente', Ext_Thebing_Document::$sL10NDescription),
          'printed' => L10N::t('Gedruckte Dokumente', Ext_Thebing_Document::$sL10NDescription) ,
          'success_printed' => L10N::t('Erfolgreich gedruckte Dokumente', Ext_Thebing_Document::$sL10NDescription) ,
          'not_success_printed' => L10N::t('Nicht erfolgreich gedruckte Dokumente', Ext_Thebing_Document::$sL10NDescription)
        );

        $aList = Ext_TC_Util::addEmptyItem($aList);
        
		return $aList;
	}
    
	public static function getPrintedFilterOptionQuery($sFilter) {

		$oBool = new \Elastica\Query\BoolQuery();

		if($sFilter == 'not_printed') {
			$oBool->addMustNot(new \Elastica\Query\Exists('printed'));
		} else if($sFilter == 'printed') {
			$oBool->addMust(new \Elastica\Query\Exists('printed'));
		} else if($sFilter == 'success_printed') {
			$oBool->addMust(new \Elastica\Query\Exists('print_success'));
		} else if($sFilter == 'not_success_printed') {
			$oBool->addMust(new \Elastica\Query\Exists('printed'));
			$oBool->addMustNot(new \Elastica\Query\Exists('print_success'));
		}

		return $oBool;

	}

	public static function buildAutomaticDocumentQuery(string $sFilter) {

		$oTerm = new \Elastica\Query\Term();
		$oTerm->setTerm('creator_id_original', 0);

		$oBool = new \Elastica\Query\BoolQuery();
		$sFilter === 'yes' ? $oBool->addMust($oTerm) : $oBool->addMustNot($oTerm);

		return $oBool;

	}

}
