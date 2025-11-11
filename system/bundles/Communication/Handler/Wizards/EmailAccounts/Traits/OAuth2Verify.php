<?php

namespace Communication\Handler\Wizards\EmailAccounts\Traits;

trait OAuth2Verify
{
	protected function needsNewToken(\Ext_TC_Communication_EmailAccount $account): bool
	{
		if (
			empty($accessToken = $account->getOAuth2AccessToken()) ||
			$accessToken->hasExpired()
		) {
			return true;
		}

		return false;
	}
}