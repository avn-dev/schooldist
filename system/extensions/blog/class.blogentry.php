<?php

class Ext_Blog_BlogEntry
{
	private $_aData = array();
	private $_iId;

	public function __construct($iBlogId, $iId = 0)
	{
		if($iId < 0)
		{
			return;
		}

		// !!! Only in association with a Blog-ID !!!
		if(!isset($iBlogId) || (int)$iBlogId <= 0) {
			return false;
		}
		$this->_aData['blog_id'] = $iBlogId;

		if(!isset($iId) || $iId == 0) {
			$this->_addEntry();
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
				*,
				UNIX_TIMESTAMP(`created`) as `created`
			FROM
				blog_entries
			WHERE
					`id` = :iId
				AND
					`blog_id` = :iBlogId
			LIMIT 1
		";

		$aSql = array(
					'iId'		=> $this->_iId,
					'iBlogId'	=> $this->_aData['blog_id']
				);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aData = $aResult[0];
	}

	private function _addEntry()
	{
		$sSql = "
			INSERT INTO
				`blog_entries`
			SET
				`created` = NOW(),
				`blog_id` = :iBlogId
		";

		$aSql = array('iBlogId' => $this->_aData['blog_id']);
		DB::executePreparedQuery($sSql, $aSql);
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
   				`blog_entries`
			SET
				`active`	= :iActive,
				`blog_id`	= :iBlogId,
				`user_id`	= :iUserId,
				`title`		= :sTitle,
				`text`		= :sText
			WHERE
				`id` = :iId
		";

		$aSql = array(
					'iId'		=> $this->_iId,
					'iActive'	=> $this->_aData['active'],
					'iBlogId'	=> $this->_aData['blog_id'],
					'iUserId'	=> $this->_aData['user_id'],
					'sTitle'	=> $this->_aData['title'],
					'sText'		=> $this->_aData['text']
				);
		DB::executePreparedQuery($sSql, $aSql);
	}

	public function deleteEntry()
	{
		$sSql = "
			DELETE FROM
				`blog_comments`
			WHERE
				`entry_id` = :iId
		";
		$aSql = array('iId' => $this->_iId);
		DB::executePreparedQuery($sSql, $aSql);

		$sSql = "
			DELETE FROM
				`blog_entries`
			WHERE
				`id` = :iId
			LIMIT
				1
		";
		$aSql = array('iId' => $this->_iId);
		DB::executePreparedQuery($sSql, $aSql);
	}

	public function getCommentsList()
	{
		$sSql = "
			SELECT 
				*,
				UNIX_TIMESTAMP(`created`) as `created`
			FROM
				`blog_comments`
			WHERE
				`entry_id` = :iEntryId
			ORDER BY
				`created` DESC
		";

		$aSql = array(
					'iEntryId'	=> $this->_iId
				);
		return DB::getPreparedQueryData($sSql, $aSql);
	}
}

?>