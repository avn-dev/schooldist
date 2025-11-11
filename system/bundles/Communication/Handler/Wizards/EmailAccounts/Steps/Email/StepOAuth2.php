<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email;

use Api\Client\OAuth2\MicrosoftProvider;
use Api\Factory\OAuth2Provider;
use Core\Helper\Routing;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class StepOAuth2 extends Step
{
	public function render(Wizard $wizard, Request $request): Response
	{
		/* @var \Ext_TC_Communication_EmailAccount $account */
		$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')
			->findOrFail($request->get('account_id', 0));

		// imap/smtp
		$type = $this->getConfig('mail_type', 'smtp');

		if ($account->{$type.'_auth'} !== 'oauth2') {
			throw new \RuntimeException('No OAuth2 account');
		}

		$wizard->getSession()->set('oauth2_state', $state = \Util::generateRandomString(30));

		[$providerKey, $providerConfig] = \Api\Helper\MailserverOAuth2::getByHost($account->{$type.'_host'});

		$provider = OAuth2Provider::get($providerKey, $account->getOAuth2ClientAuth());

		$providerUrl = $provider
			->getAuthorizationUrl([
				...['response_type' => 'code', 'state' => $state, 'scope' => Arr::wrap($providerConfig['scopes'] ?? [])],
				...($provider instanceof MicrosoftProvider) ? ['login_hint' =>  $account->{$type.'_user'}] : []
			]);

		$params = http_build_query([
			'forward' => $providerUrl,
			'fidelo_callback' => Routing::generateUrl('Api.api.oauth2.verify', ['provider' => $providerKey])
		]);

		$url = \Util::getProxyHost().'oauth2/forward/'.$providerKey.'?'.$params;

		return $this->view($wizard, '@Communication/wizards/email_accounts/oauth2_step', ['title' => $this->getTitle($wizard).' &raquo; '.$account->email, 'time' => 5, 'redirectUrl' => $url]);
	}

	protected function save(Wizard $wizard, Request $request): ?MessageBag
	{
		if (empty($tokenJson = $request->input('access_data'))) {
			// Hidden Field was nur gesetzt ist wenn der Prozess abgeschlossen wurde
			return new MessageBag([$wizard->translate('Bitte führen Sie den Autorisierungsprozess vollständig durch.')]);
		}

		/* @var \Ext_TC_Communication_EmailAccount $account */
		$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')
			->findOrFail($request->get('account_id', 0));

		$tokenData = json_decode($tokenJson, true);

		$errors = [$wizard->translate('Authentifizierung fehlgeschlagen.')];

		if (is_array($tokenData)) {

			$auth = $account->getOAuth2ClientAuth() ?: OAuth2Provider::getProviderClientAuth($tokenData['provider']);
			$providerConfig = \Api\Helper\MailserverOAuth2::getByProviderKey($tokenData['provider']);

			try {
				$accessToken = \Api\Service\OAuth2\Token::getAccessToken($tokenData['provider'], $tokenData['code'], Arr::wrap($providerConfig['scopes'] ?? []), $auth);
			} catch (\Throwable $e) {
				return new MessageBag([$wizard->translate('Authentifizierung fehlgeschlagen.')]);
			}

			$account->bValidateSettings = false;
			$account->setOAuth2AccessToken($tokenData['provider'], $accessToken, $auth);

			if (true === $errorOrTrue = $account->checkSmtp()) {
				$account->save();
				return parent::save($wizard, $request);
			} else {
				$errors[] = $errorOrTrue;
			}

		}

		return new MessageBag($errors);
	}

}