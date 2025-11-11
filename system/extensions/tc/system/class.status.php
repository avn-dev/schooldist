<?php
class Ext_TC_System_Status {
    
    static public function getVersion(){
        return System::d('version');
    }
    
    static public function getDebugmode(){
        $sSql   = "SELECT `debugmode` FROM `system_access` ORDER BY `debugmode` DESC LIMIT 1";
        $aRow   = DB::getQueryOne($sSql);
        $iDebug = (int)$aRow['debugmode'];
        return $iDebug;
    }
    
    static public function getStatus($sUrl){
        $sUrl .= '/wdmvc/tc/api/intern/serverstatus';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $sUrl);
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($curl, CURLOPT_POST ,0);
        curl_setopt($curl,CURLOPT_TIMEOUT,1);
        curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);

        $sServerStatus = curl_exec($curl); 
        $sStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl); 
        
        if($sStatus == 200){
            return $sServerStatus;
        } else {
            return null;
        }
    }
    
    static public function setDebugmode($sUrl, $iStatus){
        $sUrl .= '/wdmvc/tc/api/intern/debugmode';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $sUrl);
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($curl, CURLOPT_POST ,1);
        curl_setopt($curl,CURLOPT_TIMEOUT,1); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, '&debugmode='.$iStatus);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
		
        $sContent = curl_exec($curl); 
        $sStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl); 
        
        if($sStatus == 200){
            return true;
        } else {
            return false;
        }
    }
    
    static public function saveDebugmode($iStatus){
        $sSql   = "UPDATE `system_access` SET `debugmode` = :mode ";
        $bSuccess = (bool)DB::executePreparedQuery($sSql, array('mode' => (int)$iStatus));
        return $bSuccess;
    }
    
}