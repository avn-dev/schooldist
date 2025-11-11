<?php

namespace TsCompany\Entity;

use Core\Database\WDBasic\Builder;

class Company extends AbstractCompany {

	protected $_sPlaceholderClass = \TsCompany\Service\Placeholder\Company::class;

	public function __construct(int $iDataID = 0, string $sTable = null) {

		$this->_aJoinTables['industries'] = [
			'table' => 'ts_companies_to_industries',
			'foreign_key_field' => 'industry_id',
			'primary_key_field' => 'company_id',
			'class' => Industry::class,
			'autoload' => true
		];

		parent::__construct($iDataID, $sTable);
	}

	public function getContacts($bForSelect = false) {

		$aContacts = $this->getJoinedObjectChilds('contacts');

		if($bForSelect) {
			$aContacts = collect($aContacts)
				->mapWithKeys(function(Contact $contact) {
					return [$contact->getId() => $contact->name];
				})
				->toArray();
		}

		return $aContacts;
	}

	public static function getSelectOptions(bool $longname = false) {

		return self::query()->get()
			->mapWithKeys(function(Company $company) use ($longname) {
				return [$company->getId() => $company->getName($longname)];
			})
			->toArray();

	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= " 
			, GROUP_CONCAT(DISTINCT `industries`.`industry_id` SEPARATOR ',') `industry_ids`
		";

	}

	public static function booted() {

		static::addGlobalScope('type', function (Builder $builder) {
			$builder->where($builder->getModel()->qualifyColumn('type'), '&', AbstractCompany::TYPE_COMPANY);
		});

	}

}
