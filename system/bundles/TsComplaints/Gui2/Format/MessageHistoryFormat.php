<?php

namespace TsComplaints\Gui2\Format;

use \Ext_Gui2_View_Format_Abstract;
use \TcComplaints\Entity\ComplaintHistory;
use \TsComplaints\Entity\Complaint;
use \TcComplaints\Gui2\Data\ComplaintHistory as TcComplaint_Gui2_Data_ComplaintHistory;
use \TcComplaints\Gui2\Dialog\Complaints;

class MessageHistoryFormat extends Ext_Gui2_View_Format_Abstract {

	/**
	 * Setzt die Daten in das Template für den Nachrichtenverlauf der Beschwerden
	 *
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 * @throws \Exception
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oComplaintHistory = ComplaintHistory::getInstance($aResultData['id']);
		$oComplaint = Complaint::getInstance($oComplaintHistory->complaint_id);
		$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		if(!$oSchool->getId()) {
			$oSchool = \Ext_Thebing_School::getFirstSchool();
		}

		$sDate = \Ext_Thebing_Format::LocalDateTime($oComplaintHistory->created, $oSchool->getId());
		$aStates = TcComplaint_Gui2_Data_ComplaintHistory::getState(\System::getInterfaceLanguage());
		$sName = Complaints::findCommentAuthor($oComplaint, $oComplaintHistory);
		$oFormatDate = new \Ext_Thebing_Gui2_Format_Date();
		$sFollowUpDate = $oFormatDate->format($oComplaintHistory->followup);
		$sString = \L10N::t('Nachhaken:');
		$sHash = $this->_oGui->hash;

		$oSmarty = new \SmartyWrapper;

		$oSmarty->assign('hash', $sHash);
		$oSmarty->assign('id', $oComplaintHistory->getId());
		$oSmarty->assign('comment_date',$sDate);
		$oSmarty->assign('comment_state', $aStates[$oComplaintHistory->state]);
		$oSmarty->assign('comment_followup_string', $sString);
		$oSmarty->assign('comment_followup', $sFollowUpDate);
		$oSmarty->assign('name', $sName);
		$oSmarty->assign('comment', $oComplaintHistory->comment);

		$Output = $oSmarty->fetch(\Util::getDocumentRoot().'system/bundles/TcComplaints/Resources/Views/complaint_history.tpl');

		return $Output;

	}
}
