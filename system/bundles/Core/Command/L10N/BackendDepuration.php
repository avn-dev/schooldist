<?php

namespace Core\Command\L10N;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

/**
 * Dieser Befehl reäumt die Backenübersetzungen auf
 * 
 * - abgelaufene Übersetzungen werden gelöscht (used < NOW - 12 Monate)
 *   -> es wird geprüft ob diese noch im Code existieren '' oder ""
 * - der gesamte Code in system/ wird auf gängige Muster geprüft (Strings) die 
 *   in die Backendüberstezungen kommen
 */
class BackendDepuration extends AbstractCommand {
	/**
	 * Backendpbersetzungen 
	 * 
	 * @var string
	 */
	protected $sTable = 'language_data';
	
	/**
	 * Log: core_l10_depuration
	 * 
	 * @var Log 
	 */
	protected $oLog;
	
	/**
	 * @var bool
	 */
	protected $bExecute = false;

	/**
	 * Hier werden alle gelöschten Codes gespeichert um diese mit der Liste der neu eingefügten
	 * Übersetzungen zu überprüfen
	 * 
	 * crc32() => Test
	 * 
	 * @var array 
	 */
	protected $aDeletedCodes = [];
	
	/**
	 * Liste von Patterns wie Übersetzungen im Code auftauchen können
	 * Shell => preg_match
	 * 
	 * @var array
	 */
	protected $aMatchingPatterns = [
		[
			'shell' => "::t(",
			'regex' => '/::t\(("|\')(.*?)\1(,|\))/im',
			'index' => 2
		],
		[
			'shell' => "->t(",
			'regex' => '/->t\(("|\')(.*?)\1(,|\))/im',
			'index' => 2
		],
		[
			'shell' => "->translate(",
			'regex' => '/->translate\(("|\')(.*?)\1(,|\))/im',
			'index' => 2
		],
		/*[
			'shell' => "|L10N",
			'regex' => '/{(\\\\?)("|\')(.*?)(\\\\?)\2|L10N(.*?)}/im',
			'index' => 3,
			'extensions' => ['.tpl']
		],*/
		[
			'shell' => "[[:alpha:]]", // @todo - das kann man bestimmt noch anders mit grep lösen
			'regex' => '/\'([\wöäüß%"\{\}]+\s([\wöäüß%"\{\}\s]+([\.\?\!])?)+)\'/i',
			'index' => 1,
			'extensions' => ['.php', '.html']
		]
	];
	
	/**
	 * Liste von Tabellen in denen Werte stehen die ebenfalls in L10N geschrieben werden
	 * 
	 * @var array
	 */
	protected $aBlockedTables = [
		[
			'table' => 'tc_flex_sections',
			'columns' => ['title']
		],
		[
			'table' => 'kolumbus_statistic_cols_definitions',
			'columns' => ['title']
		],
		[
			'table' => 'kolumbus_statistic_cols_group',
			'columns' => ['title']
		],
		[
			'table' => 'kolumbus_statistic_pages',
			'columns' => ['title']
		],
		[
			'table' => 'kolumbus_statistic_statistics',
			'columns' => ['title']
		],
		[
			'table' => 'kolumbus_examination_sections_entity_type',
			'columns' => ['title']
		]
	];


	/**
	 * Konfiguriert eigene Befehle für die Symfony2-Konsole
	 */
    protected function configure() {
		
         $this->setName("core:l10n:depuration")
            ->setDescription("Cleans up backend translations")
			->addOption(
				'execute',
				'e',
				InputOption::VALUE_NONE,
				'If not set, only show results.'
			);

    }
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return bool
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);
		
		ini_set('memory_limit', '1G');
		
		// Eigenes Log weil das riesig werden kann
		$this->oLog = \Log::getLogger('l10n');

		$this->bExecute = $input->getOption('execute');

		// Debugging (durch . getrennt damit die Suche nicht in dieser Datei fündig wird)
//		$b = $this->checkCodeExistingTranslation("Es sind E-Mail-Konten mit zu vielen E-Mails vorhanden. Das kann zu "."Problemen mit der Geschwindigkeit der Anwendung führen!");		
//		dd($b);
		
		#$sTransactionPoint = 'core_l10n_depuration';
				
		// Hat keinen Sinn wg. MyISAM
		#\DB::begin($sTransactionPoint);
		
		/**
		 * Backup --------------------------------------------------------------
		 */
		
		$output->writeln('Starting backup');
		
		if($this->bExecute) {
			
			$mBackup = \Util::backupTable($this->sTable);
			$mBackupFiles = \Util::backupTable('language_files');
			$mBackupExternal = \Util::backupTable('language_data_external');
	
			if(!$mBackup || !$mBackupFiles || !$mBackupExternal) {
				$output->writeln('Backup of backend translations failed! Stopping...');
				return 1;
			}
			
			$output->writeln('....backup: '.$mBackup);
			$output->writeln('....backup external: '.$mBackupExternal);
			$output->writeln('....backup files: '.$mBackupFiles);
		
		}
		
		try {
		
			/**
			 * Globale Übersetzungen anlegen ---------------------------------------
			 */
			
			$output->writeln('Start collecting existing translations in database');
			
			$aCurrentTranslations = $this->getAllTranslations();

			$output->writeln('....found: '.count($aCurrentTranslations));
			
			// Alle Vorkommnisse von L10N unter system/ suchen und als globale Übersetzung neu speichern

			$output->writeln('Start searching for translations in system/');
			
			$aTranslations = $this->collectPossibleTranslationsInFiles();

			$output->writeln('....found: '.count($aTranslations));

			$output->writeln('Start generating global translations');
			
			$iGenerated = 0;
			foreach($aTranslations as $sTranslationCode) {

				// Nur ergänzen mit File-ID 0 wenn noch nicht da
				if(!isset($aCurrentTranslations[crc32($sTranslationCode)])) {
					
					// Sicherheitsabfrage
					$sSqlSearch = "
						SELECT
							*
						FROM
							`language_data`
						WHERE
							`active` = 1  AND
							`code` COLLATE utf8mb4_bin = :text
					";
					$aSqlSearch = [
						'text' => $sTranslationCode
					];

					try {
						$aResult = \DB::getQueryRow($sSqlSearch, $aSqlSearch);
						if (empty($aResult)) {

							$aData = [
								'created' => date('Y-m-d H:i:s'),
								'active' => 1,
								'use' => 1,
								'file_id' => 0,
								'code' => $sTranslationCode,
								'de' => $sTranslationCode,
								'used' => date('Y-m-d H:i:s'),
								'created_language' => 'de'
							];

							if ($this->bExecute) {
								\DB::insertData('language_data', $aData);
							}

							++$iGenerated;
						}
					} catch (\Exception $e) {
						// Probably because collation of table or column not yet changed to utf8mb4. See Collation_Check.
						$output->writeln('....exception: '. $e->getMessage());
					}
					
				}

			}
			
			$output->writeln('....generated: '. $iGenerated);
			
			/**
			 * Abgelaufene Übersetzungen -------------------------------------------
			 */
			if(0) {
				$dDepurationDate = (new \DateTime())->sub(new \DateInterval('P1Y')); // -1 Jahr

				$output->writeln('Start collecting outdated tanslations');

				// Alle Übersetzungen suchen wo used < NOW - 12 Monate 
				$aOutdatedTranslations = $this->collectOutdatedTranslations($dDepurationDate);
				$iSumOutdated = count($aOutdatedTranslations);

				$output->writeln('....found: '.$iSumOutdated);

				/**
				 * Blockierte Übersetzungscodes ----------------------------------------
				 */

				$output->writeln('Start collecting blocked translation codes');

				// Übersetzungen suchen die nicht gelöscht werden dürfen (z.b weil sie aus der Datenbank kommen)
				$aBlockedTranslations = $this->collectBlockedTranslations();

				$output->writeln('....found: '.count($aBlockedTranslations));

				/**
				 * Löschen -------------------------------------------------------------
				 */

				$output->writeln('Start cleaning up');

				$iDeleteCount = $iCount = 0;

				foreach($aOutdatedTranslations as $aTranslation) {

					++$iCount;

					if(isset($aBlockedTranslations[crc32($aTranslation['code'])])) {				
						continue;
					}

					// Prüfen ob der Übersetzungscode noch in system/ vorkommt
					$bExistsInCode = $this->checkCodeExistingTranslation($aTranslation['code']);

					if($bExistsInCode === false && !empty($aTranslation['trace'])) {
						$bExistsInCode = $this->checkCodeExistingByTrace($aTranslation['code'], $aTranslation['trace']);
					}

					if($bExistsInCode === false) {
						$this->deleteTranslation($aTranslation['id']);

						++$iDeleteCount;
						$output->writeln('Checking code ('.$iCount.'/'.$iSumOutdated.'): "'. $aTranslation['code'] .'" (Id: "'.$aTranslation['id'].'", last used: "'.$aTranslation['used'].'")');
						$output->writeln('....deleted');

						$this->oLog->addError('delete translation', ['code' => $aTranslation['code'], 'id' => $aTranslation['id']]);

						$this->aDeletedCodes[crc32($aTranslation['code'])] = $aTranslation['code'];
					} else {
						// used updaten da es im Code noch existiert
						if($this->bExecute) {
							\DB::updateData($this->sTable, ['used' => (new \DateTime())->format('Y-m-d H:i:s')], ['id' => $aTranslation['id']]);
						}
					}

				}

				$output->writeln('Deleted: '. $iDeleteCount);
				
			}
			
			$output->writeln('Searching for duplicates (global/not global)');
			
			$this->cleanUpGlobalDuplicates($output);
			
			$output->writeln('Clean up files table');
			
			$this->cleanUpLanguageFiles();
		
		} catch (\Exception $e) {
			#\DB::rollback($sTransactionPoint);
			$output->writeln('ERROR: '.$e->getMessage());
			return 1;
		}
		
		#\DB::commit($sTransactionPoint);
		
		$output->writeln('Finished');

		return Command::SUCCESS;
	}
	
	protected function getAllTranslations() {
		
		$sSql = "
			SELECT
				`id`,
				`used`,
				`code`
			FROM
				#table
			WHERE
				1				
		";

		$aItems = (array)\DB::getQueryRows($sSql, ['table' => $this->sTable]);
		
		$aTranslations = [];
		foreach($aItems as $aItem) {
			$aTranslations[crc32($aItem['code'])] = $aItem['code'];
		}

		return $aTranslations;
	}
	
	protected function cleanUpGlobalDuplicates($output) {
		
		// Alle nicht globalen Übersetzungen wo `de` = `code` ist
		$sSql = "
			SELECT 
				* 
			FROM 
				#table 
			WHERE 
				`file_id` > 0 AND 
				`active` = 1 AND 
				`use` = 1 AND 
				`de` = `code`
		";
		
		$aNonGlobalTranslations = (array) \DB::getPreparedQueryData($sSql, ['table' => $this->sTable]);
		
		$unusedTranslations = 0;
		foreach($aNonGlobalTranslations as $aTranslation) {
			// Globale Übersetzung wo der Code übereinstimmt und `de` = `code`
			$sExistingSql = "
				SELECT 
					* 
				FROM 
					#table 
				WHERE 
					`file_id` = 0 AND 
					`active` = 1 AND 
					`use` = 1 AND 
					`code` = :code AND
					`de` = :code
			";
			
			$aExistingGlobalTranslations = (array)\DB::getPreparedQueryData($sExistingSql, [
				'table' => $this->sTable,
				'code' => $aTranslation['code']
			]);
			
			if(!empty($aExistingGlobalTranslations)){
				
				$unusedTranslations++;
				
				$this->unuseTranslation($aTranslation['id']);
			}
			
		}
		
		$output->writeln('Unuse translations "'.$unusedTranslations.'" because of global existing');
		
	}
	
	/**
	 * Löscht alle unbenutzten language files
	 */
	protected function cleanUpLanguageFiles() {
		if($this->bExecute) {
			\DB::executeQuery("DELETE `language_files` FROM `language_files` LEFT JOIN `language_data` ON `language_files`.`id` = `language_data`.`file_id` WHERE `language_data`.`id` IS NULL");
		}
	}
	
	/**
	 * Prüft ob der übergebene Übsersetzungscode noch unter system/ existiert. Dabei wird
	 * zuerst mit Anführungszeichen geprüft (" + ') und wenn dort nichts gefunden wurde 
	 * wird nochmal explizit in den YAML-Dateien gesucht	 
	 * 
	 * @param string $sCode
	 * @return bool
	 */
	public function checkCodeExistingTranslation($sCode, $sFilePath = null) {
					
		if(is_null($sFilePath)) {
			$sFilePath = 'system/';
		}
		
		$sCode = str_replace("\\n", '*', $sCode);		
		$sCode = addslashes($sCode);

		$aPossibleAppearances = [
			sprintf('-e "\'%s\'"', $sCode),						// 'Test'
			sprintf('-e "\"%s\""', $sCode),						// "Test"
			sprintf("-e '\"%s\"'", addslashes($sCode)),			// "Test \"{name}\" editieren"
			sprintf('-e "\'%s\'"', addslashes($sCode))			// 'Test \'{name}\' editieren'
		];
		
		$sCmd = 'cd '.\Util::getDocumentRoot().'; grep -R '.implode(' ', $aPossibleAppearances). ' '.$sFilePath.' | head -1';
		// Als erstes nach dem Code mit Anführungszeichen suchen (z.B. 'example', "example")
		$bFound = $this->search($sCmd, $sCode);
		
		if(!$bFound && $sFilePath === 'system/') {
			// In den YAML-Dateien stehen die Übersetzungen ohne Anhührungszeichen
			$sYmlCmd = 'cd '.\Util::getDocumentRoot().'; grep -R --include \*.yml -e "'.$sCode.'" system/ | head -1';
			$bFound = $this->search($sYmlCmd, $sCode);
		}

		return $bFound;		
	}
	
	/**
	 * Die Aufrufe anhand des Bachtraces durchlaufen und nochmal explizit in den 
	 * Dateien suchen
	 * 
	 * @param string $sCode
	 * @param string $sTrace
	 * @return bool
	 */
	protected function checkCodeExistingByTrace($sCode, $sTrace) {
		
		$aTrace = explode('>', $sTrace);
		// Rückwärts durchlaufen da der L10N Aufruf in der Regel am Ende steht
		$aReverseTrace = array_reverse($aTrace);

		foreach($aReverseTrace as $sFunctionCall) {
			
			$sCall = trim($sFunctionCall);			
			
			if(strpos($sCall, 'L10N::') !== false) {
				continue;
			}
			
			$sFile = null;
					
			if(strpos($sCall, '::') !== false) {
				$aCall = explode('::', $sCall);
				
				if(class_exists($aCall[0])) {
					$reflector = new \ReflectionClass($aCall[0]);	
					// Dateipfad der Klasse herausfinden
					$sFile = $reflector->getFileName();
				}				
			}
			
			if(is_string($sFile) && file_exists($sFile)) {
				
				$sFilePath = str_replace(\Util::getDocumentRoot(), '', $sFile);
				// explizit in der Datei suchen
				$bFound = $this->checkCodeExistingTranslation($sCode, $sFilePath);
				
				if($bFound) {
					return true;
				}
			}

		}
				
		return false;
	}
	
	/**
	 * Prüft ob die Code-Suche für ein Shell-Befehl erflogreich war
	 * 
	 * @param string $sCmd
	 * @param string $sCode
	 * @return bool
	 */
	protected function search($sCmd, $sCode) {
		
		$mOutput = $this->executeCommand($sCmd);

		if($mOutput !== false) {
			$this->oLog->addInfo('code found', ['code' => $sCode, 'output' => $mOutput]);
			return true;
		}
		
		$this->oLog->addError('code not found', ['code' => $sCode]);
		
		return false;
	}
	
	/**
	 * Die Methode versucht Strings 'hello world' herauszufiltern die in die Backendübersetzungen gepflegt werden müssen
	 * 
	 * @return array
	 */
	protected function collectPossibleTranslationsInFiles() {

		$aTranslations = [];
		
		foreach($this->aMatchingPatterns as $sShellPattern => $aRegexPattern) {
					
			$aIncludes = [];
			// Spezifische Dateiendungen durchsuchen (.php, .yml, ...)
			if(isset($aRegexPattern['extensions'])) {
				foreach($aRegexPattern['extensions'] as $sExtension) {
					$aIncludes[] = '--include \*'.$sExtension;
				}
			}
			
			$sCmd = 'cd '.\Util::getDocumentRoot().'; grep -R '.implode(" ", $aIncludes).' -e "'.$aRegexPattern['shell'].'" system/';

			$mContent = \Update::executeShellCommand($sCmd);

			if($mContent !== false) {

				$aRows = explode(PHP_EOL, $mContent);

				foreach($aRows as $sOriginalRow) {

					// Dateipfad und Inhalt herausfiltern
					$iExtensionPos = strpos($sOriginalRow, ':');
					$sFilePath = substr($sOriginalRow, 0, $iExtensionPos);
					$sRow = str_replace($sFilePath.':', '', $sOriginalRow);

					// bestimmte Muster der Zeile herausfiltern (z.b. machen Exceptions keinen Sinn)
					if(
						empty($sRow) ||
						strpos($sFilePath, '/Command/') !== false ||
						strpos($sFilePath, '/system/checks/') !== false ||
						strpos($sFilePath, '/includes/checks/') !== false ||
						strpos($sFilePath, '/internal/') !== false ||
						strpos($sFilePath, 'system/includes/class.update.php') !== false ||
						strpos($sFilePath, 'system/bundles/Cms/Includes/footer.inc.php') !== false ||	
						strpos($sFilePath, 'system/bundles/TsMobile/') !== false ||		
						strpos($sFilePath, 'system/bundles/TsStudentApp/Http') !== false ||	
						strpos($sFilePath, 'system/bundles/TsStudentApp/Pages') !== false ||	
						strpos($sFilePath, 'system/bundles/TsAgencyLogin/') !== false ||	
						strpos($sFilePath, 'system/bundles/TsTeacherLogin/') !== false ||	
						strpos($sFilePath, 'system/includes/class.db.php') !== false ||
						strpos($sFilePath, 'system/extensions/kolumbus_db_merge.php') !== false ||
						strpos($sFilePath, 'TsStatistic/Helper/QuicExcel.php') !== false ||
						strpos($sFilePath, 'login.php') !== false ||
						strpos($sFilePath, 'system/extensions/thebing/access/class.client.php') !== false ||
						strpos($sFilePath, '/Resources/config/') !== false ||
							
						strpos($sRow, '->class') !== false ||
						strpos($sRow, '$aConfig[\'css\']') !== false ||
						strpos($sRow, 'JOIN') !== false ||
						strpos($sRow, 'INTO') !== false ||
						strpos($sRow, '$sJsonErrorMessage') !== false ||
						strpos($sRow, '->addError') !== false ||
						strpos($sRow, '->addInfo') !== false ||
						strpos($sRow, '->addDebug') !== false ||
						strpos($sRow, '->modify(') !== false ||
						strpos($sRow, '$aLog') !== false ||
						strpos($sRow, 'aDebugInfo') !== false ||						
						strpos($sRow, 'Exception') !== false ||
						strpos($sRow, 'throw new') !== false ||
						strpos($sRow, '\'.') !== false ||					
						strpos($sRow, 'executeShellCommand') !== false ||					
						strpos($sRow, 'addArgument') !== false ||
						strpos($sRow, 'TEXT NOT NULL') !== false ||							
						strpos($sRow, 'Create Table') !== false ||						
						strpos($sRow, 'HTTP Statuscode') !== false ||						
						strpos($sRow, 'timestamp NULL') !== false ||
						strpos($sRow, '__uout(') !== false ||
						strpos($sRow, '__pout(') !== false ||
						strpos($sRow, '__out(') !== false ||					
						strpos($sRow, '->writeln(') !== false ||							
						strpos($sRow, 'wdmail(') !== false ||					
						strpos($sRow, 'sendErrorMessage(') !== false ||					
						strpos($sRow, 'writeAgencyErrorMessage(') !== false ||
						strpos($sRow, '::reportError(') !== false ||
						strpos($sRow, '::log(') !== false ||
						strpos($sRow, '->log(') !== false ||
						strpos($sRow, '->log->') !== false ||
						strpos($sRow, '->tf(') !== false ||
						strpos($sRow, '$tf(') !== false ||
						strpos($sRow, 'mail(') !== false ||
						strpos($sRow, '::reportMessage(') !== false ||
						strpos($sRow, '$oConfig->set(') !== false ||
						strpos($sRow, 'strtotime(') !== false ||
						strpos($sRow, '->setDebugPart(') !== false ||
						strpos($sRow, 'die(') !== false ||
						strpos($sRow, '->error(') !== false ||
						strpos($sRow, 'DateTime(') !== false ||
						strpos($sRow, 'oLog->') !== false ||
						strpos($sRow, 'oLogger->') !== false ||
						strpos($sRow, '->err(') !== false ||
						strpos($sRow, '->debug(') !== false ||
						strpos($sRow, '->assignError(') !== false ||
						strpos($sRow, 'dropZone.text(') !== false ||
						strpos($sRow, '\'class\' =>') !== false ||
						strpos($sRow, '"class" =>') !== false ||
						strpos($sRow, 'exec(') !== false ||
						strpos($sRow, '$sSavePrefix') !== false ||
						strpos($sRow, '$sClass') !== false ||
						strpos($sRow, '\'class\'') !== false ||
						strpos($sRow, '\'frontend\'') !== false ||
						strpos($sRow, '$languageFrontend') !== false ||
						// Kommentarzeile
						preg_match('/^\s*(\*|\/\/|\#)/mi', $sRow) === 1
					) {
						continue;
					}

					// String herausfiltern
					$aMatches = [];															
					preg_match_all($aRegexPattern['regex'], $sRow, $aMatches);

					$iIndex = $aRegexPattern['index'];

					//$this->oLog->addInfo('match', ['row' => $sRow, 'match' => $aMatches]);
					
					if(!empty($aMatches[$iIndex])) {						
						$aTranslations = array_merge($aTranslations, array_filter($aMatches[$iIndex], function($sMatch) {
							return (!empty($sMatch) && mb_strlen($sMatch) > 8);
						}));
					}				
				}
			}			
		}
		
		return array_unique($aTranslations);
	}

	/**
	 * Liefert alle Übersetzungen bei denen "used" älter als ein Jahr ist
	 * 
	 * @param \DateTime $dDepurationDate
	 * @return array
	 */
	protected function collectOutdatedTranslations(\DateTime $dDepurationDate) {
		
		$sSql = "
			SELECT
				`id`,
				`used`,
				`code`,
				`trace`
			FROM
				#table
			WHERE
				`active` = 0 ||
				`used` < :depuration_date ||
				`used` = :no_date				
		";
		
		return (array) \DB::getPreparedQueryData($sSql, [
			'table' => $this->sTable,
			'depuration_date' => $dDepurationDate->format('Y-m-d H:i:s'),
			'no_date' => '0000-00-00 00:00:00'
		]);
		
	}
	
	/**
	 * Erstellt ein Array mit Übersetzungscodes die nicht gelöscht werden dürfen
	 *  
	 * @return array
	 */
	protected function collectBlockedTranslations() {
		
		$aBlockedTranslationCodes = [];
		
		foreach($this->aBlockedTables as $aTableConfig) {
			
			if(!\Util::checkTableExists($aTableConfig['table'])) {
				continue;
			}
			
			$sSql = "SELECT * FROM #table";
			
			$aTableEntries = (array) \DB::getPreparedQueryData($sSql, [
				'table' => $aTableConfig['table']
			]);
			
			foreach($aTableEntries as $aEntry) {
				
				if(
					isset($aEntry['active']) &&
					$aEntry['active'] == 0
				) {
					continue;					
				}
				
				foreach($aTableConfig['columns'] as $sColumn) {
					if(isset($aEntry[$sColumn])) {
						$aBlockedTranslationCodes[crc32($aEntry[$sColumn])] = $aEntry[$sColumn];
					}
				}				
			}
			
		}
		
		return $aBlockedTranslationCodes;
	}

	/**
	 * Führt ein Shell-Kommando aus. Liefert false wenn es keine Ausgabe gibt oder
	 * wenn der Befehl nicht ausgeführt werden konnte
	 * 
	 * @param string $sCmd
	 * @return bool
	 */
	protected function executeCommand($sCmd) {
		
		$oProcess = new Process([$sCmd]);		
		$oProcess->run();
				
		$sOutput = $oProcess->getOutput();

		if(
			$oProcess->isSuccessful() &&
			!empty($sOutput)
		) {
			return $sOutput;
		}
		
		return false;
	}
	
	public function unuseTranslation($iTranslationId) {
		if($this->bExecute) {
			\DB::executePreparedQuery('UPDATE #table SET `use` = 0 WHERE `id` = :id', [
				'table' => $this->sTable,
				'id' => $iTranslationId
			]);
		}
	}
	
	public function deleteTranslation($iTranslationId, $bSoftDelete = false) {
		
		if(!$this->bExecute) {
			return;
		}
		
		if($bSoftDelete) {
			
			\DB::executePreparedQuery('UPDATE #table SET `active` = 0 WHERE `id` = :id', [
				'table' => $this->sTable,
				'id' => $iTranslationId
			]);
			
		} else {
			
			\DB::executePreparedQuery('DELETE FROM #table WHERE `id` = :id', [
				'table' => $this->sTable,
				'id' => $iTranslationId
			]);
			\DB::executePreparedQuery('DELETE FROM `language_data_external` WHERE `language_data_id` = :id', [
				'id' => $iTranslationId
			]);
			
		}
			
	}
	
}
