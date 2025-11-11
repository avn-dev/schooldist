<?php

/**
 * @todo Durch Ext_TC_Validity ersetzen 
 */
class Ext_Thebing_Validity_WDBasic extends Ext_TC_Validity
{
	protected $_sTable = 'kolumbus_validity';
	protected $_sTableAlias = 'kv';

	protected static $_sStaticTable = 'kolumbus_validity';

	protected $_sEditorIdColumn = 'user_id';

	public function  __get($sName)
	{
		if($sName=='item_name')
		{
			return null;
		}
		else
		{
			return parent::__get($sName);
		}
	}

	public function getLatestEntry($bIncludeSelf = false, $bWithEndDate = false, $iDependencyId = null)
	{
		$sWhere = '';

		if(!$bIncludeSelf)
		{
			$sWhere .= " AND `id` != :id";
		}
		if(!$bWithEndDate)
		{
			$sWhere .= " AND `valid_until` = '0000-00-00'";
		}
		else
		{
			$sWhere .= " AND `valid_until` != '0000-00-00'";
		}

		$sSql = "
				SELECT
					`id`
				FROM
					#table
				WHERE
					`active` = 1 AND
					`parent_id` = :parent_id AND
					`parent_type` = :parent_type AND
					`item_type`	= :item_type
					".$sWhere."
				ORDER BY
					`valid_from` DESC
				LIMIT 1
					";
		$aSql = array(
			'table'			=> $this->_sTable,
			'id'			=> $this->id,
			'parent_id'		=> $this->parent_id,
			'parent_type'	=> $this->parent_type,
			'item_type'		=> $this->item_type,
		);

		$iLastEntry = DB::getQueryOne($sSql, $aSql);

		return $iLastEntry;
	}

	/**
	 * Passende Gültigkeit für diesen Zeitraum
	 * @param <string> $sParentType
	 * @param <string> $sItemType
	 */
	public static function getValidity($sParentType, $iParentId, $sItemType)
	{
		$oWdDate	= new WDDate();
		$dDate		= $oWdDate->get(WDDate::DB_DATE);

		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`parent_type` = :parent_type AND
				`parent_id` = :parent_id AND
				`item_type` = :item_type AND
				IF(
					 `valid_until` = '0000-00-00',
					 :current_date > `valid_from`,
					 :current_date BETWEEN `valid_from` AND `valid_until`
				) AND
				`active` = 1
		";

		$aSql = array(
			'table'			=> self::$_sStaticTable,
			'parent_type'	=> $sParentType,
			'parent_id'		=> $iParentId,
			'item_type'		=> $sItemType,
			'current_date'	=> $dDate
		);

		$aResult = DB::getQueryRow($sSql, $aSql);

		return $aResult;
	}
	
	/**
	 * Analog zu Ext_TC_Validity
	 * @return Ext_Thebing_Cancellation_Group
	 */
	public function getItem() {
		return Ext_Thebing_Cancellation_Group::getInstance($this->item_id);
	}
	
	/**
	 * Analog zu Ext_TC_Validity
	 * @return Ext_Thebing_Agency|Ext_Thebing_School
	 */
	public function getParent() {
		
		switch($this->parent_type) {
			case 'school':
				return Ext_Thebing_Agency::getInstance($this->parent_id);
			case 'agency':
				return Ext_Thebing_Agency::getInstance($this->parent_id);				
		}
		
	}
	
}