<?php

namespace Core\Helper;

class Agent {

	public static function getDeviceName($sUserAgent = null): ?string
	{
		if (empty($info = self::getInfo($sUserAgent))) {
			return null;
		}

		return sprintf('%s - %s (%s)', $info['os'], $info['agent'], $info['version']);
	}

	static public function getInfo($sUserAgent=null) {

		if(!class_exists('\Jenssegers\Agent\Agent')) {
			return [];
		}

		$oAgent = new \Jenssegers\Agent\Agent;

		if(!empty($sUserAgent)) {
			$oAgent->setUserAgent($sUserAgent);
		}
		
		$aBrowser = [];
		$aBrowser['agent'] = $oAgent->browser();
		$aBrowser['version'] = $oAgent->version($aBrowser['agent']);
		$aBrowser['os'] = $oAgent->platform();

		return $aBrowser;
	}
	
	static public function getFingerprint() {
		
		$oAgent = new \Jenssegers\Agent\Agent;

		$aBrowser = [];
		$aBrowser['agent'] = $oAgent->browser();
		$aBrowser['os'] = $oAgent->platform();
		$aBrowser['languages'] = join('-', $oAgent->languages());
		
		return join('_', $aBrowser);
	}
	
	static public function getHTTPUserAgent() {
		return $_SERVER['HTTP_USER_AGENT'];
	}
	
	static public function getBrowserLanguage($allowed_languages, $default_language, $lang_variable = null, $strict_mode = false) {

        // $_SERVER['HTTP_ACCEPT_LANGUAGE'] verwenden, wenn keine Sprachvariable mitgegeben wurde
        if (
			$lang_variable === null &&
			!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])
		) {
			$lang_variable = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        // wurde irgendwelche Information mitgeschickt?
        if(empty($lang_variable)) {
            // Nein? => Standardsprache zurückgeben
			return $default_language;
        }

        // Den Header auftrennen
        $accepted_languages = preg_split('/,\s*/', $lang_variable);

        // Die Standardwerte einstellen
        $current_lang = null;
        $current_q = 0;

        // Nun alle mitgegebenen Sprachen abarbeiten
        foreach ($accepted_languages as $accepted_language) {

			// get all language info
			$matches = array();
			$res = preg_match ('/^([a-z]{1,8}(?:-[a-z]{1,8})*)'.
							   '(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i', $accepted_language, $matches);

			// war die Syntax gÃ¼ltig?
			if (!$res) {
				// Nein? Dann ignorieren
				continue;
			}

			// Sprachcode holen und dann sofort in die Einzelteile trennen
			$lang_code = explode ('-', $matches[1]);

			// Wurde eine Qualität mitgegeben?
			if (isset($matches[2])) {
				// die Qualität benutzen
				$lang_quality = (float)$matches[2];
			} else {
				// Kompabilitätsmodus: Qualität 1 annehmen
				$lang_quality = 1.0;
			}

			// Bis der Sprachcode leer ist...
			while(count($lang_code)) {
				
				// mal sehen, ob der Sprachcode angeboten wird
				$sCompare = join ('_', $lang_code);
				
				foreach($allowed_languages as $sAllowedLanguage) {
					
					if($sAllowedLanguage === $sCompare) {
						// Qualität anschauen
						if ($lang_quality > $current_q) {
							// diese Sprache verwenden
							$current_lang = $sAllowedLanguage;
							$current_q = $lang_quality;
							// Erstbesten Treffer nehmen, weil Sprachen immer nach Qualität sortiert übergeben werden.
							break 3;
						}
					}
					
				}
				
				// Wenn wir im strengen Modus sind, die Sprache nicht versuchen zu minimalisieren
				if ($strict_mode) {
					// innere While-Schleife aufbrechen
					break;
				}
				
				// den rechtesten Teil des Sprachcodes abschneiden
				array_pop ($lang_code);
			}
			
        }
		
		if(empty($current_lang)) {
			$current_lang = $default_language;
		}
		
        // die gefundene Sprache zurückgeben
        return $current_lang;
	}
	
}