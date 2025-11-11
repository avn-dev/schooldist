<?php

class Ext_Thebing_Tuition_Gui2_Template extends Ext_Thebing_Gui2_Data {

	protected function _getErrorMessage($sError, $sField = '', $sLabel = '', $sAction = null, $sAdditional = null) {
		switch($sError)
		{
			case 'INVALID_UNTIL':
				$sMessage = $this->t('Die Enduhrzeit muss nach der Startuhrzeit liegen.');
				break;
			case 'TEACHER_PAYMENTS_EXISTS':
				$sMessage = $this->t('Die Vorlage kann nicht verändert werden. Es existieren Lehrerzahlungen.');
				break;
			case 'ALLOCATION_FOUND':
				$sMessage = $this->t('Die Vorlage kann nicht verändert werden. Es existieren Zuweisungen in der Klassenplanung.');
				break;
			case 'ATTENDANCE_FOUND':
				$sMessage = $this->t('Die Vorlage kann nicht verändert werden. Es existieren Einträge in der Anwesenheit.');
				break;
			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;
	}
	
	static public function getOrderby() {
		
		return ['position' => 'ASC'];
	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oDialog			= $oGui->createDialog(L10N::t('Kursvorlage "{name}" editieren', $oGui->gui_description), L10N::t('Neue Kursvorlage anlegen', $oGui->gui_description));
		$oDialog->width		= 900;
		$oDialog->height	= 650;

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;

		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$aTimes				= $oSchool->getClassTimesOptions('format', 5);

		$oDialog->setElement($oDialog->createRow(L10N::t('Bezeichnung', $oGui->gui_description), 'input', array('db_alias' => 'ktt', 'db_column'=>'name','required' => 1)));
		$oDialog->setElement($oDialog->createRow(L10N::t('Zeit von', $oGui->gui_description), 'select', array('db_alias' => 'ktt', 'db_column'=>'from', 'select_options' => $aTimes, 'format'=>new Ext_Thebing_Gui2_Format_Time())));
		$oDialog->setElement($oDialog->createRow(L10N::t('Zeit bis', $oGui->gui_description), 'select', array('db_alias' => 'ktt', 'db_column'=>'until', 'select_options' => $aTimes, 'format'=>new Ext_Thebing_Gui2_Format_Time())));
		$oDialog->setElement($oDialog->createRow(L10N::t('Lektionen (Pro Tag)', $oGui->gui_description), 'input', array('db_alias' => 'ktt', 'db_column'=>'lessons','required' => 1, 'format'=>new Ext_Thebing_Gui2_Format_Float())));

		return $oDialog;
	}
	
	static public function getWhere(){
		
		$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		return ['school_id' => (int)$iSessionSchoolId,'custom' => 0];
	}

}