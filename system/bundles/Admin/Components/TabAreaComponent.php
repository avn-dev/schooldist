<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Facades\Admin;
use Admin\Facades\Router;
use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Interfaces\RouterAction;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TabAreaComponent implements Component
{
	const KEY = 'tabArea';
	const DEFAULT_MAX_TABS = 20;

	public function __construct(
		private \Access_Backend $access,
		private Instance $admin
	) {}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(): InitialData
	{
		/* @var \User $user */
		$user = $this->access->getUser();

		$allowSaving = false;

		if(!empty(\System::d('system_update_locked_by'))) {
			$updateAction = Router::openSystemUpdate();
			$tabs = [$updateAction];
		} else {
			$tabs = array_map(fn ($payload) => Router::fromStoreData($this->admin, $payload), (array)$user->getMeta('admin.tabs'));
			$allowSaving = true;
		}

		$finalTabs = array_filter($tabs, fn ($routerAction) => $routerAction instanceof RouterAction && $routerAction->getTarget()->isTab());

		if (empty($finalTabs)) {
			// Falls keine Tabs da sind immer das Dashboard laden
			$finalTabs[] = Router::openDashboard();
		}

		return (new InitialData([
				'allow_saving' => $allowSaving,
				'max_tabs' => \System::d('admin.max_tabs', self::DEFAULT_MAX_TABS),
				'tabs' => array_values($finalTabs)
			]))
			->l10n([
				'tabs.too_many.title' => $this->admin->translate('Zu viele Tabs offen'),
				'tabs.too_many.text' => $this->admin->translate('Sie haben zu viele Tabs auf einmal geöffnet. Bitte schließen Sie erst Tabs und öffnen dann wieder neue.'),
				'tabs.context.add_bookmark' => $this->admin->translate('Tab zur Schnellauswahl hinzufügen'),
				'tabs.context.remove_bookmark' => $this->admin->translate('Tab aus Schnellauswahl entfernen'),
				'tabs.context.refresh' => $this->admin->translate('Tab neu laden'),
				'tabs.context.clone' => $this->admin->translate('Tab duplizieren'),
				'tabs.context.close' => $this->admin->translate('Tab schließen'),
				'tabs.context.close_tabs_before' => $this->admin->translate('Tabs links schließen'),
				'tabs.context.close_tabs_after' => $this->admin->translate('Tabs rechts schließen'),
				'tabs.context.close_other_tabs' => $this->admin->translate('Andere Tabs schließen'),
				'tabs.context.save_tabs' => $this->admin->translate('Tabs speichern'),
				'tabs.existing_tab' => $this->admin->translate('Diese Ansicht ist bereits geöffnet'),
				'tabs.existing.text' => $this->admin->translate('Möchten Sie die Ansicht in einem neuen Tab öffnen, oder die vorhandene Ansicht anzeigen?'),
				'tabs.existing.open_new' => $this->admin->translate('Neuen Tab öffnen'),
				'tabs.existing.reload_existing' => $this->admin->translate('Vorhandenen Tab neu laden'),
				'tabs.existing.open_existing' => $this->admin->translate('Vorhandenen Tab öffnen'),
			]);
	}

	public function save(Request $request, Instance $admin)
	{
		$tabs = $request->input('tabs');

		if (empty($tabs)) {
			return response('Bad request', Response::HTTP_BAD_REQUEST);
		}

		/* @var \User $user */
		$user = $this->access->getUser();

		$storeData = [];
		foreach ($tabs as $tabPayload) {
			$routerAction = Router::fromStoreData($admin, $tabPayload);
			if ($routerAction instanceof StorableRouterAction) {
				$storeData[] = $tabPayload;
			}
		}

		$user->setMeta('admin.tabs', $storeData)->save();

		return response()->json(['success' => true]);
	}

}