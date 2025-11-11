<?php

namespace TsStatistic\Model\Filter;

use \TcStatistic\Model\Filter\AbstractFilter;

class InvoiceType extends AbstractFilter {

	public function getKey() {
		return 'invoice_type';
	}

	public function getTitle() {
		return self::t('Rechnungstyp');
	}

	public function getInputType() {
		return 'select';
	}

	public function getSelectOptions() {
		return [
			'all' => static::t('Alle'),
			'proforma_or_invoice' => static::t('Proforma oder Rechnung'),
			'proforma' => static::t('Nur Proforma'),
			'invoice' => static::t('Nur Rechnungen')
		];
	}

	public function getDefaultValue() {

		$iCustomerSetting = (int)\Ext_Thebing_System::getConfig('show_customer_without_invoice');
		if($iCustomerSetting == 2) {
			return 'invoice';
		} elseif($iCustomerSetting == 0) {
			return 'proforma_or_invoice';
		}

		return 'all';

	}

}
