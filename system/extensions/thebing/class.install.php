<?php

class Ext_Thebing_Install {
	
	public static function listFilesOfDir($sDir, $iLastTime = 0, $iOut = 1){
		$sOut = "";
		if ($handle = opendir($sDir)) {
	
		    /* Das ist der korrekte Weg, ein Verzeichnis zu durchlaufen. */
		    while (false !== ($file = readdir($handle))) {

		    	if($file == '..') {
		    		continue;
		    	}

				if(
					is_dir($sDir.'/'.$file)
				){
					if(
						$file != '.' && 
						$file != '..' && 
						strpos($file, '.') === false &&
						strpos($file, 'secure') === false &&
						strpos($file, 'backup') === false &&
						strpos($file, 'dbModels') === false &&
						strpos($file, 'picture_library') === false &&
						strpos($file, 'plesk') === false &&
						strpos($file, '__') === false &&
						$file != 'german' &&
						$file != 'english' &&
						$file != 'tools' &&
						$file != 'test' &&
						$file != 'nbproject' &&
						$file != 'install' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'zend' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'img' &&
						strpos($sDir.'/'.$file, 'admin/stats') === false  &&
						strpos($sDir.'/'.$file, 'media/temp') === false  &&
						strpos($sDir.'/'.$file, 'media/original') === false &&
						strpos($sDir.'/'.$file, 'media/auto') === false &&
						strpos($sDir.'/'.$file, 'system/extensions/zend') === false &&
						strpos($sDir.'/'.$file, 'admin/extensions/phpmyadmin') === false &&
						strpos($sDir.'/'.$file, 'admin/extensions/manual') === false &&
						strpos($sDir.'/'.$file, 'system/includes/classes') === false
					) {
						$sOut .= self::listFilesOfDir($sDir.'/'.$file, $iLastTime);
					}
				} else {
					if(
						strpos($file, '%%') === false &&
						strpos($file, '__') === false &&
						strpos($file, '.svn') === false &&
						strpos($file, 'nbproject') === false &&
						substr($file, -4) !== '.pdf' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'system/includes/config.inc.php' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'system/extensions/thebing/access/class.client.php' &&
						$sDir.'/'.$file != \Util::getDocumentRoot().'system/extensions/thebing/access/tmp/accesfile.php' &&
						$file != '.' && 
						$file != '..'
					) {
	
						if(filemtime($sDir.'/'.$file) > (int)$iLastTime){
							$sDir_ = str_replace(\Util::getDocumentRoot(), '', $sDir);
							$sOut .= "<file>".$sDir_.'/'.$file."</file>";
							$aFiles[] = $sDir_.'/'.$file;
						}
						
					}
				}
					   
		    }
	
	    	closedir($handle);
		}
		
		if($iOut == 1){
			return $sOut;
		}
		
		return $aFiles;
		
	}

}