<?php

namespace TsWizard\Handler\Setup\Steps\PriceWeek;

use TsWizard\Traits\SchoolElement;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;

class BlockPriceWeeks extends Wizard\Structure\Block
{
	use SchoolElement;

	public function getFirstStep(): ?Step
	{
		$school = $this->getSchool(app(Request::class));

		// Wenn keine Entitäten von anderen Schulen übernommen werden können den Step überspringen
		if (self::othersQuery($school)->pluck('id')->isEmpty()) {
			if (self::entityQuery($school)->pluck('id')->isEmpty()) {
				// Wenn es noch keine Entitäten gibt direkt auf das Formular weiterleiten, um eine neue Entität anzulegen
				return $this->get('form')->getFirstStep();
			} else {
				return $this->get('list');
			}
		}

		return parent::getFirstStep();
	}

	public static function entityQuery(\Ext_Thebing_School $school)
	{
		return \Ext_Thebing_School_Week::query()
			->select('kw.*')
			->join('ts_weeks_schools as ts_ws',  function ($join) use ($school) {
				$join->on('ts_ws.week_id', '=', 'kw.id')
					->where('ts_ws.school_id', $school->id);
			})
			->groupBy('kw.id')
		;
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		$schoolsWithSameValue = \Ext_Thebing_School::query()
			->where('price_structure_week', $school->price_structure_week)
			->where('id', '!=', $school->id)
			->pluck('id');

		return \Ext_Thebing_School_Week::query()
			->select('kw.*')
			->leftJoin('ts_weeks_schools as ts_ws',  function ($join) use ($school) {
				$join->on('ts_ws.week_id', '=', 'kw.id')
					->where('ts_ws.school_id', $school->id);
			})
			->join('ts_weeks_schools as ts_ws2',  function ($join) use ($schoolsWithSameValue) {
				$join->on('ts_ws2.week_id', '=', 'kw.id')
					->whereIn('ts_ws2.school_id', $schoolsWithSameValue);
			})
			->whereNull('ts_ws.week_id')
			->groupBy('kw.id');
	}

}