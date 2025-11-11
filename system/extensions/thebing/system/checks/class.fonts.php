<?php
/**
 * Schreibt die 'Schriftarten' von der Schultabelle in die Core tabelle und zentralisiert diese
 */

class Ext_Thebing_System_Checks_Fonts extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Fonts Structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Convert fonts into new structure';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$aError = array();
		$aInfo = array();
		
		$aSql = array();
		
		$bExistsOldTable = Ext_Thebing_Util::checkTableExists('kolumbus_fonts');
		
		if($bExistsOldTable){
			
			$sSql = "
				TRUNCATE
					`tc_fonts`
			";
			
			DB::executeQuery($sSql);
			
			Ext_Thebing_Util::backupTable('kolumbus_fonts');
			
			
			$sSql = "SELECT
							*
						FROM
							`kolumbus_fonts`
						WHERE
							`active` = 1
						ORDER BY
							`id` ASC
					";
			
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
			
			$aFontTypes = array(
				'',
				'b',
				'bi',
				'i'
			);
			
			foreach($aResult as $aData){
				
				$sSql = "INSERT INTO
								`tc_fonts` 
							SET
						";
				$aTempSql	= array();
				$aSql		= array();
				foreach($aData as $sKey => $sValue){					
					
					if($sKey == 'user_id'){
						$sKey = 'editor_id';
					}elseif($sKey == 'client_id'){
						continue;
					}
					
					$aTempSql[] = "`" . $sKey . "` = :" . $sKey ;
					$aSql[$sKey] = $sValue;
				}
				
				$sSql .= implode(', ', $aTempSql);
				DB::executePreparedQuery($sSql, $aSql); 
				
				
									
				// Schrift Datei vom Server in das neue Verzeichniss kopieren
				$oClient = Ext_Thebing_Client::getInstance((int)$aData['client_id']);
				
				if($oClient->id > 0){

					foreach($aFontTypes as $sFontType)
					{
						$sFont			= $aData['font'];
						
						$sFontFileBase	= substr($sFont,0,-4);
						$sExt			= substr($sFont,-4);
						
						$sFontName = $sFontFileBase . $sFontType . $sExt;
						
						
						$sPath = $oClient->getFilePath();
						
						$sPath .= 'fonts/' . $sFontName;
						
						$sPathNew = \Util::getDocumentRoot().'storage/tc/fonts/' . $sFontName;

						if(
							file_exists($sPath) &&
							!file_exists($sPathNew)
						){
							rename($sPath, $sPathNew);
						}
					}
					
				}
				
				
			}
			
			$sSql = "DROP TABLE `kolumbus_fonts`";
			DB::executeQuery($sSql);
			
		}
		
		
		

		return true;
	}
	
	
	public static function report($aError, $aInfo){
		
		$oMail = new WDMail();
		$oMail->subject = 'Customer Structure';
		
		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($aInfo, 1)."\n\n";
		
		if(!empty($aError)){
			$sText .= '------------ERROR------------';
			$sText .= "\n\n";
			$sText .= print_r($aError, 1);
		}
		
		$oMail->text = $sText;

		$oMail->send(array('m.durmaz@thebing.com'));
				
	}
	
	
	

}