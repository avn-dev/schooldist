<?php

namespace TsCompany\Gui2\Data;

use TsCompany\Entity\AbstractCompany;
use TsCompany\Traits\Gui2\DialogBuild;

class Company extends \Ext_Thebing_Gui2_Data {
	use \Tc\Traits\Gui2\Import, DialogBuild;

	protected function _getWDBasicObject($aSelectedIds) {

		/* @var AbstractCompany $entity */
		$entity = parent::_getWDBasicObject($aSelectedIds);

		if (!$entity->exist()) {
			$entity->addFlag('type', (int)$this->_oGui->getOption('type', AbstractCompany::TYPE_COMPANY));
		}

		return $entity;
	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		// Damit der Bit-Flag gesetzt wird und $oDialog->getDataObject()->setWDBasicObject($this->oWDBasic) aufgerufen wird
		$this->_getWDBasicObject($aSelectedIds);

		return parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
	}

	protected function getImportDialogId() {
		return 'IMPORT_COMPANY_';
	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
		return new \TsCompany\Service\Import\Company();
	}

	protected function addSettingFields(\Ext_Gui2_Dialog $dialog) {

		$dialog->setElement($dialog->createRow($this->t('Vorhandene Kontakte und Kommentare leeren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'delete_existing']));

		$dialog->setElement($dialog->createRow($this->t('Vorhandene Einträge aktualisieren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']));

	}

	public static function getOrderby(){
		return [
			'ka.ext_1' => 'ASC',
			'ka.ext_2' => 'ASC'
		];
	}

}
