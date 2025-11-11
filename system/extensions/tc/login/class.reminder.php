<?php

/**
 * Hier werden alle Daten gespeichert von Benutzern die Ihr PW vergessen haben und ein neues angefordert haben
 */
class Ext_TC_Login_Reminder extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_login_reminder';
	
	/**
	 * Prüft ob ein Hash einmalig ist in dieser Tabelle
	 * @param type $sHash
	 * @return boolean 
	 */
	public static function checkUniqueHash($sHash){
		
		$bCheck = true;
		
		$sSql = "SELECT
						*
					FROM
						`tc_login_reminder`
					WHERE
						`hash` = :hash
		";
		
		$aSql = array();
		$aSql['hash'] = $sHash;
		
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		
		if(!empty($aData)){
			$bCheck = false;
		}
		
		return $bCheck;
	}
	
	/**
	 * Prüft einen Password-vergessen Hash auf die Gültickkeit
	 * @param type $sHash
	 * @param type $iDbTable
	 * @param type $iHour
	 * @return boolean 
	 */
	public static function checkHash($sHash, $iDbTable, $iHour = 5){
		
		$mCheck = false;

		$sSql = "SELECT
						*
					FROM
						`tc_login_reminder`
					WHERE
						`hash` = :hashcode AND
						`active` = 1  AND
						`db_table` = :db_table AND
						NOW() < (`created`  + INTERVAL " . $iHour . " HOUR)
					ORDER BY
						`created` DESC
					LIMIT 1
					";
		
		$aSql = array();
		$aSql['db_table'] = $iDbTable;
		$aSql['hashcode'] = $sHash;
		
		$aData = DB::getQueryRow($sSql, $aSql);
		if(!empty($aData)){
			$mCheck = $aData;
		}
		
		return $mCheck;
	}
	
	/**
	 * Löscht alle Anfragen eines Hashes
	 * @param type $sHash 
	 */
	public static function deleteRequest($sHash){
		
		$sSql = "UPDATE
						`tc_login_reminder`
					SET
						`active` = 0
					WHERE
						`hash` = :hash
				";
		$aSql = array();
		$aSql['hash'] = $sHash;
		
		DB::executePreparedQuery($sSql, $aSql);
	}

}