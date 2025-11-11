<?php

namespace TsFrontend\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TsFrontend\Factory\PaymentFactory;
use TsFrontend\Interfaces\PaymentProvider\WebhookCapture;

class WebhookController extends Controller {

	public function payment(Request $request, string $handler): Response {

		try {
			$handler = (new PaymentFactory())->make($handler);
			$logger = $handler->createLogger();
		} catch (\InvalidArgumentException) {
			return response('Bad request', 400);
		}

//		$logger->info(__METHOD__.': Webhook incoming', [
//			'handler' => get_class($handler),
//			'request' => $request->all(),
//			'server' => $request->server->all()
//		]);

		if (!$handler instanceof WebhookCapture) {
			$logger->error(__METHOD__.': Handler is not of type WebhookCapture');
			return response('Bad request', 400);
		}

		try {
			return $handler->captureByWebhook(collect($request->request->all()));
		} catch (\Throwable $e) {
			$logger->error('Payment webhook error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'request' => $request->all()]);
			return response('Webhook error', 500);
		}

	}

}
