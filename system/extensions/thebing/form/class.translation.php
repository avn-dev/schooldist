<?php

/**
 * @property integer $id
 * @property integer $active
 * @property integer $creator_id
 * @property integer $user_id
 * @property string $item
 * @property integer $item_id
 * @property string $language
 * @property string $field
 * @property string $content
 */
class Ext_Thebing_Form_Translation extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_forms_translations';

	protected $_sTableAlias = 'kft';

	protected $_aFormat = array(
		'item' => array(
			'required' => true
		),
		'item_id' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		),
		'language' => array(
			'required' => true
		),
		'field' => array(
			'required' => true
		)
	);

}
