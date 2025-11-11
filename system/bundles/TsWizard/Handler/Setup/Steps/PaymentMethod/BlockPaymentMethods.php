<?php

namespace TsWizard\Handler\Setup\Steps\PaymentMethod;

use TsWizard\Traits\SchoolElement;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;

class BlockPaymentMethods extends Wizard\Structure\Block
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
		return \Ext_Thebing_Admin_Payment::query()
			->select('kpm.*')
			->join('kolumbus_payment_method_schools as ts_pms',  function ($join) use ($school) {
				$join->on('ts_pms.payment_method_id', '=', 'kpm.id')
					->where('ts_pms.school_id', $school->id);
			})
			->groupBy('kpm.id')
		;
	}

	public static function othersQuery(\Ext_Thebing_School $school)
	{
		return \Ext_Thebing_Admin_Payment::query()
			->select('kpm.*')
			->leftJoin('kolumbus_payment_method_schools as ts_pms',  function ($join) use ($school) {
				$join->on('ts_pms.payment_method_id', '=', 'kpm.id')
					->where('ts_pms.school_id', $school->id);
			})
			->whereNull('ts_pms.payment_method_id')
			->groupBy('kpm.id');
	}

}