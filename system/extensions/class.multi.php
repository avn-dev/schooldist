<?php

class Ext_Multi extends WDBasic {

	protected $_sTable = 'multi_init';

	protected static $_aCacheCategories	= array();

	protected static $_aCacheFields		= array();

	/**
	 * Get the categories
	 * 
	 * @return array
	 */
	public function getCategories() {

		if(empty(self::$_aCacheCategories[$this->id])) {

			$sSQL = "
				SELECT
					`id`,
					`name`
				FROM
					`multi_categories`
				WHERE
					`active` = 1 AND
					`multi_id` = :iMultiID
				ORDER BY
					`position`,
					`name`
			";
			$aSQL = array(
				'iMultiID' => $this->id
			);

			self::$_aCacheCategories[$this->id] = (array)DB::getQueryPairs($sSQL, $aSQL);
		}

		return self::$_aCacheCategories[$this->id];
	}

	/**
	 * Get the data fields
	 * 
	 * @return array
	 */
	public function getFields() {
		
		if(empty(self::$_aCacheFields[$this->id])) {
			
			$sSQL = "
				SELECT
					*
				FROM
					`multi_fields`
				WHERE
					`multi_id` = :iMultiID
				ORDER BY
					`position`,
					`name`
			";
			$aSQL = array(
				'iMultiID' => $this->id
			);
			self::$_aCacheFields[$this->id] = (array)DB::getPreparedQueryData($sSQL, $aSQL);
		}

		return self::$_aCacheFields[$this->id];
	}

	/**
	 * Write the multi table
	 * 
	 * @param int $idMulti
	 */
	public static function writeMultiDataTable($idMulti) {
		global $db_data;

		include_once Util::getDocumentRoot().'system/includes/functions.inc.php';
		
		$arrFieldInfo = array();
		$sQuery = "CREATE TABLE `multi_table_".$idMulti."` (`id` INT NOT NULL AUTO_INCREMENT, `created` TIMESTAMP NOT NULL,`category_id` INT NOT NULL , `language_code` VARCHAR( 2 ) NOT NULL, `title` TEXT NOT NULL ,";
		$rFields = (array)DB::getQueryRows("SELECT field_id as id,type FROM multi_fields WHERE multi_id = ".(int)$idMulti." ORDER BY position");
		foreach($rFields as $aFields) {
			$arrFieldInfo[$aFields['id']] = $aFields;
			switch($aFields['type']) {
				case "int":
					$sQuery .= "`field_".(int)$aFields['id']."` INT NOT NULL ,";
					break;
				case "date":
					$sQuery .= "`field_".(int)$aFields['id']."` timestamp NOT NULL ,";
					break;
				default:
					$sQuery .= "`field_".(int)$aFields['id']."` TEXT NOT NULL ,";
					break;
			}
		}
		$sQuery .= "`validfrom` timestamp NOT NULL,`validuntil` timestamp NOT NULL,`position` INT NOT NULL , PRIMARY KEY ( `id` ), INDEX(`category_id`))";

		DB::executeQuery("DROP TABLE IF EXISTS `multi_table_".(int)$idMulti."`");
		DB::executeQuery($sQuery);

		$sSql = "
					SELECT 
						e.*, 
						d.field_id, 
						d.value 
					FROM 
						multi_entry e LEFT OUTER JOIN 
						multi_data d 
							ON 
								e.id = d.entry_id 
					WHERE 
						e.id = d.entry_id AND 
						e.active = 1 AND e.multi_id = ".(int)$idMulti." 
					ORDER BY 
						d.entry_id, 
						d.field_id
					";
		$aFieldData = DB::getQueryData($sSql);

		$sQuery = "";
		foreach((array)$aFieldData as $iKey=>$aData) {

			if(
				!isset($aFieldData[$iKey-1]) ||
				$aFieldData[$iKey-1]['id'] != $aData['id']
			) {

				$sQuery = "
						INSERT INTO 
							multi_table_".$idMulti." 
						SET 
							`id` = ".(int)$aData['id'].", 
							`created` = '".\DB::escapeQueryString($aData['created'])."', 
							`category_id` = '".\DB::escapeQueryString($aData['category_id'])."', 
							`language_code` = '".\DB::escapeQueryString($aData['language_code'])."', 
							`title` = '".\DB::escapeQueryString($aData['name'])."', 
							`validfrom` = '".\DB::escapeQueryString($aData['validfrom'])."', 
							`validuntil` = '".\DB::escapeQueryString($aData['validuntil'])."',  
							";
			}

			if(isset($arrFieldInfo[$aData['field_id']])) {
				switch($arrFieldInfo[$aData['field_id']]['type']) {
					case "int":
						$sQuery .= "`field_".$aData['field_id']."` = ".(int)$aData['value'].", ";
						break;
					case "date":
						$sQuery .= "`field_".$aData['field_id']."` = '".\DB::escapeQueryString(strtotimestamp($aData['value'], 1))."', ";
						break;
					default:
						$sQuery .= "`field_".$aData['field_id']."` = '".\DB::escapeQueryString($aData['value'])."', ";
						break;
				}
			}

			if(
				!isset($aFieldData[$iKey+1]) ||
				$aFieldData[$iKey+1]['id'] != $aData['id']
			) {
				$sQuery .= "`position` = '".\DB::escapeQueryString($aData['position'])."'";
				DB::executeQuery($sQuery);
			}
		}
	}
	
	public static function getValidQueryPart($sSelectMode) {
		
		if($sSelectMode == 'archive') {
			$sValidQuery = "NOW() > e.validfrom AND NOW() > e.validuntil";
		} elseif($sSelectMode == 'all') {
			$sValidQuery = "1";
		} else {
			$sValidQuery = "
				NOW() >= e.validfrom AND 
				(
					NOW() <= e.validuntil OR 
					e.validuntil = 0
				)
				";
		}
		
		return $sValidQuery;
		
	}
	
}