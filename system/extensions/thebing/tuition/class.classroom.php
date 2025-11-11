<?php

/**
* @property $id 
* @property $changed 	
* @property $created 	
* @property $active 	
* @property $creator_id 	
* @property $user_id 	
* @property $school_id 	
* @property $title
*/
class Ext_Thebing_Tuition_Classroom extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_classroom';

	// Tabellenalias
	protected $_sTableAlias = 'kc';

	protected $_sPlaceholderClass = \TsTuition\Service\Placeholder\Classroom::class;

	protected $_aJoinTables = array(
		'rooms'=>array(
			'table'=>'kolumbus_tuition_blocks_to_rooms',
			'class'=>'Ext_Thebing_School_Tuition_Block',
			'primary_key_field'=>'room_id',
			'foreign_key_field'=>'block_id',
			'autoload'=>false,
			'delete_check'=>true,
			'on_delete' => 'cascade'
		),
		'tags'=>array(
			'table'=>'ts_classrooms_to_tags',
			'class'=>\TsTuition\Entity\Classroom\Tag::class,
			'foreign_key_field'=>'tag_id',
			'primary_key_field'=>'classroom_id'
		)
	);

	protected $_aFlexibleFieldsConfig = [
		'tuition_course_classrooms' => []
	];

    /**
     * Klassenraum für Online-Kurse?
     *
     * @return bool
     */
	public function isOnline(): bool {
	    return ($this->online == 1);
    }

	/**
	 * Klassenraum für Online-Kurse?
	 *
	 * @return bool
	 */
	public function isOffline(): bool {
		return ($this->online == 0);
	}

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {

		$aQueryData = array();
		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolID	= $oSchool->id;

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sAliasString .= '`'.$this->_sTableAlias.'`.';
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					`kc`.*,
					`ksf`.`title` `floor`,
					`ksb`.`title` `building`,
					GROUP_CONCAT(DISTINCT `ts_ct`.`tag` SEPARATOR '{#}') `tags`
					{FORMAT}
				FROM
					`{TABLE}` ".$sTableAlias." LEFT JOIN 
					`kolumbus_school_floors` `ksf` ON 
						".$sTableAlias.".`floor_id` = `ksf`.`id` LEFT JOIN
					`kolumbus_school_buildings` `ksb` ON 
						`ksf`.`building_id` = `ksb`.`id` LEFT JOIN
					`ts_classrooms_to_tags` `ts_ctt` ON
						`ts_ctt`.`classroom_id` = ".$sTableAlias.".`id` LEFT JOIN
					`ts_classrooms_tags` `ts_ct` ON
						`ts_ctt`.`tag_id` = `ts_ct`.`id`
			";

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		$aQueryData['sql'] .= "GROUP BY ".$sAliasString."`id` ";
		
		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	/**
	 * @return Ext_Thebing_Tuition_Floors
	 */
	public function getFloor() {
		$iFloorId = (int)$this->floor_id;
		$oFloor = Ext_Thebing_Tuition_Floors::getInstance($iFloorId);
		return $oFloor;
	}
	
	static public function getTagsCacheKey($classroomId) {
		return __CLASS__.'::TAGS::'.$classroomId;
	}
	
	static public function getTags($classroomId) {
		
		$cacheKey = self::getTagsCacheKey($classroomId);
		
		$tags = \WDCache::get($cacheKey);
		$tags = null;

		if($tags === null) {
		
			$classroom = self::getInstance($classroomId);
			$tagObjects = $classroom->getJoinTableObjects('tags');

			$tags = array_column($tagObjects, 'tag');

			$tags = array_filter($tags, fn ($tag) => !empty($tag));

			\WDCache::set($cacheKey, 60*60*24, $tags);
			
		}
		 
		return $tags;
	}

	public function isUsed(): bool {

		$firstBlock = Ext_Thebing_School_Tuition_Block::query()
			->select('kolumbus_tuition_blocks.*')
			->join('kolumbus_tuition_blocks_to_rooms', function ($join) {
				$join->on('kolumbus_tuition_blocks_to_rooms.block_id', '=', 'kolumbus_tuition_blocks.id')
					->where('kolumbus_tuition_blocks_to_rooms.room_id', $this->id);
			})->first();

		return $firstBlock !== null;
	}

	public function delete() {

		if ($this->isUsed()) {
			return ['rooms' => 'EXISTING_JOINED_ITEMS'];
		}

		return parent::delete();
	}

	public function save($bLog = true) {
		
		$return = parent::save($bLog);
		
		\WDCache::delete(self::getTagsCacheKey($this->id));
		
		// Ungenutzte Tags bereinigen
		\DB::executeQuery("DELETE `ts_ct` FROM `ts_classrooms_tags` `ts_ct` LEFT JOIN `ts_classrooms_to_tags` `ts_ctt` ON `ts_ct`.`id` = `ts_ctt`.`tag_id` WHERE `ts_ctt`.`tag_id` IS NULL");

		return $return;
	}
	
}
