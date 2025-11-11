<?php

/**
 * Class Ext_Thebing_Placementtests_Question_Answer
 *
 * @property string id
 * @property string changed
 * @property string created
 * @property string editor_id
 * @property string active
 * @porperty string creator_id
 * @porperty string position
 * @property string text
 * @property string idQuestion
 * @property string right_answer
 */
class Ext_Thebing_Placementtests_Question_Answer extends Ext_Thebing_Basic {

	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'ts_placementtests_questions_answers';

	/**
	 * Tabellenalias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'kpta';

	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'placementtest_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'idQuestion' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),
		'text' => array(
			'required'	=> true,
		),
		'position' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		),
		'right_answer' => array(
			'validate'	=> 'INT_NOTNEGATIVE',
		)
	);

}