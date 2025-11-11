<?php

namespace TsTeacherLogin\Traits;

use Carbon\Carbon;
use Spatie\Period\Period;

trait SchoolViewPeriod
{
	protected function checkDateInViewPeriod(\Ext_Thebing_School $school, \DateTimeInterface $date): bool
	{
		$viewPeriod = $this->getViewPeriod($school);

		if ($viewPeriod && !$viewPeriod->contains($date)) {
			return false;
		}

		return true;
	}

	protected function checkDateInViewWeekPeriod(\Ext_Thebing_School $school, \DateTimeInterface $date): bool
	{
		$viewWeekPeriod = $this->getViewWeekPeriod($school);

		if ($viewWeekPeriod) {
			return $viewWeekPeriod->contains($date);
		}

		return true;
	}

	protected function getViewPeriod(\Ext_Thebing_School $school): ?Period
	{
		if (!empty($this->viewPeriod)) {
			return $school->getTeacherLoginViewPeriod($this->viewPeriod);
		}

		return null;
	}

	protected function getViewWeekPeriod(\Ext_Thebing_School $school): ?Period
	{
		$viewPeriod = $this->getViewPeriod($school);

		if ($viewPeriod) {
			return \Spatie\Period\Period::make(
				Carbon::make($viewPeriod->start())->startOfWeek(Carbon::MONDAY),
				Carbon::make($viewPeriod->end())->endOfWeek(Carbon::SUNDAY),
				\Spatie\Period\Precision::SECOND()
			);
		}

		return null;
	}
}