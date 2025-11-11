<?php

namespace TsStudentApp\Pages;

use TsStudentApp\AppInterface;

class About extends AbstractPage {

	private $appInterface;

	private $school;

	public function __construct(AppInterface $appInterface, \Ext_Thebing_School $school) {
		$this->appInterface = $appInterface;
		$this->school = $school;
	}

	public function init(): array {

		$countryFormat = new \Ext_Thebing_Gui2_Format_Country($this->appInterface->getLanguage());

		$data = [];
		$data['school'] = [
			'name' => $this->school->getName(),
			'phone' => (string)$this->school->phone_1,
			'phone_link' => $this->appInterface->formatPhoneNumberLink($this->school->phone_1),
			'email' => (string)$this->school->email,
			'email_link' => $this->appInterface->formatEmailLink($this->school->email),
			'address' => [
				'icon' => 'location-outline',
				'address' => $this->school->address,
				'address_addon' => $this->school->address_addon,
				'zip' => $this->school->zip,
				'city' => $this->school->city,
				'country' => $countryFormat->format($this->school->country_id)
			]
		];

		return $data;
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.about.email' => $appInterface->t('Email'),
			'tab.about.phone' => $appInterface->t('Phone'),
			'tab.about.app_version' => $appInterface->t('App version')
		];
	}

}
