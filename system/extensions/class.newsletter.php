<?php

class Ext_Newsletter extends WDBasic
{
	// Tabellenname
	protected $_sTable = 'newsletter2_recipients';

	// Klassenname
	static protected $sClassName = 'Ext_Newsletter';

	// Instanz Cache
	private static $aInstance = null;

	protected $_aFormat = array(
		'email' => array(
			'required'	=> true,
			'validate'	=> 'MAIL'
		)
	);

	/* ==================================================================================================== */

	/**
	 * Gibt den Namen der Klasse zurück
	 * @return string
	 */
	public function getClassName()
	{
		return get_class($this);
	}

	/**
	 * Returns the instance of an object by data ID
	 *
	 * @param int $iDataID
	 * @return Ext_Newsletter
	 */
	static public function getInstance($iDataID = 0) {

		$sClass = self::$sClassName;

		if($iDataId == 0)
		{
			return new $sClass($iDataID);
		}

		if(!isset(self::$aInstance[$sClass][$iDataID]))
		{
			try
			{
				self::$aInstance[$sClass][$iDataID] = new $sClass($iDataID);
			}
			catch(Exception $e)
			{
				\Util::handleErrorMessage($e->getMessage());
			}
		}

		return self::$aInstance[$sClass][$iDataID];
	}

	/* ==================================================================================================== */

	/**
	 * Check newsletter directory
	 */
	public static function checkDirectory() {

		$sNewsletterPath = self::getNewsletterPath();

		if(!is_dir($sNewsletterPath)) {
			Util::checkDir($sNewsletterPath);
		}

	}


	/**
	 * Delete newsletter and recipients lists
	 * 
	 * @param int $iListID
	 */
	public static function deleteList($iListID)
	{
		$aSQL = array(
			'iListID' => $iListID
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			DELETE FROM
				`newsletter2_recipients`
			WHERE
				`idList` = :iListID
		";
		DB::executePreparedQuery($sSQL, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			DELETE FROM
				`newsletter2_lists`
			WHERE
				`id` = :iListID
			LIMIT
				1
		";
		DB::executePreparedQuery($sSQL, $aSQL);
	}


	/**
	 * Delete email
	 * 
	 * @param int $iMailID
	 */
	public static function deleteMail($iMailID)
	{
		$aSQL = array(
			'iMailID' => $iMailID
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			DELETE FROM
				`newsletter2_mails`
			WHERE
				`id` = :iMailID
			LIMIT
				1
		";
		DB::executePreparedQuery($sSQL, $aSQL);
	}


	/**
	 * Get database tables
	 * 
	 * @param bool $bWithEmpty
	 * @return array
	 */
	public static function getDatabaseTables($bWithEmpty = true)
	{
		$aTables = array();

		$sSQL = "SHOW TABLES";
		$aTemp = DB::getQueryCol($sSQL);

		foreach((array)$aTemp as $sTable)
		{
			$aTables[$sTable] = $sTable;
		}

		if($bWithEmpty)
		{
			$aTables = array('' => 'Nein') + $aTables;
		}

		return $aTables;
	}


	/**
	 * Get list data
	 * 
	 * @param int $iListID
	 * @return array
	 */
	public static function getListData($iListID)
	{
		$sSQL = "
			SELECT
				*
			FROM
				`newsletter2_lists`
			WHERE
				`id` = :iListID
			LIMIT
				1
		";
		$aSQL = array('iListID' => $iListID);
		$aList = DB::getQueryRow($sSQL, $aSQL);

		return $aList;
	}


	/**
	 * Get the list of all lists
	 * 
	 * @return array
	 */
	public static function getListsList($bForSelect = true)
	{
		if($bForSelect)
		{
			$sSelect = " `id`, `name` ";
		}
		else
		{
			$sSelect = " * ";
		}

		$sSQL = "
			SELECT
				" . $sSelect . "
			FROM
				`newsletter2_lists`
			WHERE
				`active` = 1
			ORDER BY
				`name`
		";

		if($bForSelect)
		{
			$aList = DB::getQueryPairs($sSQL);
		}
		else
		{
			$aList = DB::getQueryData($sSQL);
		}

		return $aList;
	}


	/**
	 * Get the list of all emails
	 * 
	 * @return array
	 */
	public static function getMailsList($bForSelect = true)
	{
		if($bForSelect)
		{
			$sSelect = " `id`, `name` ";
		}
		else
		{
			$sSelect = " * ";
		}

		$sSQL = "
			SELECT
				" . $sSelect . "
			FROM
				`newsletter2_mails`
			WHERE
				`active` = 1
			ORDER BY
				`name`
		";

		if($bForSelect)
		{
			$aList = DB::getQueryPairs($sSQL);
		}
		else
		{
			$aList = DB::getQueryData($sSQL);
		}

		return $aList;
	}


	/**
	 * Get newsletter path
	 * 
	 * @return string
	 */
	public static function getNewsletterPath() {

		$sNewsletterUrl = self::getNewsletterUrl();

		$sNewsletterPath = \Util::getDocumentRoot().$sNewsletterUrl;

		return $sNewsletterPath;
	}


	/**
	 * Get newsletter URL
	 * 
	 * @return string
	 */
	public static function getNewsletterUrl() {

		$sNewsletterUrl = '/storage/newsletter/';

		return $sNewsletterUrl;
	}


	/**
	 * Get recipients genders
	 * 
	 * @return array
	 */
	public static function getRecipientsGenders($bWithEmpty = false) {
		
		$aGenders = array(
			0 => '',
			1 => 'Herr',
			2 => 'Frau'
		);

		if(!$bWithEmpty) {
			unset($aGenders[0]);
		}

		return $aGenders;
	}


	/**
	 * Get recipients titles
	 * 
	 * @return array
	 */
	public static function getRecipientsTitles($bWithEmpty = false) {
		
		$aTitles = array(
			0 => '',
			1 => 'Dr.',
			2 => 'Prof.'
		);

		if(!$bWithEmpty) {
			unset($aTitles[0]);
		}

		return $aTitles;
	}

	/**
	 * See parent
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		$aSqlParts['where'] = '';
	}
	
	/**
	 *
	 * @return array 
	 */
	public function getTableFields() {
		return self::$_aTable[$this->_sTable];
	}

}