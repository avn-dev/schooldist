<?php

namespace Admin\Http\Middleware;

use Admin\Enums\ColorScheme;
use Admin\Http\Resources\UserResource;
use Admin\Instance;
use Admin\Traits\WithColorScheme;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleBackendInertiaRequests extends Middleware
{
	use WithColorScheme;

	/**
	 * The root template that's loaded on the first page visit.
	 *
	 * @see https://inertiajs.com/server-side-setup#root-template
	 * @var string
	 */
	protected $rootView = 'app';

	public function __construct(private readonly Instance $admin) {}

	/**
	 * Determines the current asset version.
	 *
	 * @see https://inertiajs.com/asset-versioning
	 * @param  \Illuminate\Http\Request  $request
	 * @return string|null
	 */
	public function version(Request $request): ?string
	{
		return md5(\System::d('version'));
	}

	/**
	 * Defines the props that are shared by default.
	 *
	 * @see https://inertiajs.com/shared-data
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function share(Request $request): array
	{
		$this->admin->boot();

		$user = \Access_Backend::getInstance()->getUser();

		$logos = (new \Admin\Helper\Design)->getLogos();

		$navigationInit = $this->admin->getComponent('navigation')->init($request);

		$version = $server = null;
		if (\Util::isInternEmail($user->email)) {
			$version = 'v'.\System::d('version');
			$server = gethostname();
		}

		return array_merge(parent::share($request), [
			'interface' => [
				'language' => \System::getInterfaceLanguage(),
				// Regelt u.a. ob die Component-Parameter als Header oder als Query-Parameter mitgeschickt werden
				'debug' => \System::d('debugmode') == 2,
				'color_scheme' => $this->getColorScheme($user, ColorScheme::LIGHT),
				'logo' => [
					'framework' => $logos['dark:framework_logo'],
					'framework_small' => $logos['dark:framework_logo_small'],
					'system' => $logos['system_logo'],
					'support' => $logos['support_logo'] ?? null,
				],
				'tenants' => $this->admin->getTenants()?->getOptions()->map(fn ($tenant) => $tenant->toArray()),
				'user' => (new UserResource($user))->toArray($request),
				'navigation' => $navigationInit->getData(),
				'support' => $this->admin->getSupportFeatures(),
				'ping_interval' => 10000, // 10 sek.
				'version' => $version,
				'server' => $server,
				'_l10n' => [
					...[
						'interface.support' => $this->admin->translate('Fidelo Support'),
						'interface.support.chat.online' => $this->admin->translate('Online'),
						'interface.support.chat.offline' => $this->admin->translate('Offline'),
						'interface.support.chat.away' => $this->admin->translate('Abwesend'),
						'interface.loading.status.failed.title' => $this->admin->translate('Ups, das hätte nicht passieren dürfen'),
						'interface.loading.status.failed.text' => $this->admin->translate('Bitte versuchen Sie es später noch einmal oder kontaktieren Sie den Support'),
						'interface.loading.status.unauthorized.title' => $this->admin->translate('Session abgelaufen'),
						'interface.loading.status.unauthorized.text' => $this->admin->translate('Deine Anmeldung ist abgelaufen. Logge dich bitte erneut ein, um fortzufahren.'),
						'interface.loading.status.forbidden.title' => $this->admin->translate('Zugriff verweigert'),
						'interface.loading.status.forbidden.text' => $this->admin->translate('Sie sind nicht dazu berechtigt diese Aktion auszuführen'),
						'interface.bookmark.add' => $this->admin->translate('Zur Schnellauswahl hinzufügen'),
						'interface.bookmark.remove' => $this->admin->translate('Aus Schnellauswahl entfernen'),
						'notifications.important' => $this->admin->translate('Wichtige Meldungen'),
						'notifications.important.previous' => $this->admin->translate('Zurück'),
						'notifications.important.next' => $this->admin->translate('Nächste'),
						'notifications.important.accept' => $this->admin->translate('Verstanden'),
						'notifications.toast.close' => $this->admin->translate('Schließen'),
						'notifications.toast.close_all' => $this->admin->translate('Alle schließen'),
						'notifications.toast.close_group' => $this->admin->translate('Gruppe schließen'),
						'notifications.toast.group' => $this->admin->translate('Nachrichten gruppieren'),
						'notifications.toast.ungroup' => $this->admin->translate('Nachrichten nicht gruppieren'),
						'common.back' => $this->admin->translate('Zurück'),
						'common.cancel' => $this->admin->translate('Abbrechen'),
						'common.close' => $this->admin->translate('Schließen'),
						'common.confirm' => $this->admin->translate('Okay'),
					],
					...$navigationInit->getTranslations()
				]
			]
		]);
	}
}