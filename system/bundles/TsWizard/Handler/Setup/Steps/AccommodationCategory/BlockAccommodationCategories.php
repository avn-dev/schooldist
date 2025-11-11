<?php

namespace TsWizard\Handler\Setup\Steps\AccommodationCategory;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsAccommodation\Entity\Provider\SchoolSetting;
use TsWizard\Handler\Setup\Steps\PriceWeek\BlockPriceWeeks;
use TsWizard\Traits\SchoolElement;

class BlockAccommodationCategories extends Wizard\Structure\Block
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
		return \Ext_Thebing_Accommodation_Category::query()
			->select('kac.*')
			->join('ts_accommodation_categories_settings as ts_acs',  'ts_acs.category_id', '=', 'kac.id')
			->join('ts_accommodation_categories_settings_schools as ts_acss',  function ($join) use ($school) {
				$join->on('ts_acss.setting_id', '=', 'ts_acs.id')
					->where('ts_acss.school_id', $school->id);
			})
			->groupBy('kac.id');
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		$ids = \Ext_Thebing_Accommodation_Category::query()
			->selectRaw('kac.id, GROUP_CONCAT(DISTINCT `ts_acss`.`school_id` SEPARATOR "|") `school_ids`')
			->join('ts_accommodation_categories_settings as ts_acs',  'ts_acs.category_id', '=', 'kac.id')
			->join('ts_accommodation_categories_settings_schools as ts_acss',  'ts_acss.setting_id', '=', 'ts_acs.id')
			->groupBy('kac.id')
			->pluck('school_ids', 'id')
			->filter(fn ($schools) => !in_array($school->id, explode('|', $schools)))
			->keys();

		return \Ext_Thebing_Accommodation_Category::query()->whereIn('id', $ids);
	}

	public static function getOrCreatePriceWeek(\Ext_Thebing_School $school): \Ext_Thebing_School_Week {

		// Preiswoche suchen oder anlegen (Startwoche = 1 und Extrawoche)
		$priceweek = BlockPriceWeeks::entityQuery($school)
			->firstOrCreate(
				['start_week' => 1, 'extra' => 1],
				[
					'active' => 1,
					'title' => '1+ Weeks',
					'position' => BlockPriceWeeks::entityQuery($school)->max('position') + 1
				]
			);

		if (!in_array($school->id, $priceweek->schools)) {
			$schools = $priceweek->schools;
			$schools[] = $school->id;
			$priceweek->schools = $schools;
			$priceweek->save();
		}

		return $priceweek;
	}

}