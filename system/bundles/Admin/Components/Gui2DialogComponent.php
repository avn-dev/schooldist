<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Instance;
use Admin\Interfaces\Component\HasParameters;
use Admin\Interfaces\Component\VueComponent;
use Admin\Attributes\Component\Parameter;
use Admin\Traits\Component\WithParameters;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

#[Parameter(name: 'yml_file')]
#[Parameter(name: 'action')]
#[Parameter(name: 'selected_ids')]
#[Parameter(name: 'vars')]
class Gui2DialogComponent implements VueComponent, HasParameters
{
	use WithParameters;

	const KEY = 'gui2_modal_preview';

	private static $gui2Cache = [];

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('Gui2Dialog', '@Admin/components/Gui2Dialog.vue');
	}

	public function rules()
	{
		return [
			'yml_file' => ['required', 'string'],
			'action' => ['required', 'string'],
			'selected_ids' => ['array'],
			'selected_ids.*' => ['int'],
			'vars' => 'array',
		];
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$gui2 = $this->buildGui2();
		$html = $this->generateHtml($gui2);

		return new InitialData([
			'gui2' => ['hash' => $gui2->hash, 'instance_hash' => $gui2->instance_hash],
			'html' => $html,
		]);
	}

	private function buildGui2(): \Ext_Gui2
	{
		global $_VARS;

		$ymlFile = $this->parameters->get('yml_file');
		$vars = $this->parameters->get('vars', []);

		[$fileName, $set] = explode('|', $ymlFile);

		$_VARS = array_merge((array)$_VARS, $vars);

		$cacheKey = md5(json_encode([$ymlFile, ...$_VARS]));

		if (!isset(self::$gui2Cache[$cacheKey])) {
			// \Ext_TC_Gui2::__construct() braucht den Pfad für den Context der Übersetzungen
			$_SERVER['REQUEST_URI'] = '/gui2/page/'.$fileName;

			$gui2 = (new \Ext_Gui2_Factory($fileName))
				->createGui($set);

			self::$gui2Cache[$cacheKey] = $gui2;
		} else {
			$gui2 = self::$gui2Cache[$cacheKey];
		}

		$gui2->setRequest(app(\MVC_Request::class));

		return $gui2;
	}

	private function generateHtml(\Ext_Gui2 $gui2): string
	{
		$bars = $gui2->getBar();

		$selectedIds = $this->parameters->get('selected_ids', []);
		$action = $this->parameters->get('action');

		$icon = null;
		foreach ($bars as $loop) {
			$elements = array_filter($loop->getElements(), fn ($element) => $element instanceof \Ext_Gui2_Bar_Icon);
			if (!empty($found = Arr::first($elements, fn (\Ext_Gui2_Bar_Icon $icon) => $icon->task === 'openDialog' && $icon->action === $action))) {
				$icon = $found;
				break;
			}
		}

		if (!$icon) {
			throw new \RuntimeException(sprintf('Cannot find gui2 icon for action [%s]', $action));
		}

		$params = [
			'task' => 'openDialog',
			'id' => $selectedIds,
			'action' => $icon->action,
			'load_translations' => 1
		];

		if (!empty($icon->additional)) {
			$params['additional'] = $icon->additional;
		}

		$query = http_build_query($params);

		if (!empty($icon->request_data)) {
			$query .= $icon->request_data;
		}

		$gui2->resetOnloadActions();
		$gui2->addOnloadAction("request('&".$query."')");
		$gui2->dialogOnlyMode(true);

		// TODO Das hier wird aktuell nur dafür benötigt um aIconData zu befüllen damit das Dialog-Objekt auch da ist
		$gui2->setTableData('limit', 1);
		$gui2->load_table_bar_data = 1;
		$gui2->load_table_pagination_data = 0;
		$gui2->getTableData(null, null, [], 'list', false);

		ob_start();
		$gui2->display();
		$html = ob_get_clean();

		return $html;
	}
}