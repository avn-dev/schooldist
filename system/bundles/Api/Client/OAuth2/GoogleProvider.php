<?php

namespace Api\Client\OAuth2;

use League\OAuth2\Client\Provider\Google;

/**
 * Ableitung wegen 'approval_prompt'. Ansonsten wird kein Refresh-Token generiert
 */
class GoogleProvider extends Google
{
	protected $approvalPrompt;

	protected function getScopeSeparator(): string
	{
		return ' ';
	}

	public function getAuthorizationUrl(array $options = [])
	{
		if ($this->approvalPrompt && !isset($options['approval_prompt'])) {
			$options['approval_prompt'] = 'force'; // Refresh-Token
		}
		return parent::getAuthorizationUrl($options);
	}
}