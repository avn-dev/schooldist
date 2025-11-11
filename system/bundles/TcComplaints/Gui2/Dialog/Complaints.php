<?php

namespace TcComplaints\Gui2\Dialog;

use \Ext_Gui2_Dialog_Data;
use \TcComplaints\Entity\Complaint;
use \TcComplaints\Entity\ComplaintHistory;

class Complaints extends Ext_Gui2_Dialog_Data {

	/**
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 * @throws \Exception
	 */
	public function getHtml($sAction, $aSelectedIds, $sAdditional = false) {

		$sArea = str_replace('_complaint', '', $sAction);
		/** @var \Ext_Thebing_Accommodation|\Ext_Thebing_Teacher|\Ext_Thebing_Pickup_Company $oObject */
		$oObject = \Ext_TC_Factory::executeStatic('\TcComplaints\Gui2\Data\Complaint', 'getObject', array($aSelectedIds, $sArea));
		$oComplaintRepo = Complaint::getRepository();
		$aComplaints = $oComplaintRepo->getAllComplaintsViaAreaAndId($oObject, $sArea);

		$this->_oDialog->aElements = array();

		if(empty($aComplaints)) {
			throw new \Exception('There were no complaints found for %s', 0, (string)$oObject);
		}

		foreach($aComplaints as $oComplaint) {
			$this->_oDialog->setElement(
				$this->getAccordion($oComplaint)
			);
		}

		$aData = $this->_oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		$aData = $this->setDialogTitle($aData, $oObject);

		return $aData;

	}

	/**
	 * Findet den Namen zum Autor des Kommentars
	 *
	 * @param Complaint $oComplaint
	 * @param ComplaintHistory $oComplaintHistory
	 * @return object
	 */
	public static function findCommentAuthor(Complaint $oComplaint, ComplaintHistory $oComplaintHistory) {

		$sName = \Ext_TC_Factory::executeStatic('\TcComplaints\Gui2\Data\Complaint', 'getCommentAuthor', array($oComplaint, $oComplaintHistory));

		return $sName;
	}

	/**
	 * Setzt das Accordion für die Beschwerden
	 *
	 * @param Complaint $oComplaint
	 * @return \Ext_Gui2_Dialog_Accordion
	 * @throws \Exception
	 */
	public function getAccordion(Complaint $oComplaint) {

		$aComplaintHistory = \Ext_TC_Factory::executeStatic('\TcComplaints\Entity\ComplaintHistory', 'getAllComplaintsCommentsViaComplaint', array($oComplaint));
		$sName = \Ext_TC_Factory::executeStatic('\TcComplaints\Gui2\Data\Complaint', 'getNameOfTraveller', array($oComplaint->inquiry_id));

		$oAccordion = new \Ext_Gui2_Dialog_Accordion('accordion_complaints_' . $oComplaint->id);
		$oAccordionElement = $oAccordion->createElement($sName);

		$sContent = '';
		foreach($aComplaintHistory as $oComplaintHistory) {

			$oDivElement = $this->createElement($oComplaint, $oComplaintHistory, $this->_oDialog);
			$sContent .= $oDivElement->generateHTML();
			$oAccordionElement->setContent($sContent);

		}

		$oAccordion->addElement($oAccordionElement);

		return $oAccordion;
	}

	/**
	 * Erzeugt ein Div von jeder Beschwerde in denen die Kinder-Elemente
	 * hin geschrieben werden. (Ein Div pro Beschwerde)
	 *
	 * @param Complaint $oComplaint
	 * @param ComplaintHistory $oComplaintHistory
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @return \Ext_Gui2_Html_Div
	 */
	public function createElement(Complaint $oComplaint, ComplaintHistory $oComplaintHistory, \Ext_Gui2_Dialog $oDialog) {

		$oDiv = $oDialog->create('div');
		$sTemplate = $this->loadTemplate($oComplaint, $oComplaintHistory);
		$oDiv->setElement($sTemplate);

		return $oDiv;

	}

	/**
	 * Holt sich das Template der View, da der Dialog das Gleiche Design verwendet.
	 * Alle Variablen im Template werden ausgewertet.
	 *
	 * @param Complaint $oComplaint
	 * @param ComplaintHistory $oComplaintHistory
	 * @return string
	 * @throws \Exception
	 */
	public function loadTemplate(Complaint $oComplaint, ComplaintHistory $oComplaintHistory) {

		$aStates = \TcComplaints\Gui2\Data\ComplaintHistory::getState(\System::getInterfaceLanguage());
		$oFormatDate = new \Ext_Gui2_View_Format_Date();
		$sDate = $oFormatDate->format($oComplaintHistory->comment_date);

		$oSmarty = new \SmartyWrapper;

		$oSmarty->assign('id', $oComplaintHistory->getId());
		$oSmarty->assign('comment_date',$sDate);
		$oSmarty->assign('comment_state', $aStates[$oComplaintHistory->state]);
		$oSmarty->assign('name', self::findCommentAuthor($oComplaint, $oComplaintHistory));
		$oSmarty->assign('comment', $oComplaintHistory->comment);

		$sOutput = $oSmarty->fetch(\Util::getDocumentRoot().'system/bundles/TcComplaints/Resources/Views/dialog_complaint_history.tpl');

		return $sOutput;

	}

	/**
	 * Setzt den Titel des aktuellen Dialoges
	 *
	 * @param array $aData
	 * @param \Ext_Thebing_Accommodation|\Ext_Thebing_Teacher|\Ext_Thebing_Pickup_Company $oObject
	 *
	 * @return array
	 */
	public function setDialogTitle($aData, $oObject) {
		$aData['title'] = sprintf($aData['title'], (string)$oObject);
		return $aData;
	}

}