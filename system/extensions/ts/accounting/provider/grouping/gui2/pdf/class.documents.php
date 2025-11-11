<?php

class Ext_TS_Accounting_Provider_Grouping_Gui2_Pdf_Documents extends Ext_Gui2_Pdf_Abstract {
	
	/**
	 * {@inheritdoc}
	 */
	public function getPdfPath($iSelectedId) {

		$sClassName = $this->oGuiData->getGui()->class_wdbasic;

		$iSelectedId = (int)$iSelectedId;
		$oProvider = $sClassName::getInstance($iSelectedId);

		$sPath = Util::getDocumentRoot().'storage/'.$oProvider->file;

		if(!is_file($sPath)) {
			throw new RuntimeException('File can not found! Path: ' . $sPath);
		}

		return $sPath;

	}
}