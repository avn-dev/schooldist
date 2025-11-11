<?

class Ext_Thebing_Data_Railway{
	
	public static function getRailways($bForSelects = true){
		global $user_data;
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		$sSql = "SELECT 
					* 
				FROM 
					#table 
				WHERE 
					`idClient` = :client 
				AND 
					`idSchool` = :idSchool 
				AND 
					`active` = 1
				ORDER BY position";
		$aSql = array('table'=>'kolumbus_railways','client'=>$user_data['client'],'idSchool'=>$iSessionSchoolId);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		if($bForSelects == true){
			$aSelect = array();
			$aSelect[0] = "---";
			foreach($aResult as $aValue){
				$aSelect[$aValue['id']] = $aValue['name'];
			}
			return $aSelect;
		} else {
			return $aResult;
		}
	}
	
	
}