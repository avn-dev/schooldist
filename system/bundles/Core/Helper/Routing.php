<?php

namespace Core\Helper;

use Core\Service\RoutingService;
use Symfony\Component\HttpFoundation\Request;

class Routing {
	
	/**
	 * @param string $sName
	 * @param array $aParameters
	 * @param Request $oRequest
	 * @return string
	 */
	public static function generateUrl($sName, array $aParameters=[], Request $oRequest = null) {
		$oRoutingService = new RoutingService();
		return $oRoutingService->generateUrl($sName, $aParameters, $oRequest);
	}
	
}
