<?php

use Dubture\Monolog\Reader\LogReader;

class Ext_TC_Log_Parser {
	private static $_aCurrentLine = array();

	public static function parseLog($sFile) {
		$aLines = array();
		$oFile = new SplFileObject($sFile);

		while(!$oFile->eof()) {
			$sLine = $oFile->fgets();

			if(empty($sLine)) {
				continue;
			}

			preg_match('/\[(.*)\] (.+?).(\w+): (.*)/', $sLine, $aMatches);

			$aLines[] = array(
				'date' => $aMatches[1],
				'level' => $aMatches[3],
				'data' => $aMatches[4],
			);
		}

		return $aLines;
	}

	public static function parseEntityLog($sFile) {
		$aLines = self::parseLog($sFile);
		$aReturn = array();

		foreach($aLines as $aLine) {

			// Zeilen, die eh keine Daten enthalten überspringen
			if(
				mb_substr($aLine['data'], 0, 19) === 'Intersection Data [' ||
				mb_substr($aLine['data'], 0, 12) === 'Jointables ['
			) {
				continue;
			}

			// Zeile zum Matchen einer Entität
			preg_match('/(.+)::([0-9]+) \[(.+)\] \[(.*)\]/', $aLine['data'], $aMatches);

			// Neue Entität
			if(
				!empty($aMatches[1]) &&
				isset($aMatches[2]) &&
				!empty($aMatches[3])
			) {
				if(!empty(self::$_aCurrentLine)) {
					$aReturn[] = self::$_aCurrentLine;
				}

				self::$_aCurrentLine = array(
					'id' => (int)$aMatches[2],
					'entity' => $aMatches[1],
					'action' => str_replace('"', '', $aMatches[3])
				);
			} else {

				$iFirstSpace = strpos($aLine['data'], ' ');
				$sVar = mb_substr($aLine['data'], 0, $iFirstSpace);

				$aKnownVars = array(
					'Additional' => 'additional',
					'Data' => 'data',
					'VARS' => 'params'
				);

				if(!isset($aKnownVars[$sVar])) {
					throw new UnexpectedValueException('Unknown variable/line "'.$sVar.'"');
				}

				// JSON extrahieren: [] am Ende der Zeichenkette entfernen
				$sJson = mb_substr($aLine['data'], $iFirstSpace + 1, mb_strlen($aLine['data'])-3 - $iFirstSpace-1);
				$mJson = json_decode($sJson, true);

				if(!$mJson) {
					throw new UnexpectedValueException('Invalid JSON string');
				}

				self::$_aCurrentLine[$aKnownVars[$sVar]] = $mJson;
			}

			if(!empty(self::$_aCurrentLine)) {
				$aReturn[] = self::$_aCurrentLine;
			}
		}

		return $aReturn;
	}
}