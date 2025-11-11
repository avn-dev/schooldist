<?php

class Ext_Thebing_Management_Page_Gui2 extends Ext_Thebing_Gui2_Data
{

	/**
	 * {@inheritdoc}
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false)
    {

		$oMatrix = new Ext_Thebing_Access_Matrix_StatisticPages;
		$aMatrix = $oMatrix->aMatrix;

		$oDialog = $this->_oGui->createDialog($this->_oGui->t('Zugriffsrechte'), $this->_oGui->t('Zugriffsrechte'));

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		$aData['bSaveButton'] = 1;
		$aData['aMatrixData'] = $aMatrix;

		$aData['aMatrixCellColors'] = array(
			'red'	=> Ext_Thebing_Util::getColor('red'),
			'green'	=> Ext_Thebing_Util::getColor('green')
		);

		$aData['html'] = $oMatrix->generateHTML($this->_oGui->gui_description);

		return $aData;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true)
    {

		if($sAction == 'openAccessDialog') {

			$oMatrix = new Ext_Thebing_Access_Matrix_StatisticPages;
			$oMatrix->saveAccessData($aData['access']);

			$aTransfer = [
				'task' => 'openDialog',
				'action' => 'saveAccessDialogCallback',
				'data' => [
					'action' => ''
				]
			];

		} else {
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional);
		}

		return $aTransfer;
	}
	
	static public function getDialog(\Ext_Gui2 $oGui)
    {
		
		$oStatistic = new Ext_Thebing_Management_Statistic();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDialog = $oGui->createDialog($oGui->t('Seite "{title}" bearbeiten'), $oGui->t('Neue Seite'));

		$oTabData = $oDialog->createTab( $oGui->t('Daten'));

		$oDiv = $oDialog->createRow( $oGui->t('Titel'), 'input', array('db_column' => 'title', 'required' => 1));
		$oTabData->setElement($oDiv);

		$oDiv = $oDialog->createRow(
            $oGui->t('Statistiken'), 'select',
            array('db_column' => 'statistics', 'multiple' => 10,
                'select_options' => $oStatistic->getListQueryData(null, true),
                'jquery_multiple' => 1, 'sortable' => 1, 'searchable' => 1)
        );
		$oTabData->setElement($oDiv);
		$oDialog->setElement($oTabData);

		return $oDialog;
	}

}