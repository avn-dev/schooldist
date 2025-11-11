<?php

class Ext_Thebing_Inquiry_Document_Numberrange_List {

	public $sDocumentType			= '';
	public $bIsCredit				= false;
	public $bCheckAccess			= true;
	public $iSchoolId				= null;
	public $iInvoiceNumberrangeId	= null;
	public $iOldId					= 0;
	protected $_aSelectedIds		= array();
	protected $_aList				= null;
	protected $_sL10NPart			= null;

	/**
	 * @var Ext_TS_Inquiry
	 */
	protected $oInquiry;
	
	public function __construct($aSelectedIds = array()) {
		$this->_aSelectedIds = (array)$aSelectedIds;
	}
	
	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 */
	public function setInquiry(Ext_TS_Inquiry $oInquiry) {
		$this->oInquiry = $oInquiry;
	}
	
	public function generateDefault() {

		$oInquiryDocument = new Ext_Thebing_Inquiry_Document();
		$oInquiryDocument->type = $this->sDocumentType;
		$oInquiryDocument->is_credit = $this->bIsCredit;
		$oInquiryDocument->iOldId = $this->iOldId;
		$oInquiryDocument->iSchoolId = $this->iSchoolId;

		if($this->oInquiry instanceof Ext_TS_Inquiry_Abstract) {
//			$oInquiryDocument->inquiry_id = $this->oInquiry->id;
			$oInquiryDocument->entity = get_class($this->oInquiry);
			$oInquiryDocument->entity_id = $this->oInquiry->id;

		}
		
		$this->_aSelectedIds = array(
			$oInquiryDocument
		);
	}
	
	public function getList() {

		$aList = array();
		
		if($this->_aList === null) {
			$aSelectedIds = $this->getSelectedIds();

			foreach($aSelectedIds as $mSelected) {
				$oInquiryDocument = $this->_getInquiryDocument($mSelected);

				if(!$oInquiryDocument) {
					continue;
				}

				if($oInquiryDocument->id > 0) {
					$oInquiry = $oInquiryDocument->getInquiry();
					$oSchool = $oInquiry->getSchool();
					$iSchoolId = (int)$oSchool->id;
					$iSelectedID = (int)$oInquiryDocument->id;
				} else {
					$iSchoolId = (int)$oInquiryDocument->iSchoolId;
					$iSelectedID = (int)$oInquiryDocument->iOldId;
				}

				$sDocumentType = (string)$oInquiryDocument->type;
				$bIsCredit = (bool)$oInquiryDocument->is_credit;

				if(
					isset($oInquiry) &&
					$oInquiry instanceof Ext_TS_Inquiry
				) {
					$oInbox = $oInquiry->getInbox();
					Ext_TS_NumberRange::setInbox($oInbox);
				}

				// Inbox setzen, damit Ext_TS_NumberRange::manipulateSqlNumberRangeQuery() direkt auch auf Inbox prüft
				Ext_TS_NumberRange::setInbox($oInquiryDocument->getInbox(true));

				if(Ext_Thebing_Access::hasRight('thebing_invoice_numberranges')) {
					// Wenn Recht auf Nummernkreise vorhanden: Alle Nummernkreise holen, auf die der Benutzer Rechte hat
					$aNumberranges = (array)Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangesByType($sDocumentType, $bIsCredit, $this->bCheckAccess, $iSchoolId);
				} else {
					// Leeres Array, damit nur Default-Nummernkreis gesetzt wird
					$aNumberranges = array();
				}

				$oDefaultNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($sDocumentType, $bIsCredit, $iSchoolId);
				Ext_TS_NumberRange::setInbox(null);

				// Prüfen, ob Defaultnummernkreis noch im Array ist
				if(!array_key_exists($oDefaultNumberrange->id, $aNumberranges)) {
					$aNumberranges[$oDefaultNumberrange->id] = $oDefaultNumberrange->name;
					asort($aNumberranges);
				}

				$aList[$iSelectedID] = $aNumberranges;
			}

			$this->_aList = $aList;
		} else {
			$aList = (array)$this->_aList;
		}

		return $aList;
	}
	
	public function getNumberRanges($iSelectedId) {
		$aNumberranges	= array();
		$iSelectedId	= (int)$iSelectedId;
		$aList			= $this->getList();

		if(isset($aList[$iSelectedId])) {
			$aNumberranges = (array)$aList[$iSelectedId];
		}

		return $aNumberranges;
	}
	
	public function canShowListDialog() {
		$bShowDialog = false;
		
		$aSelectedIds = $this->getSelectedIds();
		
		foreach($aSelectedIds as $mSelected) {
			$iSelectedId = $this->_getSelectedId($mSelected);

			if($iSelectedId === false) {
				continue;
			}
			
			$aNumberRanges = $this->getNumberRanges($iSelectedId);

			if(count($aNumberRanges) > 1) {
				$bShowDialog = true;
				break;
			}
		}
		
		return $bShowDialog;
	}
	
	public function getDialog(\Ext_Gui2 $oGui2) {

		// Wenn Entwürfe aktiv und ts_payments_without_invoice nicht aktiv ist, Text um Hinweis erweitern, dass
		// keine Entwürfe erstellt werden.
		$firstDocument = Ext_Thebing_Inquiry_Document::getInstance($this->_getSelectedId(reset($this->_aSelectedIds)));
		if (
			$firstDocument &&
			\Ext_Thebing_School::draftInvoicesActive($firstDocument->getSchool()) &&
			!\System::d('ts_payments_without_invoice')
		) {
			$sTitle = $this->t('Proforma direkt in Rechnungen umwandeln');
		} else {
			$sTitle = $this->t('Proforma umwandeln');
		}

		$oDialog = $oGui2->createDialog($sTitle, $sTitle, $sTitle);
		$oDialog->width = 600;
		$oDialog->height = 400;

		$bShowHintDiv = true;

		foreach($this->_aSelectedIds as $mSelected) {
			$iSelectedId = $this->_getSelectedId($mSelected);

			if($iSelectedId === false) {
				continue;
			}

			$aNumberRanges = $this->getNumberRanges($iSelectedId);

			$oInquiryDocumentTemp = Ext_Thebing_Inquiry_Document::getInstance($iSelectedId);
			$oInquiry = $oInquiryDocumentTemp->getInquiry();
			$oCustomer = $oInquiry->getCustomer();

			// Dialog wird immer angezeigt, daher bei nur einem Nummernkreis Hinweis anzeigen und Selects ausblenden #6810
			$sRowStyle = '';
			if(
				count($aNumberRanges) === 1 &&
				System::d('debugmode') !== 2
			) {
				$sRowStyle = 'display: none;';

				if($bShowHintDiv) {
					$bShowHintDiv = false;
					$oDiv = new Ext_Gui2_Html_Div();
					$oDiv->setElement($this->t('Bitte bestätigen Sie das Konvertieren der Dokumente.'));
					$oDialog->setElement($oDiv);
				}
			}

			$sTitle = '';
			$sTitle .= $oCustomer->getName();
			$sTitle .= ' (' . $oInquiryDocumentTemp->document_number . ' ) ';

			$oDialog->setElement($oDialog->createRow($sTitle, 'select', [
				'db_column' => 'numberrange_id',
				'db_alias' => $iSelectedId,
				'select_options' => $aNumberRanges,
				'row_style' => $sRowStyle
			]));
		}

		// Die Umwandlung wurde über den Bezahlen-Button gestartet. Diese Information weiterreichen.
		if ($oGui2->getRequest()->get('initiated_by')) {
			$oDialog->save_button = false;
			$aButton = array(
				'label' => self::t('speichern'),
				'task' => 'saveDialog',
				'action' => 'numberrange',
				'request_data' => '&initiated_by='.$oGui2->getRequest()->get('initiated_by')
			);
			$oDialog->aButtons = [$aButton];
		}

		if (!\Ext_Thebing_Client::immutableInvoicesForced()) {
			$format = new \Ext_Thebing_Gui2_Format_Date();

			$oDialog->setElement($oDialog->createRow($this->t('Rechnungsdatum'), 'calendar', [
				'db_column' => 'date',
				'value' => $format->formatByValue(\Carbon\Carbon::today()->toDateString()),
				'format' => $format
			]));
		}

		return $oDialog;
	}
	
	public function setL10NPart($sDescriptionPart) {
		$this->_sL10NPart = $sDescriptionPart;
	}
	
	protected function t($sKey) {
		return L10N::t($sKey, $this->_sL10NPart);
	}
	
	public function getSelectedIds() {
		if(
			empty($this->_aSelectedIds) &&
			strlen($this->sDocumentType) > 0
		) {
			$this->generateDefault();
		}
		
		return $this->_aSelectedIds;
	}
	
	protected function _getInquiryDocument($mSelected) {
		$oInquiryDocument = false;
		
		if(
			is_object($mSelected) &&
			$mSelected instanceof Ext_Thebing_Inquiry_Document
		) {
			$oInquiryDocument = $mSelected;
		} elseif(is_numeric($mSelected)) {
			$oInquiryDocument = Ext_Thebing_Inquiry_Document::getInstance($mSelected);
		}
		
		return $oInquiryDocument;
	}
	
	protected function _getSelectedId($mSelected) {
		$iSelectedId		= false;
		$oInquiryDocument	= $this->_getInquiryDocument($mSelected);
		
		if($oInquiryDocument) {
			if($oInquiryDocument->iOldId > 0) {
				$iSelectedId = (int)$oInquiryDocument->iOldId;
			} else {
				$iSelectedId = (int)$oInquiryDocument->id;
			}	
		}
		
		return $iSelectedId;
	}
}