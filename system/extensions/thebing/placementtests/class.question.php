<?php

/**
 * Class Ext_Thebing_Placementtests_Question
 *
 * @property string id
 * @property string changed
 * @property string created
 * @property string editor_id
 * @property string active
 * @property string creator_id
 * @property string placementtest_id
 * @property string text
 * @property string type
 * @property string position
 * @property string idCategory
 * @property string optional
 * @property string always_evaluate
 */
class Ext_Thebing_Placementtests_Question extends Ext_Thebing_Basic {

	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'ts_placementtests_questions';

	/**
	 * Tabellenalias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'kptq';

	protected $_aJoinedObjects = [
		'placementtest' => [
			'class' => \TsTuition\Entity\Placementtest::class,
			'type' => 'parent',
			'key' => 'placementtest_id',
			'bidirectional' => true,
			'check_active' => true
		],
		'answers' => [
			'class' => Ext_Thebing_Placementtests_Question_Answer::class,
			'type' => 'child',
			'key' => 'idQuestion',
			'bidirectional' => true,
			'check_active' => true,
			'orderby' => 'position',
			'cloneable' => true
		],
		'category' => [
			'class' => Ext_Thebing_Placementtests_Question_Category::class,
			'type' => 'parent',
			'key' => 'idCategory',
			'bidirectional' => true,
			'check_active' => true
		],
	];

	protected $_sEditorIdColumn = 'editor_id';

	const TYPE_CHECKBOX = 2;
	const TYPE_TEXT = 3;
	const TYPE_TEXTAREA = 4;
	const TYPE_SELECT = 5;
	const TYPE_MULTISELECT = 6;

	/**
	 * @var array
	 */
	protected $_aFormat = [
		'placementtest_id' => [
			'required' => true,
			'validate' => 'INT_POSITIVE'
		],
		'text' => [
			'required' => true,
		],
		'type' => [
			'required' => true,
			'validate' => 'INT_POSITIVE'
		],
		'idCategory' => [
			'required' => true,
			'validate' => 'INT_POSITIVE'
		],
		'position' => [
			'validate' => 'INT_NOTNEGATIVE'
		],
	];

	/**
	 * @param $sDescriptionPart
	 * @param bool $bWithEmptyItem
	 * @return array
	 */
	public static function getTypesOptions($sDescriptionPart, $bWithEmptyItem = false) {

		$aTypes = [
			self::TYPE_CHECKBOX => L10N::t("Checkbox",$sDescriptionPart),
			self::TYPE_TEXT => L10N::t("Text" ,$sDescriptionPart),
			self::TYPE_TEXTAREA => L10N::t("Textarea" ,$sDescriptionPart),
			self::TYPE_SELECT => L10N::t("Select" ,$sDescriptionPart),
			self::TYPE_MULTISELECT => L10N::t("Multiselect" ,$sDescriptionPart),
		];

		if($bWithEmptyItem) {
			$aTypes = Ext_Thebing_Util::addEmptyItem($aTypes, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));
		}

		return $aTypes;
	}

	public function getCategory() {
		return $this->getJoinedObject('category');
	}

	/**
	 * @return \Ext_Thebing_Placementtests_Question_Answer[]
	 */
	public function getAnswers() {
		return $this->getJoinedObjectChilds('answers');
	}

	public function getCorrectAnswers() {
		return Ext_Thebing_Placementtests_Question_Answer::query()
			->where('right_answer', 1)
			->where('idQuestion', $this->id)
			->get();
	}

}