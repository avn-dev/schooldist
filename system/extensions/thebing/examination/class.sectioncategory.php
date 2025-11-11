<?php

class Ext_Thebing_Examination_SectionCategory extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'kolumbus_examination_sections_categories';

	// Tabellenalias
	protected $_sTableAlias = 'kexsc';

	protected $_aFormat = array(
		'name' => array(
			'required'	=> true,
		),
	);

	protected $_aJoinTables = [
        'schools' => [
            'table' => 'kolumbus_examination_sections_categories_to_schools',
            'class' => \Ext_Thebing_School::class,
            'foreign_key_field' => 'school_id',
            'primary_key_field' => 'category_id',
        ]
	];
	
	protected $_aJoinedObjects = array(
		'areas' => array(
			'class'=>'Ext_Thebing_Examination_Sections',
	 		'key'=>'section_category_id',
			'type'=>'child',
			'check_active'=>true
		)
	);

	public static function getOptionList(){
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_examination_sections_categories` `kexsc` JOIN
				`kolumbus_examination_sections_categories_to_schools` `kexscs` ON
					`kexsc`.`id` = `kexscs`.`category_id`
			WHERE
				`kexsc`.`active` = 1 AND
				`kexscs`.`school_id` = :school_id
		";
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		
		$aSql = array(
			'school_id' => (int)$oSchool->id
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aOptions = array();

		foreach($aResult as $aRowData)
		{
			$aOptions[$aRowData['id']] = $aRowData['name'];
		}

		return $aOptions;
	}
}
