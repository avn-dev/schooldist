<?php

/**
 * v5
 */
class Ext_TC_Import {

	/**
	 * @var DB
	 */
	static public $oDb;

	/**
	 * @var array
	 */
	static public $aHeadlines = array();

	static protected $bResetAutoIncrement = null;

	static public $importMappingKey = null;
	
	/**
	 * @var bool
	 */
	protected $bSave = false;

	/**
	 * @return void
	 */
	public function activateSave() {
		$this->bSave = true;
	}

	/**
	 * Bestimmen, ob Autoincrement in der Datenbank zurückgesetzt wird oder nicht.
	 *
	 * Hier gibt es ein Problem beim Import-Script, dass Datensätze über Methoden angelegt werden,
	 * die dann keinen Import-Key haben und folglich nicht mehr gelöscht werden. Wenn der Import
	 * dann erneut ausgeführt wird und das Autoincrement erfolgreich zurückgesetzt wird, gibt
	 * es in den Verknüpfungstabellen plötzlich Einträge, die mit neuen Datensätzen verknüpft werden.
	 *
	 * Daher muss bei einem leeren System eigentlich ein reset_customer_data-Script ausgeführt werden
	 * und bei einem System in Gebrauch darf das Autoincrement nicht zurückgesetzt werden.
	 *
	 * ALLERDINGS hängt das vom Import-Fall ab! Die IDs sollten nicht irgendwann auf 50000 stehen,
	 * nur weil mehrere Import-Versuche fehlgeschlagen sind.
	 *
	 * @param bool $bReset
	 */
	public static function setAutoIncrementReset($bReset = true) {
		self::$bResetAutoIncrement = $bReset;
	}

	/**
	 * Das kann auch direkt ins entsprechende Feld geschrieben werden. Mit prefix und separator arbeiten!
	 *
	 * @deprecated
	 *
	 * @param array $aFields
	 * @param array $aItem
	 * @param int $iItemId
	 * @param string $sTable
	 * @param string $sField
	 */
	public static function saveComment($aFields, $aItem, $iItemId, $sTable, $sField) {
		// Zusätzliche Kommentare eintragen
		$sComment = "";
		foreach((array)$aFields as $iKey=>$aField) {

			if($aField['target'] == 'comments') {

				if(is_numeric($iKey)) {
					$sKey = 'field_'.$iKey;
				} else {
					$sKey = $iKey;
				}

				if(empty($aItem[$sKey])) {
					continue;
				}

				$sValue = $aField['field'].": ".$aItem[$sKey];

				$sComment .= $sValue."\n";

			}

		}

		if(!empty($sComment)) {

			$sComment = str_replace('\r\n', "\n", $sComment);

			$aData = array(
				$sField=>$sComment
			);
			self::$oDb->update($sTable, $aData, "`id` = ".(int)$iItemId);
		}

	}

	public static function replaceBreaks($sString) {

		if(empty($sString)) {
			return $sString;
		}

		$sString = preg_replace("/(\r\n|\n|\r)/", ", ", $sString);

		return $sString;

	}

	/**
	 * @param array $aFields
	 * @param array $aItem
	 * @param array $aData
	 */
	public static function processItems($aFields, $aItem, &$aData) {

		foreach((array)$aFields as $iKey=>$aField) {

			if(empty($aField['separator'])) {
				$aField['separator'] = ' ';
			}

			$targetDefinedAsArray = false;
			if(is_array($aField['target'])) {
				$aTargets = $aField['target'];
				$targetDefinedAsArray = true;
			} else {
				$aTargets = explode(",", $aField['target']);
			}

			if(
				is_numeric($iKey) &&
				!isset($aItem[$iKey])
			) {
				$sKey = 'field_'.$iKey;
			} else {
				$sKey = $iKey;
			}

			if(
				$aItem[$sKey] === '' &&
				isset($aField['default'])
			) {
				$sValue = $aField['default'];
			} else {

				if(!empty($aField['special'])) {
					$sValue = self::processSpecial($aField['special'], $aItem[$sKey], $aField['additional']);
				} else {
					$sValue = $aItem[$sKey];
				}

			}

			$aValue = null;
			if(is_array($sValue)) {
				$aValue = array_values($sValue);
				$sValue = null;
			}

			foreach((array)$aTargets as $iTarget=>$sTarget) {
				if(
					$sTarget != 'comments' &&
					!empty($sTarget)
				) {

					if($aValue) {
						if($targetDefinedAsArray) {
							$sValue = $aValue[$iTarget] ?? '';
						} else {
							$sValue = $aValue;
						}
					}

					if(empty($aField['keep_breaks'])) {
						$sValue = self::replaceBreaks($sValue);
					}

					// Wert darf nicht Null sein?
					if(
						empty($aField['nullable']) &&
						is_null($sValue)
					) {
						$sValue = '';
					}

					if(
						!empty($aField['prefix']) &&
						!empty($sValue)
					) {
						$sValue = $aField['prefix'].$sValue;
					}

					if(!empty($aField['array_index'])) {
						
						$aData[$sTarget][$aField['array_index']] = $sValue;

					} elseif(
						!empty($aData[$sTarget]) && (
							// Bei overwrite = true in den else-Fall springen
							!isset($aField['overwrite']) ||
							!$aField['overwrite']
						)
					) {
						if(!empty($sValue)) {
							if($aField['order'] == 'first') {
								$aData[$sTarget] = $sValue.$aField['separator'].$aData[$sTarget];
							} else {
								$aData[$sTarget] = $aData[$sTarget].$aField['separator'].$sValue;
							}
						}
					} else {
						$aData[$sTarget] = $sValue;
					}

				}
			}

		}

	}

	/**
	 *
	 * @param array|string $aSpecials
	 * @param string $mValue
	 * @param array|string $mAdditional
	 * @return array|string|int|float|boolean
	 */
	public static function processSpecial($aSpecials, $mValue, $mAdditional=null) {

		if(!is_array($aSpecials)) {
			$aSpecials = array($aSpecials);
		}

		foreach($aSpecials as $sSpecial) {

			switch($sSpecial) {
				case 'language':
					if(!empty($mValue)) {
						$aLanguages = Data_Languages::search($mValue);
						if(!empty($aLanguages)) {
							$mValue = $aLanguages[0]['iso_639_1'];
						}
					}
					break;
				case 'country':
				case 'country_strict':
					if(!empty($mValue)) {
						if($sSpecial === 'country_strict') {
							$aCountries = Data_Countries::search($mValue, 'cn_short_', true);
						} else {
							$aCountries = Data_Countries::search($mValue);
						}
						if(!empty($aCountries)) {
							$mValue = $aCountries[0]['cn_iso_2'];
						}
					}
					break;
				case 'nationality':
					if(!empty($mValue)) {
						$aCountries = Data_Countries::search($mValue, 'nationality_', true);
						
						if(empty($aCountries)) {
							$aCountries = Data_Countries::search($mValue, 'nationality_', false);
						}
						
						if(!empty($aCountries)) {
							if(empty($mAdditional)) {
								$mAdditional = 'cn_iso_2';
							}
							$mValue = $aCountries[0][$mAdditional];
						}
					}
					break;
				case 'country_or_nationality':
					if(!empty($mValue)) {
						$aCountries = Data_Countries::search($mValue);
						
						if(empty($aCountries)) {
							$aCountries = Data_Countries::search($mValue, 'nationality_', false);
						}
						
						if(!empty($aCountries)) {
							$mValue = $aCountries[0]['cn_iso_2'];
						}
					}
					break;
				case 'yes_no':
				case 'yes_no_text':

					// Flex Yes/No
					if($sSpecial === 'yes_no_text') {
						$mAdditional = [
							0 => null,
							1 => 'no',
							2 => 'yes'
						];
					}

					if(
						$mAdditional &&
						!is_array($mAdditional)
					) {
						$mAdditional = array(
							0 => 0,
							1 => 0,
							2 => $mAdditional
						);
					}

					if(empty($mAdditional)) {
						$mAdditional = array(
							0 => 0,
							1 => 0,
							2 => 1
						);
					}

					if(!empty($mValue)) {
						$mValue = mb_strtolower($mValue);

						if(
							$mValue == 'yes' ||
							$mValue == 'true' ||
							$mValue == 'si' ||
							$mValue == 'sí' ||
							$mValue == 'oui' ||
							$mValue == 'ja' ||
							$mValue == 'wahr' ||
							$mValue == 'j' ||
							$mValue == 'y' ||
							$mValue == '1'
						) {
							$mValue = 2;
						} else {
							$mValue = 1;
						}
					} else {
						$mValue = 0;
					}

					$mValue = $mAdditional[$mValue];

					break;
				case 'gender':

					$mValue = trim(rtrim($mValue, '.'));
					$mValue = mb_strtolower($mValue);

					if(
						$mValue == 'mr' ||
						$mValue == 'herr' ||
						$mValue == 'm' ||
						$mValue == 'male' ||
						$mValue == 'monsieur'
					) {
						$mValue = 1;
					} elseif(
						$mValue == 'ms' ||
						$mValue == 'mme' ||
						$mValue == 'mrs' ||
						$mValue == 'frau' ||
						$mValue == 'f' ||
						$mValue == 'w' ||
						$mValue == 'female' ||
						$mValue == 'madame'
					) {
						$mValue = 2;
					} elseif(
						$mValue == 'x' ||
						$mValue == 'divers' ||
						$mValue == 'non-binary'
					) {
						$mValue = 3;
					} else {
						$mValue = 0;
					}

					break;
				case 'currency_USD':
					$mValue = str_replace('$', '', $mValue);
					$mValue = str_replace('.', '', $mValue);
					$mValue = str_replace(',', '.', $mValue);
					$mValue = (float)$mValue;
					break;
				case 'array':
					if($mAdditional !== null) {
						$mValue = $mAdditional[$mValue];
					}
					break;
				case 'array_trim':
					if($mAdditional !== null) {
						$mValue = $mAdditional[trim($mValue)];
					}
					break;
				case 'array_split':
					if($mAdditional !== null) {
						$mValue = array_filter(explode(',', $mValue));
						array_walk($mValue, function(&$mElement) use($mAdditional) {
							$mElement = $mAdditional[trim($mElement)];
						});
					}
					break;
				case 'array_split_semicolon':
					if($mAdditional !== null) {
						$mValue = explode(';', $mValue);
						array_walk($mValue, function(&$mElement) use($mAdditional) {
							$mElement = $mAdditional[trim($mElement)];
						});
					}
					break;
				case 'array_optional':
					if(
						$mAdditional !== null &&
						isset($mAdditional[$mValue])
					) {
						$mValue = $mAdditional[$mValue];
					} elseif(isset($mAdditional[0])) {
						// Default-Wert
						$mValue = $mAdditional[0];
					}
					break;
				case 'array_lower':
					if($mAdditional !== null) {
						$mValue = $mAdditional[strtolower($mValue)];
					}
					break;
				case 'split_trim':
					if ($mAdditional !== null) {
						$mValue = array_map('trim', explode($mAdditional, $mValue));
					}
					break;
				case 'split':
					if($mAdditional !== null) {
						$mValue = explode($mAdditional, $mValue);
					}
					break;
				case 'split_2':
					if($mAdditional !== null) {
						$mValue = explode($mAdditional, $mValue, 2);
					}
					break;
				case 'time':
					if(preg_match('/^[0-9]{2}:[0-9]{1,2}:[0-9]{1,2}$/', $mValue)) {
                        // Format 00:00:00 ist ok
					} else if(preg_match('/^[0-9]{1,2}:[0-9]{1,2}$/', $mValue)){
                        // Format 00:00 müssen sec. ergänz werden
                        $mValue = $mValue.':00';
                    } else {
                        $mValue = '00:00:00';
                    }
                    // ggf fehlende null auffüllen
                    $mValue = explode(':', $mValue);
                    foreach($mValue as $sKey => $sPart){
                        $mValue[$sKey] = str_pad($sPart, 2, '0', STR_PAD_LEFT);
                    }
                    $mValue = implode(':', $mValue);
					break;
				case 'locale_date':
					if($mAdditional) {
						$mValue = self::getMySqlDate($mValue, $mAdditional);
					}
					break;
				// deprecated
				case 'date':
					if($mAdditional) {
						$mValue = self::makeTimestamp('mysql', $mValue, $mAdditional);
					}
					break;
				case 'date_time':
					try {
						if(!empty($mValue)) {
							$oDateTime = DateTime::createFromFormat($mAdditional, $mValue);
							$mValue = $oDateTime->format('Y-m-d H:i:s');
						}
					} catch (Exception $ex) {
					}
					break;
				case 'date_object':
					try {
						if(!empty($mValue)) {
							$oDateTime = new DateTime($mValue);
							$mValue = $oDateTime->format('Y-m-d');
						}
					} catch (Exception $ex) {
					}
					break;
				case 'date_object_format':
					if(!empty($mValue)) {
						$dDate = DateTime::createFromFormat($mAdditional, $mValue);
						if($dDate instanceof DateTime) {
							$mValue = $dDate->format('Y-m-d');
						}
					}
					break;
				// deprecated
				case 'date_unix':
					if($mAdditional) {
						$mValue = self::makeTimestamp('unix', $mValue, $mAdditional);
					}
					break;
				case 'float':
					$mValue = preg_replace('/[^\-0-9\,\.]/', '', $mValue);
					$mValue = str_replace('.', '', $mValue);
					$mValue = str_replace(',', '.', $mValue);
					$mValue = str_replace('£', '', $mValue);
					$mValue = (float)$mValue;
					break;
				case 'int':
					$mValue = (int)$mValue;
					break;
				case 'substring':
					if(!$mAdditional) {
						$mAdditional = 3;
					}
					$mValue = mb_substr($mValue, 0, $mAdditional);
					break;
				case 'clean_email':
					$mValue = self::cleanEmail($mValue);
					break;
				case 'rn_break_replace':
					$mValue = str_replace('\r\n', "\n", $mValue);
					break;
				case 'replace_breaks':
					$mValue = self::replaceBreaks($mValue);
					break;
				case 'nl2br':
					$mValue = nl2br($mValue);
					break;
				case 'stripslashes':
					$mValue = stripslashes($mValue);
					break;
				case 'closure':
					$mValue = $mAdditional($mValue);
					break;
				case 'phone_itu':
					
					$oValidate = new WDValidate();
					$mValue = $oValidate->formatPhonenumber($mValue, $mAdditional);
					
					break;
				default:
					throw new InvalidArgumentException('Unknown special: '.$sSpecial);
			}
		}
		return $mValue;

	}

	public static function convertCharset($mString, $sSourceCharset='cp1252', $sTargetCharset='utf-8') {

		// Wenn beide Charsets gleich sind nix machen
		if($sSourceCharset == $sTargetCharset) {
			return $mString;
		}

		if(is_array($mString)) {

			foreach((array)$mString as $iKey=>$mValue) {
				$mString[$iKey] = self::convertCharset($mValue, $sSourceCharset, $sTargetCharset);
			}

		} else {

			$mString = trim($mString);

			if($sSourceCharset != $sTargetCharset) {
				$mString = iconv($sSourceCharset, $sTargetCharset.'//IGNORE', $mString);
			}

		}

		return $mString;

	}

	public static function getMySqlDate($sInput, $sFormat="%d. %h %y") {

        // local abfragen
        $locale = setlocale(LC_ALL, 0);

        // wenn deutsch muss Mrz durch Mär ersetzt werden
        // bei imports kann das durch excel und co falsch sein!
        if($locale == 'de_DE.UTF-8'){
           $sInput = str_replace('Mrz', 'Mär', $sInput);
        }

		$aDate = strptime($sInput, $sFormat);

		$sDate = '0000-00-00';

		if($aDate) {
			$sDate = sprintf("%04d-%02d-%02d", ($aDate['tm_year'] + 1900), ($aDate['tm_mon']+1), $aDate['tm_mday']);
		}

		return $sDate;

	}

	public static function makeTimestamp($sTargetFormat, $sDate, $sDateFormat, $sTime='', $sTimeFormat='') {

		if(empty($sDate)) {
			return '';
		}

		$sDate = str_replace(' ', '', $sDate);
		$sDate = trim($sDate);

		$aDate = self::getTimestampValues($sDate, $sDateFormat);
		if(!empty($sTime)) {
			$aTime = self::getTimestampValues($sTime, $sTimeFormat);
		}

		if(!empty($aDate['d'])) {
			$aDate['D'] = $aDate['d'];
		} elseif(!empty($aDate['j'])) {
			$aDate['D'] = $aDate['j'];
		}
		if(!empty($aDate['n'])) {
			$aDate['M'] = $aDate['n'];
		} elseif(!empty($aDate['m'])) {
			$aDate['M'] = $aDate['m'];
		}
		if(!empty($aDate['y'])) {
			$iValue = (int)$aDate['y'];
			if(
				$iValue <= (date("y")+2) &&
				$iValue >= 0
			) {
				$aDate['Y'] = $iValue + 2000;
			} else {
				$aDate['Y'] = $iValue + 1900;
			}
		}

		if(!empty($aTime)) {
			if(!empty($aTime['A'])) {
				$aTime['a'] = mb_strtolower($aTime['A']);
			}

			if(!empty($aTime['g'])) {
				if($aTime['a'] == 'pm') {
					$aTime['g'] = (int)$aTime['g'] + 12;
				}
				$aTime['h'] = $aTime['g'];
			} elseif(!empty($aTime['G'])) {
				$aTime['h'] = $aTime['G'];
			} elseif(!empty($aTime['h'])) {
				if($aTime['a'] == 'pm') {
					$aTime['h'] = (int)$aTime['h'] + 12;
				}
				$aTime['h'] = $aTime['h'];
			} elseif(!empty($aTime['H'])) {
				$aTime['h'] = $aTime['H'];
			}
		}

		if($sTargetFormat == 'unix') {
			$sDate = mktime((int)$aTime['h'], (int)$aTime['i'], (int)$aTime['s'], (int)$aDate['M'], (int)$aDate['D'], (int)$aDate['Y']);
		} else {
			if(!empty($aTime)) {
				$sDate = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $aDate['Y'], $aDate['M'], $aDate['D'], $aTime['h'], $aTime['i'], $aTime['s']);
			} else {
				$sDate = sprintf("%04d-%02d-%02d", $aDate['Y'], $aDate['M'], $aDate['D']);
			}
		}

		return $sDate;

	}

	public static function getTimestampValues($sValue, $sFormat) {

		if(empty($sValue)) {
			return '';
		}

		$sValue = mb_strtolower($sValue);

		$aSearch = array(
						'd',
						'j',
						'm',
						'n',
						'y',
						'Y',
						'g',
						'G',
						'h',
						'H',
						'i',
						'a',
						'A',
						's'
						);
		$aReplace = array(
						'(?P<d>[0-3][0-9])',
						'(?P<j>[0-3]?[0-9])',
						'(?P<m>[0-1][0-9])',
						'(?P<n>[0-1]?[0-9])',
						'(?P<y>[0-9]{2})',
						'(?P<Y>[1-2][0-9]{3})',
						'(?P<g>[0-1]?[0-9])',
						'(?P<G>[0-2]?[0-9])',
						'(?P<h>[0-1][0-9])',
						'(?P<H>[0-2][0-9])',
						'(?P<i>[0-5][0-9])',
						'(?P<a>am|pm|)',
						'(?P<s>[0-5][0-9])'
						);
		$sFormat = str_replace($aSearch, $aReplace, $sFormat);
		$sFormat = str_replace('/', '\\/', $sFormat);

		preg_match('/'.$sFormat.'/', $sValue, $aMatch);

		return $aMatch;

	}

	public static function saveCSV2MySql($sFile, $sFileName, $sTable=false, $sCharset='cp1252', $sDelimiter=';', $bMysqli = false, $bUpdateExistingTable=false) {

		if(is_file($sFile)) {

            $oDB = DB::getDefaultConnection();

			if(strpos($sFileName, '.xlsx') !== false) {
			
				$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				$spreadsheet = $reader->load($sFile);
				
			} else {
			
				$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
				$reader->setInputEncoding($sCharset);
				$reader->setDelimiter($sDelimiter);

				$spreadsheet = $reader->load($sFile);
			
			}
			
			$worksheet = $spreadsheet->getSheet(0);
			$aItems = $worksheet->toArray();

			$iFields = count(reset($aItems));

			if($bUpdateExistingTable === true) {

				$aTables = $oDB->tables();

				if(!in_array($sTable, $aTables)) {
					throw new Exception('Table "'.$sTable.'"does not exist.');
				}

			} else {

				$aFields = array('`id` int(11) NOT NULL auto_increment');

				for($i=0; $i<$iFields; $i++) {
					$aFields[] = "`field_".(int)$i."` text NOT NULL";
				}

				if($sTable === false) {
					$sTable = "__".Ext_TC_Util::generateRandomString(8);
					$sSql = "CREATE TEMPORARY TABLE";
				} else {
					$sSql = "DROP TABLE IF EXISTS `".$sTable."`";
					DB::executeQuery($sSql);
					$sSql = "CREATE TABLE";
				}

				// Create table
				$sSql .= " `".$sTable."` (".implode(",", $aFields).", PRIMARY KEY  (`id`))";
				DB::executeQuery($sSql);

			}

			// Zeile mit Überschriften entfernen
			self::$aHeadlines[$sTable] = $aItems[0];
			unset($aItems[0]);

            $stmt = null;
            // Mysqli prepared statment vorbereiten
            if($bMysqli){
                $sSql = "INSERT INTO `".$sTable."` SET ";
                $aSql = array();

                for($i=0; $i<$iFields; $i++) {
                    $sSql .= " `field_".(int)$i."` = ?, ";
                }

                $sSql = rtrim($sSql, ', ');
                $stmt = $oDB->getPreparedStatement($sSql, md5($sSql));
            }

			foreach((array)$aItems as $aItem) {

                $bEmpty = true;
                foreach($aItem as $mValue){
                    if(!empty($mValue)){
                        $bEmpty = false;
                        break;
                    }
                }

				if(!$bEmpty){

                    $aSql = array();

                    if(!$bMysqli){
                        $sSql = "INSERT INTO `".$sTable."` SET ";
                        for($i=0; $i<$iFields; $i++) {
                            $sSql .= " `field_".(int)$i."` = :field_".(int)$i.", ";
                            $aSql['field_'.(int)$i] = (string)$aItem[$i];
                        }
                        $sSql = rtrim($sSql, ', ');
                        DB::executePreparedQuery($sSql, $aSql);
                    //MYSQLI array füllen und statement ausführen!
                    } else {
                        for($i=0; $i<$iFields; $i++) {
                            $aSql[] = (string)$aItem[$i];
                        }
                        $oDB->executePreparedStatement($stmt, $aSql);
                    }
                }

			}

            if($bMysqli){
                $oDB->closePreparedStatements();
            }

			return $sTable;

		}

		return false;

	}

	/*
	 * Die Funktion bekommt einen Tabellennamen und falls die Tabelle vorhanden ist wird ein Backup inkl. Daten erstellt
	 */
	public static function backupTable($sTable = '', $sBackupTable=false) {

		$bSuccess = Util::backupTable($sTable, false, $sBackupTable);

		return $bSuccess;

	}

	public static function cleanEmail($sEmail) {

		$sEmail = mb_strtolower($sEmail);
		$sEmail = str_replace('mailto:', '', $sEmail);
		$sEmail = trim($sEmail, '#');
		$iEmailPos = strpos($sEmail, '#');
		if($iEmailPos > 0) {
			$sEmail = mb_substr($sEmail, 0, $iEmailPos);
		}

		return $sEmail;

	}

	public static function checkEntry($sTable, $aCheckFields) {

		$sSql = "
				SELECT
					*
				FROM
					#table
				WHERE
					1
				";
		$aCheckKeys = array_keys($aCheckFields);
		foreach($aCheckKeys as $sKey) {
			$sSql .= " AND `".$sKey."` = :".$sKey." ";
		}
		$sSql .= " LIMIT 1";
		$aCheckSql = $aCheckFields;
		$aCheckSql['table'] = $sTable;
		$aCheck = DB::getQueryRow($sSql, $aCheckSql);

		return $aCheck;
	}

    /**
	 * Prüft, ob ein Eintrag schon vorhanden ist und legt ihn dann an oder aktualisiert ihn
	 *
	 * @param string $sTable
	 * @param array $aCheckFields
	 * @param array $aData
	 * @param int $iOriginalId
	 * @param string $sImportKey
	 * @param bool $bMysqli
	 * @param bool $bCheckInsertId
	 * @param bool $bOverwriteAll
	 * @return int
	 */
	public static function addEntry($sTable, $aCheckFields, $aData, $iOriginalId=null, $sImportKey=null, $bMysqli = false, $bCheckInsertId = false, $bOverwriteAll=false) {

		if($aCheckFields !== null) {
			$aCheck = self::checkEntry($sTable, $aCheckFields);
		} else {
			$aCheck = null;
		}

        // Nullwerte gibt es nicht, wenn das zutrifft wurde in einem mapping array der eintrag nicht gefunden
        // in diesem Fall müssen wir das auf einen Leeren String ändern ansonsten bekommen wir einen DB Fehler!
		// MK: MAN MUSS AUCH NULL WERTE SPEICHERN KÖNNEN
//        foreach($aData as $sKey=>&$sValue) {
//            if(
//                $sValue === null
//            ) {
//                $aData[$sKey] = '';
//            }
//        }

		if(empty($aCheck)) {

			if($sImportKey){
				$aData['import_key'] = $sImportKey;
			}

			$iEntryId = DB::insertData($sTable, $aData, $bMysqli, $bCheckInsertId);

		} else {

			unset($aData['created']);

			if($bOverwriteAll === false) {
				// Daten vervollständigen
				$aIntersect = array_intersect_key($aCheck, $aData);

				foreach($aData as $sKey=>&$sValue) {
					if(
						empty($sValue) ||
						(
							!empty($aIntersect[$sKey]) &&
							$aIntersect[$sKey] !== 'null'
						)
					) {
						unset($aData[$sKey]);
					}
				}
			}

			// Wenn es neue Daten gibt, Datensatz aktualisieren
			if(!empty($aData)) {
				DB::updateData($sTable, $aData, array('id' => (int)$aCheck['id']), $bMysqli);
			}

			$iEntryId = $aCheck['id'];

		}

		if($iOriginalId > 0) {


            if(!$bMysqli){
                $sSql = "
                    REPLACE 
                        `__import_mapping`
                    SET
                        `table` = :table,
						`key` = :key,
                        `original_id` = :original_id,
                        `new_id` = :new_id,
                        `import_key` = :import_key
                    ";
                $aSql = array(
                    'table' => $sTable,
                    'key' => self::$importMappingKey,
                    'original_id' => (int)$iOriginalId,
                    'new_id' => (int)$iEntryId,
                    'import_key' => (string)$sImportKey
                );
                DB::executePreparedQuery($sSql, $aSql);
            } else {
                $sSql = "
                    REPLACE 
                        `__import_mapping`
                    SET
                        `table` = ?,
						`key` = ?,
                        `original_id` = ?,
                        `new_id` = ?,
                        `import_key` = ?
                    ";

                $aSql = array(
                    $sTable,
					self::$importMappingKey,
                    (int)$iOriginalId,
                    (int)$iEntryId,
                    (string)$sImportKey
                );

                $oDB    = DB::getDefaultConnection();
                $stmt   = $oDB->getPreparedStatement($sSql, md5($sSql));
                $oDB->executePreparedStatement($stmt, $aSql);
            }
		}

		return $iEntryId;

	}

	/**
	 * @return array
	 */
	public static function getMappingInfo($key=null) {

		$sSql = "
			SELECT
				*
			FROM
				`__import_mapping`
			WHERE
				
			";
		
		if($key === null) {
			$sSql .= "`key` IS NULL";
		} else {
			$sSql .= "`key` = :key";
		}
		
		$aMappings = DB::getQueryRows($sSql, ['key'=>$key]);

		$aReturn = array();
		foreach((array)$aMappings as $aMapping) {
			$aReturn[$aMapping['table']][$aMapping['original_id']] = $aMapping['new_id'];
		}

		return $aReturn;

	}

	/**
	 * Macht ein Rollback des letzten Durchlaufs und legt Backups an
	 * 
	 * @todo Attribute müssen unbedingt auch zurückgesetzt werden!
	 * 
	 * @param array $aTables
	 * @param string $sImportKey
	 * @param bool $bRemoveOldEntries
	 */
	public static function prepareImport(array $aTables, $sImportKey, $bRemoveOldEntries=true, $bBackup=true) {

		// Muss zwingend vorhanden sein, sonst gibt es bei veränderten Daten doppelte Einträge / Anomalien
		if(!in_array('__import_mapping', $aTables)) {
			$aTables[] = '__import_mapping';
		}

		if(self::$bResetAutoIncrement === null) {
			throw new RuntimeException('$bResetAutoIncrement is not set! Call Ext_TC_Import::setAutoIncrementReset()?');
		}

		if(empty($sImportKey)) {
			throw new RuntimeException('$sImportKey is empty!');
		}

		/**
		 * Mapping-Tabelle anlegen
		 */
		$sSql = "
			CREATE TABLE IF NOT EXISTS `__import_mapping` (
				`table` varchar(200) NOT NULL,
				`key` varchar(20) NULL DEFAULT NULL,
				`original_id` int(11) NOT NULL,
				`new_id` int(11) NOT NULL,
				`created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
				`import_key` varchar(100) NOT NULL,
				UNIQUE KEY `table` (`table`,`key`,`original_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		DB::executeQuery($sSql);

		foreach($aTables as $sTable) {

			$bSuccess = true;
			
			if($bBackup === true) {
				$bSuccess = Ext_TC_Util::backupTable($sTable);
			}

			if($bSuccess !== false) {

				// Feld ergänzen
				DB::addField($sTable, 'import_key', 'VARCHAR(50) CHARACTER SET ascii NULL DEFAULT NULL', null, 'INDEX');

				// Imports werden neuerdings auch in der GUI verwendet, aber wenn Feld nicht da, fliegt erstes Speichern auf die Schnauze
				WDCache::delete('wdbasic_table_description_'.$sTable);
				WDCache::delete('db_table_description_'.$sTable);

				if($bRemoveOldEntries === true) {
					// Alten Zustand wiederherstellen
					$sSql = "
						DELETE FROM
							#table
						WHERE
							`import_key` = :import_key
						";
					$aSql = array(
						'table' => $sTable,
						'import_key' => $sImportKey
					);

					DB::executePreparedQuery($sSql, $aSql);
				}

				// TODO Die Scripte legen so viele Datensätze über Methoden an, wo demnach kein Import-Key existiert,
				// dass es hier zwangsläufig zu ID-Kollissionen bei erneutem Import kommt, solange nicht alle Daten gelöscht werden!
				try {
                    // Auto-Increment zurücksetzen
                    $sSql = "
                        ALTER TABLE
                            #table
                        AUTO_INCREMENT = 1
                        ";
                    $aSql = array(
                        'table' => $sTable
                    );

                    // Nur ausführen bei einem System, das noch nicht benutzt wird
					if(self::$bResetAutoIncrement) {
						DB::executePreparedQuery($sSql, $aSql);
					}

                } catch (Exception $exc) {
                    
                }
            
			} else {
				__out('Backup of table "'.$sTable.'" failed!', 1);
			}

		}
	}

	/**
	 * @param array $aFlexFields
	 * @param array $aItem
	 * @param int $iItemId
	 *
	 * @return void
	 */
	public function saveFlexValues($aFlexFields, $aItem, $iItemId, $sItemType = '') {

		$aFlexData = array();

		self::processItems($aFlexFields, $aItem, $aFlexData);

		// TODO import_key fehlt! Siehe Ext_Thebing_Import
		Ext_TC_Flexibility::saveData($aFlexData, $iItemId, $sItemType);

	}

	public static function getFileCache($sKey) {
		
		$sPath = Util::getDocumentRoot().'media/secure/filecache/'.md5($sKey).'.txt';
		
		if(is_file($sPath)) {
			$sContent = file_get_contents($sPath);
		
			return json_decode($sContent, true);
		}

	}
	
	public static function saveFileCache($sKey, $mData) {
		
		$sPath = Util::getDocumentRoot().'media/secure/filecache/';
		
		Util::checkDir($sPath);
		
		$sPath .= md5($sKey).'.txt';
		
		file_put_contents($sPath, json_encode($mData));
		
	}
	
	/**
	 * Damit man nicht unnötig leere Spalten berücksichtigt, gibt diese Funktion nur die gefüllten zurück.
	 * 
	 * @param DB $oDb
	 * @param type $sTable
	 */
	public static function getNonEmptyFields(DB $oDb, $sTable, $blacklistPrefix=null) {
		
		$aReturn = [];
		
		$aFields = $oDb->describe($sTable);

		foreach($aFields as $sField=>$aField) {
		
			if(
				$blacklistPrefix !== null &&
				strpos($sField, $blacklistPrefix) === 0
			) {
				continue;
			}			
			
			$aTest = $oDb->queryRow("SELECT * FROM #table WHERE #field != '0' AND TRIM(#field) != '' AND #field IS NOT NULL", ['table' => $sTable, 'field' => $sField]);

			if(!empty($aTest)) {
				$aReturn[] = $sField;
			}

		}

		foreach($aReturn as $sField) {
			echo '$aFields[\''.$sField.'\'] = [\'field\' => \''.$sField.'\', \'target\' => \'comments\'];<br>';
		}

		return $aReturn;
	}
	
	public static function getFlexibilityOptionKeyIds($iFieldId) {
		
		$sSql = "
			SELECT
				`kfsfo`.`key`,
				`kfsfo`.`id`
			FROM
				`tc_flex_sections_fields_options` `kfsfo`
			WHERE
				`kfsfo`.`active` = 1 AND
				`kfsfo`.`field_id` = :field_id
			ORDER BY
				`kfsfo`.`position`
		";
		$aSql = [
			'field_id' => (int)$iFieldId
		];
		$aOptions = DB::getQueryPairs($sSql, $aSql);

		return $aOptions;
	}
	
}