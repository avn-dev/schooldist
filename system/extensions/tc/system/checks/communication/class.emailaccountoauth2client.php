<?php

class Ext_TC_System_Checks_Communication_EmailAccountOAuth2Client extends GlobalChecks
{
	public function getTitle()
	{
		return 'E-mail Account';
	}

	public function getDescription()
	{
		return 'Update e-mail account settings for oauth2 process';
	}

	public function executeCheck()
	{
		set_time_limit(120);
		ini_set("memory_limit", '1024M');

		$accounts = \Ext_TC_Communication_EmailAccount::query()
			->whereNotNull('oauth2_data')
			->get();

		if ($accounts->isEmpty()) {
			return true;
		}

		if(!\Util::backupTable('tc_communication_emailaccounts')) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			foreach ($accounts as $account) {
				/* @var \Ext_TC_Communication_EmailAccount $account */

				$oauth2Data = $account->getOAuth2Data();

				if (!empty($oauth2Data) && (empty($oauth2Data['client_id']) || empty($oauth2Data['client_secret']))) {

					$token = new \League\OAuth2\Client\Token\AccessToken($oauth2Data);
					$auth = \Api\Factory\OAuth2Provider::getProviderClientAuth($account->oauth2_provider);

					$account->setOAuth2AccessToken($account->oauth2_provider, $token, $auth);

					$account->save();
				}

			}

		} catch (\Exception $ex) {
			\DB::rollback(__METHOD__);
			__pout($ex);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

}