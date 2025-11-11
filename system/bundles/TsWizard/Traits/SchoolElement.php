<?php

namespace TsWizard\Traits;

use Core\Handler\SessionHandler;
use Illuminate\Http\Request;

trait SchoolElement
{
	protected function getSchool(Request $request): \Ext_Thebing_School
	{
		$schoolId = (int)$request->get('school_id', 0);

		if ($schoolId === 0) {
			return new \Ext_Thebing_school();
		}

		$school = \Ext_Thebing_school::getInstance($schoolId);

		SessionHandler::getInstance()->set('sid', $school->id);
		// (new \Ts\Handler\SchoolId())->setSchool($school->id); // TODO geht nicht wegen Cookie::set()

		return $school;
	}
}