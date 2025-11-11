<?php

namespace Core\Service;

class Module {

	/**
	 * @var \Update
	 */
	private $oUpdate;
	
	/**
	 * @var array
	 */
	private $aModule;
	
	/**
	 * @var \Log
	 */
	private $oLog;
	
	public function __construct(\Update $oUpdate, array $aModule) {
		
		$this->oUpdate = $oUpdate;
		$this->aModule = $aModule;
		
		$this->oLog = \Log::getLogger('modules');
		
	}
	
	public function getFiles() {
		
		$this->oLog->addInfo('Get files for module "'.$this->aModule['key'].'"');
		
		// Dateien updaten
		$aUpdatedFiles = array();

		// Der Pfad zu composerdatei dieses Moduls, wie er in der Variable $file aussehen würde
		$sComposerFileName = '/system/bundles/' . ucfirst($this->aModule['key']) . '/composer.json';

		foreach ((array)$this->aModule['files'] as $file) {

			$bUpdate = $this->oUpdate->getFile($file, true, 1.001);
			if ($bUpdate === true) {

				$bFileChanged = $this->oUpdate->getLastFileChanged();

				if ($bFileChanged) {
					
					$this->oLog->addInfo('Updated file "'.$file.'"');
					
					// Wenn die Composerdatei geupdatet wurde, dann muss ein composer update durchgeführt werden. Dabei wird die composerdatei dynamisch erstellt.
					if ($sComposerFileName === $file) {
						$bComposerSuccess = $this->oUpdate->executeComposerUpdate();
						if ($bComposerSuccess) {
							$this->oLog->addInfo('Executed composer update');
							echo '<p>' . \L10N::t('Das Update der Fremdsoftware via Composer war erfolgreich.', 'Framework') . '</p>';
						} else {
							echo '<p class="red">' . \L10N::t('Das Update der Fremdsoftware via Composer konnte nicht durchgeführt werden!', 'Framework') . '</p>';
						}
					}

					echo sprintf(\L10N::t('Update von Datei "%s" war erfolgreich.', 'Framework'), $file) . "<br />";
					$aUpdatedFiles[] = $file;
				}
			} else {
				echo '<p class="red">' . sprintf(\L10N::t('Update von Datei "%s" ist fehlgeschlagen.', 'Framework'), $file) . '</p>';
			}
		}
		
		return $aUpdatedFiles;
	}
	
	public function executeConfigSql() {

		$this->oLog->addInfo('Execute config sql for module "'.$this->aModule['key'].'"');
		
		if(empty($this->aModule['require']['configsql'])) {
			return [];
		}

		foreach($this->aModule['require']['configsql'] as $sSql) {
			if(
				!empty($sSql) &&
				!preg_match("/(DROP|DELETE|PROCESS|SHUTDOWN)/i", $sSql) && 
				preg_match("/(SELECT|INSERT|CREATE|UPDATE|ALTER)/i", $sSql)
			) {
				\DB::executeQuery($sSql);
			}
		}

	}
		
	public function executeQueries() {
		
		$this->oLog->addInfo('Execute queries for module "'.$this->aModule['key'].'"');

		if(empty($this->aModule['queries'])) {
			return [];
		}
		
		$aUpdatedQueries = [];

		// SQL updaten
		foreach((array)$this->aModule['queries'] as $sSql) {
			if(
				!empty($sSql) &&
				!preg_match("/(DROP|DELETE|PROCESS|SHUTDOWN)/i", $sSql) && 
				preg_match("/(SELECT|INSERT|CREATE|UPDATE|ALTER)/i", $sSql)
			) {
				$bSuccess = false;
				try {
					$bSuccess = \DB::executeQuery($sSql);
				} catch(\Exception $e) {
					if(\System::d('debugmode') == 2) {
						__out($e->getMessage());
					}
				}
				
				if($bSuccess !== false) {
					
					$this->oLog->addInfo('Executed query "'.$sSql.'"');
					
					$aUpdatedQueries[] = $sSql;
				}
				
			}
		}
		
		return $aUpdatedQueries;
	}
	
	public function install() {

		$sSql = "INSERT INTO system_elements SET title = :title, element = 'modul', category = :category, file = :file, administrable = 1, active = 1";
		$aSql = [
			'title' => $this->aModule['title'],
			'category' => $this->aModule['category'],
			'file' => $this->aModule['key']
		];
		\DB::executePreparedQuery($sSql, $aSql);

		$this->executeQueries();
		
		$this->getFiles();
		
		$this->executeConfigSql();

		return true;
	}
	
}