<?php

namespace Core\Command\ParallelProcessing;

use Core\Command\AbstractCommand;
use Core\Entity\ParallelProcessing\Stack;
use Core\Enums\ErrorLevel;
use Core\Events\ParallelProcessing\RewriteTask;
use Core\Events\ParallelProcessing\TaskFailed;
use Core\Facade\SequentialProcessing;
use Core\Service\ParallelProcessingService;
use Core\Exception\ParallelProcessing\TaskException;
use Core\Exception\ParallelProcessing\RewriteException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

class Execute extends AbstractCommand {
	
	/**
	 * Konfiguriert eigene Befehle für die Symfony2-Konsole
	 */
    protected function configure() {   

        $this->setName("core:parallelprocessing:execute")
             ->setDescription("Executes tasks")
			 ->addArgument('tasks', InputArgument::REQUIRED, 'JSON encoded array with task');

    }

	/**
	 * Führt die übergebenen Tasks aus
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);
		
		$aReturn = array();
		$aReturn['success'] = 0;
		
		$sTasks = $input->getArgument('tasks');
		$aTasks = json_decode($sTasks, true);

		$oParallelProcessingService = new ParallelProcessingService();
		$oRepository = Stack::getRepository();
		
		foreach($aTasks as $aTask) {

			$bRewriteTask = false;
			$bWriteTaskToErrorStack = false;
			$aExceptionData = [];
			
			// Wenn data zu groß ist, reicht es auch nur die ID zu übergeben.
			if(!isset($aTask['data'])) {
				
				$oTask = $oRepository->find($aTask['id']);
				
				if($oTask !== null) {
					$aTask = $oTask->aData;
				}
			}

			try {

				$bTypeHandlerExecution = $oParallelProcessingService->executeTask($aTask, $this->bDebugmode);

			} catch(RewriteException $oRewriteException) {
				
				$bTypeHandlerExecution = false;

				// Anzahl der Ausführungen überschritten, daher Task in den Error-Stack schreiben
				if($oRewriteException->getRewriteAttempts() === (int)$aTask['execution_count']) {

					$bWriteTaskToErrorStack = true;

					$sMessage = 'Rewrite exception limit of '.$oRewriteException->getRewriteAttempts().' exceeded!';
					if(!empty($oRewriteException->getMessage())) {
						$sMessage .= ' '.$oRewriteException->getMessage();
					}

					$oException = new TaskException(ErrorLevel::CRITICAL, $sMessage, $aTask);
					$aExceptionData = $oParallelProcessingService->getExceptionData($aTask, [], $oException);

				} else {
					$bRewriteTask = true;
				}

			} catch (TaskException $oException) {

				$bTypeHandlerExecution = false;
				$bWriteTaskToErrorStack = true;

				$aExceptionData = $oException->getErrorData();

			}


			// Wenn durch einen Fehler eine DB-Transaktion nicht beendet wurde, muss diese jetzt abgebrochen werden
			// Ansonsten kann der Eintrag niemals gelöscht werden oder in den Error-Stack wandern!
			if(!empty(\DB::getLastTransactionPoint())) {
				\DB::rollback(\DB::getLastTransactionPoint());
			}

			// Task in den Error-Stack schreiben, damit dieser manuell abgearbeitet werden kann
			if($bWriteTaskToErrorStack === true) {
				$oRepository->writeTaskToErrorStack($aTask, $aExceptionData);
			}

			// Der Eintrag wird immer aus dem Stack geleert, damit er nicht in eine Endlosschleife kommt		
			$oRepository->deleteStackEntry($aTask['id']);

			if($bRewriteTask === true) {
				// Task erneut in den Stack schreiben; darf hier erst passieren da sonst der Eintrag mit 
				// dem Hash noch existiert
				$oRepository->rewriteTaskToStack($aTask);
				RewriteTask::dispatch($aTask);
			}
			
			if($bTypeHandlerExecution === true) {
				$aReturn['success']++;
			}

			if($bWriteTaskToErrorStack === true) {
				TaskFailed::dispatch($aTask, $aExceptionData);

				if ($oException instanceof Throwable) {
					(new \Core\Exception\ExceptionHandler)->report($oException);
				}
			}

		}

		// TODO Hier fehlen die Ext_Gui2_Index_Stack-Aufrufe. Das sollte am besten selbst übers SequentialProcessing gehen
		SequentialProcessing::execute();
		
		$output->writeln(json_encode($aReturn));

		return Command::SUCCESS;

    }

}