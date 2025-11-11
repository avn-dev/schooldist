<?php

namespace TsAccounting\Gui2\Format\Company;

class Types extends \Ext_Gui2_View_Format_Abstract
{

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{

		if (!$this->oGui instanceof \Ext_Gui2) {
			throw new \Exception('No gui object available!');
		}

		$aTypes = array(
			'position' => $this->oGui->t('Position'),
			'commission' => $this->oGui->t('Provision'),
			'vat' => $this->oGui->t('Umsatzsteuer'),
			'claim_debt' => $this->oGui->t('Forderung / Verbindlichkeit'),
			'deposit' => $this->oGui->t('Anzahlung'),
			'deposit_credit' => $this->oGui->t('Gutschrift Anzahlung'),
			'payment' => $this->oGui->t('Zahlung')
		);

		return $aTypes[$mValue];

	}

}