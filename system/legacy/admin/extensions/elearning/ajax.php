<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Access_Backend::checkAccess("elearning_exam");

$arrTransfer = array();

if($_VARS['task'] == 'positions') {
	
	Access_Backend::checkAccess("elearning_exam_contents");
	
	$aSorted = json_decode($_VARS['sort']);
	$sItem = $_VARS['item'];

	switch($sItem) {
		case 'groups':

			$oExam = new Ext_Elearning_Exam((int)$_VARS['exam_id']);
			$oExam->setChildPositions($aSorted);
		
			break;
		case 'questions':

			$oGroup = new Ext_Elearning_Exam_Group((int)$_VARS['group_id']);
			$oGroup->setChildPositions($aSorted);
		
			break;
		case 'answers':

			$oQuestion = new Ext_Elearning_Exam_Question((int)$_VARS['question_id']);
			$oQuestion->setChildPositions($aSorted);
		
			break;
		default:
			break;
	}
	
} elseif($_VARS['task'] == 'participant_invite') {
	
	Access_Backend::checkAccess("elearning_exam_participants");
	
	$oExam = new Ext_Elearning_Exam((int)$_VARS['exam_id']);
	$aReturn = $oExam->sendInvitaitions('single_invitation', false, (int)$_VARS['participant_id']);

	if(
		$aReturn['success'] == 1 && 
		$aReturn['success'] == $aReturn['total']
	) {
		$arrTransfer = L10N::t('Die Einladung wurde erfolgreich versendet!', 'E-Learning');
	} else {
		$arrTransfer = L10N::t('Die Einladung wurde nicht erfolgreich versendet!', 'E-Learning');
	}

} elseif($_VARS['task'] == 'participant_result') {
	
	Access_Backend::checkAccess("elearning_exam_participants");
	
	$oExam = new Ext_Elearning_Exam((int)$_VARS['exam_id']);
	$oParticipant = $oExam->getParticipant((int)$_VARS['participant_id']);
	$aData = $oParticipant->getData();
	$aReport = $oParticipant->getReport($_VARS['last_result_id']);
	$arrTransfer['data'] = $aData;
	$arrTransfer['date'] = (string)$aReport['date'];
	$arrTransfer['state'] = (int)$aReport['state'];
	$arrTransfer['report'] = (array)$aReport['results'];
	$arrTransfer['result_ids'] = (array)$aReport['result_ids'];

} elseif($_VARS['task'] == 'get_participants') {

	Access_Backend::checkAccess("elearning_exam_participants");
	
	$oExam = new Ext_Elearning_Exam((int)$_VARS['exam_id']);
	$aParticipants = $oExam->getParticipants($_VARS['group'], $_VARS['search']);
	
	if(count($aParticipants) > 1000) {
		$aParticipants = array_slice($aParticipants, 0, 1000);
	}
	
	$arrTransfer['data'] = $aParticipants;

} elseif($_VARS['task'] == 'delete_participants') {

	Access_Backend::checkAccess("elearning_exam_participants");
	
	$oExam = new Ext_Elearning_Exam((int)$_VARS['exam_id']);
	$oParticipant = new Ext_Elearning_Exam_Participants($oExam, (int)$_VARS['participant_id']);
	$oParticipant->delete();
	$arrTransfer['data'] = array();

}

$strJson = json_encode($arrTransfer);
echo $strJson;