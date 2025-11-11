<?php

namespace TsWizard\Handler\Setup\Steps\AccommodationMeal;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockAccommodationMeals extends Wizard\Structure\Block
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
		return \Ext_Thebing_Accommodation_Meal::query()
			->select('kam.*')
			->join('ts_accommodation_meals_schools as ts_ams',  function ($join) use ($school) {
				$join->on('ts_ams.accommodation_meal_id', '=', 'kam.id')
					->where('ts_ams.school_id', $school->id);
			})
			->groupBy('kam.id');
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		return \Ext_Thebing_Accommodation_Meal::query()
			->select('kam.*')
			->leftJoin('ts_accommodation_meals_schools as ts_ams',  function ($join) use ($school) {
				$join->on('ts_ams.accommodation_meal_id', '=', 'kam.id')
					->where('ts_ams.school_id', $school->id);
			})
			->whereNull('ts_ams.accommodation_meal_id')
			->groupBy('kam.id');
	}

}