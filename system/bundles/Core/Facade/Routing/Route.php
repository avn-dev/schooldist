<?php

namespace Core\Facade\Routing;

/**
 * @deprecated use Illuminate\Support\Facades\Route;
 * @mixin \Illuminate\Support\Facades\Route
 */
class Route extends \Illuminate\Support\Facades\Route {
	
/*	private static $oInstance = null;

	public static function getInstance() {
		if(self::$oInstance === null) {			
			$oEvents = new \Illuminate\Events\Dispatcher();
		
			$oRouter = new \Illuminate\Routing\Router($oEvents);
			
			self::$oInstance = $oRouter;
		}
		
		return self::$oInstance;
	}

	public static function __callStatic($sFunction, $aArguments) {
		return call_user_func_array([self::getInstance(), $sFunction], $aArguments);
	}*/
	
}
