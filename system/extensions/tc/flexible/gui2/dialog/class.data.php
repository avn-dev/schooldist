<?php

class Ext_TC_Flexible_Gui2_Dialog_Data extends Ext_Gui2_Dialog_Data {

	/**
	 * getEdit musste ich hier ableiten, da die Kompabilität zwischen $this->_oWBasic & $this->_oGui->getDataObject()->oWDBasic
	 * nicht richtig funktioniert, wenn man eine nicht gespeicherte WDBasic Instanz in einer Ableitung vor parent manipuliert,
	 * in unserem Fall setzen wir die section_id je nach Fall in das Objekt rein
	 * 
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param string $sAdditional
	 * @return array 
	 */
	public function getEdit($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$iSectionId = $this->_getSectionId();

		if($iSectionId > 0) {
			// Objekt nur neu holen wenn noch nicht vorhanden
			if(
				is_null($this->_oWDBasic) ||
				!($this->_oWDBasic instanceof WDBasic)
			) {
				$this->_getWDBasicObject($aSelectedIds);
			}

			$this->_oWDBasic->section_id = $iSectionId;
		}

		$aData = (array)parent::getEdit($aSelectedIds, $aSaveData, $sAdditional);
		
		if($iSectionId > 0)
		{
			foreach($aData as &$aSaveField)
			{
				if($aSaveField['db_column'] == 'section_id')
				{
					$aSaveField['value'] = $iSectionId;
				}
			}	
		}

		return $aData;

	}
	
	/**
	 * Die jetzige Sektion anhand des Filters oder Dialoges ermitteln
	 * 
	 * @return int 
	 */
	protected function _getSectionId() {

		$iSectionId			= $this->_oGui->getDataObject()->getSectionId();

		return $iSectionId;
	}

	public function saveEdit(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		/* @var $oFlexibilityField Ext_TC_Flexibility */
		$oFlexibilityField = $this->_getWDBasicObject($aSelectedIds);
		$iI18N = (int)$oFlexibilityField->i18n;
		
		if(empty($_VARS['ignore_errors'])) {

			$aErrors = array();

			// Hinweis wenn man Umstellungen vornimmt, die einen Verlust der Informationen zur Folge hat.
			if(
				$oFlexibilityField->id > 0 &&
				$oFlexibilityField->type != $aSaveData['type']
			) {

				$aChangeable = array(
					0, // Text
					1, // Textarea
					6 // HTML
				);

				if(
					!in_array($oFlexibilityField->type, $aChangeable) ||
					!in_array($aSaveData['type'], $aChangeable)
				) {
					$aErrors[] = 'Bitte beachten Sie, dass bei dem Wechsel des Typs die gespeicherten Werte nicht mehr (vollständig) zur Verfügung stehen!';
				}

			}

			if(
				$iI18N === 1 &&
				$iI18N !== (int)$aSaveData['i18n']
			) {
				$aErrors[] = 'Bitte beachten Sie, dass bei dem Entfernen der Mehrsprachigkeit die gespeicherten Werte nicht mehr (vollständig) zur Verfügung stehen!';
			}

			if(!empty($aErrors)) {

				foreach($aErrors as &$mError) {
					$mError = array(
						'message' => $this->_oGui->t($mError),
						'type' => 'hint'
					);
				}

				$aTransfer = array(
					'action' => 'saveDialogCallback',
					'data' => array(
						'show_skip_errors_checkbox' => 1
					),
					'error' => $aErrors
				);
				return $aTransfer;

			}			
			
		}

		$aTransfer = parent::saveEdit($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		// Mehrsprachigkeit verändert?
		if($iI18N !== (int)$oFlexibilityField->i18n) {
			$oFlexibilityField->convertI18NValues();
		}

		return $aTransfer;
	}
	
}