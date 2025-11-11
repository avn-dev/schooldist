<?php


/**
 * @author Mehmet Durmaz
 * @todo Diese Klasse ist noch nicht fertig, weiter nach Gemeinsamkeiten bei den Kindern suchen...
 */

abstract class Ext_Thebing_Payment_Provider_Gui2_Abstract Extends Ext_Thebing_Gui2_Data
{
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)
	{
		switch($sError)
		{
			case 'CURRENCY_CONVERT_HINT':
				$sMessage = $this->t('Sind Sie sich sicher das alle Eingaben korrekt sind? Die Beträge der verschiedenen Währungen stimmen überein.');
				break;
			case 'TRANSACTION_ALLOCATION_ERROR':
				$sMessage = $this->t('Es besteht schon eine Verknüpfung zur Buchhaltung, die Verknüpfung kann nicht verändert werden.');
				break;
			case 'UNIQUE_DATA_FOUND':
				$sMessage = $this->t('Für diesen Zeitpunkt existiert bereits eine Zahlung!');
				break;	
			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;
		}

		return $sMessage;
	}
}