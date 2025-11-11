<?php


class Ext_Thebing_Examination_Sections extends Ext_Thebing_Basic
{
	const TYPE_FLOAT = 1;
	const TYPE_INPUT = 2;
	const TYPE_TEXTAREA = 3;
	const TYPE_CHECKBOX = 4;
	const TYPE_DROPDOWN = 5;

	// Tabellenname
	protected $_sTable = 'kolumbus_examination_sections';

	// Tabellenalias
	protected $_sTableAlias = 'kexs';

	protected $_aFormat = array(

		'title'		=> array(
			'required'	=> true,
		),
		'entity_type_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_POSITIVE'
		),

	);

	protected $_aJoinedObjects = array(
		'options' => array(
			'class'=>'Ext_Thebing_Examination_Sections_Option',
	 		'key'=>'field_id',
			'type'=>'child',
			'check_active'=>true
		)
	);
	
	public static function getEntityTypes($bPrepareForSelect = false)
	{
		$sSql = "
			SELECT
				*
			FROM
				#table
		";

		$aSql = array(
			'table' => 'kolumbus_examination_sections_entity_type'
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if($bPrepareForSelect)
		{
			$aBack = array();
			foreach((array)$aResult as $aData)
			{
				$aBack[$aData['id']] = L10N::t($aData['title'], 'Thebing » Tuition » Resources » Examination Sections');
			}

			return $aBack;
		}

		return $aResult;
	}

	public function getSchoolSections() {

		$aSql = $this->getListQueryData();
		$aSql = DB::splitQuery($aSql['sql']);
		$sTableAlias = $this->_sTableAlias;
		if(empty($sTableAlias))	{
			$sTableAlias = $this->_sTable;
		}

		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId	= $oSchool->id;
		
		$aSql['where'] .= ' AND `kexscs`.`school_id` = '.$iSchoolId;
		$aSql['orderby'] = '`'.$sTableAlias.'`.`title` ASC';

		$aSql['from'] .= ' JOIN
				`kolumbus_examination_sections_categories_to_schools` `kexscs` ON
					`kexsc`.`id` = `kexscs`.`category_id`
				INNER JOIN
					`kolumbus_examination_sections_entity_type`
					ON `kexs`.`entity_type_id` = `kolumbus_examination_sections_entity_type`.`id`';
		$aSql['select'] .= ',`kolumbus_examination_sections_entity_type`.`title` `entity_title`, `kolumbus_examination_sections_entity_type`.`model_class`';

		$sSql = DB::buildQueryPartsToSql($aSql);

		$aResult	= DB::getQueryRows($sSql);

		if($bPrepareForSelect)
		{
			$aBack		= array();
			foreach((array)$aResult as $aData)
			{
				$aBack[$aData['id']] = $aData['category'].' - '.$aData['title'];
			}

			return $aBack;
		}

		return $aResult;
	}

	/**
	 *
	 * @return Ext_Thebing_Examination_Sections_Entity_Abstract
	 *
	 * @TODO Tabelle entfernen (macht in Datenbank überhaupt keinen Sinn)
	 */
	public function getEntityModel()
	{
		$sSql = "
			SELECT
				`kexset`.`model_class`
			FROM
				#table
			INNER JOIN
				`kolumbus_examination_sections_entity_type` `kexset`
			ON
				#table.`entity_type_id` = `kexset`.`id`
			WHERE
				#table.`id` = :id
		";

		$aSql = array(
			'table' => $this->_sTable,
			'id'	=> $this->id,
		);

		$aData = DB::getQueryCol($sSql, $aSql);
		if(is_array($aData))
		{
			$sClassName = reset($aData);
			$oModel		= new $sClassName();
			return $oModel;
		}

		return false;
	}

	public function getListQueryData($oGui = null)
	{
		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sTableAlias = $this->_sTableAlias;

		if(empty($sTableAlias))
		{
			$sTableAlias = $this->_sTable;
		}

		$sAliasString = '';
		$sAliasName = '';
		if(!empty($sTableAlias))
		{
			$sAliasString .= '`'.$sTableAlias.'`.';
			$sAliasName .= '`'.$sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					".$sAliasString."*, 
					`kexset`.`title` `entity_type`,
					`kexsc`.`id` `category_id`,
					`kexsc`.`name` `category`
					{FORMAT}
				FROM
					`{TABLE}` ".$sAliasName." INNER JOIN 
					`kolumbus_examination_sections_entity_type` `kexset` ON
						".$sAliasName.".`entity_type_id` = `kexset`.`id` INNER JOIN
					`kolumbus_examination_sections_categories` `kexsc` ON
						`kexsc`.`id` = ".$sAliasName.".`section_category_id` AND
						`kexsc`.`active` = 1
		";

		$iJoinCount = 1;

		foreach((array)$this->_aJoinTables as $sJoinAlias => $aJoinData)
		{

			$aQueryData['sql'] .= " LEFT OUTER JOIN
									#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
									#join_alias_".$iJoinCount.".#join_pk_".$iJoinCount." = ".$sAliasString."`id`
								";

			$aQueryData['data']['join_table_'.$iJoinCount]	=  $aJoinData['table'];
			$aQueryData['data']['join_pk_'.$iJoinCount]		=  $aJoinData['primary_key_field'];
			$aQueryData['data']['join_alias_'.$iJoinCount]	=  $sJoinAlias;

			$iJoinCount++;
		}

		if(array_key_exists('active', $this->_aData))
		{
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		if(count($this->_aJoinTables) > 0)
		{
			$aQueryData['sql'] .= "GROUP BY ".$sAliasString."`id` ";
		}

		if(array_key_exists('id', $this->_aData))
		{
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;
	}

	/*
	 * Liefert die Options (falls es ein Dropdown ist)
	 */
	public function getOptions()
	{
		$aBack = array();

		if($this->entity_type_id == 5)
		{
			$sSql = "SELECT
							*
						FROM
							`kolumbus_examination_sections_options`
						WHERE
							`field_id` = :section_id AND
							`active` = 1
						ORDER BY
							`position`
					";
			$aSql = array();
			$aSql['section_id'] = (int)$this->id;

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aResult as $aData)
			{
				$aBack[] = Ext_Thebing_Examination_Sections_Option::getInstance($aData['id']);
			}
		}

		return $aBack;
	}
}