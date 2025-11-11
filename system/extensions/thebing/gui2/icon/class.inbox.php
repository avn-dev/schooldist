<?php

/*
 * Die Klasse ist für das De-aktivieren der GUI2 Icons zuständig in der Inbox
 */

class Ext_Thebing_Gui2_Icon_Inbox extends Ext_Gui2_View_Icon_Abstract {

	protected $_sDecodedIdField;
	protected $_iMultiple;
	public $bIsEnquiry = false;

	public function __construct($sDecodedIdField=false)
	{
		$this->_sDecodedIdField = $sDecodedIdField;
	}

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		foreach ($aRowData as $aData) {
			// TODO Das sollte im besten Fall auf den aktuellen DB-Status gehen
			if (
				count($aSelectedIds) > 0 &&
				isset($aData['status_original']) &&
				$aData['status_original'] === 'pending'
			) {
				return false;
			}
		}

		## START Proformaliste
			if(
				$oElement->action == 'convertProforma' &&
				count($aSelectedIds) > 0
			) {
				return 1;
			}
		## ENDE
		## START Payments
			if (
				$oElement->action == 'payment'
			) {

				if (
					empty($aSelectedIds) ||
					count($aSelectedIds) > 1 ||
					(!\System::d('ts_payments_without_invoice') &&!collect($aRowData)->every(fn(array $aInquiry) => !empty($aInquiry['has_invoice_data']) || !empty($aInquiry['has_proforma_data'])))
				) {
					return 0;
				}

				// Bei Gruppen wird weiterhin eine Rechnung benötigt
				foreach ($aRowData as $aData) {
					if (
						$aData['has_group'] &&
						empty($aData['has_invoice_data'])
					) {
						return 0;
					}
				}

				return 1;

//				$bSuccess = 1;
//
//				foreach($aRowData as $aData){
//					/*
//					$oInquiry = Ext_TS_Inquiry::getInstance($aData['id']);
//					// Payments darf man nur eingeben wenn es schon dokumente gibt
//					$aDocuments = (array)$oInquiry->getDocuments('invoice', true);
//					if(empty($aDocuments)){
//						$bSuccess = 0;
//					}*/
//
//				  if(
//
//						(
//							($aData['has_invoice'] ?? 0) == 0 &&
//							($aData['has_proforma'] ?? 0) == 0 &&
//							!isset($aData['filter_documents'])
//						) ||
//						(
//							isset($aData['filter_documents'])	&& // Indexliste
//							empty($aData['filter_documents'])
//						)
//					){
//						$bSuccess = 0;
//					}
//				}
//
//				return $bSuccess;
			}
		## ENDE

		## Sonstige Dokumente
		if(
			$oElement->action == 'additional_document'
		){
			if(count($aSelectedIds) > 0){
				return 1;
			}else{
				return 0;
			}
		}

		## Sonstige Dokumente Ende
		if(
			count($aSelectedIds) > 0 &&
			$aRowData[0]['id'] > 0 &&
			$oElement->action != 'communication'
		) {

			$sDecodedIdField = $this->_sDecodedIdField;

			if(!empty($sDecodedIdField)){
				$iInquiryId = $aRowData[0][$sDecodedIdField];
			}else{
				$iInquiryId = reset($aSelectedIds);
			}

			if($this->bIsEnquiry)
			{
				$oInquiry = Ext_TS_Enquiry::getInstance($iInquiryId);
			}
			else
			{
				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
			}

			if($oElement->action == 'openInvoicePdf') {
				// PDF Icon Rechnungen

				// PDF Icon darf nur anklickbar sein wenn es auch PDFs gibt
				$sPdfPath = (string)$oInquiry->getLastDocumentPdf('invoice');

				if(!empty($sPdfPath)) {
					return 1;
				}

				return 0;

			}elseif($oElement->action == 'openDocumentPdf'){
				// PDF Icon sonstige Dokumente
				if(count($aSelectedIds) > 1){

					return 0;
				}

				$oSearch = new Ext_Thebing_Inquiry_Document_Search($oInquiry->id);

				// Rechteprüfung, ob Dokument in dieser GUI erzeugt wurde
				if(
					$this->_oGui &&
					$this->_oGui->getOption('only_documents_from_same_gui') &&
					!Ext_Thebing_Access::hasRight('thebing_gui_document_areas')
				) {
					$sGuiName = $this->_oGui->name;
					if(!empty($sGuiName)) {
						$oSearch->setGuiLists(array(array($sGuiName, $this->_oGui->set)));
					}
				}

				// Zusätzliche Rechteprüfung mit Inboxrechten
				// Dies wäre durch »thebing_gui_document_areas« schon abgedeckt, allerdings nur für neue Dokumente
				if(System::d('ts_check_inbox_rights_for_document_templates')) {
					$oUser = System::getCurrentUser();
					$aInboxes = $oUser->getInboxes('id');
					$oSearch->setTemplateInboxes($aInboxes);
				}

				$sPdfPath = (string)$oInquiry->getLastDocumentPdf('additional_document', [], $oSearch);

				if(!empty($sPdfPath)) {

					return 1;
				}

				return 0;
			}elseif($oElement->task == 'deleteRow'){
				//keine Schüler mit Rechnungen löschen, siehe t-2392
				if(
					$oInquiry->has_invoice == 1 ||
					$oInquiry->hasGroup() // Müssen über das Gruppen-Konstrukt gelöscht werden!
				) {

					return 0;
				}
			}elseif(
				$oElement->task == 'openDialog' &&
				$oElement->action == 'edit' &&
				count($aSelectedIds) > 1
			){

				return 0;		// SR Editierenn darf NIE bei Multiple
			}elseif(
				$oElement->action == 'booking' &&
				$oInquiry->isConfirmed()
			) {
				return 0;
			} elseif (
				$oElement->action === 'reallocateAmounts' &&
				count($aSelectedIds) > 1
			) {
				return 0;
			}

			return 1;

		}


		if(
			count($aSelectedIds) > 0 &&
			(
				$oElement->action == 'payment' ||
				$oElement->action == 'releaseDocument' ||
				$oElement->action == 'transfer_provider'
			)
		){
			return 1;
		}

		if(
			$oElement->action == 'communication' ||
			$oElement->action == 'notices'
		){
			
			if(count($aSelectedIds) > 0) {
				
				if(
					$oElement->action === 'communication' &&
					$oElement->additional === 'booking'
				) {

					// Icon soll doch immer aktiv sein, wg. den Notizen und SMS
//					foreach($aRowData as $aData) {
//						if(empty($aData['has_student_app'])) {
//							return 0;
//						}
//					}

					return 1;
				}
				
				return 1;
			}else{
				return 0;
			}
		} else {

			return $oElement->active;
		}

		return 0;
	}
}
