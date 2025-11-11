<?php

namespace TsComplaints\Gui2\Data;

use \TcComplaints\Gui2\Data\ComplaintHistory as TcComplaints_Gui2_Data_ComplaintHistory;
use \TsComplaints\Entity\Complaint as TsComplaints_Entity_Complaint;
use \Ext_TC_Util;

class ComplaintHistory extends TcComplaints_Gui2_Data_ComplaintHistory {

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {
		$oDialog = $oGui->createDialog($oGui->t('Kommentar "{name}" editieren'), $oGui->t('Kommentar anlegen'));
		return $oDialog;
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

		global $_VARS;

		if($aData['comment_type']['tc_ch'] === 'agency_commentary') {

			$iComplaintId = $_VARS['parent_gui_id'][0];
			$oComplaint = TsComplaints_Entity_Complaint::getInstance($iComplaintId);
			$oInquiry = \Ext_TS_Inquiry::getInstance($oComplaint->inquiry_id);

			$oAgency = $oInquiry->getAgency();

			if(empty($oAgency)) {

				$aTransfer['action'] = 'showError';
				$aTransfer['error'][0]['message'] = \L10N::t('Speichervorgang fehlgeschlagen, die Buchung hat keine Agentur.', $this->_oGui->gui_description);

				$bSave = false;

			}

		}

		if($bSave === true) {

			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true);

			$iComplaintId = $_VARS['parent_gui_id'][0];
			$oComplaint = TsComplaints_Entity_Complaint::getInstance($iComplaintId);
			$oComplaint->latest_comment_id = $aTransfer['save_id'];
			$oComplaint->save();

		}

		return $aTransfer;

	}

	/**
	 * @param string $sIconAction
	 * @param array $aSelectedIds
	 * @param bool $iTab
	 * @param bool $sAdditional
	 * @param bool $bSaveSuccess
	 * @return array
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {

		$sIconKey = self::getIconKey($sIconAction, $sAdditional);

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

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		return $aData;
	}

	/**
	 * @param \Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	protected function getDialogContent(&$oDialogData, $aSelectedIds) {

		$oDialogData->aElements = array();

		$oWDBasic	= $this->_getWDBasicObject($aSelectedIds);
		$oComplaint = TsComplaints_Entity_Complaint::getInstance($oWDBasic->complaint_id);
		$oInquiry = \Ext_TS_Inquiry::getInstance($oComplaint->inquiry_id);

		// Das aktuelle Datum soll angezeigt werden
		$oDateTime = new \DateTime();
		$sDate = $oDateTime->format('Y-m-d');

		$oDialogData->setElement($oDialogData->createRow($this->t('Datum des Kommentar'), 'calendar', array(
			'db_alias' => 'tc_ch',
			'db_column' => 'comment_date',
			'default_value' => $sDate,
			'format' => new \Ext_Thebing_Gui2_Format_Date(),
			'required' => true
		)));

		$aCommentaryTypes = self::getCommentaryType(\System::getInterfaceLanguage());

		$oInquiry->getSchool();

		if(!$oInquiry->hasAgency()) {
			unset($aCommentaryTypes['agency_commentary']);
		}

		if($oComplaint->type_id === "0") {
			unset($aCommentaryTypes['provider_commentary']);
		}

		$oDiv = $oDialogData->createRow($this->t('Kommentarart'), 'select', array(
			'db_alias' => 'tc_ch',
			'db_column' => 'comment_type',
			'select_options' => Ext_TC_Util::addEmptyItem($aCommentaryTypes),
			'required' => true
		));
		$oDialogData->setElement($oDiv);

		$oDiv = $oDialogData->createRow($this->t('Kommentar'), 'html', array(
			'db_alias' => 'tc_ch',
			'db_column' => 'comment',
			'required' => true,
			'advanced' => true
		));
		$oDialogData->setElement($oDiv);

		$aStates = TcComplaints_Gui2_Data_ComplaintHistory::getState(\System::getInterfaceLanguage());

		$oDiv = $oDialogData->createRow($this->t('Status'), 'select', array(
			'db_alias' => 'tc_ch',
			'db_column' => 'state',
			'select_options' => Ext_TC_Util::addEmptyItem($aStates),
			'required' => true
		));
		$oDialogData->setElement($oDiv);

		$oDiv = $oDialogData->createRow($this->t('Nachhaken'), 'calendar', array(
			'db_alias' => 'tc_ch',
			'db_column' => 'followup',
			'format' => new \Ext_Thebing_Gui2_Format_Date(),
			'required' => false
		));
		$oDialogData->setElement($oDiv);

		$oDiv = $oDialogData->createRow($this->t('Zuordnen an'), 'select', [
			'db_alias' => 'tc_ch',
			'db_column' => 'assigned_to',
			'select_options' => \Ext_TS_Marketing_Feedback_Questionary_Process_Gui2_Data::getUserSelectOptions(false),
			'required' => false
		]);
		$oDialogData->setElement($oDiv);

	}

}