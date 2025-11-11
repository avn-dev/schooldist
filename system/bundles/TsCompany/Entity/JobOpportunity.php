<?php

namespace TsCompany\Entity;

/**
 * @property int $active
 * @property string $name
 * @property string $short_name
 * @property string $description
 */
class JobOpportunity extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_companies_job_opportunities';

	protected $_sTableAlias = 'ts_cjo';

	protected $_sPlaceholderClass = \TsCompany\Service\Placeholder\JobOpportunity::class;

	protected $_aJoinedObjects = [
		'company' => [
			'class'	=> Company::class,
			'key' => 'company_id',
			'type' => 'parent',
		],
		'industry' => [
			'class'	=> Industry::class,
			'key' => 'industry_id',
			'type' => 'parent',
		]
	];

	/**
	 * Firma des Arbeitsangebotes
	 *
	 * @return Company
	 */
	public function getCompany(): Company {
		return $this->getJoinedObject('company');
	}

	/**
	 * Branche des Arbeitsangebotes
	 *
	 * @return Industry
	 */
	public function getIndustry(): Industry {
		return $this->getJoinedObject('industry');
	}

	/**
	 * Name des Arbeitsangebotes
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Abkürzung des Arbeitsangebotes
	 *
	 * @return string
	 */
	public function getShortName(): string {
		return $this->short_name;
	}

}
