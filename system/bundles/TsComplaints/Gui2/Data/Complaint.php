<?php

namespace TsComplaints\Gui2\Data;

use \TsComplaints\Entity\Complaint as TsComplaint_Entity_Complaint;
use \TcComplaints\Entity\Complaint as TcComplaint_Entity_Complaint;
use \TcComplaints\Entity\ComplaintHistory as TcComplaints_Entity_ComplaintHistory;
use \TcComplaints\Entity\Category as TcComplaints_Entity_Category;
use \TcComplaints\Gui2\Data\ComplaintHistory as TcComplaints_Gui2_Data_ComplaintHistory;
use \TsComplaints\Gui2\View\Autocomplete;
use \TcComplaints\Gui2\Selection\SubCategory;
use \TsComplaints\Gui2\Selection\Area;
use \TcComplaints\Gui2\Data\Complaint as TcComplaints_Gui2_Data_Complaint;
use \Ext_TC_L10N;

class Complaint extends TcComplaints_Gui2_Data_Complaint {

	const TRANSLATION_PATH = 'Thebing » Marketing » Complaints';

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Beschwerde "{customer_name}" editieren'), $oGui->t('Beschwerde anlegen'));

		return $oDialog;
	}

	/**
	 * Bereit den Dialog wieder auf
	 *
	 * @param $sIconAction
	 * @param $aSelectedIds
	 * @param bool $iTab
	 * @param bool $sAdditional
	 * @param bool $bSaveSuccess
	 * @return array
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {

		if (in_array($sIconAction, ['new', 'edit'])) {
			if(!$this->oWDBasic) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			if(
				!empty($aSelectedIds) &&
				$sIconAction == 'new'
			) {
				$sIconKey = self::getIconKey('edit', $sAdditional);
			} else {
				$sIconKey = self::getIconKey($sIconAction, $sAdditional);
			}
			$oDialog = $this->aIconData[$sIconKey]['dialog_data'];

			$this->getDialogContent($oDialog, $aSelectedIds);
		}

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		return $aData;
	}

	/**
	 * Erstellt den Inhalt des Dialoges
	 *
	 * @param \Ext_Gui2_Dialog $oDialogData
	 * @param $aSelectedIds
	 * @throws \Exception
	 */
	protected function getDialogContent(\Ext_Gui2_Dialog &$oDialogData, $aSelectedIds) {

		$oDialogData->aElements = array();
		$oDialogData->aSaveData = array();
		$oDialogData->aUniqueFields = array();

		$oWDBasic = $this->_getWDBasicObject($aSelectedIds);

		if($oWDBasic->getId() === 0) {

			// Das aktuelle Datum soll angezeigt werden
			$oDateTime = new \DateTime();
			$sDate = $oDateTime->format('Y-m-d');

			$oDiv = $oDialogData->createRow($this->t('Datum'), 'calendar', array(
				'db_alias' => 'tc_cs',
				'db_column' => 'complaint_date',
				'default_value' => $sDate,
				'required' => true,
				'format' => new \Ext_Thebing_Gui2_Format_Date()
			));
			$oDialogData->setElement($oDiv);

			$aInquiryAutocompleteOptions = array(
				'db_alias' => '',
				'db_column' => 'inquiry_id',
				'required' => true,
				'autocomplete' => new Autocomplete(),
			);

			$sParentGuiHash = $this->_oGui->parent_hash;
			if(!empty($sParentGuiHash)) {
				$aInquiryAutocompleteOptions['readonly'] = true;
			}

			$oDiv = $oDialogData->createRow($this->t('Schüler'), 'autocomplete', $aInquiryAutocompleteOptions);
			$oDialogData->setElement($oDiv);

			self::getDialogInputs('new', $oDialogData, $this, $oWDBasic);

		} else {

			$oDiv = $oDialogData->createRow($this->t('Datum'), 'calendar', array(
				'db_alias' => 'tc_cs',
				'db_column' => 'complaint_date',
				'required' => true,
				'format' => new \Ext_Thebing_Gui2_Format_Date()
			));
			$oDialogData->setElement($oDiv);

			$oDiv = $oDialogData->createRow($this->t('Schüler'), 'autocomplete', array(
				'db_alias' => '',
				'db_column' => 'inquiry_id',
				'required' => true,
				'readonly' => true,
				'autocomplete' => new Autocomplete(),
			));
			$oDialogData->setElement($oDiv);

			self::getDialogInputs('', $oDialogData, $this, $oWDBasic);

		}

	}

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public static function getEditDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Beschwerde "{customer_name}" editieren'), $oGui->t('Beschwerde anlegen'));

		return $oDialog;
	}

	/**
	 * Wird in der Buchungsliste eingesetzt, da dieser Dialog Beschwerden hinzufügen sowie Kommentare hinzufügen kann
	 *
	 * @param \Ext_Gui2 $oGui
	 * @param $bInquiryList
	 * @return \Ext_Gui2_Dialog
	 */
	public static function getComplaintDialog(\Ext_Gui2 $oGui, $bInquiryList = true){

		$oPage = new \Ext_Gui2_Page();

		$sClassName = \Ext_TC_Factory::getClassName('\TcComplaints\Gui2\Data\Complaint');
		$sFirstListPath = \Ext_TC_Factory::executeStatic($sClassName, 'getComplaintYmlPath');
		$sSecoundListPath = \Ext_TC_Factory::executeStatic($sClassName, 'getComplaintHistoryYmlPath');

		$oComplaintGenerator = new \Ext_Gui2_Factory($sFirstListPath);
		$oComplaintHistoryGenerator = new \Ext_Gui2_Factory($sSecoundListPath);

		$oGuiComplaint = $oComplaintGenerator->createGui('', $oGui);
		$oGuiComplaint->gui_description = static::TRANSLATION_PATH;
		$oGuiComplaintHistory = $oComplaintHistoryGenerator->createGui('', $oGuiComplaint);
		$oGuiComplaintHistory->gui_description = static::TRANSLATION_PATH;

		if($bInquiryList) {
			$sForeignKey = 'inquiry_id';
			$sParentPrimaryKey = 'id';
		} else {
			$sForeignKey = 'type_id';
			$sParentPrimaryKey = 'id';
			$oGuiComplaint->bReadOnly = true;
			$oGuiComplaintHistory->bReadOnly = true;
		}

		$oGuiComplaint->foreign_key			= $sForeignKey;
		$oGuiComplaint->parent_primary_key	= $sParentPrimaryKey;

		$oGuiComplaintHistory->foreign_key = 'complaint_id';
		$oGuiComplaintHistory->parent_primary_key = 'id';

		if($bInquiryList) {
			$oDialog = $oGui->createDialog($oGui->t('Beschwerden von "{customer_name}"'));
		} else {
			$oDialog = $oGui->createDialog($oGui->t('Beschwerden über "{name}"'));
		}

		$oDialog->sDialogIDTag = 'COMPLAINT_';
		
		$oPage->setGui($oGuiComplaint);
		$oPage->setGui($oGuiComplaintHistory);

		$oDialog->setElement($oPage);

		return $oDialog;

	}

	/**
	 * Wird in folgenden Listen um den Beschwerdeverlauf anzuzeigen benötigt:
	 * Unterkunftsanbieter, Transferanbieter und bei den Lehrern
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 */
	public static function getComplaintsDialog($oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Beschwerden über "%s" ansehen'), $oGui->t('Beschwerden ansehen'));

		$oDialog->setDataObject('\TcComplaints\Gui2\Dialog\Complaints');

		$oDialog->save_button = false;

		return $oDialog;

	}

	/**
	 * Setzt die Bereiche der Beschwerden
	 *
	 * @param string $sLanguage
	 * @param bool $bAddEmptyItem
	 * @return array
	 */
	public static function getAreas($sLanguage = '', $bAddEmptyItem = false)  {

		$aReturn = array(
			'generally' => Ext_TC_L10N::t('Allgemein', $sLanguage),
			'activity' => Ext_TC_L10N::t('Aktivitäten', $sLanguage),
			'accommodation' => Ext_TC_L10N::t('Unterkunftsanbieter', $sLanguage),
			'transfer' => Ext_TC_L10N::t('Transferanbieter', $sLanguage),
			'teacher' => Ext_TC_L10N::t('Lehrer', $sLanguage),
			'lesson' => Ext_TC_L10N::t('Unterricht', $sLanguage),
		);

		if($bAddEmptyItem) {
			$aReturn = \Ext_TC_Util::addEmptyItem($aReturn, '', 'xNullx');
		}

		return $aReturn;

	}

	/**
	 * Soll nur die Benutzer wieder geben die auch Beschwerden erstellt haben
	 * @return array
	 */
	public static function getUsers() {

		$oClient = \Ext_Thebing_Client::getInstance();
		$aUsers = $oClient->getUsers(true);

		$aReturn = array();
		foreach($aUsers as $iId => $aUser) {
			$oComplaintRepository = TcComplaint_Entity_Complaint::getRepository();
			if($oComplaintRepository->hasUserCreatedComplaints($iId)) {
				$aReturn[$iId] = $aUser;
			}
		}

		asort($aReturn);
		$aReturn = \Ext_TC_Util::addEmptyItem($aReturn);

		return $aReturn;

	}

	/**
	 * Gibt den Pfad der Yaml-Datei zurück
	 *
	 * @return string
	 */
	public static function getComplaintYmlPath() {
		return 'TsComplaints_complaint_list';
	}

	/**
	 * Gibt den Pfad der Yaml-Datei des Beschwerde Verlaufs zurück
	 *
	 * @return string
	 */
	public static function getComplaintHistoryYmlPath() {
		return 'TsComplaints_complainthistory_list';
	}

	/**
	 * @param string $sValue
	 * @param array $aResultData
	 * @return array
	 * @throws \Exception
	 */
	public static function getDependencies($sValue, $aResultData) {

		$aName = array();
		$sLanguage = \System::getInterfaceLanguage();

		switch($aResultData['type']) {
			case 'generally':
			case 'activity':
				break;
			case 'transfer':
				if($sValue > 0) {
					$oTransfer = \Ext_Thebing_Pickup_Company::getInstance($sValue);
					$aName[$aResultData['type']] = $oTransfer->getName();
				} elseif($sValue < 0) {
					$iValue = (int)$sValue;
					$iValue = $iValue * (-1);
					$oTransfer = \Ext_Thebing_Accommodation::getInstance($iValue);
					$aName[$aResultData['type']] = $oTransfer->getName();
				}
				break;
			case 'accommodation':
				$oAccommodation = \Ext_Thebing_Accommodation::getInstance($sValue);
				$aName[$aResultData['type']] = $oAccommodation->getName($sLanguage);
				break;
			case 'teacher':
				$oTeacher = \Ext_Thebing_Teacher::getInstance($sValue);
				$aName[$aResultData['type']] = $oTeacher->getName();
				break;
		}

		return $aName;

	}

	/**
	 * Gibt ein Objekt einer Klasse zurück
	 *
	 * @param array $aSelectedIds
	 * @param $sArea
	 * @return \Ext_Thebing_Accommodation|\Ext_Thebing_Teacher|\Ext_Thebing_Pickup_Company
	 * @throws \Exception
	 */
	public static function getObject($aSelectedIds, $sArea) {

		switch($sArea) {
			case 'teacher':
				$oObject = \Ext_Thebing_Teacher::getInstance($aSelectedIds[0]);
				break;
			case 'transfer':
				$oObject = \Ext_Thebing_Pickup_Company::getInstance($aSelectedIds[0]);
				break;
			case 'accommodation':
				$oObject = \Ext_Thebing_Accommodation::getInstance($aSelectedIds[0]);
				break;
		}

		return $oObject;

	}

	/**
	 * Gibt das Buchungsobjekt wieder
	 *
	 * @param int $iInquiryId
	 * @return string
	 */
	public function getNameOfTraveller($iInquiryId) {

		$oInquiry = \Ext_TS_Inquiry::getInstance($iInquiryId);
		$oTraveller = $oInquiry->getTraveller();
		$sName = $oTraveller->getName();

		return $sName;
	}

	/**
	 * Holt den Namen des Kommentarautors.
	 *
	 * @param TsComplaint_Entity_Complaint|TcComplaint_Entity_Complaint $oComplaint
	 * @param TcComplaints_Entity_ComplaintHistory $oComplaintHistory
	 * @return string
	 * @throws \Exception
	 */
	public static function getCommentAuthor($oComplaint, TcComplaints_Entity_ComplaintHistory $oComplaintHistory) {

		$sName = '';
		$oCategory = $oComplaint->getCategory();

		switch($oComplaintHistory->comment_type) {
			case 'student_commentary':
				$oInquiry = \Ext_TS_Inquiry::getInstance($oComplaint->inquiry_id);
				$oTraveller = $oInquiry->getTraveller();
				$sName = $oTraveller->getName();
				break;
			case 'employee_commentary':
				$oClient = \Ext_Thebing_User::getInstance($oComplaintHistory->creator_id);
				$sName = $oClient->name;
				break;
			case 'agency_commentary':
				$oInquiry = \Ext_TS_Inquiry::getInstance($oComplaint->inquiry_id);
				$oAgency = $oInquiry->getAgency();
				if($oAgency) {
					$sName = $oAgency->getName(true);
				}
				break;
			case 'provider_commentary':
				switch($oCategory->type) {
					case 'accommodation':
						$oAccommodation = \Ext_Thebing_Accommodation::getInstance($oComplaint->type_id);
						$sName = $oAccommodation->getName();
						break;
					case 'transfer':
						$oTransfer = \Ext_Thebing_Pickup_Company::getInstance($oComplaint->type_id);
						$sName = $oTransfer->getName();
						break;
					case 'teacher':
						$oTeacher = \Ext_Thebing_Teacher::getInstance($oComplaint->type_id);
						$sName = $oTeacher->getName();
						break;
				}
				break;
		}

		return $sName;

	}

	/**
	 * Gibt die Inputfelder für den "neuer Eintrag"-Dialog und den "editier"-Dialog
	 *
	 * @param string $sDialog
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @param \Ext_Gui2_Data $oGui
	 * @param TcComplaint_Entity_Complaint $oComplaint
	 * @throws \Exception
	 */
	public function getDialogInputs($sDialog = '', \Ext_Gui2_Dialog $oDialog, \Ext_Gui2_Data $oGui, TcComplaint_Entity_Complaint $oComplaint) {
		global $_VARS;

		$aAreas = self::getAreas();

		$sType = $_VARS['save']['type'];

		if(
			$oComplaint->getId() > 0 &&
			$_VARS['task'] != 'reloadDialogTab'
		) {
			$sType = $oComplaint->getCategory()->type;
		}

		$aOptions = array(
			'id' => 'form_area',
			'db_column' => 'type',
			'select_options' => $aAreas,
			'required' => true,
			'skip_value_handling' => true,
			'value' => $sType,
			'events' => array(
				array(
					'event' => 'change',
					'function' => 'reloadDialogTab',
					'parameter' => 'aDialogData.id, 1'
				)
			)
		);

		$oDialog->setElement($oDialog->createRow($oGui->t('Bereich'), 'select', $aOptions));

		if(
			$sType !== '0' &&
			$sType !== 'xNullx' &&
			$sType !== null
		) {

			$oDialog->setElement($oDialog->createRow($oGui->t('Kategorie'), 'select', array(
				'id' => 'form_category',
				'db_alias' => 'tc_cs',
				'db_column' => 'category_id',
				'required' => true,
				'selection' => new \TcComplaints\Gui2\Selection\Category($sType),
				'events' => array(
					array(
						'event' => 'change',
						'function' => 'reloadDialogTab',
						'parameter' => 'aDialogData.id, 1'
					)
				)
			)));

		} else {
			$oComplaint->category_id = '0';
		}


		if(
			$oComplaint->category_id !== '0' &&
			$oComplaint->category_id !== ''
		) {

			$oCategory = TcComplaints_Entity_Category::getInstance($oComplaint->category_id);

			if($oCategory->hasChilds()) {

				$oDialog->setElement($oDialog->createRow($oGui->t('Unterkategorie'), 'select', array(
					'db_alias' => 'tc_cs',
					'db_column' => 'sub_category_id',
					'selection' => new SubCategory()
				)));

			}
		}

		if(
			$sType === 'accommodation' ||
			$sType === 'transfer' ||
			$sType === 'teacher'
		) {

			$iInquiryId = $oComplaint->inquiry_id;

			$bResult = false;
			if(!empty($iInquiryId)) {
				$oInquiry = \Ext_TS_Inquiry::getInstance($iInquiryId);
				$bResult = $this->checkInquiryHasProvider($oInquiry, $sType);
			}

			if($bResult) {
				$oDialog->setElement($oDialog->createRow($oGui->t('Anbieter'), 'select', array(
					'db_alias' => 'tc_cs',
					'db_column' => 'type_id',
					'selection' => new Area($sType)
				)));
			}

		}

		$oJoinContainer = $oDialog->createJoinedObjectContainer('history', array('min' => 1, 'max' => 1));

		if($sDialog === 'new') {

			$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Kommentar'), 'html', array(
				'db_alias' => 'tc_ch',
				'db_column' => 'comment',
				'required' => true,
				'advanced' => true
			)));

		}

		$aSubCategories = TcComplaints_Gui2_Data_ComplaintHistory::getState();

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Status'), 'select', array(
			'db_alias' => 'tc_ch',
			'db_column' => 'state',
			'select_options' => $aSubCategories,
			'required' => true
		)));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Nachhaken'), 'calendar', array(
			'db_alias' => 'tc_ch',
			'db_column' => 'followup',
			'format' => new \Ext_Thebing_Gui2_Format_Date(),
			'required' => false
		)));

		$oDialog->setElement($oJoinContainer);

	}

	/**
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param bool $sAdditional
	 * @param bool $bSave
	 * @return array
	 * @throws \Exception
	 */
	public function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {

		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		if (in_array($sAction, ['new', 'edit'])) {
			/*
			 * Muss gemacht werden, da ein Error von JSON geschmissen wird,
			 * wenn man eine Kategorie oder einen Bereich auswählt
			 */
			if(
				empty($aTransfer['error'][0]) &&
				$bSave === true
			) {
				$oComplaint = TsComplaint_Entity_Complaint::getInstance($aTransfer['data']['selectedRows'][0]);
				$aComplaintHistory  = $oComplaint->getComplaintHistory();
				$oComplaintHistory = end($aComplaintHistory);

				$oComplaint->latest_comment_id = $oComplaintHistory->id;
				$oComplaintHistory->comment_date = $oComplaint->complaint_date;

				$oComplaint->save();
				$oComplaintHistory->save();
			}
		}

		return $aTransfer;

	}

	/**
	 * Prüft ob die Buchung Anbieter hat
	 *
	 * @param \Ext_TS_Inquiry $oInquiry
	 * @param $sType
	 * @return bool
	 * @throws \Exception
	 */
	public function checkInquiryHasProvider(\Ext_TS_Inquiry $oInquiry, $sType) {

		switch($sType) {
			case 'accommodation':
				$aProviders = $oInquiry->getAccommodationProvider();
				break;
			case 'teacher':
				$aProviders = $oInquiry->getTuitionTeachers();
				break;
			case 'transfer':
				$aJourneys = $oInquiry->getJourneys();
				foreach($aJourneys as $oJourney) {
					$aJourneyTransfers = $oJourney->getUsedTransfers();
					$aJourneyTransferIds = array();
					foreach($aJourneyTransfers as $oJourneyTransfer) {
						$aJourneyTransferIds[] = $oJourneyTransfer->id;
					}
				}
				$aProviders = \Ext_TS_Inquiry_Journey_Transfer::getProvider($aJourneyTransferIds);
				break;
		}

		if(!empty($aProviders)) {
			return true;
		}

		return false;

	}

	/**
	 * @return mixed[]
	 */
	public static function getOrderby(){
		return [
			'created' => 'DESC',
		];
	}

	/**
	 * Filteroptionen für den Statusfilter.
	 *
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getStatusOptions(\Ext_Gui2 $oGui) {
		return [
			'due_follow_up' => $oGui->t('Nachhaken fällig'),
			'entered_follow_up' => $oGui->t('Nachhaken eingetragen'),
		];
	}

}
