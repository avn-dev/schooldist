<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class School extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Schule');
	}

	public function getType(): string
	{
		return 'select:multiple';
	}

	public function build(QueryBuilder $builder)
	{
		$schoolIds = array_keys(\Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(true));
		$builder->whereIn('ts_ij.school_id', array_intersect($schoolIds, $this->value));
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return array_map(function (\Ext_Thebing_School $school) {
			return ['key' => (int)$school->id, 'label' => $school->short];
		}, \Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(false, true));
	}

	public function getDefault(): array
	{
		$options = \Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(true);

		if (\Ext_Thebing_System::isAllSchools()) {
			return array_keys($options);
		}

		$school = \Ext_Thebing_School::getSchoolFromSession();

		return [$school->id];
	}

	public function isRequired(): bool
	{
		return true;
	}
}