<?php

	/**
	 * Class Ext_Thebing_Placementtests_Question_Category
	 *
	 * @property string id
	 * @property string changed
	 * @property string created
	 * @property string editor_id
	 * @property string active
	 * @property string creator_id
	 * @property string placementtest_id
	 * @property string category
	 * @property string position
	 */
class Ext_Thebing_Placementtests_Question_Category extends Ext_Thebing_Basic {
	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'ts_placementtests_categories';

	/**
	 * Tabellenalias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'kptc';

	protected $_aJoinedObjects = [
		'questions' => [
			'class' => Ext_Thebing_Placementtests_Question::class,
			'type' => 'child',
			'key' => 'idCategory',
			'bidirectional' => true,
			'check_active' => true,
			'orderby' => 'position',
			'cloneable' => false
		],
	];

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'placementtest_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'category' => array(
			'required'	=> true,
		),
	);

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	public static function getSelectOptions() {
		return self::query()
			->pluck('category', 'id')
			->toArray();
	}

	/**
	 * @return \Ext_Thebing_Placementtests_Question[]
	 */
	public function getQuestions() {
		return $this->getJoinedObjectChilds('questions');
	}

}