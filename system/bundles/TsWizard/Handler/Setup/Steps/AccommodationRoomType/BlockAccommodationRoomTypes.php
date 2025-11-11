<?php

namespace TsWizard\Handler\Setup\Steps\AccommodationRoomType;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockAccommodationRoomTypes extends Wizard\Structure\Block
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
		return \Ext_Thebing_Accommodation_Roomtype::query()
			->select('kar.*')
			->join('ts_accommodation_roomtypes_schools as ts_ars',  function ($join) use ($school) {
				$join->on('ts_ars.accommodation_roomtype_id', '=', 'kar.id')
					->where('ts_ars.school_id', $school->id);
			})
			->groupBy('kar.id');
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		return \Ext_Thebing_Accommodation_Roomtype::query()
			->select('kar.*')
			->leftJoin('ts_accommodation_roomtypes_schools as ts_ars',  function ($join) use ($school) {
				$join->on('ts_ars.accommodation_roomtype_id', '=', 'kar.id')
					->where('ts_ars.school_id', $school->id);
			})
			->whereNull('ts_ars.accommodation_roomtype_id')
			->groupBy('kar.id');
	}
}