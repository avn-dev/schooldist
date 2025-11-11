<?php

class Ext_TC_Marketing_Feedback_Topic_Gui2_Data extends Ext_TC_Gui2_Data {
	
	/**
	 * Dialog um Themen anzulegen
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getDialog($oGui)
	{
		$oDialog = $oGui->createDialog($oGui->t('Thema editieren'), $oGui->t('Thema anlegen'));	

		$aLanguages = (array) Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
		
		$oDialog->setElement($oDialog->createI18NRow($oGui->t('Name'), array(
			'db_alias' => 'topics_tc_i18n', 
			'db_column'=> 'name', 
			'i18n_parent_column' => 'topic_id',
			'required' => true
		), $aLanguages));
		
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options   = true;
		$oDialog->save_bar_default_option = 'open';
		
		return $oDialog;
	}
	
	/**
	 * Dialog um Themen anzulegen
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getDeleteDialog($oGui)
	{
		$oDialog = $oGui->createDialog($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oDialog->height = 500;
		$oDialog->width = 600;
		
		$oDialog->sDialogIDTag = 'DELETE_';
		
		$oDialog->save_button = false;
		$oDialog->aButtons = array(
			array(
				'label'			=> $oGui->t('Löschen'), 
				'task'			=> 'saveDialog', 
				'request_data'	=> '', 
				'action'		=> 'deleteCheck'
			)
		);
		
		return $oDialog;
	}

	/**
	 * Liefert den Delete-Dialog für Themen
	 *
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 */
	public function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional = false) {
	
		if($sIconAction === 'deleteCheck'){
			
			$oDialog->aElements = array();
			
			$sMessage = false;
			
			$iTopic = (int) reset($aSelectedIds);
			$oTopic = Ext_TC_Marketing_Feedback_Topic::getInstance($iTopic);
			
			$sLabel = sprintf($this->t('Möchten Sie das Thema "%s" wirklich löschen?'), $oTopic->getName());
			
			// Prüfen, ob das Thema noch eienr Frage zugeordnet ist
			$aQuestions = $oTopic->getAllocatedQuestions();
			if(!empty($aQuestions)) {
				$sMessage .= $this->t('Dieses Thema ist bereits folgenden Fragen zugewiesen').':';
				
				$sMessage .= '<ul>';
				foreach($aQuestions as $oQuestion) {
					$sMessage .= '<li>'.$oQuestion->getQuestion().'</li>';
				}
				$sMessage .= '</ul>';
			}			
			
			$oNotification = $oDialog->createNotification($sLabel, $sMessage, 'hint', array());
			
			$oDialog->setElement($oNotification);

		}
		$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		
		return $aData;
	}
	
	/**
	 * Handles the ajax request
	 *
	 * @param array $_VARS
	 * @return void
	 */
	public function switchAjaxRequest($_VARS) {
		
		if(
			$_VARS['action'] == 'deleteCheck' &&
			$_VARS['task'] != 'openDialog'
		){
			$aSelectedIds = (array)$_VARS['id'];
			foreach($aSelectedIds as $iTopic){
				$oTopic = Ext_TC_Marketing_Feedback_Topic::getInstance($iTopic);
				$oTopic->delete();
			}
			$aTransfer = array();
			$aTransfer['action'] = 'closeDialogAndReloadTable';
			$aTransfer['data']['id'] = 'DELETE_'.(int)implode('_', $aSelectedIds);
			$aTransfer['error'] = array();
			echo json_encode($aTransfer);
		} else {
			parent::switchAjaxRequest($_VARS);
		}
		
	}
	
}