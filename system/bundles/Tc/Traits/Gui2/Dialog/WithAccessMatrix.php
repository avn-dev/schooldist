<?php

namespace Tc\Traits\Gui2\Dialog;

use Tc\Traits\Gui2\AccessMatrix;

trait WithAccessMatrix
{
	use AccessMatrix;

	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional = false)
	{
		if($sIconAction == 'openAccessDialog') {
			return $this->getAccessDialogData($aSelectedIds);
		}

		return parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true)
	{
		if($bSave && $sAction == 'openAccessDialog') {
			return $this->saveAccessDialog($aSelectedIds, $aData);
		}

		return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
	}
}