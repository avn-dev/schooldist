<?php

class community_ecards {

    public static function getEcards($arrOptions=array()) {

		$strWhere = "";
		
		if($arrOptions['active']) {
			$strWhere = " active = 1 AND ";
		}

		$strSql = "
					SELECT 
						*
					FROM
						community_ecards
					WHERE
						".$strWhere."
						1
					ORDER BY
						name
					";
	
		$arrEcards = DB::getQueryData($strSql);

		return $arrEcards;

    }

    public static function getEcard($intEcardId, $arrOptions=array()) {

		$strWhere = "";
		
		if($arrOptions['active']) {
			$strWhere = " active = 1 AND ";
		}

		$strStr = "SELECT * FROM community_ecards WHERE ".$strWhere." id = :intId LIMIT 1";
		$arrSql = array('intId'=>(int)$intEcardId);
		$arrEcard = DB::getPreparedQueryData($strStr, $arrSql);
		$arrEcard = $arrEcard[0];

		return $arrEcard;

    }

	public static function getCategories() {
		$strSql = "SELECT * FROM community_ecards_categories WHERE active = 1 ORDER BY name";
		$arrCategoryData = DB::getQueryData($strSql);
		$arrCategories = array();
		foreach((array)$arrCategoryData as $arrData) {
			$arrCategories[$arrData['id']] = $arrData['name'];	
		}
		return $arrCategories;
	}

	public static function checkSpam($strEmail) {
		$strStr = "SELECT * FROM community_ecards_sendings WHERE recipient_email = :recipient_email AND UNIX_TIMESTAMP(created) > ".(int)(time() - (60*60*24*7))." LIMIT 1";
		$arrSql = array('recipient_email'=>$strEmail);
		$arrEcard = DB::getPreparedQueryData($strStr, $arrSql);
		if(count($arrEcard) > 0) {
			return true;
		} else {
			return false;
		}
	}

	public static function getEcardSending($strKey) {
		
		$strStr = "
					SELECT 
						s.*, 
						e.* 
					FROM 
						community_ecards_sendings s, 
						community_ecards e  
					WHERE  
						s.ecard_id = e.id AND  
						`key` = :key AND  
						UNIX_TIMESTAMP(s.created) > ".(int)(time() - (60*60*24*7))."  
					LIMIT 1";
		$arrSql = array('key'=>$strKey);
		$arrEcard = DB::getPreparedQueryData($strStr, $arrSql);
		
		$arrEcard = $arrEcard[0];
		
		return $arrEcard;
		
	}

}
