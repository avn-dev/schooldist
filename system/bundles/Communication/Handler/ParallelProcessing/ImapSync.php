<?php

namespace Communication\Handler\ParallelProcessing;
use Core\Handler\ParallelProcessing\TypeHandler;
use Tc\Events\EmailAccountError;

class ImapSync extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('E-Mail-Konto synchronisieren');
	}

	public function execute(array $data, $debug = false)
	{
		$account = \Ext_TC_Communication_Imap::query()->where('imap', 1)->find($data['account_id']);

		if (!$account) {
			return true;
		}

		if ($debug) {
			$account->getImapClient()->getConnection()->enableDebug();
		}

		try {

			$account->checkEmails();

		} catch (\Throwable $e) {

			$account->disconnectImapClient();

			if (null === \WDCache::get('imap_warning_'.$account->id, true)) {
				EmailAccountError::dispatch($account, $e->getMessage());
				// Nicht zuspammen
				\WDCache::set('imap_warning_'.$account->id, 60*60, time(), true);
			}

			throw $e;
		}

		return true;
	}
}