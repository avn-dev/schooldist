<?php

namespace TsWizard\Handler\Setup\Steps\TransferLocation;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockTransferLocations extends Wizard\Structure\Block
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
		return \Ext_TS_Transfer_Location::query()
			->select('ts_tl.*')
			->join('ts_transfer_locations_schools as ts_tls',  function ($join) use ($school) {
				$join->on('ts_tls.location_id', '=', 'ts_tl.id')
					->where('ts_tls.school_id', $school->id);
			})
			->groupBy('ts_tl.id');
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		return \Ext_TS_Transfer_Location::query()
			->select('ts_tl.*')
			->leftJoin('ts_transfer_locations_schools as ts_tls',  function ($join) use ($school) {
				$join->on('ts_tls.location_id', '=', 'ts_tl.id')
					->where('ts_tls.school_id', $school->id);
			})
			->whereNull('ts_tls.location_id')
			->groupBy('ts_tl.id');
	}
}