<?php

namespace TcFrontend\Service;

class HtmlMinifyService {
	
	const JS_SCPRIPT_PLACEHOLDER = 'thebing_frontend_html_script';
	
	const CHUNK_SPLIT = 10000;
	
	/**
	 * Entfernt Tabs, Leerzeichen und Zeilenumbrüche aus dem HTML-Code.
	 * <script>-Tags bleiben unverändert
	 * 
	 * @param string $sHtml
	 * @return string
	 */
	public function minify($sHtml) {
		
		$aJsPlaceholders = [];
				
		// <script>-Tags entfernen damit diese nicht verändert werden
		$this->replaceJsLinesWithPlaceholders($sHtml, $aJsPlaceholders);
		// HTML (ohne js) verkleinern
		$sHtml = $this->minifyCleanHtml($sHtml);
		
		if(!empty($aJsPlaceholders)) {
			// JS wieder hinzufügen (Platzhalter wieder ersetzen)
			$sHtml = str_replace(array_keys($aJsPlaceholders), array_values($aJsPlaceholders), $sHtml);			
		}
		
		return $sHtml;
	}
	
	/**
	 * Entfernt alle in dem HTML vorkommenden Tabs, Leerzeichen und Zeilenumbrüche.
	 *  
	 * @param string $sCleanHtml
	 * @return string
	 */
	private function minifyCleanHtml($sCleanHtml) {
		
		$aChunks = str_split($sCleanHtml, self::CHUNK_SPLIT);
		
		if(!empty($aChunks)) {
			
			$sSanitizedContent = "";
			foreach($aChunks as $sChunk) {
				$sSanitizedContent.= preg_replace('/\s+/S', " ", $sChunk);
			}
			
			return $sSanitizedContent;			
		}
		
		return $sCleanHtml;
	}
	
	/**
	 * Ersetzt <script>-Tags durch Platzhalter und merkt sich den Inhalt für alle Platzhalter
	 * 
	 * @param string $rsHtml
	 * @param array $raPlaceholders
	 */
	private function replaceJsLinesWithPlaceholders(&$rsHtml, &$raPlaceholders) {
		
		$iCount = 1;
 		
		// <script>-Tags ohne src=""
 		$sScriptRegex = "/<script((?:(?!src=).)*?)>(.*?)<\/script>/smix";
 		
 		while(true) {				
 			$aPregMatches = [];
 			preg_match($sScriptRegex, $rsHtml, $aPregMatches);
			// Solange noch <script>-Tags existieren werden diese einzeln entfernt
 			if(!empty($aPregMatches)) {
 
 				$sPlaceholder = '{' . implode('_', [self::JS_SCPRIPT_PLACEHOLDER, $iCount]) . '}';
				// Erstes gefundene <script>-Tag entfernen
 				$rsHtml = preg_replace($sScriptRegex, $sPlaceholder, $rsHtml, 1);
 				// Inhalt für Platzhalter merken
 				$raPlaceholders[$sPlaceholder] = $aPregMatches[0];					
				
				++$iCount;
			} else {
				break;
			}
			
		}

	}
	
}
