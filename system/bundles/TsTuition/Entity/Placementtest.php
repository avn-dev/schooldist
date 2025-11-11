<?php

namespace TsTuition\Entity;

class Placementtest extends \Ext_Thebing_Basic
{
	protected $_sTable = 'ts_placementtests';

	protected $_sTableAlias = 'ts_pt';

	protected $_aJoinedObjects = [
		'questions' => [
			'class' => \Ext_Thebing_Placementtests_Question::class,
			'key' => 'placementtest_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true,
			'on_delete' => 'cascade',
			'orderby' => 'position',
			'cloneable' => true
		],
		'categories' => [
			'class' => \Ext_Thebing_Placementtests_Question_Category::class,
			'key' => 'placementtest_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true,
			'on_delete' => 'cascade',
			'orderby' => 'position',
			'cloneable' => true
		]
	];

	/**
	 * @return \Ext_Thebing_Placementtests_Question[]
	 */
	public function getQuestions(): array
	{
		return $this->getJoinedObjectChilds('questions');
	}

	/**
	 * @return \Ext_Thebing_Placementtests_Question_Category[]
	 */
	public function getCategories(): array
	{
		return $this->getJoinedObjectChilds('categories');
	}

	public static function getSelectOptions()
	{
		return self::query()->pluck('name', 'id')->toArray();
	}

	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array())
	{
		$clone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);

		$oldCategories = array_column($this->getCategories(), 'id');
		$newCategories = array_column($clone->getCategories(), 'id');

		// Fremdschlüssel bei dieser Dreiecksbeziehung umschreiben
		foreach ($clone->getQuestions() as $question) {
			$index = array_search($question->idCategory, $oldCategories);
			$question->idCategory = $newCategories[$index];
		}

		return $clone;
	}

	public function getAccuracyInPercent() {
		// Wenn das Feld nicht gesetzt ist, wird mit 100% gerechnet.
		if ($this->placementtest_accuracy_in_percent === null) {
			return 100;
		} else {
			return $this->placementtest_accuracy_in_percent;
		}
	}

	public static function getPlacementtestByCourseLanguage($courseLanguageId) {
		return self::query()
			->where('courselanguage_id', $courseLanguageId)
			->get()
			->first();
	}
}