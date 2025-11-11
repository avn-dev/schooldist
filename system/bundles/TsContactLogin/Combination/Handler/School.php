<?php

namespace TsContactLogin\Combination\Handler;

class School extends HandlerAbstract {

	protected function handle(): void {

		$schools = [];
		$inquiries = $this->login->getInquiries();
		$formatCountry = new \Ext_Thebing_Gui2_Format_Country();
		foreach ($inquiries as $inquiry) {
			$school = $inquiry->getSchool();
			if (
				$school->id &&
				!$schools[$school->id]
			) {
				$schoolInfo = [
					'name' => $school->getName(),
					'address' => $school->address,
					'addressAdditional' => $school->address_addon,
					'zip' => $school->zip,
					'city' => $school->city,
					'country' => $formatCountry->format($school->country_id),
					'url' => $school->url,
					'phone1' => $school->phone_1,
					'phone2' => $school->phone_2,
					'fax' => $school->fax,
					'mail' => $school->email,
				];
				$schools[$school->id] = $schoolInfo;
			}
		}
		$this->assign('schools', $schools);
		$this->login->setTask('showSchoolData');
	}
}