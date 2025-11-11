<?php

namespace Api\Client\OAuth2;

use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

/**
 * Ableitung wegen Scope-Separator
 * Achtung die Scopes scheinen auch nicht mehr zu stimmen
 */
class MicrosoftProvider extends Microsoft
{
	protected $approvalPrompt;

	protected function getScopeSeparator()
	{
		return ' ';
	}

	public function getAuthorizationUrl(array $options = [])
	{
		$options['prompt'] = 'login'; // Immer neu einloggen
		return parent::getAuthorizationUrl($options);
	}

}