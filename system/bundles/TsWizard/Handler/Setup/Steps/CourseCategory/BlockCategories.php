<?php

namespace TsWizard\Handler\Setup\Steps\CourseCategory;

use TsWizard\Traits\SchoolElement;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;

class BlockCategories extends Wizard\Structure\Block
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
		return \Ext_Thebing_Tuition_Course_Category::query()
			->select('ktcc.*')
			->join('ts_tuition_coursecategories_to_schools as ts_tcts',  function ($join) use ($school) {
				$join->on('ts_tcts.category_id', '=', 'ktcc.id')
					->where('ts_tcts.school_id', $school->id);
			})
			->groupBy('ktcc.id')
		;
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		return \Ext_Thebing_Tuition_Course_Category::query()
			->select('ktcc.*')
			->leftJoin('ts_tuition_coursecategories_to_schools as ts_tcts',  function ($join) use ($school) {
				$join->on('ts_tcts.category_id', '=', 'ktcc.id')
					->where('ts_tcts.school_id', $school->id);
			})
			->whereNull('ts_tcts.category_id')
			->groupBy('ktcc.id');
	}

}