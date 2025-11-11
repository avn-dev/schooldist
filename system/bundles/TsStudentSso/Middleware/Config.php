<?php

namespace TsStudentSso\Middleware;

use Closure;
use Illuminate\Support\Arr;

class Config {

	/**
	 * Die App braucht immer eine Response mit CORS-Headers, ansonsten kommt nur der Status = 0 an
	 *
	 * @param Illuminate\Http\Request $request
	 * @param Closure $next
	 * @return Illuminate\Http\Response
	 */
	public function handle($request, Closure $next) {

		\System::setInterfaceLanguage('en');
		
		$settingsHelper = new \TsStudentSso\Helper\Settings;
		$settings = $settingsHelper->get();
		
		$settingsDot = Arr::dot($settings);
		
		foreach($settingsDot as $settingKey=>$settingValue) {
			config(['samlidp.'.$settingKey => $settingValue]);
		}
		
		return $next($request);
	}

	/**
	 * @return LoggingService
	 */
	private function getLogger(): LoggingService {
		return app()->make(LoggingService::class);
	}

}
