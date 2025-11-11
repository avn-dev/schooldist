<?php

namespace TsCompany\Traits\Gui2;

use TsCompany\Gui2\Dialog\AbstractDialog;

trait DialogBuild {

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {

		if (
			$sIconAction == 'new' ||
			$sIconAction == 'edit'
		) {
			$this->getWDBasicObject($aSelectedIds);

			$dialogClass = $this->_oGui->getOption('dialog', null);

			if($dialogClass === null) {
				throw new \RuntimeException('Please define dialog class as gui2 option');
			}

			$dialog = new $dialogClass($this->_oGui);
			/* @var AbstractDialog $dialog */

			$gui2Dialog = $this->_oGui->createDialog($dialog->getEditTitle(), $dialog->getTitle());

			$dialog->buildDialog($gui2Dialog, $this->oWDBasic);

			//Dialog für $sIconKey setzen
			$this->aIconData['new']['dialog_data'] = $gui2Dialog;
			$this->aIconData['edit']['dialog_data'] = $gui2Dialog;

		}

		return parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
	}

}
