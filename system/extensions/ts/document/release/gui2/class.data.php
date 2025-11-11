<?php

class Ext_TS_Document_Release_Gui2_Data extends Ext_Thebing_Document_Gui2 {
	use \TsAccounting\Traits\Gui2\TestExport;

	/**
	 * @return array
	 */
	public static function getListWhere(Ext_Gui2 $oGui = null) {

		$aWhere = array();
		$bIsAllSchools	= Ext_Thebing_System::isAllSchools();
		
		if(!$bIsAllSchools) {

			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$iSchoolId = (int)$oSchool->id;
			
			// manuelle Creditnotes sind nicht schulgebunden, deshalb wird hier "OR 0" mit abgefragt
			$aWhere['school_id'] = 'school_id:' . (string)$iSchoolId;

		}
		
		$aDocTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnotes_and_without_proforma');
		$aDocTypes[] = 'creditnote_cancellation';

		$sColumn = 'type_status';
		$aWhere[$sColumn] = array('IN', $aDocTypes);

		// Keine Entwürfe anzeigen
		$aWhere['draft'] = 'draft:0';

		/*
		 * @todo Testen! Klappt das mit dem OR _missing_ so noch?
		 */
		$oClient = Ext_Thebing_System::getClient();
		
		if($oClient->checkUsingOfInboxes()) {

			$aInboxList = $oClient->getInboxList(true, true);

			$aInboxQuery = array();
			foreach($aInboxList as $sInbox => $sTemp) {
				$aInboxQuery[] = 'inbox:' . (string)$sInbox . ' OR _missing_:inbox';
			}
			$aWhere['inbox'] = array('IN', $aInboxQuery);

		}

		return $aWhere;
	}
	
	/**
	 * Freigabefilter Optionen
	 * 
	 * @param Ext_Gui2 $oGui
	 * @return array 
	 */
	public static function getIsReleasedFilterOptions(Ext_Gui2 $oGui) {

		return array(
			'xNullx'		=> $oGui->t('Alle'),
			'released'		=> $oGui->t('Freigegeben'),
			'not_released'	=> $oGui->t('Nicht freigegeben'),
		);

	}
	
	/**
	 * Dokumentenfilter Optionen
	 * 
	 * @return array 
	 */
	public static function getTypeFilterOptions() {

		$aDocTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData(['invoice_with_creditnote_and_manual_creditnote', 'offer']);
		$aDocTypes = array_combine($aDocTypes, $aDocTypes);
		
		unset(
			$aDocTypes['group_proforma'],
			$aDocTypes['group_proforma_netto'],
			$aDocTypes['credit_brutto'],
			$aDocTypes['credit_netto']
		);
		
		$aDocTypes['creditnote_cancellation'] = 'creditnote_cancellation';
		$aDocTypes['offer_converted'] = 'offer_converted';
		$aDocTypes['proforma_converted'] = 'proforma_converted';

		$oFormat = new Ext_TS_Document_Release_Gui2_Format_DocType();

		$aOptions = array();
		foreach($aDocTypes as $sDocType) {
			$aOptions[$sDocType] = $oFormat->format($sDocType);
		}

		asort($aOptions, SORT_LOCALE_STRING);
		
		return $aOptions;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
    public static function getPrintedFilterOptions(Ext_Gui2 $oGui) {

        $aOptions = array(
            '' => $oGui->t('Alle Dokumente'),
            'not_printed'=>	$oGui->t('nicht gedruckte Dokumente'),
            'printed' => $oGui->t('gedruckte Dokumente'),
            'succesfull_printed' =>	$oGui->t('erfolgreich gedruckte Dokumente'),
            'not_successfull_printed' => $oGui->t('nicht erfolgreich gedruckte Dokumente')
        );

        return $aOptions;
    }
	
	/**
	 * Buchungstyp Filter Optionen
	 * 
	 * @param Ext_Gui2 $oGui
	 * @param bool $bUseNumericKeys
	 * @param bool $bUsesNummericKeys
	 * @return array 
	 */
	public static function getBookingTypes(Ext_Gui2 $oGui, $bUseNumericKeys = false) {

		if (!$bUseNumericKeys) {
		$aBookingType = array(
			'customer' => $oGui->t('Direktbuchungen'),
			'agency' => $oGui->t('Agenturbuchungen'),
		);
		} else {
			$aBookingType = [
				1 => $oGui->t('Direktbuchungen'),
				2 => $oGui->t('Agenturbuchungen'),
			];
		}
		
		return $aBookingType;
	}

	/**
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 */
	public function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false) {

		if($sIconAction == 'release') {
			$oDialogData = $this->_getReleaseDialog();
		} else {
			\System::wd()->executeHook('ts_document_release_dialog_html', $this->_oGui, $sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
		}

		$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
		
		return $aData;
	}

	/**
	 * @return Ext_Gui2_Dialog
	 * @throws Exception
	 */
	protected function _getReleaseDialog() {

		$sTitleDialog = $this->t('Dokumente freigeben');
		
		$oDialog = $this->_oGui->createDialog($sTitleDialog, $sTitleDialog, $sTitleDialog);
		$oDialog->width	= 900;
		$oDialog->height = 650;
		
		$oTab = $oDialog->createTab($this->t('Freigabe'));
		
		$oFactory = new Ext_Gui2_Factory('ts_document_release_dialog');
		$oGuiChild = $oFactory->createGui('dialog', $this->_oGui);

		$oTab->setElement($oGuiChild);
		
		$oDialog->setElement($oTab);

		return $oDialog;
	}

	protected function _getEInvoiceHistoryDialog($bFinal = false) {
		
		$sTitleDialog = $this->t('E-Invoice Historie');
		
		$oDialog = $this->_oGui->createDialog($sTitleDialog, $sTitleDialog, $sTitleDialog);
		$oDialog->width	= 900;
		$oDialog->height = 650;
		
		return $oDialog;
	}
	
	/**
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param bool $sAdditional
	 * @param bool $bSave
	 * @return array|mixed
	 */
	public function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		global $_VARS;

		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		if($sAction == 'release') {
			$this->_saveReleaseDialog($aTransfer, $_VARS);
		} else {
			\System::wd()->executeHook('ts_document_release_dialog_save', $this->_oGui, $aTransfer, $_VARS, $sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}
		
		return $aTransfer;
	}

	protected function _saveReleaseDialog(&$aTransfer, $_VARS) {

		$aIgnoreErrorCodes = (array)$_VARS['ignore_errors_codes'] ?? [];

		$aErrors = array();
		$bError = false;
		$aDocumentIds = (array)$_VARS['document_ids'];

		if(!empty($aDocumentIds)) {

			/* Alle Dokumente durchlaufen und falls es ein
			 * Gruppen-Dokument ist, müssen alle Dokumente der Gruppe
			 * gemerkt werden, da alle Dokumente einer Gruppe freigegeben
			 * werden müssen. */

			$aDocumentNumbers = array();

			foreach($aDocumentIds as $iDocumentId) {

				$this->_getWDBasicObject($iDocumentId);
				$oInquiry = $this->oWDBasic->getInquiry();

				if(
					$oInquiry instanceof Ext_TS_Inquiry_Abstract &&
					$oInquiry->hasGroup()
				) {

					/** @var Ext_Thebing_Inquiry_Document[] $aGroupDocuments */
					$aGroupDocuments = $this->oWDBasic->getDocumentsOfSameNumber();

					foreach($aGroupDocuments as $oGroupDocument) {
						if(!in_array($oGroupDocument->getId(), $aDocumentIds)) {
							$aDocumentIds[] = $oGroupDocument->getId();
						}
					}

					if(
						!in_array('group_documents', $aIgnoreErrorCodes) &&
						!isset($aDocumentNumbers[$this->oWDBasic->document_number])
					) {
						$aErrors[] = [
							'message' => $this->_getErrorMessage('GROUP_DOCUMENTS', ''),
							'type' => 'hint',
							'code' => 'group_documents'
						];
					}

				}

			}

			/* Sofern bisher ein Fehler aufgetreten ist, dürfen
			 * keine Dokumente verarbeitet werden. Es sollen die Fehler
			 * direkt angezeigt werden. */

			if(!empty($aErrors)) {
				$aDocumentIds = array();
			}

			DB::begin('create_booking_stack');

			foreach($aDocumentIds as $iDocumentId) {

				$this->_getWDBasicObject($iDocumentId);

				try {

					$mReturn = $this->oWDBasic->releaseDocument($aIgnoreErrorCodes, $aDocumentIds);

				} catch (Ext_TS_Accounting_Bookingstack_Generator_Exception $exc) {

					if ($exc->isWarning()) {
						$aErrors[] = ['message' => $exc->getMessage(), 'type' => 'hint', 'code' => $exc->getKey()];
						$bError = false;
					} else {
						$aErrors[] = ['message' => $exc->getMessage(), 'type' => 'error'];
						$bError = true;

						__pout($exc->getOptionalData());
					}

					$mReturn = false;
				}

				if($mReturn !== true && !$mReturn instanceof WDBasic) {

					if($this->oWDBasic->hasError()) {

						$aErrors[] = $this->_getErrorMessage($this->oWDBasic->getError(), '');
						$bError = true;

					} elseif($this->oWDBasic->hasHint()) {

						$aWDBasicError = [
							'message' => $this->_getErrorMessage($this->oWDBasic->getHint(), ''),
							'type' => 'hint',
						];

						if (!empty($sCode = $this->oWDBasic->getHintCode())) {
							$aWDBasicError['code'] = $sCode;
						}

						$aErrors[] = $aWDBasicError;

					}

				}

			}

			if(!empty($aErrors)) {
				DB::rollback('create_booking_stack');
			} else {
				DB::commit('create_booking_stack');
			}

		} else {
			$aErrors[] = $this->_getErrorMessage('NO_DOCUMENTS', '');
			$bError = true;
		}

		if(!isset($aTransfer['data'])) {
			$aTransfer['data'] = array();
		}

		$aTransfer['data']['id'] = $_VARS['dialog_id'];
		$aTransfer['action'] = 'saveDialogCallback';
		$aTransfer['data']['show_skip_errors_checkbox'] = 1;

		if(empty($aErrors)) {

			if(!isset($aTransfer['data']['options'])) {
				$aTransfer['data']['options'] = array();
			}
			$aTransfer['data']['options']['close_after_save'] = true;

		} else if($bError) {
			array_unshift($aErrors, L10N::t('Fehler beim Speichern'));
		}

		$aTransfer['error'] = $aErrors;
	}

	/**
	 * @param string $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @param null $sAction
	 * @param null $sAdditional
	 * @return string
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		if($sError === 'DOCUMENT_RELEASED') {

			$sErrorMessage = $this->t('Dokument "%s" wurde bereits freigegeben!');
			$sLabel	= $this->oWDBasic->document_number;
			$sErrorMessage = sprintf($sErrorMessage, $sLabel);

		} elseif($sError === 'NO_DOCUMENTS') {

			$sErrorMessage = $this->t('Keine Dokumente ausgewählt!');

		} elseif($sError === 'PARENT_NOT_RELEASED') {

			$sErrorMessage = $this->t('Dokument "%s" wurde noch nicht freigegeben. Wollen Sie trotzdem das Dokument "%s" freigeben?');
			$oParent = $this->oWDBasic->getParentDocument();
			$sDocNumber	= $this->oWDBasic->document_number;

			$sDocNumberParent = '';
			if($oParent) {
				$sDocNumberParent = $oParent->document_number;
			}
			
			$sErrorMessage = sprintf($sErrorMessage, $sDocNumberParent, $sDocNumber);

		} elseif($sError === 'GROUP_DOCUMENTS') {

			$sErrorMessage = $this->t('Die Rechnung "%s" ist eine Gruppenrechnung. Bei der Freigabe einer Gruppenrechnung werden alle Positionen der Rechnung aller Gruppenmitglieder automatisch mit freigegeben. Wollen Sie das Dokument "%s" trotzdem freigeben?');
			$sDocNumber = $this->oWDBasic->document_number;

			$sErrorMessage = sprintf($sErrorMessage, $sDocNumber, $sDocNumber);

		} else {

			$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);

		}
		
		return $sErrorMessage;
	}

	/**
	 * @param array $_VARS
	 * @throws Exception
	 */
	public function switchAjaxRequest($_VARS){

		if($_VARS['task'] == 'loadTable') {

			$aTransfer = $this->_switchAjaxRequest($_VARS);
			
			if($this->_isReleaseList()) {

				$aBody = (array)$aTransfer['data']['body'];

				foreach($aBody as $iKey => $aBodyData) {
					$aTransfer['data']['body'][$iKey]['multiple_checkbox_id'] = 'multiple_' . $aBodyData['id'];
				}

			}
			
			echo json_encode($aTransfer);

		} else {
			parent::switchAjaxRequest($_VARS);
		}
		
	}

	/**
	 * @return bool
	 */
	protected function _isReleaseList() {
		return $this->_oGui->hash === '71a0e39a7b4b7313af05a6499ca2b6f5';
	}

}
