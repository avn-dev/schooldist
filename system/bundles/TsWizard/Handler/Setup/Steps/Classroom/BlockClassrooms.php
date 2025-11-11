<?php

namespace TsWizard\Handler\Setup\Steps\Classroom;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockClassrooms extends Wizard\Structure\Block
{
	use SchoolElement;

	public function getFirstStep(): ?Step
	{
		$school = $this->getSchool(app(Request::class));

		if (self::entityQuery($school)->pluck('id')->isEmpty()) {
			// Wenn es noch keine E-Mail-Konten gibt direkt auf das Formular weiterleiten, um ein Konto anzulegen
			return $this->get('form')->getFirstStep();
		}

		return parent::getFirstStep();
	}

	public static function entityQuery(\Ext_Thebing_School $school)
	{
		return \Ext_Thebing_Tuition_Classroom::query()
			->where('idSchool', $school->id);
	}
}