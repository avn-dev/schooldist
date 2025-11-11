<?php

class Ext_Blog_BlogComment
{
	private $_aData = array();
	private $_iId;

	public function __construct($iEntryId, $iId = 0)
	{
		if($iId < 0)
		{
			return;
		}

		// !!! Only in association with a Entry-ID !!!
		if(!isset($iEntryId) || (int)$iEntryId <= 0) {
			return false;
		}
		$this->_aData['entry_id'] = $iEntryId;

		if(!isset($iId) || $iId == 0) {
			$this->_addComment();
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
				`blog_comments`
			WHERE
					`id` = :iId
				AND
					`entry_id` = :iEntryId
			LIMIT 1
		";

		$aSql = array(
					'iId'		=> $this->_iId,
					'iEntryId'	=> $this->_aData['entry_id']
				);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aData = $aResult[0];
	}

	private function _addComment()
	{
		$sSql = "
			INSERT INTO
				`blog_comments`
			SET
				`created` = NOW(),
				`entry_id` = :iEntryId
		";

		$aSql = array('iEntryId' => $this->_aData['entry_id']);
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
   				`blog_comments`
			SET
				`active`	= :iActive,
				`entry_id`	= :iEntryId,
				`name`		= :sName,
				`email`		= :sEmail,
				`comment`	= :sComment
			WHERE
				`id` = :iId
		";

		$aSql = array(
					'iId'		=> $this->_iId,
					'iActive'	=> $this->_aData['active'],
					'iEntryId'	=> $this->_aData['entry_id'],
					'sName'		=> $this->_aData['name'],
					'sEmail'	=> $this->_aData['email'],
					'sComment'	=> $this->_aData['comment']
				);
		DB::executePreparedQuery($sSql, $aSql);
	}

	public function deleteComment()
	{
		$sSql = "
			DELETE FROM
				`blog_comments`
			WHERE
				`id` = :iId
			LIMIT
				1
		";

		$aSql = array('iId' => $this->_iId);
		DB::executePreparedQuery($sSql, $aSql);
	}
}

?>