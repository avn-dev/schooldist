<?php

class WDDebug {

	private static $_fTotal;


	public static function formatFilesize($size) {
		$units = array(' B', ' KB', ' MB', ' GB', ' TB');
		for($i = 0; $size > 1024; $i++) {
			$size /= 1024;
		}
		return number_format($size, 2).$units[$i];
	}

	public static function formatOverviewRow($sPrefix, $sName, $fSize) {

		$sRow = '';

		if($fSize > 1000) {
			$sRow = str_pad($sPrefix, 10, ' ', STR_PAD_RIGHT)." - ".str_pad($sName, 80, ' ', STR_PAD_RIGHT).": ".str_pad(self::formatFilesize($fSize), 12, ' ', STR_PAD_LEFT)."\n";
			self::$_fTotal += $fSize;

		}

		return $sRow;

	}

	public static function printOverview(array $aElements, $sType='variable') {
		
		foreach($aElements as $sName=>$mElement) {
			
			if($sType == 'class') {
				$oReflectionClass = new ReflectionClass($mElement);
				$aProperties = $oReflectionClass->getProperties();
				foreach($aProperties as $oProperty) {
					
					$oProperty->setAccessible(true);
					
					if($oProperty->isStatic()) {
						$mPropertyValue = $oProperty->getValue();
						$sPropertyName = $mElement."::\$".$oProperty->getName();
					} else {
						$mPropertyValue = '';
						$sPropertyName = $mElement."->".$oProperty->getName();
					}

					echo self::formatOverviewRow('CLASS', $sPropertyName, strlen(serialize($mPropertyValue)));

				}
			} else {
				echo self::formatOverviewRow('VAR', $sName, strlen(serialize($mElement)));
			}
			
		}
		
	}
	
	public static function getCurrentVariableMemory() {

		echo "\n<pre>".str_repeat('-', 107)."\n";

		$aVariables = get_defined_vars();

		self::printOverview($aVariables);

		$aClasses = get_declared_classes();

		self::printOverview($aClasses, 'class');

		echo self::formatOverviewRow('TOTAL', '', self::$_fTotal);

		echo str_repeat('-', 107)."</pre>\n";

	}

}