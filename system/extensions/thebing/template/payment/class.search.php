<?
class Ext_Thebing_Template_Payment_Search {

	public static function getTemplate($iInquiry = 0, $isNetto = 0, $bAll = false){
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		$sSql = " SELECT 
						id
					FROM 
						`kolumbus_template_payment_receipt`
					WHERE 
						`school_id` = :school_id AND
						`isNetto` = :isNetto
					LIMIT 1";
		$aSql = array('school_id'=>$iSessionSchoolId,'isNetto'=>$isNetto);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		if($bAll){
			return $aResult;
		}
		return new Ext_Thebing_Template_Payment_Receipt((int)$aResult[0]['id'],$iInquiry);

		
	}
	
}
