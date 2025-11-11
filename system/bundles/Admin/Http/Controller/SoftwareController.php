<?php

namespace Admin\Http\Controller;

use Admin\Dto\Component\InitialData;
use Admin\Dto\TenantDto;
use Admin\Facades\Admin;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router;
use Admin\Http\Resources\UserNotificationResource;
use Admin\Instance;
use Admin\Traits\SystemButtons;
use Core\Enums\AlertLevel;
use Core\Facade\Cache;
use Core\Notifications\Channels\DatabaseChannel;
use Core\Notifications\ToastrNotification;
use Core\Service\RoutingService;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application as Laravel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class SoftwareController extends Controller
{
	public function credits()
	{
		global $db_data;

		$oComposerCredits = new \Core\Helper\Composer\Credits;

		$sCredits = $oComposerCredits->getCredits();
		$sVersion = \System::d('version');
		$sPHPVersion = phpversion();
		$databaseName = $db_data['system'];

		$sLaravelVersion = null;
		if (class_exists(Laravel::class)) {
			$sLaravelVersion = Laravel::VERSION;
		}

		$sElasticSearchVersion = null;
		try {

			// Erweiterung aktiv?
			$oElasticAdapter = \Core\Entity\System\Elements::getRepository()->findOneBy(['file'=>'elasticaadapter']);

			if(
				$oElasticAdapter !== null &&
				$oElasticAdapter->active == 1
			) {
				$oClient = new \ElasticaAdapter\Adapter\Client();
				$sElasticSearchVersion = sprintf("%01.1F", $oClient->getElasticsearchVersion());
			}

		} catch (\Throwable $e) {
			// Keine Meldung weil Elastic nicht immer verfügbar ist.
		}

		$sHost = gethostname();
		$key = 'credits';
		$title = Admin::translate('Credits');

		return response()
			->view('credits', compact(['key', 'title', 'sCredits', 'sVersion', 'sPHPVersion', 'databaseName', 'sLaravelVersion', 'sElasticSearchVersion', 'sHost']));
	}

	public function phpinfo()
	{
		$key = 'phpinfo';
		$title = Admin::translate('PHP-Info');

		ob_start();
		phpinfo(INFO_ALL);
		$phpInfo = ob_get_clean();

		$phpInfo = preg_replace('/<!DOCTYPE[^>]+>/', '', $phpInfo);
		$phpInfo = strip_tags($phpInfo, '<html><head><body><div><table><tr><th><td><h1><h2><p><br><br/>');
		$phpInfo = \Admin_Html::stripTagsContent($phpInfo, '<html><body><div><table><tr><th><td><h1><h2><p><br><br/>');
		$phpInfo = strip_tags($phpInfo, '<table><tr><th><td><h1><h2><p><br><br/>');
		$phpInfo = trim($phpInfo);
		$phpInfo = str_replace('<table>', '<table class="table table-striped">', $phpInfo);

		return response()
			->view('phpinfo', compact(['key', 'title', 'phpInfo']));
	}
}