<?php

namespace TsEdvisor\Controller;

use Illuminate\Http\Request;
use TsEdvisor\Service\Api;
use TsEdvisor\Service\Sync;

/**
 * @link https://docs.edvisor.io/#webhooks
 */
class WebhookController extends \Illuminate\Routing\Controller
{
	public function __invoke(Request $request)
	{
		Api::createLogger()->info('Incoming webhook: '.$request->input('type', '?'), $request->all());

		try {
			$this->handleWebhook($request);
		} catch (\Throwable $e) {
			Api::createLogger()->error('Error while processing webhook: '.$e->getMessage(), ['trace' => $e->getTraceAsString(), 'request' => $request->all()]);
			return response('ERROR', 500);
		}

		return response('OK');
	}

	private function handleWebhook(Request $request)
	{
		switch ($request->input('type')) {
			case 'studentEnrollment:schoolProcessing':
			case 'studentEnrollment:sent':
				\DB::begin(__METHOD__);
				(new Sync())->syncEnrollment($request->input('data.after.studentEnrollmentId'));
				\DB::commit(__METHOD__);
				break;
			default:
				Api::createLogger()->warning('Unknown webhook type: '.$request->input('type'));
				break;
		}
	}
}