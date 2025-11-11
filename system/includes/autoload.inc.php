<?php

// TODO Dieser ganze dynamische Autoloader müsste ersetzt werden mit PSR-4/PSR-0/Classmaps

// Defines the documents ROOT as 'BASE'
if(!defined("BASE")){
	define('BASE', realpath(__DIR__.'/../../').'/');
}

$sVendorAutoloadFile = BASE.'vendor/autoload.php';

if(is_file($sVendorAutoloadFile)) {
	require ($sVendorAutoloadFile);
}

spl_autoload_register('loadFrameworkClass');

/**
 * The autoloader
 */
function loadFrameworkClass($sClass) {

	// Extensions classes
	if(substr($sClass, 0, 4) == 'Ext_') {

		$sClass = str_replace('Ext_', 'extensions_', $sClass);
		$sClass = str_replace('_', '/', $sClass);
		$sClass = strtolower($sClass);

		$sFileName	= 'class.'.substr($sClass, strripos($sClass, '/')+1).'.php';
		$sTree		= 'system/'.substr($sClass, 0, strripos($sClass, '/')+1);

		$sFile = BASE.$sTree.$sFileName;
		
		// Includes class file
		if(is_file($sFile)) {
			include($sFile);
		}

	} elseif(substr($sClass, 0, 4) == 'GUI_') {

		$sClass = str_replace('_', '.', $sClass);
		$sClass = strtolower($sClass);
		$sFileName	= $sClass.'.php';

		include(BASE."system/includes/gui/".$sFileName);

	} elseif(substr($sClass, 0, 5) == 'wdPDF') {

		//$sClass = str_replace('_', '.', $sClass);
		$sClass = strtolower($sClass);
		$sFileName	= $sClass.'.php';

		include(BASE."system/includes/wdpdf/".$sFileName);
		
	} elseif(substr($sClass, 0, 8) == 'Updates_') {

		$sTmpClass = str_replace('_', '/', $sClass);
		$sTmpClass = strtolower($sTmpClass);

		$sTree		= 'system/'.substr($sTmpClass, 0, strripos($sTmpClass, '/')+1);
		$sFileName	= 'class.'.substr($sTmpClass, strripos($sTmpClass, '/')+1).'.php';
		
		$sFile = BASE.$sTree.$sFileName;

		if(is_file($sFile)) {
			include($sFile);
		}
		
	} elseif(strpos($sClass, '\\') !== false) {
		
		$sClassRelativeUrl = str_replace('\\', '/', $sClass);
		
		$sFile = BASE.'system/bundles/'.$sClassRelativeUrl.'.php';

		if(is_file($sFile)){
			include($sFile);
		}
		
	} else {

		$sTmpClass = str_replace('_', '/', $sClass);
		$sTmpClass = strtolower($sTmpClass);

		if(strpos($sTmpClass, '/') !== false) {
			$sFileName	= 'class.'.substr($sTmpClass, strripos($sTmpClass, '/')+1).'.php';
			$sTree		= substr($sTmpClass, 0, strripos($sTmpClass, '/')+1);
		} else {
			$sFileName	= 'class.'.$sTmpClass.'.php';
			$sTree		= '';
		}

		$sFile = BASE."system/includes/".$sTree.$sFileName;

		if(is_file($sFile)) {
			include($sFile);
		} else {
			// Wird laut Mark nicht mehr benötigt
//			\System::wd()->executeHook('autoload', $sClass);
		}
		
	}	

}
