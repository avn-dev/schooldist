<?php
/**
 * Kommunikation Signaturen Gui2 Data
 */
class Ext_TC_Communication_Signature_Gui2_Data extends Ext_TC_Gui2_Data {

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = (int)reset($aSelectedIds);
		$oSubObject = Factory::getInstance(Ext_TC_SubObject::class, $iSelectedId);
		$aLanguages = $oSubObject->getCorrespondenceLanguagesOptions();

		$this->_setAdditionalLanguages($aLanguages);

		$aNewTabs = array();

		// Sprach-Tabs
		foreach((array)$aLanguages as $sIso => $sLanguage) {

			$sTabTitle = '<img src="/admin/media/flag_'.\Util::convertHtmlEntities($sIso).'.gif" /> '.sprintf($this->t('Inhalte "%s"'), $sLanguage);
			$oTab = $oDialogData->createTab($sTabTitle);

			$oTab->setElement($oDialogData->createRow($this->t('Text'), 'textarea', array(
				'db_alias' => '',
				'db_column' => 'signature_text_'.$sIso,
				'style' => 'height: 200px; width: 600px'
			)));

			$oTab->setElement($oDialogData->createRow($this->t('HTML'), 'html', array(
				'db_alias' => '',
				'db_column' => 'signature_html_'.$sIso,
				'style' => 'height: 200px; width: 600px'
			)));

			$aNewTabs[] = $oTab;

		}

		// Platzhalter
		$oTab = $oDialogData->createTab($this->t('Platzhalter'));
		$oTab->no_padding = 1;
		$oTab->no_scrolling = 1;

		$oSignature = new Ext_TC_User_Signature();
		$oPlaceholder = $oSignature->getPlaceholderObject();
		$sPlaceholders = $oPlaceholder->displayPlaceholderTable('signature');
		
		#$oPlaceholder = new Ext_TC_Communication_Signature_Placeholder();
		$oHtml = new Ext_TC_Placeholder_Html();
		$sHtml = $oHtml->createPlaceholderContent($sPlaceholders);
		$oTab->setElement($sHtml);
		
		$aNewTabs[] = $oTab;

		$oDialogData->aElements = $aNewTabs;

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

		return $aData;

	}

	/**
	 * Fügt weitere benötigte Sprachen hinzu
	 * @param $aLanguages
	 */
	protected function _setAdditionalLanguages(&$aLanguages)
	{

	}

}