<?php

include("../../includes/main.inc.php");

Access_Backend::checkAccess("modules_admin");

$aTransfer = array();

if(isset($_VARS['gallery_id'])) {
	
	$aGallery = DB::getRowData('gallery_init', $_VARS['gallery_id']);
	
	$sPath = Util::getDocumentRoot().'storage/public/'.$aGallery['path'].'/';
	
	Util::checkDir($sPath);
	
	if(
		is_dir($sPath) && 
		is_writable($sPath)
	) {

		foreach($_FILES['upload']['tmp_name'] as $iKey=>$sFile) {

			$sName = \Util::getCleanFileName($_FILES['upload']['name'][$iKey]);
			
			$bMove = move_uploaded_file($sFile, $sPath.$sName);
			
			if($bMove === true) {
				Util::changeFileMode($sPath.$sName);

				$iFileSize = filesize($sPath.$sName);
				$aFileInfo = pathinfo($sName);

				$aData = array(
					'title' => $sName,
					'file' => $sName,
					'folder' => $aGallery['path'].'/',
					'active' => 1,
					'date' => time(),
					'extension' => $aFileInfo['extension'],
					'size' => $iFileSize,
					'author' => $user_data['id']
				);

				$iMediaId = DB::insertData('cms_media', $aData, false);

				$aGalleryData = array(
					'description' => $_FILES['upload']['name'][$iKey],
					'gallery_id' => (int)$_VARS['gallery_id'],
					'created' => time(),
					'image' => $iMediaId,
					'active' => 1
				);

				DB::insertData('gallery_data', $aGalleryData, false);

				$aTransfer[] = $sName;
			}
			
		}
	
	}
	
}

echo json_encode($aTransfer);