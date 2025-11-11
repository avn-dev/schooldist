<?php

namespace Communication\Exceptions\Sms;

class SmsGatewayException extends \RuntimeException
{
	public function __construct(
		private string $response
	) {
		parent::__construct(sprintf('SMS Gateway returned error [%s]', $this->response));
	}

	public function getResponse(): string
	{
		return $this->response;
	}

	public function toReadableString(): string
	{
		/* Array enthält alle Fehler, die vom SMS-Gateway zurückgegeben werden,
		 *	die an Übersetzungen in der Communication Dialog Data übersetzt sind.
		 */
		$definedSMSErrors = [
			'NO_CREDITS_LEFT', // Gateway: Keine Credits mehr auf CORE
			'WRONG_SENDER_FORMAT', // Gateway: Absender entspricht nicht RegEx (sollte eigentlich nicht vorkommen, da schon bei Eingabe geprüft)
			'NO_SENDER_SMS' // s.u. / SubObject: Kein SMS-Absender vorhanden
		];

		$response = $this->response;
		if(!in_array($this->response, $definedSMSErrors)) {
			$response = 'SERVER_ERROR_SMS';
		}

		$readable = \Ext_TC_Communication_SMS_Gateway::convertErrorKeyToMessage($response);

		return $readable;
	}
}