<?php

/**
 * GUI2-Ableitung der Templates
 */
class Ext_TC_Communication_Block_Gui2_Data extends Ext_TC_Gui2_Data {
	
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {
		
		$aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages', array(true));
		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = (int)reset($aSelectedIds);
		$oBlock = Ext_TC_Communication_Block::getInstance($iSelectedId);
		
		if($oDialogData->_sGuiType === 'email') {
			$aLayouts = Ext_TC_Util::addEmptyItem(Ext_TC_Communication_Template_Email_Layout::getSelectOptions());
			$aUploads = Ext_TC_Upload::getSelectOptionsBySearch('communication');
			$sFilePath = Ext_TC_Communication_Template::getUploadPath();
		}
		
		$aTabs = $oDialogData->aElements;
		#$oLastTab = end($aTabs);
		$aNewTabs = array(reset($aTabs));
		
		foreach((array)$oBlock->languages as $sIso) {
			
			$sLanguage = $aLanguages[$sIso];
			$oTab = $oDialogData->createTab('<img src="/admin/media/flag_'.\Util::convertHtmlEntities($sIso).'.gif" /> '.sprintf($this->t('Inhalte "%s"'), $sLanguage));
			
			$oTab->setElement($oDialogData->createRow($this->t('Text'), 'textarea', array(
				'db_alias' => '',
				'db_column' => 'text_'.$sIso,
				'style' => 'height: 200px; width: 600px'
			)));

			$oTab->setElement($oDialogData->createRow($this->t('HTML'), 'html', array(
				'db_alias' => '',
				'db_column' => 'html_'.$sIso,
				'style' => 'height: 200px; width: 600px'
			)));

			$aNewTabs[] = $oTab;
			
		}
		
		#$aNewTabs[] = $oLastTab;
		$oDialogData->aElements = $aNewTabs;
		
		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
		
		return $aData;
		
	}
	
}
