<?php

use Admin\Facades\Router;
use Admin\Interfaces\RouterAction;

class Checks_PrepareInterfaceV3 extends \GlobalChecks
{
	public function getTitle()
	{
		return 'Interface v3.0';
	}

	public function getDescription()
	{
		return 'Prepares structure for new interface';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$backup = [
			\Util::backupTable('wdbasic_attributes')
		];

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {
			// USER TABS
			$this->prepareUserTabs();

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

	private function prepareUserTabs()
	{
		/* @var \User[] $users */
		$users = \DB::getQueryData("SELECT `id`, `additional` FROM `system_user`");

		$attributes = \DB::getQueryPairs("SELECT `entity_id`, GROUP_CONCAT(`key`) `keys` FROM `wdbasic_attributes` WHERE `entity` = 'system_user' GROUP BY `entity_id`");

		$container = \Illuminate\Container\Container::getInstance();
		$container->instance(\Access_Backend::class, \Access_Backend::getInstance());

		/* @var \Admin\Instance $admin */
		$admin = $container->make(\Admin\Instance::class);
		$navigation = new \Admin\Components\NavigationComponent($admin, null, \Illuminate\Http\Request::capture());

		$structure = array_filter($navigation->buildJsStructure(withOriginalNodes: true), fn ($node) => !empty($node['action']));

		foreach ($users as $user) {
			$additional = json_decode((string)$user['additional'], true);

			if (
				!is_array($additional) ||
				empty($tabs = $additional['admin_tabs']) ||
				(isset($attributes[$user['id']]) && str_contains($attributes[$user['id']], '.tabs'))
			) {
				continue;
			}

			$newTabs = [];
			foreach ($tabs as $tab) {

				if ($tab['value'] === '/admin/dashboard') {
					$node = \Illuminate\Support\Arr::first($structure, fn ($node) => $node['original'][1] === 'Admin2\Components\Dashboard');
				} else {
					$node = \Illuminate\Support\Arr::first($structure, fn ($node) => $node['original'][1] === $tab['value']);
				}

				if ($node) {
					if (
						$node['action'] instanceof RouterAction\StorableRouterAction &&
						$node['action']->isStorable()
					) {
						$newTabs[] = Router::toStoreData($admin, $node['action']);
					}
				}
			}

			if (!empty($newTabs)) {
				\DB::insertData('wdbasic_attributes', [
					'entity' => 'system_user',
					'entity_id' => $user['id'],
					'key' => 'admin.tabs',
					'value' => json_encode($newTabs)
				]);
			}
		}
	}

}