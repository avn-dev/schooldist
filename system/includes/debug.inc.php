<?php

/**
 * __out Wrapper für Fidelo Members ;) ( Only for Fidelo IP or local DEV )
 */
function __pout($mVar = 'EMPTY', $bStopScript = false, $aDebug = false){

	if(!Util::isDebugIP()) {
		return;
	}

	if(!$aDebug){
		$aDebug	= debug_backtrace();
	}

	__out($mVar, $bStopScript, $aDebug);

}

function __uout($mVar = 'EMPTY', $sUser=null, $bStopScript = false) {

	$oAccess = Access::getInstance();

	if(
		$oAccess instanceof Access &&
		$sUser == $oAccess->getAccessUser()
	) {
		$aDebug	= debug_backtrace();
		__out($mVar, $bStopScript, $aDebug);
	}
	
}

/**
 * Debug function for developers. See http://einstein.plan-i.de/index.php/Debug-Hilfsfunktion
 */
function __out($mVar = 'EMPTY', $bStopScript = false, $aDebug = false) {
	
	// Da print_r() regelmäßig bei Exceptions abschmiert, memory_limit erhöhen, wenn unter 2GB
	if(
		$mVar instanceof Exception &&
		Util::convertPHPShorthandNotationToBytes(ini_get('memory_limit')) <= pow(1024, 3) * 2
	) {
		ini_set('memory_limit', '2G');
	}

	$iMaxStringLength = 10 * 1024 * 1024; // 10 MiB

	// Get the file name and line of output	
	if($aDebug == false){
		$aDebug	= debug_backtrace();
	}
	$dLine	= '<span style="color:#FF6600;">'.$aDebug[0]['line'].'</span>';
	$sFile	= '<span style="color:#FF6600;">/'.str_replace('/var/www/vhosts/', '', $aDebug[0]['file']).'</span>';

	$aLines = explode("\n", file_get_contents($aDebug[0]['file']));
	$sLineContent = trim($aLines[$aDebug[0]['line'] - 1]);
	$sLineContent = '<span style="color:#AAA;">'.$sLineContent.'</span>';

	// Format the output
	if(is_string($mVar)) {
		
		if($mVar === 'EMPTY') {
			$mVar = "\t<b style=\"color:#CC0000;\">EMPTY</b>\n";
		} else {
			$mVar = "\tstring(".strlen($mVar).") => ".($mVar == '' ? $mVar = '\'\'' : $mVar)."\n";
		}
	} else if(is_float($mVar)) {
		$mVar = "\tfloat => ".$mVar."\n";
	} else if(is_double($mVar)) {
		$mVar = "\tdouble => ".$mVar."\n";
	}
	else if(is_int($mVar))
	{
		$mVar = "\tint => ".$mVar."\n";
	}
	else if(is_null($mVar))
	{
		$mVar = "\t<b style=\"color:#CC0000;\">NULL</b>\n";
	}
	else if($mVar === true)
	{
		$mVar = "\t<b style=\"color:#CC0000;\">TRUE</b>\n";
	}
	else if($mVar === false)
	{
		$mVar = "\t<b style=\"color:#CC0000;\">FALSE</b>\n";
	}

	// Print the output
	echo '<span style="position:relative; z-index:1000000;">';
	echo "\n<pre>----------------------------------------------------------------------------------------------------";
	echo "\n<b>Start of output on line {$dLine} in {$sFile}</b>: {$sLineContent}\n\n";

	// Sobald print_r() irgendein MySQLi-Objekt bekommt mit aktivem xdebug, gibt es ein Dutzend Warnings
	// https://stackoverflow.com/questions/25377030/mysqli-xdebug-breakpoint-after-closing-statement-result-in-many-warnings
	// https://bugs.xdebug.org/view.php?id=900
	if(function_exists('xdebug_disable')) {
		$iErrorLevel = error_reporting();
		$iDisplayErrors = ini_get('display_errors');
		ini_set('display_errors',0);
		error_reporting(0);
		xdebug_disable();
	}

	$sPrint = print_r($mVar, true);

	if(function_exists('xdebug_enable')) {
		xdebug_enable();
		error_reporting($iErrorLevel);
		ini_set('display_errors',$iDisplayErrors);
	}

	if(strlen($sPrint) > $iMaxStringLength) {
		// Wenn String größer als Maximum: abschneiden
		$sPrint = substr($sPrint, 0, $iMaxStringLength);
		$sPrint .= "\n<strong>Output cut off!</strong>";
	}
	echo $sPrint;
	echo "\n<b>End of output</b>\n";
	echo "----------------------------------------------------------------------------------------------------\n";

	// Stop the execution of script if needed
	if((bool)$bStopScript) {
		echo "<b style=\"color:#CC0000;\">";
		echo "<blink>The execution of script was explicitly stopped after this output.</blink></b>\n";
		echo "----------------------------------------------------------------------------------------------------</pre>";
		echo "</span>";
		die();
	} else {
		echo "</pre></span>";
	}
	
}