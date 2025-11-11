<?php

namespace OpenBanking\Providers\finAPI;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\Api\Models\Webform;

class ExternalApp extends \TcExternalApps\Interfaces\ExternalApp
{
	const APP_KEY = 'finAPI';

	public function getTitle(): string
	{
		return self::APP_KEY;
	}

	public function getIcon() {
		return 'fa fa-university';
	}

	public function getCategory(): string {
		return \TcExternalApps\Interfaces\ExternalApp::CATEGORY_ACCOUNTING;
	}

	public function getDescription(): string
	{
		return $this->t('finAPI - Beschreibung');
	}

	public function install()
	{
		if (!empty(\System::d('finapi_username'))) {
			return;
		}

		$username = str_replace(['https://', 'http://', '.fidelo.com'], '', \System::d('domain'));
		$password = \Util::generateRandomString(30, ['with_specials' => true]);

		$user = DefaultApi::default()
			->createUser($username, $password);

		\System::s('finapi_username', $user->getUsername());
		\System::s('finapi_password', \Illuminate\Support\Facades\Crypt::encrypt($user->getPassword()));
	}

	public function uninstall()
	{
		if (empty($user = self::getUser())) {
			return;
		}

		DefaultApi::default()->deleteUser($user);

		\System::s('finapi_username', '');
		\System::s('finapi_password', '');
	}

	public function getContent(): ?string {
		$smarty = new \SmartyWrapper();
		$smarty->assign('props', [
			'l10n' => Arr::dot([
				'headings' => [
					'bank_connection' => $this->t('Bankverbindung'),
					'accounts' => $this->t('Ihre verknüpften Konten')
				],
				'text' => [
					'description' => $this->t('Sobald Sie eine Bankverbindung erfolgreich hinzugefügt haben können Sie im unteren Bereich die Bankkonten auswählen die für die Synchronisierung benutzt werden sollen.'),
					'no_accounts' => $this->t('Keine Bankkonten verknüpft'),
					'payment_method' => $this->t('Bezahlmethode'),
					'execution_time' => $this->t('Ausführungszeitpunkt hinzufügen'),
					'execution_time_manual' => $this->t('Aktualisierung'),
					'execution_time_hourly' => $this->t('Wird stündlich aktualisiert'),
					'confirm_delete' => $this->t('Möchten Sie das Konto wirklich löschen'),
				],
				'btn' => [
					'new_connection' => $this->t('Neue Bankverbindung hinzufügen'),
					'load' => $this->t('Bankkonten aktualisieren')
				]
			])
		]);

		return $smarty->fetch('@OpenBanking/external_apps/finAPI.tpl');
	}

	public static function getUser(): ?User
	{
		if (empty(\System::d('finapi_username'))) {
			return null;
		}

		return new User(
			\System::d('finapi_username'),
			\Illuminate\Support\Facades\Crypt::decrypt(\System::d('finapi_password')),
		);
	}

	public static function getAccountIds(): array
	{
		return Arr::wrap(
			json_decode(\System::d('finapi_accounts', ''), true)
		);
	}

	public static function saveAccountIds(array $accountIds)
	{
		\System::s('finapi_accounts', json_encode(
			array_values(
				array_unique($accountIds)
			)
		));
	}

	public static function getPaymentMethodsIds(): array
	{
		return Arr::wrap(
			json_decode(\System::d('finapi_account_payment_methods', ''), true)
		);
	}

	public static function savePaymentMethodIds(array $paymentMethodsIds)
	{
		\System::s('finapi_account_payment_methods', json_encode($paymentMethodsIds));
	}

	public static function getExecutionTimes(): array
	{
		return Arr::wrap(
			json_decode(\System::d('finapi_connection_execution_times', ''), true)
		);
	}

	public static function saveExecutionTimes(array $times)
	{
		\System::s('finapi_connection_execution_times', json_encode($times));
	}

	public static function addOpenWebform(Webform $webform)
	{
		$expired = Carbon::now()->addDay()->getTimestamp();

		$openWebforms = self::getOpenWebformIds();
		$openWebforms[] = $expired.'|'.$webform->getId();
		self::saveOpenWebforms($openWebforms);
	}

	public static function getOpenWebformIds(): array
	{
		$webforms = array_filter(
			Arr::wrap(json_decode(\System::d('finapi_app_open_webforms', ''), true)),
			function (string $key) {
				[$expired, $webformId] = explode('|', $key);
				return Carbon::createFromTimestamp($expired) >= Carbon::now();
			}
		);

		self::saveOpenWebforms($webforms);

		return $webforms;
	}

	public static function deleteOpenWebform(string $id): array
	{
		$openWebforms = array_filter(self::getOpenWebformIds(), fn ($webformId) => !str_contains($webformId, '|'.$id));
		self::saveOpenWebforms($openWebforms);
	}

	private static function saveOpenWebforms(array $webformIds)
	{
		\System::s('finapi_app_open_webforms', json_encode(
			array_values(
				array_unique($webformIds)
			)
		));
	}
}