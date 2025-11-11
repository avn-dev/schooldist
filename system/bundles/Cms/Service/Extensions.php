<?php

namespace Cms\Service;

class Extensions {
	
	static function getList() {
		
		$sCacheKey = __METHOD__;
		
		$aExtensions = \WDCache::get($sCacheKey);
		
		if($aExtensions === null) {

			$aElements = [];
			
			// Alte Struktur
			$aSystemElements = \Core\Entity\System\Elements::getRepository()->findBy(['element'=>'modul']);
			foreach($aSystemElements as $oSystemElement) {
				
				$aElements[\Util::convertHyphenLowerCaseToPascalCase($oSystemElement->file)] = $oSystemElement;
				
				$sFile = \Util::getDocumentRoot()."system/extensions/".$oSystemElement->file.".mod";
				if(is_file($sFile)) {
					$aExtensions[$oSystemElement->file] = [
						'title' => $oSystemElement->category.' » '.$oSystemElement->title,
						'file' => $sFile
					];
				}
			}

			$aSystemElements = \Core\Entity\System\Elements::getRepository()->findBy(['element'=>'bundle']);
			foreach($aSystemElements as $oSystemElement) {
				$aElements[\Util::convertHyphenLowerCaseToPascalCase($oSystemElement->file)] = $oSystemElement;
			}
			
			unset($oSystemElement);
			
			// Config-Dateien
			$oFileCollector = new \Core\Helper\Config\FileCollector();

			$aFiles = $oFileCollector->collectAllFileParts();

			foreach($aFiles as $oFile) {

				$oSystemElement = $aElements[$oFile->getBundle()];
				
				$aCommands = $oFile->get('cms_extensions');

				if(!empty($aCommands)) {
					foreach($aCommands as $sKey=>$aCommand) {
						$aTitle = [
							$oSystemElement->category,
							$oSystemElement->title
						];
						$aTitle = array_merge($aTitle, explode(' » ', $aCommand['title']));
						$aExtensions[$sKey] = [
							'title' => implode(' » ', array_unique($aTitle)),
							'class' => $aCommand['class']
						];
					}
				}

			}

			uasort($aExtensions, function($a, $b) {
				return strcmp($a['title'], $b['title']);
			});
			
			\WDCache::set($sCacheKey, (60*60*24), $aExtensions);
			
		}

		return $aExtensions;
	}
	
}
