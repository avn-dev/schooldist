<?php

namespace TsFrontend\Interfaces\PaymentProvider;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

interface WebhookCapture {

	/**
	 * Zahlung wird über Webhook abgeschlossen und existiert vorher nur flüchtig im System
	 *
	 * Funktion liefert direkt eine Response für den entsprechenden Webservice. Jeder sonstige Fehler endet ansonsten in einem Error 500.
	 *
	 * $this->school ist nicht gesetzt!
	 *
	 * @param Collection $data
	 * @return Response
	 */
	public function captureByWebhook(Collection $data): Response;

}
