<?php


class Ext_Thebing_Absence_Gui2 extends Ext_Thebing_Gui2_Data {

    protected static $_aSavedAbsences = array();


    /**
	 * WRAPPER Ajax Request verarbeiten
	 * @param $_VARS
	 * @return unknown_type
	 */
	public function switchAjaxRequest($_VARS) {
		$aTransfer = array();

		$aTransfer = $this->_switchAjaxRequest($_VARS);

		if ($_VARS['task'] == 'deleteRow'){
			$iSelectedId = reset($aTransfer['data']['selectedRows']);
			$aTransfer['data']['id'] = 'ID_'.(int)$iSelectedId;
			$aTransfer['data']['save_id'] = $iSelectedId;
			$aTransfer['action'] = 'closeDialogAndReloadTable';
		}

		if(
			isset($_VARS['action']) &&
			isset($_VARS['task']) &&
			$_VARS['action'] == 'new' &&
			$_VARS['task'] == 'openDialog'
		) {

			$aIds = array();
			$oFirstDate = null;
			$oLastDate = null;
			foreach((array)$_VARS['cells'] as $sCell) {
				list($sDay, $iItemId) = explode("_", $sCell);
				$aIds[(int)$iItemId] = (int)$iItemId;
				if($oFirstDate) {
					$iCompare = $oFirstDate->compare($sDay, WDDate::DB_DATE);
					if($iCompare > 0) {
						$oFirstDate = new WDDate($sDay, WDDate::DB_DATE);
					}
				} else {
					$oFirstDate = new WDDate($sDay, WDDate::DB_DATE);
				}
				if($oLastDate) {
					$iCompare = $oLastDate->compare($sDay, WDDate::DB_DATE);
					if($iCompare < 0) {
						$oLastDate = new WDDate($sDay, WDDate::DB_DATE);
					}
				} else {
					$oLastDate = new WDDate($sDay, WDDate::DB_DATE);
				}
			}

			$iDays = $oLastDate->getDiff(WDDate::DAY, $oFirstDate);

			$aIds = array_values($aIds);
			$sFromDate = Ext_Thebing_Format::LocalDate($oFirstDate->get(WDDate::TIMESTAMP));
			$iDays = abs($iDays)+1;

			//$iWeekDay = Ext_Gui2_Util::getWeekDay(2, $oFirstDate->get(WDDate::DB_DATE));

			foreach((array)$aTransfer['data']['values'] as $iKey=>$aValue) {
				switch($aValue['db_column']) {
					case 'item_id':
						$aTransfer['data']['values'][$iKey]['value'] = (array)$aIds;
						break;
					case 'from':
						$aTransfer['data']['values'][$iKey]['value'] = (string)$sFromDate;
						//$aTransfer['data']['values'][$iKey]['week_day'] = (int)$iWeekDay;
						break;
					case 'days':
						$aTransfer['data']['values'][$iKey]['value'] = (int)$iDays;
						break;
					default:
						break;
				}
			}

		}elseif($_VARS['task']=='calculateUntil'){
			$aTransfer['action']		= 'refreshAbsenceData';
			$aTransfer['data']['id']	= $_VARS['dialog_id'];

			$sFrom		= $_VARS['from'];
			$iDays		= (int)$_VARS['days'];

			if($iDays>0)
			{
				$dFrom		= Ext_Thebing_Format::ConvertDate($sFrom,null,1);
				$dFrom2		= Ext_Thebing_Format::ConvertDate($sFrom,null);
				$oWdDate	= new WDDate();
				$oFormat	= new Ext_Thebing_Gui2_Format_Date();
				if($oWdDate->isDate($dFrom, WDDate::DB_DATE))
				{
					$oWdDate->set($dFrom, WDDate::DB_DATE);
					$oWdDate->add($iDays,  WDDate::DAY);
					$oWdDate->sub(1, WDDate::DAY);
					$iUntil = $oWdDate->get(WDDate::TIMESTAMP);
					$sUntil	= $oFormat->formatByValue($iUntil);
					$aTransfer['data']['refresh']['until']	= $sUntil;
					//$aTransfer['data']['refresh']['day']	= $oWdDate->get(WDDate::DAY_OF_WEEK);
				}
			}
		}elseif($_VARS['task']=='calculateDays'){
			$aTransfer['action']		= 'refreshAbsenceData';
			$aTransfer['data']['id']	= $_VARS['dialog_id'];

			$sFrom		= $_VARS['from'];
			$sUntil		= $_VARS['until'];
			$iDays		= (int)$_VARS['days'];

			$dFrom		= Ext_Thebing_Format::ConvertDate($sFrom,null,1);
			$dUntil		= Ext_Thebing_Format::ConvertDate($sUntil,null,1);

			$oWdDate	= new WDDate();
			if($oWdDate->isDate($dFrom, WDDate::DB_DATE) && $oWdDate->isDate($dUntil, WDDate::DB_DATE))
			{
				$oWdDate->set($dUntil, WDDate::DB_DATE);
				$iCompare = $oWdDate->compare($dFrom, WDDate::DB_DATE);
				if($iCompare>=0)
				{
					if($iDays>0)
					{
						$iDiff = (int)$oWdDate->getDiff(WDDate::DAY, $dFrom, WDDate::DB_DATE);
						$iDiff += 1;
						$aTransfer['data']['refresh']['days']	= $iDiff;
					}
				}
				else
				{
					$aTransfer['action']	= 'showError';
					$aTransfer['error']		= array($this->t('Das von-Datum darf nicht größer sein als das bis-Datum!'));

					echo json_encode($aTransfer);
					exit();
				}
			}
		}

		$aTransfer['item']			= $_SESSION['thebing']['absence'][($_VARS['hash'] ?? '')]['item'];
		$aTransfer['absence']		= 1;
		$_VARS['parent_gui_id']		= (array)($_VARS['parent_gui_id'] ?? '');
		$aTransfer['absence_parent']= reset($_VARS['parent_gui_id']);

		echo json_encode($aTransfer);

	}
    
    protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {
        
        DB::begin('Ext_Thebing_Absence_Gui2::saveEditDialogData');
        
        $aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
        
        if($bSave){
            
            $oAbsence = Ext_Thebing_Absence::getInstance($aTransfer['save_id']);

            if($oAbsence->item == 'accommodation') {

                $oRoom = Ext_Thebing_Accommodation_Room::getInstance($oAbsence->item_id);
                $aAllocations = $oRoom->getAllocationsByPeriod($oAbsence->from, $oAbsence->until);

                if(!empty($aAllocations)) {
                    if(empty($aTransfer['error'])){
                        $aTransfer['error'][] = array('message' => $this->t('Es ist ein Fehler aufgetreten!'), 'type' => 'error');
                    }
                    $aTransfer['error'][] = array('message' => $this->t('Bitte beachten Sie: In diesem Zeitraum gibt es bereits Zuordnungen!'), 'type' => 'error');
                }

            }

            self::$_aSavedAbsences[] = $oAbsence;
            
        }
        
        if(empty($aTransfer['error'])){
            DB::commit('Ext_Thebing_Absence_Gui2::saveEditDialogData');
        } else {
            DB::rollback('Ext_Thebing_Absence_Gui2::saveEditDialogData');
        }
        
        return $aTransfer;
    }

    /**
	 * Speichert den Dialog
	 * Wenn Abwesenheit für mehrere Einträge gespeichert wird, werden mehrere Einträge generiert
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){
		global $_VARS;

		$aTransfer = array();
        
		switch($sAction){
			case 'new':

                self::$_aSavedAbsences = array();

				$aTransfer['error'] = array();

				// Prüfen ob mehrere Verträge abgespeichert werden sollen
				$bMultiple = false;
				if(is_array($aData['item_id'])) {
					if(count($aData['item_id']) > 1) {
						$bMultiple = true;
					}
				} else {
					$aData['item_id'] = array($aData['item_id']);
				}

				// Alle Items speichern
				$aVersionIds = array();

				$sIconKey	= self::getIconKey($sAction, $sAdditional);
				$oDialog	= $this->_getDialog($sIconKey);
				
				$bShowSkipErrors = false;

				foreach((array)$aData['item_id'] as $iItemId) {

					$aDataTemp = $aData;
					$aDataTemp['item_id'] = $iItemId;

					$aItemTransfer = $this->saveEditDialogData((array)$aSelectedIds, $aDataTemp, $bSave, $sAction);

					if(
						isset($aItemTransfer['data']) &&
						isset($aItemTransfer['data']['show_skip_errors_checkbox']) &&
						$aItemTransfer['data']['show_skip_errors_checkbox'] == 1
					){
						$bShowSkipErrors = true;
					}

					if(!empty($aItemTransfer['error'])) {
						
						$aTransfer['error'] = array_merge((array)$aTransfer['error'], (array)$aItemTransfer['error']);
						
					}
                   
					// Resetten, da sonst ein update auf das obj. gemacht wird bei mehreren durchläufen anstelle von einem neuen Eintrag
					unset($this->oWDBasic);
					$oDialog->getDataObject()->resetWDBasicObject();

				}

				if(
					!empty($aTransfer['error'])
				)
				{
					foreach(self::$_aSavedAbsences as $oSavedAbsence)
					{
						$oSavedAbsence->delete();
					}
					
					$aItemTransfer['data']['id'] = $aItemTransfer['dialog_id_tag'].'0';
					
					if(
						$bShowSkipErrors
					)
					{
						$aItemTransfer['data']['show_skip_errors_checkbox'] = 1;
					}
				}

				// Infos in Transfer Array schreiben
				$aTransfer['action']		= $aItemTransfer['action'];
				$aTransfer['dialog_id_tag'] = $aItemTransfer['dialog_id_tag'];
				$aTransfer['data']			= $aItemTransfer['data'];
				$aTransfer['item']			= $_SESSION['thebing']['absence'][$_VARS['hash']]['item'];
				$aTransfer['absence']		= 1;
				$_VARS['parent_gui_id']		= (array)$_VARS['parent_gui_id'];
				$aTransfer['absence_parent']= reset($_VARS['parent_gui_id']);

				if(!empty($aSelectedIds)) {
					$aTransfer['save_id']	= reset($aSelectedIds);
				}

				// Wenn mehrere Verträge gespeichert wurden, Dialog schliessen
				if(
					$bMultiple &&
					empty($aTransfer['error'])
				) {
					$aTransfer['success_message'] = sprintf(L10N::t('Die Abwesenheit wurde erfolgreich gespeichert!', $this->_oGui->gui_description), $sUrl);
					$aTransfer['data']['options']['close_after_save'] = true;
				}
                
                self::$_aSavedAbsences = array();

				break;

			default:
				$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
				break;

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aTransfer;

	}


	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)
	{
		switch($sError)
		{
			case 'INVALID_DAYS_NUMBER':
			{
				$sMessage = L10N::t('Bitte geben Sie eine positive Anzahl an Tagen ein.', $this->_oGui->gui_description);

				return $sMessage;

				break;
			}
			case 'teacher_absence_allocation_found':
			{	
				$sMessage = $this->t('Der Lehrer "%s" ist in diesem Zeitraum zugewiesen');
				
				$oTeacher = Ext_Thebing_Teacher::getInstance($sField);
				
				$sTeacherName = $oTeacher->name;
				
				$sMessage = str_replace('%s', $sTeacherName, $sMessage);


				return $sMessage;
			}	
			default:
				return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}
	}
}