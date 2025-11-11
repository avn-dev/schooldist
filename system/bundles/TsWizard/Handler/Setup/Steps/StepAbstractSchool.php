<?php

namespace TsWizard\Handler\Setup\Steps;

use Illuminate\Http\Request;
use Tc\Service\Wizard\Structure\Step;

abstract class StepAbstractSchool extends Step
{
	protected function getSchool(Request $request): \Ext_Thebing_School
	{
		return \Ext_Thebing_school::query()->findOrFail($request->get('school_id', 0));
	}
}