<?php

namespace Core\Helper\Composer;

class Credits {

	/**
	 * Methode liest Packages aus der composer.lock aus und stellt die als HTML dar.
	 * Caching nicht notwendig, da schnell genug und nicht oft aufgerufen.
	 * 
	 * @return string
	 */
	public function getCredits() {

		$oUpdate = new \Update;
		$sComposerBin = $oUpdate->getComposerBin();
				
		/*
		 * shell_exec wird anstelle von \Update::executeShellCommand benutzt, 
		 * damit Warnungen und Fehler nicht mitkommen
		 */
		
		$sCmd = sprintf('cd %s; %s show -f json', \Util::getDocumentRoot(), $sComposerBin);
		$sOutput = shell_exec($sCmd);

		$sCredits = '';
	
		if(!empty($sOutput)) {
			
			$aPackages = json_decode($sOutput, true);

			if(!empty($aPackages['installed'])) {
				foreach($aPackages['installed'] as $aPackage) {

					// Keine internen Pakete anzeigen
					if(strpos($aPackage['name'], 'fidelo-bundle') === 0) {
						continue;
					}
					
					$sCredits .= $aPackage['name'].' - '.$aPackage['description'];
									
					$iTestName = preg_match('@^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$@', $aPackage['name']);
					
					if($iTestName === 1) {

						/*
						 * Ich cache das hier einzeln, damit nach einem Composer-Update nicht der ganze Quatsch neu 
						 * generiert werden muss, womit es auch wieder langsam ist.
						 */
						$sCacheKey = __METHOD__.$aPackage['name'];
						
						$aAdditional = \WDCache::get($sCacheKey);
						
						if($aAdditional === null) {
						
							$sCmd = sprintf('cd %s; %s show '.$aPackage['name'].' -f json', \Util::getDocumentRoot(), $sComposerBin);
							$sOutput = shell_exec($sCmd);				

							$aPackage = json_decode($sOutput, true);

							$aAdditional = [];

							if(!empty($aPackage['homepage'])) {
								$aAdditional[] = '<a href="'.$aPackage['homepage'].'" target="_blank">'.$aPackage['homepage'].'</a>';
							}

							if(!empty($aPackage['licenses'])) {
								foreach($aPackage['licenses'] as $aLicense) {
									if(is_scalar($aLicense)) {
										$aLicense = ['url'=>$aLicense, 'name'=>$aLicense];
									}
									$aAdditional[] = '<a href="'.$aLicense['url'].'" target="_blank">'.$aLicense['name'].'</a>';
								}
							}

							// 4 Wochen cachen
							\WDCache::set($sCacheKey, 28*24*60*60, $aAdditional);

						}

						if(!empty($aAdditional)) {
							$sCredits .= ' ('.implode(', ', $aAdditional).')';
						}

					}
					
					$sCredits .= '<br>';
					
				}
			}
			
		}
		
		return $sCredits;
	}
	
}