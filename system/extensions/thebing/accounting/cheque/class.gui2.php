<?php

/**
 *
 *
 *  1)Anzeigen und Drucken von Schecks-Documenten(5 Ausgabe-Typen)
 *  2)Bestätigung des Druckauftrags
 *
 *    
 */

class Ext_Thebing_Accounting_Cheque_Gui2 extends Ext_Thebing_Gui2_Data
{

    private $sFilePath = '';			  
    private $aSelectedIds = array();	  //Positionen der Daten innerhalb der Anzeige-Liste
	private $aRealSelectedIds = array();  //Positionen der Liste gemappt mit den tatsächlichen DB-Ids
	private $iTemplateId = '';			  //PDF-Vorlage

	protected $oPdfContent = null;		  //akutelles PDF-Document
	private $oDialogObj = null;

	/**
	 *
	 * @return <type>
	 */
	public static function getDescriptionPart()
	{
		return 'Thebing » Accounting » Cheque';
	}


	/**
	 *  Überschreiben von saveInplaceEditor Methode.
	 *
	 * @param <type> $mValue
	 * @param <type> $iRowId
	 * @param <type> $sColumn
	 * @param <type> $sAlias
	 * @param <type> $mOldValue
	 * @param <type> $sSaveType
	 * @return <type>
	 */
	protected function saveInplaceEditor($aParams){

		$mValue		= $aParams['value'];
		$iRowId		= $aParams['row_id'];
		
		$this->_proofChequeObject($iRowId);

		$bSuccess =  parent::saveInplaceEditor($aParams);

		return $bSuccess;
		
	}

	/**
	 * @param integer $iRowId
	 * @return Ext_Thebing_Accounting_Cheque
	 */
	protected function _proofChequeObject($iRowId) {

		$sType		= $this->_oGui->decodeId($iRowId, 'type');
		$iTypeId	= $this->_oGui->decodeId($iRowId, 'type_id');
		$iId		= $this->_oGui->decodeId($iRowId, 'id');
		$iSchoolId	= $this->_oGui->decodeId($iRowId, 'school_id');

		if(empty($iId)) {

			$oCheque = Ext_Thebing_Accounting_Cheque::getInstance(0);
			$oCheque->type	 = $sType;
			$oCheque->type_id = (int)$iTypeId;
			$oCheque->school_id = (int)$iSchoolId;

			$mValidate = $oCheque->validate();

			if($mValidate === true) {
				$oCheque->save();
			}

			$iId = $oCheque->id;

			$this->_oGui->updateEncodedId($iRowId, 'id', $iId);

		} else {
			$oCheque = Ext_Thebing_Accounting_Cheque::getInstance($iId);
		}

		return $oCheque;
	}

	/**
	 *
	 *  Unmittelbar nach dem Druckvorgang sollte innerhalb des akutellen Fensters
	 *  ein zwieter Tab generiert werden über den der Benutzer den akutellen Druckstatus
	 *  für jeden ausgewählten Scheck setzen kann(Checkboxen).
	 *
	 *
	 * @param <type> $_VARS
	 */
	public function switchAjaxRequest($_VARS) {
		
		if(
			$_VARS['task'] == 'saveDialog' &&
			$_VARS['action'] == 'cheque_pdf'
		) {
			// Daten
			$iTemplateId	= (int)$_VARS['save']['pdf_template_id']; //1040
			$aSelectedIds	= (array)$_VARS['id'];

			$this->iTemplateId = $iTemplateId;
			$this->aSelectedIds = $aSelectedIds;

			if($iTemplateId > 0) {

				$oPdf = new Ext_Thebing_Pdf_Basic($iTemplateId);
				$oPdf->sDocumentType = 'cheque';

				foreach((array)$aSelectedIds as $iChequeId) {

					$oCheque = $this->_proofChequeObject($iChequeId);

					//wenn die Schecknummer noch nicht vergeben, wird eine generiert
					if(!$oCheque->cheque_number) {

						$iSchoolId = $this->_oGui->decodeId($iChequeId, 'school_id');
						$oNumber = new Ext_Thebing_Accounting_Cheque_Number($iSchoolId);
						$oCheque->cheque_number = $oNumber->make();
						$oCheque->save();
					}

					$aData  = array();
					$aData['date']			  = $oCheque->created;
					$aData['document_number'] = $oCheque->cheque_number;

					$aAdditional			  = array();
					$aAdditional['cheque_id'] = $oCheque->id;

					$oPdf->createDummyDocument($aData, array(), array(), $aAdditional); //

				}

				// Name der PDF Vorlage
				$sNumber = 'cheque_overview';
				// Nummer des Vertrages
				$sNumber .= '_'.date('YmdHis');
				$sFileName = \Util::getCleanFileName($sNumber);
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
				$sPath = $oSchool->getSchoolFileDir();
				$sPath = $sPath."/cheque/";

				try {

					$sFilepath = $oPdf->createPDF($sPath, $sFileName);
					$this->sFilePath = $sFilepath;
					
				} catch(PDF_Exception $e) {
					__pout('Exception occured '.$e->getMessage());
				}

				if(is_file($sFilepath)) {
					$sFilepath = str_replace(\Util::getDocumentRoot(), '', $sFilepath);
					$sFilepath = str_replace('storage/', '', $sFilepath);
					$sUrl = '/storage/download/'.$sFilepath;

					$aUrls = array();
					$aUrls[0] = $sUrl;

					//$oDialog = $this->_getPdfConfirmDialog($aUrls); //wird erst nach dem Drucken zum Einsatz kommen!
					$oDialog = $this->_generateConfirmationWindow($aSelectedIds); // zuerst PDFs anzeigen !
					$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

					//dem aktellen Dialogfenster neue action und task zuweisen
					$aData['action'] = 'confirm_pdf_print';
					$aData['task']	 = 'saveDialog';

					//$aTransfer['data']['options']['close_after_save'] = true;
					$aTransfer['data']	 = $aData;		//  PDFs laden ?
					$aTransfer['error']	 = array();
					$aTransfer['action'] = "openDialog"; // welche acitons sind zugelassen? (Dialogformen) handelt sich immer um ein Dialogobjekt?

					$aTransfer['data']['force_new_dialog'] = 1;
					$aTransfer['data']['old_id'] = 'CHEQUE_TEMPLATE_'.implode('_', $aSelectedIds);

					//wenn keine Fehler bei Druckvorgang kann das akutelle Fenster geschlossen  und saveDialogCallBack ausgeführt werden
					//$aTransfer['action']	= "saveDialogCallback";
	
				}else{
					$aTransfer['error'][] = L10N::t('PDF konnte nicht gespeichert werden', $this->_oGui->gui_description);
				}

			}

			echo json_encode($aTransfer);
		

		}else if(

			$_VARS['task'] == 'saveDialog' &&
			$_VARS['action'] == 'confirm_pdf_print'
		){

			$this->_savePrintStatus($_VARS['id'], $_VARS['save']);  // Druckvorgang erfolgreich?

			if(is_file($this->sFilePath)) {

				$sFilepath = str_replace(\Util::getDocumentRoot(), '', $this->sFilePath);
				$sFilepath = str_replace('storage/', '', $sFilepath);
				$sUrl = '/storage/download/'.$sFilepath;

				$aTransfer['data']['options']['close_after_save'] = true;
				//$aTransfer['success_message'] = sprintf(L10N::t('Schecks-PDF wurden erfolgreich angelegt.<br/><a href="%s">Bitte klicken Sie hier um die Pdfs anzuzeigen.</a>', $this->_oGui->gui_description), $sUrl);

				$aTransfer['data']['id'] = 'AGENCYOVERVIEW_'.implode($this->aSelectedIds);
				$aTransfer['error']		 = array();
				$aTransfer['action']	 = "saveDialogCallback"; // howto


			}else{
				$aTransfer['error'][] = L10N::t('PDF konnte nicht gespeichert werden', $this->_oGui->gui_description);
			}

			echo json_encode($aTransfer);

		}else {
			parent::switchAjaxRequest($_VARS);
		}

	}

	/**
	 *  1-Tab (generiertes PDF-Dockument anzeigen)
	 *  2-Tab (Status des Druckvorgangs setzen)
	 * 
	 *
	 * @param array $aSelectedIds
	 * @return <type> 
	 */
	private function _getPdfConfirmDialog(array $aSelectedIds){

		$oGui = $this->_oGui;

		$oDialogObj = $oGui->createDialog($oGui->t('Erfolgreich gedruckt?'), $oGui->t('Erfolgreich gedruckt?'), $oGui->t('Erfolgreich gedruckt?'));
		
		$oDialogObj->width		= 600;
		$oDialogObj->height		= 350;

		$oDialogObj->setElement($oDialogObj->createRow($oGui->t('ScheckNr'),'Checkbox',array('db_column'=> 'print_success', 'required' => false) ));

		return $oDialogObj;
	}


	/**
	 *  BestätigungsDialog mit 2 Tabs (PDF & Druckvorgangstatus)
	 *
	 * @param <type> $aSelectedIds
	 * @return <type>
	 */
	private function _generateConfirmationWindow($aSelectedIds){
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		$oGui = $this->_oGui;

		$sFilepath = str_replace(\Util::getDocumentRoot(), '', $this->sFilePath);
		#$sFilepath = str_replace('media/secure/', '', $sFilepath);
		#$sUrl = '/secure.php?t=show&f='.$sFilepath;    // um das Anzeigen des DialogFensters (Save) zu unterdrücken, das Attribut t=show setzen!!!

		if(substr($sFilepath, 0, 1) !== '/') {
			$sFilepath = '/' . $sFilepath;
		}
		
		$oDialog = $oGui->createDialog($oGui->t('Bestätigung des Druckvorgangs'), $oGui->t('Bestätigung des Druckvorgangs'), $oGui->t('Bestätigung des Druckvorgangs'));
		$oDialog->width  =  800;
		//$oDialog->height = 400;

		$oTab1 = $oDialog->createTab($oGui->t('Schecks'));
		$oTab1->no_padding	 = 1;
		$oTab1->no_scrolling = 1;

		$oTab1->setElement($this->getEmbeddedIframe($sFilepath));   //PDF-Document einbetten 
		$oTab1->sJS = $this->getEmbeddedIframeJs();

		$oTab2 = $oDialog->createTab($oGui->t('Erfolgreich gedruckt?'));
		
		foreach((array)$aSelectedIds as $iChequeId) {

			$oCheque = $this->_proofChequeObject($iChequeId);
			
			$aChequeData = $oCheque->getAdditionalInfo();

			$sRecipient	   = $aChequeData['recipient'];
			$sAmount	   = $aChequeData['amount'];
			$iChequeNumber = $aChequeData['cheque_number'];
			$iCurrencyId   = $aChequeData['currency_id'];

		
			$sAmount = Ext_Thebing_Format::Number($sAmount, $iCurrencyId, $iSessionSchoolId);


			//todo: translation setzten! bzw: was soll da überhaupt angezeigt werden?
			$oTab2->setElement("Empfänger: ".$sRecipient." ");
			$oTab2->setElement("Betrag: ".$sAmount);

			$oTab2->setElement($oDialog->createRow($oGui->t('Schecknummer').': '.$iChequeNumber, 'checkbox', array(
					'db_column'=>'print_success_'.$iChequeId,
					'required' => true) 
					)
			); 
		}

		$oDialog->setElement($oTab1);
		$oDialog->setElement($oTab2);

		return $oDialog;
	}

	/**
	 */
	private function loadPdfDocuments(){
		header("Content-Type: application/pdf");
		$oPdf = $this->oPdfContent->outputPDF();
		echo $oPdf;
	}

	/**
	 *  Pdf Document im IFrame einbetten 
	 *
	 * @param <type> $sUrl
	 * @return string
	 */
	private function getEmbeddedIframe($sUrl){
		
		$sIframe = '<iframe src="'.$sUrl.'" id="cheque_iframe" width="100%" height="100%" frameborder="0"></iframe>';
		
		return $sIframe;
	}

	/**
	 *  Pdf Document im IFrame einbetten 
	 *
	 * @param <type> $sUrl
	 * @return string
	 */
	private function getEmbeddedIframeJs(){
		
		$sJs = '
			function resizeChequeIframe() {
				var oIframe = $(\'cheque_iframe\');
				var oContainer = oIframe.up(\'.GUITabBodyActive\');

				if(oContainer) {
					var iHeight = oContainer.getHeight();
					oIframe.style.height = (iHeight-16)+\'px\';
				}
			}

			Event.observe(window, \'resize\', resizeChequeIframe);
			resizeChequeIframe();
			
			';
		
		return $sJs;

	}

	/**
	 * aktueller Druckvorgang wird gespeichert
	 *  (Was ist eigentlich mit Schecks die bereits ausgedruct wurden? können diese erneut bearbeitet werden?)
	 *
	 * @global <type> $user_data
	 * @param <type> $iPrintedCheques
	 * @param <type> $iPrintSuccess
	 */
	private function _savePrintStatus(array $printedCheques, array $printSuccess) {
		
        $school = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		
		$user = \System::getCurrentUser();

		foreach($printedCheques as $iPrintedId) {

			$cheque = $this->_proofChequeObject($iPrintedId);

            if($printSuccess['print_success_'.$iPrintedId] == 1){
                $cheque->print_success     = 1;
            } else {
                $cheque->print_success     = 0;
            }
			
            $cheque->print_user_id         = (int)$user->id;
            $cheque->print_user_created    = time();
            $cheque->school_id             = $school->id;
            $cheque->save();
            
		}
		
	}

	/**
	 * Translation EN => DE
	 *
	 * @return <type>
	 */
	public function getMappedTypeLabels(){

		$aMappedExpensesLabel = array(
			'accommodation'=> $this->t('Unterkunft'),
			'teacher'	   => $this->t('Lehrer'),
			'transfer'	   => $this->t('Transfer'),
			'manual'	   => $this->t('Manuell'),
			'refund'	   => $this->t('Schüler Auszahlung' )
			);

		return $aMappedExpensesLabel;
	}

	static public function getFromDate(){
		return Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today()->subMonth());
	}

	static public function getUntilDate(){
		return Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today());
	}

	static public function getOrderby(){
		
		return ['created' => 'DESC'];
	}

	static public function getWhere(){
		
		$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		return ['school_id'=> (int)$iSessionSchoolId];
	}

	static public function getDialog(Ext_Gui2 $gui2) {

		$aTemplateTypes = 'cheque';

		$pdfTemplates = Ext_Thebing_Pdf_Template_Search::s($aTemplateTypes, false);
		$pdfTemplates = Ext_Thebing_Util::convertArrayForSelect($pdfTemplates);
		$pdfTemplates = Ext_Thebing_Util::addEmptyItem($pdfTemplates, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));

		$oDialogPdf = $gui2->createDialog($gui2->t('Scheck PDF'));
		$oDialogPdf->width			= 600;
		$oDialogPdf->height			= 250;
		$oDialogPdf->sDialogIDTag = 'CHEQUE_TEMPLATE_';

		$oDialogPdf->setElement(
			$oDialogPdf->createRow(
				$gui2->t('PDF-Vorlage'),
				'select',
				array(
					'db_alias'			=>'',
					'db_column'			=> 'pdf_template_id',
					'select_options'	=> $pdfTemplates,
					'required'			=> 1
				)
			)
		);

		return $oDialogPdf;
	}
	
}

