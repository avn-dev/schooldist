<?php
class Ext_Thebing_System_Checks_ImportAccommodationUploads extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		return true;
		
	}

	public function executeCheck(){
		global $user_data;

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$user_data['cms'] = 0;

		$aSql = array();
		$sSql = " SELECT `id` FROM `customer_db_4` WHERE `active` = 1";

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		Ext_Thebing_Util::truncateTable('kolumbus_accommodations_uploads');

		$sPath = '/media/secure/accommodation/';
		$ordner = \Util::getDocumentRoot().$sPath;

		$aPDFFiles = array();
		if (is_dir($ordner)) {

				$handle = opendir($ordner);
				$i = 1;
				while ($file = readdir ($handle)) {

					if($file != "." && $file != "..") {
						if(!is_dir($ordner.$file)) {

							$aTemp = explode('.', $file);
							$sEnd = end($aTemp);

							if(strtolower($sEnd) != 'pdf'){
								continue;
							}

							// bisheriger aufbau $sPhoto . 'pdf_' . $idProvider . '_en.pdf';
							$aTempData = explode('_', $aTemp[0]);

							if(count($aTempData) != 3){
								continue;
							}

							$aPDFFiles[$i]['type'] = 'pdf';
							$aPDFFiles[$i]['lang'] = end($aTempData);

							$aPDFFiles[$i]['path'] = $sPath.$file;
							$aPDFFiles[$i]['file'] = $file;
							$aPDFFiles[$i]['id'] = (int)$aTempData[1];
							$aPDFFiles[$i]['published'] = 1;
							$i++;
						}
					}

				}
			closedir($handle);
		}

		foreach((array)$aResult as $aData){
			
			$oAccommodation = new Ext_Thebing_Accommodation($aData['id']);
			$this->importFiles($oAccommodation, $aPDFFiles);

		}

		return true;
	}

	public function importFiles($oAccommodation, $aPDFFiles){



		$aFiles = $oAccommodation->__old__getPictures(true);
		foreach((array)$aFiles as $iKey => $aFile){
			$aTemp = explode('.', $aFile['file']);
			$sEnd = end($aTemp);

			$aFiles[$iKey]['published'] = 0;

			if(strtolower($sEnd) == 'pdf'){
				// bisheriger aufbau $sPhoto . 'pdf_' . $idProvider . '_en.pdf';
				$aFiles[$iKey]['type'] = 'pdf';
				$aTempData = explode('_', $aTemp[0]);
				$aFiles[$iKey]['lang'] = end($aTempData);
			} else {
				$aFiles[$iKey]['type'] = 'picture';
			}
		}

		$aFiles = $oAccommodation->__old__getPictures(false);
		foreach((array)$aFiles as $iKey => $aFile){
			$aFiles[$iKey]['published'] = 1;
			$aFiles[$iKey]['type'] = 'picture';
		}

		$this->writeFiles($oAccommodation, $aFiles);

		$aFiles = array();
		foreach((array)$aPDFFiles as $aData){

			if($aData['id'] == $oAccommodation->id){
				$aFiles[] = $aData;
			}
		}

		$this->writeFiles($oAccommodation, $aFiles);
	}

	public function writeFiles($oAccommodation, $aFiles){

		foreach((array)$aFiles as $aFile){

			$sPath = \Util::getDocumentRoot().$aFile['path'];
			$sFile = $aFile['file'];
			$aTemp = explode('.', $sFile);

			if(is_file($sPath)){

				$oUpload = new Ext_Thebing_Accommodation_Upload();
				$oUpload->accommodation_id	= (int)$oAccommodation->id;
				$oUpload->published			= (int)$aFile['published'];
				$oUpload->type				= (string) $aFile['type'];
				$oUpload->lang				= (string)$aFile['lang'];
				$oUpload->save();

				$sFileName = 'filename_'.$oUpload->id.'_'.$sFile;

				$oUpload->filename = $sFileName;
				$oUpload->save();

				$sNewPath = \Util::getDocumentRoot().'storage/accommodation/'.$sFileName;

				copy($sPath, $sNewPath);
				chmod($sNewPath, 0777);

			} else {
				echo 'Datei nicht gefunden:'.$sPath.' <br/>';
			}
		}
	}
}