<?php

/**
 * @author Mark F. <mf@thebing.com>
 * @since 12.06.2012 
 */
class Ext_TC_Export {
	
	/**
	 * Liefert Trennzeichen für den Export (CSV)
	 * @return type 
	 */
	public static function getSeparatorOptions(){

		$aSeparator = array();
		$aSeparator['semicolon'] = L10N::t('Semikolon');
		$aSeparator['comma'] = L10N::t('Komma');
		$aSeparator['tab'] = L10N::t('Tabulator');
		$aSeparator['blank'] = L10N::t('Leerzeichen');
		
		return $aSeparator;
	}
	
	/**
	 * Liefert den Separator anhand des Keys
	 * @param type $sKey
	 * @return string 
	 */
	public static function getSeparator($sKey = ''){
		$aSeparator = array();
		$aSeparator['semicolon'] = ';';
		$aSeparator['comma'] = ',';
		$aSeparator['tab'] = '\t';
		$aSeparator['blank'] = ' ';
		
		// Wenn nix übergeben wurde oder ein fascher Key dann den Standard nehmen
		$sSeparator = ';';
		
		if(isset($aSeparator[$sKey])){
			$sSeparator = $aSeparator[$sKey];
		}
		
		return $sSeparator;
	}
	
	/**
	 * Liefert die Zeichensätze die möglich sind für den Export
	 * @return string 
	 */
	public static function getCharsetOptions(){
		
		$aCharset = array(
			'UTF-8'			=> 'UTF-8',
			'UTF-16'		=> 'UTF-16',
			'UTF-16BE'		=> 'UTF-16BE',
			'UTF-16LE'		=> 'UTF-16LE',
			'UTF-32'		=> 'UTF-32',
			'UTF-32BE'		=> 'UTF-32BE',
			'UTF-32LE'		=> 'UTF-32LE',
			'ISO8859-1'		=> 'ISO-8859-1',
			'ISO8859-1'		=> 'ISO-8859-2',
			'ISO8859-1'		=> 'ISO-8859-3',
			'ISO8859-1'		=> 'ISO-8859-4',
			'ISO8859-1'		=> 'ISO-8859-5',
			'ISO8859-1'		=> 'ISO-8859-6',
			'ISO8859-1'		=> 'ISO-8859-7',
			'ISO8859-1'		=> 'ISO-8859-8',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO8859-1'		=> 'ISO-8859-9',
			'ISO2022CN'		=> 'ISO-2022-CN',
			'ISO2022JP'		=> 'ISO-2022-JP',
			'ISO2022JP2'	=> 'ISO-2022-JP2',
			'ISO2022KR'		=> 'ISO-2022-KR',
			'US-ASCII'		=> 'US-ASCII',
			'CP1250'		=> 'Windows-1250',
			'CP1251'		=> 'Windows-1251',
			'CP1252'		=> 'Windows-1252',
			'CP1253'		=> 'Windows-1253',
			'CP1254'		=> 'Windows-1254',
			'CP1255'		=> 'Windows-1255',
			'CP1256'		=> 'Windows-1256',
			'CP1257'		=> 'Windows-1257',
			'CP1258'		=> 'Windows-1258',
		);
		
		return $aCharset;
		
	}

	
}
