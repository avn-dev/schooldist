<?php

namespace Deepl\Api\Object;

use Deepl\Api\Response;
use Deepl\Exception\EmptyTextException;
use Deepl\Exception\UnsupportedLanguageException;

/**
 * @see https://www.deepl.com/de/docs-api.html
 */
class Translation extends \Deepl\Api\AbstractObject {
	/**
	 * <ignore></ignore>
	 */
	const IGNORE_TAG = 'ignore';
	
	private $sSourceLanguage = '';
	private $sTargetLanguage = '';
	private $sOriginText = '';
	private $sText = '';
	
	private $aPlaceholders = [];
		
	/**
	 * DeepL only supports following languages
	 * see: https://www.deepl.com/docs-api.html?part=translating_text
	 * 
	 * @var array 
	 */
	public static $aSupportedLanguages = [
		"EN", "DE", "FR", "ES", "PT", "IT", "NL", "PL", "RU"
	];
	
	public function __construct(string $sTargetLanguage, string $sText) {		
		$this->sTargetLanguage = strtoupper($sTargetLanguage);
		$this->sOriginText = $sText;
		$this->sText = $this->markTextPlaceholders($sText);
	}

	public function getUrl() {
		return '/translate';
	}
	
	/**
	 * Die "Quellsprache" ist optional, wenn nicht vorhanden versucht DeepL die Sprache zu erkennen
	 * 
	 * @param string $sSourceLanguage
	 * @return $this
	 */
	public function setSourceLanguage(string $sSourceLanguage) {
		$this->sSourceLanguage = strtoupper($sSourceLanguage);
		return $this;
	}
	
	/**
	 * Alle nötigen Request-Parameter setzen
	 * 
	 * @param \Deepl\Api\Request $oRequest
	 * @throws \Deepl\Exception\EmptyTextException
	 * @throws \Deepl\Exception\UnsupportedLanguageException
	 */
	public function prepareRequest(\Deepl\Api\Request $oRequest) {
		
		if(empty($this->sText)) {
			throw new EmptyTextException('No text given!');
		}
		
		if(!self::checkValidLanguage($this->sTargetLanguage)) {
			throw new UnsupportedLanguageException('Unsupported target language!');
		}
		
		if(!empty($this->sSourceLanguage)) {
			
			if(!self::checkValidLanguage($this->sSourceLanguage)) {
				throw new UnsupportedLanguageException('Unsupported source language!');
			}
			
			$oRequest->add('source_lang', $this->sSourceLanguage);
		}
		
		$oRequest->add('target_lang', $this->sTargetLanguage);
		$oRequest->add('text', $this->sText);
		$oRequest->add('preserve_formatting', 1);
		$oRequest->add('tag_handling', 'xml');
		$oRequest->add('ignore_tags', self::IGNORE_TAG);
	
	}

	/**
	 * Response-Objekt bearbeiten um die Platzhalter wieder zu ersetzen. Die fertige 
	 * Übersetzung steht in 'clean_translation' 
	 * 
	 * @param \Deepl\Api\Response $oResponse
	 * @return \Deepl\Api\Response
	 */
	public function prepareResponse(Response $oResponse) {
	
		$oResponse->set('origin_text', $this->sOriginText);
		$oResponse->set('text', $this->sText);

		$aTranslations = $oResponse->get('translations');
		
		if(!empty($aTranslations)) {
			$sText = $aTranslations[0]['text'];
			
			if(!empty($this->aPlaceholders)) {
				
				$aPlaceholders = array_reverse($this->aPlaceholders);
				
				$oResponse->set('placeholders', $this->aPlaceholders);
				$sText = str_replace(array_keys($aPlaceholders), array_values($aPlaceholders), $sText);
			}
						
			$oResponse->set('clean_translation', trim($sText));
		}		

		return $oResponse;
	}
	
	/**
	 * Filtert alle Platzhalter und sonstige zeichen die in der Übersetzung erhalten sein müssen
	 * raus und ersetzt diese mit einem <ignore>-Tag. Dieser Tag wird von DeepL ignoriert.
	 * Siehe: ?ignore_tags
	 * 
	 * Beispiel:
	 *		<ignore>{name}</ignore>
	 *		<ignore>%s</ignore>
	 * 
	 * @param string $sText
	 * @return string
	 */
	private function markTextPlaceholders(string $sText) {
		
		$aPlaceholders = [];
		$iCount = 0;
			
		$oIgnoring = function($aFound, &$aPlaceholders, &$iCount, &$sText) {
			foreach($aFound as $sPlaceholder) {
				$aPlaceholders['IGNORE'.$iCount] = $sPlaceholder;
				$sText = str_replace($sPlaceholder, 'IGNORE'.$iCount, $sText);
				++$iCount;
			}
		};
		
		// {name}, etc. (Leerzeichen und Anführungszeichen - sofern vorhanden - werden sich auch gemerkt)
		
		$aPlaceholderMatches = [];
		preg_match_all('~\s?[\"|\']?\{(.+?)}[\"|\']?\s?~', $sText, $aPlaceholderMatches);
		
		if(!empty($aPlaceholderMatches[0])) {
			$oIgnoring($aPlaceholderMatches[0], $aPlaceholders, $iCount, $sText);	
		}
		
		// %s, %i, etc. (Leerzeichen, Anführungszeichen und Doppelpunkt - sofern vorhanden - werden sich auch gemerkt)
		// "%s", '%s', %s:
		
		$aPercentPlaceholderMatches = [];
		preg_match_all('~\s?[\"|\']?\%(.+?)[\"|\']?\:?\s?~', $sText, $aPercentPlaceholderMatches);
		
		if(!empty($aPercentPlaceholderMatches[0])) {
			$oIgnoring($aPercentPlaceholderMatches[0], $aPlaceholders, $iCount, $sText);
		}
				
		// - und » (Leerzeichen am Anfang und am Ende)
		
		$aSpecialCharsMatches = [];
		preg_match_all('~\s(-|»)\s~', $sText, $aSpecialCharsMatches);
		
		if(!empty($aSpecialCharsMatches[0])) {
			$oIgnoring($aSpecialCharsMatches[0], $aPlaceholders, $iCount, $sText);
		}

		// : (Leerzeichen am Anfang und am Ende optional)
		
		$aDoubleDotMatches = [];
		preg_match_all('~\s?(:)\s?~', $sText, $aDoubleDotMatches);
		
		if(!empty($aDoubleDotMatches[0])) {
			$oIgnoring($aDoubleDotMatches[0], $aPlaceholders, $iCount, $sText);
		}

		foreach($aPlaceholders as $sIgnore => $sPlaceholder) {
			// Z.b. <ignore>{name}</ignore>
			$sReplace = '<'.self::IGNORE_TAG.'>'.$sPlaceholder.'</'.self::IGNORE_TAG.'>';
			// Platzhalter mit dem <ignore>-Tag versehen
			$sText = str_replace($sIgnore, $sReplace, $sText);
			// Platzhalter merken um diesen im Response-Objekt wieder zu ersetzen
			$this->aPlaceholders[$sReplace] = $sPlaceholder;
		}

		return $sText;
	}
	
	/**
	 * Prüfen ob die übergebene Sprache von DeepL unterstützt wird
	 * 
	 * @param string $sLanguage
	 * @return bool
	 */
	public static function checkValidLanguage($sLanguage) {
		return in_array(strtoupper($sLanguage), self::$aSupportedLanguages);
	}
	
}

