<?php

class Ext_TC_Placeholder_Util {

	/**
	 * Unit-Test: ./vendor/bin/pest --filter PlaceholdersInTemplateTest
	 *
	 * TODO $skipPlaceholder sollte mMn entfernt und anders gelöst werden. Das ist eine Util-Methode welche mir ALLE Platzhalter im Template liefern soll
	 *
	 * @param $sString
	 * @param closure|null $skipPlaceholder
	 * @return array
	 */
	final public function getPlaceholdersInTemplate(&$sString, closure $skipPlaceholder=null) {

		// TODO der Default ist hier mMn. fehl am Platz.
		if($skipPlaceholder === null) {
			$skipPlaceholder = function($prefix, $placeholder) {
				return !empty($prefix);
			};
		}

		/**
		 * Findet alle Platzhalter im Template, die keine direkten Smarty-Tags darstellen
		 * Teilt den Platzhalter direkt in die verschiedenen Abschnitte auf
		 * - IF
		 * - Direkter Loop-Zugriff
		 * - Platzhaltername
		 * - Modifier
		 */
		$aMatches = array();
		// der alte Regex ermöglicht keine Loop-Indizes mehr
		//$iMatches = preg_match_all('@\{(((if|foreach)\s+)?(?!(foreach|else|if|assign))(()#([0-9]+)\.)?([a-z0-9_\.]+)(([^\|#}][^}#]*)?(\|[^}]*)?))\}@ims', $sString, $aMatches); $matchIndex = 8;
		$iMatches = preg_match_all('@\{(((if|foreach)\s+)?(?!(foreach|else|if|assign))(([a-z0-9_]+)(?:#(\d+))?((?:\.[a-z0-9_]+)*))(([^\|#}][^}#]*)?(\|[^}]*)?))\}@ims', $sString, $aMatches); $matchIndex = 5;

		$aPlaceholders = array();
		if($iMatches > 0) {
			foreach($aMatches[$matchIndex] as $iMatch=>$sMatch) {

				// HTML Löschen
				// bei z.b Fettmarkieren kann es sein das nur teile markiert wurden.
				// daher muss html gelöscht werden
				$sMatch = strip_tags($sMatch);
				// falls mit leerzeichen begonnen wurde lösche diese
				$sMatch = ltrim($sMatch);

				$lastDotPos = strrpos($sMatch, '.');

				if ($lastDotPos !== false) {
					$prefix = substr($sMatch, 0, $lastDotPos);
					$placeholder = substr($sMatch, $lastDotPos + 1);
				} else {
					$prefix = '';
					$placeholder = $sMatch;
				}

				// Verschachtelte Platzhalter überspringen und erst in der jeweiligen Ebene abarbeiten
				if($skipPlaceholder !== null && $skipPlaceholder($prefix, $placeholder)) {
					continue;
				}

				// HTML-Entities in Platzhaltern zurückwandeln
				$sSuffix = html_entity_decode($aMatches[9][$iMatch], ENT_QUOTES, 'UTF-8');
				$sFullPlaceholder = html_entity_decode($aMatches[1][$iMatch], ENT_QUOTES, 'UTF-8');
				$sString = str_replace($aMatches[1][$iMatch], $sFullPlaceholder, $sString);

				$aItem = array(
					'placeholder'=>$placeholder,
					'prefix'=>$prefix,
					'if'=>$aMatches[2][$iMatch],
					'suffix'=>$sSuffix,
					'complete'=>$sFullPlaceholder,
					'other'=>$aMatches[10][$iMatch], // TODO wofür wird das benötigt?
					'modifier'=>$aMatches[11][$iMatch],
					'direct_loop_index'=>$aMatches[7][$iMatch]
				);

				$aPlaceholders[$placeholder][$aMatches[1][$iMatch]] = $aItem;
			}

		}

		return $aPlaceholders;		
	}
	
}

