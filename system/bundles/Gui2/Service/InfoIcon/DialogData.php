<?php

namespace Gui2\Service\InfoIcon;

class DialogData extends \Ext_Gui2_Dialog_Data {

	protected function getFieldType(): string {
		$sFieldType = $this->_oDialog->getOption('field_type', 'html');
		if (!in_array($sFieldType, ['html', 'input'])) {
			throw new \InvalidArgumentException(sprintf('Invalid field type "%s" for info icon dialog (only html, input)', $sFieldType));
		}
		return $sFieldType;
	}

	protected function getLanguages(): array {
		$aDefault = array_keys(\System::getBackendLanguages(true));
		return (array)$this->_oDialog->getOption('languages', $aDefault);
	}

	protected function getRowKey() {
		return $this->_oDialog->getOption('row_key');
	}
	
	protected function getParentGuiHash() {
		return $this->_oDialog->getOption('row_key_data', [])['gui_hash'];
	}
	
	protected function getParentDialogId() {
		return $this->_oDialog->getOption('row_key_data', [])['dialog_id'];
	}
	
	protected function getParentDialogField() {
		return $this->_oDialog->getOption('row_key_data', [])['field'];
	}
	
	public function getHtml($sAction, $aSelectedIds, $sAdditional = false) {

		$sRowKey = $this->getRowKey();

		$aLanguagesData = \System::getBackendLanguages(true);

		$oInfoText = \Gui2\Entity\InfoText::query()
			->where('gui_hash', $this->getParentGuiHash())
			->where('dialog_id', $this->getParentDialogId())
			->where('field', $this->getParentDialogField())
			->first();

		//$iPrivate = (!is_null($oInfoText)) ? (int) $oInfoText->private : 0;

		// TODO Wird praktisch nicht benutzt und kann mit der Implementierung auch lediglich nur auf test.school funktionieren
		// Über privat wurde geregelt ob das Info-Icon für einen Kunden angezeigt wird
		/*$this->_oDialog->setElement($this->_oDialog->createRow(\L10N::t('Beim Kunden ausblenden', \Ext_Gui2::$sAllGuiListL10N), 'checkbox', [
			'db_column' => 'private',
			'db_alias' => $sRowKey,
			'default_value' => $iPrivate
		]));*/
		
		// Für jede Backendsprache ein Feld einbauen
		foreach($this->getLanguages() as $sLanguage) {

			if (!isset($aLanguagesData[$sLanguage])) {
				throw new \InvalidArgumentException(sprintf('Unknown language "%s" for info icon dialog', $sLanguage));
			}

			$sValue = ($oInfoText) ? $oInfoText->getInfoText($sLanguage) : '';
			
			$this->_oDialog->setElement($this->_oDialog->createRow($aLanguagesData[$sLanguage], $this->getFieldType(), [
				'db_column' => $sLanguage,
				'db_alias' => $sRowKey,
				'default_value' => $sValue
			]));
			
		}

		$this->_oDialog->setElement($this->_oDialog->createNotification(\L10N::t('Hinweis', \Ext_Gui2::$sAllGuiListL10N), \L10N::t('Bei Filtern muss entweder die Abfrage geändert werden oder die komplette GUI neu geladen werden.', \Ext_Gui2::$sAllGuiListL10N), 'info'));

		// TODO Der Request existiert nur in den Backend-Translations und auf Basis der Backend-Translations
		/*$this->_oDialog->aButtons = [
			[
				'label' => \L10N::t('Mit DeepL übersetzen'),
				'task' => 'request',
				'action' => 'DeeplTranslation'
			]
		];*/
		
		return parent::getHtml($sAction, $aSelectedIds, $sAdditional);
	}
	
	public function save($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		if(!$bSave) {
			return parent::save($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}
		
		$sRowKey = $this->getRowKey();
		$sParentGuiHash = $this->getParentGuiHash();
		$sParentDialogId = $this->getParentDialogId();
		$sParentDialogField = $this->getParentDialogField();

		$oInfoText = \Gui2\Entity\InfoText::getRepository()
				->findOneBy([
					'gui_hash' => $sParentGuiHash,
					'dialog_id' => $sParentDialogId,
					'field' => $sParentDialogField,
				]);
		
		// Es exitiert noch kein Eintrag, neu anlegen
		if(is_null($oInfoText)) {
			$oInfoText = new \Gui2\Entity\InfoText();
			$oInfoText->gui_hash = $sParentGuiHash;
			$oInfoText->dialog_id = $sParentDialogId;
			$oInfoText->field = $sParentDialogField;
		}
		
		$oInfoText->private = (int) $aData['private'][$sRowKey];
		
		$aLanguages = \System::getBackendLanguages();
		
		foreach($aLanguages as $aLanguage) {
			$sValue = (isset($aData[$aLanguage[0]][$sRowKey])) ? $aData[$aLanguage[0]][$sRowKey] : '';
			$oInfoText->setInfoText($aLanguage[0], $sValue);			
		}
		
		$oInfoText->save();
		
		// Cache zurücksetzen damit beim Neuladen die geänderten Informationen zur Verfügung stehen
		\Core\Facade\Cache::forgetGroup('gui2_info_texts');
		
		$aTransfer = array();
		$aTransfer['action'] = 'saveDialogInfoIconCallback';
		$aTransfer['error'] = [];
		// Dialog-ID des Dialoges um diesen zu schließen
		$aTransfer['dialog_id'] = $this->_oDialog->sDialogIDTag.implode('_', $aSelectedIds ?? []);
		// Informationen zu dem Eltern-Dialog aus welchem der Dialog geöffnet wurde (wichtig zum Neuladen der Info-Icons)
		$aTransfer['parent_dialog_suffix'] = $sParentDialogId;	
		$aTransfer['parent_dialog_id'] = $sParentDialogId.implode('_', $aSelectedIds ?? []);
		
		return $aTransfer;	
		
	}
	
}
