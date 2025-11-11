<?php

// @TODO Entfernen ($_aAdditional in Ext_Thebing_User)
class Ext_Thebing_User_Data {

    static public function saveData($iUser, $sData, $mValue){
            $sSql = ' REPLACE INTO
                                            `kolumbus_user_data`
                                    SET
                                            `data` = :data,
                                            `value` = :value,
                                            `user_id` = :user_id
                                            ';
            $aSql = array('data'=>$sData, 'value' => (string)$mValue, 'user_id' => (int)$iUser);
            DB::executePreparedQuery($sSql, $aSql);
    }

    static public function getData($iUser, $sData){
    	$sSql = ' SELECT
                                            `value`
                                    FROM
                                            `kolumbus_user_data`
                                    WHERE
                                            `data` = :data AND
                                            `user_id` = :user_id
                                            ';
            $aSql = array('data'=>$sData, 'user_id' => (int)$iUser);
            $aResult = DB::getPreparedQueryData($sSql, $aSql);
            return $aResult[0]['value'] ?? null;
    }

    static public function deleteData($iUser){

		$sSql = '
					DELETE FROM
						`kolumbus_user_data`
					WHERE
						`user_id` = :user_id
					';
		
		$aSql = array('user_id' => (int)$iUser);
		DB::executePreparedQuery($sSql, $aSql);

	}

}