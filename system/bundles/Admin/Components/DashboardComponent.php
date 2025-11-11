<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Facades\Admin;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router;
use Admin\Helper\Welcome;
use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Core\Enums\AlertLevel;
use Core\Factory\ValidatorFactory;
use Core\Notifications\ToastrNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DashboardComponent implements VueComponent
{
	const KEY = 'dashboard';

	public function __construct(
		private Instance $admin,
		private \Access_Backend $access,
		private Request $request
	) {}

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('Dashboard', '@Admin/components/Dashboard.vue');
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$allWidgets = $this->getWidgets();

		$widgets = $unusedWidgets = [];

		$userSettings = $this->access->getUser()->getMeta('admin.dashboard');

		foreach(['both', 'left', 'right'] as $location) {
			foreach((array)$allWidgets[$location] as $key => $widget) {
				if(!$this->hasAccessToWidget($widget)) {
					continue;
				}

				$startTime = microtime(true);

				$boxObject = \Admin\Helper\Welcome\Box::getInstance($widget);
				$handler = $boxObject->getHandler();

				try {
					if (!empty($component = $boxObject->getComponent())) {
						$type = 'component';
						$content = Router::resolveContent($component, initialize: false);
					} else {
						$type = 'html';
						$content = $boxObject->generateHtml($startTime, false);
					}
				} catch (\Throwable $e) {
					$admin->getLogger('Dashboard')->error('Unable to load widget content', ['widget' => $widget, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
					$content = '';
				}

				if (!empty($content)) {
					$widgetPayload = [
						'key' => $location.'_'.$key,
						'title' => $boxObject->getTitle(),
						'icon' => $boxObject->getIcon(),
						'color' => $boxObject->getColor(),
						'type' => $type,
						'content' => $content,
						'cache_timestamp' => (!empty($cacheTimestamp = $boxObject->getLastChanged())) ? $this->formatDateTime($cacheTimestamp) : null,
						'x' => 1,
						'y' => 1,
						'rows' => $handler->getRows(),
						'cols' => $handler->getCols(),
						'min_rows' => $handler->getMinRows(),
						'min_cols' => $handler->getMinCols(),
						'deletable' => !$widget['show_always'] ?? true,
						'printable' => $boxObject->isPrintable(),
					];

					if (empty($userSettings) && $handler->isDefault()) {
						$widgetPayload['rows'] = $handler->getRows();
						$widgetPayload['cols'] = $handler->getCols();
						$widgets[] = $widgetPayload;
					} else if (!empty($userSettings) && isset($userSettings[$widgetPayload['key']])) {
						$widgetPayload['x'] = $userSettings[$widgetPayload['key']]['x'];
						$widgetPayload['y'] = $userSettings[$widgetPayload['key']]['y'];
						$widgetPayload['rows'] = $userSettings[$widgetPayload['key']]['rows'];
						$widgetPayload['cols'] = $userSettings[$widgetPayload['key']]['cols'];
						$widgets[] = $widgetPayload;
					} else {
						$unusedWidgets[] = $widgetPayload;
					}
				}
			}
		}

		return (new InitialData([
				'version' => 'v'.\System::d('version'),
				'widgets' => array_slice($widgets, 0, 200), // debug
				'unusedWidgets' => $unusedWidgets,
			]))
			->l10n([
				'dashboard.title' => $admin->translate('Dashboard'),
				'dashboard.settings' => $admin->translate('Ansicht konfigurieren'),
				'dashboard.settings.add' => $admin->translate('Widgets hinzufügen'),
				'dashboard.settings.finish' => $admin->translate('Fertig'),
				'dashboard.widgets.add' => $admin->translate('Auswahl hinzufügen'),
				'dashboard.cache_timestamp' => $admin->translate('Stand'),
			]);
	}

	public function reload(Request $request)
	{
		$widget = (string)$request->input('widget');
		[$location, $key] = explode('_', $widget);

		$allWidgets = $this->getWidgets();

		if (
			!isset($allWidgets[$location][$key]) ||
			!$this->hasAccessToWidget($allWidgets[$location][$key])
		) {
			return response('Forbidden', 403);
		}

		$boxObject = \Admin\Helper\Welcome\Box::getInstance($allWidgets[$location][$key]);
		$startTime = microtime(true);

		// TODO das wird an manchen Stellen im $_GET erwartet um die Box zu aktualisieren
		$_GET['refresh_welcome'] = $widget;

		// Achtung, hier kommen nur HTML-Boxen rein, Components haben ihre eigene Logik
		$content = $boxObject->generateHtml($startTime, true);

		return response()
			->json([
				'content' => $content,
				'cache_timestamp' => (!empty($cacheTimestamp = $boxObject->getLastChanged())) ? $this->formatDateTime($cacheTimestamp) : null,
			]);
	}

	public function save(Request $request)
	{
		$layout = $request->input('layout');

		$allWidgets = $this->getWidgets();

		$save = [];
		foreach($layout as $widgetPayload) {
			[$location, $key] = explode('_', $widgetPayload['id']);

			if (
				isset($allWidgets[$location][$key]) &&
				$this->hasAccessToWidget($allWidgets[$location][$key])
			) {
				$validator = (new ValidatorFactory())->make($widgetPayload, [
					'id' => ['required', 'regex:/([a-zA-Z]+)_(\d+)/'],
					'x' => ['required', 'integer', 'between:1,12'],
					'y' => ['required', 'integer', 'between:1,100'],
					'rows' => ['required', 'integer', 'between:1,100'],
					'cols' => ['required', 'integer', 'between:1,12'],
				]);

				if ($validator->passes()) {
					$save[$widgetPayload['id']] = Arr::except($widgetPayload, ['index']);
				} else {
					return InterfaceResponse::json(['success' => false, 'errors' => $validator->errors()])
						->notification(
							(new ToastrNotification($this->admin->translate('Dashboard konnte nicht gespeichert werden'), AlertLevel::DANGER))->persist()
						);
				}
			}
		}

		$this->access->getUser()->setMeta('admin.dashboard', $save)->save();

		return response()->json(['success' => true]);
	}

	private function welcome(): Welcome
	{
		return new \Admin\Helper\Welcome($this->access, $this->request);
		#return \Factory::getObject(Welcome::class);
	}

	/**
	 * @return array
	 */
	private function getWidgets(): array
	{
		return $this->welcome()->getBoxes();
	}

	private function hasAccessToWidget(array $widget)
	{
		if(
			($widget['show_always'] ?? false) ||
			empty($widget['right']) ||
			$this->access->hasRight($widget['right'])
		) {
			return true;
		}

		return false;
	}

	private function formatDateTime(string $datetime): string
	{
		$format = \Factory::getObject(\Ext_Gui2_View_Format_Date_Time::class);
		return $format->formatByValue($datetime);
	}
}