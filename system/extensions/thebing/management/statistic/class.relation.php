<?php

class Ext_Thebing_Management_Statistic_Relation extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable			 = 'kolumbus_statistic_cols_definitions_access';

	// Definitionstabelle
	protected $_sDefinitionTable = 'kolumbus_statistic_cols_definitions';

	// The columns titles
	protected $_aTitles			 = array();

	// Modulename für Description
	static public $sModul		 = 'Thebing » Admin » Statistik » Abhängigkeit';

	/**
	 * 
	 * Get all columns titles
	 * 
	 * @return array
	 */
	public function getAllTitles(){
		return $this->_aTitles;
	}


	/**
	 * Get ralations by type
	 * 
	 * @param int $iType
	 * @return array
	 */
	public function getRelationsByType($iType){

		$iType			= (int)$iType;
		$aRelations		= array();

		if( 2 == $iType ){
			$sDbColumn = 'detail';
		}else{
			$sDbColumn = 'sum';
		}

		if( 0 >= $iType ){
			return array();
		}

		$sSql = "
			SELECT
				`kstcd`.`title`,
				`kstcg`.`title` AS `group`,
				`kstcd`.`id` as dimension_y,
				GROUP_CONCAT(
					CAST(
						`kstcda`.`x_id` AS CHAR
					)
				) as dimension_x
			FROM
				`" . $this->_sDefinitionTable . "` `kstcd` INNER JOIN
				`kolumbus_statistic_cols_groups` AS `kstcg` ON
					`kstcd`.`group_id` = `kstcg`.`id` AND `kstcg`.`active` = 1
			LEFT OUTER JOIN
				`" . $this->_sTable . "` `kstcda`
			ON
				`kstcd`.`id` = `kstcda`.`y_id` AND
				IFNULL(`kstcda`.`type`, " . $iType . ") = " . $iType . "
			WHERE
				`kstcd`.`" . $sDbColumn . "` = 1 AND
				`kstcd`.`active` = 1
			GROUP BY
				`kstcd`.`title`
			ORDER BY
				`kstcd`.`id` ASC
		";
		$aResult = DB::getQueryData($sSql);

		foreach((array)$aResult as $aDimension){

			if( null !== $aDimension['dimension_x']){
				$aDimensionX = explode(',', $aDimension['dimension_x']);
			}else{
				$aDimensionX = array();
			}

			if(2 == $iType)
			{
				$aDimension['title'] = '(' . $aDimension['group'] . ') ' . $aDimension['title'];
			}

			$aRelations[$aDimension['title']]['data']		 = $aDimensionX;
			$aRelations[$aDimension['title']]['dimension_y'] = $aDimension['dimension_y'];
			$this->_aTitles[$aDimension['dimension_y']]		 = $aDimension['title'];
		}

		return $aRelations;
	}


	// Objekt mit ID erneut laden anhand der anderen Felder
	// @return object Ext_Thebing_Management_Statistic_Relation
	public function loadByData(){

		if( 
			0 < $this->x_id &&
			0 < $this->y_id &&
			0 < $this->type
		){
			$sSql = "
				SELECT
					`kstcda`.`id`
				FROM
					#sTable AS `kstcda`
				WHERE
					`kstcda`.`x_id` = :iX AND
					`kstcda`.`y_id` = :iY AND
					`kstcda`.`type` = :iType
				LIMIT 1
			";
			$aSql = array(
				'sTable'	=> $this->_sTable,
				'iX'		=> $this->x_id,
				'iY'		=> $this->y_id,
				'iType'		=> $this->type
			);
			$aResults = DB::getPreparedQueryData($sSql, $aSql);

			if( is_array($aResults) && 0 < count($aResults) ){
				$iDefinitionsAccessId = $aResults[0]['id'];
				$this->_loadData($iDefinitionsAccessId);
			}
		}

		return $this;
	}


	// wir löschen wirklich
	// @return boolean
	public function delete() {
		
		$bAffected = false;

		if( 0 < $this->id )
		{
			$sSql = "
				DELETE FROM
					#sTable
				WHERE
					`id` = :iID
			";
			$aSql = array(
				'sTable'	=> $this->_sTable,
				'iID'		=> $this->id
			);

			try{
				$bAffected = (bool)DB::executePreparedQuery($sSql, $aSql);
			}catch(Exception $e){
				//
			}

		}

		return $bAffected;
	}
}
