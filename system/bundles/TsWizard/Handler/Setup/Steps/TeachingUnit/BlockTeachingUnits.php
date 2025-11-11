<?php

namespace TsWizard\Handler\Setup\Steps\TeachingUnit;

use TsWizard\Traits\SchoolElement;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;

class BlockTeachingUnits extends Wizard\Structure\Block
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
		return \Ext_Thebing_School_TeachingUnit::query()
			->select('kcou.*')
			->join('ts_courseunits_schools as ts_cus',  function ($join) use ($school) {
				$join->on('ts_cus.courseunit_id', '=', 'kcou.id')
					->where('ts_cus.school_id', $school->id);
			})
			->groupBy('kcou.id')
		;
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		$schoolsWithSameValue = \Ext_Thebing_School::query()
			->where('price_structure_unit', $school->price_structure_unit)
			->where('id', '!=', $school->id)
			->pluck('id');

		return \Ext_Thebing_School_TeachingUnit::query()
			->select('kcou.*')
			->leftJoin('ts_courseunits_schools as ts_cus',  function ($join) use ($school) {
				$join->on('ts_cus.courseunit_id', '=', 'kcou.id')
					->where('ts_cus.school_id', $school->id);
			})
			->join('ts_courseunits_schools as ts_cus2',  function ($join) use ($schoolsWithSameValue) {
				$join->on('ts_cus2.courseunit_id', '=', 'kcou.id')
					->whereIn('ts_cus2.school_id', $schoolsWithSameValue);
			})
			->whereNull('ts_cus.courseunit_id')
			->groupBy('kcou.id');
	}

}