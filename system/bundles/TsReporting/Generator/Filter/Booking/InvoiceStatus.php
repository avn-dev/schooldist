<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class InvoiceStatus extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Rechnungsstatus');
	}

	public function getType(): string
	{
		return 'select';
	}

	public function build(QueryBuilder $builder)
	{
		match ($this->value) {
			'proforma' => $builder->where(function(QueryBuilder $query) {
				$query->where('ts_i.has_invoice', 1);
				$query->orWhere('ts_i.has_proforma', 1);
			}),
			'invoice' => $builder->where('ts_i.has_invoice', 1),
		};
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return [
			['key' => 'proforma', 'label' => $this->t('ab Proforma')],
			['key' => 'invoice', 'label' => $this->t('ab Rechnung')],
		];
	}

	public function getDefault(): ?string
	{
		return match((int)\Ext_Thebing_System::getConfig('show_customer_without_invoice')) {
			0 => 'proforma',
			1 => null,
			2 => 'invoice'
		};
	}
}
