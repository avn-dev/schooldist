<?php

namespace FideloSoftware\Verifactu;

use FideloSoftware\Verifactu\ComplexTypes\RegistroAlta;
use FideloSoftware\Verifactu\ComplexTypes\RegistroAnulacion;

class Helper
{
	public static function formatCurrency(float $num): string
	{
		return number_format($num, 2, '.', '');
	}

	public static function formatDateTime(string $date): string
	{
		return date('d-m-Y', strtotime($date));
	}

	public static function sanitizeString(string $inputString) :string
	{
		$trimmedString = trim($inputString);
		return str_replace(['&', '<'], ['&amp;', '&lt;'], $trimmedString);
	}

	public static function getFingerprint(RegistroAnulacion|RegistroAlta &$registroFactura): string
	{
		if ($registroFactura instanceof RegistroAnulacion) {
			return strtoupper(hash('sha256',
				'IDEmisorFacturaAnulada=' . trim($registroFactura->IDFactura->IDEmisorFacturaAnulada, ' ') .
				'&NumSerieFacturaAnulada=' . trim($registroFactura->IDFactura->NumSerieFacturaAnulada, ' ') .
				'&FechaExpedicionFacturaAnulada=' . trim($registroFactura->IDFactura->FechaExpedicionFacturaAnulada, ' ') .
				'&Huella=' . trim($registroFactura->Encadenamiento->RegistroAnterior?->Huella ?? '', ' ') .
				'&FechaHoraHusoGenRegistro=' . trim($registroFactura->FechaHoraHusoGenRegistro, ' ')
			));
		} else {
			return strtoupper(hash('sha256',
				'IDEmisorFactura=' . trim($registroFactura->IDFactura->IDEmisorFactura, ' ') .
				'&NumSerieFactura=' . trim($registroFactura->IDFactura->NumSerieFactura, ' ') .
				'&FechaExpedicionFactura=' . trim($registroFactura->IDFactura->FechaExpedicionFactura, ' ') .
				'&TipoFactura=' . $registroFactura->TipoFactura->value .
				'&CuotaTotal=' . trim($registroFactura->CuotaTotal, ' ') .
				'&ImporteTotal=' . trim($registroFactura->ImporteTotal, ' ') .
				'&Huella=' . trim($registroFactura->Encadenamiento->RegistroAnterior?->Huella ?? '', ' ') .
				'&FechaHoraHusoGenRegistro=' . trim($registroFactura->FechaHoraHusoGenRegistro, ' ')
			));
		}
	}

	public static function objectToXml($data, $rootElement = '<root/>'): string
	{
		// Create a new SimpleXMLElement instance
		$xml = new \SimpleXMLElement($rootElement);

		// A helper function to recursively process each element
		$recursiveMapper = function ($data, $xml) use (&$recursiveMapper) {
			foreach ($data as $key => $value) {
				// If variable is an object or array, recursively add its children
				if (is_object($value) || is_array($value)) {
					$child = $xml->addChild($key);
					$recursiveMapper($value, $child);
				} else {
					// Otherwise, assign the value directly
					$xml->addChild($key, htmlentities($value, ENT_QUOTES | ENT_XML1));
				}
			}
		};

		// Call the recursive function
		$recursiveMapper($data, $xml);

		// Return the XML as a string
		return $xml->asXML();
	}

	public static function xmlNodeToArray($node): array|string
	{
		$result = [];
		if ($node->hasChildNodes()) {
			foreach ($node->childNodes as $child) {
				if ($child->nodeType === XML_ELEMENT_NODE) {
					$result[$child->nodeName] = self::xmlNodeToArray($child);
				} elseif ($child->nodeType === XML_TEXT_NODE) {
					return trim($child->nodeValue);
				}
			}
		}
		return $result;
	}
}
