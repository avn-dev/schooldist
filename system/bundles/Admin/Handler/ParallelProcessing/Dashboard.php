<?php

namespace Admin\Handler\ParallelProcessing;

use Admin\Instance;
use Core\Handler\ParallelProcessing\TypeHandler;
use Illuminate\Http\Request;

class Dashboard extends TypeHandler {

    /**
	 *  
     * @param  array $aData
     * @param bool $bDebug
     * @return bool
     */
	public function execute(array $aData, $bDebug = false) {

		app()->instance(\Access_Backend::class, new \Access_Backend(\DB::getDefaultConnection()));
		app()->instance('request', $request = new Request());

		$admin = app()->make(Instance::class);

		$oWelcome = \Factory::getObject('\Admin\Helper\Welcome');
		$oWelcome->updateCache($admin, $request);

		return true;
	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Dashboard-Aktualisierung', 'Framework');
	}

}