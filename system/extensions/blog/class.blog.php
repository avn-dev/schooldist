<?php

class Ext_Blog_Blog
{
	private $_aData = array();
	private $_iId;

	public function __construct($iId = null)
	{
		if((int)$iId < 0)
		{
			return;
		}

		if(!isset($iId) || $iId == null) {
			$this->_addBlog();
		}
		if(isset($iId) && (int)$iId > 0) {
			$this->_iId = $iId;
		}
		$this->_fetchData();
	}

	private function _fetchData()
	{
		$sSql = "
			SELECT 
				*
			FROM
				`blog`
			WHERE
				`id` = :iId
			LIMIT 1
		";

		$aSql = array('iId' => $this->_iId);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aData = $aResult[0];
	}

	private function _addBlog()
	{
		$sSql = "
			INSERT INTO
				`blog`
			SET
				`created` = NOW()
		";

		DB::executeQuery($sSql);
		$this->_iId = DB::fetchInsertID();
		$this->_fetchData();
	}

	public function __get($sField)
	{
		if(isset($this->_aData[$sField])) {
			return $this->_aData[$sField];
		}
	}

	public function __set($sField, $mValue)
	{
		if(isset($this->_aData[$sField])) {
			$this->_aData[$sField] = $mValue;
		}
	}

	public function saveData()
	{
		$sSql = "
   			UPDATE
   				`blog`
			SET
				`active`	= :iActive,
				`title`		= :sTitle
			WHERE
				`id` = :iId
		";

		$aSql = array(
					'iId'		=> $this->_iId,
					'iActive'	=> $this->_aData['active'],
					'sTitle'	=> $this->_aData['title']
				);

		DB::executePreparedQuery($sSql, $aSql);
	}

	public function deleteBlog()
	{
		$aResult = $this->getEntriesList();

		foreach($aResult as $aEntry)
		{
			$oEntry = new Ext_Blog_BlogEntry($this->_iId, $aEntry['id']);
			$oEntry->deleteEntry();
			unset($oEntry);
		}

		$sSql = "
			DELETE FROM
				`blog`
			WHERE
				`id` = :iId
			LIMIT
				1
		";

		$aSql = array('iId' => $this->_iId);
		DB::executePreparedQuery($sSql, $aSql);
	}

	public function getBlogsList()
	{
		$sSql = "
			SELECT 
				*
			FROM
				`blog`
			ORDER BY
				`created` DESC
		";

		return DB::getQueryData($sSql);
	}

	public function getEntriesList($aLimit = array(0, 999999999))
	{
		if(isset($aLimit[0])) {
			$iLimit = $aLimit[0];
		}
		if(isset($aLimit[1])) {
			$iOffset = $aLimit[1];
		} else {
			$iOffset = 999999999;
		}

		$sSql = "
			SELECT 
				*,
				UNIX_TIMESTAMP(`created`) as `created`
			FROM
				`blog_entries`
			WHERE
				`blog_id` = :iBlogId
			ORDER BY
				`created` DESC
			LIMIT
				:intLimit , :intOffset
		";
		$aSql = array(
					'iBlogId'	=> $this->_iId,
					'intLimit'	=> (int)$iLimit,
					'intOffset'	=> (int)$iOffset
				);
		return DB::getPreparedQueryData($sSql, $aSql);
	}
}

?>