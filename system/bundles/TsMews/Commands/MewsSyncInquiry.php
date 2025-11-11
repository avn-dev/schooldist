<?php

namespace TsMews\Commands;

use Carbon\Carbon;
use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsMews\Hook\CronjobHook;
use TsMews\Service\Synchronization;

class MewsSyncInquiry extends AbstractCommand {

    protected function configure() {   

        $this->setName("mews:sync:inquiry")
			->addArgument('inquiry', InputArgument::REQUIRED, 'Inquiry ID')
			->addArgument('without-ids', InputArgument::OPTIONAL, 'Sync inquiry as new entry', false)
			->setDescription("Sync Mews reservations");

    }

	/**
	 * Gibt den Stack als JSON aus
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);

		/* @var \Ext_TS_Inquiry $inquiry */
		$inquiry = \Ext_TS_Inquiry::query()->find($input->getArgument('inquiry'));
		$reset = (bool)$input->getArgument('without-ids');

		if (!$inquiry) {
			$this->error('Inquiry not found!');
			return Command::INVALID;
		}

		$this->info(sprintf('Start syncing inquiry "%s" to Mews', $inquiry->id));

		if (empty($allocations = $inquiry->getAllocations())) {
			$this->warn('No accommodation allocations found');
			return Command::FAILURE;
		}

		$traveller = $inquiry->getTraveller();

		if ($reset) {
			// Werden über die Allocations synchronisiert
			$traveller->unsetMeta('mews_id');
		} else {
			// Ansonsten einzeln synchronisiert
			Synchronization::syncCustomerToMews($traveller);
		}

		foreach ($allocations as $allocation) {

			if ($reset) {
				$allocation->unsetMeta('mews_id');
			}

			$this->info(sprintf('Syncing accommodation allocation "%d - %s - %s" to Mews', $allocation->id, Carbon::parse($allocation->from)->toDateString(), Carbon::parse($allocation->until)->toDateString()));
			Synchronization::syncAllocationToMews($allocation);
		}

        $this->info('...finished!');

        return Command::SUCCESS;
    }
	
}
