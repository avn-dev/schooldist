<?php

namespace Core\Service;

use Core\Enums\ErrorLevel;
use Core\Exception\ReportErrorException;
use Core\Helper\Bundle as BundleHelper;
use Core\Handler\ParallelProcessing\TypeHandler;
use Core\Exception\ParallelProcessing\TaskException;
use Core\Exception\ParallelProcessing\FalseException;
use Core\Exception\ParallelProcessing\RewriteException;
use Core\Interfaces\ParallelProcessing\TaskAware;

class ParallelProcessingService {
	/**
	 * @var \Core\Helper\Bundle 
	 */
	private $oBundleHelper;
	
	/**
	 * Konstruktor
	 */
	public function __construct() {
		$this->oBundleHelper = new BundleHelper();
	}

	/**
	 * Führt den übergebenen Task aus und wirft im Fehlerfall eine Exception
	 * 
	 * @TODO Wenn execute/afterAction mit einem Fatal Error/Segfault abstürzen, haben wir eine Endlosschleife!
	 * 
	 * @param array $aTask
	 * @param bool $bDebugMode
	 * @return bool
	 */
	public function executeTask(array $aTask, $bDebugMode = false) {

		$bTypeHandlerExecution = false;

		$oTypeHandler = $this->getTypeHandler($aTask['type']);
			
		if($oTypeHandler instanceof TypeHandler) {
		
			$aData = json_decode($aTask['data'], true);
			
			try {

				if ($oTypeHandler instanceof TaskAware) {
					$oTypeHandler->setTask($aTask);
				}

				$bTypeHandlerExecution = $oTypeHandler->execute($aData, $bDebugMode);

				if($bTypeHandlerExecution === false) {
					throw new FalseException(ErrorLevel::ERROR, 'Execution of task failed', $aTask);
				}
				
			} catch(\Exception $e) {

				$oTypeHandler->afterAction($aData, false);
				$this->throwHandlerException($oTypeHandler, $aTask, $aData, $e);
				
			} catch(\Error $e) {
				// PHP ignoriert unbekannte Klassen bei catch/instanceof, daher funktioniert das in PHP 5.6
				$this->throwHandlerException($oTypeHandler, $aTask, $aData, $e);
			}
		
			$oTypeHandler->afterAction($aData, $bTypeHandlerExecution);
		}	
		
		return true;
	}

	/**
	 * @param TypeHandler $oTypeHandler
	 * @param array $aTask
	 * @param array $aData
	 * @param \Exception|mixed $oException
	 * @throws RewriteException
	 * @throws TaskException
	 */
	private function throwHandlerException(TypeHandler $oTypeHandler, array $aTask, array $aData, $oException) {
		
		if($oException instanceof RewriteException) {
			$oException->setRewriteAttempts($oTypeHandler->getRewriteAttempts());
			throw $oException;
		}

		$aExceptionData = $this->getExceptionData($aTask, $aData, $oException);
		
		// Abwärtskompatibilität - Wenn ein TypeHandler eine Exception wirft oder einfach nur false
		// zurückliefert wird hier eine eigene Exception geschmissen und mit Informationen gefüllt

		// TODO - das sollte mal alles refaktorisiert werden
		if ($oException instanceof ReportErrorException) {
			$oException = new TaskException($oException->getErrorLevel(), $oException->getMessage(), $aTask);
		} else {
			$oException = new TaskException(ErrorLevel::ERROR, $aExceptionData['exception'], $aTask);
		}

		$oException->bindErrorData($aExceptionData);

		// Falls der TypeHandler noch weitere Angaben zu dem Fehler hat können diese hier beigefügt werden
		$oTypeHandler->handleException($aData, $oException);

		// Exception werfen damit diese an anderen Stellen abgefangen werden kann
		throw $oException;
		
	}

	/**
	 * @param array $aTask
	 * @param array $aData
	 * @param \Exception|mixed $oException
	 * @return array
	 */
	public function getExceptionData(array $aTask, array $aData, $oException) {

		$sExceptionMessage = 'Unknown';
		$sExceptionTrace = '';

		if($oException instanceof \Throwable) {
			$sExceptionMessage = $oException->getMessage();
			$sExceptionTrace = $oException->getTraceAsString();
		}

		$aExceptionData = [
			'exception' => $sExceptionMessage,
			'task' => $aTask,
			'data' => $aData,
			'trace' => $sExceptionTrace
		];

		return $aExceptionData;

	}
	
	/**
	 * Liefert das Label anhand des Typen
	 * 
	 * @param string $sType
	 * @return string
	 */
	public function getLabelForType($sType) {
		
		$oTypeHandler = $this->getTypeHandler($sType);
		if($oTypeHandler instanceof TypeHandler) {
			return $oTypeHandler->getLabel();
		}
		
		return '';
	}
	
	/**
	 * Liefert ein Handlerobjekt anhand des Types und den Einstellungen eines Bundles
	 * 
	 * @param string $sType
	 * @return \Core\Handler\ParallelProcessing\TypeHandler|null
	 */
	public function getTypeHandler($sType) {

		$this->checkType($sType);
		
		$aTypeData = explode('/', $sType);

		$sBundle = $this->oBundleHelper->convertBundleName($aTypeData[0]);
		
		$aConfig = $this->getBundleConfigData($sBundle);

		if(!empty($aConfig['parallel_processing_mapping'][$aTypeData[1]]['class'])) {
			$oTypeHandler = new $aConfig['parallel_processing_mapping'][$aTypeData[1]]['class']();
			return $oTypeHandler;			
		}
		
		return null;
	}

	/**
	 * Liefert Daten aus der Config-Datei eines Bundles
	 * - Config-Datei unter: $sBundle/Resources/config/config.yml
	 * 
	 * @param string $sBundle
	 * @return array
	 */
	protected function getBundleConfigData($sBundle) {

		$aBundleConfig = $this->oBundleHelper->getBundleConfigData($sBundle);

		return $aBundleConfig;
	}
	
	/**
	 * Prüft, ob der übergebene Typ dem Format des Parallel Processings entspricht
	 * 
	 * @param string $sType
	 * @throws \RuntimeException
	 */
	protected function checkType($sType) {

		$aTypeData = explode('/', $sType);

		if(
			strpos($sType, '/') === false ||
			count($aTypeData) > 2
		) {
			throw new \RuntimeException('Wrong format for type "'.$sType.'" (bundle/action)');
		}

	}
	
}

