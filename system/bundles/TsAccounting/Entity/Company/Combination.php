<?php

namespace TsAccounting\Entity\Company;

use TsAccounting\Entity\Company;

class Combination extends \TsAccounting\Entity\Company\CombinationAbstract
{
	//Tabelle
	protected $_sTable				= 'ts_accounting_companies_combinations';

	protected $_sTableAlias			= 'ts_com_c';

	// Bearbeiter Spalte
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * Jointables
	 *
	 * @var array
	 */
	protected $_aJoinTables = array(
		'schools' => array(
			'table' => 'ts_accounting_companies_combinations_to_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'company_combination_id',
			'autoload' => false,
			'class' => 'Ext_Thebing_School',
			'readonly_class' => true, // readonly, da sonst beim Speichern der Firma auch die Schule gespeichert wird (#9549)
		),
		'services' => array(
			'table' => 'ts_accounting_companies_combinations_to_services',
			'foreign_key_field' => 'service',
			'primary_key_field' => 'company_combination_id',
			'autoload' => false
		),
		'inboxes' => array(
			'table' => 'ts_accounting_companies_combinations_to_inboxes',
			'foreign_key_field' => 'inbox_id',
			'primary_key_field' => 'company_combination_id',
			'autoload' => false,
			'class' => 'Ext_Thebing_Client_Inbox',
			'readonly_class' => true
		),
		'course_categories' => array(
			'table' => 'ts_accounting_companies_combinations_to_course_categories',
			'foreign_key_field' => 'course_category_id',
			'primary_key_field' => 'company_combination_id',
			'autoload' => false,
			'readonly_class' => true
		)
	);

	/**
	 * Joinedobjects
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'company' => array(
			'class' => Company::class,
			'key' => 'company_id',
			'bidirectional' => true,
		),
	);

	/**
	 *
	 * @return \Ext_Thebing_School <array>
	 */
	public function getSchools()
	{
		return (array)$this->getJoinTableObjects('schools');
	}

	/**
	 *
	 * @return \Ext_Thebing_Client_Inbox <array>
	 */
	public function getInboxes()
	{
		return (array)$this->getJoinTableObjects('inboxes');
	}

	/**
	 * siehe abstract
	 *
	 * @return string
	 */
	protected function _getCompaniesJoinKey()
	{
		return false;
	}

	/**
	 * siehe abstract
	 *
	 * @return string
	 */
	protected function _getSchoolJoinKey()
	{
		return 'schools';
	}

	/**
	 * siehe abstract
	 *
	 * @return string
	 */
	protected function _getInboxJoinKey()
	{
		return 'inboxes';
	}

	/**
	 * siehe parent
	 *
	 * @param array $aFreeCombinations
	 */
	public function manipulateAllocations(array $aFreeCombinations)
	{
		$aCompanyCombinations = $this->getCompany()->getCombinationsFromObjectContext();

		foreach ($aCompanyCombinations as $oCombination) {
			if ($oCombination !== $this) {
				$oCombination->removeAllocations($aFreeCombinations);
			}
		}

		return $aFreeCombinations;
	}

	/**
	 *
	 * @return \TsAccounting\Entity\Company
	 */
	public function getCompany()
	{
		return $this->getJoinedObject('company');
	}

	/**
	 * siehe parent
	 *
	 * @return int
	 */
	protected function _getErrorKey()
	{
		return $this->getJoinKey();
	}

	/**
	 * Rausfinden welchen Containerkey dieses Objekt bei den Firmen hat
	 *
	 * @return int
	 */
	public function getJoinKey()
	{
		$aCombinations = $this->getCompany()->getCombinationsFromObjectContext();

		foreach ($aCombinations as $iKey => $oCombination) {
			if ($oCombination === $this) {
				return $iKey;
			}
		}

		return 0;
	}
}
