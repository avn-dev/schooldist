<?php

namespace Core\Exception;

use Core\Entity\ParallelProcessing\Stack;
use Core\Enums\ErrorLevel;
use Core\Exception\ParallelProcessing\TaskException;
use Core\Facade\Cache;
use Illuminate\Console\View\Components\BulletList;
use Illuminate\Console\View\Components\Error;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Throwable;

/**
 * TODO weiter ausbauen (siehe \Illuminate\Foundation\Exceptions\Handler)
 */
class ExceptionHandler implements ExceptionHandlerContract
{
    public function report(Throwable $e): void
    {
		if (!$e instanceof ReportErrorException) {
			\Log::getLogger()->error('Throwable', ['class' => $e::class, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			return;
		}

		try {
			$this->reportErrorReport($e);
		} catch (\Throwable $e) {
			\Log::getLogger()->error('Reporting error failed', ['class' => $e::class, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
		}
    }

    public function shouldReport(Throwable $e): void
    {
		// TODO
        \Log::getLogger()->error('Throwable', ['class' => $e::class, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    }

    public function render($request, Throwable $e): void
    {
		// TODO
        \Log::getLogger()->error('Throwable', ['class' => $e::class, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    }

    public function renderForConsole($output, Throwable $e): void
    {
        if ($e instanceof CommandNotFoundException) {
            $message = str($e->getMessage())->explode('.')->first();

            if (! empty($alternatives = $e->getAlternatives())) {
                $message .= '. Did you mean one of these?';

                with(new Error($output))->render($message);
                with(new BulletList($output))->render($e->getAlternatives());

                $output->writeln('');
            } else {
                with(new Error($output))->render($message);
            }

            return;
        }

        (new ConsoleApplication)->renderThrowable($e, $output);
    }

	private function reportErrorReport(ReportErrorException $exception): void
	{
		$noOfficeReports = false;
		if ($exception instanceof TaskException) {
			$task = $exception->getTask();
			if ($task['type'] == 'core/logging-handler') {
				$noOfficeReports = true;
			}
		}
		switch ($exception->getErrorLevel()) {
			case ErrorLevel::EMERGENCY:
			case ErrorLevel::CRITICAL:
				$this->reportToSystemEmail($exception);
			case ErrorLevel::ERROR:
			case ErrorLevel::WARNING:
				if (
					\System::d('report_error') &&
					\System::d('error_logging') == 'logging_server' &&
					!$noOfficeReports
				) {
					$this->reportToOfficeLog($exception);
				}
				if (
					\System::d('report_error') &&
					\System::d('error_logging') == 'apache'
				) {
					$this->reportToServerLog($exception);
				}
			default:
				$this->reportToLogger($exception);
		}
	}

	/**
	 * @param ReportErrorException $exception
	 * @return void
	 * @throws \Exception
	 */
	private function reportToOfficeLog(ReportErrorException $exception): void
	{
		$data = [
			'subject' => 'Error Level "'.$exception->getErrorLevel()->value.'" in '.\Util::getSystemHost(),
			'message' => $exception->getMessage().' >> '.print_r($exception->getAdditionalData(),1),
			'error_level' => $exception->getErrorLevel()->value
		];
		$oStackRepository = Stack::getRepository();
		$oStackRepository->writeToStack('core/logging-handler', $data, $exception->getErrorLevel()->getParallelProcessingPriority());
	}

	/**
	 * @param ReportErrorException $exception
	 * @return void
	 */
	private function reportToServerLog(ReportErrorException $exception): void
	{
		$sErrorLog = preg_replace('/(\s+|)(\n)(\s+|)/', '|', print_r($exception->getAdditionalData(),1));
		if(strlen($sErrorLog) > ini_get('log_errors_max_len')) {
			$sErrorLog = substr($sErrorLog, 0, ini_get('log_errors_max_len'));
		}
		error_log($sErrorLog);
	}

	/**
	 * @param ReportErrorException $exception
	 * @return void
	 */
	private function reportToSystemEmail(ReportErrorException $exception): void
	{
		$data = [
			'subject' => 'Error Level "'.$exception->getErrorLevel()->value.'" in '.\Util::getSystemHost(),
			'message' => $exception->getMessage().' >> '.print_r($exception->getAdditionalData(),1)
		];
		// Nach 5 Mails mit gleichem Betreff oder bei fehlende E-Mail-Adresse keine Mails mehr schicken
		$cacheKey = __METHOD__.'_'.md5($data['subject']);
		$mailCount = (int)Cache::get($cacheKey);
		if ($mailCount >= 5) {
			return;
		}

		// Betreff für 15 Minuten im Cache halten
		Cache::put($cacheKey, 60*15, ++$mailCount);

		$mail = new \WDMail();
		$mail->subject = $data['subject'];
		$mail->html = $data['message'];
		$mail->priority = $exception->getErrorLevel()->getEmailPriority();
		$mail->send(\System::getErrorEmail());
	}

	/**
	 * @param ReportErrorException $exception
	 * @return void
	 */
	private function reportToLogger(ReportErrorException $exception): void
	{
		\Log::getLogger()->error('Error Level '.$exception->getErrorLevel()->value, [
			'message' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'additionalData' => $exception->getAdditionalData()
		]);
	}
}