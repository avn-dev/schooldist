<?php

use Admin\Instance;

abstract class Ext_TC_System_Tools {

	/**
	 * Vorhandene ElasticaSearch-Indexe und ihre Checks zum Zurücksetzen
	 *
	 * @return array
	 */
	abstract public function getIndexes();

	/**
	 * Alle ID-Aktionen (Select-Options)
	 *
	 * @return array
	 */
	public function getIdActions() {

		return [
			'System' => [
				'execute_check' => 'Check ausführen',
				'execute_parallel_processing_entry_async' => 'Parallel Processing ID => Über CLI (wiederholt) ausführen',
				'execute_parallel_processing_entry_sync' => 'Parallel Processing ID => Über Web-Prozess (Debugger) ausführen',
				'update_dashboard' => 'Dashboard aktualisieren'
			]
		];

	}

	/**
	 * ID-Aktion ausführen
	 *
	 * @param string $sAction
	 * @param array $aData
	 * @return array
	 */
	public function executeIdAction($sAction, array $aData) {

		switch($sAction) {
			case 'execute_check':
				$start = microtime(true);
				$check = new $aData['value'];
				if (!$check instanceof GlobalChecks) {
					throw new InvalidArgumentException('Check does not extends from GlobalChecks.');
				}

				$return = [
					'result' => $check->executeCheck(),
					'runtime' => microtime(true) - $start
				];

				if ($return['result'] !== true) {
					$return['error'] = 'Check does not return true (Update will stop).';
				}

				return $return;
			case 'execute_parallel_processing_entry_async':

				$oRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oEntry = $oRepository->find(trim($aData['value'])); /** @var \Core\Entity\ParallelProcessing\Stack $oEntry */

				if($oEntry === null) {
					throw new RuntimeException('Stack entry doesn\'t exist!');
				}

				// Kommandozeile für CLI zusammenbauen
				$sLine = System::d('php_executable', 'php') . ' ';
				$sLine .= Util::getDocumentRoot() . 'system/bundles/Core/App/console.php ';
				$sLine .= 'core:parallelprocessing:execute ';
				$sLine .= escapeshellarg(json_encode([$oEntry->getData()]))." -vvv 2>&1";

				$sReturn = $iReturn = null;
				exec($sLine, $sReturn, $iReturn);

				// Eintrag erneut ausführen
				try {
					DB::insertData('core_parallel_processing_stack', $oEntry->getData());
				} catch(DB_QueryFailedException $e) {

				}

				return [$iReturn, $sReturn];

			case 'execute_parallel_processing_entry_sync':

				$oRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oEntry = $oRepository->find(trim($aData['value'])); /** @var \Core\Entity\ParallelProcessing\Stack $oEntry */

				if($oEntry === null) {
					throw new RuntimeException('Stack entry doesn\'t exist!');
				}

				$aEntryData = $oEntry->getData();

				$oParallelProcessingService = new \Core\Service\ParallelProcessingService();
				$oParallelProcessingService->executeTask($aEntryData, true);

				return null;

			case 'update_dashboard':

				$admin = app()->make(Instance::class);

				$oWelcome = \Factory::getObject('\Admin\Helper\Welcome');
				$oWelcome->updateCache($admin, \Illuminate\Http\Request::capture());
				
				return true;
				
			default:
				throw new InvalidArgumentException('Unknown action: "'.$sAction.'"');
		}

	}

	/**
	 * Indexe mithilfe der Checks zurücksetzen
	 *
	 * @param $aData
	 * @return array
	 */
	public function executeIndexReset($aData) {

		$aResult = [];
		$aIndexes = $this->getIndexes();

		if($aData['index_name'] === 'all') {
			foreach($aIndexes as $sClass) {
				$aChecks[] = new $sClass;
			}
		} else {
			$aChecks = [new $aIndexes[$aData['index_name']]];
		}

		/** @var Ext_TC_System_Checks_Index_Reset[] $aChecks */
		foreach($aChecks as $oCheck) {
			if(method_exists($oCheck, 'setStackFilling')) {
				$oCheck->setStackFilling((bool)$aData['fill_stack']);
			}
			$aResult[get_class($oCheck)] = (int)$oCheck->executeCheck();
		}

		return $aResult;

	}

	public function executeFillStack($aData) {
			
		$aIndexes = [];
		$aAllIndexes = $this->getIndexes();

		if($aData['index_name'] === 'all') {
			foreach($aAllIndexes as $sIndex=>$sClass) {
				$aIndexes[] = $sIndex;
			}
		} else {
			$aIndexes[] = $aData['index_name'];
		}

		foreach($aIndexes as $sIndex) {
			$oGenerator = new Ext_Gui2_Index_Generator($sIndex);
			$oGenerator->fillStack();
		}

	}

	/**
	 * Richtige Service-Klasse je nach System
	 *
	 * @return Ext_TS_System_Tools|Ext_TA_System_Tools|self
	 */
	public static function getToolsService() {

		if (class_exists('Ext_TS_System_Tools')) {
			return new Ext_TS_System_Tools();
		}

		if (class_exists('Ext_TA_System_Tools')) {
			return new Ext_TA_System_Tools();
		}

		return new class extends Ext_TC_System_Tools {
			public function getIndexes() {
				return [];
			}
		};

	}

}