<?php

namespace AdminTools\Helper;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class Util
{
	public static function setDebugMode(string $ip, int $mode)
	{
		global $system_data;

		\WDCache::set('system_debugmode_' . $ip, 3600 * 24, $mode, true);

		$system_data['debugmode'] = $mode;
	}

	public static function setDebugIP(string $ip)
	{
		$ips = \WDCache::get('system_debug_ips', true) ?? [];
		$ips[] = $ip;

		\WDCache::set('system_debug_ips', 3600 * 24, $ips, true);
	}

	public static function removeDebugIP(string $ip)
	{
		$ips = \WDCache::get('system_debug_ips', true) ?? [];
		$ips = array_filter($ips, fn ($loop) => $loop !== $ip);

		\WDCache::set('system_debug_ips', 3600 * 24, $ips, true);
	}

	public static function handleButton(string $button)
	{
		match ($button) {
			'clear_cache' => (new \Core\Helper\Cache())->clearAll(),
			'refresh_routing' => (new \Core\Service\RoutingService())->buildRoutes(),
			'refresh_bundles' => \Core\Facade\Cache::forget('core_system_elements'),
			'refresh_db_functions' => \Factory::executeStatic('Ext_TC_Db_StoredFunctions', 'updateStoredFunctions'),
			default => throw new \RuntimeException('Unknown button')
		};
	}

	public static function handleAction(string $action, string $value): ?string
	{
		if (empty($action)) {
			return null;
		}

		try {
			$toolsService = \Ext_TC_System_Tools::getToolsService();
			$result = $toolsService->executeIdAction($action, compact('value'));
		} catch (\Throwable $e) {
			$result = $e;
		}

		$cloner = new VarCloner();
		$output = fopen('php://memory', 'r+b');
		$dumper = new HtmlDumper($output);
		$dumper->setTheme('light');
		$dumper->dump($cloner->cloneVar($result ?? null));

		return stream_get_contents($output, -1, 0);
	}

	public static function handleIndex(string $index, string $action): void
	{
		if (empty($index) || empty($action)) {
			return;
		}

		$toolsService = \Ext_TC_System_Tools::getToolsService();

		$data = [
			'index_name' => $index,
			'fill_stack' => true
		];

		match ($action) {
			'reset' => $toolsService->executeIndexReset($data),
			'reset_no_stack' => $toolsService->executeIndexReset([...$data, 'fill_stack' => false]),
			'refresh' => $toolsService->executeFillStack($data),
		};
	}
}
