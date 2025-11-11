<?php

namespace TsAccommodation\Handler\ExternalApp;

use Core\Factory\ValidatorFactory;
use Core\Handler\SessionHandler;
use TcExternalApps\Service\AppService;

class CityTax extends \Ts\Handler\ExternalAppPerSchool {

	const APP_NAME = 'accommodation_city_tax';

	const L10N_PATH = 'TS » Apps » City Tax';

	const KEY_ACCOMMODATION_COSTS = 'city_tax_accommodation_costs';
	const KEY_CITY_TAX_CURRENT = 'city_tax_percentage_current';
	const KEY_CITY_TAX_NEW = 'city_tax_percentage_new';
	const KEY_CITY_TAX_NEW_VALID_FROM = 'city_tax_percentage_new_valid_from';
	const KEY_ACCOMMODATION_CATEGORIES = 'city_tax_categories';
	const KEY_CITY_TAX_CALCULATE_MAX_DAYS = 'city_tax_max_days';
	const KEY_CITY_TAX_CALCULATE_DAYS = 'city_tax_calculate_days';

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return $this->t('Accommodation city tax');
	}

	public function getDescription() : string {
		return $this->t('Automatic city tax calculation');
	}

	public function getIcon() {
		return 'fas fa-bed';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::ACCOMMODATION;
	}

	public function getSettings(): array {

		$schoolAdditionalCosts = $schoolAccommodationCategories = [];
		$schools = \Ext_Thebing_School::getRepository()->findAll();
		foreach($schools as $school) {
			$schoolAccommodationCategories[$school->id] = $school->getAccommodationCategoriesList(true);
			$schoolAdditionalCosts[$school->id] = array_column($school->getAdditionalServices('accommodation', false), 'name', 'id');
		}

		return [
			self::KEY_ACCOMMODATION_COSTS => [
				'label' => $this->t('City Tax Zusatzgebühren'),
				'type' => 'select_multiple',
				'options_per_school' => $schoolAdditionalCosts,
			],
			self::KEY_CITY_TAX_CURRENT => [
				'label' => $this->t('Aktuelle City Tax in Prozent'),
				'type' => 'number',
				'step' => .1
			],
			self::KEY_CITY_TAX_NEW => [
				'label' => $this->t('Neue City Tax'),
				'type' => 'number',
				'step' => .1
			],
			self::KEY_CITY_TAX_NEW_VALID_FROM => [
				'label' => $this->t('Neue City Tax gültig ab (YYYY-MM-DD)'),
				'type' => 'date'
			],
			self::KEY_CITY_TAX_CALCULATE_MAX_DAYS => [
				'label' => $this->t('Keine City Tax ab einer Dauer von (in Tagen)'),
				'type' => 'number',
				'step' => 1
			],
			self::KEY_CITY_TAX_CALCULATE_DAYS => [
				'label' => $this->t('City Tax nur die ersten X Tage berechnen'),
				'type' => 'number',
				'step' => 1
			],
			self::KEY_ACCOMMODATION_CATEGORIES => [
				'label' => $this->t('Unterkunftskategorien auf die die City Tax angewendet wird'),
				'type' => 'select_multiple',
				'options_per_school' => $schoolAccommodationCategories,
			]
		];

	}

}
