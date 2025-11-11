<?php

/**
 * @TODO Umbenennen
 */
class Ext_Thebing_Marketing_TeacherCategories extends Ext_Thebing_Basic {

	// TODO Umbenennen
	protected $_sTable = 'kolumbus_costs_kategorie_teacher';

	protected $_sTableAlias = 'kckt';

	protected $_aJoinTables = array(
		// TODO Umbenennen
			'teacher_selaries'=>array(
				'table'=>'kolumbus_teacher_salary',
				'primary_key_field'=>'costcategory_id',
				//'delete_check'=>true,
				'on_delete' => 'casade',
				'check_active'=> true,
				'cloneable' => false // Wichtig, die dürfen nicht einfach mitkopiert werden
			)
	);

}
