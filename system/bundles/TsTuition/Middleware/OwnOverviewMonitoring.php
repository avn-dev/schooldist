<?php

namespace TsTuition\Middleware;

use Illuminate\Http\Request;

class OwnOverviewMonitoring
{

	/**
	 * @param \MVC_Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\Response
	 */
	public function handle(\MVC_Request $request, \Closure $next)
	{
		$data = array(
			'hash' => 'tuition_own_overview',
			'action' => $request->input('action')
		);

		$monitoringService = new \Gui2\Service\MonitoringService($data);

		$response = $next($request);

		$monitoringService->save();

		return $response;
	}

}
