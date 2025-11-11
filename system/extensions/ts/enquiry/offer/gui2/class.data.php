<?php

// TODO 16002 Entfernen
class Ext_TS_Enquiry_Offer_Gui2_Data extends Ext_Thebing_Document_Gui2 {
	
	public function prepareColumnListByRef(&$aColumnList)
	{

		$oEnquiry = $this->_getParentWDBasic();

		if(is_object($oEnquiry)){

			if($oEnquiry->is_group == 1) {

				//Default-Columns zwischenspeichern
				$iColumns = count($aColumnList) -1;
				$aDefaultColumns = array();
				for($i = $iColumns; $i > ($iColumns - 4); $i--) {
					$aDefaultColumns[] = $aColumnList[$i];
					unset($aColumnList[$i]);
				}			

				$oColumn = $this->_oGui->createColumn();
				$oColumn->db_column = 'group_members';
				$oColumn->db_alias = '';
				$oColumn->select_column = 'group_members';
				$oColumn->title = $this->_oGui->t('Mitglieder');
				$oColumn->width = Ext_TC_Util::getTableColumnWidth('count');
				$oColumn->width_resize = false;
				$oColumn->format = new Ext_TS_Enquiry_Offer_Gui2_Format_Groupdetails('members');
				$oColumn->sortable = false;
				$aColumnList[] = $oColumn;

				$oColumn = $this->_oGui->createColumn();
				$oColumn->db_column = 'guides';
				$oColumn->db_alias = '';
				$oColumn->select_column = 'guides';
				$oColumn->title = $this->_oGui->t('Guides');
				$oColumn->width = Ext_TC_Util::getTableColumnWidth('count');
				$oColumn->width_resize = false;
				$oColumn->format = new Ext_TS_Enquiry_Offer_Gui2_Format_Groupdetails('guides');
				$oColumn->sortable = false;
				$aColumnList[] = $oColumn;

				$aColumnList = array_merge($aColumnList, $aDefaultColumns);

			}
		}
		
		
		parent::prepareColumnListByRef($aColumnList);
		
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional = false) {

		if($sIconAction == 'document_edit') {
			if(!($oDialog instanceof Ext_Gui2_Dialog)) {
				$sIconKey = self::getIconKey($sIconAction, $sAdditional);
				$oDialog = $this->_getDialog($sIconKey);
			}
			return $oDialog->getDataObject()->getHtml($sIconAction, $aSelectedIds, $sAdditional);
		}

		return parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);

	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		if($sIconAction == 'document_edit')
		{
			$iSelectedId	= $this->_getFirstSelectedId();
			
			if($this->oWDBasic === null)
			{
				$this->_getWDBasicObject($iSelectedId);
			}
			
			$oEnquiryOffer	= $this->oWDBasic;

			$oEnquiry		= $oEnquiryOffer->getEnquiry();
			$oEnquiry->setOffer($oEnquiryOffer);
				
			$oOfferDocument		= $oEnquiryOffer->getOfferDocument();
			$iOfferDocumentId	= (int)$oOfferDocument->id;

			$oDocument	= new Ext_Thebing_Document();
			$oDocument->setInquiry($oEnquiry);
			$oDialog	= $oDocument->getEditDialog($this->_oGui, $iOfferDocumentId, 'offer', $aSelectedIds);
			
			$this->aIconData[$sIconAction]['dialog_data'] = $oDialog;
		}
		
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($sIconAction == 'document_edit')
		{
			$oInquiryDocument = new Ext_Thebing_Inquiry_Document();

			$aData = $oDocument->getAdditionalDataForDialog($oInquiryDocument, $aData);	
		}

		return $aData;
	}

	public function switchAjaxRequest($_VARS){
		
		if(
			$_VARS['action'] == 'convert_offer_to_inquiry' &&
			$_VARS['task'] != 'openDialog'
		) {

			$bValidTransaction = true;

			// @TOOD Funktioniert nicht, wenn Fehler auftritt
			// Wenn Dialog ID und TAN übergeben werden, wird TAN überprüft
			/*if(
				isset($_VARS['dialog_id']) &&
				isset($_VARS['token'])
			) {
				// Token überprüfen
				$bValidTransaction = $this->_checkDialogToken($_VARS['dialog_id'], $_VARS['token']);
			}*/

			if($bValidTransaction === true) {

				$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($_VARS['save']['numberrange_id']);
				if(!$oNumberrange->exist()) {
					throw new RuntimeException('Numberrange doesn\'t exist!');
				}

				// Nummernkreis über ganzen Prozess sperren
				$bUnlockNumberrange = true;
				if(!$oNumberrange->acquireLock()) {
					$bUnlockNumberrange = false;
				}

				$oRollback = function() {
					DB::rollback('convert_offer_to_inquiry');

					// Kompletten Stack löschen, sonst werden bei einem Rollback trotzdem Buchung und Proforma im Index generiert #9819
					Ext_Gui2_Index_Stack::clearStack();
				};

				try {

					DB::begin('convert_offer_to_inquiry');

					// Aufruf für das Umwandeln (es wird an mehreren Stellen benötigt) daher soll Redundanz vermieden werden
					if($bUnlockNumberrange) {
						$aErrorData = $this->convertToInquiry($_VARS, $oNumberrange);
					} else {
						$aErrorData[] = 'NUMBERRANGE_LOCKED';
					}

					$aTransfer = array();
					// Rückgabe prüfen
					if(!empty($aErrorData)){
						$aTransfer['success'] = 0;

						array_unshift($aErrorData, 'ENQUIRY_CONVERT_ERROR');

						$aError = array();
						foreach($aErrorData as $iKey => $sError){
							$aError[$iKey] =  $this->_getErrorMessage($sError, '');
						}
						$sAction = 'saveDialogCallback';

						$oRollback();
					} else {
						#$aTransfer['success'] = 1;
						#$aTransfer['success_message'] = $this->t('Die Anfrage wurde erfolgreich umgewandelt.');
						$aError = array();

						// Fenster schließen
						$sAction = 'closeDialogAndReloadTable';

						DB::commit('convert_offer_to_inquiry');
					}

				} catch(Exception $e) {
					$aTransfer['success'] = 0;
					$sAction = 'saveDialogCallback';

					$aError = array(
						$this->_oGui->t('Fehler beim Speichern'),
						$this->_oGui->t('Folgender Fehler ist aufgetreten:').' '.$e->getMessage()
					);

					if(System::d('debugmode') > 0) {
						$aError[1] .= '<br>'.$e->getFile().'::'.$e->getLine();
					}

					$oRollback();
				}

				if($bUnlockNumberrange) {
					$oNumberrange->removeLock();
				}

				$aParentGui = $this->_oGui->getConfig('parent_gui');

				$aTransfer['action'] = $sAction;
				$aTransfer['data']['id'] = 'CONVERT_' . reset($_VARS['id']);
				#$aTransfer['save_row'] = (int) $_VARS['save_row'];
				$aTransfer['data']['options']['close_after_save'] = false;
				// Damit auch die Parent GUI nach geladen wird
				$aTransfer['parent_gui'][0]['hash'] = $aParentGui[0];
				$aTransfer['parent_gui'][0]['class_js'] = 'Enquiry';
				$aTransfer['error'] = $aError;	

			} else {

				$aTransfer = array();
				$aTransfer['error']			= array(L10N::t('Der Dialog wurde bereits gespeichert.', $this->_oGui->gui_description));

			}

			echo json_encode($aTransfer);
			$this->_oGui->save();

		}else{
			parent::switchAjaxRequest($_VARS);
		}
	}

	protected function _decodeSelectedIdsForDocument(array $aSelectedIds, $bGetAllData=false) {
		return $aSelectedIds;
	}

	/**
	 * Liefert das Nummernformat
	 * @global array $_VARS
	 * @return array
	 */
	public function getNumberFormat() {
		global $_VARS;

		// In der All-schools Ansicht muss das Nummernformat der Schule des Angebotes genommen werden #5902
		if(Ext_Thebing_System::isAllSchools()) {
			$iWDBasic = reset($_VARS['id']);

			$oOffer		= Ext_TS_Enquiry_Offer::getInstance($iWDBasic);
			$oSchool		= $oOffer->getSchool();
			$iNumberFormat	= $oSchool->number_format;

			$aData = Ext_Thebing_Util::getNumberFormatData($iNumberFormat);
		} else {
			$aData = parent::getNumberFormat();
		}
		
		return $aData;
	}
}