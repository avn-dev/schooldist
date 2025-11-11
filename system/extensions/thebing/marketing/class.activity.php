<?php
class Ext_Thebing_Marketing_Activity extends Ext_Thebing_Basic
{

	protected $_sTable = 'kolumbus_activity';

	public function getList($iClientId, $bPrepareForSelect=false)
	{
		$aBack = array();

		$aQuery			= $this->getListQueryData();
		$aQueryParts	= DB::splitQuery($aQuery['sql']);
		$aQueryParts['orderby'] = "`".$this->_sTable."`.`title` ASC";

		$sSql = DB::buildQueryPartsToSql($aQueryParts);

		$aSql = array(
		);

		$aResult = DB::getQueryRows($sSql, $aSql);

		if(!$bPrepareForSelect)
		{
			$aBack = $aResult;
		}
		else
		{
			foreach((array)$aResult as $aItem)
			{
				$aBack[$aItem['id']] = $aItem['title'];
			}
		}

		return $aBack;
	}

}
?>