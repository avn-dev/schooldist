<?php

namespace TsCompany\Service\Placeholder;

class JobOpportunity extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oJobOpportunity'
	];

	protected $_aPlaceholders = [
		'job_opportunity_name' => [
			'label' => 'Bezeichnung',
			'type' => 'field',
			'source' => 'name'
		],
		'job_opportunity_short_name' => [
			'label' => 'Abkürzung',
			'type' => 'field',
			'source' => 'short_name'
		],
		'job_opportunity_description' => [
			'label' => 'Beschreibung',
			'type' => 'field',
			'source' => 'description'
		],
		'job_opportunity_wage' => [
			'label' => 'Gehalt',
			'type' => 'field',
			'source' => 'wage',
			'format' => \Ext_Thebing_Gui2_Format_Amount::class
		],
		'job_opportunity_wage_unit' => [
			'label' => 'Gehalt (Einheit)',
			'type' => 'field',
			'source' => 'wage_per',
			'format' => \TsCompany\Gui2\Format\JobOpportunity\ValueUnit::class
		],
		'job_opportunity_hours' => [
			'label' => 'Geplante Stunden',
			'type' => 'field',
			'source' => 'hours',
			'format' => \Ext_Thebing_Gui2_Format_Amount::class
		],
		'job_opportunity_hours_unit' => [
			'label' => 'Geplante Stunden (Einheit)',
			'type' => 'field',
			'source' => 'hours_per',
			'format' => \TsCompany\Gui2\Format\JobOpportunity\ValueUnit::class
		],
		'job_opportunity_company' => [
			'label' => 'Firma',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getCompany'
		],
		'job_opportunity_industry' => [
			'label' => 'Branche',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getIndustry'
		],
	];

}
