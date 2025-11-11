<?php

namespace Communication\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImapSync extends AbstractCommand {

    protected function configure() {

        $this->setName("communication:imap:sync")
            ->setDescription("Sync incoming emails from imap accounts")
            ->addOption('account_id', null, InputOption::VALUE_OPTIONAL, 'Define email account')
        ;

    }

    /**
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{

        $this->_setDebug($output);

        $specificAccount = $input->getOption('account_id');

        // Imap Konten ermitteln
        if ($specificAccount !== null) {
            $account = \Ext_TC_Communication_Imap::query()->where('imap', 1)->findOrFail($specificAccount);
            $accounts = collect([$account]);
        } else {
            $accounts = \Ext_TC_Communication_Imap::getAccounts();
        }

        foreach ($accounts as $index => $account) {
			/* @var \Ext_TC_Communication_Imap $account*/

            if($output->isDebug()) {
                $account->getImapClient()->getConnection()->enableDebug();
            }

            $this->components->info(sprintf('Start syncing emails for account "%s (ID: %d)" (%d/%d)', $account->email, $account->id, ($index + 1), $accounts->count()));

            try {
                [$loaded, $synced, $failed] = $account->checkEmails();
                $this->components->info(sprintf('Loaded %d / Synced %d / Failed %d', $loaded, $synced, $failed));
            } catch (\Throwable $e) {

                $account->disconnectImapClient();

                $this->components->error(sprintf('Failed with exception: %s [%s(%s)])', $e->getMessage(), $e->getFile(), $e->getLine()));
            }
        }

        $this->components->info('Finished');

        return Command::SUCCESS;
    }

}