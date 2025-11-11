<?php

namespace Ts\Command\OpenBanking;

use Core\Command\AbstractCommand;
use Ts\Service\OpenBanking\IncomingPayments;

class IncominPayments extends AbstractCommand
{
	protected function configure() {
		$this->setName("ts:open-banking:sync")
			->setDescription("Sync incoming payments from linked bank accounts");
	}

	public function handle() {

		$this->components->info('Start syncing incoming transactions');

		$transaction = IncomingPayments::run();

		$this->components->info(sprintf('Synced %d transactions', $transaction->count()));

	}
}