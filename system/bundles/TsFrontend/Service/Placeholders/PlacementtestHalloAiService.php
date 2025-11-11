<?php

namespace TsFrontend\Service\Placeholders;

use TsTuition\Service\HalloAiApiService;

class PlacementtestHalloAiService extends PlacementtestService
{
	protected function generateUrl(\Ext_Thebing_School $school, \Ext_Thebing_Placementtests_Results $result): string
	{
		$halloAiApi = new HalloAiApiService();
		$response = $halloAiApi->getAssessmentUrl($result);
		return $response['assessmentUrl'];
	}
}