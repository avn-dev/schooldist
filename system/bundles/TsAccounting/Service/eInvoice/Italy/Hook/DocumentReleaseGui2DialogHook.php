<?php

namespace TsAccounting\Service\eInvoice\Italy\Hook;

use TsAccounting\Service\eInvoice\Italy\ExternalApp\XmlIt as XmlItApp;

/**
 * Hook für die Liste der Dokumentenfreigabe
 * 
 * Hier werden der Dialog für den italienischen XML-Export generiert
 */
class DocumentReleaseGui2DialogHook extends \Core\Service\Hook\AbstractHook {
	
	/**
	 * @param \Ext_Gui2 $oGui
	 * @param string $sIconAction
	 * @param \Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param string $sAdditional
	 */
	public function run(\Ext_Gui2 $oGui, &$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = null) {

		if(\TcExternalApps\Service\AppService::hasApp(XmlItApp::APP_NAME)) {
			if($sIconAction == 'xml_export_it') {
				$oDialogData = $this->_getXmlExportItDialog($oGui, $sIconAction);
			} else if($sIconAction == 'xml_export_it_final') {
				$oDialogData = $this->_getXmlExportItDialog($oGui, $sIconAction, true);
			}
		}

	}
	
	/**
	 * Baut den Dialog auf
	 * 
	 * @param \Ext_Gui2 $oGui
	 * @param string $sIconAction
	 * @param bool $bFinal
	 * @return \Ext_Gui2_Dialog
	 */
	protected function _getXmlExportItDialog(\Ext_Gui2 $oGui, $sIconAction, bool $bFinal = false) {
		
		if($bFinal) {
			$sTitleDialog = $oGui->t('XML Export IT (final)');
		} else {
			$sTitleDialog = $oGui->t('XML Export IT');
		}
		
		$oDialog = $oGui->createDialog($sTitleDialog, $sTitleDialog, $sTitleDialog);
		$oDialog->width	= 900;
		$oDialog->height = 650;
		
		$oTab = $oDialog->createTab($oGui->t('XML Export'));
		
		$oFactory = new \Ext_Gui2_Factory('tsAccounting_document_release_xml_it');
		$oGuiChild = $oFactory->createGui('dialog', $oGui);		
		
		$oTab->setElement($oGuiChild);
		
		$oDialog->setElement($oTab);
		
		$oDialog->save_button = false;
		$oDialog->aButtons = array(
			array(
				'label'			=> $oGui->t('Exportieren'), 
				'task'			=> 'saveDialog',
				'action'		=> $sIconAction
			)
		);
		
		return $oDialog;
	}
	
}

