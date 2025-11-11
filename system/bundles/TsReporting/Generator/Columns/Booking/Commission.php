<?php

namespace TsReporting\Generator\Columns\Booking;

// TODO Hinweis: Specials werden nicht abgezogen?
class Commission extends Revenue
{
	protected string $amountType = 'commission';

	protected string $serviceType = 'all';

	protected string $taxType = 'no';

	protected function getRevenueTitle(): string
	{
		return $this->t('Provision');
	}

	public function getConfigOptions(): array
	{
		return array_values(array_filter(parent::getConfigOptions(), fn(array $option) => $option['key'] === 'currency'));
	}
}