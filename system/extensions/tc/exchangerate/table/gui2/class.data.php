<?php

class Ext_TC_Exchangerate_Table_Gui2_Data extends Ext_TC_Gui2_Data {
	
	/**
	 * Dialog um Wechselkurstabellen anzulegen
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getDialog($oGui)
	{
		
		$oDialog = $oGui->createDialog($oGui->t('Wechselkurstabelle "{name}" editieren'), $oGui->t('Wechselkurstabelle anlegen'));

		// Name

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'tc_et',
			'db_column' => 'name',
			'required' => true
		)));

		// Aktualisierungszeitpunkt

		$oDialog->setElement($oDialog->createRow($oGui->t('Aktualisierungszeitpunkt'), 'select', array(
			'db_column'			=> 'update_time',
			'db_alias'			=> 'tc_ets',
			'text_after'        => $oGui->t('Uhr'),
			'select_options'	=> Ext_TC_Util::getHours(),
			'style'             => 'width:100px;'
		)));

		$oDialog->height = 300;
		$oDialog->width = 800;

		return $oDialog;
	}

	/**
	 * Dialog für die Übersicht der Wechselkurs
	 * Icon: "Wechselkurse ansehen"
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getExchangerateDialog($oGui)
	{

		$oDialog = $oGui->createDialog($oGui->t('Wechselkurse für "{name}" ansehen'), $oGui->t('Wechselkurse für "{name}" ansehen'));

		return $oDialog;

	}	
	
	/**
	 * see parent
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @param string $sAdditional
	 * @return array 
	 */
	public function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional = false) {
		
		if($sIconAction === 'exchangerate_view')
		{
			$oGuiFactory = new Ext_Gui2_Factory('Tc_exchangerate_tables_overview');
			$oExchangerateGui = $oGuiFactory->createGui('', $this->_oGui);
			
			$iParentId = (int) reset($aSelectedIds);
			$oExchangerateGui->setOption('parent_gui_id', $iParentId);

			$oDialog->setElement($oExchangerateGui);		
		}

		$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		
		return $aData;
	}
	
	/**
	 * liest den Inhalt einer XML-Datei aus und gibt diesen aus
	 * @todo Mögliche Fehler einzeln abfangen und konkrete Fehlermeldung ausgeben
	 * @param array $_VARS 
	 */	
	public function switchAjaxRequest($_VARS) {
		
		if($_VARS['action'] == 'exchangerate_update') {

			/* @var $oExchangerate Ext_TC_Exchangerate_Table*/
			$oExchangerate = $this->oWDBasic;	
			if(!$oExchangerate){
				$oExchangerate = $this->_getWDBasicObject($_VARS['id']);
			}
						
			$aErrors = array();		

			try {
				$bUpdate = $oExchangerate->update();
			} catch (Exception $e) {
				__pout($e);
				$bUpdate = false;
			}
			
			// Wenn beim Aktualisierungsvorgang ein Fehler aufgetreten ist
			if($bUpdate === false) {
				$aErrors[0] = $this->t('Aktualisierung fehlgeschlagen');
				$aErrors[1] = $this->t('Die Wechselkurse konnten nicht aktualisiert werden.');
			}			
			
			if(empty($aErrors)){
				$aTransfer['action']							= 'showSuccess';
				$aTransfer['message']							= $this->t('Die Wechselkurse wurden erfolgreich aktualisiert.');
				$aTransfer['success_title']						= $this->t('Aktualisierung erfolgreich');
			} else {
				$aTransfer['action']							= 'showError';
				$aTransfer['error']								= $aErrors;
			}

			$aTransfer = json_encode($aTransfer);

			echo $aTransfer;
			
		} else {
			parent::switchAjaxRequest($_VARS);
		}

	}	
	
}
