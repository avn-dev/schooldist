<?php

// TODO Was ist das? Das wird nur noch bei Prüfungen verwendet.
class Ext_Thebing_Inquiry_Course_Search
{
	protected $_sTable			= 'ts_inquiries_journeys_courses';
	protected $_sTableAlias		= 'kic';
	protected $_aSelect			= array();
	protected $_aWhere			= array();
	protected $_aJoin			= array();
	protected $_sOrder			= '`kic`.`from` ASC';
	protected $_aValues			= array();

	public static $_aCache = array();

	public function setSelect($sSelect)
	{
		$this->_aSelect[] = $sSelect;
	}

	public function setWhere($sWhere)
	{
		$this->_aWhere[] = $sWhere;
	}

	public function setJoin($sJoin)
	{
		$this->_aJoin[] = $sJoin;
	}

	public function setOrder($sOrder)
	{
		$this->_sOrder = $sOrder;
	}

	public function setValue($sKey, $mValue)
	{
		$this->_aValues[$sKey] = $mValue;
	}

	public static function clearCache()
	{
		self::$_aCache = array();
	}

	public function getResult()
	{
		// select part
		$sSql = "
			SELECT
				`kic`.*,
				UNIX_TIMESTAMP(`kic`.`from`) AS `from`,
				UNIX_TIMESTAMP(`kic`.`until`) AS `until`,
				UNIX_TIMESTAMP(`kic`.`created`) AS `created`,
				`ts_i_j`.`inquiry_id` `inquiry_id` 
		";
		foreach((array)$this->_aSelect as $sSelect)
		{
			$sSql .= ", ".$sSelect;
		}

		// from part
		$sSql .= " FROM
						`ts_inquiries_journeys` `ts_i_j` INNER JOIN
						#table AS #table_alias ON
							#table_alias.`journey_id` = `ts_i_j`.`id`
		";
		foreach((array)$this->_aJoin as $sJoin)
		{
			$sSql .= " ".$sJoin." ";
		}

		// where part
		$sSql .= " WHERE
				`ts_i_j`.`active` = 1 AND 
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`kic`.`active` = 1
		";
		foreach((array)$this->_aWhere as $sWhere)
		{
			$sSql .= " AND
				".$sWhere."
			";
		}

		$sSql .= "
			ORDER BY
				".$this->_sOrder."
		";

		$aSql					= $this->_aValues;
		$aSql['table']			= $this->_sTable;
		$aSql['table_alias']	= $this->_sTableAlias;

		$sKey = md5(implode('-', $aSql).$sSql);

		//if(!isset(self::$_aCache[$sKey])) {
			self::$_aCache[$sKey] = DB::getPreparedQueryData($sSql, $aSql);
		//}
		
		return self::$_aCache[$sKey];

	}

}