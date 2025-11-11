<?php

namespace TsAccounting\Gui2\Data;

use Ext_Gui2;
use Ext_Gui2_Dialog;
use Ext_Thebing_Client;
use Ext_Thebing_Gui2_Data;
use Ext_Thebing_System;
use Ext_Thebing_Util;
use L10N;

class CompanyTemplateReceiptText extends Ext_Thebing_Gui2_Data
{
	/**
	 *
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog(Ext_Gui2 $oGui)
	{
		$aCompanies = Ext_Thebing_System::getAccountingCompanies(true);

		$aSchools = Ext_Thebing_Client::getStaticSchoolListByAccess(true);

		$aInboxes = Ext_Thebing_System::getInboxList('use_id', true);

		$oClient = Ext_Thebing_System::getClient();

		// Dialog
		$oDialog = $oGui->createDialog($oGui->t('Belegtext "{name}" editieren'), $oGui->t('Neuen Belegtext anlegen'));
		$oDialog->width = 900;
		$oDialog->height = 650;

		$oTab = $oDialog->createTab($oGui->t('Vorlagen'));

		$oTab->setElement($oDialog->createSaveField('hidden', array(
			'db_column' => 'type',
			'value' => '1',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'required' => 1,
			'db_column' => 'name',
		)));

		if (Ext_Thebing_System::hasAccountingCompanies()) {
			$oTab->setElement($oDialog->createRow($oGui->t('Firma'), 'select', array(
				'db_column' => 'companies',
				'select_options' => $aCompanies,
				'selection' => new \TsAccounting\Gui2\Selection\Company(),
				'multiple' => 5,
				'jquery_multiple' => 1,
				'style' => 'height: 105px;', 'class' => 'txt week_fields',
				'required' => 1,
			)));
		}

		$oTab->setElement($oDialog->createRow($oGui->t('Schule'), 'select', array(
			'db_column' => 'schools',
			'select_options' => $aSchools,
			'dependency' => array(array('db_column' => 'companies')),
			'selection' => new \TsAccounting\Gui2\Selection\Company\Combination\School(),
			'multiple' => 5,
			'jquery_multiple' => 1,
			'style' => 'height: 105px;', 'class' => 'txt week_fields',
			'required' => 1,
		)));

		if ($oClient->checkUsingOfInboxes()) {
			$oTab->setElement($oDialog->createRow($oGui->t('Inbox'), 'select', array(
				'db_column' => 'inboxes',
				'select_options' => $aInboxes,
				'dependency' => array(array('db_column' => 'schools')),
				'selection' => new \TsAccounting\Gui2\Selection\Company\Combination\Inbox(),
				'multiple' => 5,
				'jquery_multiple' => 1,
				'style' => 'height: 105px;', 'class' => 'txt week_fields',
				'required' => 1,
			)));
		}

		$aBasedOn = \TsAccounting\Helper\Company\ReceiptTextBasedOn::getTypesForSelect($oGui->gui_description);

		$aBasedOn = Ext_Thebing_Util::addEmptyItem($aBasedOn);

		$oTab->setElement($oDialog->createRow($oGui->t('Belegtexte'), 'select', array(
			'db_column' => 'based_on',
			'select_options' => $aBasedOn,
			'events' => array(
				array(
					'function' => 'showBasedOnElements',
					'event' => 'change'
				)
			),
			'required' => true,
		)));

		$oDialog->setElement($oTab);

		$oTabPlaceholder = $oDialog->createTab(L10N::t('Platzhalter', $oGui->gui_description));

		$oPlaceholder = new \TsAccounting\Service\Placeholder\Company\TemplateReceiptText($oGui);

		$oTabPlaceholder->setElement((string)$oPlaceholder->displayPlaceholderTable());

		$oDialog->setElement($oTabPlaceholder);

		return $oDialog;
	}

	/**
	 *
	 * @param string $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @param string $sAction
	 * @param string $sAdditional
	 * @return string
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)
	{
		switch ($sError) {
			case 'COMBINATION_NOT_FREE':
				$sErrorMessage = $this->t('Kombination wurde schon angelegt!');
				break;
			default:
				$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;
		}

		return $sErrorMessage;
	}

	/**
	 *
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param string $sAdditional
	 * @return string
	 */
//	public function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false)
//	{
//		dd($oDialogData);
//		if (!$this->oWDBasic) {
//			$this->_getWDBasicObject($aSelectedIds);
//		}
//
//		$oTab = $oDialogData->aElements[0];
//
//		$aElementsBefore = $oTab->aElements;
//
//		$this->_addBasedOnElements($this->oWDBasic->based_on, $oDialogData);
//
//		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
//
//		// Elemente wieder resetten, sonst vermehrt sicht das jedes mal beim öffnen des Dialoges
//		$oTab->aElements = $aElementsBefore;
//
//		return $aData;
//
//	}

	/**
	 * Basierend auf Elemente dem Tab hinzufügen
	 *
	 * @param int $iBasedOn
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	protected function _addBasedOnElements($iBasedOn, Ext_Gui2_Dialog $oDialog)
	{
		$aBasedOnData = \TsAccounting\Helper\Company\ReceiptTextBasedOn::getBasedOnData();

		if (isset($aBasedOnData[$iBasedOn])) {

			$aBasedOnDataByType = $aBasedOnData[$iBasedOn];

			$oFormatDocTypeName = new \Ext_TS_Document_Release_Gui2_Format_DocType();
			$oFormatDocTypeName->oGui = $this->_oGui;

			$oFormatPositionName = new \Ext_Thebing_Gui2_Format_Position_Position();
			$oFormatPositionName->oGui = $this->_oGui;

			$oFormatOthers = new \TsAccounting\Gui2\Format\Company\Types();
			$oFormatOthers->oGui = $this->_oGui;

			$aFormat = array($oFormatOthers, $oFormatPositionName);

			$oTab = $oDialog->aElements[0];

			if ($iBasedOn == 2) {
				$this->_addAttributesToTab($aBasedOnDataByType, $oDialog, $aFormat);
			} else {

				foreach ($aBasedOnDataByType as $sDocType => $aAttributes) {

					$sDocTypeName = $oFormatDocTypeName->format($sDocType);

					$oH3 = $oDialog->create('h3');
					$oH3->setElement($sDocTypeName);

					$oTab->setElement($oH3);

					$this->_addAttributesToTab($aAttributes, $oDialog, $aFormat);

				}

			}

			$oH3 = $oDialog->create('h3');
			$oH3->setElement($this->t('Weitere Belegtexte'));

			$oTab->setElement($oH3);

			$this->_addAttributesToTab(['payment' => 'payment'], $oDialog, [$oFormatOthers]);

		}

	}

	protected function _addAttributesToTab(array $aAttributes, Ext_Gui2_Dialog $oDialog, array $aFormat)
	{
		$oTab = $oDialog->aElements[0];

		foreach ($aAttributes as $sLabelKey => $sAttribute) {
			foreach ($aFormat as $oFormat) {
				$sAttributeName = $oFormat->format($sLabelKey);
				// Wenn ein Name gefunden wurde, abbrechen
				if (!empty($sAttributeName)) {
					break;
				}
			}

			$oTab->setElement($oDialog->createRow($sAttributeName, 'input', array(
				'db_alias' => 'attribute',
				'db_column' => $sAttribute,
			)));
		}
	}
}
