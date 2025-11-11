<?php

namespace TsWizard\Handler\Setup\Conditions;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;

class ExistingSchool
{
	public function __construct(Wizard $wizard, private Request $request) {}

	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if (null === $schoolId = $this->request->get('school_id', null)) {
			$element->disable();
		}

		if ((int)$schoolId === 0 || \Ext_Thebing_School::query()->find($schoolId) === null) {
			$element->disable();
		}
	}
}