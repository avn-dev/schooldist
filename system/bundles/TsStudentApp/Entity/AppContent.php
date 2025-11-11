<?php

namespace TsStudentApp\Entity;

class AppContent extends \Ext_Thebing_Basic
{
	protected $_sTableAlias = 'ts_sac';

	protected $_sTable = 'ts_student_app_contents';

	protected $_sEditorIdColumn = 'editor_id';

	protected $_aJoinTables = [
		'i18n' => [
			'table' => 'ts_student_app_contents_i18n',
			'foreign_key_field' => ['language_iso', 'title', 'content'],
			'primary_key_field'	=> 'entry_id'
		]
	];
}