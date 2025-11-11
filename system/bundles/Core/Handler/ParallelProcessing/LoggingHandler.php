<?php

namespace Core\Handler\ParallelProcessing;

class LoggingHandler extends TypeHandler
{
	public function execute(array $data, $debug = false): bool
	{

		$bSuccess = false;
		
		try {
			
			$oApi = new \Licence\Service\Office\Api();
			$bSuccess = $oApi->addLog(
				$data['subject'],
				$data['message'],
				$data['error_level'] ?? \Core\Enums\ErrorLevel::INFO->value
			);

		} catch (\Throwable $ex) {}

		return $bSuccess;
	}

	public function getLabel() {
		return \L10N::t('Logging');
	}

}