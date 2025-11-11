<?php

class Ext_TC_Communication_Message_Notice_Gui2_Data extends Ext_TC_Communication_Gui2_Data {

	/**
	 * Dialog zum Anlegen einer neuen Notiz in der Kommunikation
	 *
	 * @param Ext_TC_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog(Ext_TC_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Notiz bearbeiten'), $oGui->t('Notiz anlegen'));
		$oSelection = Ext_TC_Factory::getObject('Ext_TC_Communication_Message_Notice_Gui2_Selection_Correspondant');

		$oDialog->aOptions['close_after_save'] = 1;

		$oDialog->setElement($oDialog->createRow($oGui->t('Art'), 'select', array(
			'db_alias' => 'attribute',
			'db_column' => 'notice_type',
			'select_options' => static::getNoticeTypeSelectOptions(),
			'default_value' => 'call',
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Betreff'), 'input', array(
			'db_column' => 'subject'
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Memo'), 'html', array(
			'db_column' => 'content',
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Richtung'), 'select', array(
			'db_column' => 'direction',
			'select_options' => Ext_TC_Util::addEmptyItem(static::getDirectionSelectOptions()),
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Gesprächspartner'), 'select', array(
			'db_alias' => 'attribute',
			'db_column' => 'notice_correspondant_key',
			'selection' => $oSelection
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Person'), 'input', array(
			'db_alias' => 'attribute',
			'db_column' => 'notice_correspondant_value',
			'required' => true
		)));

		$dNow = new \DateTime();

		$oDialog->setElement($oDialog->createRow($oGui->t('Datum'), 'calendar', array(
			'db_alias' => 'attribute',
			'db_column' => 'date_date',
			'format' => Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date'),
			'default_value' => $dNow->format('Y-m-d'),
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Uhrzeit'), 'input', array(
			'db_alias' => 'attribute',
			'db_column' => 'date_time',
			'format' => Ext_TC_Factory::getObject('Ext_Gui2_View_Format_Time'),
			'required' => true,
			'default_value' => $dNow->format('H:i'),
			'style' => 'width: 50px;'
		)));

		return $oDialog;
	}

	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $aAdditional = false) {
		
		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $aAdditional);

		foreach($aData as &$aField) {

			// Gesprächspartner-Feld enkodieren
			if(
				$aField['db_column'] === 'notice_correspondant_key' &&
				in_array($aField['value'], static::getEncodedCorrespondantFields())
			) {
				// Value zu Key suchen
				foreach($aData as &$aField2) {
					if($aField2['db_column'] === 'notice_correspondant_value') {
						$iContactId = $aField2['value'];
						$aField2['value'] = '';
					}
				}

				if(empty($iContactId)) {
					throw new UnexpectedValueException(print_r(array($aSelectedIds, $aField, $aField2), true));
				}

				$aField['value'] .= '_'.$iContactId;
			}
			
			// Für das Auffinden der Formatklasse in $aSaveData
			foreach($aSaveData as $aSaveDataField) {
				if($aSaveDataField['db_column'] === $aField['db_column']) {
					$aSaveField = $aSaveDataField;
					break;
				}
			}

			if($aAdditional['action'] !== 'edit') {
				if($aField['db_column'] === 'notice_date') {
					$aField['value'] .= static::executeFormat($aSaveField['format'], date('Y-m-d'));
				} elseif($aField['db_column'] === 'notice_time') {
					$aField['value'] .= static::executeFormat($aSaveField['format'], date('H:i'));
				}
			}

		}

		return $aData;
	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $aAction='edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		$this->_getWDBasicObject($aSelectedIds);

		$aParentIds = (array)$_VARS['parent_gui_id'];
		$iParentId = reset($aParentIds);
		$oParentGuiObject = $this->_getParentGui()->getWDBasic($iParentId);

		if($aAction['action'] === 'new') {

			// Relation setzen für Notiz
			/** @var Ext_TC_Communication $oCommunication */
			$oCommunication = $this->_oGui->getOption('communication');
			$oCommunication->setRelations(array('selected_object' => $oParentGuiObject), $this->oWDBasic);

			// Die creator_id steht nicht in der Tabelle, sondern in einer Jointable
			$oCurrentUser = System::getCurrentUser();
			$this->oWDBasic->creator_id = $oCurrentUser->id;

		}

		$aData = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $aAction, $bPrepareOpenDialog);
		
		$aErrors = $aData['error'];
		if(empty($aErrors)) {
			// Gesprächspartner-Feld dekodieren, da Werte im Key kodiert sind
			foreach(static::getEncodedCorrespondantFields() as $sField) {
				if(mb_strpos($this->oWDBasic->notice_correspondant_key, $sField.'_') !== false) {
					$aParts = explode('_', $this->oWDBasic->notice_correspondant_key);
					$iContactId = (int)array_pop($aParts);

					if(empty($iContactId)) {
						throw new UnexpectedValueException(print_r(array(get_class($this->oWDBasic), $this->oWDBasic->id, $this->oWDBasic->notice_correspondant_key), true));
					}

					$this->oWDBasic->notice_correspondant_key = join('_', $aParts);
					$this->oWDBasic->notice_correspondant_value = $iContactId;
				}
			}

			$this->oWDBasic->save();
		} else {
			
			$aError = [
				L10N::t('Fehler beim Speichern'),
				[
					'type' => 'error',
					'input' => [
						'dbcolumn' => 'date_date',
						'dbalias' => 'attribute'
					],
					'message' => L10N::t('Das Format in Feld Datum ist nicht korrekt.'),
				],
				[
					'type' => 'error',
					'input' => [
						'dbcolumn' => 'date_time',
						'dbalias' => 'attribute'
					],
					'message' => L10N::t('Das Format in Feld Uhrzeit ist nicht korrekt.'),
				]
			];
			$aData['error'] = $aError;
		}

		return $aData;
	}

	/**
	 * Options für Notiztyp
	 * @return array
	 */
	public static function getNoticeTypeSelectOptions() {
		return array(
			'call' => Ext_TC_Communication::t('Anruf'),
			'letter' => Ext_TC_Communication::t('Brief'),
			'email' => Ext_TC_Communication::t('E-Mail'),
			'fax' => Ext_TC_Communication::t('Fax'),
			'conversation' => Ext_TC_Communication::t('Persönliches Gespräch')
		);
	}

	/**
	 * Options für Kommunikationsrichtung
	 * @return array
	 */
	public static function getDirectionSelectOptions() {
		return array(
			'in' => Ext_TC_Communication::t('eingehend'),
			'out' => Ext_TC_Communication::t('ausgehend'),
		);
	}

	public function switchAjaxRequest($_VARS, $bReturn = false) {

		$aTransfer = parent::switchAjaxRequest($_VARS, true);

		$aTransfer['aNoticeHideCorrespondantValue'] = static::getEncodedCorrespondantFields();

		echo json_encode($aTransfer);

	}

	/**
	 * Liefert ein Array aller Felder, die enkodierte Werte haben
	 * Wird im JS und in der Ext_TA_Communication_Message verwendet.
	 *
	 * @return array
	 */
	public static function getEncodedCorrespondantFields() {
		return array();
	}

	/**
	 * Hier muss die Klasse der Elternliste gesetzt werden, damit die Notizen korrekt zugeordnet werden
	 * 
	 * @param string $sIconKey
	 * @return Ext_Gui2_Dialog
	 */
	public function _getDialog($sIconKey) {

		$oDialog = parent::_getDialog($sIconKey);
		
		if($oDialog) {
			
			$aWhereData = $this->_oGui->_aTableData['where'];

			if(isset($aWhereData['relations.relation'])) {

				$sClass = null;

				// siehe Ext_TC_Communication_Gui2_Data::createGui()
				if (is_string($aWhereData['relations.relation'])) {
					$sClass = $aWhereData['relations.relation'];
				} else if (is_array($aWhereData['relations.relation'])) {
					$sClass = end($aWhereData['relations.relation'][1]);
				}

				if ($sClass === null) {
					throw new \RuntimeException('Cannot extract relation class out of gui where part');
				}

				$oDialog->getDataObject()->addInitData('relations.relation', $sClass);

			}
			
		}
		
		return $oDialog;
	}
	
}
