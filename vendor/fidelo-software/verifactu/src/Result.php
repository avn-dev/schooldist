<?php

namespace FideloSoftware\Verifactu;

class Result
{
	public ?string $response = null;

	public ?string $request = null;

	public int $status = 0;

	public ?string $error = null;

	/**
	 * @return array
	 */
	public function parseResponse(): array
	{
		$doc = new \DOMDocument();
		$doc->loadXML($this->response);
		$xpath = new \DOMXPath($doc);
		$result = [];

		$csvNode = $xpath->query('//CSV')->item(0);
		if ($csvNode) {
			$result['csv'] = $csvNode->nodeValue;
		}

		$headerNode = $xpath->query('//Cabecera')->item(0);
		if ($headerNode) {
			$result['header'] = Helper::xmlNodeToArray($headerNode);
		}

		$waitNode = $xpath->query('//TiempoEsperaEnvio')->item(0);
		if ($waitNode) {
			$result['waitTime'] = $waitNode->nodeValue;
		}

		$statusNode = $xpath->query('//EstadoEnvio')->item(0);
		if ($statusNode) {
			$result['submissionStatus'] = $statusNode->nodeValue;
		}

		$dataNode = $xpath->query('//DatosPresentacion')->item(0);
		if ($dataNode) {
			$result['submissionData'] = Helper::xmlNodeToArray($dataNode);
		}

		$result['responseLines'] = [];
		foreach ($xpath->query('//RespuestaLinea') as $lineNode) {
			$lineXml = Helper::xmlNodeToArray($lineNode);
			if (empty($lineXml['DescripcionErrorRegistro'])) {
				if (isset($lineXml['CodigoErrorRegistro'])) {
					$lineXml['DescripcionErrorRegistro'] = Error::$errorCodes[$line['CodigoErrorRegistro']] ?? 'Unknown error';
				}
			}
			$accepted = match($lineXml['EstadoRegistro']) {
				'Aceptado' => true,
				'AceptadoConErrores' => true,
				'Rechazado' => false,
				'RegistradoParcialmente' => false,
				'Pendiente' => false,
			};
			$line = [
				'xml' => $lineXml,
				'accepted' => $accepted
			];
			$result['responseLines'][] = $line;
		}

		return $result;
	}
}
