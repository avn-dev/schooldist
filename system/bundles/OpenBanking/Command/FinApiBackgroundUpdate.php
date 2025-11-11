<?php

namespace OpenBanking\Command;

use Core\Command\AbstractCommand;
use OpenBanking\OpenBanking;
use OpenBanking\Providers\finAPI\Api\Models\BankConnection;
use OpenBanking\Providers\finAPI\ExternalApp;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use TcExternalApps\Service\AppService;
use Ts\Service\OpenBanking\IncomingPayments;

class FinApiBackgroundUpdate extends AbstractCommand
{
	protected function configure() {
		$this->setName("open-banking:finapi:update")
			->setDescription("Trigger finApi background update (import new transactions)");
	}

	private function logger(): LoggerInterface
	{
		return OpenBanking::logger('finAPI');
	}

	public function handle() {

		if (!AppService::hasApp(\OpenBanking\Providers\finAPI\ExternalApp::APP_KEY)) {
			$this->components->info('App not installed');
			return Command::SUCCESS;
		}

		$savedExecutionTimes = ExternalApp::getExecutionTimes();
		$now = \Carbon\Carbon::now();

		$user = \OpenBanking\Providers\finAPI\ExternalApp::getUser();

		$accountIds = ExternalApp::getAccountIds();
		$bankConnections = \OpenBanking\Providers\finAPI\DefaultApi::default()
			->getAllBankConnections($user);

		$accounts = [];
		foreach ($bankConnections as $bankConnection) {
			/* @var BankConnection $bankConnection */

			$executionTimes = $savedExecutionTimes[$bankConnection->getId()] ?? [];

			if (!empty($executionTimes) && !in_array($now->hour, $executionTimes)) {
				$this->components->info(sprintf('Skip connection due execution times [%s -> %s]', $bankConnection->getId(), implode(', ', $executionTimes)));
				continue;
			}

			if (empty(array_intersect_key($accountIds, $bankConnection->getAccountIds()))) {
				$this->components->info(sprintf('Connection is not used [%s]', $bankConnection->getId()));
				continue;
			}

			$this->logger()->info('Bank connection', ['status' => $bankConnection->getStatus(), 'accountIds' => $bankConnection->getAccountIds()]);

			if ($bankConnection->isReady()) {

				$success = \OpenBanking\Providers\finAPI\DefaultApi::default()
					->backgroundUpdate($user, $bankConnection);

				if ($success) {
					$this->components->info(sprintf('Background task generated [%s -> %s]', $bankConnection->getId(), implode(', ', $bankConnection->getAccountIds())));
					$this->logger()->info('Background task generated', ['accountIds' => $bankConnection->getAccountIds()]);

					$accounts = array_merge($accounts, $bankConnection->getAccountIds());

				} else {
					$this->logger()->error('Generating of background task failed', ['accountIds' => $bankConnection->getAccountIds()]);
					$this->components->error(sprintf('Generating of background task failed [%s]', $bankConnection->getId()));
				}

			} else {
				// TODO Hier kann es sein, dass das Webform je nach Konto erneut ausgeführt werden muss. Hier muss ein Event geschmissen werden
				$this->logger()->warning('Generating of background task not possible', ['accountIds' => $bankConnection->getAccountIds(), 'status' => $bankConnection->getStatus()]);
				$this->components->warn(sprintf('Generating of background task not possible [status: %s]', $bankConnection->getStatus()));
			}
		}

		$this->components->info(sprintf('Triggered %d accounts [%s]', count($accounts), implode(', ', $accounts)));

		return Command::SUCCESS;
	}

}