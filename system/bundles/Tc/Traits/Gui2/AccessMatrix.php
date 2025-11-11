<?php

namespace Tc\Traits\Gui2;

trait AccessMatrix
{
	abstract protected function getAccessMatrix(): \Ext_TC_Access_Matrix;

	protected function saveAccessDialog($aSelectedIds, $aSaveData)
	{
		$oMatrix = $this->getAccessMatrix();

		$oMatrix->saveAccessData($aSaveData['access']);

		$aData = $this->getAccessDialogData($aSelectedIds);

		return [
			'data' => $aData,
			'error' => [],
			'task' => 'openDialog',
			'action' => 'saveDialogCallback'
		];
	}

	protected function getAccessDialogData($aSelectedIds)
	{
		$oMatrix = $this->getAccessMatrix();

		$aMatrix = $oMatrix->aMatrix;

		$oDialog = $this->_oGui->createDialog($this->_oGui->t('Zugriffsrechte'), $this->_oGui->t('Zugriffsrechte'));

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		$aData['bSaveButton'] = 1;
		$aData['aMatrixData'] = $aMatrix;
		$aData['aMatrixCellColors'] = [
			'red' => \Ext_TC_Util::getColor('red'),
			'green' => \Ext_TC_Util::getColor('green')
		];

		$aData['html'] = $oMatrix->generateHTML($this->_oGui->gui_description);

		$aData['action'] = 'openAccessDialog';
		$aData['task'] = 'saveDialog';

		return $aData;
	}
}